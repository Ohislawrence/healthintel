<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = [
        'user_id',
        'provider_id',
        'patient_name',
        'patient_phone',
        'title',
        'description',
        'appointment_date',
        'appointment_time',
        'status',
        'notes',
        'provider_notes',
        'confirmed_at',
        'credits_charged',
        'refunded_at',
        'reminder_enabled',
        'reminder_minutes_before',
        'reminder_sent_at',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'reminder_enabled' => 'boolean',
        'reminder_minutes_before' => 'integer',
        'reminder_sent_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'credits_charged' => 'integer',
        'refunded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ProviderDirectoryEntry::class, 'provider_id');
    }
}