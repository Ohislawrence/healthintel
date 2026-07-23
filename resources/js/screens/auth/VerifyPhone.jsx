import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../../lib/api';

export default function VerifyPhone() {
    const [code, setCode] = useState('');
    const [error, setError] = useState(null);
    const [loading, setLoading] = useState(false);
    const navigate = useNavigate();

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError(null);
        setLoading(true);

        try {
            await api.post('/verify-phone', { code });
            navigate('/dashboard');
        } catch (err) {
            setError(err?.message || 'Invalid verification code.');
        } finally {
            setLoading(false);
        }
    };

    const handleResend = async () => {
        try {
            await api.post('/resend-otp');
        } catch {
            // silently fail
        }
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4">
            <div className="w-full max-w-sm">
                <Link to="/" className="block text-center text-2xl font-bold text-teal-600">
                    Lab<span className="text-gray-800">Doc</span>
                </Link>

                <h2 className="mt-8 text-center text-xl font-semibold text-gray-900">
                    Verify your phone number
                </h2>
                <p className="mt-2 text-center text-sm text-gray-500">
                    We sent a verification code to your phone.
                </p>

                <form onSubmit={handleSubmit} className="mt-6 space-y-4">
                    {error && (
                        <div className="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
                            {error}
                        </div>
                    )}

                    <div>
                        <label htmlFor="code" className="block text-sm font-medium text-gray-700">
                            Verification code
                        </label>
                        <input
                            id="code"
                            type="text"
                            required
                            maxLength={6}
                            value={code}
                            onChange={(e) => setCode(e.target.value.replace(/\D/g, ''))}
                            className="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-center text-2xl tracking-widest focus:border-teal-500 focus:ring-2 focus:ring-teal-200 outline-none"
                            placeholder="000000"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={loading || code.length < 6}
                        className="w-full rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50 transition-colors"
                    >
                        {loading ? 'Verifying...' : 'Verify'}
                    </button>
                </form>

                <p className="mt-4 text-center text-sm text-gray-600">
                    Didn't receive a code?{' '}
                    <button
                        onClick={handleResend}
                        className="font-semibold text-teal-600 hover:text-teal-700"
                    >
                        Resend
                    </button>
                </p>
            </div>
        </div>
    );
}
