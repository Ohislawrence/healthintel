<?php

namespace App\Services;

use App\Models\LabPartnership;
use App\Models\PartnerInterpretation;

/**
 * Calculates ROI metrics for partner dashboard and sales proposals.
 */
class RoiMetricsService
{
    /**
     * Calculate comprehensive ROI metrics for a partnership.
     */
    public function calculate(LabPartnership $partnership, int $days = 90): array
    {
        $since = now()->subDays($days);

        $interpretations = PartnerInterpretation::where('partnership_id', $partnership->id)
            ->where('status', 'completed')
            ->where('created_at', '>=', $since)
            ->get();

        $totalTests = $interpretations->count();
        $totalPatients = $interpretations->unique('patient_identifier')->count();

        if ($totalTests === 0) {
            return $this->emptyMetrics($days);
        }

        // Abnormal detection rate
        $abnormalCount = 0;
        $criticalCount = 0;
        $normalCount = 0;
        foreach ($interpretations as $i) {
            if ($i->classification_status) {
                if (in_array($i->classification_status, ['critical_low', 'critical_high'])) {
                    $criticalCount++;
                } elseif (in_array($i->classification_status, ['abnormal_low', 'abnormal_high'])) {
                    $abnormalCount++;
                } else {
                    $normalCount++;
                }
            } elseif ($i->reference_range_low && $i->reference_range_high) {
                $v = (float) $i->value;
                $l = (float) $i->reference_range_low;
                $h = (float) $i->reference_range_high;
                if ($v < $l || $v > $h) $abnormalCount++;
                else $normalCount++;
            }
        }

        $abnormalRate = $totalTests > 0 ? round(($abnormalCount / $totalTests) * 100, 1) : 0;
        $criticalRate = $totalTests > 0 ? round(($criticalCount / $totalTests) * 100, 1) : 0;

        // Estimated patient calls prevented (conservative: 70% of abnormal results would have triggered a call)
        $callsPrevented = (int) round($abnormalCount * 0.7);
        $callReductionRate = round(($callsPrevented / max($totalTests, 1)) * 100, 1);

        // Re-test rate (patients with more than one interpretation)
        $retestPatients = PartnerInterpretation::where('partnership_id', $partnership->id)
            ->where('status', 'completed')
            ->whereNotNull('patient_identifier')
            ->selectRaw('patient_identifier, count(*) as count')
            ->groupBy('patient_identifier')
            ->having('count', '>', 1)
            ->count();

        $retestRate = $totalPatients > 0 ? round(($retestPatients / $totalPatients) * 100, 1) : 0;

        // Average turnaround time (minutes from creation to completion)
        $avgTurnaround = $interpretations
            ->filter(fn($i) => $i->created_at && $i->updated_at)
            ->avg(fn($i) => $i->created_at->diffInMinutes($i->updated_at));

        // Urgent findings (escalation_level = 'urgent')
        $urgentCount = $interpretations->where('escalation_level', 'urgent')->count();

        return [
            'period_days' => $days,
            'total_tests' => $totalTests,
            'total_patients' => $totalPatients,
            'abnormal_detection' => [
                'total_abnormal' => $abnormalCount + $criticalCount,
                'abnormal_rate' => $abnormalRate,
                'critical_count' => $criticalCount,
                'critical_rate' => $criticalRate,
            ],
            'patient_communication' => [
                'estimated_calls_prevented' => $callsPrevented,
                'call_reduction_rate' => $callReductionRate . '%',
                'description' => "~{$callReductionRate}% of results were auto-explained, reducing 'what does this mean?' calls.",
            ],
            'patient_retention' => [
                'retest_patients' => $retestPatients,
                'retest_rate' => $retestRate . '%',
                'description' => "{$retestRate}% of patients returned for follow-up testing.",
            ],
            'efficiency' => [
                'avg_turnaround_minutes' => round($avgTurnaround, 1),
                'description' => 'Average ' . round($avgTurnaround, 1) . ' minutes from submission to interpretation.',
            ],
            'urgent_findings' => [
                'count' => $urgentCount,
                'description' => $urgentCount . ' critical results flagged for immediate clinical attention.',
            ],
            'cost_efficiency' => [
                'total_cost_naira' => round($partnership->interpretationsThisMonth()->sum('cost_to_partner') / 100, 2),
                'rate_per_report' => $partnership->rateNaira(),
            ],
        ];
    }

    private function emptyMetrics(int $days): array
    {
        return [
            'period_days' => $days,
            'total_tests' => 0,
            'total_patients' => 0,
            'abnormal_detection' => ['total_abnormal' => 0, 'abnormal_rate' => 0, 'critical_count' => 0, 'critical_rate' => 0],
            'patient_communication' => ['estimated_calls_prevented' => 0, 'call_reduction_rate' => '0%', 'description' => 'Not enough data yet.'],
            'patient_retention' => ['retest_patients' => 0, 'retest_rate' => '0%', 'description' => 'Not enough data yet.'],
            'efficiency' => ['avg_turnaround_minutes' => 0, 'description' => 'Not enough data yet.'],
            'urgent_findings' => ['count' => 0, 'description' => 'No urgent findings.'],
            'cost_efficiency' => ['total_cost_naira' => 0, 'rate_per_report' => 0],
        ];
    }
}