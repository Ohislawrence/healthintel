import React, { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../lib/api';

export default function AdminPanels() {
    const [editingSlug, setEditingSlug] = useState(null);
    const [form, setForm] = useState({ name: '', description: '', category: '', is_active: true });

    const { data, isLoading, refetch } = useQuery({
        queryKey: ['admin-panels'],
        queryFn: () => api.get('/admin/panels'),
    });

    const updateMutation = useMutation({
        mutationFn: ({ slug, payload }) => api.put(`/admin/panels/${slug}`, payload),
        onSuccess: () => { refetch(); setEditingSlug(null); },
    });

    const panels = data?.data?.panels || [];

    const startEdit = (panel) => {
        setEditingSlug(panel.slug);
        setForm({ name: panel.name, description: panel.description || '', category: panel.category || '', is_active: panel.is_active });
    };

    const handleSave = () => {
        updateMutation.mutate({ slug: editingSlug, payload: form });
    };

    return (
        <div className="space-y-6">
            <h2 className="text-xl font-semibold text-gray-900">Test Panels</h2>

            {isLoading ? (
                <div className="h-20 animate-pulse rounded-xl bg-gray-100" />
            ) : (
                <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium text-gray-500">Name</th>
                                <th className="px-4 py-3 font-medium text-gray-500">Tests</th>
                                <th className="px-4 py-3 font-medium text-gray-500">Active</th>
                                <th className="px-4 py-3 font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {panels.map((p) => (
                                <tr key={p.id} className="hover:bg-gray-50">
                                    {editingSlug === p.slug ? (
                                        <>
                                            <td className="px-4 py-3">
                                                <input className="w-full rounded border border-gray-300 px-2 py-1 text-sm" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
                                            </td>
                                            <td className="px-4 py-3 text-gray-400">{p.tests_count}</td>
                                            <td className="px-4 py-3">
                                                <select className="rounded border border-gray-300 px-2 py-1 text-sm" value={form.is_active ? '1' : '0'} onChange={(e) => setForm({ ...form, is_active: e.target.value === '1' })}>
                                                    <option value="1">Yes</option>
                                                    <option value="0">No</option>
                                                </select>
                                            </td>
                                            <td className="px-4 py-3 space-x-2">
                                                <button onClick={handleSave} className="text-teal-600 font-medium text-xs hover:underline">Save</button>
                                                <button onClick={() => setEditingSlug(null)} className="text-gray-400 text-xs hover:underline">Cancel</button>
                                            </td>
                                        </>
                                    ) : (
                                        <>
                                            <td className="px-4 py-3 font-medium text-gray-900">{p.name}</td>
                                            <td className="px-4 py-3 text-gray-400">{p.tests_count}</td>
                                            <td className="px-4 py-3">{p.is_active ? <span className="text-green-600">●</span> : <span className="text-gray-300">○</span>}</td>
                                            <td className="px-4 py-3">
                                                <button onClick={() => startEdit(p)} className="text-teal-600 text-xs hover:underline">Edit</button>
                                            </td>
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
