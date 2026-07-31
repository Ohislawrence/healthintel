<?php

namespace App\Services;

use App\Models\ReferenceRange;

/**
 * Classifies lab results against verified reference ranges BEFORE the LLM is called.
 * The LLM should only generate plain-language explanation AFTER classification.
 *
 * Supports: age, sex, pregnancy-aware matching, unit conversion, and confidence scoring.
 */
class ReferenceRangeService
{
    /**
     * Unit conversion factors to canonical units.
     * Key: canonical_unit -> [alias -> factor]
     *
     * Example: haemoglobin in g/dL is canonical. If someone submits in g/L,
     * multiply by 0.1 to convert to g/dL before matching.
     */
    protected array $unitConversions = [
        'g/dL' => [
            'g/l' => 0.1,
            'g/dl' => 1,
            'g%' => 1,
            'g/100ml' => 1,
            'mmol/l' => null, // requires substance-specific molar mass — handled separately
        ],
        'mg/dL' => [
            'mg/dl' => 1,
            'mmol/l' => null,
            'umol/l' => null,
            'mg/l' => 0.1,
        ],
        'mmol/L' => [
            'mmol/l' => 1,
            'mg/dl' => null, // requires molar mass
        ],
        '10^9/L' => [
            '10^9/l' => 1,
            '/mm3' => null, // not directly convertible
            'cells/ul' => null,
            'x10^9/l' => 1,
            'k/ul' => 1,
        ],
        '10^12/L' => [
            '10^12/l' => 1,
            'million/ul' => 1,
            'x10^12/l' => 1,
        ],
        '%' => [
            '%' => 1,
            'percent' => 1,
            'fraction' => 0.01,
        ],
        'IU/L' => [
            'iu/l' => 1,
            'u/l' => 1,
        ],
        'U/L' => [
            'u/l' => 1,
            'iu/l' => 1,
        ],
        'ng/mL' => [
            'ng/ml' => 1,
            'ug/l' => 1,
            'mcg/l' => 1,
        ],
        'pg/mL' => [
            'pg/ml' => 1,
            'ng/l' => 1,
        ],
        'mEq/L' => [
            'meq/l' => 1,
            'mmol/l' => 1, // for monovalent ions like Na+, K+
        ],
        'g/L' => [
            'g/l' => 1,
            'g/dl' => 10,
            'mg/dl' => 0.01,
        ],
    ];

    /**
     * Known test-specific unit conversions (for tests where molar mass matters).
     */
    protected array $testSpecificConversions = [
        'glucose' => [
            'from' => 'mmol/l',
            'to' => 'mg/dl',
            'factor' => 18.018,
        ],
        'creatinine' => [
            'from' => 'umol/l',
            'to' => 'mg/dl',
            'factor' => 0.0113,
        ],
        'bilirubin' => [
            'from' => 'umol/l',
            'to' => 'mg/dl',
            'factor' => 0.0585,
        ],
        'total_bilirubin' => [
            'from' => 'umol/l',
            'to' => 'mg/dl',
            'factor' => 0.0585,
        ],
        'cholesterol' => [
            'from' => 'mmol/l',
            'to' => 'mg/dl',
            'factor' => 38.67,
        ],
        'total_cholesterol' => [
            'from' => 'mmol/l',
            'to' => 'mg/dl',
            'factor' => 38.67,
        ],
        'ldl' => [
            'from' => 'mmol/l',
            'to' => 'mg/dl',
            'factor' => 38.67,
        ],
        'hdl' => [
            'from' => 'mmol/l',
            'to' => 'mg/dl',
            'factor' => 38.67,
        ],
        'triglycerides' => [
            'from' => 'mmol/l',
            'to' => 'mg/dl',
            'factor' => 88.57,
        ],
        'bun' => [
            'from' => 'mmol/l',
            'to' => 'mg/dl',
            'factor' => 2.801,
        ],
        'uric_acid' => [
            'from' => 'umol/l',
            'to' => 'mg/dl',
            'factor' => 0.0168,
        ],
        'hb' => [
            'from' => 'g/l',
            'to' => 'g/dl',
            'factor' => 0.1,
        ],
        'haemoglobin' => [
            'from' => 'g/l',
            'to' => 'g/dl',
            'factor' => 0.1,
        ],
    ];

