<?php

namespace App\Http\Controllers\Api;

use App\Models\ProviderDirectoryEntry;
use App\Models\ProviderListingRequest;
use App\Models\ReferralEvent;
use App\Services\BookingService;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PartnerPortalController extends BaseController
{
    public function __construct(
        private ReferralService $referralService,
        private BookingService $bookingService,
    ) {}

    /**
     * Provider login via access_code (magic-link style).
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'access_code' => 'required|string|max:64',
        ]);

        $provider = ProviderDirectoryEntry::where('access_code', $validated['access_code'])
            ->where('partner_status', '!=', 'none')
            ->first();

        if (! $provider) {
            return $this->error('Invalid access code or partner not active.', 401);
        }



        // Generate a short-lived provider token (expires in 2 hours)
        $token = $provider->createToken('partner-portal', ['partner-access'], now()->addHours(2))->plainTextToken;

        return $this->success([
            'provider' => $provider->only([
                'id', 'name', 'slug', 'type', 'specialty',
                'phone', 'email',
                'address', 'city', 'state',
                'partner_status',
                'referral_link',
                'is_verified', 'is_active',
            ]),
            'token' => $token,
        ], 'Welcome to your partner dashboard.');
    }

    /**
     * Provider dashboard with referral analytics.
     */
    public function dashboard(Request $request)
    {
        $provider = $this->resolveProvider($request);

        $thirtyDaysAgo = now()->subDays(30);

        $totalReferrals = ReferralEvent::where('provider_id', $provider->id)->count();

        $recentReferrals = ReferralEvent::where('provider_id', $provider->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        $referralsByAction = ReferralEvent::where('provider_id', $provider->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('action, count(*) as total')
            ->groupBy('action')
            ->pluck('total', 'action');

        $referralsBySource = ReferralEvent::where('provider_id', $provider->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('source_feature, count(*) as total')
            ->groupBy('source_feature')
            ->pluck('total', 'source_feature');

        $referralsPerDay = ReferralEvent::where('provider_id', $provider->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $this->success([
            'stats' => [
                'total_referrals' => $totalReferrals,
                'recent_referrals_30d' => $recentReferrals,
                'by_action' => $referralsByAction,
                'by_source' => $referralsBySource,
            ],
            'referrals_per_day' => $referralsPerDay,
        ]);
    }

    /**
     * Fetch the partner's full listing including monetization/ad details.
     */
    public function myListing(Request $request)
    {
        $provider = $this->resolveProvider($request);

        $provider->load('locations')->append('is_sponsored');

        return $this->success([
            'provider' => $provider->makeVisible(['access_code']),
        ]);
    }

    /**
     * Update provider listing info.
     */
    public function updateListing(Request $request)
    {
        $provider = $this->resolveProvider($request);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:200',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'website' => 'nullable|string|max:300',
            'bio' => 'nullable|string|max:2000',
            'specialty' => 'nullable|string|max:200',
            'insurance_plans' => 'nullable|array',
            'logo_url' => 'nullable|string|max:500',
            'banner_url' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'locations' => 'nullable|array|max:100',
            'locations.*.name' => 'nullable|string|max:200',
            'locations.*.address' => 'nullable|string|max:255',
            'locations.*.city' => 'nullable|string|max:100',
            'locations.*.state' => 'nullable|string|max:100',
            'locations.*.country' => 'nullable|string|max:100',
            'locations.*.phone' => 'nullable|string|max:50',
            'locations.*.latitude' => 'nullable|numeric',
            'locations.*.longitude' => 'nullable|numeric',
            'locations.*.is_primary' => 'boolean',
        ]);

        $locations = $request->input('locations');
        unset($validated['locations']);

        // Normalize empty strings to null
        foreach ($validated as $key => $value) {
            if ($value === '') {
                $validated[$key] = null;
            }
        }

        $provider->update($validated);

        if (is_array($locations)) {
            $this->syncLocations($provider, $locations);
        }

        $provider->load('locations')->append('is_sponsored');

        return $this->success([
            'provider' => $provider->makeVisible(['access_code']),
        ]);
    }

    /**
     * Fetch the partner's own listing/ad requests.
     */
    public function myRequests(Request $request)
    {
        $provider = $this->resolveProvider($request);

        $requests = ProviderListingRequest::where('contact_email', $provider->email)
            ->orWhere('provider_id', $provider->id)
            ->latest()
            ->get();

        return $this->success(['requests' => $requests]);
    }

    /**
     * Partner asks to become a sponsored listing (ad placement request).
     */
    public function requestPromotion(Request $request)
    {
        $provider = $this->resolveProvider($request);

        $validated = $request->validate([
            'promotion_plan' => 'nullable|string|max:30',
            'promotion_budget_naira' => 'nullable|numeric|min:0',
            'promotion_duration_days' => 'nullable|integer|min:1|max:365',
            'message' => 'nullable|string|max:2000',
        ]);

        $budget = null;
        if (isset($validated['promotion_budget_naira'])) {
            $budget = (int) round($validated['promotion_budget_naira'] * 100);
        }

        $listing = ProviderListingRequest::create([
            'request_type' => 'promotion',
            'facility_name' => $provider->name,
            'type' => $provider->type,
            'specialty' => $provider->specialty,
            'contact_name' => $provider->name,
            'contact_email' => $provider->email,
            'contact_phone' => $provider->phone,
            'address' => $provider->address,
            'city' => $provider->city,
            'state' => $provider->state,
            'website' => $provider->website,
            'description' => $validated['message'] ?? null,
            'promotion_plan' => $validated['promotion_plan'] ?? null,
            'promotion_budget_kobo' => $budget,
            'promotion_duration_days' => $validated['promotion_duration_days'] ?? null,
            'status' => 'pending',
            'provider_id' => $provider->id,
        ]);

        return $this->success([
            'request' => $listing->only(['id', 'request_type', 'facility_name', 'status', 'created_at']),
        ], 'Ad request submitted. Our team will review and contact you shortly.', 201);
    }

    /**
     * Replace a provider's locations with the supplied list.
     */
    private function syncLocations(ProviderDirectoryEntry $provider, array $locations): void
    {
        $normalized = collect($locations)
            ->filter(fn($loc) => is_array($loc) && (
                !empty($loc['name']) || !empty($loc['address']) || !empty($loc['city'])
            ))
            ->values()
            ->map(function ($loc) {
                $nullIfEmpty = fn ($value) => ($value === '' || $value === null) ? null : $value;

                return [
                    'name' => $nullIfEmpty($loc['name'] ?? null),
                    'address' => $nullIfEmpty($loc['address'] ?? null),
                    'city' => $nullIfEmpty($loc['city'] ?? null),
                    'state' => $nullIfEmpty($loc['state'] ?? null),
                    'country' => $loc['country'] ?? 'Nigeria',
                    'phone' => $nullIfEmpty($loc['phone'] ?? null),
                    'latitude' => $nullIfEmpty($loc['latitude'] ?? null),
                    'longitude' => $nullIfEmpty($loc['longitude'] ?? null),
                    'is_primary' => (bool) ($loc['is_primary'] ?? false),
                ];
            });

        // Ensure exactly one primary location when locations exist.
        if ($normalized->isNotEmpty() && !$normalized->contains('is_primary', true)) {
            $normalized = $normalized->map(function ($item, $index) {
                if ($index === 0) $item['is_primary'] = true;
                return $item;
            });
        }

        $provider->locations()->delete();
        foreach ($normalized as $data) {
            $provider->locations()->create($data);
        }
    }

    /**
     * List booking requests for this provider.
     */
    public function appointments(Request $request)
    {
        $provider = $this->resolveProvider($request);

        $status = $request->query('status');

        $appointments = \App\Models\Appointment::where('provider_id', $provider->id)
            ->with('user:id,name,email,phone')
            ->when($status && in_array($status, ['pending', 'confirmed', 'declined', 'completed', 'cancelled']), fn ($q) => $q->where('status', $status))
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->get();

        return $this->success([
            'appointments' => $appointments,
            'counts' => [
                'pending' => \App\Models\Appointment::where('provider_id', $provider->id)->where('status', 'pending')->count(),
                'confirmed' => \App\Models\Appointment::where('provider_id', $provider->id)->where('status', 'confirmed')->count(),
            ],
        ]);
    }

    /**
     * Confirm or decline a booking request.
     */
    public function appointmentDecision(Request $request, int $id)
    {
        $provider = $this->resolveProvider($request);

        $validated = $request->validate([
            'decision' => 'required|in:confirm,decline',
            'provider_notes' => 'nullable|string|max:2000',
        ]);

        $appointment = \App\Models\Appointment::where('id', $id)
            ->where('provider_id', $provider->id)
            ->firstOrFail();

        if ($appointment->status !== 'pending') {
            return $this->error('This request has already been handled.', 422);
        }

        if ($validated['decision'] === 'confirm') {
            $appointment->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'provider_notes' => $validated['provider_notes'] ?? null,
            ]);

            // Schedule the patient reminder now that it's confirmed.
            if ($appointment->reminder_enabled) {
                $appointmentDateTime = \Carbon\Carbon::parse(
                    $appointment->appointment_date->format('Y-m-d') . ' ' . ($appointment->appointment_time ?? '00:00')
                );
                $reminderAt = $appointmentDateTime->copy()->subMinutes($appointment->reminder_minutes_before);
                \App\Jobs\SendAppointmentReminder::dispatch($appointment->id)
                    ->delay($reminderAt->isFuture() ? $reminderAt : null);
            }
        } else {
            $appointment->update([
                'status' => 'declined',
                'provider_notes' => $validated['provider_notes'] ?? null,
            ]);

            // Refund any credits charged for the booking.
            $this->bookingService->refund($appointment->fresh());
        }

        $this->bookingService->notifyPatient($appointment->fresh(), $validated['decision'] === 'confirm', $validated['provider_notes'] ?? null);

        return $this->success($appointment->load('user:id,name'), $validated['decision'] === 'confirm' ? 'Booking confirmed' : 'Booking declined');
    }

    /**
     * Generate or regenerate an access code.
     */
    public function regenerateAccessCode(Request $request)
    {
        $provider = $this->resolveProvider($request);

        $provider->update([
            'access_code' => Str::random(40),
            'access_code_generated_at' => now(),
        ]);

        return $this->success([
            'access_code' => $provider->access_code,
        ]);
    }

    private function resolveProvider(Request $request): ProviderDirectoryEntry
    {
        // The authenticated model is ProviderDirectoryEntry (via Sanctum's HasApiTokens)
        $provider = $request->user();

        if (! $provider instanceof ProviderDirectoryEntry) {
            abort(403, 'Partner access required.');
        }

        return $provider;
    }
}