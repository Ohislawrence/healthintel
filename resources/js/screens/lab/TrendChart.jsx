import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';

export default function TrendChart() {
    const { testSlug } = useParams();
    const navigate = useNavigate();

    const { data, isLoading } = useQuery({
        queryKey: ['trends', testSlug],
        queryFn: () => api.get('/trends', { params: { test_slug: testSlug } }),
    });
    const trendData = data?.data || {};
    const points = trendData.points || [];
    const testName = trendData.test_name || testSlug;

    const getMax = () => Math.max(...points.map(p => parseFloat(p.value) || 0), 100);
    const getMin = () => Math.min(...points.map(p => parseFloat(p.value) || 0), 0);
    const range = getMax() - getMin() || 1;

    return (
        <div className="space-y-5 max-w-lg mx-auto">
            <button onClick={() => navigate(-1)} className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back</button>

            <div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Trends</p>
                <p className="text-sm text-neutral-500 mt-0.5">{testName} over time</p>
            </div>

            {isLoading ? (
                <div className="card p-8 text-center">
                    <div className="skeleton h-64 w-full rounded-xl" />
                </div>
            ) : points.length === 0 ? (
                <div className="card p-8 text-center">
                    <span className="text-3xl block mb-3">▤</span>
                    <p className="text-sm font-bold text-neutral-900 mb-1">No trend data yet</p>
                    <p className="text-xs text-neutral-500">Submit more lab results to see trends over time</p>
                </div>
            ) : (
                <div className="card p-5">
                    {/* Simple chart visualization */}
                    <div className="relative h-48 border-b border-l border-neutral-200 mb-4">
                        <div className="absolute bottom-0 left-0 right-0 top-0 flex items-end gap-2 px-2">
                            {points.map((point, i) => {
                                const height = ((parseFloat(point.value) - getMin()) / range) * 90;
                                return (
                                    <div key={i} className="flex-1 flex flex-col items-center justify-end" style={{ height: '100%' }}>
                                        <span className="text-[9px] font-bold text-neutral-500 mb-0.5">{point.value}</span>
                                        <div
                                            className="w-full max-w-[40px] rounded-t-lg bg-teal-500/60 hover:bg-teal-500 transition-all min-h-[4px]"
                                            style={{ height: `${Math.max(height, 4)}%` }}
                                        />
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {/* X-axis labels */}
                    <div className="flex gap-2 px-2">
                        {points.map((point, i) => (
                            <span key={i} className="flex-1 text-[9px] text-neutral-400 text-center">
                                {new Date(point.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
                            </span>
                        ))}
                    </div>
                </div>
            )}

            {/* Data Table */}
            {points.length > 0 && (
                <div className="card overflow-hidden">
                    <div className="flex items-center px-4 py-2 bg-neutral-50 border-b border-neutral-100">
                        <span className="flex-1 text-xs font-bold text-neutral-500">Date</span>
                        <span className="text-xs font-bold text-neutral-500">Value</span>
                    </div>
                    {points.map((point, i) => (
                        <div
                            key={i}
                            className={`flex items-center px-4 py-2.5 ${
                                i < points.length - 1 ? 'border-b border-neutral-100' : ''
                            }`}
                        >
                            <span className="flex-1 text-sm text-neutral-900">
                                {new Date(point.date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}
                            </span>
                            <span className="text-sm font-bold text-neutral-900">{point.value} {point.unit || ''}</span>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}