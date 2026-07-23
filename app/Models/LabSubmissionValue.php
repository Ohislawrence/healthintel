<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabSubmissionValue extends Model
{
    protected $fillable = [
        'lab_submission_id', 'test_slug', 'test_name', 'unit', 'value', 'flag',
    ];

    protected $casts = [
        'value' => 'decimal:4',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(LabSubmission::class, 'lab_submission_id');
    }
}
