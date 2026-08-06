<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultMessage extends Model
{
    protected $table = 'result_messages';

    protected $fillable = [
        'result_conversation_id',
        'role',
        'content',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ResultConversation::class, 'result_conversation_id');
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function isAssistant(): bool
    {
        return $this->role === 'assistant';
    }
}