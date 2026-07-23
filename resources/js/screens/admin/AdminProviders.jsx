import React, { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../lib/api';

const EMPTY_FORM = {
    name: '', type: 'hospital', specialty: '', bio: '', phone: '', email: '',
    address: '', city: '', state: '', website: '',
    partner_status: 'none', referral_link: '', is_verified: false, is_active: true,
    monetization_type: '', monetization_rate: '', monetization_amount: '',
    monetization_limit_type: 'time', monetization_limit_value: '',
    banner_url: '',
};

export default function AdminProviders() {
    const [page, setPage] = useState(1);
    const [editingSlug, setEditingSlug] = useState(null);
    const [form, setForm] = useState({});
    const [showCreate, setShowCreate] = useState(false);
    const [createForm, setCreateForm] = useState({ ...EMPTY_FORM });

    const { data, isLoading, refetch } = useQuery({
        queryKey: ['admin-providers', page],
        queryFn: () => api.get('/admin/providers', { params: { page } }),
    });

    const createMutation = useMutation({
        mutationFn: (payload) => api.post('/admin/providers', payload),
        onSuccess: () => {
            refetch();
            setShowCreate(false);
            setCreateForm({ ...EMPTY_FORM });
        },
    });

    const updateMutation = useMutation({
        mutationFn: ({ slug, payload }) => api.put(`/admin/providers/${slug}`, payload),
        onSuccess: () => { refetch(); setEditingSlug(null); },
    });

    const toggleMutation = useMutation({
        mutationFn: (slug) => api.post(`/admin/providers/${slug}/toggle-active`),
        onSuccess: () => refetch(),
    });

    const providers = data?.data || [];
    const pagination = data?.meta || {};

    const startEdit = (p) => {
        setEditingSlug(p.slug);
        setForm({
            name: p.name, type: p.type, specialty: p.specialty || '', bio: p.bio || '',
            phone: p.phone || '', email: p.email || '', address: p.address || '',
            city: p.city || '', state: p.state || '', website: p.website || '',
            partner_status: p.partner_status || 'none', referral_link: p.referral_link || '',
            is_verified: p.is_verified, is_active: p.is_active,
            monetization_type: p.monetization_type || '',
            monetization_rate: p.monetization_rate ?? '',
            monetization_amount: p.monetization_amount ?? '',
            monetization_limit_type: p.monetization_limit_type || 'time',
            monetization_limit_value: p.monetization_limit_value ?? '',
        });
    };

    const handleSave = () => {
        updateMutation.mutate({ slug: editingSlug, payload: form });
    };

    const set = (key, value) => setForm((f) => ({ ...f, [key]: value }));
    const setCreate = (key, value) => setCreateForm((f) => ({ ...f, [key]: value }));

    const handleCreate = () => {
        createMutation.mutate(createForm);
    };

    // Render monetization fields (shared between create and edit)
    const renderMonetizationFields = (formObj, setterFn, prefix = '') => {
        const getVal = (k) => formObj[k];
        const setVal = (k, v) => setterFn(k, v);
        const showFields = getVal('partner_status') === 'affiliate' || getVal('partner_status') === 'sponsored';

        if (getVal('partner_status') === 'none') {
            return (
                <div className="sm:col-span-2 text-xs text-gray-400 mt-1">
                    Select "Affiliate" or "Sponsored" as partner status to configure monetization.
                </div>
            );
        }

        return (
            <>
                {/* Affiliate: rate per conversion */}
                {getVal('partner_status') === 'affiliate' && (
                    <div>
                        <label className="block text-xs font-medium text-gray-600 mb-1">
                            Earnings Per Conversion (₦)
                        </label>
                        <input
                            className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm"
                            type="number" min="0"
                            value={getVal('monetization_rate')}
                            onChange={(e) => setVal('monetization_rate', e.target.value)}
                            placeholder="e.g. 500"
                        />
                        <p className="text-xs text-gray-400 mt-0.5">Amount earned per referral/click-out</p>
                    </div>
                )}

                {/* Sponsored: amount paid + limit */}
                {getVal('partner_status') === 'sponsored' && (
                    <>
                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">
                                Sponsored Amount (₦)
                            </label>
                            <input
                                className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm"
                                type="number" min="0"
                                value={getVal('monetization_amount')}
                                onChange={(e) => setVal('monetization_amount', e.target.value)}
                                placeholder="e.g. 10000"
                            />
                            <p className="text-xs text-gray-400 mt-0.5">Total amount paid for this sponsored listing</p>
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">
                                Limit Type
                            </label>
                            <select
                                className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm"
                                value={getVal('monetization_limit_type')}
                                onChange={(e) => setVal('monetization_limit_type', e.target.value)}
                            >
                                <option value="time">Time-based (days)</option>
                                <option value="views">View-based (impressions)</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">
                                Limit Value
                            </label>
                            <input
                                className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm"
                                type="number" min="0"
                                value={getVal('monetization_limit_value')}
                                onChange={(e) => setVal('monetization_limit_value', e.target.value)}
                                placeholder={getVal('monetization_limit_type') === 'time' ? 'e.g. 30 (days)' : 'e.g. 1000 (views)'}
                            />
                            <p className="text-xs text-gray-400 mt-0.5">
                                {getVal('monetization_limit_type') === 'time'
                                    ? 'Listing stays sponsored for this many days.'
                                    : 'Listing stays sponsored until this many directory views.'}
                            </p>
                        </div>
                    </>
                )}
            </>
        );
    };

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h2 className="text-xl font-semibold text-gray-900">Providers</h2>
                <button
                    onClick={() => setShowCreate(!showCreate)}
                    className="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700 transition-colors"
                >
                    {showCreate ? 'Cancel' : '+ Add Provider'}
                </button>
            </div>

            {/* Create Form */}
            {showCreate && (
                <div className="rounded-xl border border-teal-200 bg-teal-50 p-6 space-y-4">
                    <h3 className="text-sm font-semibold text-teal-800">New Provider</h3>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">Name *</label>
                            <input className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" value={createForm.name} onChange={(e) => setCreate('name', e.target.value)} placeholder="Provider name" />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">Type *</label>
                            <select className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" value={createForm.type} onChange={(e) => setCreate('type', e.target.value)}>
                                <option value="hospital">Hospital</option>
                                <option value="clinic">Clinic</option>
                                <option value="lab">Lab</option>
                                <option value="pharmacy">Pharmacy</option>
                                <option value="specialist">Specialist</option>
                                <option value="insurance">Insurance</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">Specialty</label>
                            <input className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" value={createForm.specialty} onChange={(e) => setCreate('specialty', e.target.value)} placeholder="e.g. Cardiology" />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">Phone</label>
                            <input className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" value={createForm.phone} onChange={(e) => setCreate('phone', e.target.value)} placeholder="Phone number" />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">Email</label>
                            <input className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" value={createForm.email} onChange={(e) => setCreate('email', e.target.value)} placeholder="Email address" />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">Website</label>
                            <input className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" value={createForm.website} onChange={(e) => setCreate('website', e.target.value)} placeholder="https://..." />
                        </div>
                        <div className="sm:col-span-2">
                            <label className="block text-xs font-medium text-gray-600 mb-1">Address</label>
                            <input className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" value={createForm.address} onChange={(e) => setCreate('address', e.target.value)} placeholder="Street address" />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">City</label>
                            <input className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" value={createForm.city} onChange={(e) => setCreate('city', e.target.value)} placeholder="City" />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">State</label>
                            <input className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" value={createForm.state} onChange={(e) => setCreate('state', e.target.value)} placeholder="State" />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">Partner Status</label>
                            <select className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" value={createForm.partner_status} onChange={(e) => setCreate('partner_status', e.target.value)}>
                                <option value="none">None</option>
                                <option value="affiliate">Affiliate</option>
                                <option value="sponsored">Sponsored</option>
                            </select>
                        </div>
                    </div>

                    {/* Monetization Fields — Create */}
                    <div className="border-t border-teal-200 pt-4 mt-4">
                        <h4 className="text-xs font-semibold text-teal-700 mb-3">💵 Monetization Settings</h4>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            {renderMonetizationFields(createForm, (k, v) => {
                                setCreateForm(f => ({ ...f, [k]: v }));
                                // Auto-set monetization_type from partner_status
                                if (k === 'partner_status') {
                                    if (v === 'affiliate' || v === 'sponsored') {
                                        setCreateForm(f => ({ ...f, monetization_type: v }));
                                    } else {
                                        setCreateForm(f => ({ ...f, monetization_type: '', monetization_rate: '', monetization_amount: '', monetization_limit_value: '' }));
                                    }
                                }
                            })}
                        </div>
                    </div>

                    <div className="flex items-center gap-6">
                        <label className="flex items-center gap-1.5 text-sm text-gray-700">
                            <input type="checkbox" checked={createForm.is_verified} onChange={(e) => setCreate('is_verified', e.target.checked)} />
                            Verified
                        </label>
                        <label className="flex items-center gap-1.5 text-sm text-gray-700">
                            <input type="checkbox" checked={createForm.is_active} onChange={(e) => setCreate('is_active', e.target.checked)} />
                            Active
                        </label>
                    </div>
                    <div>
                        <button
                            onClick={handleCreate}
                            disabled={createMutation.isPending || !createForm.name.trim()}
                            className="rounded-lg bg-teal-600 px-5 py-2 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50 transition-colors"
                        >
                            {createMutation.isPending ? 'Creating...' : 'Create Provider'}
                        </button>
                        {createMutation.isError && (
                            <p className="mt-2 text-xs text-red-600">Failed to create provider. Please try again.</p>
                        )}
                    </div>
                </div>
            )}

            {/* Providers Table */}
            {isLoading ? (
                <div className="h-20 animate-pulse rounded-xl bg-gray-100" />
            ) : (
                <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left">
                            <tr>
                                <th className="px-3 py-3 font-medium text-gray-500">Name</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Type</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Partner</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Monetization</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Active</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {providers.map((p) => (
                                <tr key={p.id} className="hover:bg-gray-50">
                                    {editingSlug === p.slug ? (
                                        <td colSpan={6} className="px-4 py-3">
                                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                                <div>
                                                    <label className="block text-xs font-medium text-gray-500 mb-0.5">Name</label>
                                                    <input className="w-full rounded border border-gray-300 px-2 py-1.5 text-xs" value={form.name} onChange={(e) => set('name', e.target.value)} />
                                                </div>
                                                <div>
                                                    <label className="block text-xs font-medium text-gray-500 mb-0.5">Type</label>
                                                    <select className="w-full rounded border border-gray-300 px-2 py-1.5 text-xs" value={form.type} onChange={(e) => set('type', e.target.value)}>
                                                        <option value="hospital">Hospital</option>
                                                        <option value="clinic">Clinic</option>
                                                        <option value="lab">Lab</option>
                                                        <option value="pharmacy">Pharmacy</option>
                                                        <option value="specialist">Specialist</option>
                                                        <option value="insurance">Insurance</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label className="block text-xs font-medium text-gray-500 mb-0.5">Partner Status</label>
                                                    <select
                                                        className="w-full rounded border border-gray-300 px-2 py-1.5 text-xs"
                                                        value={form.partner_status}
                                                        onChange={(e) => {
                                                            set('partner_status', e.target.value);
                                                            if (e.target.value === 'affiliate' || e.target.value === 'sponsored') {
                                                                set('monetization_type', e.target.value);
                                                            } else {
                                                                set('monetization_type', '');
                                                                set('monetization_rate', '');
                                                                set('monetization_amount', '');
                                                                set('monetization_limit_value', '');
                                                            }
                                                        }}
                                                    >
                                                        <option value="none">None</option>
                                                        <option value="affiliate">Affiliate</option>
                                                        <option value="sponsored">Sponsored</option>
                                                    </select>
                                                </div>
                                                <div className="flex items-end gap-2">
                                                    <label className="flex items-center gap-1 text-xs mb-1.5">
                                                        <input type="checkbox" checked={form.is_active} onChange={(e) => set('is_active', e.target.checked)} />
                                                        Active
                                                    </label>
                                                    <button onClick={handleSave} className="rounded bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-teal-700">Save</button>
                                                    <button onClick={() => setEditingSlug(null)} className="text-gray-400 text-xs hover:underline">Cancel</button>
                                                </div>
                                            </div>

                                            {/* Monetization section in edit mode */}
                                            {(form.partner_status === 'affiliate' || form.partner_status === 'sponsored') && (
                                                <div className="mt-3 pt-3 border-t border-gray-200">
                                                    <h4 className="text-xs font-semibold text-teal-700 mb-2">💵 Monetization</h4>
                                                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                                        {form.partner_status === 'affiliate' && (
                                                            <div>
                                                                <label className="block text-xs font-medium text-gray-500 mb-0.5">Earnings Per Conversion (₦)</label>
                                                                <input
                                                                    className="w-full rounded border border-gray-300 px-2 py-1.5 text-xs"
                                                                    type="number" min="0"
                                                                    value={form.monetization_rate}
                                                                    onChange={(e) => set('monetization_rate', e.target.value)}
                                                                    placeholder="e.g. 500"
                                                                />
                                                            </div>
                                                        )}
                                                        {form.partner_status === 'sponsored' && (
                                                            <>
                                                                <div>
                                                                    <label className="block text-xs font-medium text-gray-500 mb-0.5">Sponsored Amount (₦)</label>
                                                                    <input
                                                                        className="w-full rounded border border-gray-300 px-2 py-1.5 text-xs"
                                                                        type="number" min="0"
                                                                        value={form.monetization_amount}
                                                                        onChange={(e) => set('monetization_amount', e.target.value)}
                                                                        placeholder="e.g. 10000"
                                                                    />
                                                                </div>
                                                                <div>
                                                                    <label className="block text-xs font-medium text-gray-500 mb-0.5">Limit Type</label>
                                                                    <select className="w-full rounded border border-gray-300 px-2 py-1.5 text-xs" value={form.monetization_limit_type} onChange={(e) => set('monetization_limit_type', e.target.value)}>
                                                                        <option value="time">Time-based (days)</option>
                                                                        <option value="views">View-based (impressions)</option>
                                                                    </select>
                                                                </div>
                                                                <div>
                                                                    <label className="block text-xs font-medium text-gray-500 mb-0.5">Limit Value</label>
                                                                    <input
                                                                        className="w-full rounded border border-gray-300 px-2 py-1.5 text-xs"
                                                                        type="number" min="0"
                                                                        value={form.monetization_limit_value}
                                                                        onChange={(e) => set('monetization_limit_value', e.target.value)}
                                                                        placeholder={form.monetization_limit_type === 'time' ? 'Days' : 'Max views'}
                                                                    />
                                                                </div>
                                                            </>
                                                        )}
                                                    </div>
                                                </div>
                                            )}
                                        </td>
                                    ) : (
                                        <>
                                            <td className="px-3 py-2 font-medium text-gray-900 truncate max-w-[200px]">{p.name}</td>
                                            <td className="px-3 py-2"><span className="uppercase text-xs bg-gray-100 rounded px-2 py-0.5">{p.type}</span></td>
                                            <td className="px-3 py-2">
                                                {p.partner_status !== 'none' ? (
                                                    <span className={`text-xs font-medium ${p.partner_status === 'affiliate' ? 'text-amber-600' : 'text-indigo-600'}`}>{p.partner_status}</span>
                                                ) : (
                                                    <span className="text-gray-300">—</span>
                                                )}
                                            </td>
                                            <td className="px-3 py-2">
                                                {p.partner_status === 'affiliate' && p.monetization_rate ? (
                                                    <span className="text-xs text-amber-600 font-medium">₦{p.monetization_rate}/conv</span>
                                                ) : p.partner_status === 'sponsored' ? (
                                                    <span className="text-xs text-indigo-600 font-medium">
                                                        ₦{p.monetization_amount || '0'}
                                                        {p.monetization_limit_type === 'time' && p.monetization_expires_at
                                                            ? ` · expires ${new Date(p.monetization_expires_at).toLocaleDateString()}`
                                                            : p.monetization_limit_type === 'views'
                                                            ? ` · ${p.monetization_views_used ?? 0}/${p.monetization_limit_value ?? '?'} views`
                                                            : ''}
                                                    </span>
                                                ) : (
                                                    <span className="text-gray-300 text-xs">—</span>
                                                )}
                                            </td>
                                            <td className="px-3 py-2">{p.is_active ? '🟢' : '🔴'}</td>
                                            <td className="px-3 py-2 space-x-2">
                                                <button onClick={() => startEdit(p)} className="text-teal-600 text-xs hover:underline">Edit</button>
                                                <button onClick={() => { if (confirm(`Toggle active for ${p.name}?`)) toggleMutation.mutate(p.slug); }} className="text-gray-400 text-xs hover:underline">Toggle</button>
                                            </td>
                                        </>
                                    )}
                                </tr>
                            ))}
                            {providers.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-3 py-8 text-center text-gray-400">No providers found.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
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