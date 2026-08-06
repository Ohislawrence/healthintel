<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'password', 'google_id', 'email_verification_code', 'email_verification_code_expires_at', 'referral_code', 'referred_by_user_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    public function healthProfile(): HasOne
    {
        return $this->hasOne(HealthProfile::class);
    }

    public function labSubmissions(): HasMany
    {
        return $this->hasMany(LabSubmission::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by_user_id');
    }

    public function referralEarnings(): HasMany
    {
        return $this->hasMany(ReferralEarning::class, 'user_id');
    }

    public function resultConversations(): HasMany
    {
        return $this->hasMany(ResultConversation::class);
    }

    public function referralPayoutRequests(): HasMany
    {
        return $this->hasMany(ReferralPayoutRequest::class, 'user_id');
    }
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_code_expires_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}

