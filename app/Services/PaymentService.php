<?php

namespace App\Services;

use App\Models\CreditPackage;
use App\Models\Payment;
use App\Models\User;

class PaymentService
{
    public function __construct(
        private PaystackService $paystack,
        private FlutterwaveService $flutterwave,
        private CreditService $credits,
        private ReferralProgramService $referralProgram,
    ) {}

    /**
     * Get the active payment gateway.
     * Currently hardcoded to 'flutterwave' — deactivate others for now.
     */
    public function getActiveGateway(): string
    {
        return 'flutterwave';
    }

    /**
     * Create a payment record, initialize with the active gateway, return auth URL.
     */
    public function initialize(User $user, CreditPackage $package, string $callbackUrl): ?string
    {
        $gateway = $this->getActiveGateway();

        $providerName = $gateway === 'flutterwave' ? 'flutterwave' : 'paystack';

        $payment = Payment::create([
            'user_id' => $user->id,
            'purchasable_type' => CreditPackage::class,
            'purchasable_id' => $package->id,
            'provider' => $providerName,
            'reference' => 'LD-' . now()->format('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
            'amount_kobo' => $package->price_kobo,
            'currency' => $package->currency,
            'status' => 'pending',
        ]);

        if ($gateway === 'flutterwave') {
            if (!$this->flutterwave->isConfigured()) {
                \Illuminate\Support\Facades\Log::warning('Flutterwave not configured — skipping payment initialization.');
                $payment->update(['status' => 'failed']);
                return null;
            }
            return $this->flutterwave->initialize($payment, $user, $callbackUrl);
        }

        // Default: Paystack
        if (!$this->paystack->isConfigured()) {
            \Illuminate\Support\Facades\Log::warning('Paystack not configured — skipping payment initialization.');
            $payment->update(['status' => 'failed']);
            return null;
        }
        return $this->paystack->initialize($payment, $user, $callbackUrl);
    }

    /**
     * Verify a payment and grant credits on success.
     * Works for both Paystack and Flutterwave.
     *
     * @param array|null $preVerifiedResult Pass the already-fetched verification
     *                    result to avoid a redundant API call (used by webhooks).
     */
    public function verify(string $reference, ?array $preVerifiedResult = null): Payment
    {
        $payment = Payment::where('reference', $reference)->firstOrFail();

        // Already processed?
        if ($payment->status === 'success') {
            return $payment;
        }

        // Use the correct provider to verify
        // Flutterwave: use provider_reference (numeric charge ID), not tx_ref
        $result = $preVerifiedResult ?? match ($payment->provider) {
            'flutterwave' => $this->flutterwave->verify($payment->provider_reference ?? $reference),
            default => $this->paystack->verify($reference),
        };

        $payment->update([
            'provider_response' => array_merge($payment->provider_response ?? [], $result),
            'webhook_log' => $result,
        ]);

        // Detect success status from the provider response
        // Flutterwave v4: status can be 'successful' or 'completed'
        // Note: 'received' means pending bank confirmation — do NOT mark as success
        $isSuccess = match ($payment->provider) {
            'flutterwave' => ($result['data']['status'] ?? null) === 'successful'
                || ($result['data']['status'] ?? null) === 'completed'
                || ($result['status'] ?? null) === 'success',
            default => ($result['data']['status'] ?? null) === 'success',
        };

        if ($isSuccess) {
            $payment->update([
                'status' => 'success',
                'paid_at' => now(),
            ]);

            // Grant credits if not already granted
            $this->grantCreditsForPayment($payment);
        } else {
            $payment->update(['status' => 'failed']);
        }

        return $payment->fresh();
    }

    /**
     * Handle webhook event from either Paystack or Flutterwave.
     */
    public function handleWebhook(array $payload, string $provider = 'paystack'): void
    {
        if ($provider === 'flutterwave') {
            $this->handleFlutterwaveWebhook($payload);
            return;
        }

        $this->handlePaystackWebhook($payload);
    }

    /**
     * Handle Paystack webhook event.
     */
    private function handlePaystackWebhook(array $payload): void
    {
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];
        $reference = $data['reference'] ?? null;

        if (!$reference) {
            return;
        }

        if ($event === 'charge.success') {
            $this->verify($reference);
        }

        // Log webhook to the payment record
        $payment = Payment::where('reference', $reference)->first();
        if ($payment) {
            $payment->update(['webhook_log' => $payload]);
        }
    }

    /**
     * Handle Flutterwave v4 webhook event.
     * Performs server-side re-verification by calling the Flutterwave verify
     * endpoint before trusting the webhook payload, then passes the result
     * directly to verify() to avoid a redundant second API call.
     */
    private function handleFlutterwaveWebhook(array $payload): void
    {
        $eventType = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];
        $reference = $data['tx_ref'] ?? null;

        if (!$reference) {
            return;
        }

        // Persist the v4 charge ID from the webhook payload for future lookups
        $payment = Payment::where('reference', $reference)->first();
        if ($payment) {
            if (!empty($data['id']) && $payment->provider_reference === null) {
                $payment->update(['provider_reference' => $data['id']]);
            }
            $payment->update(['webhook_log' => $payload]);
        }

        // v4 webhook events: charge.completed, charge.failed, etc.
        if ($eventType === 'charge.completed' || ($data['status'] ?? null) === 'successful') {
            // Server-side re-verification: call Flutterwave to confirm
            $transactionId = $data['id'] ?? null;
            if (!$transactionId) {
                \Illuminate\Support\Facades\Log::warning(
                    'Flutterwave webhook received without transaction ID.',
                    ['reference' => $reference]
                );
                return;
            }
            $result = $this->flutterwave->verify($transactionId);

            $verifiedStatus = ($result['data']['status'] ?? null)
                ?? ($result['status'] ?? null);

            if (!in_array($verifiedStatus, ['successful', 'completed', 'success'])) {
                \Illuminate\Support\Facades\Log::warning(
                    'Flutterwave webhook received but server-side verification failed.',
                    ['reference' => $reference, 'webhook_status' => $data['status'] ?? null, 'verify_status' => $verifiedStatus]
                );
                return;
            }

            // Pass the pre-verified result to avoid a redundant second API call
            $this->verify($reference, $result);
        }
    }

    /**
     * Grant credits for a completed payment (idempotent).
     */
    private function grantCreditsForPayment(Payment $payment): void
    {
        $user = $payment->user;
        $package = $payment->purchasable;

        if (!$package instanceof CreditPackage) {
            return;
        }

        // Check if already credited
        $exists = \App\Models\CreditLedger::where('user_id', $user->id)
            ->where('reference_type', Payment::class)
            ->where('reference_id', $payment->id)
            ->exists();

        if ($exists) {
            return;
        }

        $this->credits->credit(
            user: $user,
            amount: $package->credits,
            actionType: 'credit_purchase',
            reference: $payment,
        );

        // Process referral commission for the referrer
        $this->referralProgram->processCommission($payment);
    }
}