import React, { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../lib/api';

export default function AdminSymptomMappings() {
    const [symptomSlug, setSymptomSlug] = useState('');
    const [panelSlug, setPanelSlug] = useState('');
    const [score, setScore] = useState(5);

    const { data: mappingsData, isLoading, refetch } = useQuery({
        queryKey: ['admin-symptom-mappings'],
        queryFn: () => api.get('/admin/symptom-mappings'),
    });

    const { data: symptomsData } = useQuery({
        queryKey: ['symptoms'],
        queryFn: () => api.get('/symptoms'),
    });

    const { data: panelsData } = useQuery({
        queryKey: ['panels'],
        queryFn: () => api.get('/panels'),
    });

    const createMutation = useMutation({
        mutationFn: (payload) => api.post('/admin/symptom-mappings', payload),
        onSuccess: () => { refetch(); setSymptomSlug(''); setPanelSlug(''); setScore(5); },
    });

    const deleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/admin/symptom-mappings/${id}`),
        onSuccess: () => refetch(),
    });

    const mappings = mappingsData?.data?.mappings || [];
    const symptomsDataMap = symptomsData?.data?.symptoms || {};
    const allSymptoms = Object.values(symptomsDataMap).flat();
    const panels = panelsData?.data?.panels || [];

    const handleAdd = () => {
        if (!symptomSlug || !panelSlug) return;
        createMutation.mutate({ symptom_slug: symptomSlug, panel_slug: panelSlug, relevance_score: score });
    };

    return (
        <div className="space-y-6">
            <h2 className="text-xl font-semibold text-gray-900">Symptom ↔ Panel Mappings</h2>

            {/* Add form */}
            <div className="rounded-xl border border-gray-200 bg-white p-5">
                <h3 className="font-semibold text-gray-900 mb-3">Add Mapping</h3>
                <div className="grid gap-3 sm:grid-cols-3 items-end">
                    <div>
                        <label className="block text-xs text-gray-500 mb-1">Symptom</label>
                        <select value={symptomSlug} onChange={(e) => setSymptomSlug(e.target.value)} className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <option value="">Select symptom</option>
                            {allSymptoms.map((s) => <option key={s.slug} value={s.slug}>{s.name}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className="block text-xs text-gray-500 mb-1">Panel</label>
                        <select value={panelSlug} onChange={(e) => setPanelSlug(e.target.value)} className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <option value="">Select panel</option>
                            {panels.map((p) => <option key={p.slug} value={p.slug}>{p.name}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className="block text-xs text-gray-500 mb-1">Relevance (1-10)</label>
                        <input type="number" min={1} max={10} value={score} onChange={(e) => setScore(Number(e.target.value))} className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                    </div>
                </div>
                <button onClick={handleAdd} disabled={createMutation.isPending} className="mt-3 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50">
                    {createMutation.isPending ? 'Adding...' : 'Add mapping'}
                </button>
            </div>

            {/* Table */}
            {isLoading ? (
                <div className="h-20 animate-pulse rounded-xl bg-gray-100" />
            ) : (
                <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium text-gray-500">Symptom</th>
                                <th className="px-4 py-3 font-medium text-gray-500">Panel</th>
                                <th className="px-4 py-3 font-medium text-gray-500">Relevance</th>
                                <th className="px-4 py-3 font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {mappings.length === 0 ? (
                                <tr><td colSpan={4} className="px-4 py-6 text-center text-gray-400">No mappings yet.</td></tr>
                            ) : (
                                mappings.map((m) => (
                                    <tr key={m.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3 font-medium text-gray-900">{m.symptom_name}</td>
                                        <td className="px-4 py-3 text-gray-700">{m.panel_name}</td>
                                        <td className="px-4 py-3">
                                            <span className={`inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-semibold ${m.relevance_score >= 7 ? 'bg-green-100 text-green-700' : m.relevance_score >= 4 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500'}`}>
                                                {m.relevance_score}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3">
                                            <button onClick={() => { if (confirm('Delete mapping?')) deleteMutation.mutate(m.id); }} className="text-red-500 text-xs hover:underline">Delete</button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
