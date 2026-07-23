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
