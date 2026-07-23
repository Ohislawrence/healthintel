@extends('layouts.frontend')

@section('title', 'Privacy Policy — HealthIntel')
@section('description', 'HealthIntel privacy policy. We take your health data privacy seriously.')
@section('robots', 'noindex, follow')

@section('content')
<section class="section">
    <div class="wrap">
        <div class="section-header center">
            <span class="eyebrow">Legal</span>
            <h1>Privacy Policy</h1>
            <p>Last updated: {{ date('F Y') }}</p>
        </div>

        <div class="page-section" style="margin:0 auto">
            <h2>1. Information We Collect</h2>
            <p>We collect your name, email address, phone number (optional), date of birth, sex, and the lab results you upload or enter. We also collect payment information processed securely through Paystack.</p>

            <h2>2. How We Use Your Data</h2>
            <p>Your data is used to generate AI-powered interpretations of your lab results and to personalize reference ranges. We do not sell your data to third parties.</p>

            <h2>3. Data Security</h2>
            <p>All data is encrypted in transit and at rest. We comply with the Nigeria Data Protection Regulation (NDPR).</p>

            <h2>4. Your Rights</h2>
            <p>You may request a copy of your data or ask us to delete it at any time by contacting us.</p>
        </div>
    </div>
</section>
@endsection