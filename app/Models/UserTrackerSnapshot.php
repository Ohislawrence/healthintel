<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTrackerSnapshot extends Model
{
    protected $fillable = ['user_id', 'date', 'data'];

    protected $casts = [
        'date' => 'date',
        'data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}