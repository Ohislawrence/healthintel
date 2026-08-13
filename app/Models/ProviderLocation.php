<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderLocation extends Model
{
    protected $fillable = [
        'provider_id', 'name', 'address', 'city', 'state', 'country',
        'phone', 'latitude', 'longitude', 'is_primary',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_primary' => 'boolean',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ProviderDirectoryEntry::class, 'provider_id');
    }
}