@extends('layouts.frontend')

@section('title', 'For Labs — AI-Powered Lab Result Interpretation API | HealthIntel')
@section('description', 'Connect your LIMS to HealthIntel. Get AI-powered lab result interpretations, HL7 integration, bulk processing, SMS/email delivery to patients, and population analytics.')

@section('content')
<section class="section">
    <div class="wrap">
        <div class="section-header center anim-fade-up">
            <span class="eyebrow">For Labs</span>
            <h1>Add AI interpretation to every report you produce.</h1>
            <p>Your lab runs the tests. We add plain-language explanations — automatically. Integrate via API or HL7, process in bulk, and deliver results to patients via SMS or email.</p>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="wrap">
        <div class="features-grid stagger-children">
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>HL7 / API integration</h3>
                <p>Send lab results in standard HL7 format or via our REST API. Get AI interpretations back in seconds. Works with any LIMS.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Bulk interpretation</h3>
                <p>Process hundreds of results at once. Upload a batch file or send via API. Get interpretations for every patient in one go.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Automated patient delivery</h3>
                <p>Send interpreted results directly to patients via SMS (powered by Termii) or email. Set delivery preferences per batch.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Dual AI + clinician review</h3>
                <p>AI drafts the interpretation. Your lab scientists review and approve before delivery. Full audit trail on every report.</p>
            </div>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="wrap">
        <div class="section-header center anim-fade-up">
            <span class="eyebrow">Analytics & ROI</span>
            <h2>Know what your data is telling you</h2>
            <p>Population health analytics, delivery success rates, and ROI tracking — all from one partner dashboard.</p>
        </div>
        <div class="features-grid stagger-children">
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Population analytics</h3>
                <p>See aggregate trends across all your patients — which biomarkers are most frequently flagged, by age group, sex, and location.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Delivery health monitoring</h3>
                <p>Track SMS and email delivery rates, bounce rates, and patient engagement. Know when reports are opened.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>ROI metrics</h3>
                <p>See exactly how much value the interpretation service adds — patient retention improvements, report turnaround time reductions, and revenue impact.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Flexible billing</h3>
                <p>Pay per interpretation or set up monthly invoicing. No upfront fees. Transparent pricing based on volume.</p>
            </div>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="wrap">
        <div class="section-header center anim-fade-up">
            <span class="eyebrow">Integration</span>
            <h2>Connect in minutes, not weeks</h2>
        </div>
        <div class="steps-grid stagger-children">
            <div class="step-card">
                <div class="step-number mono">01</div>
                <h3>Get your access code</h3>
                <p>Sign up as a lab partner. We'll provide an access code and API credentials for your lab.</p>
            </div>
            <div class="step-card">
                <div class="step-number mono">02</div>
                <h3>Send us results</h3>
                <p>POST your lab results via REST API (JSON) or HL7 format. Our parser handles all common lab result structures.</p>
            </div>
            <div class="step-card">
                <div class="step-number mono">03</div>
                <h3>Get interpretations</h3>
                <p>AI interpretations are returned in seconds. Your team reviews and approves, then delivers to patients automatically.</p>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="wrap stagger-children" style="text-align: center;">
        <h2>Ready to add AI to every lab report?</h2>
        <p>Join the labs already delivering smarter reports with HealthIntel.</p>
        <a href="/partnerships" class="btn btn-primary btn-lg" style="margin-top: 8px;">Become a lab partner</a>
    </div>
</section>
@endsection