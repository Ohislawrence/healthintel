<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferenceRange extends Model
{
    protected $fillable = [
        'test_code', 'test_name', 'category', 'sex',
        'age_min_years', 'age_max_years',
        'pregnancy_applicable', 'pregnancy_trimester',
        'range_low', 'range_high',
        'critical_low', 'critical_high',
        'unit', 'source',
        'reviewed_by', 'reviewed_at',
        'is_active',
    ];

    protected $casts = [
        'age_min_years' => 'decimal:1',
        'age_max_years' => 'decimal:1',
        'range_low' => 'decimal:4',
        'range_high' => 'decimal:4',
        'critical_low' => 'decimal:4',
        'critical_high' => 'decimal:4',
        'pregnancy_applicable' => 'boolean',
        'pregnancy_trimester' => 'integer',
        'is_active' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope to find a range matching a demographic profile.
     */
    public function scopeMatchDemographics($query, string $testCode, ?string $sex, ?float $age, ?bool $isPregnant, ?int $trimester)
    {
        return $query->where('test_code', $testCode)
            ->where('is_active', true)
            ->where(function ($q) use ($sex) {
                $q->where('sex', $sex)->orWhere('sex', 'all');
            })
            ->when($age !== null, function ($q) use ($age) {
                $q->where(function ($sub) use ($age) {
                    $sub->whereNull('age_min_years')->orWhere('age_min_years', '<=', $age);
                })->where(function ($sub) use ($age) {
                    $sub->whereNull('age_max_years')->orWhere('age_max_years', '>=', $age);
                });
            })
            ->when($isPregnant, function ($q) use ($trimester) {
                $q->where('pregnancy_applicable', true)
                  ->when($trimester, fn($sub) => $sub->where('pregnancy_trimester', $trimester));
            })
            ->orderByRaw("FIELD(sex, ?, 'all')", [$sex])  // prefer sex-specific over 'all'
            ->orderByRaw('age_min_years IS NOT NULL DESC') // prefer age-specific over generic
            ->orderByRaw('pregnancy_applicable DESC');     // prefer pregnancy-specific
    }
}