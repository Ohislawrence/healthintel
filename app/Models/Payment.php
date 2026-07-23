<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 'purchasable_type', 'purchasable_id',
        'provider', 'reference', 'provider_reference',
        'amount_kobo', 'currency', 'status',
        'provider_response', 'webhook_log', 'paid_at',
    ];

    protected $casts = [
        'amount_kobo' => 'integer',
        'provider_response' => 'array',
        'webhook_log' => 'array',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchasable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Amount in Naira. */
    public function amountNaira(): float
    {
        return $this->amount_kobo / 100;
    }

    /** Check if payment was successful. */
    public function isPaid(): bool
    {
        return $this->status === 'success';
    }
}
