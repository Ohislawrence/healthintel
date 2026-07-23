@extends('layouts.frontend')

@section('title', 'HealthIntel — Understand your lab results, in plain language')
@section('description', 'Add a result and get an interpretation checked against real, verified reference ranges — not a black-box guess. Check symptoms, find a doctor near you, and compare health insurance, all in one place.')

@php
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => 'HealthIntel',
    'url' => url('/'),
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => 'https://healthintel.app/directory?search={search_term_string}',
        'query-input' => 'required name=search_term_string',
    ],
];
@endphp

@section('structured_data')
<script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}</script>
@endsection

@section('content')
{{-- HERO --}}
{{-- HERO --}}
<section class="hero">
    <div class="wrap">
        <div class="hero-grid">
            <!-- Left: Copy & CTAs -->
            <div class="hero-content stagger-children">
                <div class="hero-badge">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <span>Trusted by thousands across Nigeria</span>
                </div>
                
                <h1>Finally understand your <span class="text-gradient">lab results.</span></h1>
                
                <p class="lead">
                    Stop staring at confusing medical jargon. Enter your values or upload your report, and we’ll explain exactly what they mean for <em>you</em>—checked against verified reference ranges, not black-box AI guesses.
                </p>
                
                <div class="hero-ctas">
                    <a href="/register" class="btn btn-primary btn-lg">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        Upload or enter results
                    </a>
                    <a href="#how-it-works" class="btn btn-ghost btn-lg">See how it works</a>
                </div>
                
                <div class="trust-cluster">
                    <div class="trust-note">
                        <span class="trust-dot"></span>
                        First 5 interpretations are free
                    </div>
                    <div class="trust-note">
                        <span class="trust-dot" style="background: var(--amber); box-shadow: 0 0 0 3px var(--amber-soft);"></span>
                        No credit card required
                    </div>
                </div>
            </div>

            <!-- Right: The "Transformation" Visual -->
            <div class="hero-visual anim-scale-in" style="animation-delay: 300ms;">
                <!-- Background: The "Confusing" Old Lab Report -->
                <div class="lab-report-bg" aria-hidden="true">
                    <div class="report-line" style="width: 60%"></div>
                    <div class="report-line" style="width: 80%"></div>
                    <div class="report-line" style="width: 45%"></div>
                    <div class="report-line" style="width: 70%"></div>
                    <div class="report-line" style="width: 50%"></div>
                    <div class="report-blur-overlay"></div>
                </div>

                <!-- Foreground: The Clear HealthIntel Insight Card -->
                <div class="insight-card">
                    <div class="insight-header">
                        <div>
                            <span class="insight-label">Fasting Glucose</span>
                            <span class="insight-date">Today, 8:30 AM</span>
                        </div>
                        <span class="status-chip warn">Slightly Elevated</span>
                    </div>

                    <div class="insight-value-row">
                        <span class="insight-value mono">118 <span class="unit">mg/dL</span></span>
                        <span class="insight-range mono">Ref: 70 – 99</span>
                    </div>

                    <div class="range-track-wrapper">
                        <div class="range-track">
                            <div class="range-marker warn" style="left: 78%" aria-hidden="true"></div>
                        </div>
                        <div class="range-labels">
                            <span>Low</span>
                            <span>Optimal</span>
                            <span>High</span>
                        </div>
                    </div>

                    <div class="plain-language-box">
                        <div class="plain-language-icon">💡</div>
                        <div class="plain-language-text">
                            <strong>What this means:</strong> This is slightly above the typical range. It’s not an emergency, but it’s a good signal to discuss dietary adjustments or a follow-up test with your doctor.
                        </div>
                    </div>
                    
                    <div class="insight-footer">
                        <span>Verified against standard medical ranges</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- HOW IT WORKS --}}
