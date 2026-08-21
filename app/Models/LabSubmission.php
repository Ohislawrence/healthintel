<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LabSubmission extends Model
{
    protected $fillable = ['user_id', 'test_panel_id', 'submission_type', 'credits_used', 'pdf_report_url', 'pdf_text', 'submitted_at'];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    /**
     * Sanitize string attributes to valid UTF-8 on write so stored PDF text
     * can never crash JSON responses with malformed UTF-8.
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

    public function testPanel(): BelongsTo
    {
        return $this->belongsTo(TestPanel::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(LabSubmissionValue::class);
    }

    public function interpretation(): HasOne
    {
        return $this->hasOne(AiInterpretation::class);
    }
}
