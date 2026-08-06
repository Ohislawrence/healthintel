@extends('layouts.frontend')

@section('title', 'For Insurance — HMO Plan Comparison Platform | HealthIntel')
@section('description', 'Compare HMO plans, see provider networks, and connect with insurers. HealthIntel helps Nigerians compare and choose health insurance plans.')

@section('content')
<section class="section">
    <div class="wrap">
        <div class="section-header center anim-fade-up">
            <span class="eyebrow">For Insurance</span>
            <h1>Help Nigerians find and compare the right health plan.</h1>
            <p>Our platform connects patients with HMO plans that match their needs. Users compare coverage, premiums, and provider networks — then request a call-back from your team.</p>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="wrap">
        <div class="features-grid stagger-children">
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>HMO plan comparison</h3>
                <p>Users browse and compare your plans side by side — coverage details, premium rates, network hospitals, and more.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Direct enquiry flow</h3>
                <p>Interested users submit an enquiry directly through the platform. Your team receives qualified leads — no cold outreach needed.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Provider network visibility</h3>
                <p>Show which hospitals, clinics, and labs accept your plans. Patients can search by location and specialty to find in-network providers.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Verified provider directory</h3>
                <p>Your plans appear alongside our verified directory of Nigerian healthcare providers — building trust and context for potential enrollees.</p>
            </div>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="wrap">
        <div class="section-header center anim-fade-up">
            <span class="eyebrow">How it works</span>
            <h2>List your plans, get qualified leads</h2>
        </div>
        <div class="steps-grid stagger-children">
            <div class="step-card">
                <div class="step-number mono">01</div>
                <h3>List your HMO plans</h3>
                <p>Add your plans with coverage details, premiums, and network information. We'll feature them in our comparison tool.</p>
            </div>
            <div class="step-card">
                <div class="step-number mono">02</div>
                <h3>Patients compare & enquire</h3>
                <p>Users search, filter, and compare plans. When they find a match, they submit an enquiry directly to your team.</p>
            </div>
            <div class="step-card">
                <div class="step-number mono">03</div>
                <h3>You follow up</h3>
                <p>Receive qualified, intent-driven leads. No cold calls. No wasted time. Just people actively looking for coverage.</p>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="wrap stagger-children" style="text-align: center;">
        <h2>Want your HMO listed on HealthIntel?</h2>
        <p>Reach thousands of Nigerians actively searching for health coverage.</p>
        <a href="/partnerships" class="btn btn-primary btn-lg" style="margin-top: 8px;">List your plans</a>
    </div>
</section>
@endsection