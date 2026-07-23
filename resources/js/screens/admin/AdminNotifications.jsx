import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';

export default function AdminNotifications() {
    const [page, setPage] = useState(1);
    const [showForm, setShowForm] = useState(false);
    const [form, setForm] = useState({ title: '', body: '', target: 'all', user_ids: [] });
    const queryClient = useQueryClient();

    const { data, isLoading } = useQuery({
        queryKey: ['admin-notifications', page],
        queryFn: () => api.get('/admin/notifications', { params: { page } }),
    });

    const sendMutation = useMutation({
        mutationFn: (payload) => api.post('/admin/notifications', payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['admin-notifications'] });
            setShowForm(false);
            setForm({ title: '', body: '', target: 'all', user_ids: [] });
        },
    });

    const notifications = data?.data || [];
    const pagination = data?.meta || {};

    const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h2 className="text-xl font-semibold text-gray-900">Push Notifications</h2>
                <button onClick={() => setShowForm(!showForm)} className="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">
                    {showForm ? 'Cancel' : '+ New Notification'}
                </button>
            </div>

            {showForm && (
                <div className="rounded-xl border border-teal-200 bg-teal-50 p-5 space-y-3">
                    <h3 className="text-sm font-semibold text-teal-800">Send Push Notification</h3>
                    <input className="w-full rounded border border-gray-300 px-3 py-2 text-sm" placeholder="Title" value={form.title} onChange={e => set('title', e.target.value)} />
                    <textarea className="w-full rounded border border-gray-300 px-3 py-2 text-sm" placeholder="Body" rows={3} value={form.body} onChange={e => set('body', e.target.value)} />
                    <select className="rounded border border-gray-300 px-3 py-1.5 text-sm" value={form.target} onChange={e => set('target', e.target.value)}>
                        <option value="all">All Users</option>
                        <option value="users">All Non-Admin Users</option>
                    </select>
                    <button
                        onClick={() => sendMutation.mutate(form)}
                        disabled={sendMutation.isPending || !form.title.trim() || !form.body.trim()}
                        className="rounded-lg bg-teal-600 px-5 py-2 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50"
                    >
                        {sendMutation.isPending ? 'Sending...' : 'Send Notification'}
                    </button>
                    {sendMutation.isError && <p className="text-xs text-red-600">Failed to send. Try again.</p>}
                </div>
            )}

            {isLoading ? <div className="h-20 animate-pulse rounded-xl bg-gray-100" /> : (
                <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left">
                            <tr>
                                <th className="px-3 py-3 font-medium text-gray-500">Title</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Body</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Target</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Sent</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {notifications.map((n) => (
                                <tr key={n.id} className="hover:bg-gray-50">
                                    <td className="px-3 py-2 font-medium text-gray-900">{n.title}</td>
                                    <td className="px-3 py-2 text-gray-500 max-w-[300px] truncate">{n.body}</td>
                                    <td className="px-3 py-2"><span className="rounded bg-gray-100 px-2 py-0.5 text-xs">{n.target}</span></td>
                                    <td className="px-3 py-2 text-xs text-gray-400">{n.sent_at ? new Date(n.sent_at).toLocaleString() : '—'}</td>
                                </tr>
                            ))}
                            {notifications.length === 0 && <tr><td colSpan={4} className="px-3 py-8 text-center text-gray-400">No notifications sent yet.</td></tr>}
                        </tbody>
                    </table>
                </div>
            )}
            {pagination?.last_page > 1 && (
                <div className="flex justify-center gap-2">
                    {Array.from({ length: pagination.last_page }, (_, i) => i + 1).map(p => (
                        <button key={p} onClick={() => setPage(p)} className={`rounded px-3 py-1 text-xs ${page === p ? 'bg-teal-600 text-white' : 'bg-white border text-gray-600'}`}>{p}</button>
                    ))}
                </div>
            )}
        </div>
    );
}