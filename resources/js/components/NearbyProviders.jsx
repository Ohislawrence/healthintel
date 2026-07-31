import React from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../lib/api';

const typeLabels = { lab: 'Lab', hospital: 'Hospital', clinic: 'Clinic' };
const partnerBadgeStyles = {
    sponsored: 'bg-amber-50 text-amber-700 border-amber-200',
    affiliate: 'bg-blue-50 text-blue-700 border-blue-200',
};

/**
 * Fetches nearby providers (labs/hospitals) using the user's geolocation.
 * Sponsored and affiliate providers get priority sorting.
 *
 * Props:
 *  - type (optional): 'lab' | 'hospital' | 'clinic' — filter by type
 *  - title (optional): override section heading
 */
export default function NearbyProviders({ type, title }) {
    const { data, isLoading } = useQuery({
        queryKey: ['nearby-providers', type],
        queryFn: async () => {
            // Try browser geolocation
            const position = await new Promise((resolve, reject) => {
                if (!navigator.geolocation) return reject(new Error('Geolocation not available'));
                navigator.geolocation.getCurrentPosition(resolve, reject, { timeout: 5000, maximumAge: 5 * 60 * 1000 });
            });

            const params = {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                limit: 3,
            };
            if (type) params.type = type;

            return api.get('/providers/nearby-recommended', { params });
        },
        retry: 1,
        staleTime: 5 * 60 * 1000,
    });

    const providers = data?.data?.providers || [];

    if (isLoading || providers.length === 0) return null;

    return (
        <div className="card p-5">
            <div className="flex items-center gap-3 mb-4 pb-4 border-b border-neutral-100">
                <div className="w-9 h-9 rounded-lg bg-purple-50 flex items-center justify-center text-base text-purple-600">⚕</div>
                <div>
                    <p className="text-base font-bold text-neutral-900">{title || 'Nearby Providers'}</p>
                    <p className="text-xs text-neutral-400 mt-0.5">{typeLabels[type] || 'Labs & hospitals'} near you</p>
                </div>
            </div>

            <div className="space-y-2">
                {providers.map((provider) => (
                    <Link
                        key={provider.id}
                        to={`/directory/${provider.slug}`}
                        className="flex items-center gap-3 bg-neutral-50 rounded-xl p-3 hover:bg-neutral-100 transition-colors"
                    >
                        <div className="w-10 h-10 rounded-lg bg-gradient-to-br from-teal-100 to-teal-50 flex items-center justify-center text-lg flex-shrink-0">
                            {provider.type === 'lab' ? '⚗' : provider.type === 'hospital' ? '🏥' : '⚕'}
                        </div>
                        <div className="flex-1 min-w-0">
                            <div className="flex items-center gap-2">
                                <p className="text-sm font-semibold text-neutral-900 truncate">{provider.name}</p>
                                {(provider.partner_status === 'sponsored' || provider.partner_status === 'affiliate') && (
                                    <span className={`text-[10px] font-bold px-1.5 py-0.5 rounded border ${partnerBadgeStyles[provider.partner_status] || ''}`}>
                                        {provider.partner_status === 'sponsored' ? 'Sponsored' : 'Partner'}
                                    </span>
                                )}
                            </div>
                            <div className="flex items-center gap-3 mt-0.5">
                                <span className="text-xs text-neutral-500">
                                    {[provider.city, provider.state].filter(Boolean).join(', ')}
                                </span>
                                {provider.distance_km != null && (
                                    <span className="text-xs text-teal-600 font-semibold">
                                        {provider.distance_km.toFixed(1)} km
                                    </span>
                                )}
                            </div>
                        </div>
                        <span className="text-neutral-300 text-lg">›</span>
                    </Link>
                ))}
            </div>

            <Link
                to="/directory"
                className="block text-center mt-4 text-sm font-bold text-teal-700 hover:text-teal-800"
            >
                View full directory →
            </Link>
        </div>
    );
}