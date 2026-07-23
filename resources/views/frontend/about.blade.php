@extends('layouts.frontend')

@section('title', 'About HealthIntel — AI-Powered Lab Result Interpretation in Nigeria')
@section('description', 'HealthIntel helps Nigerians understand their medical lab results using artificial intelligence. Upload PDFs or enter values for instant plain-language explanations.')

@section('content')
<section class="section">
    <div class="wrap">
        <div class="section-header center">
            <span class="eyebrow anim-fade-up">About us</span>
            <h1 class="anim-fade-up d1">About HealthIntel</h1>
            <p class="anim-fade-up d2">We believe everyone deserves to understand their medical test results — without needing a medical degree.</p>
        </div>

        <div class="page-section" style="margin:0 auto">
            <h2 class="anim-fade-up">Our Mission</h2>
            <p class="anim-fade-up d1">
                Medical lab reports are full of numbers, abbreviations, and technical jargon that most people can't interpret.
                HealthIntel bridges that gap by using artificial intelligence to translate complex lab results into clear,
                plain-language explanations that anyone can understand.
            </p>

            <h2 class="anim-fade-up d2">Why HealthIntel?</h2>
            <div class="value-grid">
                <div class="value-item anim-fade-up d1">
                    <div class="feature-dot" aria-hidden="true"></div>
                    <h3>Built for Nigeria</h3>
                    <p>Reference ranges adapted to Nigerian demographics, with provider directory for local hospitals, labs, and HMOs.</p>
                </div>
                <div class="value-item anim-fade-up d2">
                    <div class="feature-dot" aria-hidden="true"></div>
                    <h3>Instant Results</h3>
                    <p>Upload a PDF or enter values — get your plain-language explanation in under 60 seconds.</p>
                </div>
                <div class="value-item anim-fade-up d3">
                    <div class="feature-dot" aria-hidden="true"></div>
                    <h3>Privacy First</h3>
                    <p>Your health data is encrypted and never shared. We comply with NDPR regulations.</p>
                </div>
                <div class="value-item anim-fade-up d4">
                    <div class="feature-dot" aria-hidden="true"></div>
                    <h3>Free to Start</h3>
                    <p>Get 3 free credits when you sign up. Each lab interpretation costs just 2-3 credits.</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection