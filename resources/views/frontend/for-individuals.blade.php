@extends('layouts.frontend')

@section('title', 'For Individuals — Personal Lab Test Interpretation | HealthIntel')
@section('description', 'Upload your lab results and get plain-language explanations. Track trends over time, check symptoms, and find healthcare providers near you — all in one place.')

@section('content')
<section class="section">
    <div class="wrap">
        <div class="section-header center anim-fade-up">
            <span class="eyebrow">For Individuals</span>
            <h1>Your lab results, finally explained.</h1>
            <p>Stop Googling medical terms. Upload your report or type in your values — we'll tell you what's normal, what's not, and what to do next.</p>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="wrap">
        <div class="features-grid stagger-children">
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Upload your lab report</h3>
                <p>Take a photo or upload a PDF. Our AI extracts your values and explains every result in plain language — no medical degree needed.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Track your trends</h3>
                <p>See how your cholesterol, blood sugar, and other markers change over time. Spot patterns before they become problems.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Check your symptoms</h3>
                <p>Not sure what test you need? Answer a few questions and we'll suggest relevant lab panels — so you walk into the lab prepared.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Get your health score</h3>
                <p>A personalized score based on your lab history, BMI, lifestyle, and medications. Watch it improve as you take action.</p>
            </div>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="wrap">
        <div class="section-header center anim-fade-up">
            <span class="eyebrow">More tools</span>
            <h2>Everything you need to stay on top of your health</h2>
        </div>
        <div class="credits-table" style="max-width: 600px; margin: 0 auto; border: 1px solid var(--line); border-radius: var(--radius-md); padding: 20px 28px;">
            <div class="credits-table-row"><span>BMI Calculator</span><span class="cost">Free</span></div>
            <div class="credits-table-row"><span>Blood Pressure Log</span><span class="cost">Free</span></div>
            <div class="credits-table-row"><span>Period Tracker</span><span class="cost">Free</span></div>
            <div class="credits-table-row"><span>Due Date Calculator</span><span class="cost">Free</span></div>
            <div class="credits-table-row"><span>Immunization Tracker</span><span class="cost">Free</span></div>
            <div class="credits-table-row"><span>Appointment Reminders</span><span class="cost">Free</span></div>
            <div class="credits-table-row"><span>Food & Symptom Diary</span><span class="cost">Free</span></div>
            <div class="credits-table-row"><span>Water Intake Tracker</span><span class="cost">Free</span></div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="wrap stagger-children" style="text-align: center;">
        <h2>First interpretation is free.</h2>
        <p>No subscription. No credit card. Just upload and understand.</p>
        <a href="/register" class="btn btn-primary btn-lg" style="margin-top: 8px;">Get started free</a>
    </div>
</section>
@endsection