import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../lib/api';

export default function SymptomChecker() {
    const navigate = useNavigate();
    const [search, setSearch] = useState('');
    const [selectedSymptoms, setSelectedSymptoms] = useState([]);
    const [error, setError] = useState(null);

    const { data } = useQuery({
        queryKey: ['symptoms'],
        queryFn: () => api.get('/symptoms'),
    });
    const symptoms = data?.data || [];
    const filtered = symptoms.filter(s => 
        s.name?.toLowerCase().includes(search.toLowerCase()) ||
        s.description?.toLowerCase().includes(search.toLowerCase())
    );

    const toggleSymptom = (slug) => {
        setSelectedSymptoms(prev =>
            prev.includes(slug) ? prev.filter(s => s !== slug) : [...prev, slug]
        );
    };

    const suggestMutation = useMutation({
        mutationFn: (slugs) => api.post('/symptoms/suggest', { symptoms: slugs }),
        onSuccess: (res) => {
            navigate('/symptom-checker/results', { state: { recommendations: res.data, symptoms: selectedSymptoms } });
        },
        onError: (err) => setError(err?.message || 'Failed to get suggestions.'),
    });

    return (
        <div className="space-y-5">
            <div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Symptom Checker</p>
                <p className="text-sm font-medium text-neutral-500 mt-0.5">Select your symptoms for AI-powered test recommendations</p>
            </div>

            {error && (
                <div className="rounded-xl bg-danger-50 border border-danger-200 px-4 py-3 text-sm text-danger-700 font-medium">{error}</div>
            )}

            {/* Selected Symptoms */}
            {selectedSymptoms.length > 0 && (
                <div className="card p-4">
                    <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Selected ({selectedSymptoms.length})</p>
                    <div className="flex flex-wrap gap-2">
                        {selectedSymptoms.map(slug => (
                            <button
                                key={slug}
                                onClick={() => toggleSymptom(slug)}
                                className="flex items-center gap-1 bg-teal-50 border border-teal-200 rounded-lg px-3 py-1.5 text-xs font-semibold text-teal-700 hover:bg-teal-100 transition-colors"
                            >
                                {symptoms.find(s => s.slug === slug)?.name || slug}
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
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                {filtered.map(symptom => {
                    const active = selectedSymptoms.includes(symptom.slug);
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

            <button
                onClick={() => suggestMutation.mutate(selectedSymptoms)}
                disabled={selectedSymptoms.length === 0 || suggestMutation.isPending}
                className="btn btn-primary w-full"
            >
                {suggestMutation.isPending ? 'Analyzing...' : `Get Test Recommendations (${selectedSymptoms.length})`}
            </button>
        </div>
    );
}