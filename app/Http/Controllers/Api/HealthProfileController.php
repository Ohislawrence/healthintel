<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Profile\UpdateHealthProfileRequest;
use App\Models\HealthProfile;
use Illuminate\Http\Request;

class HealthProfileController extends BaseController
{
    /**
     * Get the authenticated user's health profile.
     */
    public function show(Request $request)
    {
        $profile = $request->user()->healthProfile;

        if (!$profile) {
            // Auto-create an empty profile if it doesn't exist
            $profile = $request->user()->healthProfile()->create();
        }

        return $this->success(['health_profile' => $profile]);
    }

    /**
     * Create or update the health profile.
     * Skippable — partial updates are allowed.
     */
    public function update(UpdateHealthProfileRequest $request)
    {
        $user = $request->user();

        $profile = $user->healthProfile()->updateOrCreate(
            ['user_id' => $user->id],
            array_merge(
                $request->only([
                    'date_of_birth',
                    'sex',
                    'is_pregnant',
                    'height_cm',
                    'weight_kg',
                    'blood_type',
                    'medical_conditions',
                    'current_medications',
                ]),
                ['profile_completed' => $request->boolean('profile_completed', false)],
            ),
        );

        if ($profile->profile_completed && !$profile->completed_at) {
            $profile->completed_at = now();
            $profile->save();
        }

        return $this->success([
            'health_profile' => $profile->fresh(),
        ], 'Health profile updated');
    }
}
