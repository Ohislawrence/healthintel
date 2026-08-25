import React, { useState, useEffect, useCallback } from 'react';
import useAuthStore from '../stores/authStore';
import api from '../lib/api';
import { trackEngagement } from '../lib/engagement';

export default function ReferralDashboard() {
  const { user } = useAuthStore();
  const [summary, setSummary] = useState(null);
  const [earnings, setEarnings] = useState([]);
  const [payouts, setPayouts] = useState([]);
  const [bankDetails, setBankDetails] = useState(null);
  const [loading, setLoading] = useState(true);

  // Payout confirm modal
  const [showPayoutConfirm, setShowPayoutConfirm] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  // One-time bank details modal
  const [showBankModal, setShowBankModal] = useState(false);
  const [bankSaving, setBankSaving] = useState(false);
  const [bankForm, setBankForm] = useState({
    bank_name: '',
    account_number: '',
    account_name: '',
  });

  const fetchData = useCallback(async () => {
    setLoading(true);
    try {
      const [summaryRes, earningsRes, payoutsRes, bankRes] = await Promise.all([
        api.get('/referral/earnings/summary'),
        api.get('/referral/earnings'),
        api.get('/referral/payouts'),
        api.get('/referral/bank-details'),
      ]);
      setSummary(summaryRes.data);
      setEarnings(earningsRes.data?.earnings?.data || []);
      setPayouts(payoutsRes.data?.payouts?.data || []);
      setBankDetails(bankRes.data);
    } catch (err) {
      alert('Failed to load referral data');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  const openBankModal = () => {
    setBankForm({
      bank_name: bankDetails?.bank_name || '',
      account_number: bankDetails?.account_number || '',
      account_name: bankDetails?.account_name || '',
    });
    setShowBankModal(true);
  };

  const handleBankSave = async (e) => {
    e.preventDefault();
    setBankSaving(true);
    try {
      const res = await api.post('/referral/bank-details', bankForm);
      setBankDetails({
        ...res.data,
        has_bank_details: true,
      });
      setShowBankModal(false);
      alert('Bank details saved.');
    } catch (err) {
      alert(err.response?.data?.message || err?.message || 'Failed to save bank details');
    } finally {
      setBankSaving(false);
    }
  };

  const handlePayoutSubmit = async () => {
    setSubmitting(true);
    try {
      await api.post('/referral/payout/request', {});
      trackEngagement('referral_payout_requested', 'referral_dashboard', {
        amount_naira: summary?.pending_balance_naira || 0,
      });
      alert('Payout request submitted!');
      setShowPayoutConfirm(false);
      fetchData();
    } catch (err) {
      const message = err.response?.data?.message || err?.message || 'Failed to submit payout request';
      // If bank details are missing, open the bank details modal.
      if (/bank details/i.test(message)) {
        setShowPayoutConfirm(false);
        openBankModal();
        return;
      }
      alert(message);
    } finally {
      setSubmitting(false);
    }
  };

  const copyReferralLink = () => {
    if (!user?.referral_code) return;
    const link = `${window.location.origin}/register?ref=${user.referral_code}`;
    trackEngagement('referral_link_copied', 'referral_dashboard', {
      referrals_total: summary?.total_referrals || 0,
      pending_balance_naira: summary?.pending_balance_naira || 0,
    });

    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(link).then(
        () => alert('Referral link copied!'),
        () => fallbackCopy(link),
      );
    } else {
      fallbackCopy(link);
    }
  };

  const fallbackCopy = (text) => {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.left = '-9999px';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();
    try {
      document.execCommand('copy');
      alert('Referral link copied!');
    } catch {
      alert('Failed to copy. Your link: ' + text);
    }
    document.body.removeChild(textarea);
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-600" />
      </div>
    );
  }

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

  const hasBank = !!bankDetails?.has_bank_details;

  return (
    <div className="max-w-4xl mx-auto px-4 py-8">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-gray-900">Referral Program</h1>
        <p className="text-gray-500 mt-1">
          Share your link and earn commission when your referrals buy credits.
        </p>
      </div>

      {/* Referral Link Card */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-2">Your Referral Link</h3>
        <p className="text-sm text-gray-500 mb-3">
          Share this link after you top up credits or after a successful interpretation for better conversion.
        </p>
        <div className="flex items-center gap-3">
          <code className="flex-1 bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-sm text-gray-700 break-all">
            {`${window.location.origin}/register?ref=${user?.referral_code || '...'}`}
          </code>
          <button
            onClick={copyReferralLink}
            className="px-4 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium whitespace-nowrap"
          >
            Copy Link
          </button>
        </div>
        <p className="text-xs text-gray-400 mt-3">
          Your referral code: <strong>{user?.referral_code || 'Generating...'}</strong>
        </p>

        <div className="mt-4 rounded-lg border border-emerald-100 bg-emerald-50 p-3">
          <p className="text-xs font-bold uppercase tracking-wide text-emerald-700">High-converting share moments</p>
          <p className="text-sm text-emerald-900 mt-1">After a friend asks about your result, after they see your dashboard, or right after they get a successful interpretation.</p>
        </div>
      </div>

      {/* Stats Cards */}
      {summary && (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p className="text-sm text-gray-500">Pending Balance</p>
            <p className="text-2xl font-bold text-emerald-600">₦{summary.pending_balance_naira?.toLocaleString() || 0}</p>
          </div>
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p className="text-sm text-gray-500">Total Earned</p>
            <p className="text-2xl font-bold text-gray-900">₦{summary.total_earnings_naira?.toLocaleString() || 0}</p>
          </div>
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p className="text-sm text-gray-500">Paid Out</p>
            <p className="text-2xl font-bold text-blue-600">₦{summary.paid_earnings_naira?.toLocaleString() || 0}</p>
          </div>
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p className="text-sm text-gray-500">Total Referrals</p>
            <p className="text-2xl font-bold text-gray-900">{summary.total_referrals || 0}</p>
          </div>
        </div>
      )}

      {/* Bank details + payout */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div className="flex items-center justify-between">
          <div>
            <h3 className="text-lg font-semibold text-gray-900">Payout Account</h3>
            <p className="text-sm text-gray-500 mt-1">
              {hasBank
                ? `${bankDetails.bank_name} — ${bankDetails.account_number} (${bankDetails.account_name})`
                : 'Add your bank account to receive payouts.'}
            </p>
          </div>
          <button
            onClick={openBankModal}
            className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium"
          >
            {hasBank ? 'Edit Details' : 'Add Bank Details'}
          </button>
        </div>

        <div className="mt-5 pt-5 border-t border-gray-100 flex items-center justify-between">
          <div>
            <h3 className="text-lg font-semibold text-gray-900">Request Payout</h3>
            <p className="text-sm text-gray-500 mt-1">
              Minimum payout: ₦{summary?.min_payout_threshold_naira?.toLocaleString() || '5,000'}
            </p>
          </div>
          <button
            onClick={() => {
              if (!hasBank) {
                openBankModal();
                return;
              }
              setShowPayoutConfirm(true);
            }}
            disabled={!summary?.can_request_payout}
            className="px-6 py-2.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors text-sm font-medium"
          >
            Request Payout
          </button>
        </div>
      </div>

      {/* Earnings History */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Earnings History</h3>
        {earnings.length === 0 ? (
          <p className="text-gray-400 text-sm py-8 text-center">
            No earnings yet. Share your referral link to start earning!
          </p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-gray-200">
                  <th className="text-left py-3 px-3 font-medium text-gray-500">Referred User</th>
                  <th className="text-left py-3 px-3 font-medium text-gray-500">Source Amount</th>
                  <th className="text-left py-3 px-3 font-medium text-gray-500">Commission</th>
                  <th className="text-left py-3 px-3 font-medium text-gray-500">Rate</th>
                  <th className="text-left py-3 px-3 font-medium text-gray-500">Status</th>
                  <th className="text-left py-3 px-3 font-medium text-gray-500">Date</th>
                </tr>
              </thead>
              <tbody>
                {earnings.map((e) => (
                  <tr key={e.id} className="border-b border-gray-100">
                    <td className="py-3 px-3">{e.referred_user?.name || 'N/A'}</td>
                    <td className="py-3 px-3">₦{e.source_amount_naira?.toLocaleString() || 0}</td>
                    <td className="py-3 px-3 text-emerald-600 font-medium">₦{e.commission_naira?.toLocaleString() || 0}</td>
                    <td className="py-3 px-3">{e.percentage_rate}%</td>
                    <td className="py-3 px-3">{statusBadge(e.status)}</td>
                    <td className="py-3 px-3 text-gray-400">{new Date(e.created_at).toLocaleDateString()}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Payout History */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Payout History</h3>
        {payouts.length === 0 ? (
          <p className="text-gray-400 text-sm py-8 text-center">No payout requests yet.</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-gray-200">
                  <th className="text-left py-3 px-3 font-medium text-gray-500">Amount</th>
                  <th className="text-left py-3 px-3 font-medium text-gray-500">Bank</th>
                  <th className="text-left py-3 px-3 font-medium text-gray-500">Account</th>
                  <th className="text-left py-3 px-3 font-medium text-gray-500">Status</th>
                  <th className="text-left py-3 px-3 font-medium text-gray-500">Date</th>
                </tr>
              </thead>
              <tbody>
                {payouts.map((p) => (
                  <tr key={p.id} className="border-b border-gray-100">
                    <td className="py-3 px-3 font-medium">₦{p.amount_naira?.toLocaleString() || 0}</td>
                    <td className="py-3 px-3">{p.bank_name}</td>
                    <td className="py-3 px-3">{p.account_number} - {p.account_name}</td>
                    <td className="py-3 px-3">
                      {statusBadge(p.status)}
                      {p.admin_notes && <p className="text-xs text-gray-400 mt-1">{p.admin_notes}</p>}
                    </td>
                    <td className="py-3 px-3 text-gray-400">{new Date(p.created_at).toLocaleDateString()}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Bank details modal (one-time setup) */}
      {showBankModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <div className="bg-white rounded-xl shadow-lg max-w-md w-full mx-4 p-6">
            <h3 className="text-xl font-bold text-gray-900 mb-4">
              {hasBank ? 'Edit Bank Details' : 'Add Bank Details'}
            </h3>
            <p className="text-sm text-gray-500 mb-4">
              {hasBank
                ? 'Update the account where your payouts will be sent.'
                : 'Enter your bank account once. We\'ll use it for all future payouts.'}
            </p>
            <form onSubmit={handleBankSave} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                <input
                  type="text"
                  required
                  value={bankForm.bank_name}
                  onChange={(e) => setBankForm({ ...bankForm, bank_name: e.target.value })}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                  placeholder="e.g., Access Bank"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Account Number</label>
                <input
                  type="text"
                  required
                  maxLength={20}
                  value={bankForm.account_number}
                  onChange={(e) => setBankForm({ ...bankForm, account_number: e.target.value.replace(/\D/g, '') })}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                  placeholder="10-digit account number"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Account Name</label>
                <input
                  type="text"
                  required
                  value={bankForm.account_name}
                  onChange={(e) => setBankForm({ ...bankForm, account_name: e.target.value })}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                  placeholder="Account holder name"
                />
              </div>
              <div className="flex gap-3 pt-2">
                <button
                  type="button"
                  onClick={() => setShowBankModal(false)}
                  className="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={bankSaving}
                  className="flex-1 px-4 py-2.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50 text-sm font-medium"
                >
                  {bankSaving ? 'Saving...' : 'Save'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Payout confirm modal */}
      {showPayoutConfirm && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <div className="bg-white rounded-xl shadow-lg max-w-md w-full mx-4 p-6">
            <h3 className="text-xl font-bold text-gray-900 mb-4">Confirm Payout Request</h3>
            <p className="text-sm text-gray-500 mb-4">
              You are about to request a payout of <strong>₦{summary?.pending_balance_naira?.toLocaleString() || 0}</strong> to:
            </p>
            {hasBank && (
              <div className="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-4 text-sm">
                <p><strong>{bankDetails.bank_name}</strong></p>
                <p className="text-gray-600">{bankDetails.account_number}</p>
                <p className="text-gray-600">{bankDetails.account_name}</p>
              </div>
            )}
            <div className="flex gap-3">
              <button
                onClick={() => setShowPayoutConfirm(false)}
                className="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium"
              >
                Cancel
              </button>
              <button
                onClick={handlePayoutSubmit}
                disabled={submitting}
                className="flex-1 px-4 py-2.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50 text-sm font-medium"
              >
                {submitting ? 'Submitting...' : 'Confirm'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}