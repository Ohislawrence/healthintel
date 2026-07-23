<?php

namespace App\Services;

use App\Models\ProviderDirectoryEntry;
use App\Models\ReferralEvent;
use App\Models\User;

class ReferralService
{
    /** Log a referral click-out event for all external actions. */
    public function log(
        User $user,
        ?ProviderDirectoryEntry $provider,
        string $action,
        string $sourceFeature = 'directory',
        array $metadata = [],
    ): ReferralEvent {
        return ReferralEvent::create([
            'user_id' => $user->id,
            'provider_id' => $provider?->id,
            'source_feature' => $sourceFeature,
            'action' => $action,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Haversine distance calculation for proximity search.
     * Returns distance in km.
     */
    public function haversineDistance(
        float $lat1, float $lng1,
        float $lat2, float $lng2,
    ): float {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 1);
    }

    /**
     * Get affiliate summary for reporting.
     */
    public function getAffiliateSummary(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $events = ReferralEvent::whereBetween('created_at', [$from, $to])
            ->whereNotNull('provider_id')
            ->get();

        $byProvider = $events->groupBy('provider_id')->map(fn($e) => $e->count());
        $byAction = $events->groupBy('action')->map(fn($e) => $e->count());
        $bySource = $events->groupBy('source_feature')->map(fn($e) => $e->count());

        return [
            'total_events' => $events->count(),
            'unique_providers' => $byProvider->count(),
            'by_action' => $byAction,
            'by_source' => $bySource,
            'top_providers' => $byProvider->sortDesc()->take(10),
        ];
    }
}
