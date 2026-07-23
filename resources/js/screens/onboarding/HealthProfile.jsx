import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation } from '@tanstack/react-query';
import api from '../../lib/api';
import useAuthStore from '../../stores/authStore';

export default function HealthProfile() {
    const navigate = useNavigate();
    const { user, fetchUser } = useAuthStore();
    const existing = user?.health_profile || {};

    const [form, setForm] = useState({
        date_of_birth: existing.date_of_birth || '',
        sex: existing.sex || '',
        is_pregnant: existing.is_pregnant || false,
        height_cm: existing.height_cm || '',
        weight_kg: existing.weight_kg || '',
        blood_type: existing.blood_type || '',
        medical_conditions: existing.medical_conditions || '',
        current_medications: existing.current_medications || '',
    });
    const [step, setStep] = useState(1);
    const [error, setError] = useState(null);

    const updateMutation = useMutation({
        mutationFn: (data) => api.put('/profile', data),
        onSuccess: async () => {
            await fetchUser();
            navigate('/dashboard');
        },
        onError: (err) => setError(err?.message || 'Failed to save profile.'),
    });

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setForm(prev => ({ ...prev, [name]: type === 'checkbox' ? checked : value }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setError(null);
        updateMutation.mutate(form);
    };

    const bloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    return (
        <div className="max-w-lg mx-auto space-y-5">
            <div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">
                    {existing.profile_completed ? 'Health Profile' : 'Complete Your Profile'}
                </p>
                <p className="text-sm text-neutral-500 mt-0.5">
                    This helps us provide more accurate lab result interpretations
                </p>
            </div>

            {/* Progress Steps */}
            <div className="flex gap-2">
                {[1, 2, 3].map((s) => (
                    <div key={s} className={`flex-1 h-1 rounded-full transition-all ${s <= step ? 'bg-teal-700' : 'bg-neutral-200'}`} />
                ))}
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                {error && (
                    <div className="rounded-xl bg-danger-50 border border-danger-200 px-4 py-3 text-sm text-danger-700 font-medium">{error}</div>
                )}

                {step === 1 && (
                    <div className="card p-5 space-y-4">
                        <p className="text-sm font-bold text-neutral-900">Basic Information</p>
                        <div>
                            <label className="text-sm font-semibold text-neutral-700">Date of Birth</label>
                            <input type="date" name="date_of_birth" value={form.date_of_birth} onChange={handleChange} className="input-base mt-1.5" />
                        </div>
                        <div>
                            <label className="text-sm font-semibold text-neutral-700">Sex</label>
                            <select name="sex" value={form.sex} onChange={handleChange} className="input-base mt-1.5">
                                <option value="">Select...</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                        {form.sex === 'female' && (
                            <div className="flex items-center gap-2.5">
                                <input type="checkbox" id="is_pregnant" name="is_pregnant" checked={form.is_pregnant} onChange={handleChange} className="h-4 w-4 rounded border-neutral-300 text-teal-600" />
                                <label htmlFor="is_pregnant" className="text-sm text-neutral-600">I am pregnant</label>
                            </div>
                        )}
                    </div>
                )}

                {step === 2 && (
                    <div className="card p-5 space-y-4">
                        <p className="text-sm font-bold text-neutral-900">Body Measurements</p>
                        <div>
                            <label className="text-sm font-semibold text-neutral-700">Height (cm)</label>
                            <input type="number" name="height_cm" value={form.height_cm} onChange={handleChange} step="0.1" className="input-base mt-1.5" placeholder="e.g. 170" />
                        </div>
                        <div>
                            <label className="text-sm font-semibold text-neutral-700">Weight (kg)</label>
                            <input type="number" name="weight_kg" value={form.weight_kg} onChange={handleChange} step="0.1" className="input-base mt-1.5" placeholder="e.g. 65" />
                        </div>
                        <div>
                            <label className="text-sm font-semibold text-neutral-700">Blood Type</label>
                            <div className="grid grid-cols-4 gap-2 mt-1.5">
                                {bloodTypes.map(bt => (
                                    <button
                                        key={bt}
                                        type="button"
                                        onClick={() => setForm(prev => ({ ...prev, blood_type: bt }))}
                                        className={`p-2 rounded-lg text-sm font-bold border transition-all ${
                                            form.blood_type === bt ? 'bg-teal-50 border-teal-300 text-teal-700' : 'bg-neutral-50 border-neutral-200 text-neutral-500 hover:border-teal-200'
                                        }`}
                                    >
                                        {bt}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>
                )}

                {step === 3 && (
                    <div className="card p-5 space-y-4">
                        <p className="text-sm font-bold text-neutral-900">Medical History</p>
                        <div>
                            <label className="text-sm font-semibold text-neutral-700">Medical Conditions</label>
                            <textarea name="medical_conditions" value={form.medical_conditions} onChange={handleChange} rows={3} className="input-base mt-1.5" placeholder="e.g. Diabetes, Hypertension..." />
                        </div>
                        <div>
                            <label className="text-sm font-semibold text-neutral-700">Current Medications</label>
                            <textarea name="current_medications" value={form.current_medications} onChange={handleChange} rows={3} className="input-base mt-1.5" placeholder="e.g. Metformin 500mg..." />
                        </div>
                    </div>
                )}

                <div className="flex gap-3">
                    {step > 1 && (
                        <button type="button" onClick={() => setStep(s => s - 1)} className="btn btn-outline flex-1">
                            Back
                        </button>
                    )}
                    {step < 3 ? (
                        <button type="button" onClick={() => setStep(s => s + 1)} className="btn btn-primary flex-1">
                            Continue
                        </button>
                    ) : (
                        <button type="submit" disabled={updateMutation.isPending} className="btn btn-primary flex-1">
                            {updateMutation.isPending ? 'Saving...' : 'Save Profile'}
                        </button>
                    )}
                </div>
            </form>
        </div>
    );
}