<?php

namespace App\Http\Controllers\Api;

use App\Models\ProviderDirectoryEntry;
use App\Services\ReferralService;
use Illuminate\Http\Request;

class ProviderDirectoryController extends BaseController
{
    public function __construct(
        private ReferralService $referralService,
    ) {}

    /**
     * List providers with optional filtering and proximity search.
     */
    public function index(Request $request)
    {
        $query = ProviderDirectoryEntry::where('is_active', true);

        // Auto-expire sponsored listings that have run out
        $this->expireStaleSponsorships();

        if ($request->filled('specialty')) {
            $query->where('specialty', 'like', '%' . $request->input('specialty') . '%');
        }

        if ($request->filled('city')) {
            $query->where('city', 'like', '%' . $request->input('city') . '%');
        }

        if ($request->filled('state')) {
            $query->where('state', $request->input('state'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('partner')) {
            $query->where('partner_status', $request->input('partner'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('specialty', 'like', '%' . $search . '%')
                  ->orWhere('city', 'like', '%' . $search . '%');
            });
        }

        // Proximity search
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $lat = (float) $request->input('latitude');
            $lng = (float) $request->input('longitude');
            $radiusKm = (float) $request->input('radius', 10);

            $providers = $query->get()
                ->map(function ($p) use ($lat, $lng) {
                    if ($p->latitude && $p->longitude) {
                        $p->distance_km = $this->referralService->haversineDistance($lat, $lng, $p->latitude, $p->longitude);
                    } else {
                        $p->distance_km = null;
                    }
                    return $p;
                })
                ->filter(fn($p) => !is_null($p->distance_km) && $p->distance_km <= $radiusKm)
                ->sortBy('distance_km')
                ->values();

            return $this->success(['data' => $providers]);
        }

        // Sort: sponsored first (still valid), then verified, then alphabetical
        $providers = $query
            ->orderByRaw("
                CASE 
                    WHEN partner_status = 'sponsored' AND monetization_type IS NOT NULL THEN 0
                    WHEN partner_status = 'affiliate' THEN 1
                    ELSE 2
                END
            ")
            ->orderBy('is_verified', 'desc')
            ->orderBy('name')
            ->paginate(15);

        return $this->paginated($providers);
    }

    /**
     * Show a single provider + log a view referral event.
     */
    public function show(Request $request, string $slug)
    {
        $provider = ProviderDirectoryEntry::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $this->referralService->log(
            $request->user(),
            $provider,
            'view',
            'directory',
            ['slug' => $slug],
        );

        return $this->success(['provider' => $provider]);
    }

    /**
     * Log a click-out (call, website visit, directions)
     */
    public function clickOut(Request $request, string $slug)
    {
        $validated = $request->validate([
            'action' => 'required|in:call,website,directions,enquiry',
        ]);

        $provider = ProviderDirectoryEntry::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $this->referralService->log(
            $request->user(),
            $provider,
            $validated['action'],
            'directory',
            ['action' => $validated['action'], 'provider_type' => $provider->type],
        );

        return $this->success(['logged' => true]);
    }

    /**
     * Insurance/HMO list for comparison.
     */
    public function insuranceList()
    {
        $hmoList = ProviderDirectoryEntry::where('type', 'insurance')
            ->where('is_active', true)
            ->get();

        return $this->success(['hmo_list' => $hmoList]);
    }

    /**
     * Insurance lead capture: submit enquiry and log referral.
     */
    public function insuranceEnquire(Request $request)
    {
        $validated = $request->validate([
            'provider_slug' => 'required|string|exists:provider_directory_entries,slug',
            'message' => 'nullable|string|max:1000',
        ]);

        $provider = ProviderDirectoryEntry::where('slug', $validated['provider_slug'])
            ->where('is_active', true)
            ->firstOrFail();

        $this->referralService->log(
            $request->user(),
            $provider,
            'enquiry',
            'insurance_comparison',
            ['message' => $validated['message'] ?? ''],
        );

        return $this->success(['message' => 'Your enquiry has been submitted. The provider will contact you shortly.']);
    }

    public function specialties()
    {
        return $this->success([
            'specialties' => ProviderDirectoryEntry::where('is_active', true)
                ->whereNotNull('specialty')->distinct()
                ->pluck('specialty')->sort()->values(),
        ]);
    }

    public function states()
    {
        return $this->success([
            'states' => ProviderDirectoryEntry::where('is_active', true)
                ->whereNotNull('state')->distinct()
                ->pluck('state')->sort()->values(),
        ]);
    }

    public function types()
    {
        return $this->success(['types' => ProviderDirectoryEntry::TYPES]);
    }

    /**
     * Get active sponsored providers for the mobile dashboard carousel.
     * Returns providers with banners, sorted by proximity if coordinates provided.
     */
    public function sponsoredBanners(Request $request)
    {
        $this->expireStaleSponsorships();

        $sponsored = ProviderDirectoryEntry::where('is_active', true)
            ->where('partner_status', 'sponsored')
            ->whereNotNull('monetization_type')
            ->select([
                'id', 'name', 'slug', 'type', 'specialty', 'city', 'state',
                'banner_url', 'monetization_amount',
                'latitude', 'longitude',
            ])
            ->get();

        // If user provided coordinates, sort by proximity (closest first)
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $lat = (float) $request->input('latitude');
            $lng = (float) $request->input('longitude');

            $sponsored = $sponsored
                ->map(function ($p) use ($lat, $lng) {
                    if ($p->latitude && $p->longitude) {
                        $p->distance_km = $this->referralService->haversineDistance(
                            $lat, $lng, $p->latitude, $p->longitude
                        );
                    } else {
                        $p->distance_km = null;
                    }
                    return $p;
                })
                ->sortBy(fn($p) => $p->distance_km ?? PHP_FLOAT_MAX)
                ->values();
        }

        return $this->success(['banners' => $sponsored]);
    }

    /**
     * Auto-expire sponsored listings that have exceeded their limit.
     * Called on every directory query to keep listings current.
     */
    private function expireStaleSponsorships(): void
    {
        $now = now();

        // Expire time-based sponsorships
        ProviderDirectoryEntry::where('partner_status', 'sponsored')
            ->where('monetization_limit_type', 'time')
            ->whereNotNull('monetization_expires_at')
            ->where('monetization_expires_at', '<=', $now)
            ->update([
                'partner_status' => 'none',
                'monetization_type' => null,
                'monetization_expires_at' => null,
            ]);

        // Expire view-based sponsorships that have hit their limit
        ProviderDirectoryEntry::where('partner_status', 'sponsored')
            ->where('monetization_limit_type', 'views')
            ->whereNotNull('monetization_limit_value')
            ->whereColumn('monetization_views_used', '>=', 'monetization_limit_value')
            ->update([
                'partner_status' => 'none',
                'monetization_type' => null,
                'monetization_views_used' => null,
            ]);
    }
}
