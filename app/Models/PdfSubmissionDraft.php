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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}