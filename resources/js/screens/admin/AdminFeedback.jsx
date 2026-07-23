import React, { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../lib/api';

export default function AdminFeedback() {
    const [page, setPage] = useState(1);

    const { data, isLoading, refetch } = useQuery({
        queryKey: ['admin-feedback', page],
        queryFn: () => api.get('/admin/feedback', { params: { page } }),
    });

    const updateMutation = useMutation({
        mutationFn: ({ id, payload }) => api.put(`/admin/feedback/${id}`, payload),
        onSuccess: () => refetch(),
    });

    const feedback = data?.data || [];
    const pagination = data?.meta || {};

    const statusOptions = ['new', 'reviewed', 'resolved'];
    const statusColors = { new: 'bg-red-100 text-red-700', reviewed: 'bg-yellow-100 text-yellow-700', resolved: 'bg-green-100 text-green-700' };

    return (
        <div className="space-y-6">
            <h2 className="text-xl font-semibold text-gray-900">User Feedback</h2>
            {isLoading ? <div className="h-20 animate-pulse rounded-xl bg-gray-100" /> : (
                <div className="space-y-4">
                    {feedback.map((f) => (
                        <div key={f.id} className="rounded-xl border border-gray-200 bg-white p-4">
                            <div className="flex items-start justify-between">
                                <div>
                                    <p className="font-semibold text-gray-900">{f.user?.name || 'Anonymous'}</p>
                                    <p className="text-xs text-gray-400">{f.user?.email} · {new Date(f.created_at).toLocaleDateString()}</p>
                                </div>
                                <select
                                    value={f.status || 'new'}
                                    onChange={(e) => updateMutation.mutate({ id: f.id, payload: { status: e.target.value } })}
                                    className={`rounded-full px-3 py-1 text-xs font-medium border-0 ${statusColors[f.status] || statusColors.new}`}
                                >
                                    {statusOptions.map(s => <option key={s} value={s}>{s}</option>)}
                                </select>
                            </div>
                            <p className="mt-3 text-sm text-gray-700">{f.message || f.content || 'No message'}</p>
                            {f.category && <span className="mt-2 inline-block rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-500">{f.category}</span>}
                        </div>
                    ))}
                    {feedback.length === 0 && <p className="text-center text-gray-400 py-8">No feedback yet.</p>}
                </div>
            )}
            {pagination?.last_page > 1 && (
                <div className="flex justify-center gap-2">
                    {Array.from({ length: pagination.last_page }, (_, i) => i + 1).map((p) => (
                        <button key={p} onClick={() => setPage(p)} className={`rounded px-3 py-1 text-xs ${page === p ? 'bg-teal-600 text-white' : 'bg-white border text-gray-600'}`}>{p}</button>
                    ))}
                </div>
            )}
        </div>
    );
}