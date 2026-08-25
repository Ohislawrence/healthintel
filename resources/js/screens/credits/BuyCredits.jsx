import React, { useEffect, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../lib/api';
import { trackEngagement } from '../../lib/engagement';
import useAuthStore from '../../stores/authStore';

export default function BuyCredits() {
    const navigate = useNavigate();
    const location = useLocation();
    const { user } = useAuthStore();
    const [selectedPackage, setSelectedPackage] = useState(null);
    const [error, setError] = useState(null);

    const { data, isLoading } = useQuery({
        queryKey: ['packages'],
        queryFn: () => api.get('/payment/packages'),
    });
    const packages = data?.data?.packages || [];

    const sortedPackages = [...packages].sort((a, b) => {
        const aCredits = Number(a?.credits || 0);
        const bCredits = Number(b?.credits || 0);
        const aPrice = Number(a?.price_naira || 0);
        const bPrice = Number(b?.price_naira || 0);

        if (!aCredits || !bCredits) return 0;

        return (aPrice / aCredits) - (bPrice / bCredits);
    });
    const bestValuePackage = sortedPackages[0] || null;

    useEffect(() => {
        if (!selectedPackage && bestValuePackage?.id) {
            setSelectedPackage(bestValuePackage.id);
        }
    }, [bestValuePackage?.id, selectedPackage]);

    const selectedPackageData = packages.find((pkg) => pkg.id === selectedPackage) || null;

    const formatPrice = (pkg) => pkg?.price_formatted || '₦' + parseFloat(pkg?.price_naira || 0).toLocaleString();
    const formatPerCredit = (pkg) => {
        const credits = Number(pkg?.credits || 0);
        const price = Number(pkg?.price_naira || 0);
        if (!credits || !price) return null;
        return `₦${Math.round(price / credits).toLocaleString()} / credit`;
    };

    const initMutation = useMutation({
        mutationFn: (pkgId) => api.post('/payment/initialize', { package_id: pkgId }),
        onSuccess: async (res) => {
            const activePackage = packages.find((pkg) => pkg.id === selectedPackage);
            trackEngagement('payment_initialize_started', 'buy_credits', {
                package_id: selectedPackage,
                credits: activePackage?.credits || null,
                price_naira: activePackage?.price_naira || null,
            });

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

    const purchaseIntent = location.state?.from === 'lab-review' ? location.state : null;

    useEffect(() => {
        trackEngagement('buy_credits_viewed', 'buy_credits', {
            from: location.state?.from || null,
            balance: user?.credits ?? 0,
        });
    }, []);

    const handleSelectPackage = (pkg) => {
        setSelectedPackage(pkg.id);
        trackEngagement('credit_package_selected', 'buy_credits', {
            package_id: pkg.id,
            credits: pkg.credits || null,
            price_naira: pkg.price_naira || null,
        });
    };

    return (
        <div className="space-y-5 max-w-lg mx-auto">
            <button onClick={() => navigate('/credits')} className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back</button>

            <div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Buy Credits</p>
                <p className="text-sm text-neutral-500 mt-0.5">Top up your account to interpret lab results, check symptoms, and keep moving without interruption.</p>
            </div>

            {purchaseIntent && (
                <div className="rounded-2xl border border-teal-200 bg-teal-50 px-4 py-3">
                    <p className="text-xs font-bold uppercase tracking-wider text-teal-700">Continue your report</p>
                    <p className="text-sm font-semibold text-teal-900 mt-1">
                        You came here from a lab review. Finish topping up and return to your health workflow faster.
                    </p>
                </div>
            )}

            {/* Current Balance */}
            <div className="card p-4 bg-teal-50 border-teal-100 space-y-3">
                <div className="text-center">
                    <span className="text-xs font-bold text-teal-600 uppercase tracking-wider">Current Balance</span>
                    <p className="text-3xl font-extrabold text-teal-700 mt-1">{user?.credits ?? 0}</p>
                </div>

                <div className="grid grid-cols-3 gap-2 text-center text-[11px] font-semibold text-teal-700">
                    <div className="rounded-xl bg-white/80 px-2 py-2">Lab reports</div>
                    <div className="rounded-xl bg-white/80 px-2 py-2">Symptom checks</div>
                    <div className="rounded-xl bg-white/80 px-2 py-2">Follow-ups</div>
                </div>

                {selectedPackageData && (
                    <div className="rounded-xl bg-white border border-teal-100 px-3 py-3 text-sm">
                        <p className="font-bold text-neutral-900">Selected bundle: {selectedPackageData.name}</p>
                        <p className="text-neutral-500 mt-0.5">
                            {selectedPackageData.credits || 0} credits for {formatPrice(selectedPackageData)}
                            {formatPerCredit(selectedPackageData) ? ` • ${formatPerCredit(selectedPackageData)}` : ''}
                        </p>
                    </div>
                )}
            </div>

            <div className="card p-4 border-teal-100 bg-white">
                <p className="text-sm font-bold text-neutral-900">What credits unlock</p>
                <div className="mt-3 grid gap-2 text-sm text-neutral-600">
                    <div className="flex items-start gap-2"><span className="mt-0.5 text-teal-600">✓</span><span>Interpret one lab report or follow-up review.</span></div>
                    <div className="flex items-start gap-2"><span className="mt-0.5 text-teal-600">✓</span><span>Run a symptom check when something feels off.</span></div>
                    <div className="flex items-start gap-2"><span className="mt-0.5 text-teal-600">✓</span><span>Stay ready for the next result without waiting.</span></div>
                </div>
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
                            onClick={() => handleSelectPackage(pkg)}
                            className={`w-full card p-4 text-left transition-all ${
                                selectedPackage === pkg.id
                                    ? 'border-teal-500 bg-teal-50 ring-2 ring-teal-200 shadow-md'
                                    : 'border-neutral-200 hover:border-teal-200 hover:shadow-sm'
                            }`}
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <p className="text-sm font-bold text-neutral-900">{pkg.name}</p>
                                        {bestValuePackage?.id === pkg.id && (
                                            <span className="text-[10px] font-bold bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">Best value</span>
                                        )}
                                        {selectedPackage === pkg.id && (
                                            <span className="text-[10px] font-bold bg-teal-500 text-white px-2 py-0.5 rounded-full">Selected</span>
                                        )}
                                    </div>
                                    <p className="text-xs text-neutral-500 mt-0.5">{pkg.description || `${pkg.credits} credits`}</p>
                                </div>
                                <div className="text-right">
                                    <span className={`text-xl font-extrabold ${selectedPackage === pkg.id ? 'text-teal-700' : 'text-neutral-700'}`}>
                                        {formatPrice(pkg)}
                                    </span>
                                    {pkg.credits && (
                                        <>
                                            <p className="text-xs font-bold text-teal-500 mt-0.5">{pkg.credits} credits</p>
                                            {formatPerCredit(pkg) && (
                                                <p className="text-[11px] text-neutral-400 mt-0.5">{formatPerCredit(pkg)}</p>
                                            )}
                                        </>
                                    )}
                                </div>
                            </div>
                        </button>
                    ))}
                </div>
            )}

            <button
                onClick={() => selectedPackage && initMutation.mutate(selectedPackage)}
                disabled={!selectedPackage || initMutation.isPending}
                className="btn btn-primary w-full shadow-lg shadow-teal-200"
            >
                {initMutation.isPending ? 'Redirecting...' : 'Continue to payment'}
            </button>

            <p className="text-[11px] text-neutral-400 text-center leading-relaxed">
                HealthIntel is owned by Apex Cloud Tech. Payments are securely processed by Apex Cloud Tech.
            </p>
        </div>
    );
}
