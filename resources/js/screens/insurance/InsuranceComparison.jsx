import React, { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../lib/api';
import Disclaimer from '../../components/ui/Disclaimer';

export default function InsuranceComparison() {
    const [selectedHmo, setSelectedHmo] = useState(null);
    const [message, setMessage] = useState('');
    const [submitted, setSubmitted] = useState(false);

    const { data, isLoading } = useQuery({
        queryKey: ['hmo-list'],
        queryFn: () => api.get('/insurance/hmos'),
    });

    const enquireMutation = useMutation({
        mutationFn: (payload) => api.post('/insurance/enquire', payload),
        onSuccess: () => setSubmitted(true),
    });

    const hmoList = data?.data?.hmo_list || [];

    const handleEnquire = () => {
        if (!selectedHmo) return;
        enquireMutation.mutate({ provider_slug: selectedHmo.slug, message });
    };

    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-xl font-semibold text-gray-900">Compare Health Insurance Plans</h2>
                <p className="mt-1 text-sm text-gray-500">
                    Browse available HMO plans in Nigeria. Select a provider to learn more or submit an enquiry.
                </p>
            </div>

            {isLoading ? (
                <div className="space-y-3">
                    {[1, 2, 3, 4, 5].map((i) => (
                        <div key={i} className="h-20 animate-pulse rounded-xl bg-gray-100" />
                    ))}
                </div>
            ) : (
                <div className="space-y-3">
                    {hmoList.map((hmo) => (
                        <button
                            key={hmo.id}
                            onClick={() => { setSelectedHmo(hmo); setSubmitted(false); }}
                            className={`w-full text-left rounded-xl border p-5 transition-all ${
                                selectedHmo?.id === hmo.id
                                    ? 'border-teal-500 bg-teal-50 shadow-sm'
                                    : 'border-gray-200 bg-white hover:border-teal-200'
                            }`}
                        >
                            <div className="flex justify-between items-start">
                                <div>
                                    <h3 className="font-semibold text-gray-900">{hmo.name}</h3>
                                    <p className="mt-1 text-sm text-gray-500">{hmo.bio}</p>
                                </div>
                                {hmo.is_verified && (
                                    <span className="rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">✓ Verified</span>
                                )}
                            </div>
                            {hmo.insurance_plans?.length > 0 && (
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {hmo.insurance_plans.map((plan) => (
                                        <span key={plan} className="rounded-full bg-teal-100 px-3 py-1 text-xs font-medium text-teal-700">
                                            {plan}
                                        </span>
                                    ))}
                                </div>
                            )}
                        </button>
                    ))}
                </div>
            )}

            {/* Enquiry form */}
            {selectedHmo && !submitted && (
                <div className="rounded-xl border border-gray-200 bg-white p-6">
                    <h3 className="font-semibold text-gray-900 mb-4">
                        Request information about {selectedHmo.name}
                    </h3>
                    <textarea
                        rows={3}
                        placeholder="I'd like to learn more about your plans..."
                        value={message}
                        onChange={(e) => setMessage(e.target.value)}
                        className="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-200 outline-none resize-none"
                    />
                    <button
                        onClick={handleEnquire}
                        disabled={enquireMutation.isPending}
                        className="mt-3 rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50 transition-colors"
                    >
                        {enquireMutation.isPending ? 'Submitting...' : 'Submit enquiry'}
                    </button>
                    {enquireMutation.isError && (
                        <p className="mt-2 text-sm text-red-600">Failed to submit. Please try again.</p>
                    )}
                </div>
            )}

            {submitted && (
                <div className="rounded-xl border border-green-200 bg-green-50 p-6 text-center">
                    <p className="text-sm font-semibold text-green-700">✓ Enquiry submitted!</p>
                    <p className="mt-1 text-xs text-green-600">{selectedHmo?.name} will contact you shortly.</p>
                    <button
                        onClick={() => { setSelectedHmo(null); setSubmitted(false); setMessage(''); }}
                        className="mt-3 text-sm text-teal-600 hover:underline"
                    >
                        Browse other plans
                    </button>
                </div>
            )}

            <Disclaimer />
        </div>
    );
}
