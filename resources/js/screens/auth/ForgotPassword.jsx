import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../../lib/api';

export default function ForgotPassword() {
    const [email, setEmail] = useState('');
    const [loading, setLoading] = useState(false);
    const [sent, setSent] = useState(false);
    const [token, setToken] = useState('');
    const [error, setError] = useState(null);

    const handleSendReset = async (e) => {
        e.preventDefault();
        setError(null);
        if (!email) return;
        setLoading(true);
        try {
            await api.post('/auth/forgot-password', { email });
            setSent(true);
        } catch (err) {
            setError(err?.message || 'Failed to send reset link.');
        } finally {
            setLoading(false);
        }
    };

    if (sent) {
        return (
            <div className="flex min-h-screen items-center justify-center bg-neutral-50 px-4">
                <div className="w-full max-w-sm animate-fade-in-up">
                    <Link to="/login" className="block text-center">
                        <span className="text-2xl font-extrabold tracking-tight text-teal-700">Health</span>
                        <span className="text-2xl font-extrabold tracking-tight text-neutral-900">Intel</span>
                    </Link>

                    <div className="mt-10 card p-6 text-center">
                        <span className="text-4xl block mb-4">✉️</span>
                        <h2 className="text-xl font-bold text-neutral-900">Check Your Inbox</h2>
                        <p className="text-sm text-neutral-500 mt-2 mb-4">
                            If an account exists for <strong>{email}</strong>, we've sent a password reset link.
                        </p>

                        <p className="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-4">Or enter reset token</p>

                        <input
                            type="text"
                            value={token}
                            onChange={(e) => setToken(e.target.value)}
                            className="input-base mb-3"
                            placeholder="Enter reset token from email"
                        />

                        <Link
                            to={`/reset-password?email=${encodeURIComponent(email)}&token=${encodeURIComponent(token)}`}
                            className={`btn btn-primary w-full ${!token ? 'opacity-50 pointer-events-none' : ''}`}
                        >
                            Continue
                        </Link>

                        <button onClick={() => setSent(false)} className="mt-4 text-sm font-semibold text-teal-600 hover:text-teal-700">
                            ← Try a different email
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="flex min-h-screen items-center justify-center bg-neutral-50 px-4">
            <div className="w-full max-w-sm animate-fade-in-up">
                <Link to="/login" className="block text-center">
                    <span className="text-2xl font-extrabold tracking-tight text-teal-700">Health</span>
                    <span className="text-2xl font-extrabold tracking-tight text-neutral-900">Intel</span>
                </Link>

                <div className="mt-10 card p-6">
                    <h2 className="text-xl font-bold text-neutral-900 text-center">Forgot Password?</h2>
                    <p className="text-sm text-neutral-500 text-center mt-1 mb-6">
                        Enter your email and we'll send you a reset code.
                    </p>

                    <form onSubmit={handleSendReset} className="space-y-4">
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

                        <button type="submit" disabled={loading || !email} className="btn btn-primary w-full">
                            {loading ? 'Sending...' : 'Send Reset Code'}
                        </button>
                    </form>
                </div>

                <p className="mt-6 text-center text-sm text-neutral-500">
                    <Link to="/login" className="font-bold text-teal-700 hover:text-teal-800">
                        ← Back to Sign in
                    </Link>
                </p>
            </div>
        </div>
    );
}