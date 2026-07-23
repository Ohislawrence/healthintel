import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';

export default function PanelPicker() {
    const navigate = useNavigate();
    const [search, setSearch] = useState('');

    const { data, isLoading } = useQuery({
        queryKey: ['panels'],
        queryFn: () => api.get('/panels'),
    });

    const panels = data?.data?.panels || [];

    const filtered = panels.filter((p) =>
        p.name.toLowerCase().includes(search.toLowerCase()),
    );

    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-xl font-semibold text-gray-900">
                    Choose a Lab Panel
                </h2>
                <p className="mt-1 text-sm text-gray-500">
                    Select a test panel to submit your results.
                </p>
            </div>

            <input
                type="text"
                placeholder="Search panels..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-200 outline-none"
            />

            {isLoading ? (
                <div className="grid gap-4 sm:grid-cols-2">
                    {[1, 2, 3, 4].map((i) => (
                        <div key={i} className="h-32 animate-pulse rounded-xl bg-gray-100" />
                    ))}
                </div>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2">
                    {filtered.map((panel) => (
                        <button
                            key={panel.id}
                            onClick={() => navigate(`/lab-results/${panel.slug}`)}
                            className="text-left rounded-xl border border-gray-200 bg-white p-5 hover:border-teal-300 hover:shadow-sm transition-all"
                        >
                            <h3 className="font-semibold text-gray-900">{panel.name}</h3>
                            <p className="mt-1 text-sm text-gray-500 line-clamp-2">
                                {panel.description}
                            </p>
                            <span className="mt-2 inline-block text-xs text-teal-600">
                                {panel.tests?.length || 0} tests
                            </span>
                        </button>
                    ))}
                </div>
            )}

            {!isLoading && filtered.length === 0 && (
                <p className="text-center text-sm text-gray-500 py-10">
                    No panels match your search.
                </p>
            )}
        </div>
    );
}
