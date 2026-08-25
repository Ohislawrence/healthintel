<?php

namespace App\Http\Controllers\Api;

use App\Models\EngagementEvent;
use Illuminate\Http\Request;

class EngagementController extends BaseController
{
    public function track(Request $request)
    {
        $validated = $request->validate([
            'event_key' => 'required|string|max:64',
            'event_source' => 'nullable|string|max:64',
            'metadata' => 'nullable|array',
        ]);

        $allowedKeys = [
            'buy_credits_viewed',
            'credit_package_selected',
            'payment_initialize_started',
            'payment_verified_success',
            'payment_verified_failed',
            'payment_verified_cancelled',
            'referral_link_copied',
            'referral_payout_requested',
        ];

        if (!in_array($validated['event_key'], $allowedKeys, true)) {
            return $this->error('Unsupported event key', 422);
        }

        EngagementEvent::create([
            'user_id' => $request->user()?->id,
            'event_key' => $validated['event_key'],
            'event_source' => $validated['event_source'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
        ]);

        return $this->success(['tracked' => true]);
    }
}
