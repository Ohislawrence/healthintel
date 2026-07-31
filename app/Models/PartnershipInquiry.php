<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnershipInquiry extends Model
{
    protected $fillable = [
        'facility_name',
        'contact_name',
        'contact_email',
        'contact_phone',
        'estimated_volume',
        'message',
        'status',
        'admin_notes',
    ];
}