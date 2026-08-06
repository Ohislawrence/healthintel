@extends('layouts.frontend')

@section('title', 'For Clinicians — AI-Assisted Lab Result Review | HealthIntel')
@section('description', 'Speed up lab result review with AI-assisted interpretation and dual-review workflow. Override, annotate, and generate patient-friendly reports instantly.')

@section('content')
<section class="section">
    <div class="wrap">
        <div class="section-header center anim-fade-up">
            <span class="eyebrow">For Clinicians</span>
            <h1>Review lab results faster. Spend more time with patients.</h1>
            <p>AI drafts the interpretation. You review, approve, override, and annotate. The patient gets a clear, clinician-verified report — in seconds instead of hours.</p>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="wrap">
        <div class="features-grid stagger-children">
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Dual interpretation workflow</h3>
                <p>AI generates a first-pass interpretation. You review and finalize with a single click. Every report shows both the AI draft and the clinician-reviewed version.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Override & annotate</h3>
                <p>Disagree with the AI? Override any interpretation and add your clinical notes. Your expertise stays front and center.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Panel narrative templates</h3>
                <p>Pre-built interpretation templates for common panels (CBC, LFT, RFT, lipid panel, thyroid). Customize them to match your practice voice.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Batch PDF downloads</h3>
                <p>Download patient-friendly report PDFs for multiple patients at once. Print, email, or share via WhatsApp — all from one dashboard.</p>
            </div>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="wrap">
        <div class="section-header center anim-fade-up">
            <span class="eyebrow">Clinical safety</span>
            <h2>AI that respects clinical judgment</h2>
            <p>Our system is designed as a clinical decision support tool, not a replacement. Every interpretation is flagged, referenced, and ready for your review.</p>
        </div>
        <div class="credits-wrapper anim-scale-in">
            <div class="credits-grid">
                <div class="credits-copy">
                    <h2>Built with guardrails</h2>
                    <div class="credit-tags" style="flex-direction:column;gap:12px">
                        <span class="credit-tag" style="text-align:left;font-family:'Inter',sans-serif;font-size:0.88rem;width:100%">
                            🛡️ <strong>Never diagnoses:</strong> AI uses "may indicate" language — never claims a diagnosis
                        </span>
                        <span class="credit-tag" style="text-align:left;font-family:'Inter',sans-serif;font-size:0.88rem;width:100%">
                            📋 <strong>Reference-range backed:</strong> Every flag is checked against verified age/sex-adjusted ranges
                        </span>
                        <span class="credit-tag" style="text-align:left;font-family:'Inter',sans-serif;font-size:0.88rem;width:100%">
                            🔄 <strong>Two-version history:</strong> Full audit trail of AI vs clinician versions
                        </span>
                    </div>
                </div>
                <div class="credits-table">
                    <div class="credits-table-row"><span>Reference ranges verified</span><span class="cost">2,500+</span></div>
                    <div class="credits-table-row"><span>Panel templates</span><span class="cost">Pre-built</span></div>
                    <div class="credits-table-row"><span>Medication-aware</span><span class="cost">Yes</span></div>
                    <div class="credits-table-row"><span>Pregnancy-adjusted</span><span class="cost">Yes</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="wrap stagger-children" style="text-align: center;">
        <h2>Ready to speed up your lab review workflow?</h2>
        <p>Join labs and clinics across Nigeria already using HealthIntel.</p>
        <a href="/partnerships" class="btn btn-primary btn-lg" style="margin-top: 8px;">Become a partner</a>
    </div>
</section>
@endsection