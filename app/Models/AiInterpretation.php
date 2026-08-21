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

    /**
     * Sanitize string attributes to valid UTF-8 on write. PDF/OCR text and
     * AI output can contain invalid byte sequences that would otherwise crash
     * Laravel's JSON responses ("Malformed UTF-8 characters").
     */
    public function setAttribute($key, $value)
    {
        if (is_string($value)) {
            $cleaned = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            $value = $cleaned === false ? '' : $cleaned;
        }

        return parent::setAttribute($key, $value);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(LabSubmission::class, 'lab_submission_id');
    }
}
