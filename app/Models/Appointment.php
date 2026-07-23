<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = [
        'user_id',
        'provider_id',
        'title',
        'description',
        'appointment_date',
        'appointment_time',
        'status',
        'notes',
        'reminder_enabled',
        'reminder_minutes_before',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'reminder_enabled' => 'boolean',
        'reminder_minutes_before' => 'integer',
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