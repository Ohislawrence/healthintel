<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditLedger extends Model
{
    protected $table = 'credit_ledger';

    protected $fillable = [
        'user_id',
        'action_type',
        'credits_delta',
        'balance_after',
        'reference_type',
        'reference_id',
        'metadata',
    ];

    protected $casts = [
        'credits_delta' => 'integer',
        'balance_after' => 'integer',
        'metadata' => 'array',
    ];
}
