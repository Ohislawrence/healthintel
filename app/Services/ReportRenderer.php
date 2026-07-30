<?php

namespace App\Services;

use App\Models\LabPartnership;
use App\Models\PartnerInterpretation;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportRenderer
{
    /**
     * Generate a white-label or co-branded PDF report for a single interpretation.
     */
    public function renderSingle(PartnerInterpretation $interpretation): string
    {
        $partnership = $interpretation->partnership;
        $provider = $partnership->provider;
        $patientName = $interpretation->patient_identifier ? "Patient #{$interpretation->patient_identifier}" : 'Patient';

        $interpretationText = $interpretation->interpretation_text
            ?? $this->generateFallbackText($interpretation);

        $data = [
            'provider' => $provider,
            'partnership' => $partnership,
            'interpretation' => $interpretation,
            'patient_name' => $patientName,
            'interpretation_text' => $interpretationText,
            'is_white_label' => $partnership->white_label,
            'brand_color' => $partnership->brand_primary_color ?: '#0E6B5C',
            'brand_logo' => $partnership->brand_logo_url,
            'brand_contact' => $partnership->brand_contact_info ?: ($provider->phone ?? $provider->email ?? ''),
            'generated_at' => now()->format('F j, Y \a\t h:i A'),
        ];

        $pdf = Pdf::loadView('reports.partner-interpretation', $data);
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

    public function generateFallbackText(PartnerInterpretation $i): string
    {
        $status = $this->assessStatus($i);
        $name = strtolower($i->test_name);

        return match ($status) {
            'normal' => "Your {$name} result of {$i->value} {$i->unit} is within the normal range. No action needed.",
            'low' => "Your {$name} result of {$i->value} {$i->unit} is below the normal range ({$i->reference_range_low} – {$i->reference_range_high} {$i->unit}). Please discuss this with your doctor.",
            'high' => "Your {$name} result of {$i->value} {$i->unit} is above the normal range ({$i->reference_range_low} – {$i->reference_range_high} {$i->unit}). Please discuss this with your doctor.",
            default => "Your {$name} result is {$i->value} {$i->unit}. Please consult your healthcare provider for interpretation.",
        };
    }
}