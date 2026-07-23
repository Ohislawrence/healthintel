import React from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';

export default function PanelPicker() {
    const { data, isLoading } = useQuery({
        queryKey: ['panels'],
        queryFn: () => api.get('/panels'),
    });
    const panels = data?.data || [];

    return (
        <div className="space-y-5">
            <div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Lab Results</p>
                <p className="text-sm font-medium text-neutral-500 mt-0.5">Choose a test panel or upload a PDF</p>
            </div>

            {/* PDF Upload Card */}
            <Link to="/lab-results" className="card p-5 flex items-center gap-4 hover:shadow-md hover:border-teal-200 transition-all border-dashed">
                <div className="w-12 h-12 rounded-xl gradient-teal flex items-center justify-center text-xl text-white">⇧</div>
                <div className="flex-1">
                    <p className="text-sm font-bold text-neutral-900">Upload a PDF Report</p>
                    <p className="text-xs text-neutral-500 mt-0.5">We'll extract and interpret your lab values</p>
                </div>
                <span className="text-xl text-neutral-300 font-bold">›</span>
            </Link>

            {/* Panel Grid */}
            <div>
                <p className="text-sm font-bold text-neutral-900 mb-3">Or pick a test panel</p>
                {isLoading ? (
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {[1,2,3,4].map(i => (
                            <div key={i} className="card p-4 skeleton h-24 rounded-xl" />
                        ))}
                    </div>
                ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {panels.map((panel) => (
                            <Link
                                key={panel.slug}
                                to={`/lab-results/${panel.slug}`}
                                className="card p-4 hover:shadow-md hover:border-teal-200 transition-all"
                            >
                                <p className="text-sm font-bold text-neutral-900">{panel.name}</p>
                                <p className="text-xs text-neutral-500 mt-1 line-clamp-2">{panel.description || `Enter your ${panel.name} values for instant interpretation.`}</p>
                                <p className="text-[10px] font-bold text-teal-700 mt-2 uppercase tracking-wider">
                                    {panel.test_count || 'Multiple'} tests →
                                </p>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}