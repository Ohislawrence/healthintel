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
                'total_revenue' => round($totalRevenue, 2),
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

    public function providers()
    {
        $providers = ProviderDirectoryEntry::orderBy('name')->paginate(20);
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
            'banner_url' => 'nullable|url|max:500',
        ]);

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

        return $this->success(['provider' => $provider], 'Provider created', 201);
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
            'banner_url' => 'nullable|url|max:500',
        ]);

        $updateData = $request->only([
            'name', 'type', 'specialty', 'bio', 'phone', 'email',
            'address', 'city', 'state', 'website', 'partner_status',
            'referral_link', 'insurance_plans', 'is_verified', 'is_active',
            'latitude', 'longitude',
            'monetization_type', 'monetization_rate', 'monetization_amount',
            'monetization_limit_type', 'monetization_limit_value',
            'banner_url',
        ]);

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

        return $this->success(['provider' => $provider->fresh()], 'Provider updated');
    }

    public function providerToggleActive(string $slug)
    {
        $provider = ProviderDirectoryEntry::where('slug', $slug)->firstOrFail();
        $provider->update(['is_active' => !$provider->is_active]);
        return $this->success(['provider' => $provider->fresh()], 'Toggled');
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

    public function users()
    {
        $users = User::with('roles')->latest()->paginate(25);
        $users->getCollection()->transform(function ($user) {
            $user->credits = app(\App\Services\CreditService::class)->getBalance($user);
            return $user;
        });
        return $this->paginated($users);
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

        return $this->success([
            'user_id' => $user->id,
            'credits_added' => $validated['credits'],
            'new_balance' => $newBalance,
        ], 'Credits granted successfully');
    }

    public function userShow(int $id)
    {
        $user = User::with(['roles', 'healthProfile', 'labSubmissions' => fn($q) => $q->with('testPanel')->latest()->take(10)])
            ->findOrFail($id);

        $creditService = app(\App\Services\CreditService::class);
        $user->credits = $creditService->getBalance($user);

        return $this->success(['user' => $user]);
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
            'title' => 'required|string|max:200',
            'body' => 'required|string|max:1000',
            'target' => 'required|in:all,users,user_ids',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $notification = \App\Models\AdminNotification::create([
            'admin_id' => $request->user()->id,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'target' => $validated['target'],
            'user_ids' => $validated['user_ids'] ?? [],
            'read_by' => [],
            'sent_at' => now(),
        ]);

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
}