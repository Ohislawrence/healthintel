<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNotification extends Model
{
    protected $fillable = [
        'admin_id', 'title', 'body', 'target', 'user_ids', 'read_by', 'sent_at',
    ];

    protected $casts = [
        'user_ids' => 'array',
        'read_by' => 'array',
        'sent_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}