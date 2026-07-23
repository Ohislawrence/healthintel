@extends('layouts.frontend')

@section('title', 'How HealthIntel Works — AI Lab Result Interpretation')
@section('description', 'Learn how HealthIntel reads your lab reports and explains them in plain language. Three simple steps: sign up, upload, get results.')

@section('content')
<section class="section">
    <div class="wrap">
        <div class="section-header center">
            <span class="eyebrow anim-fade-up">How it works</span>
            <h1 class="anim-fade-up d1">Three steps, every time.</h1>
            <p class="anim-fade-up d2">No black boxes. Every interpretation is built the same transparent way.</p>
        </div>
        <div class="steps-grid">
            <div class="step-card anim-fade-up d1">
                <div class="step-number mono">01</div>
                <h3>Add a result</h3>
                <p>Enter values from your lab report, or describe symptoms you're feeling — takes under two minutes.</p>
            </div>
            <div class="step-card anim-fade-up d2">
                <div class="step-number mono">02</div>
                <h3>We check it against real ranges</h3>
                <p>Every value is compared to verified reference data for your age and sex — never guessed by AI on the spot.</p>
            </div>
            <div class="step-card anim-fade-up d3">
                <div class="step-number mono">03</div>
                <h3>Get plain-language guidance</h3>
                <p>Understand what it means, track it over time, and see who to talk to next if it matters.</p>
            </div>
        </div>

        <div class="text-center mt-12 anim-fade-up d3">
            <a href="/register" class="btn btn-primary btn-lg">Create free account</a>
        </div>
    </div>
</section>
@endsection