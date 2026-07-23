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
            'symptoms' => 'required|array|min:1',
            'symptoms.*' => 'required|string|exists:symptoms,slug',
            'patient_context' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $user = $request->user();
        $cost = config('credits.costs.symptom_check', 1);

        if (!$this->creditService->hasCredits($user, $cost)) {
            return $this->error('Insufficient credits. Please top up.', 402);
        }

        $selected = Symptom::whereIn('slug', $validated['symptoms'])->get();

        // Get matching test panels
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

        // Build context from health profile
        $context = $validated['patient_context'] ?? $this->buildContextFromProfile($user);

        // Build prompt with panel names for AI to reference
        $panelNames = $panels->pluck('name')->implode(', ');
        $symptomNames = $selected->pluck('name')->implode(', ');

        $prompt = "Patient symptoms: {$symptomNames}\n";
        $prompt .= "Patient context: {$context}\n";
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