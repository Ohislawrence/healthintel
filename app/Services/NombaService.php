<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NombaService
{
    private string $baseUrl;
    private string $tokenPath;
    private string $initializePath;
    private string $verifyPath;
    private ?string $secretKey;
    private ?string $clientId;
    private ?string $accountId;
    private ?string $webhookSecret;
    private ?string $webhookHeader;

    /** Cached OAuth2 access token (in-memory) */
    private ?string $accessToken = null;
    private ?int $accessTokenExpiresAt = null;

    public function __construct()
    {
        // Resolve via config() first, then real env vars, then the .env file directly.
        // This guards against a stale config cache built before the NOMBA_* keys existed.
        $this->baseUrl = rtrim($this->env('NOMBA_BASE_URL', config('services.nomba.base_url', 'https://api.nomba.com')), '/');
        $this->tokenPath = $this->env('NOMBA_TOKEN_PATH', config('services.nomba.token_path', '/v1/auth/token/issue'));
        $this->initializePath = $this->env('NOMBA_INITIALIZE_PATH', config('services.nomba.initialize_path', '/v1/checkout/order'));
        $this->verifyPath = $this->env('NOMBA_VERIFY_PATH', config('services.nomba.verify_path', '/v1/checkout/transaction'));
        $this->secretKey = $this->env('NOMBA_SECRET_KEY', config('services.nomba.secret_key'));
        $this->clientId = $this->env('NOMBA_CLIENT_ID', config('services.nomba.client_id'));
        $this->accountId = $this->env('NOMBA_PUBLIC_KEY', config('services.nomba.account_id'));
        $this->webhookSecret = $this->env('NOMBA_WEBHOOK_SECRET', config('services.nomba.webhook_secret'));
        $this->webhookHeader = $this->env('NOMBA_WEBHOOK_HEADER', config('services.nomba.webhook_header', 'x-nomba-signature'));
    }

    /**
     * Resolve an env/config value with fallback to the .env file directly.
     * Needed on production where a stale config cache hides newly added keys.
     */
    private function env(string $key, ?string $default = null): ?string
    {
        $configPath = match ($key) {
            'NOMBA_BASE_URL' => 'services.nomba.base_url',
            'NOMBA_TOKEN_PATH' => 'services.nomba.token_path',
            'NOMBA_INITIALIZE_PATH' => 'services.nomba.initialize_path',
            'NOMBA_VERIFY_PATH' => 'services.nomba.verify_path',
            'NOMBA_SECRET_KEY' => 'services.nomba.secret_key',
            'NOMBA_CLIENT_ID' => 'services.nomba.client_id',
            'NOMBA_PUBLIC_KEY' => 'services.nomba.account_id',
            'NOMBA_WEBHOOK_SECRET' => 'services.nomba.webhook_secret',
            'NOMBA_WEBHOOK_HEADER' => 'services.nomba.webhook_header',
            default => null,
        };

        if ($configPath && ($value = config($configPath))) {
            return $value;
        }

        if ($value = ($_ENV[$key] ?? getenv($key))) {
            return $value;
        }

        $path = base_path('.env');
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                if (str_starts_with($line, $key . '=')) {
                    $value = trim(substr($line, strlen($key) + 1));
                    if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                        (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                        $value = substr($value, 1, -1);
                    }
                    return $value !== '' ? $value : $default;
                }
            }
        }

        return $default;
    }

    /**
     * Check if the service is properly configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->secretKey);
    }

    /**
     * Obtain (and cache) an OAuth2 client-credentials access token.
     */
    private function getAccessToken(): ?string
    {
        if ($this->accessToken && $this->accessTokenExpiresAt && $this->accessTokenExpiresAt > (time() + 60)) {
            return $this->accessToken;
        }

        if (!$this->isConfigured()) {
            return null;
        }

        $headers = ['Content-Type' => 'application/json'];
        if ($this->accountId) {
            $headers['accountId'] = $this->accountId;
        }

        $response = Http::withHeaders($headers)
            ->timeout(20)
            ->post($this->baseUrl . $this->tokenPath, [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->secretKey,
            ]);

        if (!$response->successful()) {
            Log::error('Nomba token request failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            return null;
        }

        $body = $response->json();
        $token = $body['data']['access_token'] ?? null;

        if ($token) {
            $this->accessToken = $token;

            $expiresAt = $body['data']['expiresAt'] ?? null;
            $this->accessTokenExpiresAt = $expiresAt
                ? strtotime($expiresAt)
                : (time() + 3400); // docs show ~1h TTL (3600s); refresh a bit early
        }

        return $token;
    }

    /**
     * Initialize a transaction and return the checkout authorization URL.
     */
    public function initialize(Payment $payment, User $user, string $callbackUrl): ?string
    {
        $token = $this->getAccessToken();
        if (!$token) {
            Log::warning('Nomba access token unavailable — skipping payment initialization.');
            $payment->update(['status' => 'failed']);
            return null;
        }

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ];
        if ($this->accountId) {
            $headers['accountId'] = $this->accountId;
        }

        $response = Http::withHeaders($headers)
            ->timeout(20)
            ->post($this->baseUrl . $this->initializePath, [
                'order' => [
                    'orderReference' => $payment->reference,
                    'callbackUrl' => $callbackUrl,
                    'customerEmail' => $user->email,
                    'customerName' => $user->name ?? $user->email,
                    'amount' => $payment->amountNaira(),
                    'currency' => $payment->currency,
                ],
            ]);

        if (!$response->successful()) {
            Log::error('Nomba initialize failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            $payment->update([
                'provider_response' => $response->json(),
                'status' => 'failed',
            ]);
            return null;
        }

        $body = $response->json();

        if (($body['code'] ?? $body['status'] ?? null) === 'error' && ($body['data']['success'] ?? true) === false) {
            Log::error('Nomba initialize returned error', ['body' => $body]);
            $payment->update([
                'provider_response' => $body,
                'status' => 'failed',
            ]);
            return null;
        }

        $checkoutLink = $body['data']['checkoutLink'] ?? $body['checkoutLink'] ?? null;
        $orderReference = $body['data']['orderReference'] ?? $body['orderReference'] ?? null;

        $payment->update([
            'provider_response' => $body,
            'provider_reference' => $orderReference,
        ]);

        return $checkoutLink;
    }

    /**
     * Verify a transaction by order reference.
     *
     * Nomba returns the verification payload directly (HTTP 200 with
     * {code, description, data:{success, message, order, ...}}), which the
     * caller (PaymentService) inspects.
     */
    public function verify(string $orderReference): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $token = $this->getAccessToken();

        $headers = [];
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        if ($this->accountId) {
            $headers['accountId'] = $this->accountId;
        }

        // Nomba's create-order response returns a UUID in `data.orderReference` that
        // is actually the orderId (it matches the checkout link path and `order.orderId`
        // when fetched). In practice the lookup therefore needs idType=ORDER_ID.
        // We try ORDER_ID first, then fall back to ORDER_REFERENCE for environments
        // that follow the documented field naming.
        $lastBody = [];

        foreach (['ORDER_ID', 'ORDER_REFERENCE'] as $idType) {
            $response = Http::withHeaders($headers)
                ->timeout(15)
                ->get($this->baseUrl . $this->verifyPath, [
                    'idType' => $idType,
                    'id' => $orderReference,
                ]);

            if (!$response->successful()) {
                Log::error('Nomba verify failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
                return [];
            }

            $body = $response->json() ?? [];
            $lastBody = $body;

            $message = $body['data']['message'] ?? $body['description'] ?? '';
            $isNotFound = stripos($message, 'No Order') !== false
                || stripos($message, 'No transaction') !== false;

            if (!$isNotFound) {
                return $body;
            }
        }

        return $lastBody;
    }

    /**
     * Validate Nomba webhook signature (HMAC-SHA256 over raw body).
     */
    public function isValidWebhook(string $payload, string $signature): bool
    {
        if (!$this->isConfigured() || empty($this->webhookSecret) || empty($signature)) {
            return false;
        }

        $computed = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($computed, $signature);
    }
}