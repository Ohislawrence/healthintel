import React from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import Disclaimer from '../../components/ui/Disclaimer';

export default function SymptomResult() {
    const location = useLocation();
    const navigate = useNavigate();
    const result = location.state?.result;

    if (!result) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-gray-50">
                <div className="text-center">
                    <h2 className="text-xl font-semibold text-gray-900">No results found</h2>
                    <p className="mt-2 text-sm text-gray-500">Please go back and run a symptom check first.</p>
                    <Link to="/symptom-checker" className="mt-4 inline-block text-teal-600 font-medium hover:underline">
                        ← Back to Symptom Checker
                    </Link>
                </div>
            </div>
        );
    }

    const panels = result.suggested_panels || [];
    const interpretation = result.interpretation;
    const providers = result.nearby_providers || [];
    const aiStatus = result.ai_status;

    return (
        <div className="min-h-screen bg-gray-50">
            <div className="mx-auto max-w-4xl px-4 py-8">
                {/* Header */}
                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">📊 Symptom Analysis Results</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Based on your selected symptoms
                        </p>
                    </div>
                    <Link
                        to="/symptom-checker"
                        className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                    >
                        ← New check
                    </Link>
                </div>

                <div className="space-y-6">
                    {/* Recommended Tests */}
                    {panels.length > 0 && (
                        <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                            <h2 className="flex items-center gap-2 text-lg font-semibold text-gray-900 mb-4">
                                <span>🧪</span> Recommended Tests
                            </h2>
                            <p className="text-sm text-gray-500 mb-4">
                                Click a test to enter your values and get AI interpretation.
                            </p>
                            <div className="grid gap-3 sm:grid-cols-2">
                                {panels.map((p) => (
                                    <Link
                                        key={p.id}
                                        to={`/lab-results/${p.slug}`}
                                        className="flex items-center justify-between rounded-xl border border-teal-200 bg-teal-50 p-4 hover:bg-teal-100 transition-colors"
                                    >
                                        <div>
                                            <p className="font-semibold text-teal-700">{p.name}</p>
                                            <p className="text-xs text-teal-500 mt-0.5">
                                                {p.tests?.length || p.ranges_count || 'Multiple'} individual tests
                                            </p>
                                        </div>
                                        <span className="text-teal-500 text-lg">→</span>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* AI Interpretation */}
                    <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 className="flex items-center gap-2 text-lg font-semibold text-gray-900 mb-4">
                            <span>🤖</span> AI Guidance
                        </h2>
                        {interpretation ? (
                            <div className="prose prose-sm max-w-none text-gray-700 whitespace-pre-wrap leading-relaxed">
                                {interpretation}
                            </div>
                        ) : (
                            <div className="rounded-lg bg-amber-50 border border-amber-200 p-4">
                                <p className="text-sm text-amber-800">
                                    AI interpretation is currently unavailable. Your credits have not been deducted.
                                </p>
                                <p className="text-xs text-amber-600 mt-1">
                                    The recommended test panels above are based on your symptom selection. You can still use them.
                                </p>
                            </div>
                        )}
                    </div>

                    {/* Nearby Providers */}
                    {providers.length > 0 && (
                        <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                            <h2 className="flex items-center gap-2 text-lg font-semibold text-gray-900 mb-4">
                                <span>🏥</span> Nearby Labs & Hospitals
                            </h2>
                            <p className="text-sm text-gray-500 mb-4">
                                Facilities near you that offer these tests:
                            </p>
                            <div className="divide-y divide-gray-100">
                                {providers.map((provider) => (
                                    <Link
                                        key={provider.id}
                                        to={`/providers/${provider.slug}`}
                                        className="flex items-center justify-between py-3 hover:bg-gray-50 -mx-2 px-2 rounded-lg transition-colors"
                                    >
                                        <div className="flex items-center gap-3">
                                            <span className="text-2xl">
                                                {provider.type === 'lab' ? '🧪' : provider.type === 'hospital' ? '🏥' : '🏨'}
                                            </span>
                                            <div>
                                                <p className="font-medium text-gray-900">{provider.name}</p>
                                                <p className="text-xs text-gray-500">
                                                    {provider.type?.charAt(0).toUpperCase() + provider.type?.slice(1)}
                                                    {provider.distance_km != null && ` · ${provider.distance_km} km away`}
                                                    {provider.city && ` · ${provider.city}`}
                                                </p>
                                            </div>
                                        </div>
                                        <span className="text-gray-400">→</span>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    )}

                    {!interpretation && panels.length === 0 && (
                        <div className="rounded-2xl border border-gray-200 bg-white p-8 text-center">
                            <p className="text-lg font-medium text-gray-700">No recommendations available</p>
                            <p className="mt-2 text-sm text-gray-500">
                                Please try selecting different symptoms or adding more details in the description.
                            </p>
                            <Link
                                to="/symptom-checker"
                                className="mt-4 inline-block text-teal-600 font-medium hover:underline"
                            >
                                Try again
                            </Link>
                        </div>
                    )}
                </div>

                <div className="mt-8">
                    <Disclaimer />
                </div>
            </div>
        </div>
    );
}