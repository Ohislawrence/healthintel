import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../lib/api';

export default function ProviderDetail() {
    const { slug } = useParams();
    const navigate = useNavigate();

    const { data, isLoading } = useQuery({
        queryKey: ['provider', slug],
        queryFn: () => api.get(`/providers/${slug}`),
    });
    const provider = data?.data?.provider || {};

    const clickOutMutation = useMutation({
        mutationFn: (action) => api.post(`/providers/${slug}/click-out`, { action }),
    });

    const trackClick = (action) => {
        clickOutMutation.mutate(action, {
            onError: () => {},
        });
    };

    if (isLoading) {
        return (
            <div className="space-y-4 max-w-lg mx-auto">
                <div className="skeleton h-8 w-48 rounded" />
                <div className="skeleton h-64 w-full rounded-xl" />
            </div>
        );
    }

    const locationLabel = [provider.city, provider.state, provider.country]
        .filter(Boolean)
        .join(', ');

    return (
        <div className="space-y-5 max-w-lg mx-auto">
            <button onClick={() => navigate('/directory')} className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back to Directory</button>

            {/* Banner */}
            {provider.banner_url && (
                <div className="rounded-2xl overflow-hidden border border-neutral-100 bg-white">
                    <img
                        src={provider.banner_url}
                        alt={`${provider.name} banner`}
                        className="w-full h-32 sm:h-40 object-cover"
                        onError={(e) => { e.currentTarget.style.display = 'none'; }}
                    />
                </div>
            )}

            <div>
                <div className="flex items-center gap-3 mb-3">
                    {provider.logo_url ? (
                        <img src={provider.logo_url} alt={provider.name} className="w-14 h-14 rounded-2xl object-contain bg-white border border-neutral-100" onError={(e) => { e.currentTarget.style.display = 'none'; }} />
                    ) : (
                        <span className="w-14 h-14 rounded-2xl bg-teal-50 flex items-center justify-center text-2xl text-teal-600">⚕</span>
                    )}
                    <div>
                        <p className="text-xl font-extrabold text-neutral-900 tracking-tight">{provider.name}</p>
                        <p className="text-sm text-neutral-500 capitalize">{provider.type || 'Healthcare Provider'}</p>
                    </div>
                </div>
                {(provider.is_sponsored || provider.is_verified) && (
                    <div className="flex items-center gap-2 mb-1">
                        {provider.is_sponsored && (
                            <span className="badge badge-warning text-[10px]">Sponsored Listing</span>
                        )}
                        {provider.is_verified && (
                            <span className="badge badge-success text-[10px]">✓ Verified</span>
                        )}
                    </div>
                )}
            </div>

            {/* Details */}
            <div className="card p-4 space-y-3">
                {provider.specialty && (
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-neutral-500">Specialty</span>
                        <span className="text-sm font-semibold text-neutral-900">{provider.specialty}</span>
                    </div>
                )}
                {provider.address && (
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-neutral-500">Address</span>
                        <span className="text-sm font-semibold text-neutral-900">{provider.address}</span>
                    </div>
                )}
                {locationLabel && (
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-neutral-500">Location</span>
                        <span className="text-sm font-semibold text-neutral-900">{locationLabel}</span>
                    </div>
                )}
                {provider.phone && (
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-neutral-500">Phone</span>
                        <a href={`tel:${provider.phone}`} onClick={() => trackClick('call')} className="text-sm font-semibold text-teal-700 hover:text-teal-800">{provider.phone}</a>
                    </div>
                )}
                {provider.email && (
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-neutral-500">Email</span>
                        <a href={`mailto:${provider.email}`} className="text-sm font-semibold text-teal-700 hover:text-teal-800">{provider.email}</a>
                    </div>
                )}
                {provider.website && (
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-neutral-500">Website</span>
                        <a href={provider.website} target="_blank" rel="noopener noreferrer" onClick={() => trackClick('website')} className="text-sm font-semibold text-teal-700 hover:text-teal-800">
                            Visit Site ↗
                        </a>
                    </div>
                )}
            </div>

            {/* Quick actions */}
            {(provider.phone || provider.website) && (
                <div className="grid grid-cols-2 gap-3">
                    {provider.phone && (
                        <a
                            href={`tel:${provider.phone}`}
                            onClick={() => trackClick('call')}
                            className="rounded-xl bg-teal-600 text-white text-sm font-semibold py-3 text-center hover:bg-teal-700 transition-colors"
                        >
                            📞 Call Now
                        </a>
                    )}
                    {provider.website && (
                        <a
                            href={provider.website}
                            target="_blank"
                            rel="noopener noreferrer"
                            onClick={() => trackClick('website')}
                            className="rounded-xl bg-white border border-teal-600 text-teal-700 text-sm font-semibold py-3 text-center hover:bg-teal-50 transition-colors"
                        >
                            🌐 Visit Website
                        </a>
                    )}
                </div>
            )}

            {/* Locations */}
            {provider.locations?.length > 0 && (
                <div className="card p-4 space-y-3">
                    <div className="flex items-center justify-between">
                        <p className="text-sm font-bold text-neutral-900">Locations</p>
                        <span className="text-xs text-neutral-400">{provider.locations.length} branch{provider.locations.length > 1 ? 'es' : ''}</span>
                    </div>
                    {provider.locations.map((loc, i) => (
                        <div key={loc.id ?? i} className={`rounded-xl p-3 ${loc.is_primary ? 'bg-teal-50 border border-teal-200' : 'bg-neutral-50 border border-neutral-100'}`}>
                            <div className="flex items-center gap-2 mb-1">
                                <p className="text-sm font-bold text-neutral-900">{loc.name || `Branch ${i + 1}`}</p>
                                {loc.is_primary && <span className="badge badge-success text-[9px]">Primary</span>}
                            </div>
                            <p className="text-xs text-neutral-500">
                                {[loc.address, loc.city, loc.state, loc.country].filter(Boolean).join(', ')}
                            </p>
                            {loc.phone && (
                                <a
                                    href={`tel:${loc.phone}`}
                                    onClick={() => trackClick('call')}
                                    className="text-xs font-semibold text-teal-700 hover:text-teal-800 mt-1 inline-block"
                                >
                                    {loc.phone}
                                </a>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {provider.bio && (
                <div className="card p-4">
                    <p className="text-sm font-bold text-neutral-900 mb-2">About</p>
                    <p className="text-sm text-neutral-700 leading-relaxed">{provider.bio}</p>
                </div>
            )}

            {provider.insurance_plans?.length > 0 && (
                <div className="card p-4">
                    <p className="text-sm font-bold text-neutral-900 mb-2">Insurances Accepted</p>
                    <div className="flex flex-wrap gap-2">
                        {provider.insurance_plans.map((ins, i) => (
                            <span key={i} className="badge badge-success">{typeof ins === 'string' ? ins : (ins.name || ins.plan || JSON.stringify(ins))}</span>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}