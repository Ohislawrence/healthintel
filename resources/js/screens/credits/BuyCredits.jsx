import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import useAuthStore from '../../stores/authStore';

export default function BuyCredits() {
    const { user, fetchUser } = useAuthStore();
    const [loading, setLoading] = useState(null); // package id being purchased
    const [error, setError] = useState(null);

    const { data, isLoading } = useQuery({
        queryKey: ['credit-packages'],
        queryFn: () => api.get('/payment/packages'),
    });

    const packages = data?.data?.packages || [];

    const handleBuy = async (pkg) => {
        setLoading(pkg.id);
        setError(null);

        try {
            const res = await api.post('/payment/initialize', { package_id: pkg.id });
            const authUrl = res.data.authorization_url;

            if (authUrl) {
                window.location.href = authUrl;
            } else {
                setError('Could not start payment. Please try again.');
            }
        } catch (err) {
            setError(err?.message || 'Payment initialization failed.');
        } finally {
            setLoading(null);
        }
    };

    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-xl font-semibold text-gray-900">Buy Credits</h2>
                <p className="mt-1 text-sm text-gray-500">
                    Credits are used for lab result interpretations and other features. Your balance:{' '}
                    <span className="font-semibold text-teal-600">{user?.credits ?? 0}</span>
                </p>
            </div>

            {error && (
                <div className="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>
            )}

            {isLoading ? (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {[1, 2, 3, 4].map((i) => (
                        <div key={i} className="h-40 animate-pulse rounded-xl bg-gray-100" />
                    ))}
                </div>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {packages.map((pkg) => (
                        <div
                            key={pkg.id}
                            className="rounded-xl border border-gray-200 bg-white p-6 text-center"
                        >
                            <p className="text-3xl font-bold text-teal-600">{pkg.credits}</p>
                            <p className="mt-1 text-sm text-gray-500">Credits</p>
                            <p className="mt-3 text-2xl font-bold text-gray-900">
                                {pkg.price_naira
                                    ? `₦${pkg.price_naira.toLocaleString()}`
                                    : pkg.price_formatted || `₦${(pkg.price_kobo / 100).toLocaleString()}`}
                            </p>
                            <button
                                onClick={() => handleBuy(pkg)}
                                disabled={loading === pkg.id}
                                className="mt-4 w-full rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50 transition-colors"
                            >
                                {loading === pkg.id ? 'Starting...' : 'Buy now'}
                            </button>
                        </div>
                    ))}
                </div>
            )}

            <p className="text-center text-xs text-gray-400">
                Payments are processed securely via Paystack. Your card details are never stored on our servers.
            </p>
        </div>
    );
}
