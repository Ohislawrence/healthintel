import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';

export default function ProviderFavorites() {
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    const { data, isLoading } = useQuery({
        queryKey: ['provider-favorites'],
        queryFn: () => api.get('/providers/favorites'),
    });
    const providers = data?.data?.providers || [];

    const removeMutation = useMutation({
        mutationFn: (slug) => api.post(`/providers/${slug}/favorite`),
        onSuccess: () => {
            queryClient.invalidateQueries(['provider-favorites']);
        },
    });

    return (
        <div className="space-y-5">
            <div>
                <button onClick={() => navigate('/directory')} className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back to Directory</button>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight mt-1">Saved Providers</p>
                <p className="text-sm font-medium text-neutral-500 mt-0.5">Your shortlist of preferred providers</p>
            </div>

            {isLoading ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {[1,2,3,4].map(i => <div key={i} className="card p-4 skeleton h-24 rounded-xl" />)}
                </div>
            ) : providers.length === 0 ? (
                <div className="card p-8 text-center">
                    <span className="text-3xl block mb-3">★</span>
                    <p className="text-sm font-bold text-neutral-900 mb-1">No saved providers</p>
                    <p className="text-xs text-neutral-500 mb-4">Tap "Save Provider" on any listing to add it here.</p>
                    <Link to="/directory" className="btn bg-teal-600 text-white text-sm">Browse Directory</Link>
                </div>
            ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {providers.map((p) => (
                        <div key={p.slug} className="card p-4 relative">
                            <Link to={`/providers/${p.slug}`} className="block">
                                <div className="flex items-center gap-2 mb-1">
                                    {p.logo_url ? (
                                        <img src={p.logo_url} alt={p.name} className="w-8 h-8 rounded-lg object-contain bg-white border border-neutral-100" onError={(e) => { e.currentTarget.style.display = 'none'; }} />
                                    ) : (
                                        <span className="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center text-sm text-teal-600">⚕</span>
                                    )}
                                    <div className="min-w-0">
                                        <p className="text-sm font-bold text-neutral-900 truncate">{p.name}</p>
                                        <p className="text-xs text-neutral-500">{p.type || 'Healthcare Provider'}</p>
                                    </div>
                                </div>
                                <p className="text-xs text-neutral-400 mt-2">
                                    {[p.specialty, p.city, p.state].filter(Boolean).join(' · ')}
                                </p>
                            </Link>
                            <button
                                onClick={() => removeMutation.mutate(p.slug)}
                                disabled={removeMutation.isPending}
                                className="absolute top-3 right-3 text-neutral-300 hover:text-red-500 text-lg leading-none"
                                title="Remove"
                            >
                                ✕
                            </button>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}