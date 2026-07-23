<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class PaystackService
{
    private ?string $secretKey = null;
    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key') ?: null;
        $this->baseUrl = config('services.paystack.base_url', 'https://api.paystack.co');
    }

    /**
     * Check if the service is properly configured.
     */
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
        ])->timeout(20)->post($this->baseUrl . '/transaction/initialize', [
            'email' => $user->email,
            'amount' => $payment->amount_kobo, // already in kobo
            'currency' => $payment->currency,
            'reference' => $payment->reference,
            'callback_url' => $callbackUrl,
            'metadata' => [
                'payment_id' => $payment->id,
                'user_id' => $user->id,
            ],
        ]);

        if (!$response->successful()) {
            $payment->update([
                'provider_response' => $response->json(),
                'status' => 'failed',
            ]);
            return null;
        }

        $body = $response->json();
        $payment->update([
            'provider_response' => $body,
            'provider_reference' => $body['data']['reference'] ?? null,
        ]);

        return $body['data']['authorization_url'] ?? null;
    }

    /**
     * Verify a transaction by reference.
     */
    public function verify(string $reference): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
        ])->timeout(15)->get($this->baseUrl . '/transaction/verify/' . $reference);

        return $response->json() ?? [];
    }

    /**
     * Validate Paystack webhook signature.
     */
    public function isValidWebhook(string $payload, string $signature): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }
        $computed = hash_hmac('sha512', $payload, $this->secretKey);
        return hash_equals($computed, $signature);
    }
}

