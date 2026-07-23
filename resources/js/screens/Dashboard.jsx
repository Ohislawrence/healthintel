import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../lib/api';
import useAuthStore from '../stores/authStore';

function StatPill({ value, label, icon, color, to }) {
    const navigate = useNavigate();
    const Wrapper = to ? Link : 'div';
    return (
        <Wrapper
            to={to || '#'}
            onClick={!to ? undefined : undefined}
            className={`flex-1 card card-sm p-3 flex flex-col items-center ${to ? 'cursor-pointer hover:shadow-md hover:border-teal-200 transition-all' : ''}`}
        >
            <span className="w-9 h-9 rounded-lg flex items-center justify-center text-lg" style={{ backgroundColor: color + '15' }}>
                {icon}
            </span>
            <span className="mt-1.5 text-xl font-extrabold tracking-tight" style={{ color }}>{value}</span>
            <span className="text-[10px] font-bold text-neutral-400 uppercase tracking-wider mt-0.5">{label}</span>
        </Wrapper>
    );
}

function FeatureCard({ icon, color, title, subtitle, to }) {
    return (
        <Link
            to={to}
            className="card p-4 flex flex-col hover:shadow-md hover:border-teal-200 transition-all"
        >
            <span className="w-11 h-11 rounded-lg flex items-center justify-center text-xl mb-3" style={{ backgroundColor: color + '12' }}>
                <span style={{ color }}>{icon}</span>
            </span>
            <span className="text-sm font-bold text-neutral-900 mb-0.5">{title}</span>
            <span className="text-xs text-neutral-500 leading-relaxed line-clamp-2">{subtitle}</span>
        </Link>
    );
}

