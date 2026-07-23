<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFeedback extends Model
{
    protected $table = 'user_feedback';

    protected $fillable = [
        'user_id', 'type', 'screen', 'message', 'metadata', 'status', 'admin_notes',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public const TYPES = ['bug', 'feature', 'improvement', 'other'];
    public const STATUSES = ['open', 'acknowledged', 'resolved', 'closed'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}