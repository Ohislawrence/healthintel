<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabPartnership extends Model
{
    protected $fillable = [
        'provider_id', 'plan_tier', 'pricing_model', 'rate_per_report',
        'monthly_allowance', 'overage_rate', 'white_label', 'brand_logo_url',
        'brand_primary_color', 'brand_contact_info', 'contract_start',
        'contract_end', 'status', 'ndpa_agreement_signed',
    ];

    protected $casts = [
        'white_label' => 'boolean',
        'ndpa_agreement_signed' => 'boolean',
        'contract_start' => 'date',
        'contract_end' => 'date',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ProviderDirectoryEntry::class, 'provider_id');
    }

    public function interpretations(): HasMany
    {
        return $this->hasMany(PartnerInterpretation::class, 'partnership_id');
    }

    public function interpretationsThisMonth(): HasMany
    {
        return $this->hasMany(PartnerInterpretation::class, 'partnership_id')
            ->where('created_at', '>=', now()->startOfMonth());
    }

    /** Price in Naira per report. */
    public function rateNaira(): float
    {
        return ($this->rate_per_report ?? 0) / 100;
    }

    /** Total interpretations this billing period (month). */
    public function monthlyCount(): int
    {
        return $this->interpretationsThisMonth()->where('status', 'completed')->count();
    }

    /** Estimated current monthly bill in Naira. */
    public function estimatedMonthlyBill(): float
    {
        $count = $this->monthlyCount();
        $allowance = $this->monthly_allowance ?? 0;

        if ($count <= $allowance) {
            return 0;
        }

        $overage = $count - $allowance;
        $rate = $this->overage_rate ? ($this->overage_rate / 100) : $this->rateNaira();

        return $overage * $rate;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}