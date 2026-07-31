<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InterpretationPanel extends Model
{
    protected $fillable = [
        'slug', 'name', 'description', 'test_codes',
        'layout_sections', 'status',
        'approved_by', 'approved_at', 'version',
    ];

    protected $casts = [
        'test_codes' => 'array',
        'layout_sections' => 'array',
        'approved_at' => 'datetime',
    ];

    public function templates(): HasMany
    {
        return $this->hasMany(PanelNarrativeTemplate::class, 'panel_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}