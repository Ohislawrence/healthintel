@extends('layouts.frontend')

@section('title', 'Affiliate Program — Earn From Your Referrals | HealthIntel')
@section('description', 'Share HealthIntel and earn a commission when your referrals buy credits. Get your unique link, track earnings, and request payouts — all from one dashboard.')

@section('content')
{{-- HERO --}}
<section class="section" style="padding-bottom:0">
    <div class="wrap">
        <div class="section-header center anim-fade-up">
            <span class="eyebrow">Affiliate Program</span>
            <h1>Share HealthIntel. Earn a commission.</h1>
            <p>Help your friends, family, and followers understand their lab results — and earn a percentage every time someone you refer makes a purchase.</p>
            <div style="margin-top: 24px;">
                <a href="/register" class="btn btn-primary btn-lg">Get your referral link</a>
            </div>
        </div>
    </div>
</section>

{{-- HOW IT WORKS --}}
<section class="section">
    <div class="wrap">
        <div class="section-header center anim-fade-up">
            <span class="eyebrow">How it works</span>
            <h2>Three simple steps</h2>
        </div>

        <div class="steps-grid stagger-children">
            <div class="step-card">
                <div class="step-number mono">01</div>
                <h3>Get your link</h3>
                <p>Create a free account and open the <strong>Referral</strong> tab in your dashboard. Copy your unique referral link or share your code.</p>
            </div>
            <div class="step-card">
                <div class="step-number mono">02</div>
                <h3>Share it</h3>
                <p>Send your link to anyone who might need plain-language lab results — WhatsApp, social media, email, or in person.</p>
            </div>
            <div class="step-card">
                <div class="step-number mono">03</div>
                <h3>Earn commission</h3>
                <p>When someone signs up with your link and buys credits, you earn <strong>{{ $commission }}%</strong> of their purchase.</p>
            </div>
        </div>
    </div>
</section>

{{-- HOW THE EARNINGS WORK / DETAILS --}}
<section class="features-wrapper" id="details">
    <div class="wrap section" style="padding-bottom:60px">
        <div class="section-header center anim-fade-up">
            <span class="eyebrow">The details</span>
            <h2>Everything you need to know</h2>
        </div>
    </div>
    <div class="wrap" style="padding-bottom:80px">
        <div class="features-grid stagger-children">
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>{{ $commission }}% commission</h3>
                <p>You earn {{ $commission }}% of every purchase your referral makes on HealthIntel.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Repeated earnings</h3>
                <p>You earn on up to {{ $maxPayouts }} purchases per person you refer.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Track in real time</h3>
                <p>Your referral dashboard shows every referral, your pending balance, total earned, and what's been paid out.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Cash out easily</h3>
                <p>Once your earnings reach ₦{{ number_format($minThreshold) }}, request a payout directly from your dashboard.</p>
            </div>
        </div>
    </div>
</section>

{{-- HOW TO GET YOUR LINK --}}
<section class="section">
    <div class="wrap">
        <div class="section-header center anim-fade-up">
            <span class="eyebrow">Your link</span>
            <h2>Where to find your referral link</h2>
        </div>

        <div class="steps-grid stagger-children">
            <div class="step-card">
                <div class="step-number mono">01</div>
                <h3>Sign in</h3>
                <p>Log in to your HealthIntel account, or create a free one if you don't have one yet.</p>
            </div>
            <div class="step-card">
                <div class="step-number mono">02</div>
                <h3>Open "Referral"</h3>
                <p>In the app menu, tap <strong>Referral</strong>. You'll see your unique code and a ready-made share link.</p>
            </div>
            <div class="step-card">
                <div class="step-number mono">03</div>
                <h3>Copy & share</h3>
                <p>Copy the link and share it anywhere. Anyone who signs up with it is automatically linked to you.</p>
            </div>
        </div>

        <p class="text-center" style="margin-top:32px;color:var(--text-muted);font-size:0.9rem">
            Your link looks like: <code style="font-family:'IBM Plex Mono',monospace;font-size:0.85rem">{{ url('/register?ref=YOURCODE') }}</code>
        </p>
    </div>
</section>

{{-- FAQ --}}
<section class="section" style="padding-top:0">
    <div class="wrap">
        <div class="page-section" style="margin:0 auto">
            <div class="section-header center anim-fade-up">
                <span class="eyebrow">FAQ</span>
                <h2>Frequently asked questions</h2>
            </div>

            <div style="display:grid;gap:1px;background:var(--line);border:1px solid var(--line);border-radius:var(--radius-md);overflow:hidden">
                <div class="value-item">
                    <h3>What do my referrals need to do?</h3>
                    <p>They just need to sign up using your link (or enter your code at signup) and then buy credits. You earn when they make a purchase.</p>
                </div>
                <div class="value-item">
                    <h3>How do I track my earnings?</h3>
                    <p>Everything is in the <strong>Referral</strong> tab in your dashboard — pending balance, total earned, payout history, and each referral.</p>
                </div>
                <div class="value-item">
                    <h3>When can I withdraw?</h3>
                    <p>Once your available earnings reach the ₦{{ number_format($minThreshold) }} minimum, you can request a payout from the referral dashboard.</p>
                </div>
                <div class="value-item">
                    <h3>Is there a limit?</h3>
                    <p>You earn on up to {{ $maxPayouts }} purchases per referred user. There's no limit to how many people you can refer.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- FINAL CTA --}}
<section class="cta-section">
    <div class="wrap stagger-children" style="text-align: center;">
        <h2>Start earning today.</h2>
        <p>Get your referral link and share the gift of understanding.</p>
        <a href="/register" class="btn btn-primary btn-lg" style="margin-top: 8px;">Create your account</a>
    </div>
</section>
@endsection