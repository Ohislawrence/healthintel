<?php

namespace App\Services;

use App\Models\AiInterpretation;
use App\Models\Appointment;
use App\Models\HealthProfile;
use App\Models\LabSubmission;
use App\Models\Payment;
use App\Models\ProviderDirectoryEntry;
use App\Models\ReferralEvent;
use App\Models\User;
use App\Models\UserFeedback;
use App\Models\UserHealthMetric;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAnalyzerService
{
    /**
     * Build a compact, LLM-friendly snapshot of business metrics
     * that the DeepSeek model can reason over.
     */
    public function metrics(): array
    {
        $thirtyDaysAgo = now()->subDays(30);
        $sevenDaysAgo = now()->subDays(7);
        $fourteenDaysAgo = now()->subDays(14);

        // ── Users ──
        $totalUsers = User::count();
        $newUsers30d = User::where('created_at', '>=', $thirtyDaysAgo)->count();
        $newUsers7d = User::where('created_at', '>=', $sevenDaysAgo)->count();
        $activeUsers30d = LabSubmission::where('created_at', '>=', $thirtyDaysAgo)
            ->distinct('user_id')->count('user_id');
        $profileCompleted = HealthProfile::where('profile_completed', true)->count();

        // ── Revenue ──
        $revenue30d = (int) (Payment::where('status', 'success')
            ->where('created_at', '>=', $thirtyDaysAgo)->sum('amount_kobo') / 100);
        $revenue7d = (int) (Payment::where('status', 'success')
            ->where('created_at', '>=', $sevenDaysAgo)->sum('amount_kobo') / 100);
        $transactions30d = Payment::where('status', 'success')
            ->where('created_at', '>=', $thirtyDaysAgo)->count();
        $payingUsers30d = Payment::where('status', 'success')
            ->where('created_at', '>=', $thirtyDaysAgo)->distinct('user_id')->count('user_id');
        $arpu = $payingUsers30d > 0 ? round($revenue30d / $payingUsers30d) : 0;
        $conversionRate = $activeUsers30d > 0 ? round(($payingUsers30d / $activeUsers30d) * 100, 1) : 0;

        // ── Credit economy ──
        $creditsSold30d = Payment::where('status', 'success')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->with('purchasable')
            ->get()
            ->sum(fn($p) => $p->purchasable?->credits ?? 0);
        $creditsUsed30d = (int) LabSubmission::where('created_at', '>=', $thirtyDaysAgo)
            ->sum('credits_used');

        // ── Engagement ──
        $bmiEntries = UserHealthMetric::where('metric_type', 'bmi')->count();
        $bmrEntries = UserHealthMetric::where('metric_type', 'bmr')->count();
        $whrEntries = UserHealthMetric::where('metric_type', 'waist_hip_ratio')->count();
        $dueDateEntries = UserHealthMetric::where('metric_type', 'due_date')->count();
        $appointmentCount = Appointment::count();
        $symptomChecks30d = AiInterpretation::where('created_at', '>=', $thirtyDaysAgo)->count();

        // ── Referrals ──
        $totalReferrers = User::whereNotNull('referred_by_user_id')->count();
        $referralEvents30d = ReferralEvent::where('created_at', '>=', $thirtyDaysAgo)->count();

        // ── Content / usage ──
        $panelUsage = LabSubmission::whereNotNull('test_panel_id')
            ->with('testPanel:id,name')
            ->get()
            ->groupBy('test_panel_id')
            ->map(fn($subs) => [
                'panel_name' => $subs->first()->testPanel?->name ?? 'Unknown',
                'total' => $subs->count(),
            ])
            ->sortByDesc('total')->take(8)->values()->toArray();

        $submissionTypeSplit = LabSubmission::where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw("CASE WHEN submission_type = 'pdf' OR test_panel_id IS NULL THEN 'pdf' ELSE 'panel' END as type, count(*) as count")
            ->groupBy('type')->get()
            ->map(fn($r) => ['type' => $r->type, 'count' => (int) $r->count])
            ->toArray();

        $topSymptoms = DB::table('symptom_test_panels')
            ->join('symptoms', 'symptoms.id', '=', 'symptom_test_panels.symptom_id')
            ->join('lab_submissions', 'lab_submissions.test_panel_id', '=', 'symptom_test_panels.test_panel_id')
            ->where('lab_submissions.created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('symptoms.name, count(distinct lab_submissions.id) as count')
            ->groupBy('symptoms.id', 'symptoms.name')
            ->orderByDesc('count')
            ->take(8)
            ->get()
            ->map(fn($r) => ['name' => $r->name, 'count' => (int) $r->count])
            ->toArray();

        // ── Providers ──
        $providersByType = ProviderDirectoryEntry::where('is_active', true)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')->orderByDesc('count')->get()
            ->map(fn($r) => ['type' => $r->type, 'count' => (int) $r->count])
            ->toArray();
        $totalProviders = ProviderDirectoryEntry::where('is_active', true)->count();

        // ── Feedback ──
        $feedbackByStatus = UserFeedback::selectRaw("COALESCE(status, 'new') as status, count(*) as count")
            ->groupBy('status')->get()
            ->map(fn($r) => ['status' => $r->status, 'count' => (int) $r->count])
            ->toArray();

        // ── Week-over-week trend signals (7d vs prior 7d) ──
        $newUsersPrev7d = User::whereBetween('created_at', [$fourteenDaysAgo, $sevenDaysAgo])->count();
        $submissions7d = LabSubmission::where('created_at', '>=', $sevenDaysAgo)->count();
        $submissionsPrev7d = LabSubmission::whereBetween('created_at', [$fourteenDaysAgo, $sevenDaysAgo])->count();

        return [
            'platform' => 'LabDoc / HealthIntel',
            'currency' => 'NGN',
            'period_days' => 30,
            'users' => [
                'total' => $totalUsers,
                'new_7d' => $newUsers7d,
                'new_30d' => $newUsers30d,
                'new_prev_7d' => $newUsersPrev7d,
                'active_30d' => $activeUsers30d,
                'profile_completed' => $profileCompleted,
            ],
            'revenue' => [
                'total_30d' => $revenue30d,
                'last_7d' => $revenue7d,
                'transactions_30d' => $transactions30d,
                'arpu' => $arpu,
                'conversion_rate_pct' => $conversionRate,
            ],
            'credits' => [
                'sold_30d' => $creditsSold30d,
                'used_30d' => $creditsUsed30d,
                'net_30d' => $creditsSold30d - $creditsUsed30d,
            ],
            'engagement' => [
                'bmi_count' => $bmiEntries,
                'bmr_count' => $bmrEntries,
                'whr_count' => $whrEntries,
                'due_date_count' => $dueDateEntries,
                'appointments' => $appointmentCount,
                'ai_symptom_checks_30d' => $symptomChecks30d,
            ],
            'referrals' => [
                'total_referred_users' => $totalReferrers,
                'referral_events_30d' => $referralEvents30d,
            ],
            'submissions' => [
                'last_7d' => $submissions7d,
                'prev_7d' => $submissionsPrev7d,
            ],
            'content' => [
                'top_panels' => $panelUsage,
                'submission_type_split' => $submissionTypeSplit,
                'top_symptoms' => $topSymptoms,
            ],
            'providers' => [
                'total' => $totalProviders,
                'by_type' => $providersByType,
            ],
            'feedback' => $feedbackByStatus,
        ];
    }

    /**
     * Run the AI analysis and return structured recommendations.
     */
    public function analyze(): array
    {
        $metrics = $this->metrics();

        [$raw, $error] = $this->callDeepSeek($metrics);

        $analysis = $this->parseAnalysis($raw);

        return [
            'metrics' => $metrics,
            'analysis' => $analysis,
            'ai_available' => !empty($raw),
            'ai_error' => $error,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Call DeepSeek directly with a generous timeout and explicit error capture.
     *
     * The configured model (deepseek-v4-flash) is a reasoning model that first
     * generates a `reasoning_content` chain, making requests slower than the
     * plain `ask()` 30s timeout allows. We also fall back to `reasoning_content`
     * when `content` is empty.
     */
    private function callDeepSeek(array $metrics): array
    {
        $apiKey = config('services.deepseek.api_key') ?: env('DEEPSEEK_API_KEY');
        if (empty($apiKey)) {
            return [null, 'DeepSeek API key is not configured.'];
        }

        $model = config('services.deepseek.model') ?: env('DEEPSEEK_MODEL', 'deepseek-chat');
        $baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com');

        $userPrompt = "Here is the current platform analytics snapshot (JSON):\n\n"
            . json_encode($metrics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            . "\n\n"
            . "Analyze the data and return ONLY a valid JSON object following the schema in your instructions. "
            . "Do not include markdown code fences, commentary, or trailing commas.";

        $systemPrompt = $this->systemPrompt();

        // The configured model is a reasoning model: it "thinks" (reasoning_content)
        // before producing the final answer (content). If max_tokens is hit during
        // the thinking phase, `content` comes back empty. We retry with a larger
        // token budget to let the reasoning complete and produce the final JSON.
        $attempts = [4096, 8192];

        $lastError = null;

        foreach ($attempts as $maxTokens) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(180)->post($baseUrl . '/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'max_tokens' => $maxTokens,
                    'temperature' => 0.3,
                ]);

                if (!$response->successful()) {
                    $status = $response->status();
                    $body = $response->json();
                    Log::warning('AiAnalyzer DeepSeek HTTP error', [
                        'status' => $status,
                        'body' => $body,
                    ]);
                    $lastError = 'DeepSeek returned HTTP ' . $status . '. See Laravel logs for details.';
                    continue;
                }

                $body = $response->json();
                $choice = $body['choices'][0] ?? null;
                $finishReason = $choice['finish_reason'] ?? 'unknown';
                $content = $choice['message']['content'] ?? null;
                $reasoning = $choice['message']['reasoning_content'] ?? null;

                Log::info('AiAnalyzer DeepSeek response', [
                    'finish_reason' => $finishReason,
                    'content_len' => strlen((string) $content),
                    'reasoning_len' => strlen((string) $reasoning),
                    'max_tokens' => $maxTokens,
                ]);

                if (!empty($content)) {
                    return [trim((string) $content), null];
                }

                // If the model ran out of tokens while reasoning, `content` is empty.
                // Retry with a bigger budget rather than returning a dead-end.
                $lastError = 'DeepSeek returned an empty response (finish_reason: ' . $finishReason . ').';
            } catch (\Throwable $e) {
                Log::warning('AiAnalyzer DeepSeek exception: ' . $e->getMessage());
                $lastError = 'DeepSeek request failed (timeout or connection issue).';
            }
        }

        return [null, $lastError ?? 'DeepSeek returned an empty response.'];
    }

    /**
     * Parse the model response into a structured array, tolerating
     * markdown fences and malformed JSON by falling back gracefully.
     */
    private function parseAnalysis(?string $raw): array
    {
        if (empty($raw)) {
            return $this->fallbackAnalysis();
        }

        $json = trim($raw);
        // Strip markdown code fences if present.
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $json, $m)) {
            $json = $m[1];
        } else {
            // Attempt to extract the first balanced JSON object.
            $start = strpos($json, '{');
            $end = strrpos($json, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $json = substr($json, $start, $end - $start + 1);
            }
        }

        $decoded = json_decode($json, true);

        if (is_array($decoded)) {
            return $this->normalizeAnalysis($decoded);
        }

        return [
            'summary' => $raw,
            'marketing_emails' => [],
            'encourage_usage' => [],
            'grow_users' => [],
            'channels' => [],
            'quick_wins' => [],
        ];
    }

    private function normalizeAnalysis(array $data): array
    {
        return [
            'summary' => $data['summary'] ?? '',
            'marketing_emails' => $this->asArray($data['marketing_emails'] ?? []),
            'encourage_usage' => $this->asArray($data['encourage_usage'] ?? []),
            'grow_users' => $this->asArray($data['grow_users'] ?? []),
            'channels' => $this->asArray($data['channels'] ?? []),
            'quick_wins' => $this->asArray($data['quick_wins'] ?? []),
        ];
    }

    private function asArray(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn($v) => $v !== null && $v !== ''));
        }
        if (is_string($value) && $value !== '') {
            return [$value];
        }
        return [];
    }

    private function fallbackAnalysis(): array
    {
        return [
            'summary' => 'The AI analyzer could not be reached. Verify your DeepSeek API key and try again.',
            'marketing_emails' => [],
            'encourage_usage' => [],
            'grow_users' => [],
            'channels' => [],
            'quick_wins' => [],
        ];
    }

    private function systemPrompt(): string
    {
        return <<<'TXT'
You are a growth and marketing analyst for LabDoc (HealthIntel), a Nigerian health-tech platform that lets users upload lab reports, manually enter lab values, or run symptom checks, and receive plain-language AI interpretations. Users pay with credits.

Your job is to analyze the provided analytics snapshot and produce actionable, specific recommendations for the admin team.

STRICT OUTPUT FORMAT — Return ONLY a valid JSON object (no markdown, no commentary, no trailing commas) with EXACTLY these keys:

{
  "summary": "2-4 sentence executive summary of the current state and one biggest opportunity.",
  "marketing_emails": [
    {
      "subject": "Email subject line",
      "goal": "What this email should achieve",
      "target_segment": "Which users it should go to (e.g. inactive 30d, new signups, paying users, profile-incomplete users)",
      "body": "A ready-to-send 2-3 sentence email body using the platform tone."
    }
  ],
  "encourage_usage": [
    "Specific, prioritized actions to increase product usage and retention (e.g. nudges, streak mechanics, feature education)."
  ],
  "grow_users": [
    "Specific, prioritized ways to acquire more users (referrals, partnerships, content, provider networks)."
  ],
  "channels": [
    {
      "channel": "Channel name (email, WhatsApp, SMS, push, social media, in-app, providers/labs)",
      "strategy": "How to use this channel effectively for this platform.",
      "why": "Why this channel fits the Nigerian health-tech audience."
    }
  ],
  "quick_wins": [
    "The 3-5 highest-impact, lowest-effort actions the admin can take this week."
  ]
}

GUIDANCE:
- Be concrete and data-grounded. Reference actual numbers from the snapshot.
- Provide 3 to 5 marketing emails.
- Provide 3 to 5 encourage_usage items, 3 to 5 grow_users items, and 3 to 6 channels.
- Keep each item actionable and specific. No generic advice.
- Use plain English. Currency is NGN.
TXT;
    }
}