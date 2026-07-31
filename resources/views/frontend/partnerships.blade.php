@extends('layouts.frontend')

@section('title', 'Partnerships — HealthIntel | White-Label Lab Results for Providers')
@section('description', 'Offer your patients instant, plain-language lab result interpretations. White-label, embeddable, and built for Nigerian labs, hospitals, and diagnostic centres.')

@section('content')
{{-- HERO --}}
<section class="section" style="padding-bottom:0">
    <div class="wrap">
        <div class="section-header center">
            <span class="eyebrow anim-fade-up">For Labs & Hospitals</span>
            <h1 class="anim-fade-up d1">Give your patients answers they understand.</h1>
            <p class="anim-fade-up d2">Your lab runs the tests. We handle the explanations — in plain language, checked against verified reference ranges, delivered under your brand.</p>
        </div>
    </div>
</section>

{{-- HOW IT WORKS FOR PARTNERS --}}
<section class="section">
    <div class="wrap">
        <div class="section-header center anim-fade-up">
            <span class="eyebrow">How the partnership works</span>
            <h2>Plug in. Send results. Patients understand.</h2>
        </div>

        <div class="steps-grid stagger-children">
            <div class="step-card">
                <div class="step-number mono">01</div>
                <h3>We set you up</h3>
                <p>Create your partner account, choose a plan, and customise your branding — logo, colours, and a dedicated results page URL.</p>
            </div>
            <div class="step-card">
                <div class="step-number mono">02</div>
                <h3>Submit lab results</h3>
                <p>Send results via our secure API, HL7 integration, or manual entry. Each result gets matched to verified reference ranges for the patient's age and sex.</p>
            </div>
            <div class="step-card">
                <div class="step-number mono">03</div>
                <h3>Patients get clear explanations</h3>
                <p>Your patients access a branded results page, enter their patient ID or barcode, and instantly see plain-language interpretations — with your logo at the top.</p>
            </div>
        </div>
    </div>
</section>

{{-- BENEFITS --}}
<section class="features-wrapper" id="benefits">
    <div class="wrap section" style="padding-bottom:60px">
        <div class="section-header center anim-fade-up">
            <span class="eyebrow">Why partner with us</span>
            <h2>Everything your lab needs to close the communication gap.</h2>
        </div>
    </div>
    <div class="wrap" style="padding-bottom:80px">
        <div class="features-grid stagger-children">
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>White-label delivery</h3>
                <p>Your logo, your colours, your brand. Patients see your name — not ours. Builds trust and keeps your lab top of mind.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Patient self-service</h3>
                <p>Patients look up their own results using a barcode or patient ID. No calls to your front desk asking "what does this mean?"</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Verified reference ranges</h3>
                <p>Every interpretation is checked against medical reference data reviewed by licensed advisors — age and sex appropriate.</p>
            </div>
            <div class="feature-card">
                <div class="feature-dot" aria-hidden="true"></div>
                <h3>Flexible integration</h3>
                <p>REST API, HL7 parser, or manual dashboard entry. We work with your existing lab information system.</p>
            </div>
        </div>
    </div>
</section>

{{-- PLANS --}}
<section class="section">
    <div class="wrap">
        <div class="section-header center anim-fade-up">
            <span class="eyebrow">Plans</span>
            <h2>Start small. Scale as you grow.</h2>
        </div>

        <div class="pricing-grid" style="margin-bottom:40px">
            <div class="pricing-card anim-fade-up d1">
                <p class="pricing-amount" style="font-size:1.8rem">Pilot</p>
                <p class="pricing-label">Up to 100 reports/month</p>
                <p class="pricing-price" style="font-size:0.95rem;line-height:1.6">
                    Light integration<br>Basic branding<br>Email support
                </p>
                <a href="#" class="btn btn-primary btn-sm partnership-cta">Get started</a>
            </div>
            <div class="pricing-card anim-fade-up d2" style="border-color:var(--primary);box-shadow:var(--shadow-elevated)">
                <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;color:var(--primary);margin-bottom:8px">Most popular</div>
                <p class="pricing-amount" style="font-size:1.8rem">Standard</p>
                <p class="pricing-label">Up to 500 reports/month</p>
                <p class="pricing-price" style="font-size:0.95rem;line-height:1.6">
                    Full API / HL7 integration<br>White-label branding<br>Priority support
                </p>
                <a href="#" class="btn btn-primary btn-sm partnership-cta">Get started</a>
            </div>
            <div class="pricing-card anim-fade-up d3">
                <p class="pricing-amount" style="font-size:1.8rem">Premium</p>
                <p class="pricing-label">Unlimited reports</p>
                <p class="pricing-price" style="font-size:0.95rem;line-height:1.6">
                    Everything in Standard<br>Dedicated account manager<br>Custom SLA
                </p>
                <a href="#" class="btn btn-primary btn-sm partnership-cta">Get started</a>
            </div>
        </div>

        <p class="text-center" style="color:var(--text-muted);font-size:0.9rem">
            All plans include NDPA-compliant data handling, clinician-facing reports, and secure patient lookup.
        </p>
    </div>
</section>

{{-- FINAL CTA --}}
<section class="cta-section">
    <div class="wrap stagger-children" style="text-align: center;">
        <h2>Ready to give your patients clarity?</h2>
        <p>Join the labs already using HealthIntel to deliver instant, plain-language results.</p>
        <a href="#" class="btn btn-primary btn-lg partnership-cta" style="margin-top: 8px;">Become a partner</a>
    </div>
