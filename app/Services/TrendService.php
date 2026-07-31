<?php

namespace App\Services;

use App\Models\LabSubmissionValue;

/**
 * Analyzes historical test trends and generates alerts and summaries.
 */
class TrendService
{
    /**
     * Get trend data with direction analysis and alert generation.
     */
    public function analyzeTrend(int $userId, string $testSlug): array
    {
        $values = LabSubmissionValue::where('test_slug', $testSlug)
            ->whereHas('submission', fn($q) => $q->where('user_id', $userId))
            ->with('submission:id,submitted_at')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($v) => [
                'value' => (float) $v->value,
                'flag' => $v->flag,
                'unit' => $v->unit,
                'date' => $v->submission->submitted_at?->toDateString() ?? $v->created_at->toDateString(),
            ]);

        if ($values->count() < 2) {
            return [
                'test_slug' => $testSlug,
                'test_name' => $values->first()['test_name'] ?? $testSlug,
                'total_points' => $values->count(),
                'values' => $values,
                'direction' => 'insufficient_data',
                'direction_label' => 'Insufficient Data',
                'alert' => null,
                'alert_level' => 'info',
                'message' => 'Not enough data points to analyze trends. At least 2 tests are needed.',
            ];
        }

        $numericValues = $values->pluck('value')->toArray();
        $direction = $this->detectDirection($numericValues);
        $alert = $this->generateAlert($numericValues, $direction, $values->first()['unit'] ?? '');
        $alertLevel = $this->alertLevel($direction, $numericValues);

        return [
            'test_slug' => $testSlug,
            'test_name' => $values->first()['test_name'] ?? $testSlug,
            'total_points' => $values->count(),
            'values' => $values,
            'direction' => $direction,
            'direction_label' => $this->directionLabel($direction),
            'alert' => $alert,
            'alert_level' => $alertLevel,
            'message' => $alert ?? 'No significant trend detected.',
        ];
    }

    /**
     * Detect trend direction from sequential values.
     */
    private function detectDirection(array $values): string
    {
        $count = count($values);
        if ($count < 2) return 'insufficient_data';

        // Simple trend detection: compare last 3 or last 2
        $recent = array_slice($values, -3);

        if (count($recent) >= 3) {
            // All 3 consecutively rising or falling
            $rising = true;
            $falling = true;
            for ($i = 1; $i < count($recent); $i++) {
                if ($recent[$i] <= $recent[$i - 1]) $rising = false;
                if ($recent[$i] >= $recent[$i - 1]) $falling = false;
            }

            if ($rising) return 'rising';
            if ($falling) return 'falling';
        }

        // Check last 2
        $lastTwo = array_slice($values, -2);
        if (count($lastTwo) >= 2) {
            $percentChange = $this->percentChange($lastTwo[0], $lastTwo[1]);
            if ($percentChange > 10) return 'rising';
            if ($percentChange < -10) return 'falling';
        }

        $first = $values[0];
        $last = $values[$count - 1];
        $overallChange = $this->percentChange($first, $last);

        if (abs($overallChange) < 5) return 'stable';
        return $overallChange > 0 ? 'rising_slight' : 'falling_slight';
    }

    /**
     * Generate a human-readable alert from the trend analysis.
     */
    private function generateAlert(array $values, string $direction, string $unit): ?string
    {
        $last = $values[count($values) - 1];
        $prev = $values[count($values) - 2] ?? $values[0];
        $change = round($prev > 0 ? (($last - $prev) / $prev) * 100 : 0, 1);

        return match ($direction) {
            'rising' => "📈 Trending Up: This result has been rising over your last " . count($values) . " tests. Latest change: +{$change}% {$unit}. Bring this trend report to your doctor.",
            'falling' => "📉 Trending Down: This result has been falling over your last " . count($values) . " tests. Latest change: {$change}% {$unit}. Discuss with your doctor.",
            'rising_slight' => "↗ Slightly Rising: Your values show an upward drift. Monitor and discuss at your next visit.",
            'falling_slight' => "↘ Slightly Falling: Your values show a downward drift. Monitor and discuss at your next visit.",
            'stable' => "✅ Stable: Your results have been consistent over time.",
            default => null,
        };
    }

    private function alertLevel(string $direction, array $values): string
    {
        return match ($direction) {
            'rising', 'falling' => 'flagged',
            'rising_slight', 'falling_slight' => 'info',
            'stable' => 'info',
            default => 'info',
        };
    }

    private function directionLabel(string $direction): string
    {
        return match ($direction) {
            'rising' => 'Rising',
            'falling' => 'Falling',
            'rising_slight' => 'Slightly Rising',
            'falling_slight' => 'Slightly Falling',
            'stable' => 'Stable',
            default => 'Insufficient Data',
        };
    }

    private function percentChange(float $from, float $to): float
    {
        if ($from == 0) return $to > 0 ? 100 : 0;
        return round((($to - $from) / $from) * 100, 1);
    }

    /**
     * Generate a one-page trend summary PDF for sharing with a doctor.
     */
    public function generateTrendSummaryPdf(int $userId, string $testSlug): string
    {
        $analysis = $this->analyzeTrend($userId, $testSlug);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.trend-summary', [
            'analysis' => $analysis,
            'generated_at' => now()->format('F j, Y'),
        ]);

        $pdf->setPaper('A4');

        return $pdf->output();
    }
}