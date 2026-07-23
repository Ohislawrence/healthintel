import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../lib/api';
import useAuthStore from '../../stores/authStore';

export default function BuyCredits() {
    const navigate = useNavigate();
    const { user } = useAuthStore();
    const [selectedPackage, setSelectedPackage] = useState(null);
    const [error, setError] = useState(null);

    const { data, isLoading } = useQuery({
        queryKey: ['packages'],
        queryFn: () => api.get('/payment/packages'),
    });
    const packages = data?.data?.packages || [];

    const initMutation = useMutation({
        mutationFn: (pkgId) => api.post('/payment/initialize', { package_id: pkgId }),
        onSuccess: async (res) => {
            const authUrl = res?.data?.authorization_url || res?.authorization_url;
            if (authUrl) {
                // Redirect user to Paystack's payment page
                window.location.href = authUrl;
            } else {
                setError('Could not start payment. The payment gateway may not be configured.');
            }
        },
        onError: (err) => setError(err?.message || 'Payment initialization failed.'),
    });

    return (
        <div className="space-y-5 max-w-lg mx-auto">
            <button onClick={() => navigate('/credits')} className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back</button>

            <div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Buy Credits</p>
                <p className="text-sm text-neutral-500 mt-0.5">Top up your account to interpret lab results</p>
            </div>

            {/* Current Balance */}
            <div className="card p-4 text-center bg-teal-50 border-teal-100">
                <span className="text-xs font-bold text-teal-600 uppercase tracking-wider">Current Balance</span>
                <p className="text-3xl font-extrabold text-teal-700 mt-1">{user?.credits ?? 0}</p>
            </div>

            {error && (
                <div className="rounded-xl bg-danger-50 border border-danger-200 px-4 py-3 text-sm text-danger-700 font-medium">{error}</div>
            )}

            {isLoading ? (
                <div className="space-y-3">
                    {[1,2,3].map(i => <div key={i} className="card p-4 skeleton h-20 rounded-xl" />)}
                </div>
            ) : (
                <div className="space-y-3">
                    {packages.map((pkg) => (
                        <button
                            key={pkg.id}
                            onClick={() => setSelectedPackage(pkg.id)}
                            className={`w-full card p-4 text-left hover:shadow-md transition-all ${
                                selectedPackage === pkg.id ? 'border-teal-700 ring-2 ring-teal-100' : ''
                            }`}
                        >
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-bold text-neutral-900">{pkg.name}</p>
                                    <p className="text-xs text-neutral-500 mt-0.5">{pkg.description || `${pkg.credits} credits`}</p>
                                </div>
                                <span className="text-xl font-extrabold text-teal-700">
                                    ₦{parseFloat(pkg.price_ngn || 0).toLocaleString()}
                                </span>
                            </div>
                        </button>
                    ))}
                </div>
            )}

            <button
                onClick={() => selectedPackage && initMutation.mutate(selectedPackage)}
                disabled={!selectedPackage || initMutation.isPending}
                className="btn btn-primary w-full"
            >
                {initMutation.isPending ? 'Redirecting to Paystack...' : 'Pay with Paystack'}
            </button>
        </div>
    );
}