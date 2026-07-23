import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import useAuthStore from '../../stores/authStore';
import api from '../../lib/api';

export default function HealthProfileOnboarding() {
    const { user, fetchUser } = useAuthStore();
    const navigate = useNavigate();
    const [form, setForm] = useState({
        date_of_birth: user?.health_profile?.date_of_birth || '',
        sex: user?.health_profile?.sex || '',
        is_pregnant: user?.health_profile?.is_pregnant || false,
    });
    const [error, setError] = useState(null);
    const [loading, setLoading] = useState(false);

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setForm({ ...form, [name]: type === 'checkbox' ? checked : value });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError(null);
        setLoading(true);

        try {
            await api.put('/health-profile', {
                ...form,
                profile_completed: !!form.date_of_birth && !!form.sex,
            });
            await fetchUser();
            navigate('/dashboard');
        } catch (err) {
            setError(err?.message || 'Failed to save profile');
        } finally {
            setLoading(false);
        }
    };

    const handleSkip = async () => {
        // Save partial profile as incomplete
        try {
            await api.put('/health-profile', {
                date_of_birth: form.date_of_birth || null,
                sex: form.sex || null,
                is_pregnant: form.sex === 'female' ? form.is_pregnant : false,
                profile_completed: false,
            });
        } catch {
            // silent
        }
        await fetchUser();
        navigate('/dashboard');
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-10">
            <div className="w-full max-w-sm">
                <h2 className="text-center text-2xl font-bold text-teal-600">
                    Lab<span className="text-gray-800">Doc</span>
                </h2>

                <div className="mt-8">
                    <h3 className="text-xl font-semibold text-gray-900">
                        Help us personalise your results
                    </h3>
                    <p className="mt-2 text-sm text-gray-500">
                        Reference ranges for lab tests vary by age, sex, and pregnancy status.
                        This helps us show you more accurate interpretations.
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="mt-6 space-y-4">
                    {error && (
                        <div className="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
                            {error}
                        </div>
                    )}

                    <div>
                        <label htmlFor="date_of_birth" className="block text-sm font-medium text-gray-700">
                            Date of birth
                        </label>
                        <input
                            id="date_of_birth"
                            name="date_of_birth"
                            type="date"
                            value={form.date_of_birth}
                            onChange={handleChange}
                            max={new Date().toISOString().split('T')[0]}
                            className="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-200 outline-none"
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            Sex
                        </label>
                        <div className="flex gap-4">
                            <label className="flex items-center gap-2">
                                <input
                                    type="radio"
                                    name="sex"
                                    value="male"
                                    checked={form.sex === 'male'}
                                    onChange={handleChange}
                                    className="text-teal-600 focus:ring-teal-500"
                                />
                                <span className="text-sm text-gray-700">Male</span>
                            </label>
                            <label className="flex items-center gap-2">
                                <input
                                    type="radio"
                                    name="sex"
                                    value="female"
                                    checked={form.sex === 'female'}
                                    onChange={handleChange}
                                    className="text-teal-600 focus:ring-teal-500"
                                />
                                <span className="text-sm text-gray-700">Female</span>
                            </label>
                        </div>
                    </div>

                    {form.sex === 'female' && (
                        <div className="flex items-center gap-2">
                            <input
                                id="is_pregnant"
                                name="is_pregnant"
                                type="checkbox"
                                checked={form.is_pregnant}
                                onChange={handleChange}
                                className="h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                            />
                            <label htmlFor="is_pregnant" className="text-sm text-gray-700">
                                I am currently pregnant
                            </label>
                        </div>
                    )}

                    <button
                        type="submit"
                        disabled={loading}
                        className="w-full rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50 transition-colors"
                    >
                        {loading ? 'Saving...' : 'Save & continue'}
                    </button>

                    <button
                        type="button"
                        onClick={handleSkip}
                        className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors"
                    >
                        Skip for now
                    </button>

                    <p className="text-center text-xs text-gray-400">
                        You can update this later from your dashboard.
                    </p>
                </form>
            </div>
        </div>
    );
}
