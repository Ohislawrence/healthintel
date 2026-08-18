<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\Appointment;
use App\Models\CreditPackage;
use App\Models\UserFeedback;
use App\Models\LabSubmission;
use App\Models\Payment;
use App\Models\ProviderDirectoryEntry;
use App\Models\ReferralEvent;
use App\Models\Symptom;
use App\Models\TestPanel;
use App\Models\User;
use App\Models\UserHealthMetric;
use App\Services\EmailService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminController extends BaseController
{
    // ── Dashboard & Metrics ──────────────────────────────────

    public function dashboard()
    {
        $totalUsers = User::count();
        $totalInterpretations = LabSubmission::whereHas('interpretation', fn($q) => $q->where('status', 'completed'))->count();
        $totalPayments = Payment::where('status', 'success')->count();
        $totalReferrals = ReferralEvent::count();
        $totalRevenue = Payment::where('status', 'success')->sum('amount_kobo');
        $totalProviders = ProviderDirectoryEntry::where('is_active', true)->count();
        $totalAppointments = Appointment::count();
        $totalFeedback = UserFeedback::count();

        $recentInterpretations = LabSubmission::with(['user', 'testPanel', 'interpretation'])
            ->whereHas('interpretation', fn($q) => $q->where('status', 'completed'))
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'user_name' => $s->user?->name ?? 'Unknown',
                'panel_name' => $s->testPanel?->name ?? ($s->submission_type === 'pdf' ? 'PDF Upload' : 'Lab Result'),
                'type' => $s->submission_type ?? 'panel',
                'created_at' => $s->created_at,
            ]);

        $recentPayments = Payment::with('user')
            ->where('status', 'success')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'user_name' => $p->user?->name ?? 'Unknown',
                'amount' => ($p->amount_kobo / 100) . ' NGN',
                'created_at' => $p->created_at,
            ]);

        $referralsByProvider = ReferralEvent::whereNotNull('provider_id')
            ->with('provider')
            ->selectRaw('provider_id, count(*) as total')
            ->groupBy('provider_id')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(fn($r) => [
                'provider_name' => $r->provider?->name ?? 'Unknown',
                'total' => $r->total,
            ]);

        // Time-series: daily user signups for last 14 days
        $fourteenDaysAgo = now()->subDays(14);
        $signupsPerDay = User::where('created_at', '>=', $fourteenDaysAgo)
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Daily revenue for last 14 days
        $revenuePerDay = Payment::where('status', 'success')
            ->where('created_at', '>=', $fourteenDaysAgo)
            ->selectRaw('DATE(created_at) as date, SUM(amount_kobo) as total, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($r) => [
                'date' => $r->date,
                'total' => (int) ($r->total / 100),
                'count' => $r->count,
            ]);

        // Submissions per day (last 14 days)
        $submissionsPerDay = LabSubmission::where('created_at', '>=', $fourteenDaysAgo)
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Health score distribution (from users who have lab submissions)
        $scoreBuckets = LabSubmission::selectRaw('user_id')
            ->groupBy('user_id')
            ->pluck('user_id');

        $healthScores = UserHealthMetric::whereIn('user_id', $scoreBuckets)
            ->where('metric_type', 'bmi')
            ->with('user:id,name')
            ->latest()
            ->get()
            ->groupBy('user_id')
            ->map(fn($metrics) => $metrics->first());

        return $this->success([
            'stats' => [
                'total_users' => $totalUsers,
                'total_interpretations' => $totalInterpretations,
                'total_payments' => $totalPayments,
                'total_referrals' => $totalReferrals,
                'total_revenue' => round($totalRevenue / 100, 2),
                'total_providers' => $totalProviders,
                'total_appointments' => $totalAppointments,
                'total_feedback' => $totalFeedback,
            ],
            'recent_interpretations' => $recentInterpretations,
            'recent_payments' => $recentPayments,
            'top_referral_providers' => $referralsByProvider,
            'charts' => [
                'signups_per_day' => $signupsPerDay,
                'revenue_per_day' => $revenuePerDay,
                'submissions_per_day' => $submissionsPerDay,
            ],
        ]);
    }

    // ── Test Panel CRUD ──

    public function panels()
    {
        $panels = TestPanel::withCount('ranges')->orderBy('name')->get();
        return $this->success(['panels' => $panels]);
    }

    public function panelShow(string $slug)
    {
        $panel = TestPanel::where('slug', $slug)->with('ranges')->firstOrFail();
        return $this->success(['panel' => $panel]);
    }

    public function panelUpdate(Request $request, string $slug)
    {
        $panel = TestPanel::where('slug', $slug)->firstOrFail();
        $panel->update($request->only(['name', 'description', 'category', 'is_active']));
        return $this->success(['panel' => $panel->fresh()], 'Panel updated');
    }

    // ── Symptom Mapping CRUD ──

    public function symptomMappings()
    {
        $mappings = \Illuminate\Support\Facades\DB::table('symptom_test_panels')
            ->join('symptoms', 'symptoms.id', '=', 'symptom_test_panels.symptom_id')
            ->join('test_panels', 'test_panels.id', '=', 'symptom_test_panels.test_panel_id')
            ->select('symptom_test_panels.id', 'symptoms.name as symptom_name', 'symptoms.slug as symptom_slug', 'test_panels.name as panel_name', 'test_panels.slug as panel_slug', 'symptom_test_panels.relevance_score')
            ->orderBy('symptoms.name')
            ->get();

        return $this->success(['mappings' => $mappings]);
    }

    public function symptomMappingStore(Request $request)
    {
        $validated = $request->validate([
            'symptom_slug' => 'required|exists:symptoms,slug',
            'panel_slug' => 'required|exists:test_panels,slug',
            'relevance_score' => 'required|integer|min:1|max:10',
        ]);

        $symptom = Symptom::where('slug', $validated['symptom_slug'])->firstOrFail();
        $panel = TestPanel::where('slug', $validated['panel_slug'])->firstOrFail();

        \Illuminate\Support\Facades\DB::table('symptom_test_panels')->updateOrInsert(
            ['symptom_id' => $symptom->id, 'test_panel_id' => $panel->id],
            ['relevance_score' => $validated['relevance_score'], 'updated_at' => now(), 'created_at' => now()],
        );

        return $this->success(null, 'Mapping saved');
    }

    public function symptomMappingDelete(int $id)
    {
        \Illuminate\Support\Facades\DB::table('symptom_test_panels')->where('id', $id)->delete();
        return $this->success(null, 'Mapping deleted');
    }

    // ── Provider CRUD ──

    public function providers(Request $request)
    {
        $query = ProviderDirectoryEntry::with('locations')
            ->withCount('locations');

        // Search by name, specialty, email, city or state.
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('specialty', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%");
            });
        }

        // Filter by provider type.
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        // Filter by partner status.
        if ($partnerStatus = $request->input('partner_status')) {
            $query->where('partner_status', $partnerStatus);
        }

        // Filter by active/inactive status.
        if ($request->has('is_active') && $request->input('is_active') !== null && $request->input('is_active') !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $providers = $query->orderBy('name')->paginate(20)->withQueryString();
        return $this->paginated($providers);
    }

    public function providerStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:hospital,clinic,lab,pharmacy,specialist,insurance',
            'specialty' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'partner_status' => 'nullable|in:none,affiliate,sponsored',
            'referral_link' => 'nullable|url|max:500',
            'insurance_plans' => 'nullable|array',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            // Monetization fields
            'monetization_type' => 'nullable|in:affiliate,sponsored',
            'monetization_rate' => 'nullable|integer|min:0',
            'monetization_amount' => 'nullable|integer|min:0',
            'monetization_limit_type' => 'nullable|in:time,views',
            'monetization_limit_value' => 'nullable|integer|min:0',
            'banner_url' => 'nullable|string|max:500',
            'logo_url' => 'nullable|string|max:500',
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

        // Normalize empty strings to null. The API middleware group does not run
        // ConvertEmptyStringsToNull, so JSON submissions with "" would otherwise
        // reach integer/decimal columns and fail MySQL strict mode.
        foreach ($validated as $key => $value) {
            if ($value === '') {
                $validated[$key] = null;
            }
        }

        $locations = $request->input('locations', []);
        unset($validated['locations']);

        $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
        $validated['is_verified'] ??= false;
        $validated['is_active'] ??= true;
        $validated['partner_status'] ??= 'none';
        $validated['country'] ??= 'NG';

        // Set monetization start if monetization is active
        if (!empty($validated['monetization_type']) && $validated['monetization_type'] !== 'none') {
            $validated['monetization_started_at'] = now();
            $validated['monetization_views_used'] = 0;
            // Calculate expiration for time-based
            if ($validated['monetization_limit_type'] === 'time' && !empty($validated['monetization_limit_value'])) {
                $validated['monetization_expires_at'] = now()->addDays((int) $validated['monetization_limit_value']);
            }
        }

        $baseSlug = $validated['slug'];
        $counter = 1;
        while (ProviderDirectoryEntry::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $baseSlug . '-' . $counter;
            $counter++;
        }

        $provider = ProviderDirectoryEntry::create($validated);

        $this->syncLocations($provider, $locations);

        return $this->success(['provider' => $provider->load('locations')], 'Provider created', 201);
    }

    public function providerUpdate(Request $request, string $slug)
    {
        $provider = ProviderDirectoryEntry::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|in:hospital,clinic,lab,pharmacy,specialist,insurance',
            'partner_status' => 'nullable|in:none,affiliate,sponsored',
            'monetization_type' => 'nullable|in:affiliate,sponsored',
            'monetization_rate' => 'nullable|integer|min:0',
            'monetization_amount' => 'nullable|integer|min:0',
            'monetization_limit_type' => 'nullable|in:time,views',
            'monetization_limit_value' => 'nullable|integer|min:0',
            'banner_url' => 'nullable|string|max:500',
            'logo_url' => 'nullable|string|max:500',
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

        $updateData = $request->only([
            'name', 'type', 'specialty', 'bio', 'phone', 'email',
            'address', 'city', 'state', 'website', 'partner_status',
            'referral_link', 'insurance_plans', 'is_verified', 'is_active',
            'latitude', 'longitude',
            'monetization_type', 'monetization_rate', 'monetization_amount',
            'monetization_limit_type', 'monetization_limit_value',
            'banner_url', 'logo_url',
        ]);

        foreach ($updateData as $key => $value) {
            if ($value === '') {
                $updateData[$key] = null;
            }
        }

        // If monetization is being activated or changed, reset tracking
        if (!empty($updateData['monetization_type']) && $updateData['monetization_type'] !== 'none') {
            // Only reset started_at if it wasn't already set
            if (!$provider->monetization_started_at) {
                $updateData['monetization_started_at'] = now();
            }
            $updateData['monetization_views_used'] = $provider->monetization_views_used ?? 0;
            // Recalculate expiration for time-based
            if ($updateData['monetization_limit_type'] === 'time' && !empty($updateData['monetization_limit_value'])) {
                $updateData['monetization_expires_at'] = now()->addDays((int) $updateData['monetization_limit_value']);
            }
        } else {
            // Monetization removed — clear all fields
            $updateData['monetization_type'] = null;
            $updateData['monetization_rate'] = null;
            $updateData['monetization_amount'] = null;
            $updateData['monetization_limit_type'] = null;
            $updateData['monetization_limit_value'] = null;
            $updateData['monetization_started_at'] = null;
            $updateData['monetization_expires_at'] = null;
            $updateData['monetization_views_used'] = null;
        }

        $provider->update($updateData);

        if ($request->has('locations')) {
            $this->syncLocations($provider, $request->input('locations', []));
        }

        return $this->success(['provider' => $provider->fresh()->load('locations')], 'Provider updated');
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
     * Upload a provider asset (logo/banner) and return its public URL.
     */
    public function providerAssetUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120',
        ]);

        $path = $request->file('file')->store('provider-assets', 'public');

        return $this->success([
            'url' => '/storage/' . $path,
        ], 'Asset uploaded');
    }

    public function providerToggleActive(string $slug)
    {
        $provider = ProviderDirectoryEntry::where('slug', $slug)->firstOrFail();
        $provider->update(['is_active' => !$provider->is_active]);
        return $this->success(['provider' => $provider->fresh()], 'Toggled');
    }

    public function generateProviderAccessCode(string $slug)
    {
        $provider = ProviderDirectoryEntry::where('slug', $slug)->firstOrFail();

        if ($provider->partner_status === 'none') {
            return $this->error('Provider is not a partner. Set partner status to affiliate or sponsored first.', 422);
        }

        $provider->update([
            'access_code' => \Illuminate\Support\Str::random(40),
            'access_code_generated_at' => now(),
        ]);

        // Log for audit
        \Illuminate\Support\Facades\DB::table('admin_audit_log')->insert([
            'admin_id' => request()->user()->id,
            'action' => 'generate_partner_access_code',
            'target_type' => 'provider',
            'target_id' => $provider->id,
            'metadata' => json_encode([
                'target_name' => $provider->name,
                'partner_status' => $provider->partner_status,
            ]),
            'created_at' => now(),
        ]);

        return $this->success([
            'provider_id' => $provider->id,
            'access_code' => $provider->access_code,
            'login_url' => config('app.url') . '/partner/login',
        ], 'Access code generated. Share this code with the partner to log in.');
    }

    // ── Credit Packages CRUD ──

    public function creditPackages()
    {
        $packages = CreditPackage::orderBy('credits')->get();
        return $this->success(['packages' => $packages]);
    }

    public function creditPackageUpdate(Request $request, int $id)
    {
        $pkg = CreditPackage::findOrFail($id);
        $data = $request->only(['name', 'credits', 'description', 'is_active']);

        if ($request->has('price_ngn')) {
            $data['price_kobo'] = (int) ($request->input('price_ngn') * 100);
        }

        $pkg->update($data);
        return $this->success(['package' => $pkg->fresh()], 'Package updated');
    }

    public function creditPackageStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'credits' => 'required|integer|min:1',
            'price_ngn' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $data = collect($validated)->except('price_ngn')->toArray();
        $data['price_kobo'] = (int) ($validated['price_ngn'] * 100);
        $data['is_active'] = true;

        $pkg = CreditPackage::create($data);
        return $this->success(['package' => $pkg], 'Package created', 201);
    }

    // ── Users ──

    public function users(Request $request)
    {
        $query = User::with('roles');

        // Search by name, email, or phone.
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by role name.
        if ($role = $request->input('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        // Filter by email verification status.
        if ($request->input('email_verified') !== null && $request->input('email_verified') !== '') {
            if ($request->boolean('email_verified')) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        $users = $query->latest()->paginate(25)->withQueryString();
        $users->getCollection()->transform(function ($user) {
            $user->credits = app(\App\Services\CreditService::class)->getBalance($user);
            return $user;
        });
        return $this->paginated($users);
    }

    public function trashedUsers()
    {
        $users = User::onlyTrashed()->with('roles')->latest('deleted_at')->paginate(25);
        $users->getCollection()->transform(function ($user) {
            $user->credits = app(\App\Services\CreditService::class)->getBalance($user);
            return $user;
        });
        return $this->paginated($users);
    }

    public function softDeleteUser(int $id)
    {
        $user = User::findOrFail($id);

        // Revoke all Sanctum tokens
        $user->tokens()->delete();

        // Deactivate push subscriptions (don't delete — they may be re-used if restored)
        \App\Models\PushSubscription::where('user_id', $user->id)->update(['is_active' => false]);

        // Soft delete the user
        $user->delete();

        // Log for audit
        \Illuminate\Support\Facades\DB::table('admin_audit_log')->insert([
            'admin_id' => request()->user()->id,
            'action' => 'soft_delete_user',
            'target_type' => 'user',
            'target_id' => $user->id,
            'metadata' => json_encode([
                'target_name' => $user->name,
                'target_email' => $user->email,
            ]),
            'created_at' => now(),
        ]);

        return $this->success(null, 'User has been deactivated and moved to trash.');
    }

    public function restoreUser(int $id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        // Reactivate push subscriptions
        \App\Models\PushSubscription::where('user_id', $user->id)->update(['is_active' => true]);

        \Illuminate\Support\Facades\DB::table('admin_audit_log')->insert([
            'admin_id' => request()->user()->id,
            'action' => 'restore_user',
            'target_type' => 'user',
            'target_id' => $user->id,
            'metadata' => json_encode([
                'target_name' => $user->name,
                'target_email' => $user->email,
            ]),
            'created_at' => now(),
        ]);

        return $this->success(null, 'User has been restored.');
    }

    public function forceDeleteUser(int $id)
    {
        $user = User::onlyTrashed()->findOrFail($id);

        // Permanently remove related data
        $user->tokens()->forceDelete();
        \App\Models\PushSubscription::where('user_id', $user->id)->delete();
        \App\Models\HealthProfile::where('user_id', $user->id)->delete();
        \App\Models\CreditLedger::where('user_id', $user->id)->delete();
        \App\Models\UserHealthMetric::where('user_id', $user->id)->delete();
        \App\Models\UserTrackerSnapshot::where('user_id', $user->id)->delete();

        // Lab submissions & interpretations — keep for audit integrity
        // (They're linked by user_id; we nullify the link instead)
        \App\Models\LabSubmission::where('user_id', $user->id)->update(['user_id' => null]);
        \App\Models\Appointment::where('user_id', $user->id)->delete();
        \App\Models\UserFeedback::where('user_id', $user->id)->delete();
        \App\Models\ReferralEvent::where('referrer_id', $user->id)->delete();
        \App\Models\Payment::where('user_id', $user->id)->update(['user_id' => null]);

        // Remove Spatie roles/permissions
        $user->roles()->detach();
        $user->permissions()->detach();

        $userName = $user->name;
        $userEmail = $user->email;
        $user->forceDelete();

        \Illuminate\Support\Facades\DB::table('admin_audit_log')->insert([
            'admin_id' => request()->user()->id,
            'action' => 'force_delete_user',
            'target_type' => 'user',
            'target_id' => $id,
            'metadata' => json_encode([
                'target_name' => $userName,
                'target_email' => $userEmail,
            ]),
            'created_at' => now(),
        ]);

        return $this->success(null, 'User permanently deleted along with associated data.');
    }

    public function grantCredits(Request $request, int $id)
    {
        $validated = $request->validate([
            'credits' => 'required|integer|min:1|max:1000',
            'reason' => 'nullable|string|max:500',
        ]);

        $user = User::findOrFail($id);
        $creditService = app(\App\Services\CreditService::class);
        $newBalance = $creditService->credit($user, $validated['credits'], 'admin_grant');

        // Log the credit grant for audit
        \Illuminate\Support\Facades\DB::table('admin_audit_log')->insert([
            'admin_id' => $request->user()->id,
            'action' => 'grant_credits',
            'target_type' => 'user',
            'target_id' => $user->id,
            'metadata' => json_encode([
                'target_name' => $user->name,
                'credits_granted' => $validated['credits'],
                'reason' => $validated['reason'] ?? null,
                'new_balance' => $newBalance,
            ]),
            'created_at' => now(),
        ]);

        // Send congratulatory email to the user
        $creditsText = $validated['credits'] . ' ' . \Illuminate\Support\Str::plural('credit', $validated['credits']);
        $hasReason = !empty($validated['reason']);
        $reasonText = $hasReason ? e($validated['reason']) : 'to help you get the most out of HealthIntel';
        $appUrl = config('app.url');

        $plainText = "Hi " . $user->name . ",\n\n"
            . "You've been gifted " . $creditsText . " " . $reasonText . ".\n\n"
            . "Your new balance is " . $newBalance . " credits. You can use them to upload lab reports, interpret your results, and explore your health.\n\n"
            . "Go to your dashboard: " . $appUrl . "/dashboard\n\n"
            . "Kind regards,\n"
            . "The HealthIntel Team";

        try {
            \Mail::send([], [], function ($message) use ($user, $plainText, $creditsText, $reasonText, $hasReason, $newBalance, $appUrl) {
                $message->to($user->email, $user->name)
                    ->subject($creditsText . ' added to your HealthIntel account')
                    ->text($plainText)
                    ->html(
                        '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head>'
                        . '<body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Arial, sans-serif; max-width: 560px; margin: 0 auto; padding: 32px 20px; color: #1B2622; background: #F4F6F3;">'
                        . '<table width="100%" cellpadding="0" cellspacing="0" style="background: #FFFFFF; border-radius: 14px; overflow: hidden; border: 1px solid #DCE3DE;">'
                            . '<tr><td style="padding: 32px 28px 24px;">'
                                . '<p style="font-size: 14px; color: #57645D; margin: 0 0 8px;">Hello ' . e($user->name) . ',</p>'
                                . '<p style="font-size: 16px; line-height: 1.6; color: #1B2622; margin: 0 0 24px;">'
                                    . 'We wanted to let you know that <strong>' . e($creditsText) . '</strong> ' . ($hasReason ? 'have been added manually to your account.' : 'have been added to your account.') . '</p>'
                                . '<table width="100%" cellpadding="0" cellspacing="0" style="background: rgba(14,107,92,0.06); border-radius: 10px; margin-bottom: 24px;">'
                                    . '<tr><td style="padding: 20px 24px; text-align: center;">'
                                        . '<p style="font-size: 13px; color: #57645D; margin: 0 0 4px;">Your new balance</p>'
                                        . '<p style="font-size: 32px; font-weight: 700; color: #0E6B5C; margin: 0; line-height: 1;">' . (int)$newBalance . '</p>'
                                        . '<p style="font-size: 13px; color: #57645D; margin: 4px 0 0;">credits</p>'
                                    . '</td></tr>'
                                . '</table>'
                                . '<p style="font-size: 14px; color: #57645D; line-height: 1.6; margin: 0 0 8px;">Use them to upload lab reports, enter values manually, or run a symptom check — each interpretation uses a credit from your balance.</p>'
                                . '<p style="font-size: 14px; color: #57645D; line-height: 1.6; margin: 0 0 24px;">If you have any questions, just reply to this email.</p>'
                                . '<a href="' . $appUrl . '/dashboard" style="display: inline-block; background: #0E6B5C; color: #FFFFFF; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;">Go to your dashboard</a>'
                            . '</td></tr>'
                            . '<tr><td style="padding: 16px 28px; background: #F9FAFB; border-top: 1px solid #E8EBE7; font-size: 12px; color: #9CA3AF; line-height: 1.6;">'
                                . 'This email was sent because an administrator at HealthIntel added credits to your account. '
                                . 'HealthIntel — Understand your health, in plain language.'
                            . '</td></tr>'
                        . '</table>'
                        . '</body></html>'
                    );
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                'Credit grant email failed for user ' . $user->id . ': ' . $e->getMessage()
            );
        }

        return $this->success([
            'user_id' => $user->id,
            'credits_added' => $validated['credits'],
            'new_balance' => $newBalance,
        ], 'Credits granted successfully');
    }

    public function userShow(int $id)
    {
        $user = User::with([
            'roles',
            'healthProfile',
            'labSubmissions' => function ($q) { $q->with('testPanel', 'interpretation')->latest()->take(10); },
        ])->findOrFail($id);

        $creditService = app(\App\Services\CreditService::class);
        $user->credits = $creditService->getBalance($user);

        // 360° activity data — built safely with individual queries
        $creditLedger = [];
        $payments = [];
        $appointments = [];
        $feedback = [];
        $auditLogs = [];
        $interpretations = [];
        $healthMetrics = [];
        $referrals = [];

        try { $creditLedger = \App\Models\CreditLedger::where('user_id', $id)->latest()->take(20)->get()->map(function ($e) {
            return ['id' => $e->id, 'amount' => $e->amount, 'type' => $e->type, 'description' => $e->description, 'created_at' => $e->created_at->toISOString()];
        })->toArray(); } catch (\Throwable) {}

        try { $payments = \App\Models\Payment::where('user_id', $id)->latest()->take(10)->get()->map(function ($p) {
            return ['id' => $p->id, 'reference' => $p->reference, 'amount_kobo' => $p->amount_kobo, 'amount_naira' => ($p->amount_kobo ?? 0) / 100, 'status' => $p->status, 'gateway' => $p->gateway, 'created_at' => $p->created_at?->toISOString()];
        })->toArray(); } catch (\Throwable) {}

        try { $appointments = \App\Models\Appointment::where('user_id', $id)->latest('appointment_date')->take(10)->get()->map(function ($a) {
            return ['id' => $a->id, 'title' => $a->title, 'doctor_name' => $a->doctor_name, 'facility_name' => $a->facility_name, 'appointment_date' => $a->appointment_date, 'status' => $a->status];
        })->toArray(); } catch (\Throwable) {}

        try { $feedback = \App\Models\UserFeedback::where('user_id', $id)->latest()->take(10)->get()->map(function ($f) {
            return ['id' => $f->id, 'content' => $f->content, 'rating' => $f->rating ?? null, 'status' => $f->status, 'created_at' => $f->created_at->toISOString()];
        })->toArray(); } catch (\Throwable) {}

        try {
            $auditLogs = \Illuminate\Support\Facades\DB::table('admin_audit_log')
                ->where('target_type', 'user')->where('target_id', $id)
                ->leftJoin('users', 'users.id', '=', 'admin_audit_log.admin_id')
                ->select('admin_audit_log.*', 'users.name as admin_name')
                ->latest('admin_audit_log.created_at')->take(20)->get()
                ->map(function ($l) {
                    return ['id' => $l->id, 'action' => $l->action, 'admin_name' => $l->admin_name ?? 'System', 'metadata' => json_decode($l->metadata), 'created_at' => $l->created_at];
                })->toArray();
        } catch (\Throwable) {}

        try {
            $interpretations = \App\Models\LabSubmission::where('user_id', $id)->whereHas('interpretation')
                ->with(['testPanel', 'interpretation'])->latest()->take(10)->get()->map(function ($s) {
                    return ['id' => $s->interpretation->id ?? $s->id, 'panel_name' => $s->testPanel?->name ?? 'PDF Upload', 'status' => $s->interpretation->status ?? 'pending', 'created_at' => $s->created_at->toISOString()];
                })->toArray();
        } catch (\Throwable) {}

        try { $healthMetrics = \App\Models\UserHealthMetric::where('user_id', $id)->latest()->take(10)->get()->map(function ($m) {
            return ['id' => $m->id, 'tracker_type' => $m->tracker_type, 'tracker_label' => $m->tracker_label ?? $m->tracker_type, 'value' => $m->value, 'unit' => $m->unit, 'created_at' => $m->created_at?->toISOString()];
        })->toArray(); } catch (\Throwable) {}

        try { $referrals = \App\Models\ReferralEvent::where('referrer_id', $id)->latest()->take(10)->get()->map(function ($r) {
            return ['id' => $r->id, 'event_type' => $r->event_type, 'metadata' => $r->metadata, 'created_at' => $r->created_at?->toISOString()];
        })->toArray(); } catch (\Throwable) {}

        $activity = compact('creditLedger', 'payments', 'appointments', 'feedback', 'auditLogs', 'interpretations', 'healthMetrics', 'referrals');

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'email_verified_at' => $user->email_verified_at?->toISOString(),
                'phone_verified_at' => $user->phone_verified_at?->toISOString(),
                'credits' => $user->credits,
                'roles' => $user->getRoleNames()->toArray(),
                'health_profile' => $user->healthProfile ? [
                    'date_of_birth' => $user->healthProfile->date_of_birth?->toDateString(),
                    'sex' => $user->healthProfile->sex,
                    'is_pregnant' => $user->healthProfile->is_pregnant,
                    'height_cm' => $user->healthProfile->height_cm,
                    'weight_kg' => $user->healthProfile->weight_kg,
                    'blood_type' => $user->healthProfile->blood_type,
                    'medical_conditions' => $user->healthProfile->medical_conditions,
                    'current_medications' => $user->healthProfile->current_medications,
                    'profile_completed' => $user->healthProfile->profile_completed,
                ] : null,
                'submissions' => $user->labSubmissions->map(fn($s) => [
                    'id' => $s->id,
                    'panel_name' => $s->testPanel?->name,
                    'type' => $s->submission_type,
                    'status' => $s->interpretation?->status ?? 'pending',
                    'created_at' => $s->created_at->toISOString(),
                ]),
                'created_at' => $user->created_at->toISOString(),
            ],
            'activity' => $activity,
        ]);
    }

    // ── Appointments ──

    public function appointments()
    {
        $appointments = Appointment::with('user:id,name,email')
            ->latest('appointment_date')
            ->paginate(25);

        return $this->paginated($appointments);
    }

    public function appointmentUpdate(Request $request, int $id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->update($request->only([
            'title', 'doctor_name', 'facility_name', 'appointment_date',
            'notes', 'status', 'reminder_enabled', 'reminder_minutes_before',
        ]));
        return $this->success(['appointment' => $appointment->fresh()], 'Appointment updated');
    }

    // ── User Feedback ──

    public function feedback()
    {
        $feedback = UserFeedback::with('user:id,name,email')
            ->latest()
            ->paginate(25);

        return $this->paginated($feedback);
    }

    public function feedbackUpdate(Request $request, int $id)
    {
        $fb = UserFeedback::findOrFail($id);
        $fb->update($request->only(['status', 'admin_notes']));
        return $this->success(['feedback' => $fb->fresh()], 'Feedback updated');
    }

    // ── Partner Portal ──

    public function partners()
    {
        $partners = ProviderDirectoryEntry::where('partner_status', '!=', 'none')
            ->withCount(['referralEvents as clicks_30d' => fn($q) => $q->where('created_at', '>=', now()->subDays(30))])
            ->orderBy('name')
            ->paginate(25);

        return $this->paginated($partners);
    }

    // ── PDF Submissions ──

    public function pdfSubmissions()
    {
        $submissions = LabSubmission::where('submission_type', 'pdf')
            ->with(['user:id,name,email', 'interpretation'])
            ->latest('submitted_at')
            ->paginate(25);

        return $this->paginated($submissions);
    }

    // ── Submissions (all types) ──

    public function submissions()
    {
        $submissions = LabSubmission::with(['user:id,name,email', 'testPanel:id,name', 'interpretation'])
            ->latest('submitted_at')
            ->paginate(25);

        return $this->paginated($submissions);
    }

    // ── Analytics ──

    public function analytics()
    {
        $thirtyDaysAgo = now()->subDays(30);
        $sevenDaysAgo = now()->subDays(7);

        // ═══════════════════════════════════════════════
        // 1. TIME-SERIES DATA (daily for 30 days)
        // ═══════════════════════════════════════════════
        $submissionsPerDay = LabSubmission::where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')->orderBy('date')->get();

        $referralsPerDay = ReferralEvent::where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')->orderBy('date')->get();

        $revenuePerDay = Payment::where('status', 'success')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('DATE(created_at) as date, SUM(amount_kobo) as total, count(*) as tx_count')
            ->groupBy('date')->orderBy('date')
            ->get()
            ->map(fn($r) => ['date' => $r->date, 'total' => (int)($r->total / 100), 'tx_count' => $r->tx_count]);

        $newUsersPerDay = User::where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')->orderBy('date')->get();

        // ═══════════════════════════════════════════════
        // 2. USER METRICS
        // ═══════════════════════════════════════════════
        $totalUsers = User::count();
        $newUsers30d = User::where('created_at', '>=', $thirtyDaysAgo)->count();
        $newUsers7d = User::where('created_at', '>=', $sevenDaysAgo)->count();

        // Active users (submitted labs in last 30 days)
        $activeUsers30d = LabSubmission::where('created_at', '>=', $thirtyDaysAgo)
            ->distinct('user_id')->count('user_id');

        // Users with completed health profiles
        $profileCompleted = \App\Models\HealthProfile::where('profile_completed', true)->count();

        // Users with role breakdown
        $roleCounts = \Illuminate\Support\Facades\DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->selectRaw('roles.name, count(*) as count')
            ->groupBy('roles.name')->get();

        // ═══════════════════════════════════════════════
        // 3. REVENUE METRICS
        // ═══════════════════════════════════════════════
        $totalRevenue = (int)(Payment::where('status', 'success')->sum('amount_kobo') / 100);
        $revenue30d = (int)(Payment::where('status', 'success')->where('created_at', '>=', $thirtyDaysAgo)->sum('amount_kobo') / 100);
        $revenue7d = (int)(Payment::where('status', 'success')->where('created_at', '>=', $sevenDaysAgo)->sum('amount_kobo') / 100);

        $totalTransactions = Payment::where('status', 'success')->count();
        $transactions30d = Payment::where('status', 'success')->where('created_at', '>=', $thirtyDaysAgo)->count();

        // ARPU (average revenue per paying user, 30d)
        $payingUsers30d = Payment::where('status', 'success')->where('created_at', '>=', $thirtyDaysAgo)
            ->distinct('user_id')->count('user_id');
        $arpu = $payingUsers30d > 0 ? round($revenue30d / $payingUsers30d) : 0;

        // Conversion rate: users who paid / total active users
        $conversionRate = $activeUsers30d > 0 ? round(($payingUsers30d / $activeUsers30d) * 100, 1) : 0;

        // Payment provider breakdown (the `provider` column stores the gateway name)
        $paymentMethods = Payment::where('status', 'success')
            ->selectRaw('provider, count(*) as count, SUM(amount_kobo) as total_kobo')
            ->groupBy('provider')->get()
            ->map(fn($r) => ['method' => $r->provider ?? 'unknown', 'count' => $r->count, 'total' => (int)($r->total_kobo / 100)]);

        // ═══════════════════════════════════════════════
        // 4. CREDIT ECONOMY
        // ═══════════════════════════════════════════════
        $creditsSold30d = Payment::where('status', 'success')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->with('purchasable')
            ->get()
            ->sum(fn($p) => $p->purchasable?->credits ?? 0);

        $creditsUsed30d = LabSubmission::where('created_at', '>=', $thirtyDaysAgo)
            ->sum('credits_used');

        // Average credits per user
        $usersWithSubmissions = LabSubmission::distinct('user_id')->count('user_id');
        $avgCreditsPerUser = $usersWithSubmissions > 0 ? round(LabSubmission::sum('credits_used') / $usersWithSubmissions) : 0;

        // ═══════════════════════════════════════════════
        // 5. ENGAGEMENT METRICS
        // ═══════════════════════════════════════════════
        // Tool usage
        $bmiEntries = UserHealthMetric::where('metric_type', 'bmi')->count();
        $bmrEntries = UserHealthMetric::where('metric_type', 'bmr')->count();
        $whrEntries = UserHealthMetric::where('metric_type', 'waist_hip_ratio')->count();
        $dueDateEntries = UserHealthMetric::where('metric_type', 'due_date')->count();

        $appointmentCount = Appointment::count();
        $symptomChecks = \App\Models\AiInterpretation::where('created_at', '>=', $thirtyDaysAgo)->count();

        // Feedback count by status
        $feedbackByStatus = UserFeedback::selectRaw("COALESCE(status, 'new') as status, count(*) as count")
            ->groupBy('status')->get();

        // ═══════════════════════════════════════════════
        // 6. CONTENT METRICS
        // ═══════════════════════════════════════════════
        // Panel usage breakdown (all time)
        $panelUsage = LabSubmission::whereNotNull('test_panel_id')
            ->with('testPanel:id,name')
            ->get()
            ->groupBy('test_panel_id')
            ->map(fn($subs) => ['panel_name' => $subs->first()->testPanel?->name ?? 'Unknown', 'total' => $subs->count()])
            ->sortByDesc('total')->take(10)->values();

        // PDF vs Panel split (30d)
        $submissionTypeSplit = LabSubmission::where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw("CASE WHEN submission_type = 'pdf' OR test_panel_id IS NULL THEN 'pdf' ELSE 'panel' END as type, count(*) as count")
            ->groupBy('type')->get();

        // Provider distribution by type
        $providersByType = ProviderDirectoryEntry::where('is_active', true)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')->orderByDesc('count')->get();

        // Top checked symptoms via symptom_test_panels pivot (most-used symptoms in submissions)
        $topSymptoms = \Illuminate\Support\Facades\DB::table('symptom_test_panels')
            ->join('symptoms', 'symptoms.id', '=', 'symptom_test_panels.symptom_id')
            ->join('lab_submissions', 'lab_submissions.test_panel_id', '=', 'symptom_test_panels.test_panel_id')
            ->where('lab_submissions.created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('symptoms.name, count(distinct lab_submissions.id) as count')
            ->groupBy('symptoms.id', 'symptoms.name')
            ->orderByDesc('count')
            ->take(10)
            ->get()
            ->map(fn($r) => ['name' => $r->name, 'count' => (int) $r->count]);

        $totalSymptoms = Symptom::count();
        $totalPanels = TestPanel::where('is_active', true)->count();
        $totalSymptomMappings = \Illuminate\Support\Facades\DB::table('symptom_test_panels')->count();

        // ═══════════════════════════════════════════════
        // 7. HEALTH SCORE DISTRIBUTION
        // ═══════════════════════════════════════════════
        $userIdsWithLabs = LabSubmission::select('user_id')->groupBy('user_id')->pluck('user_id');
        $bmiScores = UserHealthMetric::where('metric_type', 'bmi')
            ->whereIn('user_id', $userIdsWithLabs)
            ->get();
        $bmiDistribution = [['category' => 'Underweight', 'count' => 0], ['category' => 'Normal', 'count' => 0], ['category' => 'Overweight', 'count' => 0], ['category' => 'Obese', 'count' => 0]];
        foreach ($bmiScores as $b) {
            $v = (float)($b->data['bmi'] ?? 0);
            if ($v < 18.5) $bmiDistribution[0]['count']++;
            elseif ($v < 25) $bmiDistribution[1]['count']++;
            elseif ($v < 30) $bmiDistribution[2]['count']++;
            else $bmiDistribution[3]['count']++;
        }

        return $this->success([
            // Time-series
            'submissions_per_day' => $submissionsPerDay,
            'referrals_per_day' => $referralsPerDay,
            'revenue_per_day' => $revenuePerDay,
            'new_users_per_day' => $newUsersPerDay,

            // User KPIs
            'users' => [
                'total' => $totalUsers, 'new_30d' => $newUsers30d, 'new_7d' => $newUsers7d,
                'active_30d' => $activeUsers30d, 'profile_completed' => $profileCompleted,
                'role_breakdown' => $roleCounts,
            ],

            // Revenue KPIs
            'revenue' => [
                'total' => $totalRevenue, '30d' => $revenue30d, '7d' => $revenue7d,
                'total_transactions' => $totalTransactions, 'transactions_30d' => $transactions30d,
                'arpu' => $arpu, 'conversion_rate' => $conversionRate,
                'payment_methods' => $paymentMethods,
            ],

            // Credit economy
            'credits' => [
                'sold_30d' => $creditsSold30d, 'used_30d' => $creditsUsed30d,
                'avg_per_user' => $avgCreditsPerUser,
            ],

            // Engagement
            'engagement' => [
                'bmi_count' => $bmiEntries, 'bmr_count' => $bmrEntries,
                'whr_count' => $whrEntries, 'due_date_count' => $dueDateEntries,
                'appointments' => $appointmentCount, 'symptom_checks_30d' => $symptomChecks,
                'feedback_by_status' => $feedbackByStatus,
            ],

            // Content
            'content' => [
                'panel_usage' => $panelUsage, 'submission_type_split' => $submissionTypeSplit,
                'providers_by_type' => $providersByType, 'top_symptoms' => $topSymptoms,
                'total_symptoms' => $totalSymptoms, 'total_panels' => $totalPanels,
                'total_symptom_mappings' => $totalSymptomMappings,
            ],

            // Health distribution
            'health_distribution' => $bmiDistribution,
        ]);
    }

    // ── AI Analyzer (DeepSeek-powered growth & marketing insights) ──

    public function aiAnalyzer()
    {
        $result = app(\App\Services\AiAnalyzerService::class)->analyze();

        return $this->success($result, 'AI analysis generated');
    }

    // ── Notifications ──

    public function notifications()
    {
        $notifications = \App\Models\AdminNotification::with('creator:id,name')
            ->latest()
            ->paginate(25);

        return $this->paginated($notifications);
    }

    public function notificationStore(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:500',
            'target' => 'required|in:all,users,partners',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
            'url' => 'nullable|string|max:500',
        ]);

        $notification = \App\Models\AdminNotification::create([
            'admin_id' => $request->user()->id,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'target' => $validated['target'],
            'user_ids' => $validated['user_ids'] ?? [],
            'url' => $validated['url'] ?? null,
        ]);

        // Create in-app notifications (user_notifications) so users see them
        // in their notification page/drawer, in addition to the Web Push.
        try {
            $targetUserIds = null;

            if (!empty($validated['user_ids'])) {
                $targetUserIds = $validated['user_ids'];
            } elseif ($validated['target'] === 'all') {
                $targetUserIds = User::pluck('id')->all();
            }

            if (!empty($targetUserIds)) {
                $rows = [];
                $now = now();

                foreach ($targetUserIds as $userId) {
                    $rows[] = [
                        'user_id'    => $userId,
                        'type'       => 'admin',
                        'title'      => $validated['title'],
                        'body'       => $validated['body'],
                        'data'       => json_encode(['notification_id' => $notification->id]),
                        'action_url' => $validated['url'] ?? null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                \App\Models\UserNotification::insert($rows);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                'Admin notification in-app creation failed: ' . $e->getMessage()
            );
        }

        // Dispatch Web Push notifications to subscribed users
        if (config('webpush.send_admin_notifications', true)) {
            $webPushService = app(\App\Services\WebPushService::class);

            $pushOptions = [
                'url' => $validated['url'] ?? '/dashboard',
                'notification_id' => $notification->id,
                'requireInteraction' => true,
            ];

            // Dispatch in the background via a queue or fire-and-forget
            try {
                if (!empty($validated['user_ids'])) {
                    // Target specific users
                    foreach ($validated['user_ids'] as $userId) {
                        $webPushService->sendToUser(
                            $userId,
                            $validated['title'],
                            $validated['body'],
                            $pushOptions
                        );
                    }
                } elseif ($validated['target'] === 'all') {
                    // Target all users
                    $webPushService->sendToAll(
                        $validated['title'],
                        $validated['body'],
                        $pushOptions
                    );
                }
                // 'partners' target — only partners have separate notification handling
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning(
                    'Admin notification web-push dispatch error: ' . $e->getMessage()
                );
            }
        }

        return $this->success(['notification' => $notification], 'Notification sent', 201);
    }

    // ── Audit Log ──

    public function auditLog()
    {
        $logs = \Illuminate\Support\Facades\DB::table('admin_audit_log')
            ->leftJoin('users', 'users.id', '=', 'admin_audit_log.admin_id')
            ->select('admin_audit_log.*', 'users.name as admin_name')
            ->latest('admin_audit_log.created_at')
            ->paginate(50);

        return $this->paginated($logs);
    }

    // ── Email Campaigns ──

    /**
     * Get available token list and role list for email composition.
     */
    public function emailTokens()
    {
        $emailService = app(EmailService::class);

        return $this->success([
            'tokens' => $emailService->availableTokens(),
            'roles'  => \Spatie\Permission\Models\Role::pluck('name')->toArray(),
        ]);
    }

    /**
     * Preview the recipient count for given filters.
     */
    public function emailPreview(Request $request)
    {
        $validated = $request->validate([
            'roles'             => 'nullable|array',
            'roles.*'           => 'string',
            'has_submissions'   => 'nullable|boolean',
            'email_verified'    => 'nullable|boolean',
            'signup_from'       => 'nullable|date',
            'signup_to'         => 'nullable|date',
            'user_ids'          => 'nullable|array',
            'user_ids.*'        => 'integer|exists:users,id',
        ]);

        $emailService = app(EmailService::class);
        $filters = $this->buildEmailFilters($validated);
        $count = $emailService->countRecipients($filters);

        return $this->success(['recipient_count' => $count]);
    }

    /**
     * Send a bulk email campaign to users matching the given filters.
     */
    public function emailSend(Request $request)
    {
        $validated = $request->validate([
            'subject'           => 'required|string|max:255',
            'body_html'         => 'required|string|max:50000',
            'body_text'         => 'nullable|string|max:50000',
            'roles'             => 'nullable|array',
            'roles.*'           => 'string',
            'has_submissions'   => 'nullable|boolean',
            'email_verified'    => 'nullable|boolean',
            'signup_from'       => 'nullable|date',
            'signup_to'         => 'nullable|date',
            'user_ids'          => 'nullable|array',
            'user_ids.*'        => 'integer|exists:users,id',
        ]);

        $emailService = app(EmailService::class);
        $filters = $this->buildEmailFilters($validated);

        $result = $emailService->sendBulk(
            $filters,
            $validated['subject'],
            $validated['body_html'],
            $validated['body_text'] ?? null
        );

        // Log for audit
        \Illuminate\Support\Facades\DB::table('admin_audit_log')->insert([
            'admin_id'  => $request->user()->id,
            'action'    => 'send_email_campaign',
            'target_type' => 'users',
            'target_id'  => null,
            'metadata'  => json_encode([
                'subject'   => $validated['subject'],
                'filters'   => $filters,
                'total'     => $result['total'],
                'sent'      => $result['sent'],
                'failed'    => $result['failed'],
            ]),
            'created_at' => now(),
        ]);

        return $this->success([
            'total_recipients'  => $result['total'],
            'sent'              => $result['sent'],
            'failed'            => $result['failed'],
        ], "Email sent to {$result['sent']} of {$result['total']} recipients" . ($result['failed'] > 0 ? " ({$result['failed']} failed)" : ''));
    }

    /**
     * Send a test/personalised email to a single user (for preview).
     */
    public function emailSendTest(Request $request)
    {
        $validated = $request->validate([
            'user_id'       => 'required|integer|exists:users,id',
            'subject'       => 'required|string|max:255',
            'body_html'     => 'required|string|max:50000',
            'body_text'     => 'nullable|string|max:50000',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $emailService = app(EmailService::class);

        $success = $emailService->sendToUser(
            $user,
            $validated['subject'],
            $validated['body_html'],
            $validated['body_text'] ?? null
        );

        if ($success) {
            return $this->success(null, "Test email sent to {$user->email}");
        }

        return $this->error('Failed to send test email. Check mail configuration.', 500);
    }

    /**
     * Build filter array from validated request data.
     */
    private function buildEmailFilters(array $validated): array
    {
        $filters = [];

        if (!empty($validated['roles'])) {
            $filters[EmailService::FILTER_ROLES] = $validated['roles'];
        }
        if (array_key_exists('has_submissions', $validated) && $validated['has_submissions'] !== null) {
            $filters[EmailService::FILTER_HAS_SUBMISSIONS] = $validated['has_submissions'];
        }
        if (array_key_exists('email_verified', $validated) && $validated['email_verified'] !== null) {
            $filters[EmailService::FILTER_EMAIL_VERIFIED] = $validated['email_verified'];
        }
        if (!empty($validated['signup_from'])) {
            $filters[EmailService::FILTER_SIGNUP_FROM] = $validated['signup_from'];
        }
        if (!empty($validated['signup_to'])) {
            $filters[EmailService::FILTER_SIGNUP_TO] = $validated['signup_to'];
        }
        if (!empty($validated['user_ids'])) {
            $filters[EmailService::FILTER_USER_IDS] = $validated['user_ids'];
        }

        return $filters;
    }

    // ── Referral Program Management ──

    public function referralSettings()
    {
        $percentage = (int) \App\Models\Setting::getValue('referral.percentage', 10);
        $maxPayouts = (int) \App\Models\Setting::getValue('referral.max_payouts_per_referral', 3);
        $minThreshold = (int) \App\Models\Setting::getValue('referral.min_payout_threshold_naira', 5000);

        return $this->success([
            'percentage' => $percentage,
            'max_payouts_per_referral' => $maxPayouts,
            'min_payout_threshold_naira' => $minThreshold,
        ]);
    }

    public function referralSettingsUpdate(Request $request)
    {
        $validated = $request->validate([
            'percentage' => 'sometimes|integer|min:1|max:100',
            'max_payouts_per_referral' => 'sometimes|integer|min:1|max:100',
            'min_payout_threshold_naira' => 'sometimes|integer|min:100|max:1000000',
        ]);

        foreach ($validated as $key => $value) {
            \App\Models\Setting::setValue("referral.{$key}", $value);
        }

        return $this->success(null, 'Referral settings updated');
    }

    public function referralEarnings(Request $request)
    {
        $query = \App\Models\ReferralEarning::with(['user:id,name,email', 'referredUser:id,name,email']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $earnings = $query->latest()->paginate(30);

        $earnings->getCollection()->transform(function ($e) {
            return [
                'id' => $e->id,
                'user' => $e->user ? ['id' => $e->user->id, 'name' => $e->user->name, 'email' => $e->user->email] : null,
                'referred_user' => $e->referredUser ? ['id' => $e->referredUser->id, 'name' => $e->referredUser->name, 'email' => $e->referredUser->email] : null,
                'source_amount_naira' => $e->sourceAmountNaira(),
                'commission_naira' => $e->commissionNaira(),
                'percentage_rate' => $e->percentage_rate,
                'payout_number' => $e->payout_number,
                'status' => $e->status,
                'created_at' => $e->created_at->toISOString(),
            ];
        });

        return $this->paginated($earnings);
    }

    public function referralPayouts(Request $request)
    {
        $query = \App\Models\ReferralPayoutRequest::with('user:id,name,email');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payouts = $query->latest()->paginate(30);

        $payouts->getCollection()->transform(function ($p) {
            return [
                'id' => $p->id,
                'user' => $p->user ? ['id' => $p->user->id, 'name' => $p->user->name, 'email' => $p->user->email] : null,
                'amount_naira' => $p->amountNaira(),
                'bank_name' => $p->bank_name,
                'account_number' => $p->account_number,
                'account_name' => $p->account_name,
                'status' => $p->status,
                'admin_notes' => $p->admin_notes,
                'processed_by' => $p->processedBy ? $p->processedBy->name : null,
                'created_at' => $p->created_at->toISOString(),
                'processed_at' => $p->processed_at?->toISOString(),
            ];
        });

        return $this->paginated($payouts);
    }

    public function referralPayoutApprove(Request $request, int $id)
    {
        $payout = \App\Models\ReferralPayoutRequest::findOrFail($id);

        if ($payout->status !== 'pending') {
            return $this->error('This payout request has already been processed.', 400);
        }

        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        $payout->update([
            'status' => 'paid',
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
            'admin_notes' => $validated['admin_notes'] ?? $payout->admin_notes,
        ]);

        // Mark associated earnings as paid
        \App\Models\ReferralEarning::where('payout_request_id', $payout->id)
            ->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

        return $this->success(['payout' => $payout->fresh()], 'Payout approved and marked as paid');
    }

    public function referralPayoutReject(Request $request, int $id)
    {
        $payout = \App\Models\ReferralPayoutRequest::findOrFail($id);

        if ($payout->status !== 'pending') {
            return $this->error('This payout request has already been processed.', 400);
        }

        $validated = $request->validate([
            'admin_notes' => 'required|string|max:5000',
        ]);

        $payout->update([
            'status' => 'rejected',
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
            'admin_notes' => $validated['admin_notes'],
        ]);

        // Return earnings to pending status
        \App\Models\ReferralEarning::where('payout_request_id', $payout->id)
            ->update([
                'status' => 'pending',
                'payout_request_id' => null,
            ]);

        return $this->success(['payout' => $payout->fresh()], 'Payout rejected and earnings returned to pending');
    }

    public function referralStats()
    {
        $totalEarnings = (int) \App\Models\ReferralEarning::sum('commission_kobo') / 100;
        $pendingEarnings = (int) \App\Models\ReferralEarning::where('status', 'pending')->sum('commission_kobo') / 100;
        $paidEarnings = (int) \App\Models\ReferralEarning::where('status', 'paid')->sum('commission_kobo') / 100;
        $totalReferrers = \App\Models\ReferralEarning::distinct('user_id')->count('user_id');
        $pendingPayouts = \App\Models\ReferralPayoutRequest::where('status', 'pending')->count();
        $totalReferrals = User::whereNotNull('referred_by_user_id')->count();

        return $this->success([
            'total_earnings_naira' => $totalEarnings,
            'pending_earnings_naira' => $pendingEarnings,
            'paid_earnings_naira' => $paidEarnings,
            'total_referrers' => $totalReferrers,
            'pending_payouts' => $pendingPayouts,
            'total_referrals' => $totalReferrals,
        ]);
    }

    // ── Testimonials CRUD ──
    public function testimonials()
    {
        $testimonials = \App\Models\Testimonial::orderBy('sort_order')->latest()->get();
        return $this->success($testimonials);
    }

    public function testimonialStore(Request $request)
    {
        $validated = $request->validate([
            'author_name' => 'required|string|max:255',
            'author_role' => 'nullable|string|max:100',
            'author_organization' => 'nullable|string|max:255',
            'quote' => 'required|string|max:2000',
            'rating' => 'nullable|integer|min:1|max:5',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'category' => 'nullable|string|max:50',
        ]);
        $t = \App\Models\Testimonial::create($validated);
        return $this->success(['testimonial' => $t], 'Testimonial created', 201);
    }

    public function testimonialUpdate(Request $request, int $id)
    {
        $t = \App\Models\Testimonial::findOrFail($id);
        $validated = $request->validate([
            'author_name' => 'sometimes|string|max:255',
            'author_role' => 'nullable|string|max:100',
            'author_organization' => 'nullable|string|max:255',
            'quote' => 'sometimes|string|max:2000',
            'rating' => 'nullable|integer|min:1|max:5',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'category' => 'nullable|string|max:50',
        ]);
        $t->update($validated);
        return $this->success(['testimonial' => $t->fresh()]);
    }

    public function testimonialDestroy(int $id)
    {
        \App\Models\Testimonial::findOrFail($id)->delete();
        return $this->success(null, 'Testimonial deleted');
    }

    // ── Clinical Benchmarks ──
    public function benchmarks()
    {
        $benchmarks = \App\Models\BenchmarkRun::with([])->latest()->limit(20)->get();
        return $this->success($benchmarks);
    }

    // ── Partnership Inquiries ──

    public function partnershipInquiries()
    {
        $inquiries = \App\Models\PartnershipInquiry::latest()->paginate(25);
        return $this->paginated($inquiries);
    }

    public function partnershipInquiryUpdate(Request $request, int $id)
    {
        $inquiry = \App\Models\PartnershipInquiry::findOrFail($id);
        $validated = $request->validate([
            'status' => 'sometimes|in:new,contacted,converted,closed',
            'admin_notes' => 'nullable|string|max:5000',
        ]);
        $inquiry->update($validated);
        return $this->success(['inquiry' => $inquiry->fresh()]);
    }
}
