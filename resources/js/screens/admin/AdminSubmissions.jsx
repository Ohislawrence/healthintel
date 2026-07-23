import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';

export default function AdminSubmissions() {
    const [page, setPage] = useState(1);
    const [filter, setFilter] = useState('all'); // all, panel, pdf

    const { data, isLoading } = useQuery({
        queryKey: ['admin-submissions', page, filter],
        queryFn: () => {
            const endpoint = filter === 'pdf' ? '/admin/pdf-submissions' : '/admin/submissions';
            return api.get(endpoint, { params: { page } });
        },
    });

    const submissions = data?.data || [];
    const pagination = data?.meta || {};

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h2 className="text-xl font-semibold text-gray-900">Lab Submissions</h2>
                <div className="flex gap-2">
                    {['all', 'panel', 'pdf'].map(f => (
                        <button key={f} onClick={() => { setFilter(f); setPage(1); }}
                            className={`rounded-lg px-3 py-1.5 text-xs font-semibold ${filter === f ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}`}>
                            {f === 'all' ? 'All' : f === 'pdf' ? 'PDF Only' : 'Panel Only'}
                        </button>
                    ))}
                </div>
            </div>

            {isLoading ? <div className="h-20 animate-pulse rounded-xl bg-gray-100" /> : (
                <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left">
                            <tr>
                                <th className="px-3 py-3 font-medium text-gray-500">User</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Type</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Panel / File</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Credits</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Status</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Date</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {submissions.map((s) => (
                                <tr key={s.id} className="hover:bg-gray-50">
                                    <td className="px-3 py-2 font-medium text-gray-900">{s.user?.name || '—'}</td>
                                    <td className="px-3 py-2">
                                        <span className={`rounded px-2 py-0.5 text-xs font-medium ${s.submission_type === 'pdf' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'}`}>
                                            {s.submission_type === 'pdf' ? 'PDF' : 'Panel'}
                                        </span>
                                    </td>
                                    <td className="px-3 py-2 text-gray-500 max-w-[200px] truncate">
                                        {s.test_panel?.name || (s.pdf_report_url ? s.pdf_report_url.split('/').pop() : '—')}
                                    </td>
                                    <td className="px-3 py-2 text-gray-500">{s.credits_used}</td>
                                    <td className="px-3 py-2">
                                        <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                                            s.interpretation?.status === 'completed' ? 'bg-green-100 text-green-700' :
                                            s.interpretation?.status === 'failed' ? 'bg-red-100 text-red-700' :
                                            'bg-yellow-100 text-yellow-700'
                                        }`}>{s.interpretation?.status || 'pending'}</span>
                                    </td>
                                    <td className="px-3 py-2 text-xs text-gray-400">
                                        {s.submitted_at ? new Date(s.submitted_at).toLocaleDateString() : '—'}
                                    </td>
                                </tr>
                            ))}
                            {submissions.length === 0 && <tr><td colSpan={6} className="px-3 py-8 text-center text-gray-400">No submissions found.</td></tr>}
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