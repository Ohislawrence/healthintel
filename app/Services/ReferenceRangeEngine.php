<?php

namespace App\Services;

use App\Models\HealthProfile;
use App\Models\TestReferenceRange;
use Carbon\Carbon;

class ReferenceRangeEngine
{
    /**
     * Flag a single test value against its reference range, considering
     * the user's age, sex, and pregnancy status from their HealthProfile.
     *
     * @return array { flag, range_low, range_high, unit, is_critical }
     */
    public function flag(
        float $value,
        TestReferenceRange $range,
        ?HealthProfile $profile = null,
    ): array {
        $effective = $this->resolveEffectiveRange($range, $profile);

        $low = $effective['low'];
        $high = $effective['high'];
        $criticalLow = $range->critical_low;
        $criticalHigh = $range->critical_high;

        // Check critical thresholds first
        if ($criticalLow !== null && $value <= (float) $criticalLow) {
            return [
                'flag' => 'critical_low',
                'range_low' => $low,
                'range_high' => $high,
                'unit' => $range->unit,
                'is_critical' => true,
            ];
        }

        if ($criticalHigh !== null && $value >= (float) $criticalHigh) {
            return [
                'flag' => 'critical_high',
                'range_low' => $low,
                'range_high' => $high,
                'unit' => $range->unit,
                'is_critical' => true,
            ];
        }

        // Non-critical flags
        if ($low !== null && $value < (float) $low) {
            return [
                'flag' => 'low',
                'range_low' => $low,
                'range_high' => $high,
                'unit' => $range->unit,
                'is_critical' => false,
            ];
        }

        if ($high !== null && $value > (float) $high) {
            return [
                'flag' => 'high',
                'range_low' => $low,
                'range_high' => $high,
                'unit' => $range->unit,
                'is_critical' => false,
            ];
        }

        return [
            'flag' => 'normal',
            'range_low' => $low,
            'range_high' => $high,
            'unit' => $range->unit,
            'is_critical' => false,
        ];
    }

    /**
     * Pick the correct low/high range based on demographics.
     */
    private function resolveEffectiveRange(
        TestReferenceRange $range,
        ?HealthProfile $profile,
    ): array {
        // No profile → fall back to male range
        if (!$profile) {
            return [
                'low' => $range->range_low_male,
                'high' => $range->range_high_male,
            ];
        }

        $age = $profile->date_of_birth
            ? Carbon::parse($profile->date_of_birth)->age
            : null;

        // Pediatric (under 18)
        if ($age !== null && $age < 18 && ($range->range_low_pediatric !== null || $range->range_high_pediatric !== null)) {
            return [
                'low' => $range->range_low_pediatric,
                'high' => $range->range_high_pediatric,
            ];
        }

        // Pregnant female
        if ($profile->sex === 'female' && $profile->is_pregnant) {
            if ($range->range_low_pregnant !== null || $range->range_high_pregnant !== null) {
                return [
                    'low' => $range->range_low_pregnant,
                    'high' => $range->range_high_pregnant,
                ];
            }
            // Fall through to female if no pregnancy-specific range
        }

        // Female
        if ($profile->sex === 'female') {
            return [
                'low' => $range->range_low_female,
                'high' => $range->range_high_female,
            ];
        }

        // Male (default)
        return [
            'low' => $range->range_low_male,
            'high' => $range->range_high_male,
        ];
    }
}
