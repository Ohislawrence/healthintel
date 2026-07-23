import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';

export default function InsuranceComparison() {
    const [search, setSearch] = useState('');

    const { data, isLoading } = useQuery({
        queryKey: ['hmos'],
        queryFn: () => api.get('/insurance/hmos'),
    });
    const hmos = data?.data?.hmo_list || [];

    const filtered = hmos.filter(h =>
        h.name?.toLowerCase().includes(search.toLowerCase())
    );

    return (
        <div className="space-y-5">
            <div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Insurance / HMO</p>
                <p className="text-sm font-medium text-neutral-500 mt-0.5">Compare insurance and HMO plans</p>
            </div>

            <input
                type="text"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="input-base"
                placeholder="⌕ Search insurance providers..."
            />

            {isLoading ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {[1,2,3,4].map(i => <div key={i} className="card p-4 skeleton h-24 rounded-xl" />)}
                </div>
            ) : filtered.length === 0 ? (
                <div className="card p-8 text-center">
                    <span className="text-3xl block mb-3">◆</span>
                    <p className="text-sm font-bold text-neutral-900 mb-1">No insurance providers found</p>
                    <p className="text-xs text-neutral-500">Check back later or adjust your search</p>
                </div>
            ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {filtered.map((hmo) => (
                        <div key={hmo.id || hmo.name} className="card p-4">
                            <p className="text-sm font-bold text-neutral-900">{hmo.name}</p>
                            <p className="text-xs text-neutral-500 mt-1 line-clamp-2">{hmo.description || 'Insurance provider'}</p>
                            <div className="flex items-center gap-2 mt-3">
                                {hmo.type && <span className="badge badge-success text-[10px]">{hmo.type}</span>}
                                {hmo.state && <span className="badge text-[10px] bg-indigo-50 text-indigo-700">{hmo.state}</span>}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Disclaimer */}
            <div className="card p-4 bg-neutral-25 border-neutral-200">
                <p className="text-xs text-neutral-500 leading-relaxed">
                    <span className="font-bold">ℹ Note:</span> Insurance information is provided for reference. Please verify coverage and network status directly with the provider.
                </p>
            </div>
        </div>
    );
}