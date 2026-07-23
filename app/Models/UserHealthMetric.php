<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserHealthMetric extends Model
{
    protected $fillable = [
        'user_id', 'metric_type', 'data', 'notes', 'recorded_at',
    ];

    protected $casts = [
        'data' => 'array',
        'recorded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}