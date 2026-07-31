import React, { useState, useMemo } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useMutation } from '@tanstack/react-query';
import api from '../../lib/api';
import useAuthStore from '../../stores/authStore';

const STEPS = [
    { id: 1, label: 'Basic Info', icon: '◉' },
    { id: 2, label: 'Body', icon: '▲' },
    { id: 3, label: 'Medical', icon: '⬡' },
    { id: 4, label: 'Review', icon: '✓' },
];

const BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

const COMMON_CONDITIONS = [
    'Diabetes', 'Hypertension', 'Asthma', 'Thyroid disorder', 'Malaria',
    'Typhoid', 'High cholesterol', 'Arthritis', 'Migraine', 'Anemia',
];

const COMMON_MEDS = [
    'Metformin', 'Lisinopril', 'Amlodipine', 'Levothyroxine', 'Atorvastatin',
    'Omeprazole', 'Metoprolol', 'Losartan', 'Insulin', 'None',
];

export default function HealthProfile() {
    const navigate = useNavigate();
    const { user, fetchUser } = useAuthStore();
    const existing = user?.health_profile || {};

    const [step, setStep] = useState(1);
    const [form, setForm] = useState({
        date_of_birth: existing.date_of_birth || '',
        sex: existing.sex || '',
        is_pregnant: existing.is_pregnant || false,
        height_cm: existing.height_cm || '',
        weight_kg: existing.weight_kg || '',
        blood_type: existing.blood_type || '',
        medical_conditions: Array.isArray(existing.medical_conditions)
            ? existing.medical_conditions
            : (existing.medical_conditions ? existing.medical_conditions.split(',').map(s => s.trim()).filter(Boolean) : []),
        current_medications: existing.current_medications || '',
    });
    const [error, setError] = useState(null);

    const isComplete = existing.profile_completed;

    const updateMutation = useMutation({
        mutationFn: (data) => api.put('/profile', { ...data, profile_completed: true }),
        onSuccess: async () => {
            await fetchUser();
            navigate('/dashboard');
        },
        onError: (err) => setError(err?.message || 'Failed to save profile.'),
    });

    const setField = (name, value) => setForm(prev => ({ ...prev, [name]: value }));

    const toggleArrayItem = (field, item) => {
        setForm(prev => {
            const arr = prev[field] || [];
            return { ...prev, [field]: arr.includes(item) ? arr.filter(x => x !== item) : [...arr, item] };
        });
    };

    const age = useMemo(() => {
        if (!form.date_of_birth) return null;
        return Math.floor((new Date() - new Date(form.date_of_birth)) / (365.25 * 86400000));
    }, [form.date_of_birth]);

    const bmi = useMemo(() => {
        if (!form.height_cm || !form.weight_kg) return null;
        const h = parseFloat(form.height_cm) / 100;
        const w = parseFloat(form.weight_kg);
        if (!h || !w || h <= 0 || w <= 0) return null;
        return Math.round((w / (h * h)) * 10) / 10;
    }, [form.height_cm, form.weight_kg]);

    const bmiCategory = useMemo(() => {
        if (bmi === null) return null;
        if (bmi < 18.5) return { label: 'Underweight', color: '#0EA5E9' };
        if (bmi < 25) return { label: 'Healthy', color: '#22C55E' };
        if (bmi < 30) return { label: 'Overweight', color: '#F59E0B' };
        return { label: 'Obese', color: '#EF4444' };
    }, [bmi]);

    const handleSubmit = (e) => {
        e.preventDefault();
        setError(null);
        updateMutation.mutate({
            ...form,
            medical_conditions: form.medical_conditions.join(', '),
        });
    };

    const canProceed = () => {
        if (step === 1) return form.date_of_birth && form.sex;
        if (step === 2) return true; // body is optional
        return true;
    };

    return (
        <div className="max-w-lg mx-auto space-y-6 py-4">
            {/* Header */}
            <div className="text-center">
                <div className="w-16 h-16 rounded-2xl bg-teal-50 border-2 border-teal-200 flex items-center justify-center mx-auto mb-4">
                    <span className="text-3xl">◉</span>
                </div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">
                    {isComplete ? 'Health Profile' : 'Complete Your Profile'}
                </p>
                <p className="text-sm text-neutral-500 mt-1 max-w-xs mx-auto leading-relaxed">
                    {isComplete
                        ? 'Update your health information for more accurate AI-powered recommendations.'
                        : 'Your health data helps our AI provide personalized lab interpretations and symptom analysis tailored to you.'}
                </p>
            </div>

            {/* Progress Steps */}
            <div className="flex items-center gap-1">
                {STEPS.map((s, i) => (
                    <React.Fragment key={s.id}>
                        <button
                            onClick={() => setStep(s.id)}
                            className={`flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-bold transition-all ${
                                step === s.id
                                    ? 'bg-teal-50 text-teal-700 border border-teal-200'
                                    : step > s.id
                                    ? 'text-teal-500'
                                    : 'text-neutral-400'
                            }`}
                        >
                            <span className={`w-5 h-5 rounded-full flex items-center justify-center text-[10px] ${
                                step === s.id ? 'bg-teal-500 text-white' : step > s.id ? 'bg-teal-100 text-teal-600' : 'bg-neutral-100 text-neutral-400'
                            }`}>
                                {step > s.id ? '✓' : s.icon}
                            </span>
                            <span className="hidden sm:inline">{s.label}</span>
                        </button>
                        {i < STEPS.length - 1 && (
                            <div className={`flex-1 h-0.5 rounded-full ${step > s.id ? 'bg-teal-300' : 'bg-neutral-200'}`} />
                        )}
                    </React.Fragment>
                ))}
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                {error && (
                    <div className="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 font-medium">{error}</div>
                )}

                {/* Step 1: Basic Info */}
                {step === 1 && (
                    <div className="card p-5 space-y-4">
                        <div className="flex items-center gap-3 pb-4 border-b border-neutral-100">
                            <div className="w-9 h-9 rounded-lg bg-purple-50 flex items-center justify-center text-base text-purple-600">◉</div>
                            <div>
                                <p className="text-base font-bold text-neutral-900">Basic Information</p>
                                <p className="text-xs text-neutral-400">This helps us calculate age-specific reference ranges</p>
                            </div>
                        </div>

                        <div>
                            <label className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1.5 block">Date of Birth</label>
                            <input
                                type="date"
                                value={form.date_of_birth}
                                onChange={e => setField('date_of_birth', e.target.value)}
                                className="w-full bg-neutral-50 rounded-xl border border-neutral-200 px-4 py-3 text-sm font-semibold text-neutral-900 outline-none focus:border-teal-300 focus:ring-2 focus:ring-teal-50"
                                max={new Date().toISOString().split('T')[0]}
                            />
                            {age !== null && (
                                <p className="text-xs text-teal-600 mt-1.5 font-semibold">{age} years old</p>
                            )}
                        </div>

                        <div>
                            <label className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1.5 block">Biological Sex</label>
                            <div className="grid grid-cols-2 gap-3">
                                {[{ key: 'male', label: 'Male', icon: '♂' }, { key: 'female', label: 'Female', icon: '♀' }].map(g => (
                                    <button
                                        key={g.key}
                                        type="button"
                                        onClick={() => { setField('sex', g.key); if (g.key === 'male') setField('is_pregnant', false); }}
                                        className={`flex items-center justify-center gap-2 py-3.5 rounded-xl text-sm font-bold border-2 transition-all ${
                                            form.sex === g.key
                                                ? 'border-teal-500 bg-teal-50 text-teal-700'
                                                : 'border-neutral-200 bg-neutral-50 text-neutral-500 hover:border-teal-200'
                                        }`}
                                    >
                                        <span className="text-lg">{g.icon}</span>
                                        {g.label}
                                    </button>
                                ))}
                            </div>
                        </div>

                        {form.sex === 'female' && (
                            <div className={`rounded-xl p-4 border-2 transition-all ${form.is_pregnant ? 'border-pink-400 bg-pink-50' : 'border-neutral-200 bg-neutral-50'}`}>
                                <label className="flex items-center gap-3 cursor-pointer">
                                    <div className={`w-5 h-5 rounded border-2 flex items-center justify-center transition-all ${form.is_pregnant ? 'border-pink-500 bg-pink-500' : 'border-neutral-300 bg-white'}`}>
                                        {form.is_pregnant && <span className="text-white text-xs">✓</span>}
                                    </div>
                                    <input
                                        type="checkbox"
                                        checked={form.is_pregnant}
                                        onChange={e => setField('is_pregnant', e.target.checked)}
                                        className="sr-only"
                                    />
                                    <div>
                                        <p className={`text-sm font-bold ${form.is_pregnant ? 'text-pink-700' : 'text-neutral-700'}`}>I am pregnant</p>
                                        <p className={`text-xs ${form.is_pregnant ? 'text-pink-500' : 'text-neutral-400'}`}>This affects reference ranges and recommendations</p>
                                    </div>
                                </label>
                            </div>
                        )}
                    </div>
                )}

                {/* Step 2: Body Measurements */}
                {step === 2 && (
                    <div className="card p-5 space-y-4">
                        <div className="flex items-center gap-3 pb-4 border-b border-neutral-100">
                            <div className="w-9 h-9 rounded-lg bg-orange-50 flex items-center justify-center text-base text-orange-500">▲</div>
                            <div>
                                <p className="text-base font-bold text-neutral-900">Body Measurements</p>
                                <p className="text-xs text-neutral-400">Optional — helps with BMI and medication dosing context</p>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1.5 block">Height (cm)</label>
                                <div className="flex items-center bg-neutral-50 rounded-xl border border-neutral-200 overflow-hidden focus-within:border-teal-300 focus-within:ring-2 focus-within:ring-teal-50">
                                    <input
                                        type="number"
                                        value={form.height_cm}
                                        onChange={e => setField('height_cm', e.target.value)}
                                        step="0.1"
                                        placeholder="170"
                                        className="flex-1 px-4 py-3 text-lg font-bold text-neutral-900 bg-transparent outline-none"
                                    />
                                    <span className="text-sm font-semibold text-neutral-400 pr-4">cm</span>
                                </div>
                            </div>
                            <div>
                                <label className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1.5 block">Weight (kg)</label>
                                <div className="flex items-center bg-neutral-50 rounded-xl border border-neutral-200 overflow-hidden focus-within:border-teal-300 focus-within:ring-2 focus-within:ring-teal-50">
                                    <input
                                        type="number"
                                        value={form.weight_kg}
                                        onChange={e => setField('weight_kg', e.target.value)}
                                        step="0.1"
                                        placeholder="65"
                                        className="flex-1 px-4 py-3 text-lg font-bold text-neutral-900 bg-transparent outline-none"
                                    />
                                    <span className="text-sm font-semibold text-neutral-400 pr-4">kg</span>
                                </div>
                            </div>
                        </div>

                        {bmi !== null && (
                            <div className="bg-neutral-50 rounded-xl p-4 flex items-center gap-4">
                                <div className="w-14 h-14 rounded-xl flex items-center justify-center flex-shrink-0" style={{ backgroundColor: bmiCategory.color + '15' }}>
                                    <span className="text-2xl font-extrabold" style={{ color: bmiCategory.color }}>{bmi}</span>
                                </div>
                                <div>
                                    <p className="text-sm font-bold text-neutral-900">Your BMI: {bmi}</p>
                                    <p className="text-xs" style={{ color: bmiCategory.color }}>{bmiCategory.label}</p>
                                </div>
                            </div>
                        )}

                        <div>
                            <label className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1.5 block">Blood Type</label>
                            <div className="grid grid-cols-4 gap-2">
                                {BLOOD_TYPES.map(bt => (
                                    <button
                                        key={bt}
                                        type="button"
                                        onClick={() => setField('blood_type', bt)}
                                        className={`py-2.5 rounded-xl text-sm font-bold border-2 transition-all ${
                                            form.blood_type === bt
                                                ? 'border-teal-500 bg-teal-50 text-teal-700'
                                                : 'border-neutral-200 bg-neutral-50 text-neutral-500 hover:border-teal-200'
                                        }`}
                                    >
                                        {bt}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>
                )}

                {/* Step 3: Medical History */}
                {step === 3 && (
                    <div className="card p-5 space-y-4">
                        <div className="flex items-center gap-3 pb-4 border-b border-neutral-100">
                            <div className="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center text-base text-emerald-600">⬡</div>
                            <div>
                                <p className="text-base font-bold text-neutral-900">Medical History</p>
                                <p className="text-xs text-neutral-400">Helps our AI tailor recommendations to your health context</p>
                            </div>
                        </div>

                        <div>
                            <label className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2 block">Select conditions you have (tap to toggle)</label>
                            <div className="flex flex-wrap gap-2">
                                {COMMON_CONDITIONS.map(c => (
                                    <button
                                        key={c}
                                        type="button"
                                        onClick={() => toggleArrayItem('medical_conditions', c)}
                                        className={`text-xs font-bold px-3 py-2 rounded-xl border transition-all ${
                                            form.medical_conditions.includes(c)
                                                ? 'bg-amber-50 border-amber-300 text-amber-700'
                                                : 'bg-neutral-50 border-neutral-200 text-neutral-500 hover:border-amber-200'
                                        }`}
                                    >
                                        {c}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div>
                            <label className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2 block">Current medications</label>
                            <div className="flex flex-wrap gap-2 mb-3">
                                {COMMON_MEDS.map(m => (
                                    <button
                                        key={m}
                                        type="button"
                                        onClick={() => {
                                            const current = form.current_medications ? form.current_medications.split(',').map(s => s.trim()).filter(Boolean) : [];
                                            const updated = current.includes(m) ? current.filter(x => x !== m) : [...current, m];
                                            setField('current_medications', updated.join(', '));
                                        }}
                                        className={`text-xs font-bold px-3 py-2 rounded-xl border transition-all ${
                                            (form.current_medications || '').includes(m)
                                                ? 'bg-blue-50 border-blue-300 text-blue-700'
                                                : 'bg-neutral-50 border-neutral-200 text-neutral-500 hover:border-blue-200'
                                        }`}
                                    >
                                        {m}
                                    </button>
                                ))}
                            </div>
                            <textarea
                                value={form.current_medications}
                                onChange={e => setField('current_medications', e.target.value)}
                                rows={2}
                                placeholder="Or type your medications manually..."
                                className="w-full bg-neutral-50 rounded-xl border border-neutral-200 px-4 py-3 text-sm text-neutral-700 outline-none resize-none focus:border-teal-300"
                            />
                        </div>
                    </div>
                )}

                {/* Step 4: Review */}
                {step === 4 && (
                    <div className="card p-5 space-y-4">
                        <div className="flex items-center gap-3 pb-4 border-b border-neutral-100">
                            <div className="w-9 h-9 rounded-lg bg-teal-50 flex items-center justify-center text-base text-teal-600">✓</div>
                            <div>
                                <p className="text-base font-bold text-neutral-900">Review Your Profile</p>
                                <p className="text-xs text-neutral-400">This data will be used to improve your results</p>
                            </div>
                        </div>

                        <div className="space-y-2 text-sm">
                            <div className="flex justify-between py-2.5 border-b border-neutral-50">
                                <span className="text-neutral-500">Age</span>
                                <span className="font-bold text-neutral-900">{age !== null ? `${age} years` : '—'}</span>
                            </div>
                            <div className="flex justify-between py-2.5 border-b border-neutral-50">
                                <span className="text-neutral-500">Sex</span>
                                <span className="font-bold text-neutral-900 capitalize">{form.sex || '—'}</span>
                            </div>
                            {form.sex === 'female' && (
                                <div className="flex justify-between py-2.5 border-b border-neutral-50">
                                    <span className="text-neutral-500">Pregnant</span>
                                    <span className="font-bold text-neutral-900">{form.is_pregnant ? 'Yes' : 'No'}</span>
                                </div>
                            )}
                            <div className="flex justify-between py-2.5 border-b border-neutral-50">
                                <span className="text-neutral-500">Height / Weight</span>
                                <span className="font-bold text-neutral-900">{form.height_cm ? `${form.height_cm}cm / ${form.weight_kg}kg` : '—'}</span>
                            </div>
                            <div className="flex justify-between py-2.5 border-b border-neutral-50">
                                <span className="text-neutral-500">Blood Type</span>
                                <span className="font-bold text-neutral-900">{form.blood_type || '—'}</span>
                            </div>
                            <div className="flex justify-between py-2.5 border-b border-neutral-50">
                                <span className="text-neutral-500">Conditions</span>
                                <span className="font-bold text-neutral-900 text-right max-w-[60%]">
                                    {form.medical_conditions.length > 0 ? form.medical_conditions.join(', ') : '—'}
                                </span>
                            </div>
                            <div className="flex justify-between py-2.5">
                                <span className="text-neutral-500">Medications</span>
                                <span className="font-bold text-neutral-900 text-right max-w-[60%] truncate">
                                    {form.current_medications || '—'}
                                </span>
                            </div>
                        </div>

                        {/* Privacy Note */}
                        <div className="bg-teal-50 rounded-xl p-4 border border-teal-200">
                            <div className="flex gap-2.5">
                                <span className="text-lg flex-shrink-0">🔒</span>
                                <p className="text-xs text-teal-700 leading-relaxed">
                                    Your health profile is stored securely and used to improve the accuracy of your lab result interpretations and symptom analysis. It is never shared without your consent.
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Navigation Buttons */}
                <div className="flex gap-3">
                    {step > 1 && (
                        <button type="button" onClick={() => setStep(s => s - 1)} className="btn btn-outline flex-1">
                            Back
                        </button>
                    )}
                    {step < 4 ? (
                        <button
                            type="button"
                            onClick={() => setStep(s => s + 1)}
                            disabled={!canProceed()}
                            className={`btn flex-1 ${canProceed() ? 'btn-primary' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed'}`}
                        >
                            Continue
                        </button>
                    ) : (
                        <button type="submit" disabled={updateMutation.isPending} className="btn btn-primary flex-1">
                            {updateMutation.isPending ? 'Saving...' : isComplete ? 'Update Profile' : 'Complete & Save'}
                        </button>
                    )}
                </div>

                {step === 4 && !isComplete && (
                    <p className="text-xs text-neutral-400 text-center">
                        You can update this anytime from your dashboard
                    </p>
                )}

                {step === 4 && isComplete && (
                    <Link to="/dashboard" className="block text-center text-sm font-semibold text-neutral-400 hover:text-neutral-600">
                        Skip — go to dashboard
                    </Link>
                )}
            </form>
        </div>
    );
}