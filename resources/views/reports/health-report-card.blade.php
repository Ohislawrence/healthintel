<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Health Report Card — {{ $appName }}</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.5; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #0E6B5C; padding-bottom: 20px; }
        .header h1 { font-size: 24px; color: #0E6B5C; margin: 0 0 5px 0; }
        .header .subtitle { color: #666; font-size: 12px; }
        .score-box { background: #0E6B5C; color: #fff; border-radius: 8px; padding: 15px 25px; text-align: center; margin: 20px 0; display: inline-block; }
        .score-box .score { font-size: 36px; font-weight: bold; }
        .score-box .grade { font-size: 14px; text-transform: uppercase; letter-spacing: 2px; }
        .section { margin-bottom: 25px; }
        .section h2 { font-size: 14px; color: #0E6B5C; border-bottom: 1px solid #e0e0e0; padding-bottom: 5px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th { background: #f5f5f5; text-align: left; padding: 8px; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; }
        td { padding: 6px 8px; border-bottom: 1px solid #eee; font-size: 10px; }
        .flag-normal { color: #0E6B5C; font-weight: bold; }
        .flag-high { color: #B9812E; font-weight: bold; }
        .flag-low { color: #B9812E; font-weight: bold; }
        .flag-critical { color: #A8432F; font-weight: bold; }
        .disclaimer { margin-top: 30px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; font-size: 9px; color: #666; text-align: center; }
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $appName }} Health Report Card</h1>
        <div class="subtitle">Generated {{ $generatedAt->format('F j, Y \a\t g:i A') }}</div>
    </div>

    {{-- Patient Info --}}
    <div class="section">
        <h2>Patient Information</h2>
        <table>
            <tr><td><strong>Name:</strong></td><td>{{ $user->name }}</td></tr>
            @if(!empty($metrics))
                @if(isset($metrics['age']))<tr><td><strong>Age:</strong></td><td>{{ $metrics['age']['value'] }} {{ $metrics['age']['unit'] }}</td></tr>@endif
                @if(isset($metrics['sex']))<tr><td><strong>Sex:</strong></td><td>{{ $metrics['sex']['value'] }}</td></tr>@endif
                @if(isset($metrics['bmi']))<tr><td><strong>BMI:</strong></td><td>{{ $metrics['bmi']['value'] }} {{ $metrics['bmi']['unit'] }}</td></tr>@endif
                @if(isset($metrics['blood_pressure']))<tr><td><strong>Blood Pressure:</strong></td><td>{{ $metrics['blood_pressure']['value'] }} {{ $metrics['blood_pressure']['unit'] }}</td></tr>@endif
            @endif
        </table>
    </div>

    {{-- Health Score --}}
    <div class="section" style="text-align:center;">
        <div class="score-box">
            <div class="grade">Health Score</div>
            <div class="score">{{ $healthScore['total'] }}/100</div>
            <div class="grade">Grade {{ $healthScore['grade'] }}</div>
        </div>
    </div>

    {{-- Known Conditions --}}
    @if(!empty($conditions))
    <div class="section">
        <h2>Reported Medical Conditions</h2>
        <p>{{ implode(', ', $conditions) }}</p>
    </div>
    @endif

    {{-- Current Medications --}}
    @if(!empty($medications))
    <div class="section">
        <h2>Current Medications</h2>
        <p>{{ implode(', ', $medications) }}</p>
    </div>
    @endif

    {{-- Latest Lab Results --}}
    @if($latestSubmission)
    <div class="section">
        <h2>Latest Lab Results ({{ $latestSubmission->testPanel?->name ?? 'Panel' }} — {{ $latestSubmission->submitted_at?->format('M j, Y') }})</h2>
        <table>
            <thead>
                <tr>
                    <th>Test</th>
                    <th>Value</th>
                    <th>Unit</th>
                    <th>Flag</th>
                </tr>
            </thead>
            <tbody>
                @foreach($latestSubmission->values as $value)
                <tr>
                    <td>{{ $value->test_name }}</td>
                    <td>{{ $value->value }}</td>
                    <td>{{ $value->unit }}</td>
                    <td class="flag-{{ $value->flag }}">{{ ucfirst($value->flag) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($latestSubmission->interpretation?->interpretation_text)
        <div style="margin-top: 10px; padding: 10px; background: #f0faf8; border-left: 3px solid #0E6B5C;">
            <strong>Interpretation:</strong><br>
            {!! nl2br(e($latestSubmission->interpretation->interpretation_text)) !!}
        </div>
        @endif
    </div>
    @endif

    {{-- Trends Summary --}}
    @if(!empty($trends))
    <div class="section">
        <h2>Biomarker Trends</h2>
        @foreach($trends as $testSlug => $trend)
        <p><strong>{{ ucfirst(str_replace('-', ' ', $testSlug)) }}:</strong> {{ $trend['direction'] ?? 'Stable' }} ({{ $trend['change_percent'] ?? 'N/A' }}% change)</p>
        @endforeach
    </div>
    @endif

    {{-- Previous Submissions --}}
    @if($submissions->count() > 1)
    <div class="section">
        <h2>Recent Lab History</h2>
        <table>
            <thead>
                <tr><th>Date</th><th>Panel</th><th>Tests</th><th>Status</th></tr>
            </thead>
            <tbody>
                @foreach($submissions->skip(1)->take(5) as $sub)
                <tr>
                    <td>{{ $sub->submitted_at?->format('M j, Y') }}</td>
                    <td>{{ $sub->testPanel?->name ?? 'N/A' }}</td>
                    <td>{{ $sub->values->count() }}</td>
                    <td>{{ $sub->interpretation ? 'Interpreted' : 'Pending' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="disclaimer">
        <strong>IMPORTANT DISCLAIMER:</strong> This health report card is generated for informational purposes only. It is NOT a medical diagnosis and does NOT replace advice from a licensed healthcare professional. Always consult your doctor about your lab results and health status. {{ $appName }} provides general health information, not medical advice.
    </div>

    <div class="footer">
        Generated by {{ $appName }} &bull; {{ $generatedAt->format('Y-m-d H:i:s') }} UTC
    </div>
</body>
</html>