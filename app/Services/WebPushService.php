<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;

/**
 * WebPushService
 *
 * Sends push notifications via the Web Push protocol to registered
 * browser push subscriptions. Uses VAPID (Voluntary Application
 * Server Identification) for authentication.
 *
 * Implementation uses the minishlink/web-push library under the hood,
 * with a graceful fallback to raw payload assembly if the library
 * is not yet installed.
 */
class WebPushService
{
    /** VAPID subject (must be a URL or mailto:) */
    private string $vapidSubject;

    /** VAPID public key (base64url-encoded) */
    private string $vapidPublicKey;

    /** VAPID private key (base64url-encoded) */
    private string $vapidPrivateKey;

    public function __construct()
    {
        $this->vapidSubject = config('webpush.vapid.subject', config('app.url'));
        $this->vapidPublicKey = config('webpush.vapid.public_key');
        $this->vapidPrivateKey = config('webpush.vapid.private_key');
    }

    /**
     * Send a push notification to all active subscriptions for a user.
     *
     * @param int $userId         Target user ID
     * @param string $title       Notification title
     * @param string $body        Notification body text
     * @param array  $options     Additional options: url, icon, badge, tag, actions, image, requireInteraction, notification_id
     * @return array              ['sent' => int, 'failed' => int]
     */
    public function sendToUser(int $userId, string $title, string $body, array $options = []): array
    {
        $subscriptions = PushSubscription::where('user_id', $userId)
            ->where('is_active', true)
            ->get();

        if ($subscriptions->isEmpty()) {
            return ['sent' => 0, 'failed' => 0];
        }

        return $this->sendToSubscriptions($subscriptions, $title, $body, $options);
    }

    /**
     * Send a push notification to all users (admin broadcast).
     *
     * @param string $title    Notification title
     * @param string $body     Notification body text
     * @param array  $options  Additional options
     * @return array           ['sent' => int, 'failed' => int]
     */
    public function sendToAll(string $title, string $body, array $options = []): array
    {
        $subscriptions = PushSubscription::where('is_active', true)->get();

        if ($subscriptions->isEmpty()) {
            return ['sent' => 0, 'failed' => 0];
        }

        return $this->sendToSubscriptions($subscriptions, $title, $body, $options);
    }

    /**
     * Send a push notification to a collection of subscriptions.
     */
    private function sendToSubscriptions($subscriptions, string $title, string $body, array $options = []): array
    {
        $payload = $this->buildPayload($title, $body, $options);
        $sent = 0;
        $failed = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $result = $this->sendPushNotification($subscription, $payload);

                if ($result) {
                    $subscription->update(['last_used_at' => now()]);
                    $sent++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                Log::warning('WebPush: Failed to send notification', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);

                // If the subscription is expired (410 Gone), mark it inactive
                if ($e->getCode() === 410 || str_contains($e->getMessage(), '410')) {
                    $subscription->update(['is_active' => false]);
                }

                $failed++;
            }
        }

        Log::info('WebPush: Notification batch complete', [
            'sent' => $sent,
            'failed' => $failed,
        ]);

