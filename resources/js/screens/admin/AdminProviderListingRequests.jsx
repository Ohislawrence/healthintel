import React, { useEffect, useState } from 'react';
import api from '../../lib/api';

const STATUS_OPTIONS = ['pending', 'approved', 'rejected'];
const STATUS_STYLES = {
  pending: 'bg-yellow-50 text-yellow-700',
  approved: 'bg-green-50 text-green-700',
  rejected: 'bg-red-50 text-red-700',
};
const TYPE_LABELS = {
  listing: 'Listing request',
  promotion: 'Ad / Sponsorship',
};

export default function AdminProviderListingRequests() {
  const [requests, setRequests] = useState([]);
  const [pagination, setPagination] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState('');
  const [typeFilter, setTypeFilter] = useState('');
  const [search, setSearch] = useState('');

  const [editingId, setEditingId] = useState(null);
  const [editStatus, setEditStatus] = useState('');
  const [editNotes, setEditNotes] = useState('');
  const [saving, setSaving] = useState(false);

  const fetchRequests = async (page = 1) => {
    setLoading(true);
    try {
      const res = await api.get('/admin/provider-listing-requests', {
        params: {
          page,
          status: statusFilter || undefined,
          request_type: typeFilter || undefined,
          search: search || undefined,
        },
      });
      setRequests(res.data.data || []);
      setPagination(res.data.meta || { current_page: 1, last_page: 1, total: 0 });
    } catch (err) {
      console.error('Failed to load listing requests', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchRequests(1);
  }, [statusFilter, typeFilter]);

  const startEdit = (r) => {
    setEditingId(r.id);
    setEditStatus(r.status || 'pending');
    setEditNotes(r.admin_notes || '');
  };

  const cancelEdit = () => {
    setEditingId(null);
    setEditStatus('');
    setEditNotes('');
  };

  const saveEdit = async (id) => {
    setSaving(true);
    try {
      await api.put(`/admin/provider-listing-requests/${id}`, {
        status: editStatus,
        admin_notes: editNotes,
      });
      setRequests((prev) =>
        prev.map((r) =>
          r.id === id ? { ...r, status: editStatus, admin_notes: editNotes } : r
        )
      );
      cancelEdit();
    } catch (err) {
      console.error('Failed to update request', err);
      alert(err?.response?.data?.message || err?.message || 'Failed to update request');
    } finally {
      setSaving(false);
    }
  };

  const handleApprove = async (r) => {
    if (!confirm(`Approve "${r.facility_name}" and create its directory listing?`)) return;
    setSaving(true);
    try {
      await api.put(`/admin/provider-listing-requests/${r.id}`, {
        status: 'approved',
        admin_notes: r.admin_notes || '',
      });
      fetchRequests(pagination.current_page);
    } catch (err) {
      console.error('Failed to approve request', err);
      alert(err?.response?.data?.message || err?.message || 'Failed to approve request');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-2xl font-bold text-gray-900">Listing & Ad Requests</h2>
        <span className="text-sm text-gray-500">{pagination.total || 0} total</span>
      </div>

      {/* Filters */}
      <div className="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4 mb-4">
        <div className="flex-1 min-w-[200px]">
          <label className="block text-xs font-medium text-gray-500 mb-1">Search</label>
          <input
            className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            onKeyDown={(e) => e.key === 'Enter' && fetchRequests(1)}
            placeholder="Facility, contact, email, city…"
          />
        </div>
        <div>
          <label className="block text-xs font-medium text-gray-500 mb-1">Type</label>
          <select
            className="rounded border border-gray-300 px-3 py-1.5 text-sm"
            value={typeFilter}
            onChange={(e) => { setTypeFilter(e.target.value); setPagination((p) => ({ ...p, current_page: 1 })); }}
          >
            <option value="">All types</option>
            <option value="listing">Listing</option>
            <option value="promotion">Ad / Sponsorship</option>
          </select>
        </div>
        <div>
          <label className="block text-xs font-medium text-gray-500 mb-1">Status</label>
          <select
            className="rounded border border-gray-300 px-3 py-1.5 text-sm"
            value={statusFilter}
            onChange={(e) => { setStatusFilter(e.target.value); setPagination((p) => ({ ...p, current_page: 1 })); }}
          >
            <option value="">All statuses</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>
        <button
          onClick={() => fetchRequests(1)}
          className="rounded border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50"
        >
          Search
        </button>
      </div>

      <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
        {loading ? (
          <div className="flex justify-center py-12">
            <div className="h-6 w-6 animate-spin rounded-full border-2 border-teal-500 border-t-transparent" />
          </div>
        ) : requests.length === 0 ? (
          <div className="text-center py-12 text-gray-500">No listing requests yet.</div>
        ) : (
          <table className="w-full text-left text-sm">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-4 py-3 font-medium text-gray-600">Type</th>
                <th className="px-4 py-3 font-medium text-gray-600">Facility</th>
                <th className="px-4 py-3 font-medium text-gray-600">Contact</th>
                <th className="px-4 py-3 font-medium text-gray-600">Location</th>
                <th className="px-4 py-3 font-medium text-gray-600">Ad details</th>
                <th className="px-4 py-3 font-medium text-gray-600">Status</th>
                <th className="px-4 py-3 font-medium text-gray-600 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {requests.map((r) => (
                <tr key={r.id} className="hover:bg-gray-50 align-top">
                  <td className="px-4 py-3">
                    <span className="text-xs font-semibold text-gray-600">{TYPE_LABELS[r.request_type] || r.request_type}</span>
                  </td>
                  <td className="px-4 py-3">
                    <div className="font-medium text-gray-900">{r.facility_name}</div>
                    <div className="text-xs text-gray-500 uppercase">{r.type}</div>
                    {r.specialty && <div className="text-xs text-gray-400">{r.specialty}</div>}
                  </td>
                  <td className="px-4 py-3">
                    <div className="text-gray-700">{r.contact_name}</div>
                    <div className="text-xs text-gray-500">{r.contact_email}</div>
                    {r.contact_phone && <div className="text-xs text-gray-400">{r.contact_phone}</div>}
                  </td>
                  <td className="px-4 py-3 text-gray-600 text-xs">
                    {r.address && <div>{r.address}</div>}
                    {r.city && <div>{r.city}, {r.state}</div>}
                  </td>
                  <td className="px-4 py-3 text-gray-600 text-xs">
                    {r.request_type === 'promotion' ? (
                      <>
                        <div>{r.promotion_plan || 'Ad placement'}</div>
                        {r.promotion_budget_kobo ? <div>₦{(r.promotion_budget_kobo / 100).toLocaleString()}</div> : null}
                        {r.promotion_duration_days ? <div>{r.promotion_duration_days} days</div> : null}
                      </>
                    ) : (
                      <span className="text-gray-300">—</span>
                    )}
                  </td>
                  <td className="px-4 py-3">
                    {editingId === r.id ? (
                      <select
                        value={editStatus}
                        onChange={(e) => setEditStatus(e.target.value)}
                        className="rounded border border-gray-300 px-2 py-1 text-xs font-medium"
                      >
                        {STATUS_OPTIONS.map((s) => (
                          <option key={s} value={s}>{s}</option>
                        ))}
                      </select>
                    ) : (
                      <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${STATUS_STYLES[r.status] || 'bg-gray-50 text-gray-600'}`}>
                        {r.status}
                      </span>
                    )}
                  </td>
                  <td className="px-4 py-3 text-right whitespace-nowrap">
                    {editingId === r.id ? (
                      <div className="flex items-center justify-end gap-2">
                        <button
                          onClick={() => saveEdit(r.id)}
                          disabled={saving}
                          className="rounded-lg px-3 py-1 text-xs font-medium text-white bg-teal-600 hover:bg-teal-700 disabled:opacity-50"
                        >
                          {saving ? 'Saving…' : 'Save'}
                        </button>
                        <button
                          onClick={cancelEdit}
                          className="rounded-lg px-3 py-1 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200"
                        >
                          Cancel
                        </button>
                      </div>
                    ) : (
                      <div className="flex items-center justify-end gap-2">
                        {r.status !== 'approved' && (
                          <button
                            onClick={() => handleApprove(r)}
                            disabled={saving}
                            className="rounded-lg px-3 py-1 text-xs font-medium text-white bg-green-600 hover:bg-green-700 disabled:opacity-50"
                          >
                            Approve
                          </button>
                        )}
                        <button
                          onClick={() => startEdit(r)}
                          className="rounded-lg px-3 py-1 text-xs font-medium text-teal-700 bg-teal-50 hover:bg-teal-100"
                        >
                          Edit
                        </button>
                      </div>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}

        {editingId && (
          <div className="px-4 py-4 bg-gray-50 border-t border-gray-200">
            <label className="block text-xs font-medium text-gray-600 mb-1">Admin Notes</label>
            <textarea
              value={editNotes}
              onChange={(e) => setEditNotes(e.target.value)}
              rows={2}
              className="w-full rounded border border-gray-300 px-3 py-2 text-sm"
              placeholder="Add internal notes…"
            />
          </div>
        )}

        {pagination.last_page > 1 && (
          <div className="px-4 py-3 bg-gray-50 border-t border-gray-200 flex justify-center gap-2">
            {Array.from({ length: pagination.last_page }, (_, i) => i + 1).map((page) => (
              <button
                key={page}
                onClick={() => fetchRequests(page)}
                className={`rounded px-3 py-1 text-xs font-medium ${
                  page === pagination.current_page
                    ? 'bg-teal-600 text-white'
                    : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'
                }`}
              >
                {page}
              </button>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}