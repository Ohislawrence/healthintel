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
    id: 'onboarding',
    title: 'Onboarding a New Lab / Hospital Partner',
    content: (
      <div>
        <p className="mb-3">Follow these steps to bring a new partner on board:</p>
        <ol className="list-decimal pl-6 space-y-3 text-sm text-gray-700">
          <li>
            <strong>Add the provider to the directory.</strong> Go to <em>Admin → Providers</em> and create
            the provider entry. Set the partner status to <code className="bg-gray-100 px-1 rounded">affiliate</code> or <code className="bg-gray-100 px-1 rounded">sponsored</code>.
          </li>
          <li>
            <strong>Create the partnership.</strong> Go to <em>Admin → Partnerships → + New Partnership</em>.
            Select the provider, choose a plan tier (Pilot / Standard / Premium), set the pricing model (per-report,
            volume-tiered, or flat monthly), and configure white-label branding (logo, primary colour) if desired.
          </li>
          <li>
            <strong>Generate an access code.</strong> On the provider detail page, click "Generate Access Code".
            Share this code with the partner — they will use it to log in at <code className="bg-gray-100 px-1 rounded">/partner/login</code>.
            No user account or role creation is needed; the partner authenticates as the provider entity.
          </li>
          <li>
            <strong>Share the API key.</strong> For programmatic (API) access, the partner authenticates using
            Bearer tokens obtained via the partner login endpoint. See the API section below.
          </li>
          <li>
            <strong>Test the integration.</strong> Use the <em>Send Test Email</em> feature in Email Campaigns to verify
            delivery. Submit a test result via the partner dashboard or API and confirm it appears in the admin
            submissions list.
          </li>
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
          <li>There is no "partner" Spatie role. Partner authentication is handled entirely through the <code className="bg-gray-100 px-1 rounded">PartnerPortalController</code> and <code className="bg-gray-100 px-1 rounded">PartnerInterpretationController</code>.</li>
          <li>If you need a staff member at a partner lab to have admin-like access to HealthIntel, assign them a regular user account with the appropriate Spatie role (e.g., <code className="bg-gray-100 px-1 rounded">admin</code> or a custom role).</li>
        </ul>
      </div>
    ),
  },
  {
    id: 'api-rest',
    title: 'REST API — Partner Interpretation',
    content: (
      <div>
        <p className="mb-3 text-sm text-gray-700">
          Partners authenticate via Bearer token (obtained from <code className="bg-gray-100 px-1 rounded">POST /api/partner/login</code> with their access code). 
          All partner API routes are prefixed with <code className="bg-gray-100 px-1 rounded">/api/partner</code>.
        </p>

        <h4 className="font-semibold text-gray-800 text-sm mt-6 mb-2">Submit a Single Interpretation</h4>
        <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto">
          <code>POST /api/partner/interpretations</code>
        </div>
        <table className="w-full text-xs mb-4 border border-gray-200 rounded-lg overflow-hidden">
          <thead className="bg-gray-50">
            <tr>
              <th className="text-left px-3 py-2 font-medium text-gray-600 border-b">Field</th>
              <th className="text-left px-3 py-2 font-medium text-gray-600 border-b">Type</th>
              <th className="text-left px-3 py-2 font-medium text-gray-600 border-b">Required</th>
              <th className="text-left px-3 py-2 font-medium text-gray-600 border-b">Description</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            <tr><td className="px-3 py-2 font-mono text-gray-700">patient_identifier</td><td className="px-3 py-2 text-gray-500">string</td><td className="px-3 py-2"><span className="bg-green-100 text-green-700 rounded px-1 text-xs">Yes</span></td><td className="px-3 py-2 text-gray-600">Patient ID, barcode, or file number</td></tr>
            <tr><td className="px-3 py-2 font-mono text-gray-700">test_name</td><td className="px-3 py-2 text-gray-500">string</td><td className="px-3 py-2"><span className="bg-green-100 text-green-700 rounded px-1 text-xs">Yes</span></td><td className="px-3 py-2 text-gray-600">e.g. "Hemoglobin", "Fasting Blood Glucose"</td></tr>
            <tr><td className="px-3 py-2 font-mono text-gray-700">value</td><td className="px-3 py-2 text-gray-500">string/number</td><td className="px-3 py-2"><span className="bg-green-100 text-green-700 rounded px-1 text-xs">Yes</span></td><td className="px-3 py-2 text-gray-600">The test result value</td></tr>
            <tr><td className="px-3 py-2 font-mono text-gray-700">unit</td><td className="px-3 py-2 text-gray-500">string</td><td className="px-3 py-2">No</td><td className="px-3 py-2 text-gray-600">e.g. "g/dL", "mg/dL", "mmol/L"</td></tr>
            <tr><td className="px-3 py-2 font-mono text-gray-700">reference_range_low</td><td className="px-3 py-2 text-gray-500">string</td><td className="px-3 py-2">No</td><td className="px-3 py-2 text-gray-600">Lower bound of the normal range</td></tr>
            <tr><td className="px-3 py-2 font-mono text-gray-700">reference_range_high</td><td className="px-3 py-2 text-gray-500">string</td><td className="px-3 py-2">No</td><td className="px-3 py-2 text-gray-600">Upper bound of the normal range</td></tr>
            <tr><td className="px-3 py-2 font-mono text-gray-700">sex</td><td className="px-3 py-2 text-gray-500">string</td><td className="px-3 py-2">No</td><td className="px-3 py-2 text-gray-600">"male", "female", or omit</td></tr>
            <tr><td className="px-3 py-2 font-mono text-gray-700">age</td><td className="px-3 py-2 text-gray-500">number</td><td className="px-3 py-2">No</td><td className="px-3 py-2 text-gray-600">Patient age in years</td></tr>
            <tr><td className="px-3 py-2 font-mono text-gray-700">delivery_method</td><td className="px-3 py-2 text-gray-500">string</td><td className="px-3 py-2">No</td><td className="px-3 py-2 text-gray-600">"email", "sms", "whatsapp", or omit for none</td></tr>
            <tr><td className="px-3 py-2 font-mono text-gray-700">delivery_recipient</td><td className="px-3 py-2 text-gray-500">string</td><td className="px-3 py-2">No</td><td className="px-3 py-2 text-gray-600">Email address or phone number for delivery</td></tr>
          </tbody>
        </table>

        <h4 className="font-semibold text-gray-800 text-sm mt-6 mb-2">Submit Interpretations in Bulk (CSV)</h4>
        <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto">
          <code>POST /api/partner/interpretations/bulk</code>
        </div>
        <p className="text-sm text-gray-600 mb-2">
          Upload a CSV file with headers: <code className="bg-gray-100 px-1 rounded">patient_id,test_name,value,unit,sex,age,reference_range_low,reference_range_high</code>.
          The request must be multipart/form-data with the CSV as the <code className="bg-gray-100 px-1 rounded">file</code> field.
        </p>

        <h4 className="font-semibold text-gray-800 text-sm mt-6 mb-2">Versioned API Endpoint (for external integrators)</h4>
        <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto">
          <code>POST /api/partner/v1/interpretations</code>
        </div>
        <p className="text-sm text-gray-600 mb-2">
          Accepts the same payload as the standard interpretations endpoint but is versioned for stability.
          Use this for production integrations to avoid breaking changes.
        </p>
      </div>
    ),
  },
  {
    id: 'api-hl7',
    title: 'HL7v2 Integration',
    content: (
      <div>
        <p className="mb-3 text-sm text-gray-700">
          For labs and hospital information systems (LIS/HIS) that output HL7v2 messages,
          HealthIntel provides a parsing endpoint that extracts test results from ORU^R01 messages.
        </p>

        <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto">
          <code>POST /api/partner/v1/hl7</code>
        </div>

        <p className="text-sm text-gray-600 mb-2">Request body: raw HL7v2 message as the request body (Content-Type: text/plain or application/hl7-v2).</p>

        <p className="text-sm text-gray-700 mb-2">
          The parser extracts:
        </p>
        <ul className="list-disc pl-6 space-y-1 text-sm text-gray-700 mb-3">
          <li>Patient ID (from PID-3)</li>
          <li>Patient name, date of birth, sex (from PID segment)</li>
          <li>Ordering provider details (from OBR segment)</li>
          <li>Test names and values (from OBX segments)</li>
          <li>Units and reference ranges (from OBX-6 and OBX-7)</li>
        </ul>

        <p className="text-sm text-gray-600 mb-2">
          The response contains the parsed test list and the generated interpretations.
          Each extracted test is automatically processed against verified reference ranges.
        </p>

        <div className="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800 mt-4">
          <strong>Note:</strong> HL7 messages vary between systems. Test the endpoint with sample messages from your
          LIS first. Contact us if specific segment mappings need adjustment for your system.
        </div>
      </div>
    ),
  },
  {
    id: 'patient-results',
    title: 'Patient Results Page (White-Label)',
    content: (
      <div>
        <p className="mb-3 text-sm text-gray-700">
          Each partnership gets a branded public results page at:
        </p>
        <div className="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono mb-3 overflow-x-auto">
          <code>{'GET /r/{provider-slug}?pid=<patient-identifier>'}</code>
        </div>
        <p className="text-sm text-gray-700 mb-3">
          Patients enter their Patient ID or barcode on this page to view all their interpreted results.
          The page displays:
        </p>
        <ul className="list-disc pl-6 space-y-1 text-sm text-gray-700 mb-3">
          <li>The partner's logo (if white-label branding is configured)</li>
          <li>Each test value with reference range and colour-coded status (Normal / High / Low)</li>
          <li>Plain-language interpretation text</li>
          <li>A medical disclaimer</li>
        </ul>
        <p className="text-sm text-gray-600 mb-2">
          Partners can embed this URL on their own website or share it via SMS/email.
          No login is required for patients — they only need their patient identifier.
        </p>
      </div>
    ),
  },
  {
    id: 'delivery',
    title: 'Delivery Methods (Email / SMS / WhatsApp)',
    content: (
      <div>
        <p className="mb-3 text-sm text-gray-700">
          When submitting an interpretation via the API, partners can optionally request delivery to the patient:
        </p>
        <ul className="list-disc pl-6 space-y-1 text-sm text-gray-700 mb-3">
          <li><strong>Email</strong> — Sends a PDF interpretation report as an attachment</li>
          <li><strong>SMS</strong> — Sends a brief text summary via Termii</li>
          <li><strong>WhatsApp</strong> — Sends a formatted message via Termii (if WhatsApp is enabled)</li>
        </ul>
        <p className="text-sm text-gray-600 mb-2">
          Delivery attempts are tracked with retry logic. Admins can monitor delivery health from
          the partnership detail page. Failed deliveries are automatically retried with exponential backoff.
        </p>
        <p className="text-sm text-gray-600 mb-2">
          From the admin panel, you can also manually trigger delivery for any interpretation
          via <em>Admin → Partnerships → [View Partnership] → Interpretations → Deliver</em>.
        </p>
      </div>
    ),
  },
  {
    id: 'invoicing',
    title: 'Invoicing & Pricing',
    content: (
      <div>
        <p className="mb-3 text-sm text-gray-700">
          The billing system tracks every interpretation and generates monthly invoices automatically.
        </p>
        <ul className="list-disc pl-6 space-y-1 text-sm text-gray-700 mb-3">
          <li><strong>Per-report pricing:</strong> Each interpretation costs a fixed rate (configured in the partnership)</li>
          <li><strong>Volume-tiered:</strong> Monthly allowance with overage rates</li>
          <li><strong>Flat monthly:</strong> Unlimited reports for a fixed monthly fee</li>
        </ul>
        <p className="text-sm text-gray-600 mb-2">
          Generate invoices from <em>Admin → Partnerships → [View Partnership] → Invoices → Generate Invoice</em>.
          You can also generate all pending invoices at once from the Invoices page.
        </p>
        <p className="text-sm text-gray-600 mb-2">
          Each invoice is stored with a downloadable PDF proposal/receipt.
        </p>
      </div>
    ),
  },
  {
    id: 'admin-workflow',
    title: 'Admin Day-to-Day Workflow',
    content: (
      <div>
        <p className="mb-3 text-sm text-gray-700">A typical admin workflow looks like this:</p>
        <ol className="list-decimal pl-6 space-y-2 text-sm text-gray-700">
          <li>Monitor <strong>Partner Inquiries</strong> (📨 sidebar) for new partnership requests from the public partnerships page</li>
          <li>Follow up with leads — update inquiry status (new → contacted → converted → closed)</li>
          <li>Create the provider and partnership as described in the Onboarding section above</li>
          <li>Generate an access code and share it with the partner</li>
          <li>Monitor <strong>Partnerships</strong> dashboard for usage stats, monthly report counts, and estimated bills</li>
          <li>Check <strong>Delivery Health</strong> on each partnership for failed SMS/email deliveries</li>
          <li>Generate invoices at month-end from the partnership detail page</li>
          <li>Use <strong>Email Campaigns</strong> to send announcements to partners or users</li>
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
        <p className="text-sm text-gray-500">
          Everything you need to know about running HealthIntel — partner integration, API reference, and admin workflows.
        </p>
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
          If you have questions that aren't covered here, reach out to the development team or check the
          source code in <code className="bg-teal-100 px-1 rounded">app/Http/Controllers/Api/Partner/PartnerInterpretationController.php</code> for detailed API logic.
        </p>
      </div>
    </div>
  );
}