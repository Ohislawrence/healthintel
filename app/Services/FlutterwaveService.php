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
        $this->secretKey = config('services.flutterwave.secret_key');
        $this->baseUrl = config('services.flutterwave.base_url', 'https://api.flutterwave.com');
    }

    public function isConfigured(): bool
    {
        return !empty($this->secretKey);
    }

    /**
     * Initialize a transaction via Flutterwave v3 Payments API.
     * POST /v3/payments
     * Returns the authorization URL (redirect link) for the user to complete payment.
     */
    public function initialize(Payment $payment, User $user, string $callbackUrl): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->timeout(20)->post($this->baseUrl . '/v3/payments', [
            'tx_ref' => $payment->reference,
            'amount' => $payment->amountNaira(),
            'currency' => $payment->currency,
            'redirect_url' => $callbackUrl,
            'payment_options' => 'card',
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
            Log::error('Flutterwave v3 initialize failed', [
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

        if (($body['status'] ?? null) !== 'success') {
            Log::error('Flutterwave v3 initialize returned error', ['body' => $body]);
            $payment->update([
                'provider_response' => $body,
                'status' => 'failed',
            ]);
            return null;
        }

        $payment->update([
            'provider_response' => $body,
            'provider_reference' => $body['data']['id'] ?? $body['data']['tx_ref'] ?? null,
        ]);

        // v3 returns 'data.link' for the hosted payment page
        return $body['data']['link'] ?? null;
    }

    /**
     * Verify a transaction by its transaction ID.
     * Uses the Flutterwave v3 GET /v3/transactions/{id}/verify endpoint.
     */
    public function verify(string $transactionId): array
    {
        if (!$this->isConfigured()) {
            return ['status' => 'error', 'message' => 'Flutterwave not configured'];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
        ])->timeout(15)->get($this->baseUrl . '/v3/transactions/' . $transactionId . '/verify');

        return $response->json() ?? [];
    }

    /**
     * Validate Flutterwave webhook signature using HMAC-SHA256.
     *
     * Computes HMAC-SHA256 over the raw request body using the secret hash
     * and compares it against the verif-hash header value.
     */
    public function isValidWebhook(string $payload, string $signature): bool
    {
        $secretHash = config('services.flutterwave.secret_hash');
        if (empty($secretHash) || empty($signature)) {
            return false;
        }

        $hash = hash_hmac('sha256', $payload, $secretHash);
        return hash_equals($hash, $signature);
    }
}