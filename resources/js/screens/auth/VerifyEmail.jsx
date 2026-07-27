import React, { useState, useEffect } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import api from '../../lib/api';
import useAuthStore from '../../stores/authStore';

export default function VerifyEmail() {
    const location = useLocation();
    const navigate = useNavigate();
    const { user } = useAuthStore();
    const [code, setCode] = useState('');
    const [error, setError] = useState(null);
    const [loading, setLoading] = useState(false);
    const [resending, setResending] = useState(false);
    const [success, setSuccess] = useState(null);

    const userId = location.state?.user_id;

    useEffect(() => {
        if (!userId) {
            navigate('/register', { replace: true });
        }
        if (user) {
            navigate('/dashboard', { replace: true });
        }
    }, [userId, user, navigate]);

    const handleVerify = async (e) => {
        e.preventDefault();
        if (code.length !== 4) {
            setError('Please enter the 4-digit code');
            return;
        }
        setError(null);
        setLoading(true);
        try {
            await useAuthStore.getState().verifyEmail(userId, code);
            navigate('/onboarding');
        } catch (err) {
            setError(err?.message || 'Verification failed');
        } finally {
            setLoading(false);
        }
    };

    const handleResend = async () => {
        setResending(true);
        setError(null);
        try {
            await api.post('/auth/resend-verification', { user_id: userId });
            setSuccess('New code sent to your email');
            setTimeout(() => setSuccess(null), 5000);
        } catch (err) {
            setError(err?.data?.message || 'Failed to resend code');
        } finally {
            setResending(false);
        }
    };

    const handleCodeChange = (value) => {
        // Only allow digits and max 4
        const digits = value.replace(/\\D/g, '').slice(0, 4);
        setCode(digits);
    };

    if (!userId) return null;

    return (
        <div className="flex min-h-screen items-center justify-center bg-neutral-50 px-4">
            <div className="w-full max-w-sm animate-fade-in-up">
                <Link to="/" className="block text-center">
                    <span className="text-2xl font-extrabold tracking-tight text-teal-700">Health</span>
                    <span className="text-2xl font-extrabold tracking-tight text-neutral-900">Intel</span>
                </Link>

                <div className="mt-10 card p-6">
                    <h2 className="text-xl font-bold text-neutral-900 text-center">Verify your email</h2>
                    <p className="text-sm text-neutral-500 text-center mt-1">
                        We sent a 4-digit code to your email. Enter it below to continue.
                    </p>

                    {error && (
                        <div className="mt-4 rounded-xl bg-danger-50 border border-danger-200 px-4 py-3 text-sm text-danger-700 font-medium">
                            {error}
                        </div>
                    )}
                    {success && (
                        <div className="mt-4 rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 font-medium">
                            {success}
                        </div>
                    )}

                    <form onSubmit={handleVerify} className="mt-6 space-y-4">
                        <div>
                            <label htmlFor="code" className="text-sm font-semibold text-neutral-700">Verification Code</label>
                            <input
                                id="code"
                                type="text"
                                inputMode="numeric"
                                autoComplete="one-time-code"
                                maxLength={4}
                                value={code}
                                onChange={(e) => handleCodeChange(e.target.value)}
                                className="input-base mt-1.5 text-center text-3xl tracking-[0.5em] font-bold"
                                placeholder="0000"
                                autoFocus
                            />
                        </div>

                        <button
                            type="submit"
                            disabled={loading || code.length !== 4}
                            className="btn btn-primary w-full"
                        >
                            {loading ? 'Verifying...' : 'Verify Email'}
                        </button>
                    </form>

                    <div className="mt-4 text-center">
                        <button
                            onClick={handleResend}
                            disabled={resending}
                            className="text-xs font-semibold text-teal-600 hover:text-teal-700 disabled:opacity-50"
                        >
                            {resending ? 'Resending...' : 'Resend code'}
                        </button>
                    </div>
                </div>

                <p className="mt-6 text-center text-sm text-neutral-500">
                    Already verified?{' '}
                    <Link to="/login" className="font-bold text-teal-700 hover:text-teal-800">
                        Sign in
                    </Link>
                </p>
            </div>
        </div>
    );
}