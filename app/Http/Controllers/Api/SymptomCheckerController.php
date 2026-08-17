<?php

namespace App\Http\Controllers\Api;

use App\Models\ProviderDirectoryEntry;
use App\Models\Symptom;
use App\Models\TestPanel;
use App\Services\DeepSeekService;
use App\Services\SymptomPromptBuilder;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SymptomCheckerController extends BaseController
{
    public function __construct(
        private DeepSeekService $deepSeek,
        private SymptomPromptBuilder $promptBuilder,
        private CreditService $creditService,
    ) {}

    /**
     * List all available symptoms grouped by category.
     */
    public function index()
    {
        $symptoms = Symptom::where('is_active', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');

        return $this->success(['symptoms' => $symptoms]);
    }

    /**
     * Given symptom slugs, return matching test panels with relevance scores.
     * Free — no credit deduction.
     */
    public function suggestPanels(Request $request)
    {
        $validated = $request->validate([
            'symptoms' => 'required|array|min:1',
            'symptoms.*' => 'required|string|exists:symptoms,slug',
        ]);

        $selected = Symptom::whereIn('slug', $validated['symptoms'])->get();

        $panelIds = DB::table('symptom_test_panels')
            ->whereIn('symptom_id', $selected->pluck('id'))
            ->select('test_panel_id', DB::raw('SUM(relevance_score) as total_relevance'))
            ->groupBy('test_panel_id')
            ->orderByDesc('total_relevance')
            ->pluck('test_panel_id');

        $panels = TestPanel::whereIn('id', $panelIds)
            ->where('is_active', true)
            ->get()
            ->sortBy(fn($p) => array_search($p->id, $panelIds->toArray()))
            ->values();

        return $this->success([
            'selected_symptoms' => $selected,
            'suggested_panels' => $panels,
        ]);
    }

    /**
     * Full symptom check with AI interpretation (1 credit).
     * Now includes test recommendations and nearby providers.
     */
    public function check(Request $request)
    {
        $validated = $request->validate([
            'symptoms' => 'nullable|array',
            'symptoms.*' => 'required|string|exists:symptoms,slug',
            'patient_context' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $symptoms = $validated['symptoms'] ?? [];
        $patientContext = isset($validated['patient_context']) ? trim($validated['patient_context']) : '';

        if (empty($symptoms) && $patientContext === '') {
            return $this->error('Please describe your symptoms or select at least one symptom.', 422);
        }

        $user = $request->user();
        $cost = config('credits.costs.symptom_check', 1);

        if (!$this->creditService->hasCredits($user, $cost)) {
            return $this->error('Insufficient credits. Please top up.', 402);
        }

        $selected = !empty($symptoms)
            ? Symptom::whereIn('slug', $symptoms)->get()
            : collect();

        // Get matching test panels with health-profile boosting
        if ($selected->isNotEmpty()) {
            $panelIds = DB::table('symptom_test_panels')
                ->whereIn('symptom_id', $selected->pluck('id'))
                ->select('test_panel_id', DB::raw('SUM(relevance_score) as total_relevance'))
                ->groupBy('test_panel_id')
                ->orderByDesc('total_relevance')
                ->pluck('test_panel_id');

            $panels = TestPanel::whereIn('id', $panelIds)
                ->where('is_active', true)
                ->get()
                ->map(function($p) use ($panelIds) {
                    $p->base_rank = array_search($p->id, $panelIds->toArray());
                    return $p;
                })
                ->sortBy(fn($p) => array_search($p->id, $panelIds->toArray()))
                ->values();
        } else {
            // Text-only submission: expose the full active catalog so the AI
            // can still recommend relevant panels by name.
            $panels = TestPanel::where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function($p) {
                    $p->base_rank = null;
                    return $p;
                })
                ->values();
        }

        // ── Personalized ranking boost by health profile ──
        $profile = $user->healthProfile;
        if ($profile && $profile->medical_conditions) {
            $conditions = is_array($profile->medical_conditions)
                ? $profile->medical_conditions
                : json_decode($profile->medical_conditions, true);

            if (is_array($conditions)) {
                $boostMap = [
                    'diabetes' => ['diabetes', 'glucose', 'hba1c', 'kidney', 'lipid', 'thyroid'],
                    'hypertension' => ['heart health', 'kidney', 'electrolytes', 'lipid'],
                    'thyroid' => ['tft', 'thyroid'],
                    'asthma' => ['respiratory'],
                    'malaria' => ['malaria', 'fbc'],
                    'typhoid' => ['typhoid', 'fbc'],
                    'pregnancy' => ['antenatal', 'fbc', 'glucose'],
                ];

                $boostedSlugs = [];
                foreach ($conditions as $condition) {
                    $c = strtolower(is_array($condition) ? ($condition['condition'] ?? '') : $condition);
                    foreach ($boostMap as $key => $slugs) {
                        if (str_contains($c, $key)) {
                            $boostedSlugs = array_merge($boostedSlugs, $slugs);
                        }
                    }
                }

                // Apply +20% to relevance for matched panels
                if (!empty($boostedSlugs)) {
                    $panels = $panels->map(function($p) use ($boostedSlugs) {
                        $matches = false;
                        foreach ($boostedSlugs as $slug) {
                            if (stripos($p->slug, $slug) !== false || stripos($p->name, $slug) !== false) {
                                $matches = true;
                                break;
                            }
                        }
                        if ($matches) {
                            $p->health_profile_boost = 20;
                        }
                        return $p;
                    });
                }
            }
        }

        // Build context from health profile
        $context = $patientContext !== '' ? $patientContext : $this->buildContextFromProfile($user);

        // Build prompt with panel names for AI to reference
        $panelNames = $panels->pluck('name')->implode(', ');
        $symptomNames = $selected->pluck('name')->implode(', ');

        if ($symptomNames !== '') {
            $prompt = "Patient symptoms: {$symptomNames}\n";
            $prompt .= "Patient context: {$context}\n";
        } else {
            $prompt = "Patient's description of their symptoms: {$context}\n";
        }
        $prompt .= "Available relevant test panels: {$panelNames}\n\n";
        $prompt .= "Based on the symptoms and patient context above, provide:\n";
        $prompt .= "1. A brief, plain-language explanation of what these symptoms MIGHT indicate (never diagnose)\n";
        $prompt .= "2. Which of the available test panels would be most appropriate and WHY\n";
        $prompt .= "3. General guidance on what the patient should do next\n";
        $prompt .= "4. If symptoms suggest an emergency, state it clearly\n\n";
        $prompt .= "Format your response with these sections:\n";
        $prompt .= "## What This Might Mean\n";
        $prompt .= "## Recommended Tests\n";
        $prompt .= "## Next Steps\n";

        // Call AI
        $interpretation = null;
        $aiStatus = 'unavailable';

        try {
            $apiKey = config('services.deepseek.api_key');
            if (!empty($apiKey)) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(30)->post(
                    config('services.deepseek.base_url', 'https://api.deepseek.com') . '/v1/chat/completions',
                    [
                        'model' => config('services.deepseek.model', 'deepseek-chat'),
                        'messages' => [
                            ['role' => 'system', 'content' => $this->systemPrompt()],
                            ['role' => 'user', 'content' => $prompt],
                        ],
                        'max_tokens' => (int) config('services.deepseek.max_tokens', 2048),
                        'temperature' => (float) config('services.deepseek.temperature', 0.3),
                    ],
                );

                if ($response->successful()) {
                    $body = $response->json();
                    $interpretation = $body['choices'][0]['message']['content'] ?? null;
                    $aiStatus = $interpretation ? 'completed' : 'failed';
                } else {
                    $aiStatus = 'failed';
                }
            }
        } catch (\Throwable $e) {
            $aiStatus = 'failed';
        }

        // Only deduct on successful AI response
        if ($interpretation) {
            $this->creditService->debit($user, $cost, 'symptom_check');
        }

        // Find nearby providers (labs and hospitals) for the suggested tests
        $nearbyProviders = [];
        $userLat = $validated['latitude'] ?? null;
        $userLng = $validated['longitude'] ?? null;

        if ($userLat && $userLng) {
            $nearbyProviders = ProviderDirectoryEntry::whereIn('type', ['lab', 'hospital', 'clinic'])
                ->where('is_active', true)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get()
                ->map(function ($p) use ($userLat, $userLng) {
                    $p->distance_km = round($this->haversine($userLat, $userLng, $p->latitude, $p->longitude), 1);
                    return $p;
                })
                ->filter(fn($p) => $p->distance_km <= 50)
                ->sortBy('distance_km')
                ->take(5)
                ->values();
        }

        // ── Track funnel ──
        \App\Models\SymptomCheckFunnel::create([
            'user_id' => $user->id,
            'symptoms_selected' => $selected->pluck('slug')->toArray(),
            'panels_suggested' => $panels->map(fn($p) => [
                'slug' => $p->slug, 'name' => $p->name,
            ])->toArray(),
            'stage' => 'checked',
        ]);

        // Return error if AI completely failed
        if (!$interpretation) {
            return $this->success([
                'selected_symptoms' => $selected,
                'suggested_panels' => $panels,
                'interpretation' => 'The AI interpretation service is temporarily unavailable. Please review the suggested test panels below and consult a healthcare provider.',
                'ai_status' => 'unavailable',
                'nearby_providers' => $nearbyProviders,
            ], 'Symptom check completed — AI interpretation unavailable (no credits deducted)');
        }

        return $this->success([
            'selected_symptoms' => $selected,
            'suggested_panels' => $panels,
            'interpretation' => $interpretation,
            'ai_status' => $aiStatus,
            'nearby_providers' => $nearbyProviders,
        ]);
    }

    private function buildContextFromProfile($user): string
    {
        $profile = $user->healthProfile;
        if (!$profile) {
            return 'No health profile data available.';
        }

        $parts = [];
        if ($profile->date_of_birth) {
            $parts[] = 'Age: ' . \Carbon\Carbon::parse($profile->date_of_birth)->age;
        }
        if ($profile->sex) {
            $parts[] = 'Sex: ' . $profile->sex;
        }
        if ($profile->is_pregnant) {
            $parts[] = 'Pregnant: yes';
        }
        return $parts ? implode(', ', $parts) : 'No health profile data available.';
    }

    private function systemPrompt(): string
    {
        return <<<'TXT'
You are LabDoc's clinical triage assistant for the Symptom Checker feature.

IMPORTANT GUARDRAILS:
1. NEVER claim to diagnose a disease or condition. Use phrases like "this may indicate" or "this is consistent with."
2. NEVER recommend specific medications or dosages.
3. ALWAYS include: "This is NOT medical advice. Please consult a licensed healthcare professional."
4. If any symptom suggests a possible emergency (chest pain, difficulty breathing, severe headache with stiff neck, sudden confusion, severe bleeding, etc.), explicitly state: "Your symptoms may require urgent medical attention. Please visit the nearest emergency room or call emergency services."
5. Use plain, accessible language (Flesch-Kincaid grade 8-10).
6. Reference the available test panels by name when making recommendations.
7. Explain WHY a particular test panel is relevant to the reported symptoms.
TXT;
    }

    /**
     * Track funnel progression: user clicked on a panel, viewed a provider, or booked.
     */
    public function trackFunnel(Request $request)
    {
        $validated = $request->validate([
            'funnel_id' => 'required|integer|exists:symptom_check_funnels,id',
            'stage' => 'required|in:panel_viewed,provider_viewed,booked',
            'provider_id' => 'nullable|integer|exists:provider_directory_entries,id|required_if:stage,provider_viewed,booked',
        ]);

        $funnel = \App\Models\SymptomCheckFunnel::where('user_id', $request->user()->id)
            ->findOrFail($validated['funnel_id']);

        $update = ['stage' => $validated['stage']];

        if ($validated['stage'] === 'provider_viewed' || $validated['stage'] === 'booked') {
            $update['provider_viewed_id'] = $validated['provider_id'];
        }
        if ($validated['stage'] === 'booked') {
            $update['appointment_booked'] = true;
        }

        $funnel->update($update);

        return $this->success([
            'funnel_id' => $funnel->id,
            'stage' => $funnel->stage,
        ], 'Funnel step tracked');
    }

    /**
     * Get funnel conversion analytics.
     */
    public function funnelAnalytics(Request $request)
    {
        $days = min((int) $request->get('days', 30), 365);
        $since = now()->subDays($days);

        $funnels = \App\Models\SymptomCheckFunnel::where('created_at', '>=', $since)->get();

        $total = $funnels->count();
        $panelViewed = $funnels->whereIn('stage', ['panel_viewed', 'provider_viewed', 'booked'])->count();
        $providerViewed = $funnels->whereIn('stage', ['provider_viewed', 'booked'])->count();
        $booked = $funnels->where('stage', 'booked')->count();

        return $this->success([
            'funnel_analytics' => [
                'period_days' => $days,
                'total_checks' => $total,
                'panel_viewed' => $panelViewed,
                'panel_view_rate' => $total > 0 ? round(($panelViewed / $total) * 100, 1) . '%' : '0%',
                'provider_viewed' => $providerViewed,
                'provider_view_rate' => $total > 0 ? round(($providerViewed / $total) * 100, 1) . '%' : '0%',
                'booked' => $booked,
                'booking_rate' => $total > 0 ? round(($booked / $total) * 100, 1) . '%' : '0%',
            ],
        ]);
    }

    /**
     * Calculate distance between two coordinates using Haversine formula.
     */
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}