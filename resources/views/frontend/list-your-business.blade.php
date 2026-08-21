@extends('layouts.frontend')

@section('title', 'List Your Lab or Hospital — HealthIntel Provider Directory')
@section('description', 'Add your lab, hospital, or clinic to the HealthIntel provider directory or sponsor a listing to reach patients searching for providers in Nigeria.')

@section('content')
<section class="section" style="padding-bottom:0">
    <div class="wrap">
        <div class="section-header center anim-fade-up">
            <span class="eyebrow">For Providers</span>
            <h1>Put your lab or hospital in front of patients.</h1>
            <p>Join our provider directory and get discovered by Nigerians searching for labs, hospitals, and clinics — or sponsor your listing to appear first.</p>
        </div>
    </div>
</section>

{{-- HOW IT WORKS --}}
<section class="section">
    <div class="wrap">
        <div class="steps-grid stagger-children">
            <div class="step-card">
                <div class="step-number mono">01</div>
                <h3>Tell us about your facility</h3>
                <p>Submit your details below. Our team reviews every request to keep the directory accurate and trustworthy.</p>
            </div>
            <div class="step-card">
                <div class="step-number mono">02</div>
                <h3>We verify & publish</h3>
                <p>Once approved, your facility appears in the directory with your contact details, location, and services.</p>
            </div>
            <div class="step-card">
                <div class="step-number mono">03</div>
                <h3>Manage your listing</h3>
                <p>Partners sign in to update their listing, add branches, and request sponsored ad placements at any time.</p>
            </div>
        </div>
    </div>
</section>

{{-- FORM --}}
<section class="section" style="padding-top:0">
    <div class="wrap">
        <div class="page-section" style="margin:0 auto">
            {{-- Request type toggle --}}
            <div style="display:flex;gap:12px;margin-bottom:28px;flex-wrap:wrap">
                <button type="button" class="btn btn-primary btn-sm request-type-btn active" data-type="listing">
                    List my lab / hospital
                </button>
                <button type="button" class="btn btn-ghost btn-sm request-type-btn" data-type="promotion">
                    Advertise / sponsor a listing
                </button>
            </div>

            <form id="listingRequestForm" style="background:var(--paper-raised);border:1px solid var(--line);border-radius:var(--radius-md);padding:32px 28px">
                <input type="hidden" id="request_type" name="request_type" value="listing">

                <div class="form-group">
                    <label for="facility_name">Facility / Organisation name *</label>
                    <input type="text" id="facility_name" name="facility_name" required placeholder="e.g., ABC Diagnostics">
                </div>

                <div class="form-group">
                    <label for="type">Facility type *</label>
                    <select id="type" name="type" required style="display:block;width:100%;padding:14px 16px;border:1px solid var(--line);border-radius:var(--radius-sm);font-size:0.95rem;font-family:'Inter',sans-serif;background:var(--paper-raised);color:var(--text);-webkit-appearance:none;appearance:none">
                        <option value="lab">Laboratory / Diagnostic Centre</option>
                        <option value="hospital">Hospital</option>
                        <option value="clinic">Clinic</option>
                        <option value="pharmacy">Pharmacy</option>
                        <option value="specialist">Specialist</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="specialty">Specialty (optional)</label>
                    <input type="text" id="specialty" name="specialty" placeholder="e.g., Cardiology, Pathology">
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
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" placeholder="Street address">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" placeholder="e.g., Ikeja">
                    </div>
                    <div class="form-group">
                        <label for="state">State</label>
                        <input type="text" id="state" name="state" placeholder="e.g., Lagos">
                    </div>
                </div>

                <div class="form-group">
                    <label for="website">Website (optional)</label>
                    <input type="url" id="website" name="website" placeholder="https://...">
                </div>

                <div class="form-group">
                    <label for="description">About your facility</label>
                    <textarea id="description" name="description" rows="3" placeholder="Services offered, accreditations, or anything patients should know."></textarea>
                </div>

                {{-- Promotion-only fields --}}
                <div id="promotionFields" style="display:none;border-top:1px solid var(--line);margin-top:24px;padding-top:24px">
                    <div class="form-group">
                        <label for="promotion_plan">Ad placement</label>
                        <select id="promotion_plan" name="promotion_plan" style="display:block;width:100%;padding:14px 16px;border:1px solid var(--line);border-radius:var(--radius-sm);font-size:0.95rem;font-family:'Inter',sans-serif;background:var(--paper-raised);color:var(--text);-webkit-appearance:none;appearance:none">
                            <option value="sponsored_banner">Sponsored banner (home & directory)</option>
                            <option value="priority_listing">Priority directory listing</option>
                            <option value="custom">Custom campaign</option>
                        </select>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                        <div class="form-group">
                            <label for="promotion_budget_naira">Budget (₦, optional)</label>
                            <input type="number" id="promotion_budget_naira" name="promotion_budget_naira" min="0" placeholder="e.g., 50000">
                        </div>
                        <div class="form-group">
                            <label for="promotion_duration_days">Duration (days, optional)</label>
                            <input type="number" id="promotion_duration_days" name="promotion_duration_days" min="1" max="365" placeholder="e.g., 30">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%" id="listingSubmit">Submit request</button>
                <p id="listingSuccess" style="display:none;text-align:center;color:var(--primary);font-weight:600;margin-top:16px">Thank you! We've received your request and will review it shortly.</p>
                <p id="listingError" style="display:none;text-align:center;color:var(--brick);font-weight:600;margin-top:16px">Something went wrong. Please email us at hello@healthintel.app.</p>
                <p style="text-align:center;color:var(--text-muted);font-size:0.85rem;margin-top:20px">
                    Already a partner? <a href="/partner/login" style="color:var(--primary);font-weight:600;text-decoration:underline">Sign in to manage your listing</a>
                </p>
            </form>
        </div>
    </div>
