import React, { useEffect, useState } from 'react';
import usePartnerAuthStore from '../../stores/partnerAuthStore';

const STATUS_STYLES = {
  pending: 'bg-yellow-50 text-yellow-700',
  approved: 'bg-green-50 text-green-700',
  rejected: 'bg-red-50 text-red-700',
};

export default function PartnerAds() {
  const { apiGet, apiPost, provider } = usePartnerAuthStore();
  const [requests, setRequests] = useState([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);

  const [form, setForm] = useState({
    promotion_plan: 'sponsored_banner',
    promotion_budget_naira: '',
    promotion_duration_days: '',
    message: '',
  });

  useEffect(() => {
    loadRequests();
  }, []);

  const loadRequests = async () => {
    try {
      setLoading(true);
      const data = await apiGet('/listing-requests');
      setRequests(data.data?.requests || data.requests || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setError(null);
    setSuccess(null);
    try {
      await apiPost('/promotion-request', {
        promotion_plan: form.promotion_plan,
        promotion_budget_naira: form.promotion_budget_naira || null,
        promotion_duration_days: form.promotion_duration_days || null,
        message: form.message,
      });
      setSuccess('Your ad request has been submitted. Our team will review it and contact you shortly.');
      setForm({ promotion_plan: 'sponsored_banner', promotion_budget_naira: '', promotion_duration_days: '', message: '' });
      loadRequests();
    } catch (err) {
      setError(err.message);
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-xl font-bold text-gray-900">Advertise & Sponsorships</h2>
        <p className="text-sm text-gray-500 mt-1">
          Reach more patients by sponsoring your listing or running a banner campaign. Our team will contact you with options.
        </p>
      </div>

      {success && (
        <div className="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">{success}</div>
      )}
      {error && (
        <div className="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">{error}</div>
      )}

      <form onSubmit={handleSubmit} className="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <h3 className="text-sm font-semibold text-gray-700">Request an ad placement</h3>

        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Placement</label>
            <select
              className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none bg-white"
              value={form.promotion_plan}
              onChange={(e) => setForm((f) => ({ ...f, promotion_plan: e.target.value }))}
            >
              <option value="sponsored_banner">Sponsored banner (home & directory)</option>
              <option value="priority_listing">Priority directory listing</option>
              <option value="custom">Custom campaign</option>
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Budget (₦, optional)</label>
            <input
              type="number"
              min="0"
              className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none"
              value={form.promotion_budget_naira}
              onChange={(e) => setForm((f) => ({ ...f, promotion_budget_naira: e.target.value }))}
              placeholder="e.g., 50000"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Duration (days, optional)</label>
            <input
              type="number"
              min="1"
              max="365"
              className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none"
              value={form.promotion_duration_days}
              onChange={(e) => setForm((f) => ({ ...f, promotion_duration_days: e.target.value }))}
              placeholder="e.g., 30"
            />
          </div>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Message (optional)</label>
          <textarea
            className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none"
            rows={3}
            value={form.message}
            onChange={(e) => setForm((f) => ({ ...f, message: e.target.value }))}
            placeholder="Tell us your goals, target audience, or any questions…"
          />
        </div>

        <button
          type="submit"
          disabled={submitting}
          className="rounded-lg bg-teal-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50 transition-colors"
        >
          {submitting ? 'Submitting…' : 'Submit ad request'}
        </button>
      </form>

      {/* Request history */}
      <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100">
          <h3 className="text-sm font-semibold text-gray-700">Your requests</h3>
        </div>
        {loading ? (
          <div className="flex justify-center py-10">
            <div className="h-6 w-6 animate-spin rounded-full border-2 border-teal-500 border-t-transparent" />
          </div>
        ) : requests.length === 0 ? (
          <p className="text-sm text-gray-400 text-center py-10">No ad requests yet.</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left text-xs text-gray-400 uppercase">
                  <th className="px-5 py-3">Type</th>
                  <th className="px-5 py-3">Plan</th>
                  <th className="px-5 py-3">Budget</th>
                  <th className="px-5 py-3">Status</th>
                  <th className="px-5 py-3">Date</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {requests.map((r) => (
                  <tr key={r.id} className="hover:bg-gray-50">
                    <td className="px-5 py-3 text-gray-700 capitalize">{r.request_type}</td>
                    <td className="px-5 py-3 text-gray-600">{r.promotion_plan || '—'}</td>
                    <td className="px-5 py-3 text-gray-600">
                      {r.promotion_budget_kobo ? `₦${(r.promotion_budget_kobo / 100).toLocaleString()}` : '—'}
                    </td>
                    <td className="px-5 py-3">
                      <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${STATUS_STYLES[r.status] || 'bg-gray-50 text-gray-500'}`}>
                        {r.status}
                      </span>
                    </td>
                    <td className="px-5 py-3 text-gray-500 text-xs">
                      {new Date(r.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}