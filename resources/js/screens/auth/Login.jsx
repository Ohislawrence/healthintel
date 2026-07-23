import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import useAuthStore from '../../stores/authStore';

export default function Login() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [error, setError] = useState(null);
    const [loading, setLoading] = useState(false);
    const login = useAuthStore((s) => s.login);
    const navigate = useNavigate();

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError(null);
        setLoading(true);
        try {
            await login({ email, password });
            const user = useAuthStore.getState().user;
            if (user?.roles?.includes('admin')) {
                navigate('/admin');
            } else {
                navigate('/dashboard');
            }
        } catch (err) {
            setError(err?.message || 'Login failed. Please check your credentials.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-neutral-50 px-4">
            <div className="w-full max-w-sm animate-fade-in-up">
                {/* Logo */}
                <Link to="/" className="block text-center">
                    <span className="text-2xl font-extrabold tracking-tight text-teal-700">Health</span>
                    <span className="text-2xl font-extrabold tracking-tight text-neutral-900">Intel</span>
                </Link>

                <div className="mt-10 card p-6">
                    <h2 className="text-xl font-bold text-neutral-900 text-center">Welcome back</h2>
                    <p className="text-sm text-neutral-500 text-center mt-1">Sign in to your account</p>

                    <form onSubmit={handleSubmit} className="mt-6 space-y-4">
                        {error && (
                            <div className="rounded-xl bg-danger-50 border border-danger-200 px-4 py-3 text-sm text-danger-700 font-medium">
                                {error}
                            </div>
                        )}

                        <div>
                            <label htmlFor="email" className="text-sm font-semibold text-neutral-700">Email</label>
                            <input
                                id="email"
                                type="email"
                                required
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                className="input-base mt-1.5"
                                placeholder="you@example.com"
                            />
                        </div>

                    <div>
                        <label htmlFor="password" className="text-sm font-semibold text-neutral-700">Password</label>
                        <div className="relative mt-1.5">
                            <input
                                id="password"
                                type={showPassword ? 'text' : 'password'}
                                required
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                className="input-base pr-12"
                                placeholder="••••••••"
                            />
                            <button
                                type="button"
                                onClick={() => setShowPassword(!showPassword)}
                                className="absolute right-3 top-1/2 -translate-y-1/2 text-lg text-neutral-400 hover:text-neutral-600"
                            >
                                {showPassword ? '🙈' : '👁'}
                            </button>
                        </div>
                        <div className="text-right mt-1.5">
                            <Link to="/forgot-password" className="text-xs font-semibold text-teal-600 hover:text-teal-700">
                                Forgot password?
                            </Link>
                        </div>
                    </div>

                        <button
                            type="submit"
                            disabled={loading}
                            className="btn btn-primary w-full"
                        >
                            {loading ? 'Signing in...' : 'Sign in'}
                        </button>
                    </form>
                </div>

                <p className="mt-6 text-center text-sm text-neutral-500">
                    Don't have an account?{' '}
                    <Link to="/register" className="font-bold text-teal-700 hover:text-teal-800">
                        Create one
                    </Link>
                </p>
            </div>
        </div>
    );
}