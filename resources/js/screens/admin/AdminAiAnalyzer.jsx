import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';

export default function AdminAiAnalyzer() {
    const [copiedIndex, setCopiedIndex] = useState(null);
    const [hasRun, setHasRun] = useState(false);

    // enabled:false — no auto-run. The analysis only triggers on button click.
    const { data, isFetching, refetch, error } = useQuery({
        queryKey: ['admin-ai-analyzer'],
        queryFn: () => api.get('/admin/ai-analyzer'),
        enabled: false,
        staleTime: 0,
        retry: 0,
    });

    const payload = data?.data || {};
    const analysis = payload.analysis || {};
    const aiAvailable = payload.ai_available;
    const aiError = payload.ai_error;

    const marketingEmails = Array.isArray(analysis.marketing_emails) ? analysis.marketing_emails : [];
    const encourageUsage = Array.isArray(analysis.encourage_usage) ? analysis.encourage_usage : [];
    const growUsers = Array.isArray(analysis.grow_users) ? analysis.grow_users : [];
    const channels = Array.isArray(analysis.channels) ? analysis.channels : [];
    const quickWins = Array.isArray(analysis.quick_wins) ? analysis.quick_wins : [];

    const runAnalysis = async () => {
        setHasRun(true);
        await refetch();
    };

    const copyEmail = async (email, index) => {
        const text = `Subject: ${email.subject}\n\n${email.body}`;
        try {
            await navigator.clipboard.writeText(text);
            setCopiedIndex(index);
            setTimeout(() => setCopiedIndex(null), 2000);
        } catch {
            // Clipboard may be unavailable; no-op
        }
    };

    // Initial state: nothing run yet
    if (!hasRun) {
        return (
            <div className="space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-900">🤖 AI Growth Analyzer</h2>
                        <p className="text-sm text-gray-500">
                            DeepSeek-powered recommendations based on your live platform analytics.
                        </p>
                    </div>
                </div>
                <div className="rounded-xl border border-gray-200 bg-white p-12 text-center">
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-teal-50 text-3xl">
                        🤖
                    </div>
                    <h3 className="text-lg font-semibold text-gray-900">Analyze your platform</h3>
                    <p className="mx-auto mt-2 max-w-md text-sm text-gray-500">
                        The AI will review your user growth, revenue, engagement, referrals and provider data, then
                        suggest marketing emails, retention tactics, user-acquisition ideas, and communication channels.
                    </p>
                    <button
                        onClick={runAnalysis}
                        className="mt-6 inline-flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-6 py-3 text-sm font-semibold text-white hover:bg-teal-700"
                    >
                        ⚡ Generate AI Analysis
                    </button>
                </div>
            </div>
        );
    }

    // Loading state
    if (isFetching) {
        return (
            <div className="space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-900">🤖 AI Growth Analyzer</h2>
                        <p className="text-sm text-gray-500">
                            DeepSeek-powered recommendations based on your live platform analytics.
                        </p>
                    </div>
                </div>
                <div className="rounded-xl border border-gray-200 bg-white p-12 text-center">
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-teal-50 text-3xl">
                        🤖
                    </div>
                    <h3 className="text-lg font-semibold text-gray-900">Analyzing your platform data…</h3>
                    <p className="mt-2 text-sm text-gray-500">
                        The AI is reviewing your metrics and drafting recommendations. This can take up to a minute.
                    </p>
                    <div className="mx-auto mt-6 h-8 w-8 animate-spin rounded-full border-4 border-teal-500 border-t-transparent" />
                </div>
            </div>
        );
    }

    // Error state (network/HTTP failure)
    if (error) {
        return (
            <div className="space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-900">🤖 AI Growth Analyzer</h2>
                    </div>
                </div>
                <div className="rounded-xl border border-red-200 bg-red-50 p-8 text-center">
                    <p className="text-base font-semibold text-red-700">Failed to load AI analysis</p>
                    <p className="mt-2 text-sm text-red-600">{error.message}</p>
                    <button
                        onClick={runAnalysis}
                        className="mt-6 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700"
                    >
                        Retry
                    </button>
                </div>
            </div>
        );
    }

    // AI unavailable (DeepSeek error detail from backend)
    if (!aiAvailable && analysis.summary) {
        return (
            <div className="space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-900">🤖 AI Growth Analyzer</h2>
                    </div>
                </div>
                <div className="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center">
                    <p className="text-base font-semibold text-amber-800">The AI could not be reached</p>
                    {aiError && <p className="mt-2 text-sm text-amber-700">{aiError}</p>}
                    <button
                        onClick={runAnalysis}
                        className="mt-6 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700"
                    >
                        Try Again
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 className="text-xl font-semibold text-gray-900">🤖 AI Growth Analyzer</h2>
                    <p className="text-sm text-gray-500">
                        DeepSeek-powered recommendations based on your live platform analytics.
                    </p>
                </div>
                <button
                    onClick={runAnalysis}
                    disabled={isFetching}
                    className="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50"
                >
                    {isFetching ? 'Analyzing…' : 'Refresh Analysis'}
                </button>
            </div>

            {/* Executive Summary */}
            {analysis.summary && (
                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 className="font-semibold text-gray-900 mb-3">📋 Executive Summary</h3>
                    <p className="text-sm leading-relaxed text-gray-700 whitespace-pre-line">{analysis.summary}</p>
                    {payload.generated_at && (
                        <p className="mt-3 text-xs text-gray-400">
                            Generated {new Date(payload.generated_at).toLocaleString()}
                        </p>
                    )}
                </div>
            )}

            {/* Quick Wins */}
            {quickWins.length > 0 && (
                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 className="font-semibold text-gray-900 mb-4">⚡ Quick Wins This Week</h3>
                    <ul className="space-y-2">
                        {quickWins.map((win, i) => (
                            <li key={i} className="flex gap-3 text-sm text-gray-700">
                                <span className="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-700">
                                    {i + 1}
                                </span>
                                <span className="leading-relaxed">{typeof win === 'string' ? win : JSON.stringify(win)}</span>
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            {/* Marketing Emails */}
            {marketingEmails.length > 0 && (
                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 className="font-semibold text-gray-900 mb-4">📧 Suggested Marketing Emails</h3>
                    <div className="grid gap-4 lg:grid-cols-2">
                        {marketingEmails.map((email, i) => (
                            <div key={i} className="flex flex-col rounded-lg border border-gray-100 bg-gray-50 p-4">
                                <div className="flex items-start justify-between gap-3">
                                    <h4 className="text-sm font-semibold text-gray-900">“{email.subject}”</h4>
                                    <button
                                        onClick={() => copyEmail(email, i)}
                                        className="flex-shrink-0 rounded-md bg-white px-2 py-1 text-xs font-medium text-teal-700 border border-gray-200 hover:bg-teal-50"
                                    >
                                        {copiedIndex === i ? '✓ Copied' : 'Copy'}
                                    </button>
                                </div>
                                {email.goal && (
                                    <p className="mt-1 text-xs text-gray-500">
                                        <strong>Goal:</strong> {email.goal}
                                    </p>
                                )}
                                {email.target_segment && (
                                    <p className="mt-1 text-xs text-gray-500">
                                        <strong>Segment:</strong> {email.target_segment}
                                    </p>
                                )}
                                <p className="mt-3 text-sm leading-relaxed text-gray-700">{email.body}</p>
                            </div>
                        ))}
                    </div>
                    <p className="mt-4 text-xs text-gray-400">
                        Tip: Send these via the{' '}
                        <a href="/admin/emails" className="text-teal-600 underline">
                            Email Campaigns
                        </a>{' '}
                        section.
                    </p>
                </div>
            )}

            {/* Encourage Usage */}
            {encourageUsage.length > 0 && (
                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 className="font-semibold text-gray-900 mb-4">🔁 Ways to Encourage Usage</h3>
                    <ul className="space-y-2">
                        {encourageUsage.map((item, i) => (
                            <li key={i} className="flex gap-3 text-sm text-gray-700">
                                <span className="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">
                                    {i + 1}
                                </span>
                                <span className="leading-relaxed">{typeof item === 'string' ? item : JSON.stringify(item)}</span>
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            {/* Grow Users */}
            {growUsers.length > 0 && (
                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 className="font-semibold text-gray-900 mb-4">🚀 How to Get More Users</h3>
                    <ul className="space-y-2">
                        {growUsers.map((item, i) => (
                            <li key={i} className="flex gap-3 text-sm text-gray-700">
                                <span className="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-purple-100 text-xs font-bold text-purple-700">
                                    {i + 1}
                                </span>
                                <span className="leading-relaxed">{typeof item === 'string' ? item : JSON.stringify(item)}</span>
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            {/* Communication Channels */}
            {channels.length > 0 && (
                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 className="font-semibold text-gray-900 mb-4">📡 Communication Channels</h3>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {channels.map((ch, i) => (
                            <div key={i} className="rounded-lg border border-gray-100 bg-gray-50 p-4">
                                <h4 className="text-sm font-semibold text-gray-900 capitalize">{ch.channel}</h4>
                                {ch.strategy && (
                                    <p className="mt-2 text-xs leading-relaxed text-gray-600">
                                        <strong>Strategy:</strong> {ch.strategy}
                                    </p>
                                )}
                                {ch.why && (
                                    <p className="mt-2 text-xs leading-relaxed text-gray-500">
                                        <strong>Why:</strong> {ch.why}
                                    </p>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}