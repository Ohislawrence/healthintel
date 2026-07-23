import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import InterpretationCards from '../../components/ui/InterpretationCards';

const flagStyles = {
    normal: { bg: 'bg-success-50', text: 'text-success-700', border: 'border-success-200', dot: 'bg-success-500' },
    low: { bg: 'bg-warning-50', text: 'text-warning-700', border: 'border-warning-200', dot: 'bg-warning-500' },
    high: { bg: 'bg-warning-50', text: 'text-warning-700', border: 'border-warning-200', dot: 'bg-warning-500' },
    critical_low: { bg: 'bg-danger-50', text: 'text-danger-700', border: 'border-danger-200', dot: 'bg-danger-500' },
    critical_high: { bg: 'bg-danger-50', text: 'text-danger-700', border: 'border-danger-200', dot: 'bg-danger-500' },
};
const flagLabels = {
    normal: 'Normal', low: 'Low', high: 'High',
    critical_low: 'Critical Low', critical_high: 'Critical High',
};

export default function ResultScreen() {
    const { id } = useParams();
    const navigate = useNavigate();

    const { data, isLoading } = useQuery({
        queryKey: ['submission', id],
        queryFn: () => api.get(`/submissions/${id}`),
        refetchInterval: (query) => {
            const d = query.state.data;
            if (d?.data?.submission?.interpretation?.status === 'pending') return 3000;
            return false;
        },
    });
    const submission = data?.data?.submission || {};
    const interpretation = submission.interpretation || {};
    const values = submission.values || [];
    const panel = submission.test_panel || {};

    const isPending = interpretation?.status === 'pending';
    const isFailed = interpretation?.status === 'failed';
    const isCompleted = interpretation?.status === 'completed';
    const isPdf = submission.submission_type === 'pdf';

    // ── Loading State ────────────────────────────
    if (isLoading) {
        return (
            <div className="max-w-xl mx-auto space-y-4">
                <div className="skeleton h-8 w-48 rounded" />
                <div className="skeleton h-64 w-full rounded-xl" />
            </div>
        );
    }

    // ── Processing / Pending State (matching PdfResultScreen loading) ──
    if (isPending) {
        return (
            <div className="max-w-md mx-auto text-center py-16">
                <div className="w-20 h-20 rounded-2xl bg-teal-50 border-2 border-teal-200 flex items-center justify-center mx-auto mb-6">
                    <span className="text-4xl text-teal-500">◐</span>
                </div>
                <p className="text-lg font-bold text-neutral-900 mb-2">
                    {isPdf ? 'Analyzing Your Report' : 'Processing your results...'}
                </p>
                <p className="text-sm text-neutral-500 mb-6 leading-relaxed">
                    {isPdf
                        ? 'Our AI is reading your lab results and preparing a plain-language explanation...'
                        : 'This usually takes 30-60 seconds'}
                </p>
                <div className="w-8 h-8 border-4 border-teal-500 border-t-transparent rounded-full animate-spin mx-auto" />
            </div>
        );
    }

    // ── Failed State (matching PdfResultScreen error) ──
    if (isFailed) {
        return (
            <div className="max-w-xl mx-auto space-y-5">
                <button onClick={() => navigate('/dashboard')} className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back</button>

                <div className="flex items-center gap-2 card p-3">
                    <span className="w-2 h-2 rounded-full bg-danger-500" />
                    <span className="text-sm font-semibold text-neutral-600">Interpretation unavailable</span>
                </div>

                <div className="card p-5 bg-danger-50 border-danger-200">
                    <div className="flex gap-3 mb-4">
                        <span className="text-2xl">⚠</span>
                        <div>
                            <p className="text-base font-bold text-danger-700 mb-1">Interpretation Unavailable</p>
                            <p className="text-sm text-danger-800 leading-relaxed">
                                {interpretation?.error_message || 'The AI could not process this report.'}
                            </p>
                        </div>
                    </div>
                    <div className="bg-white rounded-xl p-4">
                        <p className="text-sm font-bold text-neutral-900 mb-2">What you can try:</p>
                        {['Use a text-based PDF', 'Ensure report is in English', 'Try uploading a clearer copy', 'Enter values manually instead'].map((tip, i) => (
                            <div key={i} className="flex gap-2 mb-1">
                                <span className="text-sm text-neutral-400 font-bold">·</span>
                                <span className="text-sm text-neutral-600">{tip}</span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        );
    }

    // ── Info Card (matching PdfResultScreen infoRow) ──
    return (
        <div className="max-w-xl mx-auto space-y-5">
            <button onClick={() => navigate('/dashboard')} className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back</button>

            {/* Status Banner */}
            <div className="flex items-center gap-2 card p-3">
                <span className="w-2 h-2 rounded-full bg-success-500" />
                <span className="text-sm font-semibold text-neutral-600">Interpretation complete</span>
            </div>

            {/* Info Row */}
            <div className="card p-5">
                <div className="flex items-center">
                    <div className="flex-1 text-center">
                        <p className="text-[10px] font-bold text-neutral-400 uppercase tracking-wider mb-1">Type</p>
                        <p className="text-sm font-semibold text-neutral-900">{isPdf ? 'PDF Upload' : panel.name || 'Lab Result'}</p>
                    </div>
                    <div className="w-px h-10 bg-neutral-100" />
                    <div className="flex-1 text-center">
                        <p className="text-[10px] font-bold text-neutral-400 uppercase tracking-wider mb-1">Date</p>
                        <p className="text-sm font-semibold text-neutral-900">
                            {new Date(submission.submitted_at || submission.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                        </p>
                    </div>
                    <div className="w-px h-10 bg-neutral-100" />
                    <div className="flex-1 text-center">
                        <p className="text-[10px] font-bold text-neutral-400 uppercase tracking-wider mb-1">Credits</p>
                        <p className="text-sm font-semibold text-neutral-900">{submission.credits_used || 3}</p>
                    </div>
                </div>
            </div>

            {/* ── Extracted Biomarker Values (matching PdfResultScreen valuesCard) ── */}
            {values.length > 0 && (
                <div className="card p-5">
                    <div className="flex items-center gap-3 mb-4 pb-4 border-b border-neutral-100">
                        <div className="w-9 h-9 rounded-lg bg-success-50 flex items-center justify-center text-base text-success-600">▼</div>
                        <div>
                            <p className="text-base font-bold text-neutral-900">Extracted Biomarkers</p>
                            <p className="text-xs text-neutral-400 mt-0.5">{values.length} value{values.length > 1 ? 's' : ''} extracted from your {isPdf ? 'PDF' : 'submission'}</p>
                        </div>
                    </div>
                    <div className="space-y-2">
                        {values.map((v) => {
                            const flag = v.result_flag || v.flag || 'normal';
                            const s = flagStyles[flag] || flagStyles.normal;
                            return (
                                <div key={v.id || v.test_slug} className="flex items-center gap-3 bg-neutral-50 rounded-xl p-3 border-l-[3px]" style={{ borderLeftColor: flag === 'normal' ? '#22C55E' : flag.includes('low') ? '#F59E0B' : flag.includes('high') && !flag.includes('critical') ? '#F59E0B' : '#EF4444' }}>
                                    <div className="flex-1">
                                        <p className="text-sm font-semibold text-neutral-900">{v.test?.name || v.test_name || v.name || 'Test'}</p>
                                        <p className="text-xs text-neutral-400 mt-0.5">{v.value} {v.test?.unit || v.unit || ''}</p>
                                    </div>
                                    <span className={`badge ${s.bg} ${s.text}`}>
                                        <span className={`w-1.5 h-1.5 rounded-full ${s.dot}`} />
                                        {flagLabels[flag] || flag}
                                    </span>
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}

            {/* ── AI Interpretation (matching PdfResultScreen resultCard) ── */}
            {isCompleted && interpretation?.interpretation_text ? (
                <div className="card p-5 lg:p-6 border-teal-100">
                    <div className="flex items-center gap-3 mb-4 pb-4 border-b border-neutral-100">
                        <div className="w-10 h-10 rounded-lg bg-teal-50 flex items-center justify-center text-xl text-teal-600">◉</div>
                        <p className="text-lg font-extrabold text-neutral-900">AI Interpretation</p>
                    </div>
                    <InterpretationCards markdownText={interpretation.interpretation_text} />
                </div>
            ) : isCompleted && (interpretation.summary || interpretation.details) ? (
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
            ) : null}

            {/* Trend Link */}
            {panel.slug && values.length > 0 && (
                <button onClick={() => navigate(`/trends/${panel.slug}`)} className="btn btn-outline w-full">
                    View Trends ↗
                </button>
            )}

            {/* Disclaimer */}
            <div className="card p-4 bg-teal-50 border-teal-200 flex gap-2.5 items-start">
                <span className="text-lg text-teal-600 mt-0.5">ℹ</span>
                <p className="text-sm text-teal-700 leading-relaxed">This is not a medical diagnosis. Please consult a licensed healthcare professional.</p>
            </div>
        </div>
    );
}