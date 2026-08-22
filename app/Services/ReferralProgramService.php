<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\ReferralEarning;
use App\Models\User;
use Illuminate\Support\Str;

class ReferralProgramService
{
    /**
     * Generate a unique referral code for a user.
     */
    public function generateReferralCode(User $user): string
    {
        $code = $this->makeCode($user->name);

        // Ensure uniqueness
        while (User::where('referral_code', $code)->exists()) {
            $code = $this->makeCode($user->name) . rand(10, 99);
        }

        $user->update(['referral_code' => $code]);

        return $code;
    }

    /**
     * Create a referral code from the user's name.
     */
    private function makeCode(string $name): string
    {
        $base = Str::slug($name, '');
        $base = strtoupper(substr($base, 0, 8));
        $random = strtoupper(Str::random(4));

        return $base . $random;
    }

    /**
     * Get the referral share link for a user.
     */
    public function getReferralLink(User $user): string
    {
        $code = $user->referral_code;

        if (!$code) {
            $code = $this->generateReferralCode($user);
        }

        return config('app.url') . '/register?ref=' . $code;
    }

    /**
     * Process referral commission when a payment is confirmed.
     * Called after a successful payment webhook.
     */
    public function processCommission(Payment $payment): void
    {
        $user = $payment->user;

        if (!$user || !$user->referred_by_user_id) {
            return;
        }

        $referrer = User::find($user->referred_by_user_id);

        if (!$referrer) {
            return;
        }

        // Check count-based limit (admin-configurable).
        $maxPayouts = (int) \App\Models\Setting::getValue('referral.max_payouts_per_referral', 3);

        $existingEarnings = ReferralEarning::where('user_id', $referrer->id)
            ->where('referred_user_id', $user->id)
            ->count();

        if ($existingEarnings >= $maxPayouts) {
            return; // Limit reached for this referred user
        }

        $percentage = (int) \App\Models\Setting::getValue('referral.percentage', 10);
        $commissionKobo = (int) floor($payment->amount_kobo * $percentage / 100);

        if ($commissionKobo <= 0) {
            return;
        }

        ReferralEarning::create([
            'user_id' => $referrer->id,
            'referred_user_id' => $user->id,
            'payment_id' => $payment->id,
            'source_amount_kobo' => $payment->amount_kobo,
            'commission_kobo' => $commissionKobo,
            'percentage_rate' => $percentage,
            'payout_number' => $existingEarnings + 1,
            'status' => 'pending',
        ]);
    }

    /**
     * Get total pending earnings balance for a user (in kobo).
     */
    public function getPendingBalance(User $user): int
    {
        return (int) ReferralEarning::where('user_id', $user->id)
            ->where('status', 'pending')
            ->sum('commission_kobo');
    }

    /**
     * Get total earnings (all time) for a user (in kobo).
     */
    public function getTotalEarnings(User $user): int
    {
        return (int) ReferralEarning::where('user_id', $user->id)
            ->sum('commission_kobo');
    }

    /**
     * Get total paid earnings for a user (in kobo).
     */
    public function getPaidEarnings(User $user): int
    {
        return (int) ReferralEarning::where('user_id', $user->id)
            ->where('status', 'paid')
            ->sum('commission_kobo');
    }

    /**
     * Attach a referrer to a new user based on referral code.
     * Returns the referrer if found.
     */
    public function attachReferrer(User $newUser, ?string $referralCode): ?User
    {
        if (!$referralCode) {
            return null;
        }

        // Don't allow self-referral
        $referrer = User::where('referral_code', $referralCode)
            ->where('id', '!=', $newUser->id)
            ->first();

        if ($referrer) {
            $newUser->update(['referred_by_user_id' => $referrer->id]);
        }

        return $referrer;
    }
}