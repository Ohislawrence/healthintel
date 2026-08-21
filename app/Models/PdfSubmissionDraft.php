<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdfSubmissionDraft extends Model
{
    protected $fillable = [
        'user_id', 'raw_ocr_text', 'extracted_tests',
        'confirmation_status', 'confirmed_by', 'confirmed_at', 'pdf_path',
    ];

    protected $casts = [
        'extracted_tests' => 'array',
        'confirmed_at' => 'datetime',
    ];

    /**
     * Sanitize string attributes to valid UTF-8 on write so stored OCR/PDF
     * text can never crash JSON responses with malformed UTF-8.
     */
    public function setAttribute($key, $value)
    {
        if (is_string($value)) {
            $cleaned = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            $value = $cleaned === false ? '' : $cleaned;
        }

        return parent::setAttribute($key, $value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}