    /**
     * Classify a single test result against verified reference ranges.
     *
     * @param string $testName    The raw test name as submitted (e.g., "Haemoglobin", "ALT (SGPT)")
     * @param float  $value       The numeric result value
     * @param string $unit        The unit provided (e.g., "g/dL", "IU/L")
     * @param string|null $sex    male / female (null defaults to "all" ranges)
     * @param float|null $age     Age in years (null = no age filtering)
     * @param bool  $isPregnant   Whether patient is pregnant
     * @param int|null $trimester 1, 2, 3 (null if unknown)
     *
     * @return array {
     *     status: 'normal'|'abnormal_low'|'abnormal_high'|'critical_low'|'critical_high'|'unknown',
     *     matched_range: ReferenceRange|null,
     *     range_low: float|null,
     *     range_high: float|null,
     *     critical_low: float|null,
     *     critical_high: float|null,
     *     unit: string,
     *     confidence: 0-100,
     *     reason: string,
     *     original_value: float,
     *     original_unit: string,
     *     converted_value: float|null,
     *     was_converted: bool,
     * }
     */
    public function classify(
        string $testName,
        float $value,
        string $unit = '',
        ?string $sex = null,
        ?float $age = null,
        bool $isPregnant = false,
        ?int $trimester = null,
    ): array {
        $testCode = $this->normalizeTestCode($testName);
        $unit = trim(strtolower($unit));

        // Find best matching reference range
        $range = ReferenceRange::matchDemographics(
            $testCode, $sex, $age, $isPregnant, $trimester
        )->first();

        if (!$range) {
            // Try broader match: ignore sex specificity
            $range = ReferenceRange::where('test_code', $testCode)
                ->where('is_active', true)
                ->first();
        }

        if (!$range) {
            // Try fuzzy: match by normalized test_name substring
            $range = ReferenceRange::where('test_name', 'like', "%{$testName}%")
                ->where('is_active', true)
                ->first();
        }

        $baseResult = [
            'original_value' => $value,
            'original_unit' => $unit,
            'converted_value' => null,
            'was_converted' => false,
            'confidence' => 0,
        ];

        if (!$range) {
            return array_merge($baseResult, [
                'status' => 'unknown',
                'matched_range' => null,
                'range_low' => null,
                'range_high' => null,
                'critical_low' => null,
                'critical_high' => null,
                'unit' => $unit,
                'reason' => "No verified reference range available for '{$testName}'. Admin notified to add this range.",
            ]);
        }

        // Unit matching & conversion
        $rangeUnit = strtolower($range->unit);
        $workingValue = $value;
        $wasConverted = false;

        if ($unit && $unit !== $rangeUnit) {
            $converted = $this->convertUnit($value, $unit, $rangeUnit, $testCode);
            if ($converted !== null) {
                $workingValue = $converted;
                $wasConverted = true;
            }
            // If conversion fails, proceed with raw value but note it
        }

        // Classify against range
        $rangeLow = (float) $range->range_low;
        $rangeHigh = (float) $range->range_high;
        $criticalLow = $range->critical_low !== null ? (float) $range->critical_low : null;
        $criticalHigh = $range->critical_high !== null ? (float) $range->critical_high : null;

        $status = 'normal';
        $reason = '';

        // Check critical thresholds first
        if ($criticalLow !== null && $workingValue <= $criticalLow) {
            $status = 'critical_low';
            $reason = "Value {$workingValue} is at or below critical threshold of {$criticalLow} {$range->unit}.";
        } elseif ($criticalHigh !== null && $workingValue >= $criticalHigh) {
            $status = 'critical_high';
            $reason = "Value {$workingValue} is at or above critical threshold of {$criticalHigh} {$range->unit}.";
        } elseif ($workingValue < $rangeLow) {
            $status = 'abnormal_low';
            $reason = "Value {$workingValue} is below normal range ({$rangeLow}–{$rangeHigh} {$range->unit}).";
        } elseif ($workingValue > $rangeHigh) {
            $status = 'abnormal_high';
            $reason = "Value {$workingValue} is above normal range ({$rangeLow}–{$rangeHigh} {$range->unit}).";
        } else {
            $reason = "Value {$workingValue} is within normal range ({$rangeLow}–{$rangeHigh} {$range->unit}).";
        }

        // Confidence scoring
        $confidence = $this->calculateConfidence($range, $workingValue, $wasConverted, $unit, $rangeUnit, $sex, $age);

        return array_merge($baseResult, [
            'status' => $status,
            'matched_range' => $range,
            'range_low' => $rangeLow,
            'range_high' => $rangeHigh,
            'critical_low' => $criticalLow,
            'critical_high' => $criticalHigh,
            'unit' => $range->unit,
            'confidence' => $confidence,
            'reason' => $reason,
            'converted_value' => $wasConverted ? $workingValue : null,
            'was_converted' => $wasConverted,
            'source' => $range->source,
        ]);
    }

