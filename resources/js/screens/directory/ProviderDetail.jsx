import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';

export default function ProviderDetail() {
    const { slug } = useParams();
    const navigate = useNavigate();

    const { data, isLoading } = useQuery({
        queryKey: ['provider', slug],
        queryFn: () => api.get(`/providers/${slug}`),
    });
    const provider = data?.data?.provider || {};

    if (isLoading) {
        return (
            <div className="space-y-4 max-w-lg mx-auto">
                <div className="skeleton h-8 w-48 rounded" />
                <div className="skeleton h-64 w-full rounded-xl" />
            </div>
        );
    }

    return (
        <div className="space-y-5 max-w-lg mx-auto">
            <button onClick={() => navigate('/directory')} className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back to Directory</button>

            <div>
                <div className="flex items-center gap-3 mb-3">
                    <span className="w-14 h-14 rounded-2xl bg-teal-50 flex items-center justify-center text-2xl text-teal-600">⚕</span>
                    <div>
                        <p className="text-xl font-extrabold text-neutral-900 tracking-tight">{provider.name}</p>
                        <p className="text-sm text-neutral-500">{provider.type || 'Healthcare Provider'}</p>
                    </div>
                </div>
                {provider.is_sponsored && (
                    <span className="badge badge-warning text-[10px]">Sponsored Listing</span>
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
                {provider.city && (
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-neutral-500">Location</span>
                        <span className="text-sm font-semibold text-neutral-900">{[provider.city, provider.state].filter(Boolean).join(', ')}</span>
                    </div>
                )}
                {provider.phone && (
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-neutral-500">Phone</span>
                        <a href={`tel:${provider.phone}`} className="text-sm font-semibold text-teal-700 hover:text-teal-800">{provider.phone}</a>
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
                        <a href={provider.website} target="_blank" rel="noopener noreferrer" className="text-sm font-semibold text-teal-700 hover:text-teal-800">
                            Visit Site ↗
                        </a>
                    </div>
                )}
            </div>

            {provider.description && (
                <div className="card p-4">
                    <p className="text-sm font-bold text-neutral-900 mb-2">About</p>
                    <p className="text-sm text-neutral-700 leading-relaxed">{provider.description}</p>
                </div>
            )}

            {provider.insurance_accepted?.length > 0 && (
                <div className="card p-4">
                    <p className="text-sm font-bold text-neutral-900 mb-2">Insurances Accepted</p>
                    <div className="flex flex-wrap gap-2">
                        {provider.insurance_accepted.map((ins, i) => (
                            <span key={i} className="badge badge-success">{ins}</span>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}