<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>HealthIntel Partnership Proposal</title>
    <style>
        @page { margin: 0; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; color: #1B2622; line-height: 1.6; margin: 0; }
        .cover { background: linear-gradient(135deg, #0E6B5C 0%, #0A4E43 100%); color: #fff; padding: 80px 60px; min-height: 100vh; display: flex; flex-direction: column; justify-content: center; }
        .cover h1 { font-size: 36pt; margin-bottom: 8px; letter-spacing: -0.02em; }
        .cover .subtitle { font-size: 18pt; opacity: 0.85; margin-bottom: 40px; }
        .cover .meta { font-size: 12pt; opacity: 0.7; margin-top: 60px; }
        .cover .date { font-size: 11pt; opacity: 0.6; }
        .page { padding: 50px 60px; page-break-before: always; }
        .page h2 { font-size: 22pt; color: #0E6B5C; margin-bottom: 16px; border-bottom: 2px solid #0E6B5C20; padding-bottom: 8px; }
        .page h3 { font-size: 14pt; color: #1B2622; margin-top: 24px; margin-bottom: 8px; }
        .page p { font-size: 10.5pt; color: #3D4A44; margin-bottom: 12px; }
        .feature-grid { display: flex; flex-wrap: wrap; gap: 16px; margin: 20px 0; }
        .feature-card { flex: 0 0 48%; background: #F4F6F3; border-radius: 8px; padding: 16px; }
        .feature-card .icon { font-size: 18pt; margin-bottom: 8px; }
        .feature-card h4 { font-size: 11pt; color: #0E6B5C; margin-bottom: 4px; }
        .feature-card p { font-size: 9pt; color: #57645D; }
        .pricing-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .pricing-table th { background: #0E6B5C10; padding: 10px 14px; text-align: left; font-size: 10pt; font-weight: 700; color: #0E6B5C; border-bottom: 2px solid #0E6B5C30; }
        .pricing-table td { padding: 10px 14px; font-size: 10pt; border-bottom: 1px solid #DCE3DE; }
        .highlight { background: #0E6B5C10; border-left: 3px solid #0E6B5C; padding: 16px 20px; border-radius: 0 8px 8px 0; margin: 16px 0; }
        .next-steps { display: flex; gap: 24px; margin-top: 24px; }
        .step { flex: 1; text-align: center; }
        .step .number { width: 36px; height: 36px; background: #0E6B5C; color: #fff; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14pt; margin-bottom: 8px; }
        .step h4 { font-size: 10pt; color: #1B2622; margin: 4px 0; }
        .step p { font-size: 8.5pt; color: #57645D; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #DCE3DE; font-size: 8pt; color: #A0AEA7; }
    </style>
</head>
<body>
    {{-- Cover Page --}}
    <div class="cover">
        <div>
            <h1>Lab Partnership<br>Proposal</h1>
            <div class="subtitle">AI-Powered Lab Result Interpretation</div>
            <div style="font-size: 24pt; margin-bottom: 16px; opacity: 0.9;">{{ $provider->name }}</div>
            <div class="meta">Prepared by HealthIntel</div>
            <div class="date">{{ now()->format('F Y') }}</div>
        </div>
    </div>

    {{-- Overview --}}
    <div class="page">
        <h2>Executive Summary</h2>
        <p>HealthIntel proposes a lab partnership with <strong>{{ $provider->name }}</strong> to provide AI-powered plain-language interpretations of lab results. This enhances patient experience, reduces support burden, and differentiates your lab from competitors.</p>

        <h3>Why Partner with HealthIntel?</h3>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="icon">🔬</div>
                <h4>Plain-Language Reports</h4>
                <p>Patients understand their results instantly, reducing calls asking "What does this mean?"</p>
            </div>
            <div class="feature-card">
                <div class="icon">🎨</div>
                <h4>White-Label Branding</h4>
                <p>Reports carry your logo, colors, and contact info — building your brand, not ours.</p>
            </div>
            <div class="feature-card">
                <div class="icon">📱</div>
                <h4>Multi-Channel Delivery</h4>
                <p>Email, WhatsApp, SMS, or printable PDF — deliver results where patients are.</p>
            </div>
            <div class="feature-card">
                <div class="icon">📊</div>
                <h4>Bulk Processing</h4>
                <p>Upload CSV files or integrate via REST API. Process hundreds of results in minutes.</p>
            </div>
            <div class="feature-card">
                <div class="icon">🇳🇬</div>
                <h4>Nigeria-Optimized</h4>
                <p>Reference ranges tailored to local populations. WhatsApp-first delivery for maximum reach.</p>
            </div>
            <div class="feature-card">
                <div class="icon">🔒</div>
                <h4>NDPA Compliant</h4>
                <p>Full data protection compliance. Signed DPA agreements. Encrypted data handling.</p>
            </div>
        </div>
    </div>

    {{-- Pricing --}}
    <div class="page">
        <h2>Proposed Plan: {{ ucfirst($partnership->plan_tier ?? 'Pilot') }}</h2>

        @php
            $rateNaira = ($partnership->rate_per_report ?? 0) / 100;
            $allowance = $partnership->monthly_allowance ?? 0;
            $pricingModel = $partnership->pricing_model ?? 'per_report';
        @endphp

        <table class="pricing-table">
            <tr><td width="40%"><strong>Plan Tier</strong></td><td>{{ ucfirst($partnership->plan_tier ?? 'Pilot') }}</td></tr>
            <tr><td><strong>Pricing Model</strong></td><td>{{ $pricingModel === 'per_report' ? 'Per Report' : ($pricingModel === 'volume_tier' ? 'Volume Tier' : 'Flat Monthly') }}</td></tr>
            @if($pricingModel === 'per_report')
                <tr><td><strong>Rate Per Report</strong></td><td>₦{{ number_format($rateNaira) }}</td></tr>
                <tr><td><strong>Monthly Allowance</strong></td><td>{{ $allowance > 0 ? number_format($allowance) . ' reports included' : 'No allowance' }}</td></tr>
            @elseif($pricingModel === 'volume_tier')
                <tr><td><strong>0–500 Reports</strong></td><td>₦300/report</td></tr>
                <tr><td><strong>501–2,000 Reports</strong></td><td>₦200/report</td></tr>
                <tr><td><strong>2,000+ Reports</strong></td><td>₦150/report</td></tr>
            @else
                <tr><td><strong>Monthly Fee</strong></td><td>₦{{ number_format($rateNaira) }}/month</td></tr>
            @endif
            <tr><td><strong>White-Label</strong></td><td>{{ $partnership->white_label ? '✓ Yes' : '✗ No (HealthIntel branded)' }}</td></tr>
            <tr><td><strong>Delivery Channels</strong></td><td>Email, WhatsApp, SMS, PDF</td></tr>
            @if($partnership->plan_tier === 'pilot')
                <tr><td><strong>Pilot Period</strong></td><td>First 3 months or 1,000 reports — FREE</td></tr>
            @endif
        </table>

        <div class="highlight">
            <strong>Pilot Offer:</strong> Start with a free 30-90 day pilot. No upfront cost. Full access to all features. Convert to paid only after you see results.
        </div>
    </div>

    {{-- Getting Started --}}
    <div class="page">
        <h2>Getting Started</h2>
        <div class="next-steps">
            <div class="step">
                <div class="number">1</div>
                <h4>Sign DPA</h4>
                <p>Data Processing Agreement for NDPA compliance</p>
            </div>
            <div class="step">
                <div class="number">2</div>
                <h4>Brand Setup</h4>
                <p>Upload logo, set colors, add contact info</p>
            </div>
            <div class="step">
                <div class="number">3</div>
                <h4>Pilot Launch</h4>
                <p>Start with CSV upload or single-entry. Train staff.</p>
            </div>
            <div class="step">
                <div class="number">4</div>
                <h4>Review & Scale</h4>
                <p>After 30-90 days, review metrics. Upgrade to paid.</p>
            </div>
        </div>

        <h3 style="margin-top: 40px;">Contact</h3>
        <p>For questions or to begin onboarding, contact your HealthIntel representative:</p>
        <div class="highlight">
            <strong>Email:</strong> partnerships@healthintel.app<br>
            <strong>Website:</strong> healthintel.app<br>
            <strong>Partner Portal:</strong> Access your dashboard at healthintel.app/partner
        </div>

        <div class="footer">
            <p>This proposal is valid for 30 days from {{ now()->format('F j, Y') }}. Pricing and terms subject to final agreement.</p>
            <p>HealthIntel — Your health, decoded.</p>
        </div>
    </div>
</body>
</html>