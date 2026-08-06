import React from 'react';

const sections = [
  {
    id: 'overview',
    title: 'Platform Overview',
    content: (
      <div>
        <p className="mb-3">
          HealthIntel's lab partner integration allows hospitals, diagnostic centres, and standalone labs
          to send patient lab results to our platform and receive plain-language interpretations checked against
          verified reference ranges — delivered back to the partner or directly to patients.
        </p>
        <p className="mb-3">
          The integration is built around four core pieces:
        </p>
        <ul className="list-disc pl-6 space-y-1 text-sm text-gray-700 mb-3">
          <li><strong>Partner API</strong> — REST endpoints for submitting individual or batch results, plus HL7v2 parsing</li>
          <li><strong>Admin Portal</strong> — Manage partnerships, view inquiries, generate invoices, monitor delivery health</li>
          <li><strong>Partner Dashboard</strong> — Partners log in with an access code to view stats, submit results manually, and manage their profile</li>
          <li><strong>Patient Results Page</strong> — A branded public page where patients enter a barcode or patient ID to view their results</li>
        </ul>
      </div>
    ),
  },
  {
    id: 'connection-guide',
    title: '📡 Connection Guide — Lab/Hospital + HealthIntel Setup',
    content: (
      <div>
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800 mb-6">
          <strong>This section explains what the lab/hospital needs to do on their side, and what the HealthIntel admin needs to do on our side. Follow both tracks in parallel.</strong>
        </div>

        <h4 className="font-semibold text-gray-800 text-base mt-6 mb-3">Part A — What the Lab/Hospital Needs to Do</h4>

        <div className="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
          <h5 className="font-bold text-sm text-gray-800 mb-2">1. Prepare Their System for API Access</h5>
          <p className="text-sm text-gray-600 mb-2">The lab needs a way to send HTTP requests to our API. They can use:</p>
          <ul className="list-disc pl-6 space-y-1 text-sm text-gray-700">
            <li><strong>Their existing LIMS/HIS:</strong> If their lab system supports webhooks, API integrations, or HL7 message forwarding, they can configure it to POST results to HealthIntel.</li>
            <li><strong>A custom script:</strong> A simple PHP, Python, or Node.js script that reads their database or CSV export and POSTs to our API.</li>
            <li><strong>Manual upload:</strong> Use the Partner Dashboard at <code className="bg-gray-100 px-1 rounded">/partner/submit</code> for manual entry (good for testing or low-volume labs).</li>
          </ul>
        </div>

        <div className="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
          <h5 className="font-bold text-sm text-gray-800 mb-2">2. Get Their Access Code</h5>
          <p className="text-sm text-gray-600 mb-2">
            The HealthIntel admin will provide them with a unique <strong>access code</strong> (a 40-character token).
            They will use this to log in at <code className="bg-gray-100 px-1 rounded">/partner/login</code>.
          </p>
          <p className="text-sm text-gray-600">
            Save the access code in a secure location. It acts as the lab's password to HealthIntel.
          </p>
        </div>

        <div className="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
          <h5 className="font-bold text-sm text-gray-800 mb-2">3. Authenticate and Get an API Token</h5>
          <p className="text-sm text-gray-600 mb-2">
            Before making any API calls, the lab must exchange their access code for a Bearer token:
          </p>
          <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto">
            <code>POST /api/partner/login</code>
          </div>
          <p className="text-xs text-gray-500 mb-1">Request body (JSON):</p>
          <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto">
{`{
  "access_code": "abc123...",
  "provider_email": "lab@hospital.com"
}`}
          </div>
          <p className="text-xs text-gray-500 mb-1">Response contains:</p>
          <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto">
{`{
  "token": "1|xxxxxxxxxxxxxxxxxxxxx",
  "provider": { "name": "City Hospital Lab", "slug": "city-hospital-lab" }
}`}
          </div>
          <p className="text-sm text-gray-600">
            The token should be included as <code className="bg-gray-100 px-1 rounded">Authorization: Bearer {'{token}'}</code> in all subsequent API requests.
          </p>
        </div>

        <div className="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
          <h5 className="font-bold text-sm text-gray-800 mb-2">4. Send Individual Results</h5>
          <p className="text-sm text-gray-600 mb-2">Send one test result at a time:</p>
          <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto">
            <code>POST /api/partner/interpretations</code>
          </div>
          <p className="text-xs text-gray-500 mb-1">Minimal payload (all the lab needs to send):</p>
          <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto">
{`{
  "patient_identifier": "PT-2024-001",
  "test_name": "Hemoglobin",
  "value": "13.5",
  "unit": "g/dL"
}`}
          </div>
          <p className="text-xs text-gray-500 mb-1">Full payload with optional fields:</p>
          <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto">
{`{
  "patient_identifier": "PT-2024-001",
  "test_name": "Hemoglobin",
  "value": "13.5",
  "unit": "g/dL",
  "reference_range_low": "12.0",
  "reference_range_high": "16.0",
  "sex": "female",
  "age": 32,
  "delivery_method": "sms",
  "delivery_recipient": "+2348012345678"
}`}
          </div>
          <p className="text-xs text-gray-500 mb-1">The API returns:</p>
          <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto">
{`{
  "interpretation": {
    "status": "pending",  // changes to "completed" after AI processes
    "interpretation_text": null,  // populates when done
    "reference_range": "12.0 — 16.0 g/dL",
    "flag": "normal"
  }
}`}
          </div>
        </div>

        <div className="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
          <h5 className="font-bold text-sm text-gray-800 mb-2">5. Send Results in Bulk (CSV)</h5>
          <p className="text-sm text-gray-600 mb-2">
            For high-volume labs, send a CSV file with all results at once:
          </p>
          <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto">
            <code>POST /api/partner/interpretations/bulk</code>
          </div>
          <p className="text-xs text-gray-500 mb-1">CSV format (required headers):</p>
          <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto">
{`patient_id,test_name,value,unit,sex,age,reference_range_low,reference_range_high
PT-001,Hemoglobin,13.5,g/dL,female,32,12.0,16.0
PT-001,WBC,8.2,10^3/uL,female,32,4.0,11.0
PT-002,Glucose,118,mg/dL,male,45,70,99`}
          </div>
          <p className="text-sm text-gray-600 mb-2">
            Send via multipart/form-data with the CSV as the <code className="bg-gray-100 px-1 rounded">file</code> field.
          </p>
        </div>

        <div className="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
          <h5 className="font-bold text-sm text-gray-800 mb-2">6. Send via HL7 (for Hospital Information Systems)</h5>
          <p className="text-sm text-gray-600 mb-2">
            If the lab's LIS/HIS generates HL7v2 ORU^R01 messages, they can POST the raw HL7 message directly:
          </p>
          <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto">
            <code>POST /api/partner/v1/hl7</code>
          </div>
          <p className="text-sm text-gray-600">
            Content-Type: <code className="bg-gray-100 px-1 rounded">text/plain</code> or <code className="bg-gray-100 px-1 rounded">application/hl7-v2</code>. The raw HL7 message goes in the request body.
            Our parser extracts patient demographics, test names, values, units, and reference ranges automatically.
          </p>
        </div>

        <div className="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
          <h5 className="font-bold text-sm text-gray-800 mb-2">7. Redirect Patients to Their Results</h5>
          <p className="text-sm text-gray-600 mb-2">
            The lab can share a branded results page with patients:
          </p>
          <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto">
            <code>GET /r/{"{provider-slug}"}?pid={"{"}patient-identifier{"}"}</code>
          </div>
          <p className="text-sm text-gray-600">
            The lab can embed this URL on their website, print it on receipts, or send it via SMS.
            Patients enter their Patient ID to view all interpreted results. No login required.
          </p>
        </div>

        <h4 className="font-semibold text-gray-800 text-base mt-8 mb-3">Part B — What the HealthIntel Admin Needs to Do</h4>

        <div className="bg-teal-50 border border-teal-200 rounded-lg p-4 mb-6">
          <h5 className="font-bold text-sm text-teal-800 mb-2">1. Add the Provider to the Directory</h5>
          <p className="text-sm text-teal-700">
            Go to <strong>Admin → Providers</strong> → <em>+ Add Provider</em>. Fill in: provider name, type (hospital/clinic/lab), contact details, and address. This creates the provider entity in the system.
          </p>
          <p className="text-sm text-teal-700 mt-1">
            Set <strong>Partner Status</strong> to <code className="bg-teal-100 px-1 rounded">affiliate</code> or <code className="bg-teal-100 px-1 rounded">sponsored</code> to enable partner features.
          </p>
        </div>

        <div className="bg-teal-50 border border-teal-200 rounded-lg p-4 mb-6">
          <h5 className="font-bold text-sm text-teal-800 mb-2">2. Create the Partnership</h5>
          <p className="text-sm text-teal-700">
            Go to <strong>Admin → Partnerships</strong> → <em>+ New Partnership</em>. Select the provider you just created.
          </p>
          <p className="text-sm text-teal-700 mt-1">Configure:</p>
          <ul className="list-disc pl-6 space-y-1 text-sm text-teal-700 mt-1">
            <li><strong>Plan Tier:</strong> Pilot (free trial), Standard, or Premium</li>
            <li><strong>Pricing Model:</strong> Per-report (₦X per interpretation), Volume-tiered (included reports + overage rate), or Flat monthly fee</li>
            <li><strong>White-Label Branding:</strong> Upload the lab's logo and set their primary brand colour (optional)</li>
            <li><strong>Contact Info:</strong> Phone, email, and address to display on branded reports</li>
          </ul>
        </div>

        <div className="bg-teal-50 border border-teal-200 rounded-lg p-4 mb-6">
          <h5 className="font-bold text-sm text-teal-800 mb-2">3. Generate the Access Code</h5>
          <p className="text-sm text-teal-700">
            Go to <strong>Admin → Providers</strong> → click on the provider → <em>Generate Access Code</em>. This creates a unique 40-character token.
          </p>
          <p className="text-sm text-teal-700 mt-1">
            <strong>Share this code securely</strong> with the lab partner (via email, encrypted message, or printed letter).
            They need it to log in at <code className="bg-teal-100 px-1 rounded">/partner/login</code>.
          </p>
          <p className="text-sm text-teal-700 mt-1">
            The access code can be regenerated at any time (old codes become invalid).
          </p>
        </div>

        <div className="bg-teal-50 border border-teal-200 rounded-lg p-4 mb-6">
          <h5 className="font-bold text-sm text-teal-800 mb-2">4. Test the Integration</h5>
          <ol className="list-decimal pl-6 space-y-1 text-sm text-teal-700 mt-1">
            <li>Ask the partner to log in at <code className="bg-teal-100 px-1 rounded">/partner/login</code> using their access code</li>
            <li>Have them submit a test result via the Partner Dashboard or API</li>
            <li>Verify the submission appears in <strong>Admin → Submissions</strong></li>
            <li>Check the interpretation was generated (click the submission to view)</li>
            <li>If SMS/email delivery is configured, verify the patient received the notification</li>
            <li>Monitor <strong>Admin → Partnerships → [Partnership] → Delivery Health</strong> for delivery success rates</li>
          </ol>
        </div>

        <div className="bg-teal-50 border border-teal-200 rounded-lg p-4 mb-6">
          <h5 className="font-bold text-sm text-teal-800 mb-2">5. Go Live!</h5>
          <p className="text-sm text-teal-700">
            Once testing is successful, the partnership is live. The lab can now:
          </p>
          <ul className="list-disc pl-6 space-y-1 text-sm text-teal-700 mt-1">
            <li>Send unlimited results via API or HL7</li>
            <li>View their dashboard at <code className="bg-teal-100 px-1 rounded">/partner/dashboard</code></li>
            <li>Download reports as branded PDFs</li>
            <li>Track their usage and monthly bills</li>
          </ul>
        </div>

        <h4 className="font-semibold text-gray-800 text-base mt-8 mb-3">Part C — Example Integration Scripts</h4>

        <div className="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
          <h5 className="font-bold text-sm text-gray-800 mb-2">Python Integration Example</h5>
          <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto">
{`import requests

# Step 1: Login and get token
login_resp = requests.post(
    "https://healthintel.app/api/partner/login",
    json={"access_code": "your-40-char-access-code"}
)
token = login_resp.json()["token"]

# Step 2: Send a test result
headers = {"Authorization": f"Bearer {token}"}
result = requests.post(
    "https://healthintel.app/api/partner/interpretations",
    headers=headers,
    json={
        "patient_identifier": "PT-2024-001",
        "test_name": "Hemoglobin",
        "value": "13.5",
        "unit": "g/dL",
        "sex": "female",
        "age": 32,
        "delivery_method": "sms",
        "delivery_recipient": "+2348012345678"
    }
)
print(result.json())`}
          </div>
        </div>

        <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
          <h5 className="font-bold text-sm text-gray-800 mb-2">PHP Integration Example (cURL)</h5>
          <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto">
{`$access_code = 'your-40-char-access-code';

// Step 1: Login
$ch = curl_init('https://healthintel.app/api/partner/login');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode(['access_code' => $access_code])
]);
$login = json_decode(curl_exec($ch), true);
$token = $login['token'];
curl_close($ch);

// Step 2: Send result
$ch = curl_init('https://healthintel.app/api/partner/interpretations');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        "Authorization: Bearer $token"
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'patient_identifier' => 'PT-2024-001',
        'test_name' => 'Hemoglobin',
        'value' => '13.5',
        'unit' => 'g/dL'
    ])
]);
echo curl_exec($ch);
curl_close($ch);`}
          </div>
        </div>
      </div>
    ),
  },
  {
    id: 'onboarding',
    title: 'Onboarding Checklist (Quick Reference)',
    content: (
      <div>
        <p className="mb-3 text-sm text-gray-700">Follow these steps to bring a new partner on board:</p>
        <ol className="list-decimal pl-6 space-y-3 text-sm text-gray-700">
          <li><strong>Add the provider.</strong> Go to <em>Admin → Providers</em> and create the provider entry. Set partner status to <code className="bg-gray-100 px-1 rounded">affiliate</code> or <code className="bg-gray-100 px-1 rounded">sponsored</code>.</li>
          <li><strong>Create the partnership.</strong> Go to <em>Admin → Partnerships → + New Partnership</em>. Select provider, choose plan tier (Pilot / Standard / Premium), set pricing model, and configure white-label branding.</li>
          <li><strong>Generate access code.</strong> On the provider detail page, click "Generate Access Code". Share this code with the partner — they use it to log in at <code className="bg-gray-100 px-1 rounded">/partner/login</code>.</li>
          <li><strong>Share API details.</strong> Point the partner to this documentation page. Show them the Python or PHP example scripts.</li>
          <li><strong>Test together.</strong> Have the partner submit a test result. Verify it appears in Admin → Submissions with a generated interpretation.</li>
          <li><strong>Go live.</strong> Monitor usage from the partnership detail page.</li>
        </ol>
      </div>
    ),
  },
  {
    id: 'partner-roles',
    title: 'Do I Need to Create a Partner Role?',
    content: (
      <div>
        <p className="mb-3 text-sm text-gray-700">
          <strong>No.</strong> Partners do not use the regular user system. They authenticate as a
          <code className="bg-gray-100 px-1 rounded"> ProviderDirectoryEntry </code>
          entity via access codes, not as <code className="bg-gray-100 px-1 rounded">User</code> models with Spatie roles.
        </p>
        <ul className="list-disc pl-6 space-y-1 text-sm text-gray-700 mb-3">
          <li>When you generate an access code for a provider, they can log in at <code className="bg-gray-100 px-1 rounded">/partner/login</code> using that code.</li>
          <li>The login returns a Sanctum token that identifies them as that provider for all subsequent API calls.</li>
          <li>There is no "partner" Spatie role. Partner authentication is handled through the <code className="bg-gray-100 px-1 rounded">PartnerPortalController</code> and <code className="bg-gray-100 px-1 rounded">PartnerInterpretationController</code>.</li>
          <li>If you need a staff member to have admin access, assign them a regular user account with the <code className="bg-gray-100 px-1 rounded">admin</code> role.</li>
        </ul>
      </div>
    ),
  },
  {
    id: 'api-rest',
    title: 'REST API Reference — All Endpoints',
    content: (
      <div>
        <p className="mb-3 text-sm text-gray-700">
          Partners authenticate via Bearer token (from <code className="bg-gray-100 px-1 rounded">POST /api/partner/login</code>).
          All partner API routes are prefixed with <code className="bg-gray-100 px-1 rounded">/api/partner</code>.
        </p>
        <h4 className="font-semibold text-gray-800 text-sm mt-6 mb-2">Submit a Single Interpretation</h4>
        <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto"><code>POST /api/partner/interpretations</code></div>
        <table className="w-full text-xs mb-4 border border-gray-200 rounded-lg overflow-hidden">
          <thead className="bg-gray-50">
            <tr><th className="text-left px-3 py-2 font-medium text-gray-600 border-b">Field</th><th className="text-left px-3 py-2 font-medium text-gray-600 border-b">Type</th><th className="text-left px-3 py-2 font-medium text-gray-600 border-b">Required</th><th className="text-left px-3 py-2 font-medium text-gray-600 border-b">Description</th></tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            <tr><td className="px-3 py-2 font-mono text-gray-700">patient_identifier</td><td className="px-3 py-2 text-gray-500">string</td><td className="px-3 py-2"><span className="bg-green-100 text-green-700 rounded px-1 text-xs">Yes</span></td><td className="px-3 py-2 text-gray-600">Patient ID, barcode, or file number</td></tr>
            <tr><td className="px-3 py-2 font-mono text-gray-700">test_name</td><td className="px-3 py-2 text-gray-500">string</td><td className="px-3 py-2"><span className="bg-green-100 text-green-700 rounded px-1 text-xs">Yes</span></td><td className="px-3 py-2 text-gray-600">e.g. "Hemoglobin", "Fasting Blood Glucose"</td></tr>
            <tr><td className="px-3 py-2 font-mono text-gray-700">value</td><td className="px-3 py-2 text-gray-500">string/number</td><td className="px-3 py-2"><span className="bg-green-100 text-green-700 rounded px-1 text-xs">Yes</span></td><td className="px-3 py-2 text-gray-600">The test result value</td></tr>
            <tr><td className="px-3 py-2 font-mono text-gray-700">unit</td><td className="px-3 py-2 text-gray-500">string</td><td className="px-3 py-2">No</td><td className="px-3 py-2 text-gray-600">e.g. "g/dL", "mg/dL", "mmol/L"</td></tr>
            <tr><td className="px-3 py-2 font-mono text-gray-700">reference_range_low</td><td className="px-3 py-2 text-gray-500">string</td><td className="px-3 py-2">No</td><td className="px-3 py-2 text-gray-600">Lower bound of normal range</td></tr>
            <tr><td className="px-3 py-2 font-mono text-gray-700">reference_range_high</td><td className="px-3 py-2 text-gray-500">string</td><td className="px-3 py-2">No</td><td className="px-3 py-2 text-gray-600">Upper bound of normal range</td></tr>
            <tr><td className="px-3 py-2 font-mono text-gray-700">sex</td><td className="px-3 py-2 text-gray-500">string</td><td className="px-3 py-2">No</td><td className="px-3 py-2 text-gray-600">"male", "female", or omit</td></tr>
            <tr><td className="px-3 py-2 font-mono text-gray-700">age</td><td className="px-3 py-2 text-gray-500">number</td><td className="px-3 py-2">No</td><td className="px-3 py-2 text-gray-600">Patient age in years</td></tr>
            <tr><td className="px-3 py-2 font-mono text-gray-700">delivery_method</td><td className="px-3 py-2 text-gray-500">string</td><td className="px-3 py-2">No</td><td className="px-3 py-2 text-gray-600">"email", "sms", "whatsapp", or omit</td></tr>
            <tr><td className="px-3 py-2 font-mono text-gray-700">delivery_recipient</td><td className="px-3 py-2 text-gray-500">string</td><td className="px-3 py-2">No</td><td className="px-3 py-2 text-gray-600">Email or phone for delivery</td></tr>
          </tbody>
        </table>
        <h4 className="font-semibold text-gray-800 text-sm mt-6 mb-2">Bulk CSV Upload</h4>
        <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto"><code>POST /api/partner/interpretations/bulk</code></div>
        <p className="text-sm text-gray-600 mb-2">Multipart/form-data with CSV file. Headers: patient_id,test_name,value,unit,sex,age,reference_range_low,reference_range_high</p>
        <h4 className="font-semibold text-gray-800 text-sm mt-6 mb-2">Versioned API (stable)</h4>
        <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto"><code>POST /api/partner/v1/interpretations</code></div>
        <p className="text-sm text-gray-600 mb-2">Same payload as standard endpoint but versioned. Use for production integrations.</p>
      </div>
    ),
  },
  {
    id: 'api-hl7',
    title: 'HL7v2 Integration',
    content: (
      <div>
        <p className="mb-3 text-sm text-gray-700">For labs and hospital information systems (LIS/HIS) that output HL7v2 messages, HealthIntel provides a parsing endpoint:</p>
        <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto"><code>POST /api/partner/v1/hl7</code></div>
        <p className="text-sm text-gray-600 mb-2">Request body: raw HL7v2 message (Content-Type: text/plain or application/hl7-v2).</p>
        <p className="text-sm text-gray-700 mb-2">The parser extracts:</p>
        <ul className="list-disc pl-6 space-y-1 text-sm text-gray-700 mb-3">
          <li>Patient ID (from PID-3)</li>
          <li>Patient name, date of birth, sex (from PID segment)</li>
          <li>Ordering provider details (from OBR segment)</li>
          <li>Test names and values (from OBX segments)</li>
          <li>Units and reference ranges (from OBX-6 and OBX-7)</li>
        </ul>
        <div className="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800 mt-4">
          <strong>Note:</strong> HL7 messages vary between systems. Test with sample messages from your LIS first. Contact us if specific segment mappings need adjustment.
        </div>
      </div>
    ),
  },
  {
    id: 'patient-results',
    title: 'Patient Results Page (White-Label)',
    content: (
      <div>
        <p className="mb-3 text-sm text-gray-700">Each partnership gets a branded public results page:</p>
        <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto"><code>{'GET /r/{provider-slug}?pid=<patient-identifier>'}</code></div>
        <p className="text-sm text-gray-700 mb-3">Patients enter their Patient ID to view all interpreted results. Displays:</p>
        <ul className="list-disc pl-6 space-y-1 text-sm text-gray-700 mb-3">
          <li>Partner's logo and branding</li>
          <li>Each test value with colour-coded status (Normal / High / Low)</li>
          <li>Plain-language interpretation text</li>
          <li>Medical disclaimer</li>
        </ul>
        <p className="text-sm text-gray-600 mb-2">No login required for patients. Partners can embed this URL on their website or share via SMS/email.</p>
      </div>
    ),
  },
  {
    id: 'delivery',
    title: 'Delivery Methods (Email / SMS / WhatsApp)',
    content: (
      <div>
        <p className="mb-3 text-sm text-gray-700">When submitting an interpretation, partners can request delivery to the patient:</p>
        <ul className="list-disc pl-6 space-y-1 text-sm text-gray-700 mb-3">
          <li><strong>Email</strong> — Sends PDF interpretation report as attachment</li>
          <li><strong>SMS</strong> — Sends brief text summary via Termii</li>
          <li><strong>WhatsApp</strong> — Sends formatted message via Termii (if WhatsApp is enabled)</li>
        </ul>
        <p className="text-sm text-gray-600 mb-2">Delivery attempts are tracked with retry logic. Admins can monitor delivery health from the partnership detail page. Failed deliveries are retried with exponential backoff.</p>
      </div>
    ),
  },
  {
    id: 'invoicing',
    title: 'Invoicing & Pricing',
    content: (
      <div>
        <p className="mb-3 text-sm text-gray-700">The billing system tracks every interpretation and generates monthly invoices.</p>
        <ul className="list-disc pl-6 space-y-1 text-sm text-gray-700 mb-3">
          <li><strong>Per-report:</strong> Fixed rate per interpretation</li>
          <li><strong>Volume-tiered:</strong> Monthly allowance with overage rates</li>
          <li><strong>Flat monthly:</strong> Unlimited reports for fixed fee</li>
        </ul>
        <p className="text-sm text-gray-600 mb-2">Generate invoices from <em>Admin → Partnerships → [Partnership] → Invoices → Generate Invoice</em>. Downloadable PDF proposal/receipt included.</p>
      </div>
    ),
  },
  {
    id: 'admin-workflow',
    title: 'Admin Day-to-Day Workflow',
    content: (
      <div>
        <p className="mb-3 text-sm text-gray-700">A typical admin workflow:</p>
        <ol className="list-decimal pl-6 space-y-2 text-sm text-gray-700">
          <li>Monitor <strong>Partner Inquiries</strong> (📨 sidebar) for new partnership requests</li>
          <li>Follow up — update inquiry status (new → contacted → converted → closed)</li>
          <li>Create provider and partnership (see Connection Guide above)</li>
          <li>Generate access code and share with partner</li>
          <li>Monitor <strong>Partnerships</strong> dashboard for usage stats and estimated bills</li>
          <li>Check <strong>Delivery Health</strong> for failed SMS/email deliveries</li>
          <li>Generate invoices at month-end</li>
          <li>Use <strong>Email Campaigns</strong> for partner announcements</li>
          <li>Review <strong>Audit Log</strong> for all administrative actions</li>
        </ol>
      </div>
    ),
  },
];

export default function AdminDocumentation() {
  return (
    <div className="max-w-4xl">
      <div className="mb-8">
        <h2 className="text-2xl font-bold text-gray-900 mb-2">📖 Documentation</h2>
        <p className="text-sm text-gray-500">Lab partner integration — API reference, connection guide, and admin workflows.</p>
      </div>
      <div className="space-y-10">
        {sections.map((section) => (
          <div key={section.id} id={section.id} className="bg-white rounded-xl border border-gray-200 p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">{section.title}</h3>
            {section.content}
          </div>
        ))}
      </div>
      <div className="mt-8 bg-teal-50 border border-teal-200 rounded-xl p-6">
        <h3 className="text-sm font-semibold text-teal-800 mb-2">Need help?</h3>
        <p className="text-sm text-teal-700">
          For questions not covered here, check the API source at{' '}
          <code className="bg-teal-100 px-1 rounded">app/Http/Controllers/Api/Partner/PartnerInterpretationController.php</code>{' '}
          or contact the development team.
        </p>
      </div>
    </div>
  );
}