<?php

namespace App\Http\Controllers\Api;

use App\Models\ReferralEarning;
use App\Models\ReferralPayoutRequest;
use App\Services\ReferralProgramService;
use Illuminate\Http\Request;

class ReferralController extends BaseController
{
    public function __construct(
        private ReferralProgramService $referralService,
    ) {}

    /**
     * Get user's referral link and code.
     */
    public function myLink(Request $request)
    {
        $user = $request->user();

        return $this->success([
            'referral_code' => $user->referral_code,
            'referral_link' => $this->referralService->getReferralLink($user),
        ]);
    }

    /**
     * Get user's earnings history (paginated).
     */
    public function earnings(Request $request)
    {
        $user = $request->user();

        $earnings = ReferralEarning::where('user_id', $user->id)
            ->with('referredUser:id,name,email')
            ->latest()
            ->paginate(20);

        $earnings->getCollection()->transform(function ($e) {
            return [
                'id' => $e->id,
                'referred_user' => $e->referredUser ? [
                    'name' => $e->referredUser->name,
                    'email' => $e->referredUser->email,
                ] : null,
                'source_amount_naira' => $e->sourceAmountNaira(),
                'commission_naira' => $e->commissionNaira(),
                'percentage_rate' => $e->percentage_rate,
                'payout_number' => $e->payout_number,
                'status' => $e->status,
                'created_at' => $e->created_at->toISOString(),
            ];
        });

        return $this->success(['earnings' => $earnings]);
    }

    /**
     * Get earnings summary (pending, total, paid balances).
     */
    public function summary(Request $request)
    {
        $user = $request->user();

        $pendingBalance = $this->referralService->getPendingBalance($user);
        $totalEarnings = $this->referralService->getTotalEarnings($user);
        $paidEarnings = $this->referralService->getPaidEarnings($user);
        $referralCount = $user->referrals()->count();

        $minPayoutThreshold = (int) \App\Models\Setting::getValue('referral.min_payout_threshold_naira', 5000);

        return $this->success([
            'pending_balance_naira' => (float) ($pendingBalance / 100),
            'total_earnings_naira' => (float) ($totalEarnings / 100),
            'paid_earnings_naira' => (float) ($paidEarnings / 100),
            'total_referrals' => $referralCount,
            'min_payout_threshold_naira' => $minPayoutThreshold,
            'can_request_payout' => ($pendingBalance / 100) >= $minPayoutThreshold,
        ]);
    }

    /**
     * Get payout request history.
     */
    public function payoutHistory(Request $request)
    {
        $user = $request->user();

        $payouts = ReferralPayoutRequest::where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        $payouts->getCollection()->transform(function ($p) {
            return [
                'id' => $p->id,
                'amount_naira' => $p->amountNaira(),
                'bank_name' => $p->bank_name,
                'account_number' => $p->account_number,
                'account_name' => $p->account_name,
                'status' => $p->status,
                'admin_notes' => $p->admin_notes,
                'created_at' => $p->created_at->toISOString(),
                'processed_at' => $p->processed_at?->toISOString(),
            ];
        });

        return $this->success(['payouts' => $payouts]);
    }

    /**
     * Request a payout.
     */
    public function requestPayout(Request $request)
    {
        $user = $request->user();

        $pendingBalance = $this->referralService->getPendingBalance($user);
        $minThresholdNaira = (int) \App\Models\Setting::getValue('referral.min_payout_threshold_naira', 5000);

        if (($pendingBalance / 100) < $minThresholdNaira) {
            return $this->error(
                'Minimum payout threshold of ₦' . number_format($minThresholdNaira) . ' not yet reached.',
                400
            );
        }

        $validated = $request->validate([
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:20',
            'account_name' => 'required|string|max:200',
        ]);

        $payout = ReferralPayoutRequest::create([
            'user_id' => $user->id,
            'amount_kobo' => $pendingBalance,
            'bank_name' => $validated['bank_name'],
            'account_number' => $validated['account_number'],
            'account_name' => $validated['account_name'],
            'status' => 'pending',
        ]);

        // Mark all pending earnings as linked to this payout
        ReferralEarning::where('user_id', $user->id)
            ->where('status', 'pending')
            ->update([
                'payout_request_id' => $payout->id,
            ]);

        return $this->success([
            'payout' => [
                'id' => $payout->id,
                'amount_naira' => $payout->amountNaira(),
                'status' => $payout->status,
                'created_at' => $payout->created_at->toISOString(),
            ],
        ], 'Payout request submitted successfully', 201);
    }
}