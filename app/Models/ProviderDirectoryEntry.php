<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class ProviderDirectoryEntry extends Model
{
    use HasApiTokens;
    protected $fillable = [
        'name', 'slug', 'type', 'specialty', 'bio', 'phone', 'email',
        'address', 'city', 'state', 'country', 'website',
        'latitude', 'longitude', 'partner_status', 'referral_link',
        'insurance_plans', 'access_code', 'access_code_generated_at', 'is_verified', 'is_active',
        'monetization_type', 'monetization_rate', 'monetization_amount',
        'monetization_limit_type', 'monetization_limit_value',
        'monetization_started_at', 'monetization_expires_at',
        'monetization_views_used', 'banner_url', 'logo_url',
    ];

    protected $hidden = [
        'access_code',
        'access_code_generated_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'insurance_plans' => 'array',
        'monetization_rate' => 'integer',
        'monetization_amount' => 'integer',
        'monetization_limit_value' => 'integer',
        'monetization_views_used' => 'integer',
        'monetization_started_at' => 'datetime',
        'monetization_expires_at' => 'datetime',
    ];

    public function referralEvents(): HasMany
    {
        return $this->hasMany(ReferralEvent::class, 'provider_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(ProviderLocation::class, 'provider_id');
    }

    public const TYPES = ['hospital', 'clinic', 'lab', 'pharmacy', 'specialist', 'insurance'];

    public const PARTNER_STATUSES = [
        'none' => 'Not a partner',
        'affiliate' => 'Affiliate partner',
        'sponsored' => 'Sponsored listing',
    ];

    /** Whether this listing is currently an active sponsored partner. */
    public function getIsSponsoredAttribute(): bool
    {
        if ($this->partner_status !== 'sponsored') return false;
        if (!$this->monetization_type) return false;

        // Check time-based expiry
        if ($this->monetization_limit_type === 'time' && $this->monetization_expires_at) {
            if (now()->gt($this->monetization_expires_at)) return false;
        }

        // Check view-based expiry
        if ($this->monetization_limit_type === 'views') {
            $limit = $this->monetization_limit_value ?? 0;
            $used = $this->monetization_views_used ?? 0;
            if ($limit > 0 && $used >= $limit) return false;
        }

        return true;
    }

    /** Increment view counter for sponsored listings. */
    public function incrementSponsorViews(): void
    {
        if ($this->partner_status === 'sponsored' && $this->monetization_limit_type === 'views') {
            $this->increment('monetization_views_used');
        }
    }
}
