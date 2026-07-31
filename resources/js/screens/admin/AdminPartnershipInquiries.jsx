import React, { useEffect, useState } from 'react';
import api from '../../lib/api';

const STATUS_OPTIONS = ['new', 'contacted', 'converted', 'closed'];
const STATUS_STYLES = {
    new: 'bg-blue-50 text-blue-700',
    contacted: 'bg-yellow-50 text-yellow-700',
    converted: 'bg-green-50 text-green-700',
    closed: 'bg-gray-50 text-gray-600',
};

export default function AdminPartnershipInquiries() {
    const [inquiries, setInquiries] = useState([]);
    const [pagination, setPagination] = useState({ current_page: 1, last_page: 1, total: 0 });
    const [loading, setLoading] = useState(true);
    const [editingId, setEditingId] = useState(null);
    const [editStatus, setEditStatus] = useState('');
    const [editNotes, setEditNotes] = useState('');
    const [saving, setSaving] = useState(false);

    const fetchInquiries = async (page = 1) => {
        setLoading(true);
        try {
            const res = await api.get('/admin/partnership-inquiries', { params: { page } });
            setInquiries(res.data.data || []);
            setPagination(res.data.meta || { current_page: 1, last_page: 1, total: 0 });
        } catch (err) {
            console.error('Failed to load partnership inquiries', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => { fetchInquiries(); }, []);

    const startEdit = (inquiry) => {
        setEditingId(inquiry.id);
        setEditStatus(inquiry.status || 'new');
        setEditNotes(inquiry.admin_notes || '');
    };

    const cancelEdit = () => {
        setEditingId(null);
        setEditStatus('');
        setEditNotes('');
    };

    const saveEdit = async (id) => {
        setSaving(true);
        try {
            await api.put(`/admin/partnership-inquiries/${id}`, {
                status: editStatus,
                admin_notes: editNotes,
            });
            setInquiries(prev => prev.map(i => i.id === id ? {
                ...i,
                status: editStatus,
                admin_notes: editNotes,
            } : i));
            cancelEdit();
        } catch (err) {
            console.error('Failed to update inquiry', err);
        } finally {
            setSaving(false);
        }
    };

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-900">Partnership Inquiries</h2>
                <span className="text-sm text-gray-500">{pagination.total || 0} total</span>
            </div>

            <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                {loading ? (
                    <div className="flex justify-center py-12">
                        <div className="h-6 w-6 animate-spin rounded-full border-2 border-teal-500 border-t-transparent" />
                    </div>
                ) : inquiries.length === 0 ? (
                    <div className="text-center py-12 text-gray-500">No partnership inquiries yet.</div>
                ) : (
                    <table className="w-full text-left text-sm">
                        <thead className="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th className="px-6 py-3 font-medium text-gray-600">Facility</th>
                                <th className="px-6 py-3 font-medium text-gray-600">Contact</th>
                                <th className="px-6 py-3 font-medium text-gray-600">Volume</th>
                                <th className="px-6 py-3 font-medium text-gray-600">Message</th>
                                <th className="px-6 py-3 font-medium text-gray-600">Status</th>
                                <th className="px-6 py-3 font-medium text-gray-600">Date</th>
                                <th className="px-6 py-3 font-medium text-gray-600 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {inquiries.map((inq) => (
                                <tr key={inq.id} className="hover:bg-gray-50">
                                    <td className="px-6 py-4">
                                        <div className="font-medium text-gray-900">{inq.facility_name}</div>
                                    </td>
                                    <td className="px-6 py-4">
                                        <div className="text-gray-700">{inq.contact_name}</div>
                                        <div className="text-xs text-gray-500">{inq.contact_email}</div>
                                        {inq.contact_phone && (
                                            <div className="text-xs text-gray-400">{inq.contact_phone}</div>
                                        )}
                                    </td>
                                    <td className="px-6 py-4 text-gray-600">{inq.estimated_volume || '—'}</td>
                                    <td className="px-6 py-4 max-w-xs">
                                        <div className="text-gray-600 truncate" title={inq.message}>
                                            {inq.message || '—'}
                                        </div>
                                    </td>
                                    <td className="px-6 py-4">
                                        {editingId === inq.id ? (
                                            <select
                                                value={editStatus}
                                                onChange={e => setEditStatus(e.target.value)}
                                                className="rounded border border-gray-300 px-2 py-1 text-xs font-medium"
                                            >
                                                {STATUS_OPTIONS.map(s => (
                                                    <option key={s} value={s}>{s}</option>
                                                ))}
                                            </select>
                                        ) : (
                                            <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${STATUS_STYLES[inq.status] || 'bg-gray-50 text-gray-600'}`}>
                                                {inq.status}
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-6 py-4 text-gray-500 text-xs">
                                        {new Date(inq.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        {editingId === inq.id ? (
                                            <div className="flex items-center justify-end gap-2">
                                                <button
                                                    onClick={() => saveEdit(inq.id)}
                                                    disabled={saving}
                                                    className="rounded-lg px-3 py-1 text-xs font-medium text-white bg-teal-600 hover:bg-teal-700 disabled:opacity-50"
                                                >
                                                    {saving ? 'Saving...' : 'Save'}
                                                </button>
                                                <button
                                                    onClick={cancelEdit}
                                                    className="rounded-lg px-3 py-1 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200"
                                                >
                                                    Cancel
                                                </button>
                                            </div>
                                        ) : (
                                            <button
                                                onClick={() => startEdit(inq)}
                                                className="rounded-lg px-3 py-1 text-xs font-medium text-teal-700 bg-teal-50 hover:bg-teal-100 transition-colors"
                                            >
                                                Edit
                                            </button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}

                {editingId && (
                    <div className="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <label className="block text-xs font-medium text-gray-600 mb-1">Admin Notes</label>
                        <textarea
                            value={editNotes}
                            onChange={e => setEditNotes(e.target.value)}
                            rows={2}
                            className="w-full rounded border border-gray-300 px-3 py-2 text-sm"
                            placeholder="Add internal notes..."
                        />
                    </div>
                 )}

                {pagination.last_page > 1 && (
                    <div className="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-center gap-2">
                        {Array.from({ length: pagination.last_page }, (_, i) => i + 1).map((page) => (
                            <button
                                key={page}
                                onClick={() => fetchInquiries(page)}
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