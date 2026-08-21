<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderListingRequest extends Model
{
    protected $fillable = [
        'request_type',
        'facility_name',
        'type',
        'specialty',
        'contact_name',
        'contact_email',
        'contact_phone',
        'address',
        'city',
        'state',
        'website',
        'description',
        'promotion_plan',
        'promotion_budget_kobo',
        'promotion_duration_days',
        'status',
        'admin_notes',
        'provider_id',
    ];

    protected $casts = [
        'promotion_budget_kobo' => 'integer',
        'promotion_duration_days' => 'integer',
    ];

    public const STATUSES = ['pending', 'approved', 'rejected'];
    public const REQUEST_TYPES = ['listing', 'promotion'];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ProviderDirectoryEntry::class, 'provider_id');
    }
}