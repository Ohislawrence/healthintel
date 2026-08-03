<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferralPayoutRequest extends Model
{
    protected $fillable = [
        'user_id', 'amount_kobo',
        'bank_name', 'account_number', 'account_name',
        'status', 'processed_by', 'processed_at', 'admin_notes',
    ];

    protected $casts = [
        'amount_kobo' => 'integer',
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(ReferralEarning::class, 'payout_request_id');
    }

    /** Amount in Naira. */
    public function amountNaira(): float
    {
        return $this->amount_kobo / 100;
    }
}