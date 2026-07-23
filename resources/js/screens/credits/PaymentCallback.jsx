import React, { useEffect, useState } from 'react';
import { useSearchParams, Link } from 'react-router-dom';
import api from '../../lib/api';
import useAuthStore from '../../stores/authStore';

export default function PaymentCallback() {
    const [searchParams] = useSearchParams();
    const { fetchUser } = useAuthStore();
    const [status, setStatus] = useState('verifying');

    useEffect(() => {
        const reference = searchParams.get('reference');
        if (!reference) {
            setStatus('error');
            return;
        }

        const verify = async () => {
            try {
                await api.get(`/payment/verify?reference=${reference}`);
                await fetchUser();
                setStatus('success');
            } catch {
                setStatus('error');
            }
        };

        verify();
    }, [searchParams, fetchUser]);

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4">
            <div className="w-full max-w-sm text-center">
                {status === 'verifying' && (
                    <>
                        <div className="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-teal-500 border-t-transparent" />
                        <h2 className="mt-6 text-lg font-semibold text-gray-900">Verifying payment...</h2>
                        <p className="mt-2 text-sm text-gray-500">Please wait while we confirm your transaction.</p>
                    </>
                )}

                {status === 'success' && (
                    <>
                        <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                            <span className="text-2xl">✓</span>
                        </div>
                        <h2 className="mt-6 text-lg font-semibold text-gray-900">Payment successful!</h2>
                        <p className="mt-2 text-sm text-gray-500">Your credits have been added to your account.</p>
                        <Link
                            to="/dashboard"
                            className="mt-6 inline-block rounded-lg bg-teal-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-teal-700"
                        >
                            Go to dashboard
                        </Link>
                    </>
                )}

                {status === 'error' && (
                    <>
                        <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                            <span className="text-2xl">✕</span>
                        </div>
                        <h2 className="mt-6 text-lg font-semibold text-gray-900">Payment verification failed</h2>
                        <p className="mt-2 text-sm text-gray-500">
                            If your payment was debited, your credits will be added automatically. Please contact support if the issue persists.
                        </p>
                        <div className="mt-6 flex gap-3 justify-center">
                            <Link
                                to="/dashboard"
                                className="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                Dashboard
                            </Link>
                            <Link
                                to="/credits"
                                className="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700"
                            >
                                Try again
                            </Link>
                        </div>
                    </>
                )}
            </div>
        </div>
    );
}
