import React from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../lib/api';
import useAuthStore from '../stores/authStore';
import Disclaimer from '../components/ui/Disclaimer';

export default function Dashboard() {
    const { user, hasHealthProfile } = useAuthStore();

    const { data: submissionsData } = useQuery({
        queryKey: ['recent-submissions'],
        queryFn: () => api.get('/submissions', { params: { per_page: 5 } }),
    });

    const submissions = submissionsData?.data || [];
    const meta = submissionsData?.meta || {};

    const quickActions = [
        {
            title: 'Interpret a Lab Result',
            description: 'Submit test values and get a plain-language explanation.',
            to: '/lab-results/new',
            color: 'bg-teal-50 text-teal-700 border-teal-200 hover:bg-teal-100',
        },
        {
            title: 'Check Your Symptoms',
            description: 'Find out which tests are commonly relevant for your symptoms.',
            to: '/symptom-checker',
            color: 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100',
        },
        {
            title: 'Find a Provider',
            description: 'Search hospitals, labs, doctors, and insurance near you.',
            to: '/directory',
            color: 'bg-purple-50 text-purple-700 border-purple-200 hover:bg-purple-100',
        },
        {
            title: 'Buy Credits',
            description: 'Top up your credits to use lab interpretation and other features.',
            to: '/credits/buy',
            color: 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100',
        },
    ];

    return (
        <div className="space-y-6">
            {/* Welcome */}
            <div className="rounded-xl bg-white border border-gray-200 p-6">
                <h2 className="text-xl font-semibold text-gray-900">
                    Welcome{user?.name ? `, ${user.name}` : ''}
                </h2>
                <p className="mt-1 text-sm text-gray-600">
                    You have{' '}
                    <span className="font-semibold text-teal-600">{user?.credits ?? 0} credits</span>{' '}
                    available.
                </p>
            </div>

            {/* Health profile nudge */}
            {!hasHealthProfile() && (
                <div className="rounded-xl bg-amber-50 border border-amber-200 p-4 flex items-center justify-between">
                    <div>
                        <p className="text-sm font-semibold text-amber-800">
                            Complete your health profile
                        </p>
                        <p className="text-xs text-amber-700 mt-0.5">
                            This helps us give you more accurate lab result interpretations.
                        </p>
                    </div>
                    <Link
                        to="/onboarding"
                        className="shrink-0 rounded-lg bg-amber-100 px-4 py-2 text-xs font-semibold text-amber-800 hover:bg-amber-200 transition-colors"
                    >
                        Set up
                    </Link>
                </div>
            )}

            {/* Quick actions */}
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {quickActions.map((action) => (
                    <Link
                        key={action.title}
                        to={action.to}
                        className={`rounded-xl border p-5 transition-colors ${action.color}`}
                    >
                        <h3 className="font-semibold">{action.title}</h3>
                        <p className="mt-1 text-sm opacity-80">{action.description}</p>
                    </Link>
                ))}
            </div>

            {/* Recent activity */}
            <div className="rounded-xl bg-white border border-gray-200 p-6">
                <div className="flex items-center justify-between mb-3">
                    <h3 className="font-semibold text-gray-900">Recent Activity</h3>
                    <Link to="/credits" className="text-xs font-medium text-teal-600 hover:underline">
                        View all ({meta?.total || 0})
                    </Link>
                </div>
                {submissions.length === 0 ? (
                    <p className="text-sm text-gray-500">
                        Your lab result submissions and interpretations will appear here.
                    </p>
                ) : (
                    <div className="divide-y divide-gray-100">
                        {submissions.map((s) => (
                            <Link
                                key={s.id}
                                to={`/lab-results/submission/${s.id}`}
                                className="flex items-center justify-between py-3 hover:bg-gray-50 -mx-2 px-2 rounded-lg transition-colors"
                            >
                                <div>
                                    <p className="text-sm font-medium text-gray-900">
                                        {s.submission_type === 'pdf' ? '📄 PDF Upload' : s.test_panel?.name || 'Lab Result'}
                                    </p>
                                    <p className="text-xs text-gray-400 mt-0.5">
                                        {new Date(s.submitted_at || s.created_at).toLocaleDateString()} · {s.credits_used} credits
                                    </p>
                                </div>
                                <span className="text-xs text-gray-400">
                                    {s.interpretation?.status === 'completed' ? '✅' : s.interpretation?.status === 'failed' ? '⚠️' : '⏳'}
                                </span>
                            </Link>
                        ))}
                    </div>
                )}
            </div>

            <Disclaimer />
        </div>
    );
}
