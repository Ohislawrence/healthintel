<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterpretationOverride extends Model
{
    protected $fillable = [
        'interpretation_id', 'overridden_by',
        'original_clinician_text', 'original_patient_text', 'original_status',
        'new_clinician_text', 'new_patient_text', 'new_status',
        'override_type', 'override_reason', 'changed_fields',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function interpretation(): BelongsTo
    {
        return $this->belongsTo(PartnerInterpretation::class, 'interpretation_id');
    }

    public function overriddenBy(): BelongsTo
    {
        return $this->belongsTo(ProviderDirectoryEntry::class, 'overridden_by');
    }
}