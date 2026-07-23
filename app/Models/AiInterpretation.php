<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiInterpretation extends Model
{
    protected $fillable = [
        'lab_submission_id', 'model_used', 'prompt_input', 'llm_output',
        'interpretation_text', 'guardrail_flags', 'status', 'error_message',
        'generated_at',
    ];

    protected $casts = [
        'llm_output' => 'array',
        'guardrail_flags' => 'array',
        'generated_at' => 'datetime',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(LabSubmission::class, 'lab_submission_id');
    }
}
