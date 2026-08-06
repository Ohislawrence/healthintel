<?php

namespace App\Services;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

class HealthReportCardService
{
    public function __construct(
        private TrendService $trendService,
    ) {}

    public function generate(User $user): string
    {
        $data = $this->gatherData($user);
        $html = view('reports.health-report-card', $data)->render();
        return Pdf::loadHTML($html)->setPaper('A4')->output();
    }

    private function gatherData(User $user): array
    {
        $profile = $user->healthProfile;
        $submissions = $user->labSubmissions()
            ->with(['testPanel:id,name', 'values', 'interpretation'])
            ->latest('submitted_at')
            ->limit(10)
            ->get();

        // Filter out submissions with no values for history display
        $nonEmptySubmissions = $submissions->filter(fn($s) => $s->values && $s->values->count() > 0);

        $healthScore = $this->calculateHealthScore($profile, $nonEmptySubmissions);

        $latestSubmission = $nonEmptySubmissions->first() ?? $submissions->first();
        $trends = $this->gatherTrends($user);
        $metrics = $this->gatherMetrics($profile);
        $medications = $this->parseMedications($profile);
        $conditions = $this->parseConditions($profile);

        // Strip markdown from interpretation text for clean PDF rendering
        if ($latestSubmission && $latestSubmission->interpretation?->interpretation_text) {
            $latestSubmission->interpretation->interpretation_text = $this->stripMarkdown(
                $latestSubmission->interpretation->interpretation_text
            );
        }

        return [
            'user' => $user,
            'profile' => $profile,
            'healthScore' => $healthScore,
            'latestSubmission' => $latestSubmission,
            'submissions' => $nonEmptySubmissions,
            'trends' => $trends,
            'metrics' => $metrics,
            'medications' => $medications,
            'conditions' => $conditions,
            'generatedAt' => now(),
            'appName' => 'HealthIntel',
        ];
    }

    private function stripMarkdown(string $text): string
    {
        // Remove markdown headers (##, ###, etc.)
        $text = preg_replace('/^#{1,6}\s+/m', '', $text);
        // Remove bold markers (**text** → text)
        $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);
        // Remove italic markers (*text* → text)
        $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/', '$1', $text);
        // Remove links [text](url) → text
        $text = preg_replace('/\[(.+?)\]\(.+?\)/', '$1', $text);
        // Convert list markers (- and *) to bullet
        $text = preg_replace('/^[\s]*[-*]\s+/m', '• ', $text);
        // Remove stray emoji-like Unicode sequences that don't render in PDF
        $text = str_replace(['𐄸', '𐂡', '𐃋'], ['- ', '- ', '- '], $text);
        // Collapse multiple blank lines to two
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }

    private function calculateHealthScore($profile, $submissions): array
    {
        $score = 50;
        $breakdown = [];

        if ($profile?->height_cm && $profile?->weight_kg) {
            $bmi = round($profile->weight_kg / (($profile->height_cm / 100) ** 2), 1);
            if ($bmi >= 18.5 && $bmi <= 24.9) { $score += 15; $breakdown['bmi'] = ['score' => 15, 'label' => 'Healthy BMI', 'value' => $bmi]; }
            elseif ($bmi >= 25 && $bmi <= 29.9) { $score += 8; $breakdown['bmi'] = ['score' => 8, 'label' => 'Overweight BMI', 'value' => $bmi]; }
            else { $score += 3; $breakdown['bmi'] = ['score' => 3, 'label' => 'BMI needs attention', 'value' => $bmi]; }
        }

        if ($profile?->profile_completed) { $score += 10; $breakdown['profile'] = ['score' => 10, 'label' => 'Profile complete']; }

        if ($submissions->isNotEmpty()) {
            $latestVals = $submissions->first()->values ?? collect();
            $normalCount = $latestVals->where('flag', 'normal')->count();
            $totalCount = $latestVals->count();
            if ($totalCount > 0) {
                $normalPct = ($normalCount / $totalCount) * 25;
                $score += (int) $normalPct;
                $breakdown['labs'] = ['score' => (int) $normalPct, 'label' => 'Lab results in range'];
            }
        }

        if (!$profile?->medical_conditions) { $score += 10; $breakdown['conditions'] = ['score' => 10, 'label' => 'No reported conditions']; }

        $score = min(100, max(0, $score));
        return ['total' => $score, 'grade' => $score >= 80 ? 'A' : ($score >= 60 ? 'B' : ($score >= 40 ? 'C' : 'D')), 'breakdown' => $breakdown];
    }

    private function gatherTrends(User $user): array
    {
        $commonTests = ['glucose', 'hba1c', 'cholesterol-total', 'creatinine', 'hemoglobin'];
        $trends = [];
        foreach ($commonTests as $testSlug) {
            try {
                $analysis = $this->trendService->analyzeTrend($user->id, $testSlug);
                if ($analysis && !($analysis['trend'] === 'insufficient_data' ?? false)) $trends[$testSlug] = $analysis;
            } catch (\Throwable) {}
        }
        return $trends;
    }

    private function gatherMetrics($profile): array
    {
        $metrics = [];
        if ($profile) {
            if ($profile->height_cm && $profile->weight_kg) $metrics['bmi'] = ['value' => round($profile->weight_kg / (($profile->height_cm / 100) ** 2), 1), 'unit' => 'kg/m²'];
            if ($profile->blood_pressure) $metrics['blood_pressure'] = ['value' => $profile->blood_pressure, 'unit' => 'mmHg'];
            if ($profile->date_of_birth) $metrics['age'] = ['value' => \Carbon\Carbon::parse($profile->date_of_birth)->age, 'unit' => 'years'];
            if ($profile->sex) $metrics['sex'] = ['value' => ucfirst($profile->sex), 'unit' => ''];
        }
        return $metrics;
    }

    private function parseMedications($profile): array
    {
        if (!$profile?->current_medications) return [];
        $meds = $profile->current_medications;
        if (is_string($meds)) { $decoded = json_decode($meds, true); if (is_array($decoded)) $meds = $decoded; }
        if (is_array($meds)) return is_array(reset($meds)) ? array_column($meds, 'medication') : $meds;
        return [];
    }

    private function parseConditions($profile): array
    {
        if (!$profile?->medical_conditions) return [];
        $conditions = $profile->medical_conditions;
        if (is_string($conditions)) { $decoded = json_decode($conditions, true); if (is_array($decoded)) $conditions = $decoded; }
        if (is_array($conditions)) return is_array(reset($conditions)) ? array_column($conditions, 'condition') : $conditions;
        return [];
    }
}