    /**
     * Convert a value from one unit to another.
     */
    public function convertUnit(float $value, string $fromUnit, string $toUnit, string $testCode = ''): ?float
    {
        $from = strtolower(trim($fromUnit));
        $to = strtolower(trim($toUnit));

        if ($from === $to) {
            return $value;
        }

        // Check test-specific conversions first
        $testKey = $this->normalizeTestCode($testCode);
        if (isset($this->testSpecificConversions[$testKey])) {
            $spec = $this->testSpecificConversions[$testKey];
            if ($spec['from'] === $from && $spec['to'] === $to) {
                return round($value * $spec['factor'], 4);
            }
            if ($spec['from'] === $to && $spec['to'] === $from) {
                return round($value / $spec['factor'], 4);
            }
        }

        // Check general unit conversions
        // Try direct conversion via canonical unit
        foreach ($this->unitConversions as $canonical => $conversions) {
            $fromFactor = $conversions[$from] ?? null;
            $toFactor = $conversions[$to] ?? null;

            if ($fromFactor !== null && $toFactor !== null && $fromFactor !== null && $toFactor !== null) {
                // Convert from -> canonical -> to
                $inCanonical = $value * $fromFactor;
                return round($inCanonical / $toFactor, 4);
            }
        }

        return null; // conversion not possible
    }

