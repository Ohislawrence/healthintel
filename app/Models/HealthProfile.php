<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthProfile extends Model
{
    protected $fillable = [
        'user_id',
        'date_of_birth',
        'sex',
        'is_pregnant',
        'height_cm',
        'weight_kg',
        'blood_type',
        'medical_conditions',
        'current_medications',
        'profile_completed',
        'completed_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_pregnant' => 'boolean',
        'height_cm' => 'decimal:1',
        'weight_kg' => 'decimal:1',
        'profile_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
