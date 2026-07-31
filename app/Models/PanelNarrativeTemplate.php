<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PanelNarrativeTemplate extends Model
{
    protected $fillable = [
        'panel_id', 'test_code', 'result_category',
        'clinician_template', 'patient_template',
        'escalation_level', 'required_action_text',
        'approved_by', 'approved_at', 'version',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function panel(): BelongsTo
    {
        return $this->belongsTo(InterpretationPanel::class, 'panel_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}