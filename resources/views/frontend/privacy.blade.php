@extends('layouts.frontend')

@section('title', 'Privacy Policy — HealthIntel')
@section('description', 'HealthIntel privacy policy. Learn how we collect, use, and protect your health data. NDPR compliant.')
@section('robots', 'noindex, follow')

@section('content')
<section class="section">
    <div class="wrap">
        <div class="section-header center">
            <span class="eyebrow">Legal</span>
            <h1>Privacy Policy</h1>
            <p>Last updated: {{ date('F d, Y') }}</p>
        </div>

        <div class="page-section" style="margin:0 auto">

            <!-- Overview -->
            <div style="background:var(--surface);border:1px solid var(--line);border-radius:16px;padding:28px;margin-bottom:36px">
                <h2 style="margin-top:0">At a Glance</h2>
                <p style="margin-bottom:0">HealthIntel ("we", "us", "our") operates the <strong>healthintel.app</strong> website and mobile application. We help you understand your lab results and track your health — but your data belongs to <strong>you</strong>. This policy explains exactly what we collect, why we need it, and how we protect it. We comply with the <strong>Nigeria Data Protection Regulation (NDPR)</strong> and <strong>Nigerian Data Protection Act (NDPA) 2023</strong>.</p>
            </div>

            <!-- 1. Data We Collect -->
            <h2>1. Information We Collect</h2>
            <p>We only collect information that is necessary to provide our services. Here is the full breakdown:</p>

            <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:0.92rem;margin:16px 0 24px">
                <thead>
                    <tr style="background:var(--surface);text-align:left">
                        <th style="padding:12px 16px;border:1px solid var(--line)">Category</th>
                        <th style="padding:12px 16px;border:1px solid var(--line)">Specific Data Points</th>
                        <th style="padding:12px 16px;border:1px solid var(--line)">Why We Need It</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding:12px 16px;border:1px solid var(--line);font-weight:600;white-space:nowrap">Account Information</td>
                        <td style="padding:12px 16px;border:1px solid var(--line)">Full name, email address, phone number (optional), hashed password</td>
                        <td style="padding:12px 16px;border:1px solid var(--line)">To create and secure your account, communicate with you, and verify your identity</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 16px;border:1px solid var(--line);font-weight:600;white-space:nowrap">Health Profile</td>
                        <td style="padding:12px 16px;border:1px solid var(--line)">Date of birth, biological sex, pregnancy status (if female), height (cm), weight (kg), blood type, medical conditions, current medications</td>
                        <td style="padding:12px 16px;border:1px solid var(--line)">To personalise lab result reference ranges (they differ by age, sex, and pregnancy status) and provide accurate interpretations</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 16px;border:1px solid var(--line);font-weight:600;white-space:nowrap">Lab Results</td>
                        <td style="padding:12px 16px;border:1px solid var(--line)">Test panel selected, individual test values (e.g., Hemoglobin: 13.2 g/dL), units, normal/abnormal flags, uploaded PDF lab reports, extracted text from PDFs</td>
                        <td style="padding:12px 16px;border:1px solid var(--line)">To generate plain-language interpretations of your lab results</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 16px;border:1px solid var(--line);font-weight:600;white-space:nowrap">Interpretations</td>
                        <td style="padding:12px 16px;border:1px solid var(--line)">The prompt sent for processing (which contains your lab values), generated interpretation text, and guardrail/safety flags</td>
                        <td style="padding:12px 16px;border:1px solid var(--line)">To provide you with understandable lab explanations and maintain a history of your interpretations. <em>Note: Your lab data is transmitted to our processing service's API. See Section 7 for details.</em></td>
                    </tr>
                    <tr>
                        <td style="padding:12px 16px;border:1px solid var(--line);font-weight:600;white-space:nowrap">Health Metrics & Trackers</td>
                        <td style="padding:12px 16px;border:1px solid var(--line)">Daily wellness tracking data (e.g., blood pressure, blood glucose, weight trends, sleep, water intake, exercise), metric types, recorded dates, notes</td>
                        <td style="padding:12px 16px;border:1px solid var(--line)">To help you track daily health habits and trends. You choose what to log — all tracking is voluntary.</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 16px;border:1px solid var(--line);font-weight:600;white-space:nowrap">Payment Information</td>
                        <td style="padding:12px 16px;border:1px solid var(--line)">Transaction reference, amount paid (in kobo), currency (NGN), payment provider (Paystack or Flutterwave), payment status, provider webhook logs</td>
                        <td style="padding:12px 16px;border:1px solid var(--line)">To process credit purchases and maintain transaction records. <strong>We do NOT store your card number, CVV, or bank details.</strong> All payment card data is handled securely by Paystack and Flutterwave (PCI-DSS certified).</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 16px;border:1px solid var(--line);font-weight:600;white-space:nowrap">Credit Transactions</td>
                        <td style="padding:12px 16px;border:1px solid var(--line)">Action type (purchase, usage, bonus, admin grant), credits added/used, balance after transaction</td>
                        <td style="padding:12px 16px;border:1px solid var(--line)">To maintain an accurate credit balance for your account</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 16px;border:1px solid var(--line);font-weight:600;white-space:nowrap">Appointments</td>
                        <td style="padding:12px 16px;border:1px solid var(--line)">Title, description, appointment date/time, provider name, status, notes, reminder preferences</td>
                        <td style="padding:12px 16px;border:1px solid var(--line)">To help you schedule and manage healthcare appointments</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 16px;border:1px solid var(--line);font-weight:600;white-space:nowrap">Symptom Checker Queries</td>
                        <td style="padding:12px 16px;border:1px solid var(--line)">Symptoms you enter, suggested test panels, generated responses</td>
                        <td style="padding:12px 16px;border:1px solid var(--line)">To suggest relevant lab tests based on symptoms. Sent to our processing service's API.</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 16px;border:1px solid var(--line);font-weight:600;white-space:nowrap">Feedback & Support</td>
                        <td style="padding:12px 16px;border:1px solid var(--line)">Feedback type, screen where submitted, message content, optional metadata</td>
                        <td style="padding:12px 16px;border:1px solid var(--line)">To improve the app based on user suggestions and resolve issues</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 16px;border:1px solid var(--line);font-weight:600;white-space:nowrap">Technical Data</td>
                        <td style="padding:12px 16px;border:1px solid var(--line)">IP address (logged temporarily), device type, browser/OS, app version, session tokens</td>
                        <td style="padding:12px 16px;border:1px solid var(--line)">For security (rate limiting, fraud detection), debugging, and service improvement</td>
                    </tr>
                </tbody>
            </table>
            </div>

            <!-- 2. How We Use -->
            <h2>2. How We Use Your Data</h2>
            <p>Your data is used <strong>exclusively</strong> to provide and improve HealthIntel's services:</p>
            <ul>
                <li><strong>Core Service:</strong> Generating plain-language lab result interpretations and symptom suggestions</li>
                <li><strong>Personalisation:</strong> Tailoring reference ranges based on your age, sex, and health profile</li>
                <li><strong>Account Management:</strong> Authentication, password resets, credit balance tracking, transaction history</li>
                <li><strong>Communication:</strong> Sending essential service updates (never marketing emails without consent)</li>
                <li><strong>Appointment Reminders:</strong> Notifying you of upcoming appointments if you enable reminders</li>
                <li><strong>Product Improvement:</strong> Analysing anonymised, aggregated usage patterns to improve features</li>
                <li><strong>Legal Compliance:</strong> Meeting obligations under Nigerian law and responding to lawful requests</li>
            </ul>
            <p><strong>We DO NOT:</strong></p>
            <ul>
                <li>Sell your personal data to third parties</li>
                <li>Use your health data for advertising or marketing</li>
                <li>Share your lab results with employers, insurers, or family members</li>
                <li>Use your data to train AI models (your data is only processed for your own interpretations)</li>
            </ul>

            <!-- 3. Legal Basis -->
            <h2>3. Legal Basis for Processing</h2>
            <p>Under the NDPR and NDPA, we process your data on the following lawful bases:</p>
            <ul>
                <li><strong>Consent:</strong> You explicitly provide your data when you create an account and enter health information. You may withdraw consent at any time by deleting your account (see Section 8).</li>
                <li><strong>Contractual Necessity:</strong> We need certain data (email, lab values) to deliver the interpretation service you requested.</li>
                <li><strong>Legitimate Interest:</strong> We process anonymised usage data to improve the service, and IP addresses for security (rate limiting, abuse prevention).</li>
                <li><strong>Legal Obligation:</strong> We may retain transaction records as required by Nigerian tax and financial regulations.</li>
            </ul>

            <!-- 4. Data Storage & Retention -->
            <h2>4. Data Storage & Retention</h2>
            <ul>
                <li><strong>Where data is stored:</strong> All data is stored on secure servers located in <strong>Nigeria</strong> (hosted by Namecheap / cPanel). Database backups are encrypted.</li>
                <li><strong>Retention period:</strong>
                    <ul>
                        <li><strong>Account data:</strong> Retained until you delete your account</li>
                        <li><strong>Lab submissions & interpretations:</strong> Retained until you delete your account (you may also delete individual submissions)</li>
                        <li><strong>Health metrics & tracker data:</strong> Retained until you delete your account or manually clear the data</li>
                        <li><strong>Payment records:</strong> Retained for 7 years as required by Nigerian financial regulations (CBN guidelines)</li>
                        <li><strong>Session data:</strong> Expires automatically after 30 days of inactivity</li>
                        <li><strong>Server logs (IP addresses):</strong> Retained for 30 days, then automatically purged</li>
                    </ul>
                </li>
                <li><strong>Uploaded PDFs:</strong> Lab report PDFs you upload are stored on our server until your account is deleted. You may request earlier deletion (see Section 8).</li>
            </ul>

            <!-- 5. Data Security -->
            <h2>5. Data Security</h2>
            <p>We take your health data security seriously. Our security measures include:</p>
            <ul>
                <li><strong>Encryption in transit:</strong> All data between your device and our servers is encrypted using TLS (HTTPS). Our domain (<code>healthintel.app</code>) uses SSL/TLS certificates with modern cipher suites.</li>
                <li><strong>Encryption at rest:</strong> Passwords are hashed using bcrypt (12 rounds). Sensitive database fields are restricted.</li>
                <li><strong>Authentication:</strong> We use Laravel Sanctum token-based authentication. Tokens are valid for 30 days and can be revoked at any time.</li>
                <li><strong>Rate Limiting:</strong> API endpoints are rate-limited to prevent brute-force and abuse. Login is limited to 10 attempts per minute; registration to 6 per minute.</li>
                <li><strong>Access Control:</strong> Only authorised administrators with role-based permissions can access the admin panel. All admin actions are logged in an audit trail.</li>
                <li><strong>Payment Security:</strong> All payment card data is handled exclusively by PCI-DSS Level 1 certified processors (Paystack and Flutterwave). We never see or store your card details.</li>
            </ul>
            <p>While we implement industry-standard safeguards, no method of electronic storage is 100% secure. If we detect a data breach involving your personal health information, we will notify you within 72 hours as required by the NDPR.</p>

            <!-- 6. Data Sharing -->
            <h2>6. Who We Share Your Data With</h2>

            <h3 style="font-size:1rem;margin-top:20px">Third-Party Service Providers (Data Processors)</h3>
            <p>We use the following services to operate HealthIntel. Each is contractually bound to process your data only per our instructions:</p>

            <table style="width:100%;border-collapse:collapse;font-size:0.92rem;margin:16px 0 24px">
                <thead>
                    <tr style="background:var(--surface);text-align:left">
                        <th style="padding:10px 16px;border:1px solid var(--line)">Service</th>
                        <th style="padding:10px 16px;border:1px solid var(--line)">Purpose</th>
                        <th style="padding:10px 16px;border:1px solid var(--line)">Data Shared</th>
                        <th style="padding:10px 16px;border:1px solid var(--line)">Location</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding:10px 16px;border:1px solid var(--line);font-weight:600">Language Processing Service</td>
                        <td style="padding:10px 16px;border:1px solid var(--line)">Lab result interpretation, symptom analysis, PDF report interpretation</td>
                        <td style="padding:10px 16px;border:1px solid var(--line)">Lab values, test names, unit measurements, health profile data (age, sex, pregnancy status), symptoms, PDF text. <em>This provider's privacy policy states they do not use API customer data for model training.</em></td>
                        <td style="padding:10px 16px;border:1px solid var(--line)">Global (API endpoints)</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 16px;border:1px solid var(--line);font-weight:600">Paystack</td>
                        <td style="padding:10px 16px;border:1px solid var(--line)">Payment processing for credit purchases</td>
                        <td style="padding:10px 16px;border:1px solid var(--line)">Email, transaction reference, amount. <em>Card details are collected directly by Paystack — never by us.</em></td>
                        <td style="padding:10px 16px;border:1px solid var(--line)">Nigeria (PCI-DSS Level 1)</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 16px;border:1px solid var(--line);font-weight:600">Flutterwave <span style="font-size:0.75rem;color:var(--text-muted)">(optional)</span></td>
                        <td style="padding:10px 16px;border:1px solid var(--line)">Alternative payment processing</td>
                        <td style="padding:10px 16px;border:1px solid var(--line)">Email, name, transaction reference, amount. <em>Card details are collected directly by Flutterwave.</em></td>
                        <td style="padding:10px 16px;border:1px solid var(--line)">Nigeria (PCI-DSS Level 1)</td>
                    </tr>
                </tbody>
            </table>

            <h3 style="font-size:1rem">When We May Disclose Data</h3>
            <p>We will only disclose your data in these limited circumstances:</p>
            <ul>
                <li><strong>Legal obligation:</strong> If required by Nigerian law, court order, or government regulation (e.g., NDPC)</li>
                <li><strong>Vital interests:</strong> In a medical emergency where disclosure may protect your life or health</li>
                <li><strong>Business transfer:</strong> If HealthIntel is acquired or merged, your data would transfer to the new entity under the same privacy commitments</li>
                <li><strong>With your consent:</strong> For any other purpose, we will ask your explicit permission first</li>
            </ul>

            <!-- 7. Automated Processing -->
            <h2>7. Automated Processing & Interpretations</h2>
            <p>HealthIntel uses a third-party language processing service to generate lab result interpretations, symptom analysis, and PDF report summaries. Here's what you should know:</p>
            <ul>
                <li><strong>What happens:</strong> When you submit lab results or symptoms, we build a prompt containing your test values and health profile. This prompt is sent to our processing partner's API. The service generates a plain-language explanation, which we display to you.</li>
                <li><strong>Not medical diagnosis:</strong> Every interpretation includes a clear disclaimer: <em>"This is NOT medical advice. Please consult a licensed healthcare professional."</em> Our service does not make medical decisions — it only translates lab data into understandable language.</li>
                <li><strong>Guardrails:</strong> We include strict safety prompts that prevent the service from claiming to diagnose diseases, recommending medications, or providing dosages.</li>
                <li><strong>Data sent to our processor:</strong> The prompt contains your lab values (e.g., "Hemoglobin: 13.2 g/dL"), test names, age, sex, pregnancy status if applicable, and symptoms. No directly identifying information (name, email, phone) is included in the prompt.</li>
                <li><strong>Processor's data policy:</strong> Per our processing partner's API terms, they do not use customer API data for model training. The data is processed ephemerally and not stored by the processor beyond what is needed to generate the response.</li>
            </ul>

            <!-- 8. Your Rights -->
            <h2>8. Your Rights Under Nigerian Data Protection Law</h2>
            <p>Under the NDPR and NDPA, you have the following rights:</p>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin:20px 0">
                <div style="background:var(--surface);border:1px solid var(--line);border-radius:12px;padding:20px">
                    <strong style="color:var(--primary)">🔍 Right to Access</strong>
                    <p style="margin:8px 0 0;font-size:0.9rem">You can request a copy of all personal data we hold about you, including lab submissions, interpretations, and health metrics.</p>
                </div>
                <div style="background:var(--surface);border:1px solid var(--line);border-radius:12px;padding:20px">
                    <strong style="color:var(--primary)">✏️ Right to Rectification</strong>
                    <p style="margin:8px 0 0;font-size:0.9rem">You can update your profile, health information, and tracker data at any time through the app. If you find errors, you can correct them directly or request our help.</p>
                </div>
                <div style="background:var(--surface);border:1px solid var(--line);border-radius:12px;padding:20px">
                    <strong style="color:var(--primary)">🗑️ Right to Erasure</strong>
                    <p style="margin:8px 0 0;font-size:0.9rem">You may delete your account at any time. This permanently removes your profile, lab submissions, interpretations, health metrics, tracker data, appointments, and credit history. Payment records may be retained for 7 years per Nigerian financial regulations (in anonymised form if requested).</p>
                </div>
                <div style="background:var(--surface);border:1px solid var(--line);border-radius:12px;padding:20px">
                    <strong style="color:var(--primary)">🚫 Right to Object / Restrict</strong>
                    <p style="margin:8px 0 0;font-size:0.9rem">You can object to processing of your data. For essential service data, this may mean we can no longer provide interpretations. For non-essential data (trackers, appointments), you can simply stop using those features.</p>
                </div>
                <div style="background:var(--surface);border:1px solid var(--line);border-radius:12px;padding:20px">
                    <strong style="color:var(--primary)">📤 Right to Data Portability</strong>
                    <p style="margin:8px 0 0;font-size:0.9rem">You can request your data in a structured, machine-readable format (JSON/CSV). This includes your lab results, health metrics, and interpretations — so you can share them with your doctor.</p>
                </div>
                <div style="background:var(--surface);border:1px solid var(--line);border-radius:12px;padding:20px">
                    <strong style="color:var(--primary)">⚖️ Right to Complain</strong>
                    <p style="margin:8px 0 0;font-size:0.9rem">If you believe your data rights have been violated, you may lodge a complaint with the <strong>Nigeria Data Protection Commission (NDPC)</strong> at <a href="https://ndpc.gov.ng" target="_blank" rel="noopener">ndpc.gov.ng</a>.</p>
                </div>
            </div>

            <!-- 9. Cookies -->
            <h2>9. Cookies & Tracking</h2>
            <ul>
                <li><strong>Essential session cookies:</strong> We use a single session cookie to keep you logged in. This is strictly necessary for the service to function.</li>
                <li><strong>No advertising cookies:</strong> We do not use third-party advertising cookies, tracking pixels, or analytics cookies on our website or mobile app.</li>
                <li><strong>No cross-site tracking:</strong> We do not track your activity across other websites or apps.</li>
                <li><strong>Mobile app:</strong> The mobile app stores an authentication token locally using secure device storage (AsyncStorage / EncryptedStorage), not browser cookies.</li>
            </ul>

            <!-- 10. Children -->
            <h2>10. Children's Privacy</h2>
            <p>HealthIntel is not intended for use by children under the age of 16 without parental consent. If you are a parent or guardian and believe your child has provided us with personal data, please contact us immediately. We will take steps to delete such information.</p>

            <!-- 11. Changes -->
            <h2>11. Changes to This Policy</h2>
            <p>We may update this privacy policy from time to time. If we make material changes, we will:</p>
            <ul>
                <li>Post the updated policy on this page with a new "Last updated" date</li>
                <li>Send an email notification to registered users at least 14 days before the changes take effect</li>
                <li>Display an in-app notice the next time you open the mobile app</li>
            </ul>
            <p>Your continued use of HealthIntel after the effective date constitutes acceptance of the updated policy.</p>

            <!-- 12. Contact -->
            <h2>12. Data Protection Officer & Contact</h2>
            <p>If you have any questions, concerns, or wish to exercise your data rights, please contact our Data Protection Officer:</p>
            <div style="background:var(--surface);border:1px solid var(--line);border-radius:12px;padding:24px;margin-top:16px">
                <p style="margin:0"><strong>Email:</strong> <a href="mailto:privacy@healthintel.app">privacy@healthintel.app</a></p>
                <p style="margin:8px 0 0"><strong>Response time:</strong> We aim to respond to all privacy-related requests within <strong>48 hours</strong> and resolve them within <strong>30 days</strong> (as required by NDPR).</p>
                <p style="margin:8px 0 0"><strong>NDPC:</strong> You may also contact the Nigeria Data Protection Commission at <a href="https://ndpc.gov.ng" target="_blank" rel="noopener">ndpc.gov.ng</a></p>
            </div>

        </div>
    </div>
</section>
@endsection