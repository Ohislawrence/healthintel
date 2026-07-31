import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../../lib/api';

export default function AdminPartnerships() {
    const [partnerships, setPartnerships] = useState([]);
    const [pagination, setPagination] = useState({ current_page: 1, last_page: 1, total: 0 });
    const [loading, setLoading] = useState(true);

    const fetchPartnerships = async (page = 1) => {
        setLoading(true);
        try {
            const res = await api.get('/admin/partnerships', { params: { page } });
            setPartnerships(res.data || []);
            setPagination(res.meta || { current_page: 1, last_page: 1, total: 0 });
        } catch (err) {
            console.error('Failed to load partnerships', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => { fetchPartnerships(); }, []);

    const planBadge = (tier) => {
        const map = {
            pilot: 'bg-blue-50 text-blue-700',
            standard: 'bg-teal-50 text-teal-700',
            premium: 'bg-purple-50 text-purple-700',
        };
        return <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${map[tier] || 'bg-gray-50 text-gray-700'}`}>{tier}</span>;
    };

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-900">Lab Partnerships</h2>
                <Link to="/admin/partnerships/new" className="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700 transition-colors">
                    + New Partnership
                </Link>
            </div>

            <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                {loading ? (
                    <div className="flex justify-center py-12"><div className="h-6 w-6 animate-spin rounded-full border-2 border-teal-500 border-t-transparent" /></div>
                ) : partnerships.length === 0 ? (
                    <div className="text-center py-12 text-gray-500">No partnerships yet.</div>
                ) : (
                    <table className="w-full text-left text-sm">
                        <thead className="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th className="px-6 py-3 font-medium text-gray-600">Provider</th>
                                <th className="px-6 py-3 font-medium text-gray-600">Plan</th>
                                <th className="px-6 py-3 font-medium text-gray-600">Pricing</th>
                                <th className="px-6 py-3 font-medium text-gray-600">Reports/Mo</th>
                                <th className="px-6 py-3 font-medium text-gray-600">Est. Bill</th>
                                <th className="px-6 py-3 font-medium text-gray-600">Status</th>
                                <th className="px-6 py-3 font-medium text-gray-600 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {partnerships.map((p) => (
                                <tr key={p.id} className="hover:bg-gray-50">
                                    <td className="px-6 py-4">
                                        <Link to={`/admin/partnerships/${p.id}`} className="font-medium text-teal-700 hover:text-teal-800">
                                            {p.provider?.name || 'Unknown'}
                                        </Link>
                                    </td>
                                    <td className="px-6 py-4">{planBadge(p.plan_tier)}</td>
                                    <td className="px-6 py-4 text-gray-600">
                                        {p.pricing_model === 'per_report' ? `₦${((p.rate_per_report || 0) / 100).toLocaleString()}/report` :
                                         p.pricing_model === 'volume_tier' ? 'Volume Tier' : 'Flat Monthly'}
                                    </td>
                                    <td className="px-6 py-4 text-gray-600">{p.monthly_count || 0}</td>
                                    <td className="px-6 py-4 font-medium text-gray-900">₦{(p.estimated_bill || 0).toLocaleString()}</td>
                                    <td className="px-6 py-4">
                                        <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${p.status === 'active' ? 'bg-green-50 text-green-700' : p.status === 'pilot' ? 'bg-blue-50 text-blue-700' : 'bg-gray-50 text-gray-600'}`}>
                                            {p.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        <Link to={`/admin/partnerships/${p.id}`} className="rounded-lg px-3 py-1 text-xs font-medium text-teal-700 bg-teal-50 hover:bg-teal-100 transition-colors">
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}

                {pagination.last_page > 1 && (
                    <div className="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-center gap-2">
                        {Array.from({ length: pagination.last_page }, (_, i) => i + 1).map((page) => (
                            <button key={page} onClick={() => fetchPartnerships(page)} className={`rounded px-3 py-1 text-xs font-medium ${page === pagination.current_page ? 'bg-teal-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'}`}>
                                {page}
                            </button>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}