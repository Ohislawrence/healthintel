<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Medical Test Interpretation</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; color: #1B2622; line-height: 1.6; font-size: 11pt; }
        .header { border-bottom: 2px solid {{ $brand_color }}; padding-bottom: 16px; margin-bottom: 24px; }
        .header-content { display: flex; align-items: center; gap: 16px; }
        .logo { max-width: 140px; max-height: 60px; }
        .logo-placeholder { font-size: 18pt; font-weight: bold; color: {{ $brand_color }}; }
        .provider-name { font-size: 16pt; font-weight: bold; }
        .provider-contact { font-size: 9pt; color: #57645D; }
        .meta { background: #F4F6F3; border-radius: 8px; padding: 12px 16px; margin-bottom: 24px; font-size: 9pt; }
        .meta-row { display: flex; justify-content: space-between; }
        .meta-label { color: #57645D; }
        .meta-value { font-weight: 600; }
        .result-card { border: 1px solid #DCE3DE; border-radius: 8px; padding: 20px; margin-bottom: 16px; }
        .result-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .test-name { font-size: 14pt; font-weight: bold; color: #1B2622; }
        .result-value { font-size: 18pt; font-weight: bold; color: {{ $brand_color }}; }
        .result-unit { font-size: 10pt; color: #57645D; font-weight: normal; }
        .range { font-size: 9pt; color: #57645D; margin-top: 4px; }
        .interpretation { background: rgba(14, 107, 92, 0.04); border-left: 3px solid {{ $brand_color }}; padding: 12px 16px; border-radius: 0 6px 6px 0; margin-top: 12px; font-size: 10pt; line-height: 1.7; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 8pt; font-weight: bold; }
        .badge-normal { background: #DCFCE7; color: #166534; }
        .badge-low { background: #FEF3C7; color: #92400E; }
        .badge-high { background: #FEE2E2; color: #991B1B; }
        .badge-unknown { background: #E5E7EB; color: #4B5563; }
        .footer { margin-top: 32px; padding-top: 16px; border-top: 1px solid #DCE3DE; font-size: 8pt; color: #57645D; }
        .powered-by { font-size: 7pt; color: #A0AEA7; text-align: center; margin-top: 8px; }
        .disclaimer { margin-top: 16px; padding: 12px; background: #FFF7ED; border: 1px solid #FFEDD5; border-radius: 6px; font-size: 8pt; color: #92400E; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            @if($brand_logo)
                <img src="{{ $brand_logo }}" class="logo" alt="Logo">
            @else
                <div class="logo-placeholder">{{ $provider->name }}</div>
            @endif
            <div>
                <div class="provider-name">{{ $is_white_label ? $provider->name : 'HealthIntel' }}</div>
                <div class="provider-contact">{{ $brand_contact }}</div>
            </div>
        </div>
    </div>

    <div class="meta">
        <div class="meta-row">
            <span><span class="meta-label">Patient:</span> <span class="meta-value">{{ $patient_name }}</span></span>
            <span><span class="meta-label">Test:</span> <span class="meta-value">{{ $interpretation->test_name }}</span></span>
        </div>
        <div class="meta-row" style="margin-top:6px">
            <span><span class="meta-label">Generated:</span> <span class="meta-value">{{ $generated_at }}</span></span>
            <span><span class="meta-label">Provider:</span> <span class="meta-value">{{ $provider->name }}</span></span>
        </div>
    </div>

    <div class="result-card">
        <div class="result-header">
            <div>
                <div class="test-name">{{ $interpretation->test_name }}</div>
                <div class="range">
                    Normal range: {{ $interpretation->reference_range_low && $interpretation->reference_range_high ? "{$interpretation->reference_range_low} – {$interpretation->reference_range_high} {$interpretation->unit}" : 'Not specified' }}
                </div>
            </div>
            <div style="text-align: right">
                <div class="result-value">
                    {{ $interpretation->value }} <span class="result-unit">{{ $interpretation->unit }}</span>
                </div>
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
                <span class="badge badge-{{ $status }}">{{ strtoupper($status) }}</span>
            </div>
        </div>

        <div class="interpretation">
            {!! nl2br(e($interpretation_text)) !!}
        </div>
    </div>

    <div class="disclaimer">
        <strong>Medical Disclaimer:</strong> This interpretation is for informational purposes only and does not constitute medical advice. It is generated by an AI-assisted system and has not been reviewed by a physician. Always consult a qualified healthcare provider for diagnosis and treatment decisions.
    </div>

    <div class="footer">
        <div>{{ $provider->name }} | {{ $brand_contact }}</div>
        @if(!$is_white_label)
            <div class="powered-by">Powered by HealthIntel — Your health, decoded.</div>
        @endif
    </div>
</body>
</html>