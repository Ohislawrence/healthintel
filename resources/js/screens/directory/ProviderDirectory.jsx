import React, { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';

import SponsoredBannerCarousel from '../../components/SponsoredBannerCarousel';

function useDebouncedValue(value, delay = 350) {
    const [debounced, setDebounced] = useState(value);
    useEffect(() => {
        const t = setTimeout(() => setDebounced(value), delay);
        return () => clearTimeout(t);
    }, [value, delay]);
    return debounced;
}

export default function ProviderDirectory() {
    const [search, setSearch] = useState('');
    const [specialty, setSpecialty] = useState('');
    const [state, setState] = useState('');
    const [city, setCity] = useState('');
    const [type, setType] = useState('');
    const [insurance, setInsurance] = useState('');
    const [sort, setSort] = useState('relevance');
    const [page, setPage] = useState(1);
    const [coords, setCoords] = useState(null);
    const [locating, setLocating] = useState(false);

    const debouncedSearch = useDebouncedValue(search, 350);

    // Reset to page 1 whenever any filter changes
    useEffect(() => {
        setPage(1);
    }, [debouncedSearch, specialty, state, city, type, insurance, sort, coords]);

    const params = useMemo(() => {
        const p = {
            search: debouncedSearch || undefined,
            specialty: specialty || undefined,
            state: state || undefined,
            city: city || undefined,
            type: type || undefined,
            insurance: insurance || undefined,
            sort: sort || undefined,
            page,
            per_page: 20,
        };
        if (coords) {
            p.latitude = coords.latitude;
            p.longitude = coords.longitude;
        }
        return p;
    }, [debouncedSearch, specialty, state, city, type, insurance, sort, page, coords]);

    const { data: providersData, isLoading, isFetching } = useQuery({
        queryKey: ['providers', params],
        queryFn: () => api.get('/providers', { params }),
        keepPreviousData: true,
    });

    const providers = providersData?.data || [];
    const meta = providersData?.meta || {};
    const hasNextPage = meta.current_page < meta.last_page;

    const { data: specialtiesData } = useQuery({
        queryKey: ['specialties'],
        queryFn: () => api.get('/providers/specialties'),
        staleTime: 60000,
    });
    const specialties = specialtiesData?.data?.specialties || [];

    const { data: statesData } = useQuery({
        queryKey: ['states'],
        queryFn: () => api.get('/providers/states'),
        staleTime: 60000,
    });
    const states = statesData?.data?.states || [];

    const { data: citiesData } = useQuery({
        queryKey: ['cities'],
        queryFn: () => api.get('/providers/cities'),
        staleTime: 60000,
    });
    const cities = citiesData?.data?.cities || [];

    const { data: insurersData } = useQuery({
        queryKey: ['insurers'],
        queryFn: () => api.get('/providers/insurers'),
        staleTime: 60000,
    });
    const insurers = insurersData?.data?.insurers || [];

    const TYPES = [
        { value: '', label: 'All Types' },
        { value: 'hospital', label: 'Hospitals' },
        { value: 'clinic', label: 'Clinics' },
        { value: 'lab', label: 'Labs' },
        { value: 'pharmacy', label: 'Pharmacies' },
        { value: 'specialist', label: 'Specialists' },
        { value: 'insurance', label: 'Insurance' },
    ];

    const useMyLocation = () => {
        if (!navigator.geolocation) return;
        setLocating(true);
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                setCoords({ latitude: pos.coords.latitude, longitude: pos.coords.longitude });
                setLocating(false);
            },
            () => setLocating(false),
            { timeout: 10000, maximumAge: 600000 },
        );
    };

    const clearLocation = () => setCoords(null);

    return (
        <div className="space-y-5">
            <div className="flex items-start justify-between">
                <div>
                    <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Provider Directory</p>
                    <p className="text-sm font-medium text-neutral-500 mt-0.5">Find hospitals, labs, doctors, and insurance near you</p>
                </div>
                <Link
                    to="/providers/favorites"
                    className="text-xs font-semibold text-warning-700 bg-warning-50 border border-warning-200 rounded-lg px-2.5 py-1.5 whitespace-nowrap"
                >
                    ★ Saved
                </Link>
            </div>

            {/* Filters */}
            <div className="space-y-3">
                <input
                    type="text"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="input-base"
                    placeholder="⌕ Search by name, specialty, or city..."
                />
                <div className="grid grid-cols-2 gap-3">
                    <select value={type} onChange={(e) => setType(e.target.value)} className="input-base">
                        {TYPES.map((t) => <option key={t.value} value={t.value}>{t.label}</option>)}
                    </select>
                    <select value={specialty} onChange={(e) => setSpecialty(e.target.value)} className="input-base">
                        <option value="">All Specialties</option>
                        {specialties.map((s) => <option key={s} value={s}>{s}</option>)}
                    </select>
                    <select value={state} onChange={(e) => setState(e.target.value)} className="input-base">
                        <option value="">All States</option>
                        {states.map((s) => <option key={s} value={s}>{s}</option>)}
                    </select>
                    <select value={city} onChange={(e) => setCity(e.target.value)} className="input-base">
                        <option value="">All Cities</option>
                        {cities.map((c) => <option key={c} value={c}>{c}</option>)}
                    </select>
                    <select value={insurance} onChange={(e) => setInsurance(e.target.value)} className="input-base">
                        <option value="">All Insurance</option>
                        {insurers.map((ins) => <option key={ins} value={ins}>{ins}</option>)}
                    </select>
                </div>

                {/* Location + sort controls */}
                <div className="flex items-center gap-2">
                    <button
                        onClick={useMyLocation}
                        disabled={locating}
                        className="btn bg-neutral-100 text-neutral-700 text-xs"
                    >
                        {locating ? 'Locating…' : coords ? '📍 Near me (on)' : '📍 Use my location'}
                    </button>
                    {coords && (
                        <button onClick={clearLocation} className="btn bg-neutral-100 text-neutral-500 text-xs">
                            ✕ Clear
                        </button>
                    )}
                    <select value={sort} onChange={(e) => setSort(e.target.value)} className="input-base flex-1 !py-2 text-xs">
                        <option value="relevance">Sort: Recommended</option>
                        <option value="rating">Sort: Highest Rated</option>
                        <option value="name">Sort: A–Z</option>
                    </select>
                </div>
            </div>

            {/* Sponsored Banner */}
            <SponsoredBannerCarousel />

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
                <>
                    <div className={`grid grid-cols-1 sm:grid-cols-2 gap-3 ${isFetching ? 'opacity-60' : ''}`}>
                        {providers.map((p) => (
                            <Link
                                key={p.slug}
                                to={`/providers/${p.slug}`}
                                className="card p-4 hover:shadow-md hover:border-teal-200 transition-all"
                            >
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
                                <div className="flex items-center gap-2 mt-1.5 flex-wrap">
                                    {p.rating_count > 0 && (
                                        <span className="text-[10px] font-semibold text-warning-600">★ {p.rating_avg} ({p.rating_count})</span>
                                    )}
                                    {p.locations_count > 1 && (
                                        <span className="text-[10px] font-semibold text-teal-600">{p.locations_count} locations</span>
                                    )}
                                    {p.distance_km != null && (
                                        <span className="text-[10px] font-semibold text-indigo-600">{p.distance_km} km</span>
                                    )}
                                    {p.is_sponsored && (
                                        <span className="badge badge-warning text-[10px]">Sponsored</span>
                                    )}
                                    {p.is_verified && (
                                        <span className="badge badge-success text-[10px]">✓ Verified</span>
                                    )}
                                    {p.is_open_now === true && (
                                        <span className="badge badge-success text-[10px]">Open now</span>
                                    )}
                                    {p.is_open_now === false && (
                                        <span className="badge bg-neutral-200 text-neutral-600 text-[10px]">Closed</span>
                                    )}
                                </div>
                            </Link>
                        ))}
                    </div>

                    {hasNextPage && (
                        <button
                            onClick={() => setPage((v) => v + 1)}
                            disabled={isFetching}
                            className="btn w-full bg-neutral-100 text-neutral-700 text-sm"
                        >
                            {isFetching ? 'Loading…' : `Load more (${meta.total - meta.to} remaining)`}
                        </button>
                    )}
                </>
            )}
        </div>
    );
}