import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';

export default function AdminPartners() {
    const [page, setPage] = useState(1);

    const { data, isLoading } = useQuery({
        queryKey: ['admin-partners', page],
        queryFn: () => api.get('/admin/partners', { params: { page } }),
    });

    const partners = data?.data || [];
    const pagination = data?.meta || {};

    return (
        <div className="space-y-6">
            <h2 className="text-xl font-semibold text-gray-900">Partner Providers</h2>
            <p className="text-sm text-gray-500">Providers with affiliate or sponsored status and their 30-day referral click counts.</p>

            {isLoading ? <div className="h-20 animate-pulse rounded-xl bg-gray-100" /> : (
                <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left">
                            <tr>
                                <th className="px-3 py-3 font-medium text-gray-500">Name</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Type</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Status</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Clicks (30d)</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Active</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {partners.map((p) => (
                                <tr key={p.id} className="hover:bg-gray-50">
                                    <td className="px-3 py-2 font-medium text-gray-900">{p.name}</td>
                                    <td className="px-3 py-2"><span className="uppercase text-xs bg-gray-100 rounded px-2 py-0.5">{p.type}</span></td>
                                    <td className="px-3 py-2">
                                        <span className={`text-xs font-medium ${p.partner_status === 'sponsored' ? 'text-indigo-600' : 'text-amber-600'}`}>
                                            {p.partner_status}
                                        </span>
                                    </td>
                                    <td className="px-3 py-2 font-medium text-teal-600">{p.clicks_30d || 0}</td>
                                    <td className="px-3 py-2">{p.is_active ? '🟢' : '🔴'}</td>
                                </tr>
                            ))}
                            {partners.length === 0 && <tr><td colSpan={5} className="px-3 py-8 text-center text-gray-400">No partner providers.</td></tr>}
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