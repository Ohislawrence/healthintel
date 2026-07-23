import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../../lib/api';

const TYPE_LABELS = { hospital: 'Hospitals', clinic: 'Clinics', lab: 'Labs', pharmacy: 'Pharmacies', specialist: 'Specialists', insurance: 'Insurance' };
const PARTNER_BADGES = { affiliate: { bg: 'bg-amber-50', text: 'text-amber-700', label: 'Partner' }, sponsored: { bg: 'bg-indigo-50', text: 'text-indigo-700', label: 'Sponsored' } };

export default function ProviderDirectory() {
    const [search, setSearch] = useState('');
    const [specialty, setSpecialty] = useState('');
    const [state, setState] = useState('');
    const [selectedType, setSelectedType] = useState('');
    const [page, setPage] = useState(1);

    const { data: specsData } = useQuery({ queryKey: ['specialties'], queryFn: () => api.get('/directory/specialties') });
    const { data: statesData } = useQuery({ queryKey: ['states'], queryFn: () => api.get('/directory/states') });
    const { data: typesData } = useQuery({ queryKey: ['types'], queryFn: () => api.get('/directory/types') });

    const { data, isLoading } = useQuery({
        queryKey: ['providers', search, specialty, state, selectedType, page],
        queryFn: () => api.get('/providers', {
            params: { search: search || undefined, specialty: specialty || undefined, state: state || undefined, type: selectedType || undefined, page },
        }),
    });

    const specialties = specsData?.data?.specialties || [];
    const states = statesData?.data?.states || [];
    const types = typesData?.data?.types || [];
    const providers = data?.data?.data || [];
    const pagination = data?.data || {};

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-xl font-semibold text-gray-900">Provider Directory</h2>
                    <p className="mt-1 text-sm text-gray-500">Find hospitals, clinics, labs, and insurance providers near you.</p>
                </div>
                <Link to="/insurance" className="rounded-lg border border-teal-300 px-4 py-2 text-sm font-semibold text-teal-700 hover:bg-teal-50">
                    Compare Insurance
                </Link>
            </div>

            {/* Type tabs */}
            <div className="flex flex-wrap gap-2">
                <button
                    onClick={() => { setSelectedType(''); setPage(1); }}
                    className={`rounded-full px-4 py-1.5 text-xs font-semibold transition-colors ${!selectedType ? 'bg-teal-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'}`}
                >
                    All
                </button>
                {types.map((t) => (
                    <button
                        key={t}
                        onClick={() => { setSelectedType(t); setPage(1); }}
                        className={`rounded-full px-4 py-1.5 text-xs font-semibold transition-colors ${selectedType === t ? 'bg-teal-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'}`}
                    >
                        {TYPE_LABELS[t] || t}
                    </button>
                ))}
            </div>

            {/* Filters */}
            <div className="grid gap-3 sm:grid-cols-3">
                <input type="text" placeholder="Search by name or city..." value={search} onChange={(e) => { setSearch(e.target.value); setPage(1); }} className="rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-200 outline-none" />
                <select value={specialty} onChange={(e) => { setSpecialty(e.target.value); setPage(1); }} className="rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 outline-none">
                    <option value="">All Specialties</option>
                    {specialties.map((s) => <option key={s} value={s}>{s}</option>)}
                </select>
                <select value={state} onChange={(e) => { setState(e.target.value); setPage(1); }} className="rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 outline-none">
                    <option value="">All States</option>
                    {states.map((s) => <option key={s} value={s}>{s}</option>)}
                </select>
            </div>

            {/* Results */}
            {isLoading ? (
                <div className="space-y-3">
                    {Array.from({ length: 5 }).map((_, i) => (
                        <div key={i} className="h-24 animate-pulse rounded-xl bg-gray-100" />
                    ))}
                </div>
            ) : providers.length === 0 ? (
                <p className="text-center text-gray-500 py-10">No providers found.</p>
            ) : (
                <div className="space-y-3">
                    {providers.map((p) => (
                        <Link
                            key={p.id}
                            to={`/providers/${p.slug}`}
                            className="block rounded-xl border border-gray-200 bg-white p-5 hover:border-teal-300 hover:shadow-sm transition-all"
                        >
                            <div className="flex items-start justify-between">
                                <div className="flex-1">
                                    <div className="flex items-center gap-2 flex-wrap">
                                        <h3 className="font-semibold text-gray-900">{p.name}</h3>
                                        <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 uppercase">{p.type}</span>
                                        {p.is_verified && (
                                            <span className="rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">✓ Verified</span>
                                        )}
                                        {PARTNER_BADGES[p.partner_status] && (
                                            <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${PARTNER_BADGES[p.partner_status].bg} ${PARTNER_BADGES[p.partner_status].text}`}>
                                                {PARTNER_BADGES[p.partner_status].label}
                                            </span>
                                        )}
                                    </div>
                                    <p className="mt-1 text-sm text-gray-500">{p.specialty}</p>
                                    <p className="mt-1 text-xs text-gray-400">
                                        {[p.city, p.state, p.country].filter(Boolean).join(', ')}
                                        {p.distance_km != null && ` · ${p.distance_km} km`}
                                    </p>
                                </div>
                                <span className="text-teal-500 text-sm">→</span>
                            </div>
                        </Link>
                    ))}
                </div>
            )}

            {/* Pagination */}
            {pagination?.last_page > 1 && (
                <div className="flex justify-center gap-2">
                    {Array.from({ length: pagination.last_page }, (_, i) => i + 1).map((p) => (
                        <button
                            key={p}
                            onClick={() => setPage(p)}
                            className={`rounded-lg px-3 py-1.5 text-sm font-medium transition-colors ${
                                page === p ? 'bg-teal-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'
                            }`}
                        >
                            {p}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
