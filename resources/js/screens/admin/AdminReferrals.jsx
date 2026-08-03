import React, { useState, useEffect, useCallback } from 'react';
import api from '../../lib/api';

export default function AdminReferrals() {
  const [tab, setTab] = useState('settings');
  const [settings, setSettings] = useState({ percentage: 10, max_payouts_per_referral: 3, min_payout_threshold_naira: 5000 });
  const [stats, setStats] = useState(null);
  const [earnings, setEarnings] = useState([]);
  const [payouts, setPayouts] = useState([]);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [rejectNote, setRejectNote] = useState('');
  const [rejectId, setRejectId] = useState(null);

  const fetchSettings = useCallback(async () => {
    try {
      const res = await api.get('/admin/referral/settings');
      setSettings(res.data);
    } catch (err) {
      alert('Failed to load settings');
    }
  }, []);

  const fetchStats = useCallback(async () => {
    try {
      const res = await api.get('/admin/referral/stats');
      setStats(res.data);
    } catch (err) {
      alert('Failed to load stats');
    }
  }, []);

  const fetchEarnings = useCallback(async (page = 1) => {
    setLoading(true);
    try {
      const res = await api.get(`/admin/referral/earnings?page=${page}`);
      setEarnings(res.data?.data || res.data || []);
    } catch (err) {
      alert('Failed to load earnings');
    } finally {
      setLoading(false);
    }
  }, []);

  const fetchPayouts = useCallback(async (page = 1) => {
    setLoading(true);
    try {
      const res = await api.get(`/admin/referral/payout-requests?page=${page}`);
      setPayouts(res.data?.data || res.data || []);
    } catch (err) {
      alert('Failed to load payout requests');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchSettings();
    fetchStats();
  }, [fetchSettings, fetchStats]);

  useEffect(() => {
    if (tab === 'earnings') fetchEarnings();
    if (tab === 'payouts') fetchPayouts();
  }, [tab, fetchEarnings, fetchPayouts]);

  const handleSaveSettings = async () => {
    setSaving(true);
    try {
      await api.put('/admin/referral/settings', settings);
      alert('Referral settings updated');
    } catch (err) {
      alert('Failed to update settings');
    } finally {
      setSaving(false);
    }
  };

  const handleApprove = async (id) => {
    try {
      await api.post(`/admin/referral/payout-requests/${id}/approve`);
      alert('Payout approved and marked as paid');
      fetchPayouts();
      fetchStats();
    } catch (err) {
      alert(err.response?.data?.message || 'Failed to approve');
    }
  };

  const handleReject = async () => {
    if (!rejectNote.trim()) {
      alert('Please provide a reason for rejection');
      return;
    }
    try {
      await api.post(`/admin/referral/payout-requests/${rejectId}/reject`, { admin_notes: rejectNote });
      alert('Payout rejected');
      setRejectId(null);
      setRejectNote('');
      fetchPayouts();
      fetchStats();
    } catch (err) {
      alert(err.response?.data?.message || 'Failed to reject');
    }
  };

  const statusBadge = (status) => {
    const colors = {
      pending: 'bg-yellow-100 text-yellow-800',
      paid: 'bg-green-100 text-green-800',
      rejected: 'bg-red-100 text-red-800',
      approved: 'bg-blue-100 text-blue-800',
    };
    return (
      <span className={`px-2 py-1 rounded-full text-xs font-medium ${colors[status] || 'bg-gray-100 text-gray-800'}`}>
        {status.charAt(0).toUpperCase() + status.slice(1)}
      </span>
    );
  };

  const tabs = [
    { key: 'settings', label: 'Settings' },
    { key: 'earnings', label: 'Earnings' },
    { key: 'payouts', label: 'Payout Requests' },
  ];

  return (
    <div className="max-w-6xl mx-auto px-4 py-8">
      <h1 className="text-2xl font-bold text-gray-900 mb-6">Referral Program Management</h1>

      {/* Stats Cards */}
      {stats && (
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p className="text-xs text-gray-500">Total Earnings</p>
            <p className="text-xl font-bold text-emerald-600">₦{stats.total_earnings_naira?.toLocaleString() || 0}</p>
          </div>
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p className="text-xs text-gray-500">Pending</p>
            <p className="text-xl font-bold text-yellow-600">₦{stats.pending_earnings_naira?.toLocaleString() || 0}</p>
          </div>
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p className="text-xs text-gray-500">Paid</p>
            <p className="text-xl font-bold text-blue-600">₦{stats.paid_earnings_naira?.toLocaleString() || 0}</p>
          </div>
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p className="text-xs text-gray-500">Referrers</p>
            <p className="text-xl font-bold text-gray-900">{stats.total_referrers || 0}</p>
          </div>
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p className="text-xs text-gray-500">Pending Payouts</p>
            <p className="text-xl font-bold text-orange-600">{stats.pending_payouts || 0}</p>
          </div>
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p className="text-xs text-gray-500">Total Referrals</p>
            <p className="text-xl font-bold text-gray-900">{stats.total_referrals || 0}</p>
          </div>
        </div>
      )}

      {/* Tabs */}
      <div className="flex gap-1 mb-6 border-b border-gray-200">
        {tabs.map((t) => (
          <button
            key={t.key}
            onClick={() => setTab(t.key)}
            className={`px-4 py-3 text-sm font-medium border-b-2 transition-colors ${
              tab === t.key
                ? 'border-emerald-600 text-emerald-600'
                : 'border-transparent text-gray-500 hover:text-gray-700'
            }`}
          >
            {t.label}
          </button>
        ))}
      </div>

      {/* Settings Tab */}
      {tab === 'settings' && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Referral Configuration</h3>
          <div className="space-y-5 max-w-lg">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Commission Percentage (%)
              </label>
              <input
                type="number"
                min={1}
                max={100}
                value={settings.percentage}
                onChange={(e) => setSettings({ ...settings, percentage: parseInt(e.target.value) || 0 })}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
              />
              <p className="text-xs text-gray-400 mt-1">
                Percentage of referred user's payment that goes to the referrer
              </p>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Max Payouts Per Referred User
              </label>
              <input
                type="number"
                min={1}
                max={100}
                value={settings.max_payouts_per_referral}
                onChange={(e) => setSettings({ ...settings, max_payouts_per_referral: parseInt(e.target.value) || 1 })}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
              />
              <p className="text-xs text-gray-400 mt-1">
                Number of purchases the referrer earns commission for per referred user
              </p>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Minimum Payout Threshold (₦)
              </label>
              <input
                type="number"
                min={100}
                value={settings.min_payout_threshold_naira}
                onChange={(e) => setSettings({ ...settings, min_payout_threshold_naira: parseInt(e.target.value) || 100 })}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
              />
              <p className="text-xs text-gray-400 mt-1">
                Minimum balance before a user can request a payout
              </p>
            </div>
            <button
              onClick={handleSaveSettings}
              disabled={saving}
              className="px-6 py-2.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50 text-sm font-medium"
            >
              {saving ? 'Saving...' : 'Save Settings'}
            </button>
          </div>
        </div>
      )}

      {/* Earnings Tab */}
      {tab === 'earnings' && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">All Referral Earnings</h3>
          {loading ? (
            <div className="flex justify-center py-8">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-600" />
            </div>
          ) : Array.isArray(earnings) && earnings.length === 0 ? (
            <p className="text-gray-400 text-sm py-8 text-center">No earnings recorded yet.</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-gray-200">
                    <th className="text-left py-3 px-3 font-medium text-gray-500">Referrer</th>
                    <th className="text-left py-3 px-3 font-medium text-gray-500">Referred User</th>
                    <th className="text-left py-3 px-3 font-medium text-gray-500">Source</th>
                    <th className="text-left py-3 px-3 font-medium text-gray-500">Commission</th>
                    <th className="text-left py-3 px-3 font-medium text-gray-500">#</th>
                    <th className="text-left py-3 px-3 font-medium text-gray-500">Status</th>
                    <th className="text-left py-3 px-3 font-medium text-gray-500">Date</th>
                  </tr>
                </thead>
                <tbody>
                  {(Array.isArray(earnings) ? earnings : []).map((e) => (
                    <tr key={e.id} className="border-b border-gray-100">
                      <td className="py-3 px-3">{e.user?.name || 'N/A'}</td>
                      <td className="py-3 px-3">{e.referred_user?.name || 'N/A'}</td>
                      <td className="py-3 px-3">₦{e.source_amount_naira?.toLocaleString() || 0}</td>
                      <td className="py-3 px-3 text-emerald-600 font-medium">₦{e.commission_naira?.toLocaleString() || 0}</td>
                      <td className="py-3 px-3">{e.payout_number}</td>
                      <td className="py-3 px-3">{statusBadge(e.status)}</td>
                      <td className="py-3 px-3 text-gray-400">{new Date(e.created_at).toLocaleDateString()}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}

      {/* Payout Requests Tab */}
      {tab === 'payouts' && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Payout Requests</h3>
          {loading ? (
            <div className="flex justify-center py-8">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-600" />
            </div>
          ) : Array.isArray(payouts) && payouts.length === 0 ? (
            <p className="text-gray-400 text-sm py-8 text-center">No payout requests yet.</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-gray-200">
                    <th className="text-left py-3 px-3 font-medium text-gray-500">User</th>
                    <th className="text-left py-3 px-3 font-medium text-gray-500">Amount</th>
                    <th className="text-left py-3 px-3 font-medium text-gray-500">Bank</th>
                    <th className="text-left py-3 px-3 font-medium text-gray-500">Account</th>
                    <th className="text-left py-3 px-3 font-medium text-gray-500">Status</th>
                    <th className="text-left py-3 px-3 font-medium text-gray-500">Date</th>
                    <th className="text-left py-3 px-3 font-medium text-gray-500">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {(Array.isArray(payouts) ? payouts : []).map((p) => (
                    <tr key={p.id} className="border-b border-gray-100">
                      <td className="py-3 px-3">
                        <p className="font-medium">{p.user?.name || 'N/A'}</p>
                        <p className="text-xs text-gray-400">{p.user?.email || ''}</p>
                      </td>
                      <td className="py-3 px-3 font-medium">₦{p.amount_naira?.toLocaleString() || 0}</td>
                      <td className="py-3 px-3">{p.bank_name}</td>
                      <td className="py-3 px-3 text-xs">
                        <p>{p.account_number}</p>
                        <p className="text-gray-400">{p.account_name}</p>
                      </td>
                      <td className="py-3 px-3">
                        {statusBadge(p.status)}
                        {p.admin_notes && <p className="text-xs text-gray-400 mt-1">{p.admin_notes}</p>}
                        {p.processed_by && <p className="text-xs text-gray-400">by {p.processed_by}</p>}
                      </td>
                      <td className="py-3 px-3 text-gray-400">{new Date(p.created_at).toLocaleDateString()}</td>
                      <td className="py-3 px-3">
                        {p.status === 'pending' && (
                          <div className="flex gap-2">
                            <button
                              onClick={() => handleApprove(p.id)}
                              className="px-3 py-1.5 bg-emerald-600 text-white rounded text-xs font-medium hover:bg-emerald-700"
                            >
                              Approve
                            </button>
                            <button
                              onClick={() => { setRejectId(p.id); setRejectNote(''); }}
                              className="px-3 py-1.5 bg-red-600 text-white rounded text-xs font-medium hover:bg-red-700"
                            >
                              Reject
                            </button>
                          </div>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          {/* Reject Modal */}
          {rejectId && (
            <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
              <div className="bg-white rounded-xl shadow-lg max-w-md w-full mx-4 p-6">
                <h3 className="text-xl font-bold text-gray-900 mb-4">Reject Payout Request</h3>
                <p className="text-sm text-gray-500 mb-4">Please provide a reason for rejection.</p>
                <textarea
                  value={rejectNote}
                  onChange={(e) => setRejectNote(e.target.value)}
                  rows={4}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-4"
                  placeholder="Reason for rejection..."
                />
                <div className="flex gap-3">
                  <button
                    onClick={() => { setRejectId(null); setRejectNote(''); }}
                    className="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium"
                  >
                    Cancel
                  </button>
                  <button
                    onClick={handleReject}
                    className="flex-1 px-4 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium"
                  >
                    Reject
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}