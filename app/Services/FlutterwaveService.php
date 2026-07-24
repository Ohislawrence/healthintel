<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlutterwaveService
{
    private ?string $secretKey = null;
    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.flutterwave.secret_key')
            ?: ($_ENV['FLUTTERWAVE_SECRET_KEY'] ?? getenv('FLUTTERWAVE_SECRET_KEY'))
            ?: $this->readEnvFile('FLUTTERWAVE_SECRET_KEY')
            ?: null;
        $this->baseUrl = config('services.flutterwave.base_url', 'https://api.flutterwave.com/v3');
    }

    private function readEnvFile(string $key): ?string
    {
        $path = base_path('.env');
        if (!file_exists($path)) {
            return null;
        }
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
                return $value !== '' ? $value : null;
            }
        }
        return null;
    }

    public function isConfigured(): bool
    {
        return !empty($this->secretKey);
    }

    /**
     * Initialize a transaction and return the authorization URL.
     */
    public function initialize(Payment $payment, User $user, string $callbackUrl): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->timeout(20)->post($this->baseUrl . '/payments', [
            'tx_ref' => $payment->reference,
            'amount' => $payment->amountNaira(),
            'currency' => $payment->currency,
            'redirect_url' => $callbackUrl,
            'customer' => [
                'email' => $user->email,
                'name' => $user->name ?? $user->email,
            ],
            'meta' => [
                'payment_id' => $payment->id,
                'user_id' => $user->id,
            ],
            'customizations' => [
                'title' => 'HealthIntel Credits',
                'logo' => '',
            ],
        ]);

        if (!$response->successful()) {
            Log::error('Flutterwave initialize failed', [
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

        if ($body['status'] !== 'success') {
            Log::error('Flutterwave initialize returned error', ['body' => $body]);
            $payment->update([
                'provider_response' => $body,
                'status' => 'failed',
            ]);
            return null;
        }

        $payment->update([
            'provider_response' => $body,
            'provider_reference' => $body['data']['tx_ref'] ?? null,
        ]);

        return $body['data']['link'] ?? null;
    }

    /**
     * Verify a transaction by reference (transaction ID).
     */
    public function verify(string $reference): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
        ])->timeout(15)->get($this->baseUrl . '/transactions/verify_by_reference', [
            'tx_ref' => $reference,
        ]);

        return $response->json() ?? [];
    }

    /**
     * Validate Flutterwave webhook signature using secret hash.
     */
    public function isValidWebhook(string $payload, string $signature): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }
        $computed = hash_hmac('sha256', $payload, $this->secretKey);
        return hash_equals($computed, $signature);
    }
}