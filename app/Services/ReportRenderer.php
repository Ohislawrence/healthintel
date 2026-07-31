<?php

namespace App\Services;

use App\Models\LabPartnership;
use App\Models\PartnerInterpretation;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportRenderer
{
    public function __construct(
        private ?ReferenceRangeService $referenceRangeService = null,
    ) {}

    /**
     * Lazy-resolve ReferenceRangeService.
     */
    private function rangeService(): ReferenceRangeService
    {
        return $this->referenceRangeService ??= app(ReferenceRangeService::class);
    }
    /**
     * Generate a white-label or co-branded PDF report for a single interpretation.
     *
     * @param string $version 'patient' (default) or 'clinician'
     */
    public function renderSingle(PartnerInterpretation $interpretation, string $version = 'patient'): string
    {
        $partnership = $interpretation->partnership;
        $provider = $partnership->provider;
        $patientName = $interpretation->patient_identifier ? "Patient #{$interpretation->patient_identifier}" : 'Patient';

        $isClinicianView = $version === 'clinician';

        $interpretationText = $isClinicianView
            ? ($interpretation->clinician_interpretation_text
                ?? $this->generateClinicianFallbackText($interpretation))
            : ($interpretation->interpretation_text
                ?? $this->generateFallbackText($interpretation));

        $view = $isClinicianView
            ? 'reports.partner-interpretation-clinician'
            : 'reports.partner-interpretation';

        $data = [
            'provider' => $provider,
            'partnership' => $partnership,
            'interpretation' => $interpretation,
            'patient_name' => $patientName,
            'interpretation_text' => $interpretationText,
            'is_clinician_view' => $isClinicianView,
            'is_white_label' => $partnership->white_label,
            'brand_color' => $partnership->brand_primary_color ?: '#0E6B5C',
            'brand_logo' => $partnership->brand_logo_url,
            'brand_contact' => $partnership->brand_contact_info ?: ($provider->phone ?? $provider->email ?? ''),
            'generated_at' => now()->format('F j, Y \a\t h:i A'),
        ];

        $pdf = Pdf::loadView($view, $data);
        $pdf->setPaper('A4');

        return $pdf->output();
    }

    /**
     * Generate a batch PDF containing multiple interpretations for one patient.
     *
     * @param PartnerInterpretation[] $interpretations
     */
    public function renderBatch(array $interpretations, LabPartnership $partnership): string
    {
        $provider = $partnership->provider;
        $patientId = $interpretations[0]->patient_identifier ?? 'Batch';

        $items = [];
        foreach ($interpretations as $i) {
            $items[] = [
                'test_name' => $i->test_name,
                'value' => $i->value,
                'unit' => $i->unit,
                'range' => $this->formatRange($i),
                'interpretation_text' => $i->interpretation_text ?? $this->generateFallbackText($i),
                'status_indicator' => $this->assessStatus($i),
            ];
        }

        $data = [
            'provider' => $provider,
            'partnership' => $partnership,
            'patient_id' => $patientId,
            'items' => $items,
            'is_white_label' => $partnership->white_label,
            'brand_color' => $partnership->brand_primary_color ?: '#0E6B5C',
            'brand_logo' => $partnership->brand_logo_url,
            'brand_contact' => $partnership->brand_contact_info ?: ($provider->phone ?? $provider->email ?? ''),
            'generated_at' => now()->format('F j, Y \a\t h:i A'),
        ];

        $pdf = Pdf::loadView('reports.partner-batch-interpretation', $data);

        return $pdf->output();
    }

    private function formatRange(PartnerInterpretation $i): string
    {
        if ($i->reference_range_low && $i->reference_range_high) {
            return "{$i->reference_range_low} – {$i->reference_range_high} {$i->unit}";
        }
        return 'Not provided';
    }

    private function assessStatus(PartnerInterpretation $i): string
    {
        $parsedAge = null;
        if ($i->age) {
            $parsedAge = (float) preg_replace('/[^0-9.]/', '', $i->age);
        }

        try {
            $classification = $this->rangeService()->classify(
                testName: $i->test_name,
                value: (float) $i->value,
                unit: $i->unit ?? '',
                sex: $i->sex,
                age: $parsedAge ?: null,
            );

            return match ($classification['status']) {
                'critical_low' => 'critical',
                'critical_high' => 'critical',
                'abnormal_low' => 'low',
                'abnormal_high' => 'high',
                'normal' => 'normal',
                default => 'unknown',
            };
        } catch (\Throwable) {
            // Fallback to manual range check
            if (!$i->reference_range_low || !$i->reference_range_high) {
                return 'unknown';
            }
            $value = (float) $i->value;
            $low = (float) $i->reference_range_low;
            $high = (float) $i->reference_range_high;
            if ($value < $low) return 'low';
            if ($value > $high) return 'high';
            return 'normal';
        }
    }

    /**
     * Generate a clinician-facing fallback text.
     */
    public function generateClinicianFallbackText(PartnerInterpretation $i): string
    {
        $status = $this->assessStatus($i);
        $name = $i->test_name;
        $rangeLow = $i->reference_range_low;
        $rangeHigh = $i->reference_range_high;
        $unit = $i->unit ?? '';

        $classificationLine = "Test: {$name} | Result: {$i->value} {$unit}";
        $rangeLine = $rangeLow && $rangeHigh
            ? " | Reference: {$rangeLow} – {$rangeHigh} {$unit}"
            : '';

        $body = match ($status) {
            'critical' => "CRITICAL: Result is critically outside range. Immediate clinical evaluation required. "
                . "Correlate with clinical presentation. Consider urgent repeat testing and specialist referral.",
            'low' => "ABNORMAL LOW: Result below reference range. "
                . "Consider nutritional deficiency, chronic disease, or medication effects. Clinical correlation advised.",
            'high' => "ABNORMAL HIGH: Result exceeds reference range. "
                . "Consider inflammatory, metabolic, or organ-related pathology. Further targeted testing may be warranted.",
            'normal' => "NORMAL: Result within verified reference range. No further action required for this test.",
            default => "Reference range not verified. Interpret with clinical judgment.",
        };

        return "{$classificationLine}{$rangeLine}\n\n{$body}\n\n— LabDoc Reference Range Engine";
    }

    public function generateFallbackText(PartnerInterpretation $i): string
    {
        $status = $this->assessStatus($i);
        $name = strtolower($i->test_name);

        return match ($status) {
            'normal' => "Your {$name} result of {$i->value} {$i->unit} is within the normal range. No action needed.",
            'low' => "Your {$name} result of {$i->value} {$i->unit} is below the normal range ({$i->reference_range_low} – {$i->reference_range_high} {$i->unit}). This result is outside range — speak to a doctor.",
            'high' => "Your {$name} result of {$i->value} {$i->unit} is above the normal range ({$i->reference_range_low} – {$i->reference_range_high} {$i->unit}). This result is outside range — speak to a doctor.",
            'critical' => "⚠️ Your {$name} result of {$i->value} {$i->unit} is critically outside the normal range. This requires urgent medical attention — speak to a doctor immediately.",
            default => "Your {$name} result is {$i->value} {$i->unit}. Please consult your healthcare provider for interpretation.",
        };
    }
}