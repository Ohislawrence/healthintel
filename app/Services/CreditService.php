<?php

namespace App\Services;

use App\Models\CreditLedger;
use App\Models\User;

class CreditService
{
    private int $signupCredits;

    public function __construct()
    {
        // Readable from admin panel later; hardcoded default for now.
        $this->signupCredits = (int) config('credits.signup_bonus', 3);
    }

    /**
     * Grant the one-time signup bonus.
     */
    public function grantSignupCredits(User $user): void
    {
        $existing = CreditLedger::where('user_id', $user->id)
            ->where('action_type', 'signup_bonus')
            ->exists();

        if ($existing) {
            return; // Already granted
        }

        $balance = $this->getBalance($user);

        CreditLedger::create([
            'user_id' => $user->id,
            'action_type' => 'signup_bonus',
            'credits_delta' => $this->signupCredits,
            'balance_after' => $balance + $this->signupCredits,
        ]);
    }

    /**
     * Get current balance from the ledger.
     */
    public function getBalance(User $user): int
    {
        $last = CreditLedger::where('user_id', $user->id)
            ->latest('id')
            ->first();

        return $last ? $last->balance_after : 0;
    }

    /**
     * Check if user has enough credits.
     */
    public function hasCredits(User $user, int $amount): bool
    {
        return $this->getBalance($user) >= $amount;
    }

    /**
     * Debit credits — returns the new balance or throws on insufficient funds.
     */
    public function debit(User $user, int $amount, string $actionType, mixed $reference = null): int
    {
        $balance = $this->getBalance($user);

        if ($balance < $amount) {
            throw new \RuntimeException('Insufficient credits');
        }

        $newBalance = $balance - $amount;

        CreditLedger::create([
            'user_id' => $user->id,
            'action_type' => $actionType,
            'credits_delta' => -$amount,
            'balance_after' => $newBalance,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
        ]);

        return $newBalance;
    }

    /**
     * Credit (refund) credits.
     */
    public function credit(User $user, int $amount, string $actionType, mixed $reference = null): int
    {
        $balance = $this->getBalance($user);
        $newBalance = $balance + $amount;

        CreditLedger::create([
            'user_id' => $user->id,
            'action_type' => $actionType,
            'credits_delta' => $amount,
            'balance_after' => $newBalance,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
        ]);

        return $newBalance;
    }
}
