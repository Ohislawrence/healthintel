import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';

export default function ProviderDirectory() {
    const [search, setSearch] = useState('');
    const [specialty, setSpecialty] = useState('');
    const [state, setState] = useState('');

    const { data: providersData, isLoading } = useQuery({
        queryKey: ['providers', search, specialty, state],
        queryFn: () => api.get('/providers', { params: { search, specialty, state, per_page: 20 } }),
    });
    const providers = providersData?.data || [];

    const { data: specialtiesData } = useQuery({
        queryKey: ['specialties'],
        queryFn: () => api.get('/providers/specialties'),
        staleTime: 60000,
    });
    const specialties = specialtiesData?.data || [];

    const { data: statesData } = useQuery({
        queryKey: ['states'],
        queryFn: () => api.get('/providers/states'),
        staleTime: 60000,
    });
    const states = statesData?.data || [];

    return (
        <div className="space-y-5">
            <div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Provider Directory</p>
                <p className="text-sm font-medium text-neutral-500 mt-0.5">Find hospitals, labs, doctors, and insurance near you</p>
            </div>

            {/* Filters */}
            <div className="space-y-3">
                <input
                    type="text"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="input-base"
                    placeholder="⌕ Search by name..."
                />
                <div className="flex gap-3">
                    <select value={specialty} onChange={(e) => setSpecialty(e.target.value)} className="input-base flex-1">
                        <option value="">All Specialties</option>
                        {specialties.map((s) => <option key={s} value={s}>{s}</option>)}
                    </select>
                    <select value={state} onChange={(e) => setState(e.target.value)} className="input-base flex-1">
                        <option value="">All States</option>
                        {states.map((s) => <option key={s} value={s}>{s}</option>)}
                    </select>
                </div>
            </div>

            {/* Results */}
            {isLoading ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {[1,2,3,4].map(i => <div key={i} className="card p-4 skeleton h-24 rounded-xl" />)}
                </div>
            ) : providers.length === 0 ? (
                <div className="card p-8 text-center">
                    <span className="text-3xl block mb-3">⚕</span>
                    <p className="text-sm font-bold text-neutral-900 mb-1">No providers found</p>
                    <p className="text-xs text-neutral-500">Try adjusting your search or filters</p>
                </div>
            ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {providers.map((p) => (
                        <Link
                            key={p.slug}
                            to={`/providers/${p.slug}`}
                            className="card p-4 hover:shadow-md hover:border-teal-200 transition-all"
                        >
                            <div className="flex items-center gap-2 mb-1">
                                <span className="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center text-sm text-teal-600">⚕</span>
                                <div>
                                    <p className="text-sm font-bold text-neutral-900">{p.name}</p>
                                    <p className="text-xs text-neutral-500">{p.type || 'Healthcare Provider'}</p>
                                </div>
                            </div>
                            <p className="text-xs text-neutral-400 mt-2">
                                {[p.specialty, p.city, p.state].filter(Boolean).join(' · ')}
                            </p>
                            {p.is_sponsored && (
                                <span className="mt-2 badge badge-warning text-[10px]">Sponsored</span>
                            )}
                        </Link>
                    ))}
                </div>
            )}
        </div>
    );
}