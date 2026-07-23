import React from 'react';
import { useParams, Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import Disclaimer from '../../components/ui/Disclaimer';

function logClickOut(slug, action) {
    navigator.sendBeacon('/api/providers/' + slug + '/click-out', JSON.stringify({ action }));
}

export default function ProviderDetail() {
    const { slug } = useParams();

    const { data, isLoading } = useQuery({
        queryKey: ['provider', slug],
        queryFn: () => api.get(`/providers/${slug}`),
        enabled: !!slug,
    });

    const p = data?.data?.provider;

    if (isLoading) {
        return (
            <div className="space-y-4 animate-pulse">
                <div className="h-8 w-48 rounded bg-gray-100" />
                <div className="h-6 w-72 rounded bg-gray-100" />
                <div className="h-32 rounded-xl bg-gray-100" />
            </div>
        );
    }

    if (!p) {
        return (
            <div className="py-20 text-center">
                <p className="text-gray-500">Provider not found.</p>
                <Link to="/directory" className="text-teal-600 text-sm mt-2 inline-block">
                    Back to directory
                </Link>
            </div>
        );
    }

    const directionsUrl = p.latitude && p.longitude
        ? `https://www.google.com/maps/dir/?api=1&destination=${p.latitude},${p.longitude}`
        : null;

    return (
        <div className="space-y-6">
            <Link to="/directory" className="text-sm text-teal-600 hover:text-teal-700">
                ← Back to directory
            </Link>

            <div className="rounded-xl border border-gray-200 bg-white p-6">
                <div className="flex items-start gap-3">
                    <div className="flex-1">
                        <div className="flex items-center gap-2 flex-wrap">
                            <h2 className="text-xl font-semibold text-gray-900">{p.name}</h2>
                            <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 uppercase">{p.type}</span>
                            {p.is_verified && (
                                <span className="rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">✓ Verified</span>
                            )}
                            {p.partner_status === 'affiliate' && (
                                <span className="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Partner</span>
                            )}
                            {p.partner_status === 'sponsored' && (
                                <span className="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">Sponsored</span>
                            )}
                        </div>
                        <p className="mt-1 text-sm text-teal-600 font-medium">{p.specialty}</p>
                    </div>
                </div>

                {p.bio && (
                    <p className="mt-4 text-sm text-gray-600 leading-relaxed">{p.bio}</p>
                )}

                {/* CTA Buttons */}
                <div className="mt-6 flex flex-wrap gap-3">
                    {p.phone && (
                        <a
                            href={`tel:${p.phone}`}
                            onClick={() => logClickOut(slug, 'call')}
                            className="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 transition-colors"
                        >
                            📞 Call
                        </a>
                    )}
                    {p.referral_link && (
                        <a
                            href={p.referral_link}
                            target="_blank"
                            rel="noopener noreferrer"
                            onClick={() => logClickOut(slug, 'website')}
                            className="inline-flex items-center gap-2 rounded-lg border border-teal-300 px-4 py-2.5 text-sm font-semibold text-teal-700 hover:bg-teal-50 transition-colors"
                        >
                            🌐 Visit website
                        </a>
                    )}
                    {directionsUrl && (
                        <a
                            href={directionsUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            onClick={() => logClickOut(slug, 'directions')}
                            className="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            🗺 Get directions
                        </a>
                    )}
                </div>

                {/* Contact details */}
                <div className="mt-6 grid gap-3 sm:grid-cols-2">
                    {p.phone && (
                        <div className="rounded-lg bg-gray-50 p-3">
                            <p className="text-xs text-gray-500 uppercase">Phone</p>
                            <a href={`tel:${p.phone}`} className="text-sm font-medium text-teal-600">{p.phone}</a>
                        </div>
                    )}
                    {p.email && (
                        <div className="rounded-lg bg-gray-50 p-3">
                            <p className="text-xs text-gray-500 uppercase">Email</p>
                            <a href={`mailto:${p.email}`} className="text-sm font-medium text-teal-600">{p.email}</a>
                        </div>
                    )}
                    {p.website && (
                        <div className="rounded-lg bg-gray-50 p-3">
                            <p className="text-xs text-gray-500 uppercase">Website</p>
                            <a
                                href={p.website}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-sm font-medium text-teal-600"
                            >
                                Visit website
                            </a>
                        </div>
                    )}
                    {p.address && (
                        <div className="rounded-lg bg-gray-50 p-3">
                            <p className="text-xs text-gray-500 uppercase">Address</p>
                            <p className="text-sm text-gray-700">
                                {[p.address, p.city, p.state, p.country].filter(Boolean).join(', ')}
                            </p>
                        </div>
                    )}
                </div>

                {/* Insurance plans */}
                {p.insurance_plans?.length > 0 && (
                    <div className="mt-6">
                        <p className="text-xs text-gray-500 uppercase mb-2">Insurance Plans</p>
                        <div className="flex flex-wrap gap-2">
                            {p.insurance_plans.map((plan) => (
                                <span key={plan} className="rounded-full bg-teal-50 px-3 py-1 text-xs font-medium text-teal-700">
                                    {plan}
                                </span>
                            ))}
                        </div>
                    </div>
                )}
            </div>

            <Disclaimer />
        </div>
    );
}
