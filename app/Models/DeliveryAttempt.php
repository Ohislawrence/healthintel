<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryAttempt extends Model
{
    protected $fillable = [
        'interpretation_id', 'delivery_method', 'recipient',
        'message_id', 'status', 'provider_response',
        'error_message', 'attempt_number', 'next_retry_at',
    ];

    protected $casts = [
        'provider_response' => 'array',
        'attempt_number' => 'integer',
        'next_retry_at' => 'datetime',
    ];

    public function interpretation(): BelongsTo
    {
        return $this->belongsTo(PartnerInterpretation::class, 'interpretation_id');
    }
}