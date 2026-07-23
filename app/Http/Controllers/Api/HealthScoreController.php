<?php

namespace App\Http\Controllers\Api;

use App\Models\LabSubmission;
use App\Models\LabSubmissionValue;
use App\Models\UserHealthMetric;
use App\Models\UserTrackerSnapshot;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HealthScoreController extends BaseController
{
    // BMI category thresholds — aligned with mobile design system
    private const BMI_CATEGORIES = [
        ['min' => 0, 'max' => 18.4, 'label' => 'Underweight', 'color' => '#3B82F6'],
        ['min' => 18.5, 'max' => 24.9, 'label' => 'Normal weight', 'color' => '#22C55E'],
        ['min' => 25.0, 'max' => 29.9, 'label' => 'Overweight', 'color' => '#F59E0B'],
        ['min' => 30.0, 'max' => 34.9, 'label' => 'Obese (Class I)', 'color' => '#F87171'],
        ['min' => 35.0, 'max' => 39.9, 'label' => 'Obese (Class II)', 'color' => '#EF4444'],
        ['min' => 40.0, 'max' => 999, 'label' => 'Obese (Class III)', 'color' => '#DC2626'],
    ];

    /**
     * Composite health score dashboard.
     */
    public function score(Request $request)
    {
        $user = $request->user();

        // ── Lab Data (both panel-based AND PDF submissions) ──
        $allSubmissions = $user->labSubmissions()
            ->whereNotNull('submitted_at')
            ->with(['values', 'interpretation', 'testPanel:id,name,slug'])
            ->latest('submitted_at')
            ->get();

        // Separate panel-based (has values) from PDF-only
        $panelSubmissions = $allSubmissions->whereNotNull('test_panel_id');
        $pdfSubmissions = $allSubmissions->where('submission_type', 'pdf');

        $allFlagged = LabSubmissionValue::whereHas(
            'submission',
            fn ($q) => $q->where('user_id', $user->id)->whereNotNull('test_panel_id')
        )
            ->with('submission:id,user_id,test_panel_id,submitted_at')
            ->get();

        $totalTests = $allFlagged->count();
        $normalCount = $allFlagged->where('flag', 'normal')->count();
        $abnormalCount = $allFlagged->where('flag', '!=', 'normal')->count();
        $criticalCount = $allFlagged->filter(fn ($v) => str_starts_with($v->flag, 'critical'))->count();

        // Per-test trend: latest flag for each test slug
        $latestBySlug = $allFlagged
            ->groupBy('test_slug')
            ->map(fn ($group) => $group->sortByDesc('submission.submitted_at')->first());

        $latestFlags = $latestBySlug
            ->groupBy('flag')
            ->map(fn ($g) => $g->count())
            ->toArray();

        // Most recent abnormal results
        $recentAbnormal = $latestBySlug
            ->where('flag', '!=', 'normal')
            ->sortByDesc('submission.submitted_at')
            ->take(5)
            ->map(fn ($v) => [
                'test_name' => $v->test_name,
                'test_slug' => $v->test_slug,
                'flag' => $v->flag,
                'value' => $v->value,
                'unit' => $v->unit,
                'date' => optional($v->submission)->submitted_at?->toDateString(),
            ])
            ->values();

        // PDF submission count (for "data exists" check)
        $pdfCount = $pdfSubmissions->count();
        $hasAnyData = $totalTests > 0 || $pdfCount > 0;

        // ══════════════════════════════════════════════════════
        // ── Tracker & Calculator Metrics ──────────────────────
        // ══════════════════════════════════════════════════════
        $latestBmi = UserHealthMetric::where('user_id', $user->id)
            ->where('metric_type', 'bmi')
            ->latest('recorded_at')
            ->first();

        $latestBmr = UserHealthMetric::where('user_id', $user->id)
            ->where('metric_type', 'bmr')
            ->latest('recorded_at')
            ->first();

        $latestWhr = UserHealthMetric::where('user_id', $user->id)
            ->where('metric_type', 'waist_hip_ratio')
            ->latest('recorded_at')
            ->first();

        $trackerMetrics = [];
        $trackerFlags = [];

        if ($latestBmi && isset($latestBmi->data['bmi'])) {
            $bmiVal = (float) $latestBmi->data['bmi'];
            $bmiCat = $this->getBmiCategory($bmiVal);
            $trackerMetrics['bmi'] = array_merge(
                $latestBmi->data,
                [
                    'category' => $bmiCat['label'],
                    'category_color' => $bmiCat['color'],
                    'recorded_at' => $latestBmi->recorded_at->toDateString(),
                ]
            );
            if ($bmiCat['label'] !== 'Normal weight' && $bmiCat['label'] !== 'Underweight') {
                $trackerFlags[] = [
                    'source' => 'bmi',
                    'label' => 'BMI',
                    'value' => $bmiVal,
                    'category' => $bmiCat['label'],
                    'severity' => str_contains($bmiCat['label'], 'Obese') ? 'high' : 'medium',
                    'color' => $bmiCat['color'],
                ];
            }
        }

        if ($latestWhr && isset($latestWhr->data['ratio'])) {
            $whrVal = (float) $latestWhr->data['ratio'];
            $whrRisk = $this->getWhrRisk($whrVal, $user->healthProfile?->sex);
            $trackerMetrics['waist_hip_ratio'] = array_merge(
                $latestWhr->data,
                [
                    'risk' => $whrRisk,
                    'recorded_at' => $latestWhr->recorded_at->toDateString(),
                    'category_color' => $whrRisk === 'High' ? '#EF4444' : ($whrRisk === 'Moderate' ? '#F59E0B' : '#22C55E'),
                ]
            );
            if ($whrRisk !== 'Low') {
                $trackerFlags[] = [
                    'source' => 'waist_hip_ratio',
                    'label' => 'Waist-to-Hip',
                    'value' => $whrVal,
                    'category' => $whrRisk . ' Risk',
                    'severity' => $whrRisk === 'High' ? 'high' : 'medium',
                    'color' => $whrRisk === 'High' ? '#EF4444' : '#F59E0B',
                ];
            }
        }

        if ($latestBmr && isset($latestBmr->data['bmr'])) {
            $trackerMetrics['bmr'] = array_merge(
                $latestBmr->data,
                [
                    'recorded_at' => $latestBmr->recorded_at->toDateString(),
                ]
            );
        }

        // Number of trackers the user has used
        $trackerCount = collect(['bmi', 'bmr', 'waist_hip_ratio'])
            ->filter(fn ($type) => !empty($trackerMetrics[$type]))
            ->count();

        // ══════════════════════════════════════════════════════
        // ── Composite Score (0-100) including trackers ────────
        // ══════════════════════════════════════════════════════
        $score = $this->computeScore(
            $totalTests,
            $normalCount,
            $criticalCount,
            $latestFlags,
            $hasAnyData,
            $trackerFlags,
            $trackerCount
        );

        $category = $this->scoreCategory($score);

        // ── Lab recency ────────────────────────────────────────
        $lastSubmission = $allSubmissions->first();
        $daysSinceLastLab = $lastSubmission
            ? (int) Carbon::parse($lastSubmission->submitted_at)->diffInDays(now())
            : null;

        // Per-panel last-submitted dates
        $panelHistory = $allSubmissions
            ->whereNotNull('test_panel_id')
            ->groupBy('test_panel_id')
            ->map(fn ($subs) => [
                'panel_name' => $subs->first()->testPanel?->name ?? 'Unknown',
                'panel_slug' => $subs->first()->testPanel?->slug ?? '',
                'last_tested' => $subs->max('submitted_at')?->toDateString(),
                'days_ago' => (int) Carbon::parse($subs->max('submitted_at'))->diffInDays(now()),
                'total_tests' => $subs->count(),
            ])
            ->sortBy('days_ago')
            ->values();

        return $this->success([
            'score' => $score,
            'category' => $category,
            'summary' => [
                'total_tests' => $totalTests,
                'normal' => $normalCount,
                'abnormal' => $abnormalCount,
                'critical' => $criticalCount,
                'latest_flags' => $latestFlags,
                'recent_abnormal' => $recentAbnormal,
                'days_since_last_lab' => $daysSinceLastLab,
                'panel_history' => $panelHistory,
                'pdf_count' => $pdfCount,
                'has_any_data' => $hasAnyData,
                'tracker_metrics' => $trackerMetrics,
                'tracker_flags' => $trackerFlags,
                'tracker_count' => $trackerCount,
            ],
        ]);
    }

    public function saveMetric(Request $request)
    {
        $validated = $request->validate([
            'metric_type' => 'required|string|in:bmi,bmr,tdee,waist_hip_ratio,due_date',
            'data' => 'required|array',
        ]);

        $metric = UserHealthMetric::create([
            'user_id' => $request->user()->id,
            'metric_type' => $validated['metric_type'],
            'data' => $validated['data'],
            'notes' => $request->input('notes'),
            'recorded_at' => $request->input('recorded_at', now()),
        ]);

        return $this->success(['metric' => $metric], 'Metric saved', 201);
    }

    public function getMetrics(Request $request)
    {
        $query = UserHealthMetric::where('user_id', $request->user()->id);

        if ($request->filled('type')) {
            $query->where('metric_type', $request->input('type'));
        }

        $metrics = $query->latest('recorded_at')
            ->limit($request->input('limit', 20))
            ->get();

        return $this->success([
            'metrics' => $metrics,
            'total' => $metrics->count(),
        ]);
    }

    /**
     * Sync local tracker data (BP, water, food diary) to the server.
     */
    public function syncTrackers(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'data' => 'required|array',
        ]);

        $snapshot = UserTrackerSnapshot::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'date' => $validated['date'],
            ],
            ['data' => $validated['data']],
        );

        return $this->success(['snapshot' => $snapshot], 'Tracker data synced');
    }

    /**
     * Get today's tracker data for the dashboard summary.
     */
    public function todayTrackers(Request $request)
    {
        $today = now()->toDateString();

        $snapshot = UserTrackerSnapshot::where('user_id', $request->user()->id)
            ->where('date', $today)
            ->first();

        return $this->success([
            'date' => $today,
            'trackers' => $snapshot?->data ?? [],
        ]);
    }

    public function reminders(Request $request)
    {
        $user = $request->user();

        $reminders = [];

        $panelSubmissions = $user->labSubmissions()
            ->whereNotNull('test_panel_id')
            ->with('testPanel:id,name,slug')
            ->get()
            ->groupBy('test_panel_id');

        foreach ($panelSubmissions as $panelId => $subs) {
            $panel = $subs->first()->testPanel;
            if (! $panel) continue;

            $lastDate = Carbon::parse($subs->max('submitted_at'));
            $daysAgo = (int) $lastDate->diffInDays(now());

            $intervals = $this->suggestedIntervals($panel->slug);

            foreach ($intervals as $intervalMonths => $label) {
                $dueDate = $lastDate->copy()->addMonths($intervalMonths);
                if ($dueDate->isPast() || $dueDate->diffInDays(now()) <= 30) {
                    $overdue = $dueDate->isPast();
                    $reminders[] = [
                        'panel_name' => $panel->name,
                        'panel_slug' => $panel->slug,
                        'last_tested' => $lastDate->toDateString(),
                        'days_ago' => $daysAgo,
                        'suggested_interval' => $label,
                        'interval_months' => $intervalMonths,
                        'overdue' => $overdue,
                        'message' => $overdue
                            ? "You last checked \"{$panel->name}\" {$daysAgo} days ago. It's time for a re-check!"
                            : "Your \"{$panel->name}\" re-check is due soon (last tested {$daysAgo} days ago).",
                        'priority' => $overdue ? 'high' : 'medium',
                    ];
                }
            }
        }

        if ($panelSubmissions->isEmpty()) {
            $reminders[] = [
                'panel_name' => null,
                'panel_slug' => null,
                'last_tested' => null,
                'days_ago' => null,
                'suggested_interval' => 'General Check-up',
                'interval_months' => null,
                'overdue' => true,
                'message' => 'You haven\'t submitted any lab results yet. Start with a general wellness panel to establish your baseline.',
                'priority' => 'high',
            ];
        }

        usort($reminders, fn ($a, $b) =>
            ($b['priority'] === 'high' ? 2 : 1) <=> ($a['priority'] === 'high' ? 2 : 1)
            ?: ($b['overdue'] ?? false) <=> ($a['overdue'] ?? false)
        );

        return $this->success([
            'reminders' => array_values($reminders),
            'total' => count($reminders),
        ]);
    }

    private function computeScore(
        int $total,
        int $normal,
        int $critical,
        array $flags,
        bool $hasAnyData,
        array $trackerFlags,
        int $trackerCount
    ): int {
        if (! $hasAnyData) {
            return 50;
        }

        if ($total > 0) {
            $normalityPct = ($normal / $total) * 100;
            $criticalPenalty = min($critical * 10, 30);
            $score = round($normalityPct - $criticalPenalty);
        } else {
            $score = 70;
        }

        $highTrackerFlags = count(array_filter($trackerFlags, fn ($f) => $f['severity'] === 'high'));
        $mediumTrackerFlags = count(array_filter($trackerFlags, fn ($f) => $f['severity'] === 'medium'));

        $trackerPenalty = ($highTrackerFlags * 5) + ($mediumTrackerFlags * 2);
        $trackerBonus = min($trackerCount * 3, 10);

        $score = $score - $trackerPenalty + $trackerBonus;

        return max(0, min(100, (int) round($score)));
    }

    private function scoreCategory(int $score): array
    {
        return match (true) {
            $score >= 85 => ['label' => 'Excellent', 'color' => '#22C55E', 'emoji' => '★'],
            $score >= 70 => ['label' => 'Good', 'color' => '#0F766E', 'emoji' => '●'],
            $score >= 50 => ['label' => 'Fair', 'color' => '#F59E0B', 'emoji' => '▲'],
            $score >= 30 => ['label' => 'Needs Attention', 'color' => '#F97316', 'emoji' => '■'],
            default => ['label' => 'Action Needed', 'color' => '#EF4444', 'emoji' => '●'],
        };
    }

    private function getBmiCategory(float $bmi): array
    {
        foreach (self::BMI_CATEGORIES as $cat) {
            if ($bmi >= $cat['min'] && $bmi <= $cat['max']) {
                return $cat;
            }
        }
        return self::BMI_CATEGORIES[0];
    }

    private function getWhrRisk(float $ratio, ?string $sex): string
    {
        if ($sex === 'male') {
            if ($ratio >= 0.95) return 'High';
            if ($ratio >= 0.86) return 'Moderate';
            return 'Low';
        }
        if ($ratio >= 0.86) return 'High';
        if ($ratio >= 0.81) return 'Moderate';
        return 'Low';
    }

    private function suggestedIntervals(string $slug): array
    {
        return match (true) {
            str_contains($slug, 'lipid') || str_contains($slug, 'cholesterol') => [
                6 => '6-month lipid re-check',
                12 => 'Annual lipid panel',
            ],
            str_contains($slug, 'glucose') || str_contains($slug, 'diabetes') || str_contains($slug, 'hba1c') => [
                3 => '3-month glucose re-check',
                6 => '6-month HbA1c re-check',
            ],
            str_contains($slug, 'liver') || str_contains($slug, 'hepatic') => [
                6 => '6-month liver panel',
                12 => 'Annual liver check',
            ],
            str_contains($slug, 'kidney') || str_contains($slug, 'renal') => [
                6 => '6-month kidney panel',
                12 => 'Annual kidney check',
            ],
            str_contains($slug, 'thyroid') || str_contains($slug, 'tsh') => [
                6 => '6-month thyroid check',
                12 => 'Annual thyroid panel',
            ],
            str_contains($slug, 'cbc') || str_contains($slug, 'complete-blood') || str_contains($slug, 'full-blood') => [
                6 => '6-month CBC re-check',
                12 => 'Annual CBC panel',
            ],
            str_contains($slug, 'wellness') || str_contains($slug, 'general') || str_contains($slug, 'basic') => [
                6 => '6-month wellness check',
                12 => 'Annual wellness panel',
            ],
            default => [
                6 => '6-month re-check',
                12 => 'Annual re-check',
            ],
        };
    }
}