@extends('layouts.frontend')

@section('title', 'Clinical Validation — HealthIntel AI Accuracy Benchmark')
@section('description', 'HealthIntel achieves high accuracy on clinical lab interpretation benchmarks. See our transparent AI validation methodology and results, compared against standard medical reference data.')

@section('content')
<section class="section">
    <div class="wrap">
        <div class="section-header center anim-fade-up">
            <span class="eyebrow">Clinical Validation</span>
            <h1>Benchmarked. Verified. Transparent.</h1>
            <p>Every AI interpretation is tested against a rigorous clinical dataset covering multiple medical specialties. Here's how we measure accuracy — and why you can trust our results.</p>
        </div>
    </div>
</section>

{{-- Method-specific content will be populated by React when benchmarks exist --}}
<section class="section" style="padding-top:0">
    <div class="wrap">
        @if($latest)
        <div class="wrap">
            <div class="section-header center" style="margin-bottom:24px">
                <h2 style="font-size:1.8rem">{{ $latest->accuracy_formatted }} Accuracy</h2>
                <p>{{ $latest->correct_answers }} of {{ $latest->total_questions }} questions correct across {{ count($latest->specialty_breakdown ?? []) }} clinical specialties</p>
            </div>

            {{-- Score Cards --}}
            <div class="features-grid stagger-children" style="margin-bottom:40px">
                <div class="feature-card">
                    <div class="feature-dot" aria-hidden="true"></div>
                    <h3>{{ $latest->accuracy_formatted }}</h3>
                    <p>Overall Accuracy</p>
                </div>
                <div class="feature-card">
                    <div class="feature-dot" aria-hidden="true"></div>
                    <h3>{{ $latest->total_questions }}</h3>
                    <p>Questions Tested</p>
                </div>
                <div class="feature-card">
                    <div class="feature-dot" aria-hidden="true"></div>
                    <h3>{{ $latest->correct_answers }}/{{ $latest->total_questions }}</h3>
                    <p>Correct Answers</p>
                </div>
                <div class="feature-card">
                    <div class="feature-dot" aria-hidden="true"></div>
                    <h3>{{ number_format($latest->avg_response_time_ms ?? 0, 0) }}ms</h3>
                    <p>Avg Response Time</p>
                </div>
            </div>

            {{-- Specialty Breakdown --}}
            @if($latest->specialty_breakdown)
            <div class="section-header center" style="margin-bottom:24px">
                <h2 style="font-size:1.4rem">By Clinical Specialty</h2>
            </div>
            <div class="credits-table" style="max-width: 700px; margin: 0 auto 40px; border: 1px solid var(--line); border-radius: var(--radius-md); padding: 20px 28px;">
                @foreach($latest->specialty_breakdown as $specialty => $data)
                    @php $pct = $data['total'] > 0 ? round(($data['correct'] / $data['total']) * 100) : 0; @endphp
                    <div class="credits-table-row">
                        <span>{{ $specialty }}</span>
                        <span class="cost" style="color: {{ $pct >= 90 ? '#0E6B5C' : ($pct >= 70 ? '#B9812E' : '#A8432F') }}">
                            {{ $data['correct'] }}/{{ $data['total'] }} ({{ $pct }}%)
                        </span>
                    </div>
                @endforeach
            </div>
            @endif

            {{-- Historical Runs --}}
            @if($benchmarks->count() > 1)
            <div class="section-header center" style="margin-bottom:24px">
                <h2 style="font-size:1.4rem">Previous Benchmark Runs</h2>
            </div>
            <div class="credits-table" style="max-width: 700px; margin: 0 auto 40px; border: 1px solid var(--line); border-radius: var(--radius-md); padding: 20px 28px;">
                <div class="credits-table-row" style="font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:0.7rem">
                    <span>Date</span><span>Questions</span><span>Accuracy</span>
                </div>
                @foreach($benchmarks as $b)
                <div class="credits-table-row">
                    <span>{{ $b->completed_at?->format('M j, Y') }}</span>
                    <span>{{ $b->total_questions }}</span>
                    <span class="cost">{{ $b->accuracy_formatted }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @else
        <div class="wrap">
            <div class="section-header center" style="margin-bottom:32px">
                <h2 style="font-size:1.5rem">Benchmark results are loading...</h2>
                <p>Run <code style="background:var(--primary-light);padding:2px 8px;border-radius:4px;font-size:0.85rem">php artisan benchmark:clinical</code> from your terminal to generate benchmark results.</p>
            </div>
        </div>
        @endif
    </div>
</section>

{{-- Methodology --}}
<section class="section" style="padding-top:0">
    <div class="wrap">
        <div class="section-header anim-fade-up">
            <span class="eyebrow">Our methodology</span>
            <h2>How we validate our AI</h2>
        </div>

        <div class="steps-grid stagger-children">
            <div class="step-card">
                <div class="step-number mono">01</div>
                <h3>Clinical dataset</h3>
                <p>50+ clinical vignettes spanning Hematology, Endocrinology, Cardiology, Nephrology, Hepatology, Obstetrics, and Critical Care — each with a verified correct answer reviewed by licensed clinicians.</p>
            </div>
            <div class="step-card">
                <div class="step-number mono">02</div>
                <h3>LLM evaluation</h3>
                <p>Each question is sent to our AI interpretation engine with near-deterministic settings to minimize variance. Responses are scored against verified ground-truth answers.</p>
            </div>
            <div class="step-card">
                <div class="step-number mono">03</div>
                <h3>Reference range verification</h3>
                <p>Beyond AI accuracy, every lab value is checked against our database of verified reference ranges — age-adjusted, sex-specific, and pregnancy-aware. This dual-layer validation is unique to HealthIntel.</p>
            </div>
        </div>
    </div>
</section>

{{-- Comparison --}}
<section class="section" style="padding-top:0">
    <div class="wrap">
        <div class="section-header center anim-fade-up">
            <h2>AI + Verified Data > AI alone</h2>
            <p>Many AI health tools rely solely on large language models. HealthIntel pairs AI with a structured reference range database — giving you interpretations that are both intelligent and medically grounded.</p>
        </div>

        <div class="credits-wrapper anim-scale-in" style="margin-top:32px">
            <div class="credits-grid">
                <div class="credits-copy">
                    <h2>Two layers of safety</h2>
                    <p>Every result passes through two independent checks before reaching you:</p>
                    <div class="credit-tags" style="flex-direction:column;gap:12px">
                        <span class="credit-tag" style="text-align:left;font-family:'Inter',sans-serif;font-size:0.88rem;width:100%">
                            🔍 <strong>Step 1:</strong> Reference range engine flags values against verified medical ranges (age, sex, pregnancy adjusted)
                        </span>
                        <span class="credit-tag" style="text-align:left;font-family:'Inter',sans-serif;font-size:0.88rem;width:100%">
                            🤖 <strong>Step 2:</strong> Our AI provides plain-language interpretation, guided by strict medical guardrails
                        </span>
                    </div>
                </div>
                <div class="credits-table">
                    <div class="credits-table-row"><span>Reference ranges in database</span><span class="cost">2,500+</span></div>
                    <div class="credits-table-row"><span>Clinical specialties covered</span><span class="cost">8+</span></div>
                    <div class="credits-table-row"><span>AI model temperature</span><span class="cost">0.3 (conservative)</span></div>
                    <div class="credits-table-row"><span>Medical disclaimer</span><span class="cost">Always shown</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Disclaimers --}}
<section class="section" id="trust">
    <div class="wrap">
        <div class="trust-grid">
            <div class="trust-copy anim-slide-left">
                <span class="eyebrow">Important</span>
                <h2>Benchmarks don't replace doctors.</h2>
                <p>Our benchmark measures AI knowledge of lab interpretation — it does not measure diagnostic ability. HealthIntel provides information, not medical advice.</p>
                <p>Always discuss your lab results with a licensed healthcare professional. Our AI helps you understand — your doctor helps you decide.</p>
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

{{-- CTA --}}
<section class="cta-section">
    <div class="wrap stagger-children" style="text-align: center;">
        <h2>Experience accurate, transparent lab interpretations.</h2>
        <p>Free credits on signup — no subscription required.</p>
        <a href="/register" class="btn btn-primary btn-lg" style="margin-top: 8px;">Try it free</a>
    </div>
</section>
@endsection