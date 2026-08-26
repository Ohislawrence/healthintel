<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorReport extends Model
{
    protected $fillable = [
        'level',
        'source',
        'type',
        'message',
        'context',
        'url',
        'user_id',
        'occurrences',
        'last_seen_at',
        'status',
    ];

    protected $casts = [
        'context' => 'array',
        'occurrences' => 'integer',
        'last_seen_at' => 'datetime',
    ];
}