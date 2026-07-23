import React from 'react';
import { useParams, Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import Disclaimer from '../../components/ui/Disclaimer';

const flagColors = {
    normal: 'bg-green-50 text-green-700 border-green-200',
    low: 'bg-amber-50 text-amber-700 border-amber-200',
    high: 'bg-amber-50 text-amber-700 border-amber-200',
    critical_low: 'bg-red-50 text-red-700 border-red-200',
    critical_high: 'bg-red-50 text-red-700 border-red-200',
};

const flagLabels = {
    normal: 'Normal',
    low: 'Low',
    high: 'High',
    critical_low: 'Critical — Low',
    critical_high: 'Critical — High',
};

export default function ResultScreen() {
    const { id } = useParams();

    const { data, isLoading } = useQuery({
        queryKey: ['submission', id],
        queryFn: () => api.get(`/lab-submissions/${id}`),
        enabled: !!id,
    });

    const submission = data?.data?.submission;
    const values = submission?.values || [];
    const interpretation = submission?.interpretation;

    if (isLoading) {
        return (
            <div className="space-y-4 animate-pulse">
                <div className="h-8 w-48 rounded bg-gray-100" />
                <div className="h-6 w-72 rounded bg-gray-100" />
                <div className="h-48 rounded-xl bg-gray-100" />
            </div>
        );
    }

    if (!submission) {
        return (
            <div className="py-20 text-center">
                <p className="text-gray-500">Submission not found.</p>
                <Link to="/lab-results" className="text-teal-600 text-sm mt-2 inline-block">
                    Back to panels
                </Link>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-xl font-semibold text-gray-900">{submission.test_panel?.name}</h2>
                    <p className="text-sm text-gray-500">
                        Submitted {new Date(submission.submitted_at).toLocaleDateString()}
                    </p>
                </div>
                <Link
                    to="/lab-results"
                    className="text-sm text-teal-600 hover:text-teal-700"
                >
                    New submission
                </Link>
            </div>

            {/* Flag badges */}
            <div className="space-y-3">
                <h3 className="text-sm font-semibold text-gray-700 uppercase tracking-wide">Results</h3>
                {values.map((v) => (
                    <div
                        key={v.id}
                        className={`rounded-xl border p-4 ${flagColors[v.flag] || flagColors.normal}`}
                    >
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="font-semibold">{v.test_name}</p>
                                <p className="text-sm mt-0.5">
                                    {v.value} {v.unit}
                                </p>
                            </div>
                            <span className="rounded-full px-3 py-1 text-xs font-semibold border">
                                {flagLabels[v.flag] || v.flag}
                            </span>
                        </div>
                        <Link
                            to={`/trends/${v.test_slug}`}
                            className="mt-2 inline-block text-xs underline opacity-70 hover:opacity-100"
                        >
                            View trend
                        </Link>
                    </div>
                ))}
            </div>

            {/* Interpretation */}
            <div className="rounded-xl border border-gray-200 bg-white p-6">
                <h3 className="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">
                    Interpretation
                </h3>
                {interpretation?.status === 'completed' && interpretation?.interpretation_text ? (
                    <div className="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap">
                        {interpretation.interpretation_text}
                    </div>
                ) : interpretation?.status === 'failed' ? (
                    <p className="text-sm text-amber-700">
                        AI interpretation is currently unavailable. Please review the flagged results above and consult your healthcare provider.
                    </p>
                ) : (
                    <p className="text-sm text-gray-500">Interpretation pending...</p>
                )}
            </div>

            <Disclaimer />
        </div>
    );
}
