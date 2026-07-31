<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerInterpretation extends Model
{
    protected $fillable = [
        'partnership_id', 'lab_submission_id', 'patient_identifier',
        'test_name', 'value', 'unit', 'reference_range_low', 'reference_range_high',
        'sex', 'age', 'interpretation_text', 'clinician_interpretation_text',
        'version_for_patient', 'interpretation_version',
        'classification_status', 'confidence_score', 'escalation_level', 'escalation_message',
        'status', 'delivery_method', 'delivery_status',
        'cost_to_partner', 'external_id',
    ];

    protected $casts = [
        'cost_to_partner' => 'integer',
        'version_for_patient' => 'boolean',
        'confidence_score' => 'integer',
    ];

    public function partnership(): BelongsTo
    {
        return $this->belongsTo(LabPartnership::class, 'partnership_id');
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(LabSubmission::class, 'lab_submission_id');
    }

    public function costNaira(): float
    {
        return $this->cost_to_partner / 100;
    }
}