import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../lib/api';
import useAuthStore from '../../stores/authStore';
import InterpretationCards from '../../components/ui/InterpretationCards';

export default function SymptomChecker() {
    const navigate = useNavigate();
    const { user, fetchUser } = useAuthStore();
    const [search, setSearch] = useState('');
    const [description, setDescription] = useState('');
    const [selected, setSelected] = useState([]);
    const [result, setResult] = useState(null);
    const [showSymptoms, setShowSymptoms] = useState(true);
    const [checkCompleted, setCheckCompleted] = useState(false);

    const { data } = useQuery({
        queryKey: ['symptoms'],
        queryFn: () => api.get('/symptoms'),
        staleTime: 5 * 60 * 1000,
    });
    const symptomsData = data?.data?.symptoms || {};
    const allSymptoms = Object.values(symptomsData).flat();

    const checkMutation = useMutation({
        mutationFn: async (payload) => {
            return api.post('/symptoms/check', payload);
        },
        onSuccess: async (res) => {
            setResult(res.data);
            setShowSymptoms(false);
            setCheckCompleted(true);
            await fetchUser();
        },
        onError: (err) => {
            alert(err?.message || 'Check failed. Please try again.');
        },
    });

    const toggleSymptom = (slug) => {
        setSelected(prev => prev.includes(slug) ? prev.filter(s => s !== slug) : [...prev, slug]);
        setResult(null);
        setCheckCompleted(false);
    };

    const filteredCategories = search
        ? Object.entries(symptomsData).reduce((acc, [cat, symptoms]) => {
            const f = symptoms.filter(s => s.name.toLowerCase().includes(search.toLowerCase()));
            if (f.length) acc[cat] = f;
            return acc;
        }, {})
        : symptomsData;

    const handleCheck = () => {
        if (selected.length === 0 && !description.trim()) return;
        checkMutation.mutate({
            symptoms: selected,
            description: description.trim() || undefined,
        });
    };

    const handleReset = () => {
        setSelected([]);
        setDescription('');
        setResult(null);
        setShowSymptoms(true);
        setCheckCompleted(false);
    };

    // ── Show Results (matching mobile SymptomCheckerScreen result view) ──
    if (checkCompleted && result) {
        return (
            <div className="max-w-xl mx-auto space-y-5">
                <button onClick={handleReset} className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ New check</button>

                <div>
                    <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Symptom Analysis</p>
                    <p className="text-sm text-neutral-500 mt-0.5">
                        {description ? 'Based on your description' : `Based on ${selected.length} symptom${selected.length !== 1 ? 's' : ''}`}
                    </p>
                </div>

                {/* Selected Symptoms Tags */}
                {selected.length > 0 && (
                    <div className="card p-4">
                        <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Reported Symptoms</p>
                        <div className="flex flex-wrap gap-1.5">
                            {selected.map(slug => {
                                const s = allSymptoms.find(x => x.slug === slug);
                                return (
                                    <span key={slug} className="bg-teal-50 border border-teal-200 rounded-lg px-2.5 py-1 text-xs font-semibold text-teal-700">
                                        {s?.name || slug}
                                    </span>
                                );
                            })}
                        </div>
                    </div>
                )}

                {/* AI Response */}
                {result?.interpretation_text ? (
                    <div className="card p-5 lg:p-6 border-teal-100">
                        <div className="flex items-center gap-3 mb-4 pb-4 border-b border-neutral-100">
                            <div className="w-10 h-10 rounded-lg bg-teal-50 flex items-center justify-center text-xl text-teal-600">♡</div>
                            <p className="text-lg font-extrabold text-neutral-900">AI Analysis</p>
                        </div>
                        <InterpretationCards markdownText={result.interpretation_text} />
                    </div>
                ) : result?.recommendations?.length > 0 ? (
                    <div className="space-y-3">
                        <p className="text-sm font-bold text-neutral-900">Recommended Tests</p>
                        {result.recommendations.map((panel) => (
                            <div key={panel.slug} className="card p-4">
                                <p className="text-sm font-bold text-neutral-900">{panel.name}</p>
                                <p className="text-xs text-neutral-500 mt-1 line-clamp-2">{panel.description || 'Recommended test panel'}</p>
                                <div className="flex items-center gap-2 mt-3">
                                    {panel.match_score && <span className="badge badge-success">Match {panel.match_score}%</span>}
                                    <button onClick={() => navigate(`/lab-results/${panel.slug}`)} className="ml-auto text-xs font-bold text-teal-700 hover:text-teal-800">
                                        Check Now →
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : null}

                {/* Disclaimer */}
                <div className="card p-4 bg-teal-50 border-teal-200 flex gap-2.5 items-start">
                    <span className="text-lg text-teal-600 mt-0.5">ℹ</span>
                    <p className="text-sm text-teal-700 leading-relaxed">This is not a medical diagnosis. Please consult a licensed healthcare professional.</p>
                </div>
            </div>
        );
    }

    // ── Input View ────────────────────────────
    return (
        <div className="space-y-5">
            <div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Symptom Checker</p>
                <p className="text-sm font-medium text-neutral-500 mt-0.5">Describe your symptoms for AI-powered guidance</p>
            </div>

            {/* Description Input */}
            <div className="card p-4">
                <label className="text-sm font-bold text-neutral-900 mb-2 block">Describe what you're feeling</label>
                <textarea
                    value={description}
                    onChange={(e) => setDescription(e.target.value)}
                    rows={4}
                    className="input-base resize-none"
                    placeholder="e.g. I've had a headache for 3 days with mild fever..."
                />
                <p className="text-xs text-neutral-400 mt-2">
                    {description.length > 0 ? `${description.length} characters` : 'Be as detailed as possible for more accurate results'}
                </p>
            </div>

            {/* OR Divider */}
            <div className="flex items-center gap-3">
                <div className="flex-1 h-px bg-neutral-200" />
                <span className="text-xs font-bold text-neutral-400 uppercase">Or select symptoms</span>
                <div className="flex-1 h-px bg-neutral-200" />
            </div>

            {/* Selected Tags */}
            {selected.length > 0 && (
                <div className="card p-4">
                    <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Selected ({selected.length})</p>
                    <div className="flex flex-wrap gap-2">
                        {selected.map(slug => (
                            <button
                                key={slug}
                                onClick={() => toggleSymptom(slug)}
                                className="flex items-center gap-1 bg-teal-50 border border-teal-200 rounded-lg px-3 py-1.5 text-xs font-semibold text-teal-700 hover:bg-teal-100 transition-colors"
                            >
                                {allSymptoms.find(s => s.slug === slug)?.name || slug}
                                <span className="text-teal-400">×</span>
                            </button>
                        ))}
                    </div>
                </div>
            )}

            {/* Search */}
            <input
                type="text"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="input-base"
                placeholder="⌕ Search symptoms..."
            />

            {/* Symptoms Grid */}
            <div className="space-y-4">
                {Object.entries(filteredCategories).map(([category, symptoms]) => (
                    <div key={category}>
                        <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">{category}</p>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            {symptoms.map(symptom => {
                                const active = selected.includes(symptom.slug);
                                return (
                                    <button
                                        key={symptom.slug}
                                        onClick={() => toggleSymptom(symptom.slug)}
                                        className={`text-left p-3 rounded-xl border transition-all ${
                                            active
                                                ? 'bg-teal-50 border-teal-300'
                                                : 'bg-white border-neutral-100 hover:border-teal-200'
                                        }`}
                                    >
                                        <p className="text-sm font-semibold text-neutral-900">{symptom.name}</p>
                                        <p className="text-xs text-neutral-400 mt-0.5 line-clamp-1">{symptom.description || ''}</p>
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </div>

            {/* Check Button */}
            <button
                onClick={handleCheck}
                disabled={checkMutation.isPending || (selected.length === 0 && !description.trim())}
                className="btn btn-primary w-full"
            >
                {checkMutation.isPending ? (
                    <span className="flex items-center gap-2">
                        <span className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                        Analyzing...
                    </span>
                ) : (
                    'Analyze Symptoms'
                )}
            </button>
        </div>
    );
}