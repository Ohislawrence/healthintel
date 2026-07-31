<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Medical Test Interpretation — Clinician View</title>
    <style>
        body { font-family: 'DejaVu Sans Mono', 'Courier New', monospace; color: #1B2622; line-height: 1.5; font-size: 10pt; }
        .header { border-bottom: 2px solid {{ $brand_color }}; padding-bottom: 12px; margin-bottom: 20px; }
        .header-content { display: flex; align-items: center; gap: 16px; }
        .logo { max-width: 120px; max-height: 50px; }
        .logo-placeholder { font-size: 16pt; font-weight: bold; color: {{ $brand_color }}; }
        .provider-name { font-size: 14pt; font-weight: bold; }
        .label-watermark { position: absolute; top: 20px; right: 20px; font-size: 10pt; color: #DC2626; font-weight: bold; padding: 4px 12px; border: 2px solid #DC2626; border-radius: 4px; }
        .meta { background: #F4F6F3; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; font-size: 9pt; }
        .meta-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .meta-label { color: #57645D; }
        .meta-value { font-weight: 600; }
        .section-title { font-size: 11pt; font-weight: bold; color: {{ $brand_color }}; margin: 16px 0 8px 0; border-bottom: 1px solid #E5E7EB; padding-bottom: 4px; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 9pt; }
        .data-table th { text-align: left; padding: 6px 8px; background: #EEF2FF; color: #374151; font-weight: 600; font-size: 8pt; text-transform: uppercase; }
        .data-table td { padding: 6px 8px; border-bottom: 1px solid #F3F4F6; }
        .data-table .value { font-family: monospace; font-weight: bold; }
        .data-table .flag-critical { color: #DC2626; font-weight: bold; }
        .data-table .flag-high { color: #EA580C; }
        .data-table .flag-low { color: #CA8A04; }
        .interpretation-block { background: #F9FAFB; border-left: 3px solid {{ $brand_color }}; padding: 10px 14px; border-radius: 0 6px 6px 0; margin-top: 12px; font-size: 10pt; line-height: 1.6; white-space: pre-wrap; }
        .footer { margin-top: 24px; padding-top: 12px; border-top: 1px solid #DCE3DE; font-size: 8pt; color: #57645D; }
        .powered-by { font-size: 7pt; color: #A0AEA7; text-align: center; margin-top: 8px; }
        .disclaimer { margin-top: 12px; padding: 10px; background: #FFF7ED; border: 1px solid #FFEDD5; border-radius: 4px; font-size: 7pt; color: #92400E; }
    </style>
</head>
<body>
    <div class="label-watermark">CLINICIAN VIEW — NOT FOR PATIENT</div>

    <div class="header">
        <div class="header-content">
            @if($brand_logo)
                <img src="{{ $brand_logo }}" class="logo" alt="Logo">
            @else
                <div class="logo-placeholder">{{ $provider->name }}</div>
            @endif
            <div>
                <div class="provider-name">{{ $is_white_label ? $provider->name : 'HealthIntel' }} — Lab Report</div>
            </div>
        </div>
    </div>

    <div class="meta">
        <div class="meta-row">
            <span><span class="meta-label">Patient:</span> <span class="meta-value">{{ $patient_name }}</span></span>
            <span><span class="meta-label">Generated:</span> <span class="meta-value">{{ $generated_at }}</span></span>
        </div>
        @if($interpretation->sex || $interpretation->age)
        <div class="meta-row">
            <span>
                @if($interpretation->sex)<span class="meta-label">Sex:</span> <span class="meta-value">{{ $interpretation->sex }}</span>@endif
                @if($interpretation->age) <span class="meta-label">Age:</span> <span class="meta-value">{{ $interpretation->age }}y</span>@endif
            </span>
            <span><span class="meta-label">Provider:</span> <span class="meta-value">{{ $provider->name }}</span></span>
        </div>
        @endif
    </div>

    @if($interpretation->escalation_level === 'urgent')
    <div style="background: #DC2626; color: white; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 11pt; font-weight: bold; text-align: center;">
        ⚠️ URGENT: {{ $interpretation->escalation_message ?? 'This result is critically outside range — seek urgent medical attention.' }}
    </div>
    @elseif($interpretation->escalation_level === 'flagged')
    <div style="background: #FEF3C7; color: #92400E; padding: 10px 16px; border: 1px solid #FCD34D; border-radius: 8px; margin-bottom: 20px; font-size: 10pt; text-align: center;">
        ⚠ FLAGGED: {{ $interpretation->escalation_message ?? 'This result is outside the normal range — speak to a doctor.' }}
    </div>
    @endif

    <div class="section-title">Test Result</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Test</th>
                <th>Result</th>
                <th>Reference Range</th>
                <th>Flag</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>{{ $interpretation->test_name }}</strong></td>
                <td class="value">{{ $interpretation->value }} <span style="font-weight:normal;font-size:9pt;">{{ $interpretation->unit }}</span></td>
                <td>
                    @if($interpretation->reference_range_low && $interpretation->reference_range_high)
                        {{ $interpretation->reference_range_low }} – {{ $interpretation->reference_range_high }} {{ $interpretation->unit }}
                    @else
                        Not specified
                    @endif
                </td>
                <td>
                    @php
                        $status = 'unknown';
                        if ($interpretation->reference_range_low && $interpretation->reference_range_high) {
                            $v = (float)$interpretation->value;
                            $l = (float)$interpretation->reference_range_low;
                            $h = (float)$interpretation->reference_range_high;
                            if ($v < $l) $status = 'low';
                            elseif ($v > $h) $status = 'high';
                            else $status = 'normal';
                        }
                    @endphp
                    <span class="flag-{{ $status }}">{{ strtoupper($status) }}</span>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Clinical Interpretation</div>
    <div class="interpretation-block">
        {!! nl2br(e($interpretation_text)) !!}
    </div>

    <div class="section-title">Patient-Facing Summary</div>
    <div class="interpretation-block" style="border-left-color:#6B7280;">
        {!! nl2br(e($interpretation->interpretation_text ?? 'Not generated')) !!}
    </div>

    <div class="disclaimer">
        <strong>CLINICIAN VIEW — INTERNAL USE ONLY:</strong> This interpretation is generated by an AI-assisted system using verified clinical reference ranges. It is a decision-support tool and does not replace clinical judgment. The patient-facing version has simplified language and must be reviewed before release. LabDoc Reference Range Engine v1.0.
    </div>

    <div class="footer">
        <div>{{ $provider->name }} | {{ $brand_contact }}</div>
        <div>Clinician View — Generated {{ $generated_at }}</div>
        @if(!$is_white_label)
            <div class="powered-by">Powered by HealthIntel — LabDoc Reference Range Engine</div>
        @endif
    </div>
</body>
</html>