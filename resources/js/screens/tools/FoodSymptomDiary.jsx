import React, { useState, useCallback, useEffect } from 'react';
import { Link } from 'react-router-dom';
import api from '../../lib/api';

const MEAL_TYPES = [
    { key: 'breakfast', label: 'Breakfast', icon: '🌅' },
    { key: 'lunch', label: 'Lunch', icon: '☀️' },
    { key: 'dinner', label: 'Dinner', icon: '🌙' },
    { key: 'snack', label: 'Snack', icon: '🍎' },
];

const SYMPTOM_OPTIONS = [
    'Bloating', 'Nausea', 'Headache', 'Fatigue', 'Heartburn', 'Stomach pain',
    'Diarrhea', 'Constipation', 'Skin rash', 'Itching', 'Cramps', 'Other',
];

export default function FoodSymptomDiary() {
    const [log, setLog] = useState([]);
    const [mealType, setMealType] = useState('breakfast');
    const [food, setFood] = useState('');
    const [selectedSymptoms, setSelectedSymptoms] = useState([]);
    const [notes, setNotes] = useState('');
    const [loading, setLoading] = useState(true);
    const [insights, setInsights] = useState(null);
    const [insightsLoading, setInsightsLoading] = useState(false);

    useEffect(() => {
        loadToday().finally(() => setLoading(false));
    }, []);

    const loadToday = async () => {
        try {
            const res = await api.get('/health-metrics/today');
            setLog(res?.data?.trackers?.food_symptom || []);
        } catch {}
    };

    const saveToday = useCallback(async (entries) => {
        try {
            await api.post('/health-metrics/sync', {
                date: new Date().toISOString().split('T')[0],
                data: { food_symptom: entries },
            });
        } catch {}
    }, []);

    const toggleSymptom = (sym) => {
        setSelectedSymptoms(prev => prev.includes(sym) ? prev.filter(s => s !== sym) : [...prev, sym]);
    };

    const loadInsights = async () => {
        setInsightsLoading(true);
        try {
            const res = await api.get('/health-metrics/food-insights');
            setInsights(res?.data || null);
        } catch {
            setInsights({ available: false, message: 'Could not load insights right now.' });
        } finally {
            setInsightsLoading(false);
        }
    };

    const addEntry = () => {
        if (!food.trim() && selectedSymptoms.length === 0) return;
        const entry = {
            meal_type: mealType,
            food: food.trim(),
            symptoms: selectedSymptoms,
            notes: notes.trim(),
            time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        };
        const updated = [entry, ...log];
        setLog(updated);
        saveToday(updated);
        setFood('');
        setSelectedSymptoms([]);
        setNotes('');
    };

    const canAdd = food.trim() || selectedSymptoms.length > 0;

    return (
        <div className="space-y-5">
            <Link to="/health-tools" className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back to tools</Link>
            <div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Food & Symptom Diary</p>
                <p className="text-sm text-neutral-500 mt-0.5">Track what you eat and how you feel — spot patterns over time.</p>
            </div>

            {/* Add Entry */}
            <div className="card p-5">
                <p className="text-base font-bold text-neutral-900 mb-4">New Entry</p>

                {/* Meal Type */}
                <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Meal Type</p>
                <div className="grid grid-cols-4 gap-2 mb-4">
                    {MEAL_TYPES.map((m) => (
                        <button
                            key={m.key}
                            onClick={() => setMealType(m.key)}
                            className={`flex flex-col items-center gap-1 p-3 rounded-xl border-2 transition-all ${mealType === m.key ? 'border-green-500 bg-green-50' : 'border-neutral-200 bg-neutral-50'}`}
                        >
                            <span className="text-lg">{m.icon}</span>
                            <span className={`text-xs font-bold ${mealType === m.key ? 'text-green-700' : 'text-neutral-500'}`}>{m.label}</span>
                        </button>
                    ))}
                </div>

                {/* Food */}
                <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">What did you eat?</p>
                <input
                    type="text"
                    value={food}
                    onChange={e => setFood(e.target.value)}
                    placeholder="e.g. Jollof rice with chicken..."
                    className="w-full bg-neutral-50 rounded-xl border border-neutral-200 px-4 py-3 text-sm font-semibold text-neutral-900 outline-none mb-4"
                />

                {/* Symptoms */}
                <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Any symptoms?</p>
                <div className="flex flex-wrap gap-2 mb-4">
                    {SYMPTOM_OPTIONS.map((sym) => (
                        <button
                            key={sym}
                            onClick={() => toggleSymptom(sym)}
                            className={`text-xs font-bold px-3 py-2 rounded-xl border transition-all ${selectedSymptoms.includes(sym) ? 'bg-amber-100 border-amber-300 text-amber-700' : 'bg-neutral-50 border-neutral-200 text-neutral-500 hover:border-amber-200'}`}
                        >
                            {sym}
                        </button>
                    ))}
                </div>

                {/* Notes */}
                <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Notes (optional)</p>
                <textarea
                    value={notes}
                    onChange={e => setNotes(e.target.value)}
                    rows={2}
                    placeholder="Any additional notes..."
                    className="w-full bg-neutral-50 rounded-xl border border-neutral-200 px-4 py-3 text-sm text-neutral-700 outline-none resize-none mb-4"
                />

                <button onClick={addEntry} disabled={!canAdd} className={`btn w-full ${canAdd ? 'bg-green-500 hover:bg-green-600 text-white' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed'}`}>
                    Log Entry
                </button>
            </div>

            {/* Insights */}
            <div className="card p-5">
                <div className="flex items-center justify-between mb-3">
                    <p className="text-base font-bold text-neutral-900">Pattern Insights</p>
                    <button
                        onClick={loadInsights}
                        disabled={insightsLoading}
                        className="text-xs font-bold text-green-600 hover:text-green-700 disabled:text-neutral-400"
                    >
                        {insightsLoading ? 'Analyzing…' : insights ? 'Refresh' : 'Analyze my entries'}
                    </button>
                </div>
                {insights?.available && insights?.insight ? (
                    <div className="whitespace-pre-wrap text-sm text-neutral-700 leading-relaxed">{insights.insight}</div>
                ) : insights ? (
                    <p className="text-sm text-neutral-500">{insights.message || 'No patterns surfaced yet.'}</p>
                ) : (
                    <p className="text-sm text-neutral-500">
                        Surface possible food↔symptom patterns from your logged entries. Always discuss with a doctor.
                    </p>
                )}
            </div>

            {/* Today's Log */}
            {log.length > 0 && (
                <div className="card p-5">
                    <p className="text-base font-bold text-neutral-900 mb-4">Today's Entries</p>
                    <div className="space-y-3">
                        {log.map((entry, i) => {
                            const meal = MEAL_TYPES.find(m => m.key === entry.meal_type);
                            return (
                                <div key={i} className="bg-neutral-50 rounded-xl p-4">
                                    <div className="flex items-center gap-3 mb-2">
                                        <span className="text-lg">{meal?.icon || '🍽️'}</span>
                                        <span className="text-sm font-bold text-neutral-900">{meal?.label || entry.meal_type}</span>
                                        <span className="text-xs text-neutral-400 ml-auto">{entry.time}</span>
                                    </div>
                                    {entry.food && <p className="text-sm text-neutral-700 mb-2">{entry.food}</p>}
                                    {entry.symptoms?.length > 0 && (
                                        <div className="flex flex-wrap gap-1.5 mt-2">
                                            {entry.symptoms.map(s => (
                                                <span key={s} className="text-xs font-bold bg-amber-100 text-amber-700 border border-amber-200 rounded-lg px-2 py-0.5">{s}</span>
                                            ))}
                                        </div>
                                    )}
                                    {entry.notes && <p className="text-xs text-neutral-400 mt-2 italic">{entry.notes}</p>}
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}

            <div className="card p-5 bg-amber-50 border-amber-200">
                <p className="text-base font-bold text-amber-800 mb-2">About Food & Symptom Diary</p>
                <p className="text-sm text-amber-800 leading-relaxed">Tracking what you eat alongside symptoms helps identify food intolerances, allergies, and triggers. Share this data with your healthcare provider for better insights.</p>
            </div>
        </div>
    );
}