<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SymptomCheckFunnel extends Model
{
    protected $fillable = [
        'user_id', 'symptoms_selected', 'panels_suggested',
        'panels_clicked', 'provider_viewed_id',
        'appointment_booked', 'appointment_id', 'stage',
    ];

    protected $casts = [
        'symptoms_selected' => 'array',
        'panels_suggested' => 'array',
        'panels_clicked' => 'array',
        'appointment_booked' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function providerViewed(): BelongsTo
    {
        return $this->belongsTo(ProviderDirectoryEntry::class, 'provider_viewed_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}