</section>

{{-- MODAL --}}
<div class="modal-overlay" id="partnershipModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-container">
        <button class="modal-close" id="modalClose" aria-label="Close">&times;</button>
        <h2 id="modalTitle" style="font-family:'Fraunces',serif;font-size:1.5rem;margin-bottom:8px;color:var(--ink)">Let's talk</h2>
        <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:24px">Tell us about your lab or hospital, and we'll get back to you within one business day.</p>

        <form id="partnershipForm" method="POST" action="/api/partnership-inquiry">
            @csrf
            <div class="form-group">
                <label for="facility_name">Facility / Organisation name *</label>
                <input type="text" id="facility_name" name="facility_name" required placeholder="e.g., ABC Diagnostics">
            </div>
            <div class="form-group">
                <label for="contact_name">Your name *</label>
                <input type="text" id="contact_name" name="contact_name" required placeholder="Full name">
            </div>
            <div class="form-group">
                <label for="contact_email">Work email *</label>
                <input type="email" id="contact_email" name="contact_email" required placeholder="you@lab.com">
            </div>
            <div class="form-group">
                <label for="contact_phone">Phone number</label>
                <input type="tel" id="contact_phone" name="contact_phone" placeholder="+234 800 000 0000">
            </div>
            <div class="form-group">
                <label for="estimated_volume">Estimated reports per month</label>
                <select id="estimated_volume" name="estimated_volume" style="display:block;width:100%;padding:14px 16px;border:1px solid var(--line);border-radius:var(--radius-sm);font-size:0.95rem;font-family:'Inter',sans-serif;background:var(--paper-raised);color:var(--text);-webkit-appearance:none;appearance:none;background-image:url('data:image/svg+xml;utf8,<svg xmlns=%27http://www.w3.org/2000/svg%27 width=%2712%27 height=%2712%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%2357645D%27 stroke-width=%272%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27><polyline points=%276 9 12 15 18 9%27/></svg>');background-repeat:no-repeat;background-position:right 16px center;padding-right:40px">
                    <option value="">Select...</option>
                    <option value="< 50">Less than 50</option>
                    <option value="50-200">50 – 200</option>
                    <option value="200-500">200 – 500</option>
                    <option value="500-1000">500 – 1,000</option>
                    <option value="1000+">1,000+</option>
                </select>
            </div>
            <div class="form-group">
                <label for="message">Anything else we should know?</label>
                <textarea id="message" name="message" rows="3" placeholder="Tell us about your current workflow, integration needs, or questions."></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%" id="modalSubmit">Send inquiry</button>
            <p id="modalSuccess" style="display:none;text-align:center;color:var(--primary);font-weight:600;margin-top:16px">Thank you! We'll be in touch within one business day.</p>
            <p id="modalError" style="display:none;text-align:center;color:var(--brick);font-weight:600;margin-top:16px">Something went wrong. Please email us at hello@healthintel.app.</p>
        </form>
    </div>
</div>

<style>
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(16, 32, 27, 0.5);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        z-index: 100;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s var(--ease-out-expo), visibility 0.3s;
    }
    .modal-overlay.active {
        opacity: 1;
        visibility: visible;
    }
    .modal-container {
        background: var(--paper-raised);
        border-radius: var(--radius-lg);
        padding: 32px 28px;
        max-width: 520px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
        box-shadow: var(--shadow-elevated);
        transform: translateY(20px);
        transition: transform 0.35s var(--ease-out-expo);
    }
    .modal-overlay.active .modal-container {
        transform: translateY(0);
    }
    .modal-close {
        position: absolute;
        top: 16px;
        right: 20px;
        font-size: 1.5rem;
        background: none;
        border: none;
        cursor: pointer;
        color: var(--text-muted);
        line-height: 1;
        padding: 4px;
    }
    .modal-close:hover { color: var(--ink); }
    body.modal-open { overflow: hidden; }
</style>

<script>
    (function() {
        const modal = document.getElementById('partnershipModal');
        const closeBtn = document.getElementById('modalClose');
        const form = document.getElementById('partnershipForm');
        const submitBtn = document.getElementById('modalSubmit');
        const successEl = document.getElementById('modalSuccess');
        const errorEl = document.getElementById('modalError');

        function openModal() {
            modal.classList.add('active');
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.remove('active');
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
        }

        // Open from any .partnership-cta link
        document.querySelectorAll('.partnership-cta').forEach(el => {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                openModal();
            });
        });

        // Close on X button
        closeBtn.addEventListener('click', closeModal);

        // Close on overlay click
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });

        // Close on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('active')) closeModal();
        });

        // Submit form via AJAX
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            successEl.style.display = 'none';
            errorEl.style.display = 'none';
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';

            try {
                const res = await fetch(form.getAttribute('action'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value,
                    },
                    body: JSON.stringify({
                        facility_name: document.getElementById('facility_name').value,
                        contact_name: document.getElementById('contact_name').value,
                        contact_email: document.getElementById('contact_email').value,
                        contact_phone: document.getElementById('contact_phone').value,
                        estimated_volume: document.getElementById('estimated_volume').value,
                        message: document.getElementById('message').value,
                    }),
                });

                if (res.ok) {
                    form.reset();
                    successEl.style.display = 'block';
                    setTimeout(closeModal, 2500);
                } else {
                    throw new Error('Server error');
                }
            } catch (err) {
                errorEl.style.display = 'block';
                console.error('Partnership inquiry failed:', err);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send inquiry';
            }
        });
    })();
</script>
@endsection