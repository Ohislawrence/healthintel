@extends('layouts.frontend')

@section('title', 'Terms of Service — HealthIntel')
@section('description', 'HealthIntel terms of service. By using HealthIntel, you agree to these terms.')
@section('robots', 'noindex, follow')

@section('content')
<section class="section">
    <div class="wrap">
        <div class="section-header center">
            <span class="eyebrow">Legal</span>
            <h1>Terms of Service</h1>
            <p>Last updated: {{ date('F Y') }}</p>
        </div>

        <div class="page-section" style="margin:0 auto">
            <h2>1. Medical Disclaimer</h2>
            <p>HealthIntel provides educational information only. It is <strong>not a substitute for professional medical advice, diagnosis, or treatment</strong>. Always consult a qualified healthcare provider with questions about your lab results or medical condition.</p>

            <h2>2. Account & Credits</h2>
            <p>Users receive 3 free credits upon registration. Additional credits may be purchased. Credits are non-refundable unless required by law.</p>

            <h2>3. Acceptable Use</h2>
            <p>You agree not to misuse the platform, upload malicious content, or attempt to reverse-engineer the AI systems.</p>

            <h2>4. Limitation of Liability</h2>
            <p>HealthIntel is not liable for any decisions made based on its interpretations. Always verify with a medical professional.</p>
        </div>
    </div>
</section>
@endsection