<section class="section" id="how-it-works">
    <div class="wrap">
        <div class="section-header center anim-fade-up">
            <span class="eyebrow">How it works</span>
            <h2>Three steps, every time.</h2>
            <p>No black boxes. Every interpretation is built the same transparent way.</p>
        </div>
        
        <!-- Staggered grid entrance -->
        <div class="steps-grid stagger-children">
            <div class="step-card">
                <div class="step-number mono">01</div>
                <h3>Add a result</h3>
                <p>Enter values from your lab report, or describe symptoms you're feeling — takes under two minutes.</p>
            </div>
            <div class="step-card">
                <div class="step-number mono">02</div>
                <h3>We check it against real ranges</h3>
                <p>Every value is compared to verified reference data for your age and sex — never guessed by AI on the spot.</p>
            </div>
            <div class="step-card">
                <div class="step-number mono">03</div>
                <h3>Get plain-language guidance</h3>
                <p>Understand what it means, track it over time, and see who to talk to next if it matters.</p>
            </div>
        </div>
    </div>
</section>

{{-- FEATURES --}}
<section class="features-wrapper" id="features">
    <div class="wrap section" style="padding-bottom:60px">
        <div class="section-header center anim-fade-up">
            <span class="eyebrow">What's inside</span>
            <h2>One app for the whole question, not just the result.</h2>
        </div>
    </div>
    <div class="wrap" style="padding-bottom:80px">
        <!-- Staggered grid entrance -->
        <div class="features-grid stagger-children">
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Lab result interpretation</h3>
                <p>Upload or enter values, see what's in range, what's not, and why — with trends over time.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Symptom checker</h3>
                <p>Answer a few structured questions and see which tests are commonly worth discussing with a doctor.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Find care nearby</h3>
                <p>A directory of hospitals, doctors, and labs, filterable by specialty and distance.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Compare insurance</h3>
                <p>See HMO plans side by side and get connected — no back-and-forth phone calls.</p>
            </div>
        </div>
    </div>
</section>

{{-- TRUST --}}
<section class="section" id="trust">
    <div class="wrap">
        <div class="trust-grid">
            <div class="trust-copy anim-slide-left">
                <span class="eyebrow">Why trust it</span>
                <h2>We explain. We don't diagnose.</h2>
                <p>Every reference range in HealthIntel comes from verified medical data, reviewed with a licensed medical advisor — never invented on the fly by an AI model.</p>
                <p>The app tells you what a result can mean and when it's worth a conversation with a doctor. It never replaces one.</p>
            </div>
            <div class="trust-disclaimer anim-scale-in" style="animation-delay: 200ms;">
                <p class="border-accent">
                    "HEALTHINTEL PROVIDES GENERAL HEALTH INFORMATION.<br>
                    IT IS NOT A DIAGNOSIS AND DOES NOT REPLACE<br>
                    ADVICE FROM A LICENSED HEALTHCARE PROFESSIONAL.<br>
                    ALWAYS CONSULT A DOCTOR ABOUT YOUR RESULTS."
                </p>
            </div>
        </div>
    </div>
</section>

{{-- CREDITS --}}
<section class="section" id="credits">
    <div class="wrap">
        <div class="credits-wrapper anim-fade-up">
            <!-- Staggered grid entrance for copy and table -->
            <div class="credits-grid stagger-children">
                <div class="credits-copy">
                    <h2>Pay only for what you use.</h2>
                    <p>No subscriptions. Buy credits when you need them, and spend them on the things that matter — browsing and finding care is always free.</p>
                    <div class="credit-tags">
                        <span class="credit-tag">5 free credits on signup</span>
                        <span class="credit-tag">Top up with Paystack</span>
                    </div>
                </div>
                <div class="credits-table">
                    <div class="credits-table-row"><span>Lab result interpretation</span><span class="cost">2-3 credits</span></div>
                    <div class="credits-table-row"><span>Symptom check</span><span class="cost">1 credit</span></div>
                    <div class="credits-table-row"><span>View your trends</span><span class="cost">Free</span></div>
                    <div class="credits-table-row"><span>Find a doctor or hospital</span><span class="cost">Free</span></div>
                    <div class="credits-table-row"><span>Compare insurance plans</span><span class="cost">Free</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- FINAL CTA --}}
<section class="cta-section">
    <!-- Staggered entrance for heading, paragraph, and button -->
    <div class="wrap stagger-children" style="text-align: center;">
        <h2>Stop guessing what your results mean.</h2>
        <p>Create a free account and use your first 5 credits on us.</p>
        <a href="/register" class="btn btn-primary btn-lg" style="margin-top: 8px;">Create free account</a>
    </div>
</section>
@endsection