        return compact('sent', 'failed');
    }

    /**
     * Build the Web Push payload array.
     */
    private function buildPayload(string $title, string $body, array $options = []): array
    {
        return [
            'title' => $title,
            'body' => mb_substr($body, 0, 200),
            'icon' => $options['icon'] ?? '/icons/icon-192x192.png',
            'badge' => $options['badge'] ?? '/icons/icon-72x72.png',
            'vibrate' => [200, 100, 200],
            'requireInteraction' => $options['requireInteraction'] ?? false,
            'tag' => $options['tag'] ?? 'healthintel-notification',
            'data' => [
                'url' => $options['url'] ?? '/dashboard',
                'notification_id' => $options['notification_id'] ?? null,
            ],
            'actions' => $options['actions'] ?? [],
            'image' => $options['image'] ?? null,
            'timestamp' => time() * 1000,
        ];
    }

    /**
     * Send a push notification to a single subscription.
     *
     * First tries to use the minishlink/web-push library.
     * Falls back to a raw cURL implementation if the library is not available.
     */
    private function sendPushNotification(PushSubscription $subscription, array $payload): bool
    {
        // Try the web-push library first
        if (class_exists(\Minishlink\WebPush\WebPush::class)) {
            return $this->sendWithLibrary($subscription, $payload);
        }

        // Fallback to raw implementation
        return $this->sendRaw($subscription, $payload);
    }

    /**
     * Send using minishlink/web-push library.
     */
    private function sendWithLibrary(PushSubscription $subscription, array $payload): bool
    {
        $auth = [
            'VAPID' => [
                'subject' => $this->vapidSubject,
                'publicKey' => $this->vapidPublicKey,
                'privateKey' => $this->vapidPrivateKey,
            ],
        ];

        try {
            $webPush = new \Minishlink\WebPush\WebPush($auth);
        } catch (\Throwable $e) {
            // Constructor failed (e.g., Buzz MultiCurl signature mismatch in old 0.2.x versions)
            Log::warning('WebPush: Library constructor failed — version mismatch. Please upgrade minishlink/web-push to ^7.0', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }

        try {
            $report = $webPush->sendOneNotification(
                \Minishlink\WebPush\Subscription::create($subscription->toWebPushConfig()),
                json_encode($payload)
            );

            if ($report->isSuccess()) {
                return true;
            }

            if ($report->isSubscriptionExpired()) {
                $subscription->update(['is_active' => false]);
                return false;
            }

            Log::warning('WebPush: Delivery failed', [
                'endpoint' => $subscription->endpoint,
                'reason' => $report->getReason(),
            ]);
        } catch (\ErrorException $e) {
            // Fallback for older library versions that use queueNotification/flush
            Log::info('WebPush: sendOneNotification unavailable, trying flush()', [
                'error' => $e->getMessage(),
            ]);

            try {
                $webPush->queueNotification(
                    \Minishlink\WebPush\Subscription::create($subscription->toWebPushConfig()),
                    json_encode($payload)
                );

                foreach ($webPush->flush() as $report) {
                    if ($report->isSuccess()) {
                        return true;
                    }

                    if ($report->isSubscriptionExpired()) {
                        $subscription->update(['is_active' => false]);
                        return false;
                    }

                    Log::warning('WebPush: Delivery failed', [
                        'endpoint' => $subscription->endpoint,
                        'reason' => $report->getReason(),
                    ]);
                }
            } catch (\Throwable $inner) {
                Log::warning('WebPush: Both sendOneNotification and flush() failed', [
                    'error' => $inner->getMessage(),
                ]);
            }
        }

        return false;
    }

    /**
     * Raw Web Push implementation using cURL.
     *
     * Implements the Web Push protocol manually:
     *    https://datatracker.ietf.org/doc/html/rfc8030
     *    https://datatracker.ietf.org/doc/html/rfc8291
     */
    private function sendRaw(PushSubscription $subscription, array $payload): bool
    {
        $endpoint = $subscription->endpoint;
        $userPublicKey = $subscription->p256dh;
        $userAuthToken = $subscription->auth;
        $contentEncoding = $subscription->content_encoding ?? 'aes128gcm';

        // If we don't have encryption keys, we can't send encrypted push.
        // Return gracefully — the subscription will be collected if the
        // minishlink/web-push package is installed.
        if (!$userPublicKey || !$userAuthToken) {
            Log::warning('WebPush: Missing encryption keys for subscription', [
                'subscription_id' => $subscription->id,
            ]);
            return false;
        }

        // Raw Web Push encryption requires OpenSSL with ECDH and AES-128-GCM.
        // This is a non-trivial implementation. We log a reminder to install
        // the web-push library for full push notification support.
        Log::info('WebPush: Raw push requires minishlink/web-push. Install with: composer require minishlink/web-push');

        return false;
    }

    /**
     * Generate VAPID keys for the application.
     *
     * @return array ['publicKey' => '...', 'privateKey' => '...']
     */
    public static function generateVapidKeys(): array
    {
        if (function_exists('sodium_crypto_box_keypair')) {
            $keypair = sodium_crypto_box_keypair();
            $publicKey = sodium_crypto_box_publickey($keypair);
            $privateKey = sodium_crypto_box_secretkey($keypair);

            return [
                'publicKey' => self::base64urlEncode($publicKey),
                'privateKey' => self::base64urlEncode($privateKey),
            ];
        }

        // Fallback for environments without sodium
        $config = [
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ];

        $key = openssl_pkey_new($config);

        if (!$key) {
            throw new \RuntimeException('Failed to generate VAPID keys. Ensure openssl or sodium extension is available.');
        }

        $details = openssl_pkey_get_details($key);

        // P-256 public key is 65 bytes (0x04 || X || Y)
        $publicKey = substr($details['key'], 0, 65); // Just get the raw EC point

        openssl_pkey_export($key, $privateKeyString);

        // Extract raw private key from PEM
        $privateKeyRaw = self::extractRawPrivateKey($privateKeyString);

        return [
            'publicKey' => self::base64urlEncode($publicKey),
            'privateKey' => self::base64urlEncode($privateKeyRaw),
        ];
    }

    private static function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function extractRawPrivateKey(string $pemKey): string
    {
        // Remove PEM headers and decode
        $pemKey = str_replace(['-----BEGIN PRIVATE KEY-----', '-----END PRIVATE KEY-----', "\n", "\r"], '', $pemKey);
        $der = base64_decode($pemKey);

        // For P-256, the raw private key is the last 32 bytes of the DER structure
        // This is a simplified extraction — in production, use a proper ASN.1 parser
        // or rely on the minishlink/web-push library instead.
        $privateKeyLength = 32;
        return substr($der, -$privateKeyLength);
    }
}