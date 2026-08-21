<?php

namespace App\Http\Controllers\Api;

use App\Models\ProviderListingRequest;
use Illuminate\Http\Request;

class ProviderListingRequestController extends BaseController
{
    /**
     * Public endpoint: partners can request to list their lab/hospital
     * in the directory or request a sponsored ad placement.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'request_type' => 'required|in:listing,promotion',
            'facility_name' => 'required|string|max:255',
            'type' => 'required|in:lab,hospital,clinic,pharmacy,specialist',
            'specialty' => 'nullable|string|max:200',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'website' => 'nullable|string|max:300',
            'description' => 'nullable|string|max:5000',
            'promotion_plan' => 'nullable|string|max:30',
            'promotion_budget_naira' => 'nullable|numeric|min:0',
            'promotion_duration_days' => 'nullable|integer|min:1|max:365',
        ]);

        $budget = null;
        if (isset($validated['promotion_budget_naira'])) {
            $budget = (int) round($validated['promotion_budget_naira'] * 100);
        }
        unset($validated['promotion_budget_naira']);

        $validated['promotion_budget_kobo'] = $budget;
        $validated['status'] = 'pending';

        $listing = ProviderListingRequest::create($validated);

        return $this->success([
            'request' => $listing->only([
                'id', 'request_type', 'facility_name', 'status', 'created_at',
            ]),
        ], 'Thanks! We\'ve received your request and will review it shortly.', 201);
    }
}