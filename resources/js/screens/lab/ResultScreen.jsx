import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';

const flagStyles = {
    normal: { bg: 'bg-success-50', text: 'text-success-700', border: 'border-success-200', dot: 'bg-success-500' },
    low: { bg: 'bg-warning-50', text: 'text-warning-700', border: 'border-warning-200', dot: 'bg-warning-500' },
    high: { bg: 'bg-warning-50', text: 'text-warning-700', border: 'border-warning-200', dot: 'bg-warning-500' },
    critical_low: { bg: 'bg-danger-50', text: 'text-danger-700', border: 'border-danger-200', dot: 'bg-danger-500' },
    critical_high: { bg: 'bg-danger-50', text: 'text-danger-700', border: 'border-danger-200', dot: 'bg-danger-500' },
};

export default function ResultScreen() {
    const { id } = useParams();
    const navigate = useNavigate();

    const { data, isLoading } = useQuery({
        queryKey: ['submission', id],
        queryFn: () => api.get(`/submissions/${id}`),
    });
    const submission = data?.data || {};
    const interpretation = submission.interpretation || {};
    const values = submission.values || [];
    const panel = submission.test_panel || {};

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
            <button onClick={() => navigate('/dashboard')} className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back</button>

            <div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">
                    {panel.name || 'Lab Result'}
                </p>
                <p className="text-sm text-neutral-500 mt-0.5">
                    {new Date(submission.created_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}
                </p>
            </div>

            {/* Status */}
            {interpretation.status === 'pending' && (
                <div className="card p-6 text-center">
                    <span className="text-3xl block mb-3">◐</span>
                    <p className="text-sm font-bold text-neutral-900">Interpretation in progress</p>
                    <p className="text-xs text-neutral-500 mt-1">Our AI is analyzing your results. Check back in a moment.</p>
                </div>
            )}

            {interpretation.status === 'failed' && (
                <div className="rounded-xl bg-danger-50 border border-danger-200 p-4 text-sm text-danger-700 font-medium">
                    Interpretation failed. Please try submitting again.
                </div>
            )}

            {/* Result Values */}
            {values.length > 0 && (
                <div className="space-y-2">
                    <p className="text-sm font-bold text-neutral-900">Your Results</p>
                    {values.map((v) => {
                        const flag = v.result_flag || 'normal';
                        const s = flagStyles[flag] || flagStyles.normal;
                        return (
                            <div key={v.id} className={`card p-4 border-l-4 ${s.border}`}>
                                <div className="flex items-center justify-between mb-1">
                                    <span className="text-sm font-bold text-neutral-900">{v.test?.name || v.test_name || 'Test'}</span>
                                    <span className={`badge ${s.bg} ${s.text}`}>
                                        <span className={`w-1.5 h-1.5 rounded-full ${s.dot}`} />
                                        {v.result_flag?.replace('_', ' ') || 'Normal'}
                                    </span>
                                </div>
                                <div className="flex items-baseline gap-2">
                                    <span className="text-xl font-extrabold text-neutral-900">{v.value}</span>
                                    <span className="text-xs text-neutral-400">{v.test?.unit || v.unit || ''}</span>
                                </div>
                                {v.reference_range && (
                                    <p className="text-xs text-neutral-400 mt-1">Ref: {v.reference_range}</p>
                                )}
                            </div>
                        );
                    })}
                </div>
            )}

            {/* AI Interpretation */}
            {interpretation.status === 'completed' && interpretation.summary && (
                <div className="card p-5">
                    <p className="text-sm font-bold text-neutral-900 mb-3">📋 AI Interpretation</p>
                    <p className="text-sm text-neutral-700 leading-relaxed whitespace-pre-line">{interpretation.summary}</p>
                    {interpretation.details && (
                        <p className="text-sm text-neutral-700 leading-relaxed mt-3 whitespace-pre-line">{interpretation.details}</p>
                    )}
                    {interpretation.recommendations && (
                        <div className="mt-4 pt-4 border-t border-neutral-100">
                            <p className="text-sm font-bold text-neutral-900 mb-2">💡 Recommendations</p>
                            <p className="text-sm text-neutral-700 leading-relaxed">{interpretation.recommendations}</p>
                        </div>
                    )}
                </div>
            )}

            {/* Trend Link */}
            {panel.slug && values.length > 0 && (
                <button onClick={() => navigate(`/trends/${panel.slug}`)} className="btn btn-outline w-full">
                    View Trends ↗
                </button>
            )}
        </div>
    );
}