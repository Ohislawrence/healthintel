import React, { useState, useEffect, useRef } from 'react';
import api from '../../lib/api';

const TOKENS = [
  { token: '{{name}}', description: "User's full name" },
  { token: '{{email}}', description: "User's email address" },
  { token: '{{phone}}', description: "User's phone number" },
  { token: '{{credits}}', description: 'Current credit balance' },
  { token: '{{signup_date}}', description: 'Account creation date' },
  { token: '{{dashboard_url}}', description: 'Link to user dashboard' },
];

export default function AdminEmails() {
  const [subject, setSubject] = useState('');
  const [bodyHtml, setBodyHtml] = useState('');
  const [bodyText, setBodyText] = useState('');
  const [roles, setRoles] = useState([]);
  const [hasSubmissions, setHasSubmissions] = useState(null); // null = any
  const [emailVerified, setEmailVerified] = useState(null);
  const [signupFrom, setSignupFrom] = useState('');
  const [signupTo, setSignupTo] = useState('');
  const [userIdsInput, setUserIdsInput] = useState('');

  const [availableRoles, setAvailableRoles] = useState([]);
  const [loadingTokens, setLoadingTokens] = useState(true);

  const [recipientCount, setRecipientCount] = useState(null);
  const [previewing, setPreviewing] = useState(false);

  const [sending, setSending] = useState(false);
  const [sendResult, setSendResult] = useState(null);
  const [sendError, setSendError] = useState(null);

  const [testUserId, setTestUserId] = useState('');
  const [sendingTest, setSendingTest] = useState(false);
  const [testResult, setTestResult] = useState(null);

  const [showPreview, setShowPreview] = useState(false);
  const previewUserRef = useRef(null);

  // Quill editor refs
  const quillRef = useRef(null);
  const quillInstance = useRef(null);
  const [editorReady, setEditorReady] = useState(false);

  useEffect(() => {
    loadTokens();
  }, []);

  // Initialize Quill editor
  useEffect(() => {
    if (editorReady || loadingTokens) return;

    const loadQuill = async () => {
      try {
        // Load Quill CSS if not already loaded
        if (!document.querySelector('link[href*="quill.snow.css"]')) {
          const linkEl = document.createElement('link');
          linkEl.href = 'https://cdn.quilljs.com/1.3.7/quill.snow.css';
          linkEl.rel = 'stylesheet';
          document.head.appendChild(linkEl);
        }

        // Load Quill script if not already loaded
        if (!window.Quill) {
          await new Promise((resolve) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.quilljs.com/1.3.7/quill.min.js';
            script.onload = resolve;
            document.head.appendChild(script);
          });
        }

        if (quillRef.current && !quillInstance.current) {
          const toolbarOptions = [
            [{ 'header': [1, 2, 3, false] }],
            ['bold', 'italic', 'underline'],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['blockquote'],
            ['link', 'image'],
            [{ 'align': [] }],
            ['clean']
          ];

          quillInstance.current = new window.Quill(quillRef.current, {
            theme: 'snow',
            modules: { toolbar: toolbarOptions },
            placeholder: 'Compose your email...',
          });

          quillInstance.current.on('text-change', () => {
            setBodyHtml(quillInstance.current.root.innerHTML);
          });
        }
        setEditorReady(true);
      } catch {
        setEditorReady('fallback');
      }
    };

    loadQuill();
  }, [loadingTokens]);

  async function loadTokens() {
    try {
      setLoadingTokens(true);
      const res = await api.get('/admin/email/tokens');
      // api interceptor returns response.data, which is { success, data: { tokens, roles }, message }
      setAvailableRoles(res.data?.roles || []);
    } catch (e) {
      console.error('Failed to load email tokens', e);
    } finally {
      setLoadingTokens(false);
    }
  }

  function toggleRole(role) {
    setRoles((prev) =>
      prev.includes(role) ? prev.filter((r) => r !== role) : [...prev, role]
    );
  }

  function buildFilters() {
    const filters = {};
    if (roles.length > 0) filters.roles = roles;
    if (hasSubmissions !== null) filters.has_submissions = hasSubmissions;
    if (emailVerified !== null) filters.email_verified = emailVerified;
    if (signupFrom) filters.signup_from = signupFrom;
    if (signupTo) filters.signup_to = signupTo;
    if (userIdsInput.trim()) {
      const ids = userIdsInput.split(',').map((s) => parseInt(s.trim(), 10)).filter((n) => !isNaN(n));
      if (ids.length > 0) filters.user_ids = ids;
    }
    return filters;
  }

  function insertToken(token) {
    if (quillInstance.current) {
      const range = quillInstance.current.getSelection();
      if (range) {
        quillInstance.current.insertText(range.index, token + ' ');
      } else {
        quillInstance.current.insertText(quillInstance.current.getLength() - 1, ' ' + token);
      }
      quillInstance.current.focus();
    } else {
      setBodyHtml((prev) => prev + ' ' + token);
    }
  }

  async function handlePreview() {
    try {
      setPreviewing(true);
      setSendResult(null);
      setSendError(null);
      const filters = buildFilters();
      const res = await api.post('/admin/email/preview', filters);
      setRecipientCount(res.data?.recipient_count ?? res.recipient_count);
    } catch (e) {
      setSendError(e.response?.data?.message || e.message || 'Failed to preview recipients');
    } finally {
      setPreviewing(false);
    }
  }

  async function handleSend() {
    if (!subject.trim() || !bodyHtml.trim()) {
      setSendError('Subject and HTML body are required.');
      return;
    }
    if (!window.confirm('Are you sure you want to send this email campaign? This action cannot be undone.')) return;
    try {
      setSending(true);
      setSendError(null);
      setSendResult(null);
      const payload = {
        subject,
        body_html: bodyHtml,
        body_text: bodyText || undefined,
        ...buildFilters(),
      };
      const res = await api.post('/admin/email/send', payload);
      setSendResult(res.data || res);
      setRecipientCount(null);
    } catch (e) {
      setSendError(e.response?.data?.message || e.message || 'Failed to send email');
    } finally {
      setSending(false);
    }
  }

  async function handleSendTest() {
    if (!testUserId || !subject.trim() || !bodyHtml.trim()) {
      setTestResult({ error: 'Subject, HTML body, and a Test User ID are required.' });
      return;
    }
    try {
      setSendingTest(true);
      setTestResult(null);
      const res = await api.post('/admin/email/send-test', {
        user_id: parseInt(testUserId, 10),
        subject,
        body_html: bodyHtml,
        body_text: bodyText || undefined,
      });
      setTestResult({ success: res.data?.message || res.message });
    } catch (e) {
      setTestResult({ error: e.response?.data?.message || e.message || 'Test send failed' });
    } finally {
      setSendingTest(false);
    }
  }

  function getPreviewHtml() {
    const previewUser = previewUserRef.current || { name: 'John Doe', email: 'john@example.com', phone: '08012345678' };
    let html = bodyHtml
      .replace(/\{\{name\}\}/g, '<strong>' + previewUser.name + '</strong>')
      .replace(/\{\{email\}\}/g, previewUser.email)
      .replace(/\{\{phone\}\}/g, previewUser.phone || 'N/A')
      .replace(/\{\{credits\}\}/g, '3')
      .replace(/\{\{signup_date\}\}/g, 'January 15, 2025')
      .replace(/\{\{dashboard_url\}\}/g, window.location.origin + '/dashboard');
    return html;
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-bold text-gray-900">📧 Email Campaigns</h2>
      </div>

      <p className="text-sm text-gray-500">
        Compose and send personalised emails to users based on role, usage, signup date, and more. Use{' '}
        <code className="bg-gray-100 px-1 py-0.5 rounded text-teal-700">&#123;&#123;token&#125;&#125;</code> placeholders for personalisation.
      </p>

      {/* Token reference */}
      <div className="bg-teal-50 border border-teal-200 rounded-lg p-4">
        <h3 className="text-sm font-semibold text-teal-800 mb-2">Available Placeholder Tokens</h3>
        <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
          {TOKENS.map((t) => (
            <button
              key={t.token}
              type="button"
              onClick={() => insertToken(t.token)}
              className="text-left text-xs bg-white border border-teal-200 rounded px-2 py-1 hover:bg-teal-100 transition-colors"
              title={t.description}
            >
              <code className="text-teal-700 font-mono">{t.token}</code>
              <span className="block text-gray-500">{t.description}</span>
            </button>
          ))}
        </div>
      </div>

      {/* Email Composition */}
      <div className="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <h3 className="font-semibold text-gray-800">Compose Email</h3>

        {/* Subject */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Subject</label>
          <input
            type="text"
            value={subject}
            onChange={(e) => setSubject(e.target.value)}
            placeholder="e.g. Important update for {{name}}"
            className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
          />
        </div>

        {/* HTML Body — Rich Text Editor */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            HTML Body
            <button
              type="button"
              onClick={() => setShowPreview(!showPreview)}
              className="ml-2 text-xs text-teal-600 hover:underline"
            >
              {showPreview ? 'Hide Preview' : 'Show Preview'}
            </button>
          </label>
          {editorReady === 'fallback' ? (
            <textarea
              value={bodyHtml}
              onChange={(e) => setBodyHtml(e.target.value)}
              rows={12}
              placeholder="<p>Hello {{name}},</p><p>Your credit balance is {{credits}}...</p>"
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
            />
          ) : (
            <div className="bg-white rounded-lg border border-gray-300 overflow-hidden">
              <div
                ref={quillRef}
                style={{ minHeight: '300px' }}
              />
            </div>
          )}
        </div>

        {/* Preview */}
        {showPreview && bodyHtml && (
          <div className="border border-gray-200 rounded-lg p-4 bg-gray-50">
            <h4 className="text-xs font-semibold text-gray-500 uppercase mb-2">Preview (sample data)</h4>
            <div
              className="prose prose-sm max-w-none bg-white rounded border border-gray-200 p-4"
              dangerouslySetInnerHTML={{ __html: getPreviewHtml() }}
            />
          </div>
        )}

        {/* Plain Text Body (optional) */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Plain Text Body <span className="text-gray-400 font-normal">(optional)</span>
          </label>
          <textarea
            value={bodyText}
            onChange={(e) => setBodyText(e.target.value)}
            rows={4}
            placeholder="Plain text version for email clients that don't render HTML..."
            className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
          />
        </div>
      </div>

      {/* Targeting Filters */}
      <div className="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <h3 className="font-semibold text-gray-800">Target Recipients</h3>
        <p className="text-xs text-gray-500">
          All filters are optional. Leaving everything blank targets all users.
        </p>

        {/* Roles */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Roles</label>
          <div className="flex flex-wrap gap-2">
            {availableRoles.map((role) => (
              <button
                key={role}
                type="button"
                onClick={() => toggleRole(role)}
                className={`px-3 py-1 text-xs rounded-full border transition-colors ${
                  roles.includes(role)
                    ? 'bg-teal-100 border-teal-300 text-teal-800'
                    : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-50'
                }`}
              >
                {role}
              </button>
            ))}
            {!loadingTokens && availableRoles.length === 0 && (
              <span className="text-xs text-gray-400">No roles found</span>
            )}
          </div>
        </div>

        {/* Has Submissions */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Has Lab Submissions</label>
          <div className="flex gap-2">
            {[
              { value: null, label: 'Any' },
              { value: true, label: 'Yes' },
              { value: false, label: 'No' },
            ].map((opt) => (
              <button
                key={String(opt.value)}
                type="button"
                onClick={() => setHasSubmissions(opt.value)}
                className={`px-3 py-1 text-xs rounded-full border transition-colors ${
                  hasSubmissions === opt.value
                    ? 'bg-teal-100 border-teal-300 text-teal-800'
                    : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-50'
                }`}
              >
                {opt.label}
              </button>
            ))}
          </div>
        </div>

        {/* Email Verified */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Email Verified</label>
          <div className="flex gap-2">
            {[
              { value: null, label: 'Any' },
              { value: true, label: 'Verified' },
              { value: false, label: 'Unverified' },
            ].map((opt) => (
              <button
                key={String(opt.value)}
                type="button"
                onClick={() => setEmailVerified(opt.value)}
                className={`px-3 py-1 text-xs rounded-full border transition-colors ${
                  emailVerified === opt.value
                    ? 'bg-teal-100 border-teal-300 text-teal-800'
                    : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-50'
                }`}
              >
                {opt.label}
              </button>
            ))}
          </div>
        </div>

        {/* Signup Date Range */}
        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Signup From</label>
            <input
              type="date"
              value={signupFrom}
              onChange={(e) => setSignupFrom(e.target.value)}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Signup To</label>
            <input
              type="date"
              value={signupTo}
              onChange={(e) => setSignupTo(e.target.value)}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
            />
          </div>
        </div>

        {/* Specific User IDs */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Specific User IDs <span className="text-gray-400 font-normal">(comma-separated)</span>
          </label>
          <input
            type="text"
            value={userIdsInput}
            onChange={(e) => setUserIdsInput(e.target.value)}
            placeholder="e.g. 1, 2, 3"
            className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
          />
        </div>

        {/* Preview + Send */}
        <div className="flex flex-wrap items-center gap-3 pt-2">
          <button
            type="button"
            onClick={handlePreview}
            disabled={previewing}
            className="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-50"
          >
            {previewing ? 'Counting...' : '🔍 Preview Recipient Count'}
          </button>

          {recipientCount !== null && (
            <span className="text-sm text-teal-700 font-medium">
              {recipientCount} recipient{recipientCount !== 1 ? 's' : ''} matched
            </span>
          )}

          <div className="flex-1" />

          <button
            type="button"
            onClick={handleSend}
            disabled={sending || !subject.trim() || !bodyHtml.trim()}
            className="px-6 py-2 text-sm font-semibold rounded-lg bg-teal-600 text-white hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {sending ? 'Sending...' : '🚀 Send Campaign'}
          </button>
        </div>

        {sendError && (
          <div className="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg p-3">{sendError}</div>
        )}

        {sendResult && (
          <div className="text-sm text-teal-700 bg-teal-50 border border-teal-200 rounded-lg p-3">
            Sent to <strong>{sendResult.sent ?? sendResult.data?.sent}</strong> of{' '}
            <strong>{sendResult.total_recipients ?? sendResult.data?.total_recipients}</strong> recipients.
            {(sendResult.failed > 0 || sendResult.data?.failed > 0) && (
              <span className="text-red-600"> ({(sendResult.failed ?? sendResult.data?.failed ?? 0)} failed)</span>
            )}
          </div>
        )}
      </div>

      {/* Test Send */}
      <div className="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <h3 className="font-semibold text-gray-800">🧪 Send Test Email</h3>
        <p className="text-xs text-gray-500">
          Send the composed email to a single user to preview personalisation.
        </p>
        <div className="flex gap-3 items-end">
          <div className="flex-1">
            <label className="block text-sm font-medium text-gray-700 mb-1">Test User ID</label>
            <input
              type="number"
              value={testUserId}
              onChange={(e) => setTestUserId(e.target.value)}
              placeholder="Enter user ID"
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
            />
          </div>
          <button
            type="button"
            onClick={handleSendTest}
            disabled={sendingTest || !testUserId || !subject.trim() || !bodyHtml.trim()}
            className="px-4 py-2 text-sm font-medium rounded-lg bg-amber-100 text-amber-800 border border-amber-300 hover:bg-amber-200 disabled:opacity-50"
          >
            {sendingTest ? 'Sending...' : 'Send Test'}
          </button>
        </div>
        {testResult && (
          <div
            className={`text-sm rounded-lg p-3 ${
              testResult.error
                ? 'text-red-600 bg-red-50 border border-red-200'
                : 'text-teal-700 bg-teal-50 border border-teal-200'
            }`}
          >
            {testResult.success || testResult.error}
          </div>
        )}
      </div>
    </div>
  );
}