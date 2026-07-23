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
            return $this->error('Payment service is not configured. Please ask the administrator to set up Paystack API keys.', 503);
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
        $paystack = app(\App\Services\PaystackService::class);
        if (!hash_equals(hash_hmac('sha512', $payload, config('services.paystack.secret_key')), $signature ?? '')) {
            return response()->json(['status' => 'invalid_signature'], 401);
        }

        $data = json_decode($payload, true);
        $this->paymentService->handleWebhook($data);

        return response()->json(['status' => 'received']);
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
