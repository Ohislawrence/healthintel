import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';

export default function AdminAuditLog() {
    const [page, setPage] = useState(1);

    const { data, isLoading } = useQuery({
        queryKey: ['admin-audit-log', page],
        queryFn: () => api.get('/admin/audit-log', { params: { page } }),
    });

    const logs = data?.data || [];
    const pagination = data?.meta || {};

    const actionColors = {
        grant_credits: 'bg-green-100 text-green-700',
        update_panel: 'bg-blue-100 text-blue-700',
        create_provider: 'bg-purple-100 text-purple-700',
        update_provider: 'bg-purple-100 text-purple-700',
        create_package: 'bg-amber-100 text-amber-700',
        update_package: 'bg-amber-100 text-amber-700',
    };

    return (
        <div className="space-y-6">
            <h2 className="text-xl font-semibold text-gray-900">Audit Log</h2>
            {isLoading ? <div className="h-20 animate-pulse rounded-xl bg-gray-100" /> : (
                <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left">
                            <tr>
                                <th className="px-3 py-3 font-medium text-gray-500">Admin</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Action</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Target</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Details</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Date</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {logs.map((l) => {
                                let meta = {};
                                try { meta = JSON.parse(l.metadata || '{}'); } catch {}
                                return (
                                    <tr key={l.id} className="hover:bg-gray-50">
                                        <td className="px-3 py-2 font-medium text-gray-900">{l.admin_name || 'System'}</td>
                                        <td className="px-3 py-2">
                                            <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${actionColors[l.action] || 'bg-gray-100 text-gray-600'}`}>
                                                {l.action}
                                            </span>
                                        </td>
                                        <td className="px-3 py-2 text-gray-500">{l.target_type}#{l.target_id}</td>
                                        <td className="px-3 py-2 text-xs text-gray-400 max-w-[200px] truncate">
                                            {meta.target_name || meta.credits_granted ? `${meta.target_name || ''} ${meta.credits_granted ? `+${meta.credits_granted} credits` : ''}` : '—'}
                                        </td>
                                        <td className="px-3 py-2 text-xs text-gray-400">{new Date(l.created_at).toLocaleString()}</td>
                                    </tr>
                                );
                            })}
                            {logs.length === 0 && <tr><td colSpan={5} className="px-3 py-8 text-center text-gray-400">No audit entries yet.</td></tr>}
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