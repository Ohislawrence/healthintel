<?php

namespace App\Http\Controllers\Api;

use App\Models\ProviderDirectoryEntry;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProviderDirectoryController extends BaseController
{
    public function __construct(
        private ReferralService $referralService,
    ) {}

    /**
     * List providers with optional filtering, proximity search, and sorting.
     */
    public function index(Request $request)
    {
        // Auto-expire sponsored listings that have run out.
        $this->expireStaleSponsorships();

        $query = ProviderDirectoryEntry::where('is_active', true);

        if ($request->filled('specialty')) {
            $query->where('specialty', 'like', '%' . $request->input('specialty') . '%');
        }

        if ($request->filled('city')) {
            $city = $request->input('city');
            $query->where(function ($q) use ($city) {
                $q->where('city', 'like', '%' . $city . '%')
                  ->orWhereHas('locations', fn ($lq) => $lq->where('city', 'like', '%' . $city . '%'));
            });
        }

        if ($request->filled('state')) {
            $state = $request->input('state');
            $query->where(function ($q) use ($state) {
                $q->where('state', $state)
                  ->orWhereHas('locations', fn ($lq) => $lq->where('state', $state));
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('partner')) {
            $query->where('partner_status', $request->input('partner'));
        }

        // Filter by accepted insurance plan/HMO (JSON column).
        if ($request->filled('insurance')) {
            $query->whereJsonContains('insurance_plans', $request->input('insurance'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('specialty', 'like', '%' . $search . '%')
                  ->orWhere('city', 'like', '%' . $search . '%')
                  ->orWhereHas('locations', function ($lq) use ($search) {
                      $lq->where('name', 'like', '%' . $search . '%')
                         ->orWhere('city', 'like', '%' . $search . '%')
                         ->orWhere('state', 'like', '%' . $search . '%');
                  });
            });
        }

        $query->with('locations')
            ->withCount('locations')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        $hasCoords = $request->filled('latitude') && $request->filled('longitude');

        if ($hasCoords) {
            // DB-side Haversine — scales without loading all rows into memory.
            $lat = (float) $request->input('latitude');
            $lng = (float) $request->input('longitude');
            $radiusKm = (float) $request->input('radius', 10);

            $query->select('*')
                ->selectRaw(
                    '(6371 * acos(least(1.0, cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))) as distance_km',
                    [$lat, $lng, $lat],
                )
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->having('distance_km', '<=', $radiusKm)
                ->orderBy('distance_km');
        } else {
            $sort = $request->input('sort', 'relevance');

            if ($sort === 'rating') {
                $query->orderByDesc('reviews_avg_rating')
                    ->orderByDesc('reviews_count')
                    ->orderBy('name');
            } elseif ($sort === 'name') {
                $query->orderBy('name');
            } else {
                $query->orderByRaw("
                    CASE
                        WHEN partner_status = 'sponsored' AND monetization_type IS NOT NULL THEN 0
                        WHEN partner_status = 'affiliate' THEN 1
                        ELSE 2
                    END
                ")
                ->orderBy('is_verified', 'desc')
                ->orderBy('name');
            }
        }

        $providers = $query->paginate(15);

        // Expose computed statuses + rating aggregates in each item.
        $providers->getCollection()->transform(function ($p) {
            $p->append(['is_sponsored', 'is_open_now']);
            $p->rating_avg = round((float) ($p->reviews_avg_rating ?? 0), 1);
            $p->rating_count = (int) ($p->reviews_count ?? 0);
            return $p;
        });

        return $this->paginated($providers);
    }

    /**
     * Show a single provider + log a view referral event.
     */
    public function show(Request $request, string $slug)
    {
        $provider = ProviderDirectoryEntry::where('slug', $slug)
            ->where('is_active', true)
            ->with('locations')
            ->firstOrFail();

        if ($request->user()) {
            $this->referralService->log(
                $request->user(),
                $provider,
                'view',
                'directory',
                ['slug' => $slug],
            );
        }

        // Expose computed sponsored + open-now status in the API payload.
        $provider->append(['is_sponsored', 'is_open_now']);

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
        $hmoList = Cache::remember('providers:insurance-list', 300, function () {
            return ProviderDirectoryEntry::where('type', 'insurance')
                ->where('is_active', true)
                ->get()
                ->map(fn ($p) => $p->toArray())
                ->all();
        });

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

    /**
     * Get recommended nearby providers for lab-result / symptom-checker pages.
     * Sponsored → affiliate → verified, sorted by proximity.
     */
    public function nearbyRecommended(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'type' => 'nullable|in:lab,hospital,clinic',
            'limit' => 'nullable|integer|min:1|max:10',
        ]);

        $lat = (float) $request->input('latitude');
        $lng = (float) $request->input('longitude');
        $type = $request->input('type');
        $limit = (int) $request->input('limit', 5);

        $this->expireStaleSponsorships();

        $query = ProviderDirectoryEntry::where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($type) {
            $query->where('type', $type);
        } else {
            $query->whereIn('type', ['lab', 'hospital', 'clinic']);
        }

        $providers = $query
            ->select('*')
            ->selectRaw(
                '(6371 * acos(least(1.0, cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))) as distance_km',
                [$lat, $lng, $lat],
            )
            ->having('distance_km', '<=', 50)
            ->orderByRaw("
                CASE
                    WHEN partner_status = 'sponsored' AND monetization_type IS NOT NULL THEN 0
                    WHEN partner_status = 'affiliate' THEN 1
                    ELSE 2
                END
            ")
            ->orderBy('distance_km')
            ->limit($limit)
            ->get();

        $providers->transform(function ($p) {
            $p->append('is_sponsored');
            return $p;
        });

        return $this->success(['providers' => $providers]);
    }

    public function specialties()
    {
        $items = Cache::remember('providers:specialties', 300, function () {
            return ProviderDirectoryEntry::where('is_active', true)
                ->whereNotNull('specialty')->distinct()
                ->pluck('specialty')->sort()->values()->all();
        });

        return $this->success(['specialties' => $items]);
    }

    public function states()
    {
        $items = Cache::remember('providers:states', 300, function () {
            return ProviderDirectoryEntry::where('is_active', true)
                ->whereNotNull('state')->distinct()
                ->pluck('state')->sort()->values()->all();
        });

        return $this->success(['states' => $items]);
    }

    public function cities()
    {
        $items = Cache::remember('providers:cities', 300, function () {
            return ProviderDirectoryEntry::where('is_active', true)
                ->whereNotNull('city')->distinct()
                ->pluck('city')->sort()->values()->all();
        });

        return $this->success(['cities' => $items]);
    }

    /**
     * Distinct list of accepted insurance plans/HMOs across providers.
     */
    public function insurers()
    {
        $plans = Cache::remember('providers:insurers', 300, function () {
            return ProviderDirectoryEntry::where('is_active', true)
                ->whereNotNull('insurance_plans')
                ->pluck('insurance_plans')
                ->flatten()
                ->unique()
                ->filter()
                ->map(fn ($p) => is_string($p) ? $p : (is_array($p) ? ($p['name'] ?? null) : null))
                ->filter()
                ->values()
                ->all();
        });

        return $this->success(['insurers' => $plans]);
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

        $cacheKey = 'providers:banners:' . md5(json_encode($request->only(['latitude', 'longitude'])));

        $banners = Cache::remember($cacheKey, 300, function () use ($request) {
            $sponsored = ProviderDirectoryEntry::where('is_active', true)
                ->whereIn('partner_status', ['sponsored', 'affiliate'])
                ->where(function ($q) {
                    $q->where('partner_status', 'affiliate')
                      ->orWhere(function ($q2) {
                          $q2->where('partner_status', 'sponsored')
                             ->whereNotNull('monetization_type');
                      });
                })
                ->select([
                    'id', 'name', 'slug', 'type', 'specialty', 'city', 'state',
                    'banner_url', 'logo_url', 'partner_status',
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
                    ->sortBy(fn ($p) => $p->distance_km ?? PHP_FLOAT_MAX)
                    ->values();
            }

            return $sponsored->map(fn ($p) => $p->toArray())->values()->all();
        });

        return $this->success(['banners' => $banners]);
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