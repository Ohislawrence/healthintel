<?php

namespace App\Services;

use App\Models\CreditPackage;
use App\Models\Payment;
use App\Models\User;

class PaymentService
{
    public function __construct(
        private PaystackService $paystack,
        private CreditService $credits,
    ) {}

    /**
     * Create a payment record, initialize with Paystack, return auth URL.
     */
    public function initialize(User $user, CreditPackage $package, string $callbackUrl): ?string
    {
        if (!$this->paystack->isConfigured()) {
            \Illuminate\Support\Facades\Log::warning('Paystack not configured — skipping payment initialization.');
            return null;
        }

        $payment = Payment::create([
            'user_id' => $user->id,
            'purchasable_type' => CreditPackage::class,
            'purchasable_id' => $package->id,
            'provider' => 'paystack',
            'reference' => 'LD-' . now()->format('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
            'amount_kobo' => $package->price_kobo,
            'currency' => $package->currency,
            'status' => 'pending',
        ]);

        return $this->paystack->initialize($payment, $user, $callbackUrl);
    }

    /**
     * Verify a payment and grant credits on success.
     */
    public function verify(string $reference): Payment
    {
        $payment = Payment::where('reference', $reference)->firstOrFail();

        // Already processed?
        if ($payment->status === 'success') {
            return $payment;
        }

        $result = $this->paystack->verify($reference);

        $payment->update([
            'provider_response' => array_merge($payment->provider_response ?? [], $result),
            'webhook_log' => $result,
        ]);

        if (($result['data']['status'] ?? null) === 'success') {
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
     * Handle Paystack webhook event.
     */
    public function handleWebhook(array $payload): void
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
    }
}

