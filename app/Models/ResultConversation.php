<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResultConversation extends Model
{
    protected $table = 'result_conversations';

    protected $fillable = [
        'user_id',
        'lab_submission_id',
        'title',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function labSubmission(): BelongsTo
    {
        return $this->belongsTo(LabSubmission::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ResultMessage::class)->orderBy('created_at');
    }
}