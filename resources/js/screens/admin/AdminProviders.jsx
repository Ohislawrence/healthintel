import React, { useState, useEffect, useRef } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../lib/api';

const EMPTY_FORM = {
    name: '', type: 'hospital', specialty: '', bio: '', phone: '', email: '',
    address: '', city: '', state: '', website: '',
    partner_status: 'none', referral_link: '', is_verified: false, is_active: true,
    monetization_type: '', monetization_rate: '', monetization_amount: '',
    monetization_limit_type: 'time', monetization_limit_value: '',
    banner_url: '',
    logo_url: '',
    locations: [],
};

const EMPTY_LOCATION = {
    name: '', address: '', city: '', state: '', country: 'Nigeria',
    phone: '', latitude: '', longitude: '', is_primary: false,
};

export default function AdminProviders() {
    const [page, setPage] = useState(1);
    // Single modal state: null | { type: 'create'|'edit', slug?, form }
    const [modal, setModal] = useState(null);

    // Filters
    const [search, setSearch] = useState('');
    const [filterType, setFilterType] = useState('');
    const [filterPartnerStatus, setFilterPartnerStatus] = useState('');
    const [filterActive, setFilterActive] = useState('');

    const { data, isLoading, refetch } = useQuery({
        queryKey: ['admin-providers', page, search, filterType, filterPartnerStatus, filterActive],
        queryFn: () => api.get('/admin/providers', {
            params: {
                page,
                search: search || undefined,
                type: filterType || undefined,
                partner_status: filterPartnerStatus || undefined,
                is_active: filterActive === '' ? undefined : filterActive,
            },
        }),
    });

    const createMutation = useMutation({
        mutationFn: (payload) => api.post('/admin/providers', payload),
        onSuccess: () => {
            refetch();
            setModal(null);
        },
    });

    const updateMutation = useMutation({
        mutationFn: ({ slug, payload }) => api.put(`/admin/providers/${slug}`, payload),
        onSuccess: () => {
            refetch();
            setModal(null);
        },
    });

    const accessCodeMutation = useMutation({
        mutationFn: (slug) => api.post(`/admin/providers/${slug}/generate-access-code`),
        onSuccess: (res) => {
            const code = res?.data?.access_code || res?.access_code;
            if (code) {
                navigator.clipboard.writeText(code).catch(() => {});
                alert(`Access code generated and copied to clipboard:\n\n${code}\n\nLogin URL: ${res?.login_url || `${window.location.origin}/partner/login`}`);
            }
            refetch();
        },
        onError: (err) => {
            alert(err?.response?.data?.message || err?.message || 'Failed to generate access code');
        },
    });

    const toggleMutation = useMutation({
        mutationFn: (slug) => api.post(`/admin/providers/${slug}/toggle-active`),
        onSuccess: () => refetch(),
    });

    const providers = data?.data || [];
    const pagination = data?.meta || {};

    const openCreate = () => setModal({ type: 'create', form: { ...EMPTY_FORM } });

    const openEdit = (p) => {
        setModal({
            type: 'edit',
            slug: p.slug,
            form: {
                name: p.name, type: p.type, specialty: p.specialty || '', bio: p.bio || '',
                phone: p.phone || '', email: p.email || '', address: p.address || '',
                city: p.city || '', state: p.state || '', website: p.website || '',
                partner_status: p.partner_status || 'none', referral_link: p.referral_link || '',
                is_verified: !!p.is_verified, is_active: !!p.is_active,
                monetization_type: p.monetization_type || '',
                monetization_rate: p.monetization_rate ?? '',
                monetization_amount: p.monetization_amount ?? '',
                monetization_limit_type: p.monetization_limit_type || 'time',
                monetization_limit_value: p.monetization_limit_value ?? '',
                banner_url: p.banner_url || '',
                logo_url: p.logo_url || '',
                locations: Array.isArray(p.locations) ? p.locations.map((l) => ({
                    name: l.name || '', address: l.address || '', city: l.city || '',
                    state: l.state || '', country: l.country || 'Nigeria',
                    phone: l.phone || '', latitude: l.latitude ?? '', longitude: l.longitude ?? '',
                    is_primary: !!l.is_primary,
                })) : [],
            },
        });
    };

    const closeModal = () => setModal(null);

    const setField = (key, value) => setModal((m) => m && ({ ...m, form: { ...m.form, [key]: value } }));

    const setLocation = (index, key, value) => setModal((m) => m && ({
        ...m,
        form: {
            ...m.form,
            locations: m.form.locations.map((loc, i) => (i === index ? { ...loc, [key]: value } : loc)),
        },
    }));

    const addLocation = () => setModal((m) => m && ({
        ...m,
        form: { ...m.form, locations: [...m.form.locations, { ...EMPTY_LOCATION }] },
    }));

    const removeLocation = (index) => setModal((m) => m && ({
        ...m,
        form: { ...m.form, locations: m.form.locations.filter((_, i) => i !== index) },
    }));

    const setPrimaryLocation = (index) => setModal((m) => m && ({
        ...m,
        form: {
            ...m.form,
            locations: m.form.locations.map((loc, i) => ({ ...loc, is_primary: i === index })),
        },
    }));

    const handleSave = () => {
        if (!modal) return;
        if (modal.type === 'edit') {
            updateMutation.mutate({ slug: modal.slug, payload: modal.form });
        } else {
            createMutation.mutate(modal.form);
        }
    };

    // Multi-location editor (rendered inside the modal form)
    const renderLocationEditor = (locations) => (
        <div className="mt-4 pt-4 border-t border-teal-200">
            <div className="flex items-center justify-between mb-3">
                <h4 className="text-xs font-semibold text-teal-700">📍 Locations ({locations.length})</h4>
                <button
                    type="button"
                    onClick={addLocation}
                    className="text-xs font-semibold text-teal-700 bg-white border border-teal-300 rounded px-2 py-1 hover:bg-teal-50"
                >
                    + Add Location
                </button>
            </div>
            {locations.length === 0 ? (
                <p className="text-xs text-gray-400">No locations yet. Add branches for multi-location businesses (e.g. Aro Lab — Ikeja, Lekki, Abuja).</p>
            ) : (
                <div className="space-y-3">
                    {locations.map((loc, i) => (
                        <div key={i} className="rounded-lg border border-gray-200 bg-white p-3 space-y-2">
                            <div className="flex items-center justify-between">
                                <span className="text-[10px] font-bold text-gray-400 uppercase">Branch {i + 1}</span>
                                <div className="flex items-center gap-2">
                                    <label className="flex items-center gap-1 text-[10px] font-semibold text-gray-500">
                                        <input
                                            type="radio"
                                            name="provider-primary-location"
                                            checked={!!loc.is_primary}
                                            onChange={() => setPrimaryLocation(i)}
                                        />
                                        Primary
                                    </label>
                                    <button type="button" onClick={() => removeLocation(i)} className="text-red-500 text-xs hover:underline">Remove</button>
                                </div>
                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <input className="w-full rounded border border-gray-300 px-2 py-1.5 text-xs" value={loc.name} onChange={(e) => setLocation(i, 'name', e.target.value)} placeholder="Branch name (e.g. Ikeja)" />
                                <input className="w-full rounded border border-gray-300 px-2 py-1.5 text-xs" value={loc.phone} onChange={(e) => setLocation(i, 'phone', e.target.value)} placeholder="Phone" />
                                <input className="w-full rounded border border-gray-300 px-2 py-1.5 text-xs sm:col-span-2" value={loc.address} onChange={(e) => setLocation(i, 'address', e.target.value)} placeholder="Address" />
                                <input className="w-full rounded border border-gray-300 px-2 py-1.5 text-xs" value={loc.city} onChange={(e) => setLocation(i, 'city', e.target.value)} placeholder="City" />
                                <input className="w-full rounded border border-gray-300 px-2 py-1.5 text-xs" value={loc.state} onChange={(e) => setLocation(i, 'state', e.target.value)} placeholder="State" />
                                <input className="w-full rounded border border-gray-300 px-2 py-1.5 text-xs" value={loc.latitude} onChange={(e) => setLocation(i, 'latitude', e.target.value)} placeholder="Latitude (optional)" />
                                <input className="w-full rounded border border-gray-300 px-2 py-1.5 text-xs" value={loc.longitude} onChange={(e) => setLocation(i, 'longitude', e.target.value)} placeholder="Longitude (optional)" />
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h2 className="text-xl font-semibold text-gray-900">Providers</h2>
                <button
                    onClick={openCreate}
                    className="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700 transition-colors"
                >
                    + Add Provider
                </button>
            </div>

            {/* Filters */}
            <div className="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4">
                <div className="flex-1 min-w-[200px]">
                    <label className="block text-xs font-medium text-gray-500 mb-1">Search</label>
                    <input
                        className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm"
                        value={search}
                        onChange={(e) => { setSearch(e.target.value); setPage(1); }}
                        placeholder="Name, specialty, email, city, state…"
                    />
                </div>
                <div>
                    <label className="block text-xs font-medium text-gray-500 mb-1">Type</label>
                    <select
                        className="rounded border border-gray-300 px-3 py-1.5 text-sm"
                        value={filterType}
                        onChange={(e) => { setFilterType(e.target.value); setPage(1); }}
                    >
                        <option value="">All types</option>
                        <option value="hospital">Hospital</option>
                        <option value="clinic">Clinic</option>
                        <option value="lab">Lab</option>
                        <option value="pharmacy">Pharmacy</option>
                        <option value="specialist">Specialist</option>
                        <option value="insurance">Insurance</option>
                    </select>
                </div>
                <div>
                    <label className="block text-xs font-medium text-gray-500 mb-1">Partner Status</label>
                    <select
                        className="rounded border border-gray-300 px-3 py-1.5 text-sm"
                        value={filterPartnerStatus}
                        onChange={(e) => { setFilterPartnerStatus(e.target.value); setPage(1); }}
                    >
                        <option value="">All</option>
                        <option value="none">None</option>
                        <option value="affiliate">Affiliate</option>
                        <option value="sponsored">Sponsored</option>
                    </select>
                </div>
                <div>
                    <label className="block text-xs font-medium text-gray-500 mb-1">Status</label>
                    <select
                        className="rounded border border-gray-300 px-3 py-1.5 text-sm"
                        value={filterActive}
                        onChange={(e) => { setFilterActive(e.target.value); setPage(1); }}
                    >
                        <option value="">All</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                {(search || filterType || filterPartnerStatus || filterActive !== '') && (
                    <button
                        onClick={() => {
                            setSearch('');
                            setFilterType('');
                            setFilterPartnerStatus('');
                            setFilterActive('');
                            setPage(1);
                        }}
                        className="rounded border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50"
                    >
                        Clear
                    </button>
                )}
            </div>

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
                                <th className="px-3 py-3 font-medium text-gray-500">Locations</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Partner</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Monetization</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Active</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {providers.map((p) => (
                                <tr key={p.id} className="hover:bg-gray-50">
                                    <td className="px-3 py-2 font-medium text-gray-900 truncate max-w-[180px]">
                                        <div className="flex items-center gap-2">
                                            {p.logo_url && (
                                                <img src={p.logo_url} alt={p.name} className="h-6 w-6 rounded object-contain bg-white border border-gray-100" onError={(e) => { e.currentTarget.style.display = 'none'; }} />
                                            )}
                                            {p.name}
                                        </div>
                                    </td>
                                    <td className="px-3 py-2"><span className="uppercase text-xs bg-gray-100 rounded px-2 py-0.5">{p.type}</span></td>
                                    <td className="px-3 py-2 text-xs text-gray-500">{p.locations_count ?? p.locations?.length ?? 0}</td>
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
                                    <td className="px-3 py-2 space-x-2 whitespace-nowrap">
                                        <button onClick={() => openEdit(p)} className="text-teal-600 text-xs hover:underline">Edit</button>
                                        <button onClick={() => { if (confirm(`Toggle active for ${p.name}?`)) toggleMutation.mutate(p.slug); }} className="text-gray-400 text-xs hover:underline">Toggle</button>
                                        {p.partner_status !== 'none' && (
                                            <button
                                                onClick={() => {
                                                    if (confirm(`Generate a new access code for ${p.name}? The old code will stop working.`)) {
                                                        accessCodeMutation.mutate(p.slug);
                                                    }
                                                }}
                                                className="text-indigo-600 text-xs hover:underline"
                                                disabled={accessCodeMutation.isPending}
                                            >
                                                {accessCodeMutation.isPending ? 'Generating...' : '🔑 Login Code'}
                                            </button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                            {providers.length === 0 && (
                                <tr>
                                    <td colSpan={7} className="px-3 py-8 text-center text-gray-400">No providers found.</td>
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

            {/* Create / Edit Modal */}
            {modal && (
                <Modal
                    title={modal.type === 'edit' ? 'Edit Provider' : 'Add Provider'}
                    onClose={closeModal}
                    wide
                >
                    <ProviderForm
                        form={modal.form}
                        setField={setField}
                        onSubmit={handleSave}
                        onCancel={closeModal}
                        saving={modal.type === 'edit' ? updateMutation.isPending : createMutation.isPending}
                        error={modal.type === 'edit' ? updateMutation.isError : createMutation.isError}
                        isEdit={modal.type === 'edit'}
                        renderLocationEditor={() => renderLocationEditor(modal.form.locations)}
                    />
                </Modal>
            )}
        </div>
    );
}

/**
 * Shared create/edit form rendered inside the modal.
 */
function ProviderForm({ form, setField: set, onSubmit, onCancel, saving, error, isEdit, renderLocationEditor }) {
    return (
        <div className="space-y-4">
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div>
                    <label className="block text-xs font-medium text-gray-600 mb-1">Name *</label>
                    <input className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" value={form.name} onChange={(e) => set('name', e.target.value)} placeholder="Provider name" />
                </div>
                <div>
                    <label className="block text-xs font-medium text-gray-600 mb-1">Type *</label>
                    <select className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" value={form.type} onChange={(e) => set('type', e.target.value)}>
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
                    <input className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" value={form.specialty} onChange={(e) => set('specialty', e.target.value)} placeholder="e.g. Cardiology" />
                </div>
                <div>
                    <label className="block text-xs font-medium text-gray-600 mb-1">Phone</label>
                    <input className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" value={form.phone} onChange={(e) => set('phone', e.target.value)} placeholder="Phone number" />
                </div>
                <div>
                    <label className="block text-xs font-medium text-gray-600 mb-1">Email</label>
                    <input className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" value={form.email} onChange={(e) => set('email', e.target.value)} placeholder="Email address" />
                </div>
                <div>
                    <label className="block text-xs font-medium text-gray-600 mb-1">Website</label>
                    <input className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" value={form.website} onChange={(e) => set('website', e.target.value)} placeholder="https://..." />
                </div>
                <ImageUploadField
                    label="Logo"
                    hint="Square — at least 200×200px"
                    value={form.logo_url || ''}
                    onUploaded={(url) => set('logo_url', url)}
                />
                <ImageUploadField
                    label="Banner"
                    hint="Wide — 1200×400px (3:1 ratio)"
                    value={form.banner_url || ''}
                    onUploaded={(url) => set('banner_url', url)}
                />
                <div className="sm:col-span-2">
                    <label className="block text-xs font-medium text-gray-600 mb-1">Address</label>
                    <input className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" value={form.address} onChange={(e) => set('address', e.target.value)} placeholder="Street address" />
                </div>
                <div>
                    <label className="block text-xs font-medium text-gray-600 mb-1">City</label>
                    <input className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" value={form.city} onChange={(e) => set('city', e.target.value)} placeholder="City" />
                </div>
                <div>
                    <label className="block text-xs font-medium text-gray-600 mb-1">State</label>
                    <input className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" value={form.state} onChange={(e) => set('state', e.target.value)} placeholder="State" />
                </div>
                <div>
                    <label className="block text-xs font-medium text-gray-600 mb-1">Partner Status</label>
                    <select className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" value={form.partner_status} onChange={(e) => {
                        set('partner_status', e.target.value);
                        if (e.target.value === 'affiliate' || e.target.value === 'sponsored') {
                            set('monetization_type', e.target.value);
                        } else {
                            set('monetization_type', '');
                            set('monetization_rate', '');
                            set('monetization_amount', '');
                            set('monetization_limit_value', '');
                        }
                    }}>
                        <option value="none">None</option>
                        <option value="affiliate">Affiliate</option>
                        <option value="sponsored">Sponsored</option>
                    </select>
                </div>
            </div>

            {/* Bio */}
            <div>
                <label className="block text-xs font-medium text-gray-600 mb-1">Bio / Description</label>
                <textarea className="w-full rounded border border-gray-300 px-3 py-2 text-sm" rows={3} value={form.bio} onChange={(e) => set('bio', e.target.value)} placeholder="Short description about this provider…" />
            </div>

            {/* Monetization */}
            <div className="border-t border-teal-200 pt-4 mt-4">
                <h4 className="text-xs font-semibold text-teal-700 mb-3">💵 Monetization Settings</h4>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    {form.partner_status === 'none' ? (
                        <div className="sm:col-span-2 text-xs text-gray-400 mt-1">
                            Select "Affiliate" or "Sponsored" as partner status to configure monetization.
                        </div>
                    ) : (
                        <>
                            {form.partner_status === 'affiliate' && (
                                <div>
                                    <label className="block text-xs font-medium text-gray-600 mb-1">Earnings Per Conversion (₦)</label>
                                    <input className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" type="number" min="0" value={form.monetization_rate} onChange={(e) => set('monetization_rate', e.target.value)} placeholder="e.g. 500" />
                                </div>
                            )}
                            {form.partner_status === 'sponsored' && (
                                <>
                                    <div>
                                        <label className="block text-xs font-medium text-gray-600 mb-1">Sponsored Amount (₦)</label>
                                        <input className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" type="number" min="0" value={form.monetization_amount} onChange={(e) => set('monetization_amount', e.target.value)} placeholder="e.g. 10000" />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-medium text-gray-600 mb-1">Limit Type</label>
                                        <select className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" value={form.monetization_limit_type} onChange={(e) => set('monetization_limit_type', e.target.value)}>
                                            <option value="time">Time-based (days)</option>
                                            <option value="views">View-based (impressions)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-xs font-medium text-gray-600 mb-1">Limit Value</label>
                                        <input className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" type="number" min="0" value={form.monetization_limit_value} onChange={(e) => set('monetization_limit_value', e.target.value)} placeholder={form.monetization_limit_type === 'time' ? 'e.g. 30 (days)' : 'e.g. 1000 (views)'} />
                                    </div>
                                </>
                            )}
                        </>
                    )}
                </div>
            </div>

            {/* Locations */}
            {renderLocationEditor()}

            {/* Flags */}
            <div className="flex items-center gap-6">
                <label className="flex items-center gap-1.5 text-sm text-gray-700">
                    <input type="checkbox" checked={form.is_verified} onChange={(e) => set('is_verified', e.target.checked)} />
                    Verified
                </label>
                <label className="flex items-center gap-1.5 text-sm text-gray-700">
                    <input type="checkbox" checked={form.is_active} onChange={(e) => set('is_active', e.target.checked)} />
                    Active
                </label>
            </div>

            {/* Actions */}
            <div className="flex items-center gap-3 pt-2">
                <button
                    onClick={onSubmit}
                    disabled={saving || !form.name.trim()}
                    className="rounded-lg bg-teal-600 px-5 py-2 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50 transition-colors"
                >
                    {saving ? 'Saving...' : isEdit ? 'Save Changes' : 'Create Provider'}
                </button>
                <button
                    onClick={onCancel}
                    className="rounded-lg border border-gray-300 bg-white px-5 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors"
                >
                    Cancel
                </button>
            </div>
            {error && (
                <p className="text-xs text-red-600">Failed to save provider. Please try again.</p>
            )}
        </div>
    );
}

/**
 * Reusable modal overlay with scrollable body + ESC/backdrop close.
 */
function Modal({ title, onClose, children, wide = false }) {
    useEffect(() => {
        const onKey = (e) => {
            if (e.key === 'Escape') onClose();
        };
        window.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';
        return () => {
            window.removeEventListener('keydown', onKey);
            document.body.style.overflow = '';
        };
    }, [onClose]);

    return (
        <div
            className="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-4"
            onClick={onClose}
        >
            <div
                className={`relative my-8 w-full ${wide ? 'max-w-3xl' : 'max-w-lg'} rounded-2xl bg-white shadow-xl`}
                onClick={(e) => e.stopPropagation()}
            >
                <div className="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <h3 className="text-base font-semibold text-gray-900">{title}</h3>
                    <button onClick={onClose} className="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
                </div>
                <div className="px-6 py-5 max-h-[75vh] overflow-y-auto">{children}</div>
            </div>
        </div>
    );
}

/**
 * File-upload field for provider logo/banner. Uploads on select, shows a preview,
 * displays the recommended dimensions, and stores the resolved `/storage/...` URL.
 */
function ImageUploadField({ label, hint, value, onUploaded }) {
    const inputRef = useRef(null);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState(null);

    const handleFile = async (e) => {
        const file = e.target.files?.[0];
        e.target.value = '';
        if (!file) return;
        if (!file.type.startsWith('image/')) {
            setError('Please choose an image file.');
            return;
        }
        setError(null);
        setUploading(true);
        try {
            const fd = new FormData();
            fd.append('file', file);
            const res = await api.post('/admin/providers/upload-asset', fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            const url = res?.data?.url || res?.url;
            if (url) onUploaded(url);
        } catch (err) {
            setError(err?.message || 'Upload failed. Please try again.');
        } finally {
            setUploading(false);
        }
    };

    return (
        <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">{label}</label>
            <div className="flex items-center gap-2">
                {value && (
                    <img
                        src={value}
                        alt={`${label} preview`}
                        className={`${label === 'Logo' ? 'h-9 w-9 rounded object-contain' : 'h-8 w-auto rounded max-w-[120px] object-cover'} border border-gray-200 bg-white p-0.5`}
                        onError={(e) => { e.currentTarget.style.display = 'none'; }}
                    />
                )}
                <input
                    ref={inputRef}
                    type="file"
                    accept="image/*"
                    className="hidden"
                    onChange={handleFile}
                />
                <button
                    type="button"
                    onClick={() => inputRef.current?.click()}
                    disabled={uploading}
                    className="rounded border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                >
                    {uploading ? 'Uploading…' : value ? 'Replace' : 'Upload'}
                </button>
                {value && (
                    <button type="button" onClick={() => onUploaded('')} className="text-xs text-red-500 hover:underline">Remove</button>
                )}
            </div>
            <p className="text-[10px] text-gray-400 mt-1">{hint} · Max 5MB · PNG/JPG/WebP/SVG</p>
            {error && <p className="text-xs text-red-600 mt-1">{error}</p>}
        </div>
    );
}