export default function Dashboard() {
    const { user, hasHealthProfile } = useAuthStore();
    const navigate = useNavigate();
    const firstName = user?.name?.split(' ')[0] || '';
    const profileComplete = !!user?.health_profile?.profile_completed;

    const { data: submissionsData, isLoading } = useQuery({
        queryKey: ['recent-submissions'],
        queryFn: () => api.get('/submissions', { params: { per_page: 5 } }),
    });
    const submissions = submissionsData?.data || [];
    const meta = submissionsData?.meta || {};

    const { data: scoreData } = useQuery({
        queryKey: ['health-score'],
        queryFn: () => api.get('/health-score'),
        staleTime: 1000 * 60 * 2,
    });
    const scoreRes = scoreData?.data;
    const healthScore = scoreRes?.score ?? null;
    const healthCategory = scoreRes?.category ?? null;

    const { data: todayData } = useQuery({
        queryKey: ['today-trackers'],
        queryFn: () => api.get('/health-metrics/today'),
        staleTime: 1000 * 60 * 2,
    });
    const todayTrackers = todayData?.data?.trackers ?? {};
    const trackerChips = [];
    if (todayTrackers.bp) {
        const bp = todayTrackers.bp;
        const bpColor = bp.avg_sys >= 140 || bp.avg_dia >= 90 ? '#DC2626' : bp.avg_sys >= 130 ? '#D97706' : '#16A34A';
        trackerChips.push({ icon: '⬤', label: 'BP', value: `${bp.avg_sys}/${bp.avg_dia}`, color: bpColor });
    }
    if (todayTrackers.water) {
        const water = todayTrackers.water;
        const pct = water.goal_ml > 0 ? Math.round((water.ml / water.goal_ml) * 100) : 0;
        trackerChips.push({ icon: '∼', label: 'Water', value: `${pct}%`, color: '#2563EB' });
    }
    if (todayTrackers.diary) {
        const diary = todayTrackers.diary;
        if (diary.foods > 0 || diary.symptoms > 0) {
            trackerChips.push({ icon: '●', label: 'Diary', value: `${diary.foods}f · ${diary.symptoms}s`, color: '#16A34A' });
        }
    }

    const profileSubtitle = profileComplete
        ? (() => {
            const parts = [];
            const hp = user?.health_profile;
            if (hp?.sex) parts.push(hp.sex.charAt(0).toUpperCase() + hp.sex.slice(1));
            if (hp?.date_of_birth) {
                const age = new Date().getFullYear() - new Date(hp.date_of_birth).getFullYear();
                parts.push(`${age}y`);
            }
            if (hp?.blood_type) parts.push(hp.blood_type);
            return parts.join(' · ') || 'Update your health information for better insights';
        })()
        : 'Set up your profile for personalized results';

    const featureCards = [
        { icon: '⚛', color: '#0F766E', title: 'Lab Results', subtitle: 'Upload or enter lab values', to: '/lab-results' },
        { icon: '♡', color: '#4F46E5', title: 'Symptom Check', subtitle: 'AI-powered health guidance', to: '/symptom-checker' },
        { icon: '⚕', color: '#7C3AED', title: 'Find Providers', subtitle: 'Labs & hospitals near you', to: '/directory' },
        { icon: '◉', color: '#16A34A', title: 'Health Tools', subtitle: 'Calculators & trackers', to: '/dashboard' },
    ];

    return (
        <div className="space-y-5">
            {/* ── Header ─────────────────────────────────── */}
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">
                        Hello, {firstName || 'there'}
                    </p>
                    <p className="text-sm font-medium text-neutral-500 mt-0.5">Your health dashboard</p>
                </div>
            </div>

            {/* ── Stats Row ──────────────────────────────── */}
            <div className="flex gap-3">
                <StatPill value={user?.credits ?? 0} label="Credits" icon="◆" color="#0F766E" to="/credits" />
                <StatPill value={meta?.total || 0} label="Reports" icon="⚛" color="#4F46E5" />
                <StatPill
                    value={profileComplete ? '✓' : '···'}
                    label="Profile"
                    icon="◉"
                    color={profileComplete ? '#16A34A' : '#D97706'}
                    to="/onboarding"
                />
            </div>

            {/* ── Health Score Card ──────────────────────── */}
            <Link to="/dashboard" className="block card overflow-hidden border-0 shadow-lg" style={{ boxShadow: '0 6px 24px rgba(15,118,110,0.12)' }}>
                <div className="gradient-teal p-5">
                    <div className="flex items-center justify-between mb-4">
                        <span className="bg-white/20 rounded-lg px-2.5 py-1 text-[10px] font-extrabold text-white tracking-widest">HEALTH SCORE</span>
                        {healthScore !== null && <span className="text-xl text-white/70 font-bold">›</span>}
                    </div>
                    {healthScore !== null && healthCategory ? (
                        <>
                            <div className="flex items-center justify-between mb-4">
                                <span className="text-5xl font-extrabold text-white tracking-tighter">{healthScore}</span>
                                <div className="flex items-center gap-1.5 bg-white/15 rounded-xl px-3 py-2">
                                    <span className="text-lg">{healthCategory.emoji}</span>
                                    <span className="text-sm font-bold text-white">{healthCategory.label}</span>
                                </div>
                            </div>
                            <div className="h-1.5 bg-white/20 rounded-full overflow-hidden mb-2">
                                <div className="h-full bg-white/90 rounded-full transition-all" style={{ width: `${healthScore}%` }} />
                            </div>
                            <div className="flex justify-between text-[10px] font-semibold text-white/50">
                                <span>0</span>
                                <span>100</span>
                            </div>
                        </>
                    ) : (
                        <div className="text-center py-3">
                            <span className="text-3xl block mb-2 text-white/60">⚛</span>
                            <p className="text-sm text-white/70">Submit lab results to unlock your personalized health score</p>
                        </div>
                    )}
                </div>
            </Link>

            {/* ── Today's Trackers ───────────────────────── */}
            {trackerChips.length > 0 && (
                <div className="card p-4">
                    <p className="text-sm font-bold text-neutral-900 mb-3">Today's Health</p>
                    <div className="flex gap-3">
                        {trackerChips.map((chip, i) => (
                            <div key={i} className="flex-1 bg-neutral-50 rounded-xl p-3 text-center border border-neutral-100">
                                <p className="text-sm text-neutral-500 mb-1">{chip.icon}</p>
                                <p className="text-[10px] font-bold text-neutral-400 uppercase mb-1">{chip.label}</p>
                                <p className="text-sm font-bold" style={{ color: chip.color }}>{chip.value}</p>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* ── Profile Card ───────────────────────────── */}
            <Link to="/onboarding" className="card p-4 flex items-center gap-3 hover:shadow-md transition-all">
                <div className="w-11 h-11 rounded-lg bg-indigo-50 flex items-center justify-center text-xl text-indigo-600">
                    ◉
                </div>
                <div className="flex-1">
                    <p className="text-sm font-bold text-neutral-900">
                        {profileComplete ? 'Health Profile' : 'Complete Your Profile'}
                    </p>
                    <p className="text-xs text-neutral-500 mt-0.5 line-clamp-1">{profileSubtitle}</p>
                </div>
                <div className="flex items-center gap-2">
                    <span className={`w-2 h-2 rounded-full ${profileComplete ? 'bg-success-500' : 'bg-warning-500'}`} />
                    <span className="text-xl text-neutral-300 font-bold">›</span>
                </div>
            </Link>

            {/* ── Quick Actions Grid ─────────────────────── */}
            <div>
                <p className="text-base font-bold text-neutral-900 mb-3">Quick Actions</p>
                <div className="grid grid-cols-2 gap-3">
                    {featureCards.map((f) => (
                        <FeatureCard key={f.title} {...f} />
                    ))}
                </div>
            </div>

            {/* ── Recent Activity ────────────────────────── */}
            <div>
                <div className="flex items-center justify-between mb-3">
                    <p className="text-base font-bold text-neutral-900">Recent Activity</p>
                    {meta?.total > 0 && (
                        <Link to="/credits" className="text-sm font-semibold text-teal-700 hover:text-teal-800">
                            See all ({meta.total})
                        </Link>
                    )}
                </div>
                {isLoading ? (
                    <div className="card p-8 text-center">
                        <div className="skeleton h-4 w-3/4 mx-auto rounded mb-3" />
                        <div className="skeleton h-3 w-1/2 mx-auto rounded" />
                    </div>
                ) : submissions.length === 0 ? (
                    <div className="card p-8 text-center border-dashed">
                        <div className="w-16 h-16 rounded-2xl bg-teal-50 flex items-center justify-center text-2xl mx-auto mb-3">
                            ⚛
                        </div>
                        <p className="text-sm font-bold text-neutral-900 mb-1">No lab reports yet</p>
                        <p className="text-xs text-neutral-500 mb-4">Upload your first lab result to start tracking your health</p>
                        <Link to="/lab-results" className="btn btn-primary text-sm">
                            Upload Lab Report
                        </Link>
                    </div>
                ) : (
                    <div className="card overflow-hidden">
                        {submissions.map((s, index) => (
                            <Link
                                key={s.id}
                                to={`/lab-results/submission/${s.id}`}
                                className={`flex items-center gap-3 px-4 py-3 hover:bg-neutral-25 transition-colors ${
                                    index < submissions.length - 1 ? 'border-b border-neutral-100' : ''
                                }`}
                            >
                                <div className="w-9 h-9 rounded-lg bg-teal-50 flex items-center justify-center text-base text-teal-600">
                                    {s.submission_type === 'pdf' ? '⚛' : '▼'}
                                </div>
                                <div className="flex-1">
                                    <p className="text-sm font-semibold text-neutral-900">
                                        {s.submission_type === 'pdf' ? 'PDF Upload' : s.test_panel?.name || 'Lab Result'}
                                    </p>
                                    <p className="text-xs text-neutral-400 mt-0.5">
                                        {new Date(s.submitted_at || s.created_at).toLocaleDateString('en-US', {
                                            month: 'short', day: 'numeric', year: 'numeric',
                                        })}
                                        {' · '}{s.credits_used} credit{s.credits_used > 1 ? 's' : ''}
                                    </p>
                                </div>
                                <span className={`badge ${
                                    s.interpretation?.status === 'completed' ? 'badge-success' :
                                    s.interpretation?.status === 'failed' ? 'badge-danger' :
                                    'badge-pending'
                                }`}>
                                    <span className={`w-1.5 h-1.5 rounded-full ${
                                        s.interpretation?.status === 'completed' ? 'bg-success-500' :
                                        s.interpretation?.status === 'failed' ? 'bg-danger-500' :
                                        'bg-warning-500'
                                    }`} />
                                    {s.interpretation?.status === 'completed' ? 'Done' :
                                     s.interpretation?.status === 'failed' ? 'Failed' : 'Pending'}
                                </span>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}