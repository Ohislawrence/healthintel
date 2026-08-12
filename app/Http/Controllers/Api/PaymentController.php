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
     * Verify a payment by reference.
     */
    public function verify(Request $request)
    {
        $reference = $request->query('reference');
        if (!$reference) {
            return $this->error('Reference query parameter is required.', 422);
        }

        $payment = $this->paymentService->verify($reference);

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

        // Verify Paystack signature
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
        $signature = $request->header('verif-hash');
        $payload = $request->getContent();

        // Verify Flutterwave signature using secret hash
        $flutterwave = app(\App\Services\FlutterwaveService::class);
        $secretHash = config('services.flutterwave.secret_hash');

        if ($secretHash && !hash_equals($secretHash, $signature ?? '')) {
            return response()->json(['status' => 'invalid_signature'], 401);
        }

        $data = json_decode($payload, true);
        $this->paymentService->handleWebhook($data, 'flutterwave');

        return response()->json(['status' => 'received']);
    }

    /**
     * Get active payment gateway (for mobile app to know which provider to display).
     */
    public function gateway(Request $request)
    {
        $gateway = $this->paymentService->getActiveGateway();

        return $this->success([
            'gateway' => $gateway,
            'is_flutterwave' => $gateway === 'flutterwave',
            'is_paystack' => $gateway === 'paystack',
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
