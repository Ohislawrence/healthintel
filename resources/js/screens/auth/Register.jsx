import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import useAuthStore from '../../stores/authStore';

export default function Register() {
    const [form, setForm] = useState({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        consent_ndpr: false,
    });
    const [error, setError] = useState(null);
    const [loading, setLoading] = useState(false);
    const register = useAuthStore((s) => s.register);
    const navigate = useNavigate();

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setForm({ ...form, [name]: type === 'checkbox' ? checked : value });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError(null);

        if (form.password !== form.password_confirmation) {
            setError('Passwords do not match.');
            return;
        }

        if (!form.consent_ndpr) {
            setError('You must consent to data processing to create an account.');
            return;
        }

        setLoading(true);
        try {
            await register(form);
            navigate('/onboarding');
        } catch (err) {
            const msg = err?.message || err?.errors
                ? Object.values(err.errors).flat().join(', ')
                : 'Registration failed. Please try again.';
            setError(msg);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-10">
            <div className="w-full max-w-sm">
                <Link to="/" className="block text-center text-2xl font-bold text-teal-600">
                    Lab<span className="text-gray-800">Doc</span>
                </Link>

                <h2 className="mt-8 text-center text-xl font-semibold text-gray-900">
                    Create your account
                </h2>
                <p className="mt-2 text-center text-sm text-gray-500">
                    Get 3 free credits to interpret your lab results.
                </p>

                <form onSubmit={handleSubmit} className="mt-6 space-y-4">
                    {error && (
                        <div className="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
                            {error}
                        </div>
                    )}

                    <div>
                        <label htmlFor="name" className="block text-sm font-medium text-gray-700">
                            Full name
                        </label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            required
                            value={form.name}
                            onChange={handleChange}
                            className="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-200 outline-none"
                        />
                    </div>

                    <div>
                        <label htmlFor="email" className="block text-sm font-medium text-gray-700">
                            Email
                        </label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            required
                            value={form.email}
                            onChange={handleChange}
                            className="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-200 outline-none"
                        />
                    </div>

                    <div>
                        <label htmlFor="password" className="block text-sm font-medium text-gray-700">
                            Password
                        </label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            minLength={8}
                            value={form.password}
                            onChange={handleChange}
                            className="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-200 outline-none"
                        />
                    </div>

                    <div>
                        <label htmlFor="password_confirmation" className="block text-sm font-medium text-gray-700">
                            Confirm password
                        </label>
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            required
                            value={form.password_confirmation}
                            onChange={handleChange}
                            className="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-200 outline-none"
                        />
                    </div>

                    <div className="flex items-start gap-2">
                        <input
                            id="consent_ndpr"
                            name="consent_ndpr"
                            type="checkbox"
                            checked={form.consent_ndpr}
                            onChange={handleChange}
                            className="mt-1 h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                        />
                        <label htmlFor="consent_ndpr" className="text-xs text-gray-500">
                            I consent to LabDoc processing my health-related data in accordance with
                            the Nigeria Data Protection Regulation (NDPR).
                        </label>
                    </div>

                    <button
                        type="submit"
                        disabled={loading}
                        className="w-full rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50 transition-colors"
                    >
                        {loading ? 'Creating account...' : 'Create account'}
                    </button>
                </form>

                <p className="mt-6 text-center text-sm text-gray-600">
                    Already have an account?{' '}
                    <Link to="/login" className="font-semibold text-teal-600 hover:text-teal-700">
                        Sign in
                    </Link>
                </p>
            </div>
        </div>
    );
}
