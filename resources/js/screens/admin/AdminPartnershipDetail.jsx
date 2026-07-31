import React, { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import api from '../../lib/api';

export default function AdminPartnershipDetail() {
    const { id } = useParams();
    const isEdit = Boolean(id);
    const navigate = useNavigate();

    const [form, setForm] = useState({
        provider_id: '',
        plan_tier: 'pilot',
        pricing_model: 'per_report',
        rate_per_report: '20000', // in kobo (₦200)
        monthly_allowance: '500',
        overage_rate: '',
        white_label: false,
        brand_logo_url: '',
        brand_primary_color: '#0E6B5C',
        brand_contact_info: '',
        contract_start: '',
        contract_end: '',
        status: 'active',
        ndpa_agreement_signed: false,
    });
    const [providers, setProviders] = useState([]);
    const [stats, setStats] = useState(null);
    const [loading, setLoading] = useState(false);
    const [fetching, setFetching] = useState(isEdit);

    // Fetch providers for dropdown
    useEffect(() => {
        api.get('/admin/providers', { params: { page: 1, per_page: 500 } }).then(res => {
            // Providers endpoint returns paginated data: res = { ok, data: [...], meta: {...} }
            setProviders(res.data || []);
        }).catch(() => {});
    }, []);

    // Fetch partnership data if editing
    useEffect(() => {
        if (isEdit) {
            setFetching(true);
            api.get(`/admin/partnerships/${id}`)
                .then(res => {
                    const p = res.data.partnership;
                    setForm({
                        provider_id: p.provider_id || '',
                        plan_tier: p.plan_tier || 'pilot',
                        pricing_model: p.pricing_model || 'per_report',
                        rate_per_report: String(p.rate_per_report || ''),
                        monthly_allowance: String(p.monthly_allowance || ''),
                        overage_rate: String(p.overage_rate || ''),
                        white_label: p.white_label || false,
                        brand_logo_url: p.brand_logo_url || '',
                        brand_primary_color: p.brand_primary_color || '#0E6B5C',
                        brand_contact_info: p.brand_contact_info || '',
                        contract_start: p.contract_start || '',
                        contract_end: p.contract_end || '',
                        status: p.status || 'active',
                        ndpa_agreement_signed: p.ndpa_agreement_signed || false,
                    });
                    setStats({
                        monthly_count: p.monthly_count,
                        estimated_bill: p.estimated_bill,
                        total_tests: p.total_tests,
                        active_since: p.created_at,
                    });
                    // Also fetch admin-specific stats
                    api.get(`/admin/partnerships/${id}/stats`).then(r => {
                        const s = r.data?.stats || r.data || {};
                        setStats(prev => ({ ...prev, ...s }));
                    }).catch(() => {});
                })
                .catch(() => {
                    alert('Failed to load partnership');
                    navigate('/admin/partnerships');
                })
                .finally(() => setFetching(false));
        }
    }, [id]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!form.provider_id) {
            alert('Please select a provider');
            return;
        }
        setLoading(true);
        try {
            const payload = {
                ...form,
                provider_id: parseInt(form.provider_id),
                rate_per_report: parseInt(form.rate_per_report) || 0,
                monthly_allowance: form.monthly_allowance ? parseInt(form.monthly_allowance) : null,
                overage_rate: form.overage_rate ? parseInt(form.overage_rate) : null,
            };

            if (isEdit) {
                await api.put(`/admin/partnerships/${id}`, payload);
            } else {
                await api.post('/admin/partnerships', payload);
            }
            navigate('/admin/partnerships');
        } catch (err) {
            alert(err?.message || 'Failed to save partnership');
        } finally {
            setLoading(false);
        }
    };

    if (fetching) {
        return <div className="flex justify-center py-20"><div className="h-8 w-8 animate-spin rounded-full border-4 border-teal-500 border-t-transparent" /></div>;
    }

    const selectedProvider = providers.find(p => p.id === parseInt(form.provider_id));

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-900">
                    {isEdit ? 'Edit Partnership' : 'New Lab Partnership'}
                </h2>
                <button onClick={() => navigate('/admin/partnerships')} className="text-sm text-gray-600 hover:text-teal-600">
                    ← Back to partnerships
                </button>
            </div>

            {isEdit && stats && (
                <div className="mb-6 grid grid-cols-3 gap-4">
                    <div className="bg-white rounded-xl border border-gray-200 p-4 text-center">
                        <p className="text-xs text-gray-500 uppercase tracking-wider">Reports This Month</p>
                        <p className="text-2xl font-bold text-teal-700">{stats.monthly_count || 0}</p>
                    </div>
                    <div className="bg-white rounded-xl border border-gray-200 p-4 text-center">
                        <p className="text-xs text-gray-500 uppercase tracking-wider">Estimated Bill</p>
                        <p className="text-2xl font-bold text-teal-700">₦{(stats.estimated_bill || 0).toLocaleString()}</p>
                    </div>
                    <div className="bg-white rounded-xl border border-gray-200 p-4 text-center">
                        <p className="text-xs text-gray-500 uppercase tracking-wider">Rate Per Report</p>
                        <p className="text-2xl font-bold text-teal-700">₦{((parseInt(form.rate_per_report) || 0) / 100).toLocaleString()}</p>
                    </div>
                </div>
            )}

            <form onSubmit={handleSubmit} className="max-w-2xl space-y-6">
                {/* Provider Selection */}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Provider *</label>
                    <select
                        value={form.provider_id}
                        onChange={(e) => setForm(prev => ({ ...prev, provider_id: e.target.value }))}
                        className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none"
                        disabled={isEdit}
                    >
                        <option value="">Select a provider...</option>
                        {providers.map(p => (
                            <option key={p.id} value={p.id}>{p.name} ({p.type}{p.specialty ? ' — ' + p.specialty : ''})</option>
                        ))}
                    </select>
                </div>

                {/* Plan Tier + Status */}
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Plan Tier</label>
                        <select value={form.plan_tier} onChange={(e) => setForm(prev => ({ ...prev, plan_tier: e.target.value }))} className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none">
                            <option value="pilot">Pilot</option>
                            <option value="standard">Standard</option>
                            <option value="premium">Premium</option>
                        </select>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select value={form.status} onChange={(e) => setForm(prev => ({ ...prev, status: e.target.value }))} className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none">
                            <option value="active">Active</option>
                            <option value="pilot">Pilot</option>
                            <option value="expired">Expired</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                {/* Pricing */}
                <div className="bg-gray-50 rounded-xl p-5 space-y-4">
                    <h3 className="text-sm font-semibold text-gray-900">Pricing</h3>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Pricing Model</label>
                        <select value={form.pricing_model} onChange={(e) => setForm(prev => ({ ...prev, pricing_model: e.target.value }))} className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none">
                            <option value="per_report">Per Report</option>
                            <option value="volume_tier">Volume Tier</option>
                            <option value="flat_monthly">Flat Monthly</option>
                        </select>
                    </div>
                    <div className="grid grid-cols-3 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Rate Per Report (₦)</label>
                            <input type="number" value={form.rate_per_report} onChange={(e) => setForm(prev => ({ ...prev, rate_per_report: e.target.value }))} className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none" />
                            <p className="mt-1 text-xs text-gray-400">In kobo (₦200 = 20000)</p>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Monthly Allowance</label>
                            <input type="number" value={form.monthly_allowance} onChange={(e) => setForm(prev => ({ ...prev, monthly_allowance: e.target.value }))} className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none" />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Overage Rate (₦)</label>
                            <input type="number" value={form.overage_rate} onChange={(e) => setForm(prev => ({ ...prev, overage_rate: e.target.value }))} className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none" />
                        </div>
                    </div>
                </div>

                {/* White-Label Branding */}
                <div className="bg-gray-50 rounded-xl p-5 space-y-4">
                    <div className="flex items-center justify-between">
                        <h3 className="text-sm font-semibold text-gray-900">White-Label Branding</h3>
                        <label className="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" checked={form.white_label} onChange={(e) => setForm(prev => ({ ...prev, white_label: e.target.checked }))} className="rounded border-gray-300 text-teal-600 focus:ring-teal-500" />
                            <span className="text-sm text-gray-600">Enable white-label</span>
                        </label>
                    </div>
                    {form.white_label && (
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Brand Logo URL</label>
                                <input type="text" value={form.brand_logo_url} onChange={(e) => setForm(prev => ({ ...prev, brand_logo_url: e.target.value }))} placeholder="https://..." className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Brand Color</label>
                                <div className="flex gap-2">
                                    <input type="color" value={form.brand_primary_color} onChange={(e) => setForm(prev => ({ ...prev, brand_primary_color: e.target.value }))} className="h-10 w-10 rounded border border-gray-300 cursor-pointer" />
                                    <input type="text" value={form.brand_primary_color} onChange={(e) => setForm(prev => ({ ...prev, brand_primary_color: e.target.value }))} className="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-mono focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none" />
                                </div>
                            </div>
                            <div className="col-span-2">
                                <label className="block text-sm font-medium text-gray-700 mb-1">Contact Info (shown on reports)</label>
                                <input type="text" value={form.brand_contact_info} onChange={(e) => setForm(prev => ({ ...prev, brand_contact_info: e.target.value }))} placeholder="Phone: 0800-CALL-LAB | Email: lab@example.com" className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none" />
                            </div>
                        </div>
                    )}
                </div>

                {/* Contract Dates */}
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Contract Start</label>
                        <input type="date" value={form.contract_start} onChange={(e) => setForm(prev => ({ ...prev, contract_start: e.target.value }))} className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none" />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Contract End</label>
                        <input type="date" value={form.contract_end} onChange={(e) => setForm(prev => ({ ...prev, contract_end: e.target.value }))} className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none" />
                    </div>
                </div>

                {/* NDPA */}
                <div>
                    <label className="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" checked={form.ndpa_agreement_signed} onChange={(e) => setForm(prev => ({ ...prev, ndpa_agreement_signed: e.target.checked }))} className="rounded border-gray-300 text-teal-600 focus:ring-teal-500" />
                        <span className="text-sm font-medium text-gray-700">NDPA Data Processing Agreement Signed</span>
                    </label>
                </div>

                {/* Submit */}
                <div className="flex items-center gap-3 pt-4">
                    <button type="submit" disabled={loading} className="rounded-lg bg-teal-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 transition-colors disabled:opacity-50">
                        {loading ? 'Saving...' : isEdit ? 'Update Partnership' : 'Create Partnership'}
                    </button>
                    <button type="button" onClick={() => navigate('/admin/partnerships')} className="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    );
}