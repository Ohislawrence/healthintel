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
     * Initialize a transaction via Flutterwave v4 API (POST /charges).
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
        ])->timeout(20)->post($this->baseUrl . '/charges', [
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
            Log::error('Flutterwave v4 initialize failed', [
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
            Log::error('Flutterwave v4 initialize returned error', ['body' => $body]);
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

        // v4 returns 'data.link' for the hosted payment page
        return $body['data']['link'] ?? null;
    }

    /**
     * Verify a transaction by its Flutterwave charge ID.
     * In v4, the charge ID is returned in data.id from the initialize/charge response.
     * We use the provider_reference (charge ID) or fall back to tx_ref lookup.
     */
    public function verify(string $reference): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        // v4: GET /charges/{id} — use the charge ID stored as provider_reference
        $payment = Payment::where('reference', $reference)->first();
        $chargeId = $payment?->provider_reference;

        if ($chargeId) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->timeout(15)->get($this->baseUrl . '/charges/' . $chargeId);

            return $response->json() ?? [];
        }

        // Fallback: try to find by tx_ref via listing charges (if charge ID is not stored)
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
        ])->timeout(15)->get($this->baseUrl . '/charges', [
            'tx_ref' => $reference,
        ]);

        $body = $response->json() ?? [];
        if (!empty($body['data']) && is_array($body['data'])) {
            $charge = $body['data'][0] ?? $body['data'];
            if (is_array($charge) && !isset($charge[0])) {
                return $charge;
            }
            return $charge ?? $body;
        }

        return $body;
    }

    /**
     * Validate Flutterwave webhook signature using secret hash.
     *
     * The verif-hash header value IS the secret hash itself — we do a direct
     * comparison, not an HMAC computation over the payload.
     */
    public function isValidWebhook(string $payload, string $signature): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $secretHash = config('services.flutterwave.secret_hash');
        if (empty($secretHash)) {
            // Fall back to secret key if secret_hash is not set
            $secretHash = $this->secretKey;
        }

        return !empty($secretHash)
            && !empty($signature)
            && hash_equals($secretHash, $signature);
    }
}