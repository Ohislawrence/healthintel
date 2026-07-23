import React, { useState } from 'react';
import { Link, useSearchParams, useNavigate } from 'react-router-dom';
import api from '../../lib/api';

export default function ResetPassword() {
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();
    const email = searchParams.get('email') || '';
    const token = searchParams.get('token') || '';

    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(false);

    const handleReset = async (e) => {
        e.preventDefault();
        setError(null);
        if (password.length < 8) {
            setError('Password must be at least 8 characters.');
            return;
        }
        if (password !== passwordConfirmation) {
            setError('Passwords do not match.');
            return;
        }
        setLoading(true);
        try {
            await api.post('/auth/reset-password', {
                email,
                token,
                password,
                password_confirmation: passwordConfirmation,
            });
            setSuccess(true);
        } catch (err) {
            setError(err?.message || 'Password reset failed. The token may have expired.');
        } finally {
            setLoading(false);
        }
    };

    if (success) {
        return (
            <div className="flex min-h-screen items-center justify-center bg-neutral-50 px-4">
                <div className="w-full max-w-sm animate-fade-in-up">
                    <Link to="/login" className="block text-center">
                        <span className="text-2xl font-extrabold tracking-tight text-teal-700">Health</span>
                        <span className="text-2xl font-extrabold tracking-tight text-neutral-900">Intel</span>
                    </Link>
                    <div className="mt-10 card p-6 text-center">
                        <div className="w-16 h-16 rounded-full bg-success-50 flex items-center justify-center text-2xl text-success-600 font-bold mx-auto mb-4">✓</div>
                        <h2 className="text-xl font-bold text-neutral-900">Password Reset!</h2>
                        <p className="text-sm text-neutral-500 mt-2 mb-6">Your password has been changed successfully.</p>
                        <Link to="/login" className="btn btn-primary w-full">Go to Sign In</Link>
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
                    <h2 className="text-xl font-bold text-neutral-900 text-center">Reset Password</h2>
                    <p className="text-sm text-neutral-500 text-center mt-1 mb-6">Enter your new password for {email}</p>

                    <form onSubmit={handleReset} className="space-y-4">
                        {error && (
                            <div className="rounded-xl bg-danger-50 border border-danger-200 px-4 py-3 text-sm text-danger-700 font-medium">{error}</div>
                        )}

                        <div>
                            <label className="text-sm font-semibold text-neutral-700">New Password</label>
                            <div className="relative mt-1.5">
                                <input
                                    type={showPassword ? 'text' : 'password'}
                                    required
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    className="input-base pr-12"
                                    placeholder="Min. 8 characters"
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword(!showPassword)}
                                    className="absolute right-3 top-1/2 -translate-y-1/2 text-lg text-neutral-400 hover:text-neutral-600"
                                >
                                    {showPassword ? '🙈' : '👁'}
                                </button>
                            </div>
                        </div>

                        <div>
                            <label className="text-sm font-semibold text-neutral-700">Confirm Password</label>
                            <div className="relative mt-1.5">
                                <input
                                    type={showConfirm ? 'text' : 'password'}
                                    required
                                    value={passwordConfirmation}
                                    onChange={(e) => setPasswordConfirmation(e.target.value)}
                                    className="input-base pr-12"
                                    placeholder="Repeat your password"
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowConfirm(!showConfirm)}
                                    className="absolute right-3 top-1/2 -translate-y-1/2 text-lg text-neutral-400 hover:text-neutral-600"
                                >
                                    {showConfirm ? '🙈' : '👁'}
                                </button>
                            </div>
                        </div>

                        <button type="submit" disabled={loading || !password || !passwordConfirmation} className="btn btn-primary w-full">
                            {loading ? 'Resetting...' : 'Reset Password'}
                        </button>
                    </form>
                </div>

                <p className="mt-6 text-center text-sm text-neutral-500">
                    <Link to="/login" className="font-bold text-teal-700 hover:text-teal-800">← Back to Sign in</Link>
                </p>
            </div>
        </div>
    );
}