<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class ProviderDirectoryEntry extends Model
{
    use HasApiTokens;
    protected $fillable = [
        'name', 'slug', 'type', 'specialty', 'bio', 'phone', 'whatsapp', 'email',
        'address', 'city', 'state', 'country', 'website',
        'latitude', 'longitude', 'partner_status', 'referral_link',
        'insurance_plans', 'access_code', 'access_code_generated_at', 'is_verified', 'is_active',
        'monetization_type', 'monetization_rate', 'monetization_amount',
        'monetization_limit_type', 'monetization_limit_value',
        'monetization_started_at', 'monetization_expires_at',
        'monetization_views_used', 'banner_url', 'logo_url',
        'services', 'opening_hours', 'gallery',
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
        'services' => 'array',
        'opening_hours' => 'array',
        'gallery' => 'array',
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

    public function reviews(): HasMany
    {
        return $this->hasMany(ProviderReview::class, 'provider_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(ProviderFavorite::class, 'provider_id');
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

    /**
     * Whether the provider is currently open based on its structured
     * opening_hours. Returns null when hours are unknown (not supplied).
     *
     * Expected shape:
     *   { "mon": {"open":"08:00","close":"17:00"}, "tue": null, ... }
     * where a null value means "closed that day".
     */
    public function getIsOpenNowAttribute(): ?bool
    {
        if (empty($this->opening_hours)) {
            return null;
        }

        $day = strtolower(now()->format('D')); // mon..sun

        if (!array_key_exists($day, $this->opening_hours)) {
            return null;
        }

        $slot = $this->opening_hours[$day];
        if (!$slot || empty($slot['open'] ?? null) || empty($slot['close'] ?? null)) {
            return false;
        }

        $now = now()->format('H:i');

        return $now >= $slot['open'] && $now <= $slot['close'];
    }

    /** Increment view counter for sponsored listings. */
    public function incrementSponsorViews(): void
    {
        if ($this->partner_status === 'sponsored' && $this->monetization_limit_type === 'views') {
            $this->increment('monetization_views_used');
        }
    }
}
