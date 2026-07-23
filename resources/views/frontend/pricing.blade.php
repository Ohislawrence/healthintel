@extends('layouts.frontend')

@section('title', 'Pricing — HealthIntel Credit Packages')
@section('description', 'Affordable credit-based pricing for AI lab result interpretation. Get 3 free credits on signup. Pay only when you need to interpret a lab report.')

@section('content')
<section class="section">
    <div class="wrap">
        <div class="section-header center">
            <span class="eyebrow anim-fade-up">Pricing</span>
            <h1 class="anim-fade-up d1">Simple, Transparent Pricing</h1>
            <p class="anim-fade-up d2">Get 3 free credits when you sign up. Each lab interpretation costs 2-3 credits. Buy more as needed — no subscriptions.</p>
        </div>

        @php
            $packages = \App\Models\CreditPackage::where('is_active', true)->orderBy('sort_order')->get();
        @endphp

        <div class="pricing-grid">
            @forelse ($packages as $i => $pkg)
            <div class="pricing-card anim-fade-up d{{ $i + 1 }}">
                <p class="pricing-amount">{{ $pkg->credits }}</p>
                <p class="pricing-label">Credits</p>
                <p class="pricing-price">₦{{ number_format($pkg->price_kobo / 100) }}</p>
                <a href="/register" class="btn btn-primary">Get started</a>
            </div>
            @empty
            <div class="pricing-card" style="grid-column:1/-1">
                <p class="pricing-label">No packages available yet. Please check back soon.</p>
            </div>
            @endforelse
        </div>

        <div class="text-center anim-fade-up d2" style="color:var(--text-muted);font-size:0.9rem">
            <p>📋 PDF interpretation: 3 credits &nbsp;|&nbsp; 📊 Panel-based: 2 credits</p>
            <p style="margin-top:6px">Credits never expire. No monthly fees.</p>
        </div>
    </div>
</section>
@endsection