    /**
     * Normalize a test name to a slug-style test code for matching.
     */
    public function normalizeTestCode(string $testName): string
    {
        // Remove common parentheticals and punctuation
        $cleaned = trim(preg_replace('/\([^)]*\)/', '', $testName));
        $cleaned = preg_replace('/[^a-zA-Z0-9\s]/', '', $cleaned);
        $cleaned = trim($cleaned);

        // Common abbreviations map
        $abbreviations = [
            'full blood count' => 'fbc',
            'complete blood count' => 'cbc',
            'white blood cell' => 'wbc',
            'white blood cells' => 'wbc',
            'white cell count' => 'wbc',
            'red blood cell' => 'rbc',
            'red blood cells' => 'rbc',
            'packed cell volume' => 'pcv',
            'haemoglobin' => 'haemoglobin',
            'hemoglobin' => 'haemoglobin',
            'mean corpuscular volume' => 'mcv',
            'mean corpuscular haemoglobin' => 'mch',
            'mean corpuscular hemoglobin' => 'mch',
            'mean corpuscular haemoglobin concentration' => 'mchc',
            'mean corpuscular hemoglobin concentration' => 'mchc',
            'platelet count' => 'platelets',
            'platelets' => 'platelets',
            'red cell distribution width' => 'rdw',
            'alanine aminotransferase' => 'alt',
            'alanine transaminase' => 'alt',
            'aspartate aminotransferase' => 'ast',
            'aspartate transaminase' => 'ast',
            'alkaline phosphatase' => 'alp',
            'gamma glutamyl transferase' => 'ggt',
            'gamma gt' => 'ggt',
            'total protein' => 'total_protein',
            'albumin' => 'albumin',
            'globulin' => 'globulin',
            'fasting blood sugar' => 'glucose',
            'fasting blood glucose' => 'glucose',
            'random blood sugar' => 'glucose_random',
            'random blood glucose' => 'glucose_random',
            'glycated haemoglobin' => 'hba1c',
            'glycated hemoglobin' => 'hba1c',
            'glycosylated haemoglobin' => 'hba1c',
            'blood urea nitrogen' => 'bun',
            'urea' => 'urea',
            'creatinine' => 'creatinine',
            'uric acid' => 'uric_acid',
            'sodium' => 'sodium',
            'potassium' => 'potassium',
            'chloride' => 'chloride',
            'bicarbonate' => 'bicarbonate',
            'total cholesterol' => 'total_cholesterol',
            'ldl cholesterol' => 'ldl',
            'hdl cholesterol' => 'hdl',
            'triglycerides' => 'triglycerides',
            'thyroid stimulating hormone' => 'tsh',
            'free t3' => 'ft3',
            'free t4' => 'ft4',
            'total t3' => 'tt3',
            'total t4' => 'tt4',
            'prothrombin time' => 'pt',
            'partial thromboplastin time' => 'ptt',
            'activated partial thromboplastin time' => 'aptt',
            'international normalised ratio' => 'inr',
            'international normalized ratio' => 'inr',
            'c reactive protein' => 'crp',
            'erythrocyte sedimentation rate' => 'esr',
            'prostate specific antigen' => 'psa',
            'bilirubin total' => 'total_bilirubin',
            'total bilirubin' => 'total_bilirubin',
            'direct bilirubin' => 'direct_bilirubin',
            'conjugated bilirubin' => 'direct_bilirubin',
            'indirect bilirubin' => 'indirect_bilirubin',
            'unconjugated bilirubin' => 'indirect_bilirubin',
            'serum iron' => 'iron',
            'ferritin' => 'ferritin',
            'vitamin b12' => 'b12',
            'vitamin d' => 'vitamin_d',
            'folate' => 'folate',
            'calcium' => 'calcium',
            'magnesium' => 'magnesium',
            'phosphate' => 'phosphate',
            'amylase' => 'amylase',
            'lipase' => 'lipase',
            'lactate dehydrogenase' => 'ldh',
            'creatine kinase' => 'ck',
            'troponin' => 'troponin',
            'hiv' => 'hiv',
            'hepatitis b surface antigen' => 'hbsag',
            'hepatitis c antibody' => 'hcv',
            'malaria parasite' => 'malaria_parasite',
            'widal test' => 'widal',
            'urinalysis ph' => 'urine_ph',
            'urine protein' => 'urine_protein',
            'urine glucose' => 'urine_glucose',
            'pregnancy test' => 'pregnancy_test',
            'blood group' => 'blood_group',
            'genotype' => 'genotype',
        ];

        $lower = strtolower($cleaned);

        if (isset($abbreviations[$lower])) {
            return $abbreviations[$lower];
        }

        // Fallback: slugify
        $slug = strtolower(preg_replace('/[\s_]+/', '_', $cleaned));
        return $slug ?: $lower;
    }

    /**
     * Calculate confidence score (0–100) for the classification.
     *
     * Factors:
     * - Range exists in DB: +40
     * - Sex-specific range (not 'all'): +15
     * - Age-specific range available: +10
     * - Pregnancy-specific range: +10
     * - Unit matches exactly (no conversion): +10
     * - Unit was converted successfully: +5
     * - Range has been reviewed: +10
     * - Value is far inside/outside range (not borderline): +5
     */
    protected function calculateConfidence(
        ReferenceRange $range,
        float $workingValue,
        bool $wasConverted,
        string $originalUnit,
        string $rangeUnit,
        ?string $sex,
        ?float $age,
    ): int {
        $score = 40; // base — range found

        // Demographics specificity
        if ($range->sex !== 'all') $score += 15;
        if ($range->age_min_years !== null || $range->age_max_years !== null) $score += 10;
        if ($range->pregnancy_applicable) $score += 10;

        // Unit quality
        $originalUnitClean = strtolower(trim($originalUnit));
        $rangeUnitClean = strtolower(trim($rangeUnit));

        if ($originalUnitClean === $rangeUnitClean) {
            $score += 15;
        } elseif ($wasConverted) {
            $score += 8;
        } else {
            $score += 2; // units differ, no conversion — low confidence
        }

        // Review status
        if ($range->reviewed_at) $score += 10;

        // Borderline penalty: value within 5% of boundary
        $rangeLow = (float) $range->range_low;
        $rangeHigh = (float) $range->range_high;
        $margin = ($rangeHigh - $rangeLow) * 0.05;
        if (
            ($workingValue >= $rangeLow - $margin && $workingValue <= $rangeLow + $margin) ||
            ($workingValue >= $rangeHigh - $margin && $workingValue <= $rangeHigh + $margin)
        ) {
            $score -= 5;
        }

        return max(0, min(100, $score));
    }
}