<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserHealthMetric;
use App\Models\UserTrackerSnapshot;

/**
 * HealthContextService
 *
 * Assembles tracked tool data (measurements + trackers) into a compact
 * text block so the AI interpretation prompts (lab results + symptom
 * checker) can reason over real user data instead of treating the tools
 * hub as a disconnected bolt-on.
 */
class HealthContextService
{
    /** NPHCDA child immunization schedule (age in weeks). */
    private const VACCINE_SCHEDULE = [
        ['name' => 'BCG', 'ageWeeks' => 0],
        ['name' => 'OPV 0', 'ageWeeks' => 0],
        ['name' => 'HepB Birth', 'ageWeeks' => 0],
        ['name' => 'OPV 1', 'ageWeeks' => 6],
        ['name' => 'Pentavalent 1', 'ageWeeks' => 6],
        ['name' => 'PCV 1', 'ageWeeks' => 6],
        ['name' => 'Rota 1', 'ageWeeks' => 6],
        ['name' => 'IPV 1', 'ageWeeks' => 6],
        ['name' => 'OPV 2', 'ageWeeks' => 10],
        ['name' => 'Pentavalent 2', 'ageWeeks' => 10],
        ['name' => 'PCV 2', 'ageWeeks' => 10],
        ['name' => 'Rota 2', 'ageWeeks' => 10],
        ['name' => 'OPV 3', 'ageWeeks' => 14],
        ['name' => 'Pentavalent 3', 'ageWeeks' => 14],
        ['name' => 'PCV 3', 'ageWeeks' => 14],
        ['name' => 'IPV 2', 'ageWeeks' => 14],
        ['name' => 'Vitamin A 1', 'ageWeeks' => 26],
        ['name' => 'Measles 1', 'ageWeeks' => 39],
        ['name' => 'Yellow Fever', 'ageWeeks' => 39],
        ['name' => 'Vitamin A 2', 'ageWeeks' => 52],
        ['name' => 'Measles 2', 'ageWeeks' => 65],
    ];

    /**
     * Build a markdown block summarizing tracked health data, or null
     * when the user has no relevant tool data yet.
     */
    public function buildContextBlock(User $user): ?string
    {
        $lines = [];

        $this->appendMeasurements($user, $lines);
        $this->appendBloodPressure($user, $lines);
        $this->appendImmunizationGaps($user, $lines);

        if (empty($lines)) {
            return null;
        }

        return "## Tracked Health Data\n" . implode("\n", array_map(fn ($l) => "- {$l}", $lines));
    }

    private function appendMeasurements(User $user, array &$lines): void
    {
        $bmi = UserHealthMetric::where('user_id', $user->id)
            ->where('metric_type', 'bmi')
            ->latest('recorded_at')
            ->first();

        if ($bmi && isset($bmi->data['bmi'])) {
            $category = $bmi->data['category'] ?? null;
            $label = $category ? " ({$category})" : '';
            $lines[] = "BMI: {$bmi->data['bmi']} kg/m²{$label}";
        }

        $whr = UserHealthMetric::where('user_id', $user->id)
            ->where('metric_type', 'waist_hip_ratio')
            ->latest('recorded_at')
            ->first();

        if ($whr && isset($whr->data['ratio'])) {
            $risk = $whr->data['risk'] ?? null;
            $riskLabel = $risk ? " — {$risk} risk" : '';
            $lines[] = "Waist-to-hip ratio: {$whr->data['ratio']}{$riskLabel}";
        }

        $bmr = UserHealthMetric::where('user_id', $user->id)
            ->where('metric_type', 'bmr')
            ->latest('recorded_at')
            ->first();

        if ($bmr && isset($bmr->data['bmr'])) {
            $lines[] = "BMR (resting calories/day): {$bmr->data['bmr']}";
        }
    }

    private function appendBloodPressure(User $user, array &$lines): void
    {
        $readings = $this->recentBloodPressure($user->id, 5);

        if (empty($readings)) {
            return;
        }

        $latest = $readings[0];
        $category = $this->bpCategory($latest['systolic'], $latest['diastolic']);
        $lines[] = "Blood pressure (latest): {$latest['systolic']}/{$latest['diastolic']} mmHg — {$category}";

        if (count($readings) > 1) {
            $trend = implode(', ', array_map(
                fn ($r) => "{$r['systolic']}/{$r['diastolic']} ({$r['date']})",
                $readings
            ));
            $lines[] = "Blood pressure trend (recent first): {$trend}";
        }
    }

    private function appendImmunizationGaps(User $user, array &$lines): void
    {
        $snapshot = UserTrackerSnapshot::where('user_id', $user->id)
            ->whereNotNull('data')
            ->latest('date')
            ->first();

        $immunization = $snapshot?->data['immunization'] ?? [];
        $children = $immunization['children'] ?? [];
        $records = $immunization['records'] ?? [];

        if (empty($children)) {
            return;
        }

        foreach ($children as $child) {
            $name = $child['name'] ?? 'Unnamed child';
            $dob = $child['dob'] ?? null;
            $given = array_column($records[$child['id'] ?? ''] ?? [], 'vaccineName');

            $due = $this->dueVaccines($dob, $given);
            if (!empty($due)) {
                $lines[] = "Vaccination gaps for {$name}: " . implode(', ', $due);
            }
        }
    }

    private function dueVaccines(?string $dob, array $given): array
    {
        if (!$dob) {
            return [];
        }

        $due = [];
        foreach (self::VACCINE_SCHEDULE as $vaccine) {
            if (in_array($vaccine['name'], $given, true)) {
                continue;
            }

            $dueDate = (clone new \DateTime($dob))->modify('+' . ($vaccine['ageWeeks'] * 7) . ' days');
            if ($dueDate <= new \DateTime('today')) {
                $due[] = $vaccine['name'];
            }
        }

        return $due;
    }

    private function recentBloodPressure(int $userId, int $limit): array
    {
        $snapshots = UserTrackerSnapshot::where('user_id', $userId)
            ->whereNotNull('data')
            ->latest('date')
            ->limit(30)
            ->get();

        $readings = [];
        foreach ($snapshots as $snapshot) {
            $entries = $snapshot->data['blood_pressure'] ?? [];
            foreach ($entries as $entry) {
                if (!isset($entry['systolic'], $entry['diastolic'])) {
                    continue;
                }
                $readings[] = [
                    'systolic' => (int) $entry['systolic'],
                    'diastolic' => (int) $entry['diastolic'],
                    'date' => $snapshot->date->toDateString(),
                ];
                if (count($readings) >= $limit) {
                    break 2;
                }
            }
        }

        return $readings;
    }

    private function bpCategory(int $sys, int $dia): string
    {
        if ($sys >= 140 || $dia >= 90) return 'High (Stage 2)';
        if ($sys >= 130 || $dia >= 85) return 'High (Stage 1)';
        if ($sys >= 120 || $dia >= 80) return 'Elevated';
        return 'Normal';
    }
}