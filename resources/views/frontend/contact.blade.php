@extends('layouts.frontend')

@section('title', 'Contact HealthIntel — Get in Touch')
@section('description', 'Have questions about HealthIntel? Contact us for support, partnership inquiries, or feedback.')

@section('content')
<section class="section">
    <div class="wrap">
        <div class="section-header center">
            <span class="eyebrow anim-fade-up">Get in touch</span>
            <h1 class="anim-fade-up d1">Contact Us</h1>
            <p class="anim-fade-up d2">We'd love to hear from you — whether it's feedback, a partnership inquiry, or a question.</p>
        </div>

        <form class="anim-fade-up d2" style="max-width:480px;margin:0 auto" method="POST" action="/api/feedback">
            @csrf
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" placeholder="Your name">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="you@example.com">
            </div>
            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" placeholder="How can we help?" rows="5"></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-lg" style="width:100%">Send Message</button>
        </form>
    </div>
</section>
@endsection