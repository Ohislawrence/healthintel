import React, { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../lib/api';

export default function AdminCreditPackages() {
    const [editingId, setEditingId] = useState(null);
    const [form, setForm] = useState({ name: '', credits: 1, price_ngn: 0, description: '', is_active: true });
    const [showAdd, setShowAdd] = useState(false);

    const { data, isLoading, refetch } = useQuery({
        queryKey: ['admin-credit-packages'],
        queryFn: () => api.get('/admin/credit-packages'),
    });

    const updateMutation = useMutation({
        mutationFn: ({ id, payload }) => api.put(`/admin/credit-packages/${id}`, payload),
        onSuccess: () => { refetch(); setEditingId(null); },
    });

    const createMutation = useMutation({
        mutationFn: (payload) => api.post('/admin/credit-packages', payload),
        onSuccess: () => { refetch(); setShowAdd(false); setForm({ name: '', credits: 1, price_ngn: 0, description: '', is_active: true }); },
    });

    const packages = data?.data?.packages || [];

    const startEdit = (pkg) => {
        setEditingId(pkg.id);
        setForm({ name: pkg.name, credits: pkg.credits, price_ngn: pkg.price_naira || (pkg.price_kobo / 100), description: pkg.description || '', is_active: pkg.is_active });
    };

    const handleSave = () => updateMutation.mutate({ id: editingId, payload: form });
    const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <h2 className="text-xl font-semibold text-gray-900">Credit Packages</h2>
                <button onClick={() => setShowAdd(!showAdd)} className="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">
                    {showAdd ? 'Cancel' : 'Add Package'}
                </button>
            </div>

            {showAdd && (
                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 className="font-semibold text-gray-900 mb-3">New Package</h3>
                    <div className="grid gap-3 sm:grid-cols-4">
                        <div>
                            <label className="text-xs text-gray-500">Name</label>
                            <input className="w-full rounded border border-gray-300 px-3 py-2 text-sm" value={form.name} onChange={(e) => set('name', e.target.value)} />
                        </div>
                        <div>
                            <label className="text-xs text-gray-500">Credits</label>
                            <input type="number" className="w-full rounded border border-gray-300 px-3 py-2 text-sm" value={form.credits} onChange={(e) => set('credits', Number(e.target.value))} />
                        </div>
                        <div>
                            <label className="text-xs text-gray-500">Price (₦)</label>
                            <input type="number" className="w-full rounded border border-gray-300 px-3 py-2 text-sm" value={form.price_ngn} onChange={(e) => set('price_ngn', Number(e.target.value))} />
                        </div>
                        <div className="flex items-end">
                            <button onClick={() => createMutation.mutate(form)} disabled={createMutation.isPending} className="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50">
                                {createMutation.isPending ? 'Creating...' : 'Create'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {isLoading ? (
                <div className="h-20 animate-pulse rounded-xl bg-gray-100" />
            ) : (
                <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium text-gray-500">Name</th>
                                <th className="px-4 py-3 font-medium text-gray-500">Credits</th>
                                <th className="px-4 py-3 font-medium text-gray-500">Price</th>
                                <th className="px-4 py-3 font-medium text-gray-500">Active</th>
                                <th className="px-4 py-3 font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {packages.map((pkg) => (
                                <tr key={pkg.id} className="hover:bg-gray-50">
                                    {editingId === pkg.id ? (
                                        <>
                                            <td className="px-4 py-2"><input className="w-full rounded border px-2 py-1 text-xs" value={form.name} onChange={e => set('name', e.target.value)} /></td>
                                            <td className="px-4 py-2"><input type="number" className="w-20 rounded border px-2 py-1 text-xs" value={form.credits} onChange={e => set('credits', Number(e.target.value))} /></td>
                                            <td className="px-4 py-2"><input type="number" className="w-24 rounded border px-2 py-1 text-xs" value={form.price_ngn} onChange={e => set('price_ngn', Number(e.target.value))} /></td>
                                            <td className="px-4 py-2"><label className="flex items-center gap-1 text-xs"><input type="checkbox" checked={form.is_active} onChange={e => set('is_active', e.target.checked)} /> Active</label></td>
                                            <td className="px-4 py-2 space-x-2">
                                                <button onClick={handleSave} className="text-teal-600 text-xs font-medium hover:underline">Save</button>
                                                <button onClick={() => setEditingId(null)} className="text-gray-400 text-xs hover:underline">Cancel</button>
                                            </td>
                                        </>
                                    ) : (
                                        <>
                                            <td className="px-4 py-2 font-medium">{pkg.name}</td>
                                            <td className="px-4 py-2">{pkg.credits}</td>
                                            <td className="px-4 py-2">{pkg.price_formatted || '₦' + (pkg.price_naira || 0).toLocaleString()}</td>
                                            <td className="px-4 py-2">{pkg.is_active ? '🟢' : '🔴'}</td>
                                            <td className="px-4 py-2"><button onClick={() => startEdit(pkg)} className="text-teal-600 text-xs hover:underline">Edit</button></td>
                                        </>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
