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
        <div className="flex min-h-screen items-center justify-center bg-neutral-50 px-4 py-10">
            <div className="w-full max-w-sm animate-fade-in-up">
                <Link to="/" className="block text-center">
                    <span className="text-2xl font-extrabold tracking-tight text-teal-700">Health</span>
                    <span className="text-2xl font-extrabold tracking-tight text-neutral-900">Intel</span>
                </Link>

                <div className="mt-8 card p-6">
                    <h2 className="text-xl font-bold text-neutral-900 text-center">Create your account</h2>
                    <div className="mt-2 flex items-center justify-center">
                        <span className="inline-flex items-center gap-1 rounded-full bg-teal-50 border border-teal-200 px-3 py-1 text-xs font-semibold text-teal-700">
                            ✨ Get 3 free credits
                        </span>
                    </div>

                    <form onSubmit={handleSubmit} className="mt-5 space-y-4">
                        {error && (
                            <div className="rounded-xl bg-danger-50 border border-danger-200 px-4 py-3 text-sm text-danger-700 font-medium">
                                {error}
                            </div>
                        )}

                        <div>
                            <label htmlFor="name" className="text-sm font-semibold text-neutral-700">Full name</label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                required
                                value={form.name}
                                onChange={handleChange}
                                className="input-base mt-1.5"
                                placeholder="John Doe"
                            />
                        </div>

                        <div>
                            <label htmlFor="email" className="text-sm font-semibold text-neutral-700">Email</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                required
                                value={form.email}
                                onChange={handleChange}
                                className="input-base mt-1.5"
                                placeholder="you@example.com"
                            />
                        </div>

                        <div>
                            <label htmlFor="password" className="text-sm font-semibold text-neutral-700">Password</label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                minLength={8}
                                value={form.password}
                                onChange={handleChange}
                                className="input-base mt-1.5"
                                placeholder="Min. 8 characters"
                            />
                        </div>

                        <div>
                            <label htmlFor="password_confirmation" className="text-sm font-semibold text-neutral-700">Confirm password</label>
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                required
                                value={form.password_confirmation}
                                onChange={handleChange}
                                className="input-base mt-1.5"
                                placeholder="Repeat your password"
                            />
                        </div>

                        <div className="flex items-start gap-2.5">
                            <input
                                id="consent_ndpr"
                                name="consent_ndpr"
                                type="checkbox"
                                checked={form.consent_ndpr}
                                onChange={handleChange}
                                className="mt-0.5 h-4 w-4 rounded border-neutral-300 text-teal-600 focus:ring-teal-500"
                            />
                            <label htmlFor="consent_ndpr" className="text-xs text-neutral-500 leading-relaxed">
                                I consent to HealthIntel processing my health-related data in accordance with the Nigeria Data Protection Regulation (NDPR).
                            </label>
                        </div>

                        <button
                            type="submit"
                            disabled={loading}
                            className="btn btn-primary w-full"
                        >
                            {loading ? 'Creating account...' : 'Create account'}
                        </button>
                    </form>
                </div>

                <p className="mt-6 text-center text-sm text-neutral-500">
                    Already have an account?{' '}
                    <Link to="/login" className="font-bold text-teal-700 hover:text-teal-800">
                        Sign in
                    </Link>
                </p>
            </div>
        </div>
    );
}