import React, { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { Link, useNavigate } from 'react-router-dom';
import api from '../../lib/api';
import useAuthStore from '../../stores/authStore';
import Disclaimer from '../../components/ui/Disclaimer';

export default function SymptomChecker() {
    const navigate = useNavigate();
    const { user, fetchUser } = useAuthStore();
    const [search, setSearch] = useState('');
    const [selected, setSelected] = useState([]);
    const [description, setDescription] = useState('');
    const [result, setResult] = useState(null);

    const { data, isLoading } = useQuery({
        queryKey: ['symptoms'],
        queryFn: () => api.get('/symptoms'),
    });

    const [mutationError, setMutationError] = useState(null);

    const checkMutation = useMutation({
        mutationFn: (payload) => api.post('/symptoms/check', payload),
        onSuccess: async (res) => {
            setResult(res.data);
            setMutationError(null);
            await fetchUser();
        },
        onError: (err) => {
            const msg = err?.message || 'Analysis failed. Please try again.';
            setMutationError(msg);
        },
    });

    const symptomsData = data?.data?.symptoms || {};
    const allSymptoms = Object.values(symptomsData).flat();

    const toggleSymptom = (slug) => {
        setSelected((prev) =>
            prev.includes(slug) ? prev.filter((s) => s !== slug) : [...prev, slug],
        );
        setResult(null);
    };

    const filteredCategories = search
        ? Object.entries(symptomsData).reduce((acc, [cat, symptoms]) => {
            const filtered = symptoms.filter((s) =>
                s.name.toLowerCase().includes(search.toLowerCase()),
            );
            if (filtered.length) acc[cat] = filtered;
            return acc;
        }, {})
        : symptomsData;

    const handleFullCheck = () => {
        if (selected.length === 0) return;
        checkMutation.mutate({ symptoms: selected, patient_context: description || undefined });
    };

    const selectedNames = selected.map((slug) => allSymptoms.find((s) => s.slug === slug)?.name).filter(Boolean);

    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-xl font-semibold text-gray-900">Symptom Checker</h2>
                <p className="mt-1 text-sm text-gray-500">
                    Select your symptoms to get relevant lab panel suggestions and AI-powered guidance.
                </p>
            </div>

            {/* Selected symptoms badges */}
            {selected.length > 0 && (
                <div className="flex flex-wrap gap-2">
                    {selectedNames.map((name, i) => (
                        <button
                            key={selected[i]}
                            onClick={() => toggleSymptom(selected[i])}
                            className="inline-flex items-center gap-1 rounded-full bg-teal-50 border border-teal-200 px-3 py-1 text-xs font-medium text-teal-700 hover:bg-teal-100"
                        >
                            {name} ✕
                        </button>
                    ))}
                </div>
            )}

            {/* Search */}
            <input
                type="text"
                placeholder="Search symptoms..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-200 outline-none"
            />

            {/* Symptom grid by category */}
            {isLoading ? (
                <div className="space-y-4">
                    {[1, 2, 3].map((i) => (
                        <div key={i} className="h-32 animate-pulse rounded-xl bg-gray-100" />
                    ))}
                </div>
            ) : (
                <div className="space-y-4">
                    {Object.entries(filteredCategories).map(([category, symptoms]) => (
                        <div key={category}>
                            <h3 className="mb-2 text-sm font-semibold uppercase text-gray-500 tracking-wide">
                                {category}
                            </h3>
                            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                {symptoms.map((s) => (
                                    <button
                                        key={s.slug}
                                        onClick={() => toggleSymptom(s.slug)}
                                        className={`rounded-lg border px-4 py-2.5 text-left text-sm transition-all ${
                                            selected.includes(s.slug)
                                                ? 'border-teal-300 bg-teal-50 text-teal-700 shadow-sm'
                                                : 'border-gray-200 bg-white text-gray-700 hover:border-teal-200'
                                        }`}
                                    >
                                        {s.name}
                                    </button>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Symptom description */}
            <textarea
                    placeholder="Describe your symptoms in your own words... (optional)"
                    value={description}
                    onChange={(e) => setDescription(e.target.value)}
                    rows={3}
                className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-200 outline-none resize-none"
            />

            {/* Action Button */}
            {selected.length > 0 && (
                <div className="space-y-3">
                    {mutationError && (
                        <div className="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                            {mutationError}
                        </div>
                    )}
                    <button
                        onClick={handleFullCheck}
                        disabled={checkMutation.isPending || (user?.credits ?? 0) < 1}
                        className="w-full rounded-lg bg-teal-600 px-5 py-3 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50 shadow-md shadow-teal-200"
                    >
                        {checkMutation.isPending ? 'Analyzing with AI...' : 'Analyze with AI (1 credit)'}
                    </button>
                </div>
            )}

            {/* Results */}
            {result && (
                <div className="space-y-4">
                    {/* Suggested panels */}
                    {result.suggested_panels?.length > 0 && (
                        <div className="rounded-xl border border-gray-200 bg-white p-6">
                            <h3 className="font-semibold text-gray-900 mb-3">
                                {result.suggested_panels.length} Test Panel{result.suggested_panels.length > 1 ? 's' : ''} Suggested
                            </h3>
                            <ul className="space-y-2">
                                {result.suggested_panels.map((p) => (
                                    <li key={p.id}>
                                        <Link
                                            to={`/lab-results/${p.slug}`}
                                            className="text-teal-600 hover:text-teal-700 underline text-sm"
                                        >
                                            {p.name}
                                        </Link>
                                        <span className="ml-2 text-xs text-gray-400">{p.tests?.length || 0} tests</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {/* AI Interpretation */}
                    {result.interpretation && (
                        <div className="rounded-xl border border-gray-200 bg-white p-6">
                            <div className="flex items-center gap-2 mb-3">
                                <span className="text-lg">🤖</span>
                                <h3 className="font-semibold text-gray-900">AI Guidance</h3>
                            </div>
                            <div className="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap">
                                {result.interpretation}
                            </div>
                        </div>
                    )}
                </div>
            )}

            <Disclaimer />
        </div>
    );
}
