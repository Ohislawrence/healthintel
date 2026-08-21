<?php

namespace App\Services;

/**
 * Extracts structured test values from raw OCR/PDF text
 * by matching known test names against the reference ranges database.
 */
class PdfValueExtractor
{
    public function __construct(
        private ReferenceRangeService $referenceRangeService,
    ) {}

    /**
     * Parse raw PDF text and attempt to extract test_name, value, unit tuples.
     *
     * Uses known test names from the reference_ranges table to find
     * likely value pairs in the text.
     */
    public function extract(string $pdfText): array
    {
        if (empty(trim($pdfText))) {
            return [];
        }

        $results = [];
        $knownTests = \App\Models\ReferenceRange::where('is_active', true)
            ->select('test_name', 'test_code', 'unit')
            ->get();

        foreach ($knownTests as $test) {
            // Build patterns to find "TestName: 12.5" or "TestName 12.5 g/dL" in text
            $name = preg_quote($test->test_name, '/');
            $code = preg_quote($test->test_code, '/');
            // Escape the unit so characters like "/" in "g/dL" don't break the
            // regular expression delimiter.
            $unit = preg_quote($test->unit ?? '', '/');
            $patterns = [
                // "Haemoglobin: 12.5 g/dL" or "Haemoglobin 12.5 g/dL"
                "/{$name}\s*[:=]?\s*(\d+\.?\d*)\s*({$unit})?/i",
                // "HB: 12.5" (where test_code might be abbreviated)
                "/{$code}\s*[:=]?\s*(\d+\.?\d*)\s*({$unit})?/i",
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $pdfText, $matches)) {
                    $value = (float) $matches[1];
                    $unit = $matches[2] ?? $test->unit;
                    $confidence = $this->assessConfidence($matches);

                    $results[] = [
                        'test_name' => $test->test_name,
                        'test_code' => $test->test_code,
                        'value' => $value,
                        'unit' => $unit,
                        'confidence' => $confidence, // 50-100 based on match quality
                    ];

                    break; // one match per test
                }
            }
        }

        return $results;
    }

    /**
     * Assess how confident we are in this extracted value.
     * 100 = perfect match with unit, 70 = match without unit, 50 = weak match.
     */
    private function assessConfidence(array $matches): int
    {
        $full = $matches[0];
        $hasUnit = !empty($matches[2] ?? null);
        $hasColon = str_contains($full, ':') || str_contains($full, '=');

        if ($hasUnit && $hasColon) return 90;
        if ($hasUnit) return 80;
        if ($hasColon) return 70;
        return 50;
    }
}