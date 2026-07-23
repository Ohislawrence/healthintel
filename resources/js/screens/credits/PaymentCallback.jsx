import React, { useEffect, useState } from 'react';
import { useSearchParams, Link } from 'react-router-dom';
import api from '../../lib/api';
import useAuthStore from '../../stores/authStore';

export default function PaymentCallback() {
    const [searchParams] = useSearchParams();
    const { fetchUser } = useAuthStore();
    const [status, setStatus] = useState('verifying');
    const [message, setMessage] = useState('');

    useEffect(() => {
        // Paystack returns both 'trxref' and 'reference' in query params
        const reference = searchParams.get('reference') || searchParams.get('trxref');

        if (!reference) {
            setStatus('error');
            setMessage('No payment reference found.');
            return;
        }

        const verify = async () => {
            try {
                await api.get(`/payment/verify?reference=${reference}`);
                await fetchUser();
                setStatus('success');
            } catch (err) {
                setStatus('error');
                setMessage(err?.message || 'Payment verification failed. If you were debited, credits will be added automatically.');
            }
        };

        verify();
    }, [searchParams, fetchUser]);

    return (
        <div className="flex min-h-screen items-center justify-center bg-neutral-50 px-4">
            <div className="w-full max-w-sm text-center animate-fade-in-up">
                <Link to="/" className="block text-center mb-8">
                    <span className="text-2xl font-extrabold tracking-tight text-teal-700">Health</span>
                    <span className="text-2xl font-extrabold tracking-tight text-neutral-900">Intel</span>
                </Link>

                <div className="card p-6">
                    {status === 'verifying' && (
                        <>
                            <div className="w-12 h-12 border-4 border-teal-500 border-t-transparent rounded-full animate-spin mx-auto mb-4" />
                            <h2 className="text-lg font-bold text-neutral-900">Verifying payment...</h2>
                            <p className="text-sm text-neutral-500 mt-1">Please wait while we confirm your transaction.</p>
                        </>
                    )}

                    {status === 'success' && (
                        <>
                            <div className="w-16 h-16 rounded-full bg-success-50 flex items-center justify-center text-2xl text-success-600 font-bold mx-auto mb-4">✓</div>
                            <h2 className="text-lg font-bold text-neutral-900">Payment successful!</h2>
                            <p className="text-sm text-neutral-500 mt-2">Your credits have been added to your account.</p>
                            <Link to="/dashboard" className="btn btn-primary w-full mt-5">
                                Go to Dashboard
                            </Link>
                            <Link to="/credits" className="block mt-3 text-sm font-semibold text-teal-600 hover:text-teal-700">
                                View Credit History →
                            </Link>
                        </>
                    )}

                    {status === 'error' && (
                        <>
                            <div className="w-16 h-16 rounded-full bg-danger-50 flex items-center justify-center text-2xl text-danger-600 font-bold mx-auto mb-4">✕</div>
                            <h2 className="text-lg font-bold text-neutral-900">Verification Failed</h2>
                            <p className="text-sm text-neutral-500 mt-2">{message || 'We could not verify your payment.'}</p>
                            <div className="mt-5 space-y-2">
                                <Link to="/credits/buy" className="btn btn-outline w-full">
                                    Try Again
                                </Link>
                                <Link to="/dashboard" className="block text-sm font-semibold text-teal-600 hover:text-teal-700">
                                    Go to Dashboard
                                </Link>
                            </div>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}