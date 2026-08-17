<?php

namespace App\Services;

use App\Models\CreditLedger;
use App\Models\CreditPackage;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        private PaystackService $paystack,
        private FlutterwaveService $flutterwave,
        private CreditService $credits,
        private ReferralProgramService $referralProgram,
    ) {}

    public function getActiveGateway(): string
    {
        return 'flutterwave';
    }

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
                Log::warning('Flutterwave not configured — skipping payment initialization.');
                $payment->update(['status' => 'failed']);
                return null;
            }
            return $this->flutterwave->initialize($payment, $user, $callbackUrl);
        }

        if (!$this->paystack->isConfigured()) {
            Log::warning('Paystack not configured — skipping payment initialization.');
            $payment->update(['status' => 'failed']);
            return null;
        }

        return $this->paystack->initialize($payment, $user, $callbackUrl);
    }

    /**
     * Verify a payment.
     * 
     * @param string $reference Our internal transaction reference (e.g. LD-...)
     * @param string|null $flwTransactionId Optional numeric Flutterwave transaction ID passed from callback query params
     * @param array|null $preVerifiedResult Pre-fetched verification payload from webhook
     */
    public function verify(string $reference, ?string $flwTransactionId = null, ?array $preVerifiedResult = null): Payment
    {
        return DB::transaction(function () use ($reference, $flwTransactionId, $preVerifiedResult) {
            $payment = Payment::where('reference', $reference)->lockForUpdate()->firstOrFail();

            if ($payment->status === 'success') {
                return $payment;
            }

            // Always store/update provider_reference from callback query param
            if ($flwTransactionId) {
                $payment->provider_reference = $flwTransactionId;
            }

            // Perform verification lookup — prefer callback transaction ID over stored reference
            $result = $preVerifiedResult ?? match ($payment->provider) {
                'flutterwave' => $this->flutterwave->verify(
                    $flwTransactionId ?? $payment->provider_reference ?? $reference
                ),
                default => $this->paystack->verify($reference),
            };

            $payment->provider_response = array_merge($payment->provider_response ?? [], $result);

            // Amount and Currency validation
            $isSuccess = false;
            $wasCancelled = false;
            
            if ($payment->provider === 'flutterwave') {
                $flwData = $result['data'] ?? [];

                // The transaction status lives inside data.status — do NOT fall back to
                // the outer API response status ($result['status']) which is always
                // 'success' when the HTTP call itself returns 200 OK.
                $txStatus = $flwData['status'] ?? null;
                $chargedAmount = (float) ($flwData['amount'] ?? 0);
                $chargedCurrency = strtoupper($flwData['currency'] ?? '');

                $expectedAmount = $payment->amount_kobo / 100;
                $expectedCurrency = strtoupper($payment->currency);

                $wasCancelled = $txStatus === 'cancelled';

                if ($txStatus === null || $chargedAmount <= 0) {
                    // Verification response is missing required fields — do NOT credit
                    $isSuccess = false;
                } else {
                    $isSuccess = in_array($txStatus, ['successful', 'completed', 'success'])
                        && abs($chargedAmount - $expectedAmount) < 0.01
                        && $chargedCurrency === $expectedCurrency;
                }
            } else {
                $paystackStatus = $result['data']['status'] ?? null;
                $wasCancelled = in_array($paystackStatus, ['cancelled', 'abandoned'], true);
                $isSuccess = $paystackStatus === 'success';
            }

            if ($isSuccess) {
                $payment->status = 'success';
                $payment->paid_at = now();
                $payment->save();

                $this->grantCreditsForPayment($payment);
            } else {
                $payment->status = $wasCancelled ? 'cancelled' : 'failed';
                $payment->save();
            }

            return $payment;
        });
    }

    public function handleWebhook(array $payload, string $provider = 'paystack'): void
    {
        if ($provider === 'flutterwave') {
            $this->handleFlutterwaveWebhook($payload);
            return;
        }

        $this->handlePaystackWebhook($payload);
    }

    private function handlePaystackWebhook(array $payload): void
    {
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];
        $reference = $data['reference'] ?? null;

        if (!$reference) {
            return;
        }

        $payment = Payment::where('reference', $reference)->first();
        if ($payment) {
            $payment->update(['webhook_log' => $payload]);
        }

        if ($event === 'charge.success') {
            $this->verify($reference);
        }
    }

    private function handleFlutterwaveWebhook(array $payload): void
    {
        $eventType = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];
        $reference = $data['tx_ref'] ?? null;
        $transactionId = $data['id'] ?? null;

        if (!$reference) {
            return;
        }

        $payment = Payment::where('reference', $reference)->first();
        if ($payment) {
            $payment->update([
                'provider_reference' => $transactionId ?? $payment->provider_reference,
                'webhook_log' => $payload,
            ]);
        }

        if ($eventType === 'charge.completed' || ($data['status'] ?? null) === 'successful') {
            if (!$transactionId) {
                Log::warning('Flutterwave webhook received without transaction ID.', ['reference' => $reference]);
                return;
            }

            $result = $this->flutterwave->verify($transactionId);

            $verifiedStatus = $result['data']['status'] ?? $result['status'] ?? null;

            if (!in_array($verifiedStatus, ['successful', 'completed', 'success'])) {
                Log::warning('Flutterwave webhook server-side verification failed.', [
                    'reference' => $reference,
                    'webhook_status' => $data['status'] ?? null,
                    'verify_status' => $verifiedStatus
                ]);
                return;
            }

            $this->verify(reference: $reference, flwTransactionId: (string) $transactionId, preVerifiedResult: $result);
        }
    }

    private function grantCreditsForPayment(Payment $payment): void
    {
        $user = $payment->user;
        $package = $payment->purchasable;

        if (!$package instanceof CreditPackage) {
            return;
        }

        $exists = CreditLedger::where('user_id', $user->id)
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

        $this->referralProgram->processCommission($payment);
    }
}