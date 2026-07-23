@extends('layouts.frontend')

@section('title', 'Features — HealthIntel | Lab Results, Symptom Checker, Provider Directory & More')
@section('description', 'HealthIntel offers AI lab result interpretation, symptom checker, provider directory, insurance comparison, health calculators, appointment tracking, and a health score — all in one platform.')

@section('content')
<section class="section">
    <div class="wrap">
        <div class="section-header center">
            <span class="eyebrow anim-fade-up">Everything you can do</span>
            <h1 class="anim-fade-up d1">One platform. All your health questions, answered.</h1>
        </div>

        <div class="features-grid" style="margin-bottom:60px">
            <div class="feature-card anim-fade-up d1">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Lab Result Interpretation</h3>
                <p>Upload a PDF lab report or enter values manually. Our AI explains every result in plain language, flags what's out of range, and gives you trends over time.</p>
            </div>
            <div class="feature-card anim-fade-up d2">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Symptom Checker</h3>
                <p>Describe what you're feeling. We suggest relevant lab tests and connect you with providers who can run them — so you walk in prepared.</p>
            </div>
            <div class="feature-card anim-fade-up d3">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Provider Directory</h3>
                <p>Search hospitals, clinics, labs, pharmacies, and specialists across Nigeria. Filter by specialty, location, and insurance accepted. Verified listings with contact details.</p>
            </div>
            <div class="feature-card anim-fade-up d4">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Insurance Comparison</h3>
                <p>Compare HMO plans side by side — coverage, premiums, network hospitals. Request a call-back from providers directly through the platform.</p>
            </div>
            <div class="feature-card anim-fade-up d5">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Health Calculators</h3>
                <p>BMI, BMR, waist-to-hip ratio, due date calculator, period tracker, and immunization schedule — evidence-based tools you can trust.</p>
            </div>
            <div class="feature-card anim-fade-up d6">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Appointment Tracker</h3>
                <p>Log doctor visits, set reminders, and keep notes. Never miss a follow-up or lose track of what your doctor said.</p>
            </div>
            <div class="feature-card anim-fade-up d7">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Health Score</h3>
                <p>Get a personalized health score based on your metrics, lab history, and lifestyle inputs. Watch it improve as you take action.</p>
            </div>
            <div class="feature-card anim-fade-up d8">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Pay As You Go</h3>
                <p>No subscriptions. Buy credits when you need them. Browsing providers, calculators, and appointment tracking are always free.</p>
            </div>
        </div>

        <div class="text-center anim-fade-up d3">
            <a href="/register" class="btn btn-primary btn-lg">Try everything free</a>
        </div>
    </div>
</section>
@endsection