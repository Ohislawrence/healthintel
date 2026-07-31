<?php

namespace App\Http\Controllers\Api;

use App\Models\PartnershipInquiry;
use Illuminate\Http\Request;

class PartnershipInquiryController extends BaseController
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'facility_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:30',
            'estimated_volume' => 'nullable|string|max:50',
            'message' => 'nullable|string|max:5000',
        ]);

        PartnershipInquiry::create($validated);

        return $this->success(null, 'Thank you! We\'ll be in touch within one business day.', 201);
    }
}