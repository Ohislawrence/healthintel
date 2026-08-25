<?php

namespace App\Http\Controllers\Api;

use App\Models\CreditPackage;
use App\Services\PaymentService;
use App\Services\CreditService;
use App\Models\CreditLedger;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends BaseController
{
    public function __construct(
        private PaymentService $paymentService,
        private CreditService $creditService,
    ) {}

    /**
     * List available credit packages.
     */
    public function packages()
    {
        $packages = CreditPackage::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return $this->success(['packages' => $packages]);
    }

    /**
     * Initialize a payment for a package.
     */
    public function initialize(Request $request)
    {
        $validated = $request->validate([
            'package_id' => ['required', 'integer', 'exists:credit_packages,id'],
        ]);

        $user = $request->user();
        $package = CreditPackage::findOrFail($validated['package_id']);

        $callbackUrl = config('app.url') . '/payment/callback';
        $authUrl = $this->paymentService->initialize($user, $package, $callbackUrl);

        if (!$authUrl) {
            return $this->error('Payment service is not configured. Please ask the administrator to set up payment API keys.', 503);
        }

        return $this->success(['authorization_url' => $authUrl]);
    }

    /**
     * Verify a payment by reference/tx_ref and transaction ID.
     */
    public function verify(Request $request)
    {
        // Flutterwave callback passes tx_ref and transaction_id as query params
        // Paystack uses reference / trxref
        $reference = $request->query('tx_ref')
            ?? $request->query('reference')
            ?? $request->query('trxref')
            ?? $request->query('orderReference')
            ?? $request->query('orderId');

        // Flutterwave/Nomba redirect passes the numeric transaction ID
        $transactionId = $request->query('transaction_id')
            ?? $request->query('id')
            ?? $request->query('orderId');

        if (!$reference) {
            return $this->error('Verification failed. No payment reference found in callback URL.', 422);
        }

        $payment = $this->paymentService->verify(
            reference: $reference,
            flwTransactionId: $transactionId ? (string) $transactionId : null
        );

        if ($payment->status === 'cancelled') {
            return $this->error('Payment was cancelled. No credits were added.', 422);
        }

        if ($payment->status !== 'success') {
            return $this->error('Payment verification failed. No credits were added.', 422);
        }

        return $this->success([
            'payment' => $payment,
            'credits' => $this->creditService->getBalance($request->user()),
        ]);
    }

    /**
     * Handle Paystack webhook.
     */
    public function webhook(Request $request)
    {
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();

        if (!hash_equals(hash_hmac('sha512', $payload, config('services.paystack.secret_key')), $signature ?? '')) {
            return response()->json(['status' => 'invalid_signature'], 401);
        }

        $data = json_decode($payload, true);
        $this->paymentService->handleWebhook($data, 'paystack');

        return response()->json(['status' => 'received']);
    }

    /**
     * Handle Flutterwave webhook.
     */
    public function flutterwaveWebhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('verif-hash');

        if (!app(\App\Services\FlutterwaveService::class)->isValidWebhook($payload, $signature ?? '')) {
            return response()->json(['status' => 'invalid_signature'], 401);
        }

        $data = json_decode($payload, true);
        $this->paymentService->handleWebhook($data, 'flutterwave');

        return response()->json(['status' => 'received']);
    }

    /**
     * Handle Nomba webhook.
     */
    public function nombaWebhook(Request $request)
    {
        $payload = $request->getContent();

        // Nomba sends the signature in `nomba-signature` (also mirrored as
        // `nomba-sig-value`) and the timestamp used in the HMAC in `nomba-timestamp`.
        $signature = $request->header('nomba-signature')
            ?? $request->header('nomba-sig-value');
        $timestamp = $request->header('nomba-timestamp', '');

        if (!app(\App\Services\NombaService::class)->isValidWebhook($payload, $signature ?? '', $timestamp)) {
            return response()->json(['status' => 'invalid_signature'], 401);
        }

        $data = json_decode($payload, true);
        $this->paymentService->handleWebhook($data, 'nomba');

        return response()->json(['status' => 'received']);
    }

    /**
     * Get active payment gateway.
     */
    public function gateway(Request $request)
    {
        $gateway = $this->paymentService->getActiveGateway();

        return $this->success([
            'gateway' => $gateway,
            'is_flutterwave' => $gateway === 'flutterwave',
            'is_paystack' => $gateway === 'paystack',
            'is_nomba' => $gateway === 'nomba',
        ]);
    }

    /**
     * Get user's credit balance, ledger, and payment history.
     */
    public function summary(Request $request)
    {
        $user = $request->user();

        return $this->success([
            'balance' => $this->creditService->getBalance($user),
            'transactions' => CreditLedger::where('user_id', $user->id)
                ->latest('id')
                ->limit(20)
                ->get(),
            'recent_payments' => Payment::where('user_id', $user->id)
                ->latest('id')
                ->limit(10)
                ->get(),
        ]);
    }
}