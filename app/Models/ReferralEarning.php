<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralEarning extends Model
{
    protected $fillable = [
        'user_id', 'referred_user_id', 'payment_id',
        'source_amount_kobo', 'commission_kobo', 'percentage_rate',
        'payout_number', 'status',
        'payout_request_id', 'paid_at', 'notes',
    ];

    protected $casts = [
        'source_amount_kobo' => 'integer',
        'commission_kobo' => 'integer',
        'percentage_rate' => 'integer',
        'payout_number' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function payoutRequest(): BelongsTo
    {
        return $this->belongsTo(ReferralPayoutRequest::class);
    }

    /** Amount in Naira. */
    public function commissionNaira(): float
    {
        return $this->commission_kobo / 100;
    }

    public function sourceAmountNaira(): float
    {
        return $this->source_amount_kobo / 100;
    }
}