</section>

{{-- FINAL CTA --}}
<section class="cta-section">
    <div class="wrap stagger-children" style="text-align: center;">
        <h2>Reach patients who need you.</h2>
        <p>Join thousands of Nigerians finding trusted providers through HealthIntel every day.</p>
        <a href="#listingRequestForm" class="btn btn-primary btn-lg" style="margin-top: 8px;">Get listed now</a>
    </div>
</section>

<script>
    (function () {
        var typeButtons = document.querySelectorAll('.request-type-btn');
        var requestTypeInput = document.getElementById('request_type');
        var promotionFields = document.getElementById('promotionFields');
        var form = document.getElementById('listingRequestForm');
        var submitBtn = document.getElementById('listingSubmit');
        var successEl = document.getElementById('listingSuccess');
        var errorEl = document.getElementById('listingError');

        function setType(type) {
            requestTypeInput.value = type;
            promotionFields.style.display = type === 'promotion' ? 'block' : 'none';
            typeButtons.forEach(function (btn) {
                var active = btn.getAttribute('data-type') === type;
                btn.classList.toggle('btn-primary', active);
                btn.classList.toggle('btn-ghost', !active);
                btn.classList.toggle('active', active);
            });
        }

        typeButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setType(btn.getAttribute('data-type'));
            });
        });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            successEl.style.display = 'none';
            errorEl.style.display = 'none';
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            try {
                var payload = {
                    request_type: requestTypeInput.value,
                    facility_name: document.getElementById('facility_name').value,
                    type: document.getElementById('type').value,
                    specialty: document.getElementById('specialty').value,
                    contact_name: document.getElementById('contact_name').value,
                    contact_email: document.getElementById('contact_email').value,
                    contact_phone: document.getElementById('contact_phone').value,
                    address: document.getElementById('address').value,
                    city: document.getElementById('city').value,
                    state: document.getElementById('state').value,
                    website: document.getElementById('website').value,
                    description: document.getElementById('description').value,
                };

                if (requestTypeInput.value === 'promotion') {
                    payload.promotion_plan = document.getElementById('promotion_plan').value;
                    payload.promotion_budget_naira = document.getElementById('promotion_budget_naira').value || null;
                    payload.promotion_duration_days = document.getElementById('promotion_duration_days').value || null;
                }

                var token = document.querySelector('meta[name="csrf-token"]')?.content
                    || document.querySelector('input[name=_token]')?.value
                    || '';

                var res = await fetch('/api/provider-listing-request', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify(payload),
                });

                if (res.ok) {
                    form.reset();
                    setType('listing');
                    successEl.style.display = 'block';
                } else {
                    throw new Error('Server error');
                }
            } catch (err) {
                errorEl.style.display = 'block';
                console.error('Listing request failed:', err);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit request';
            }
        });
    })();
</script>
@endsection