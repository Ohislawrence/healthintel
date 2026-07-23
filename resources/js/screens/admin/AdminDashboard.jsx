import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../../lib/api';

export default function AdminDashboard() {
    const { data, isLoading } = useQuery({
        queryKey: ['admin-dashboard'],
        queryFn: () => api.get('/admin/dashboard'),
    });

    const stats = data?.data?.stats || {};
    const recentInterps = data?.data?.recent_interpretations || [];
    const recentPayments = data?.data?.recent_payments || [];
    const topReferrals = data?.data?.top_referral_providers || [];
    const charts = data?.data?.charts || {};

    if (isLoading) {
        return <div className="grid grid-cols-4 gap-4">{Array.from({ length: 5 }).map((_, i) => <div key={i} className="h-24 animate-pulse rounded-xl bg-gray-100" />)}</div>;
    }

    const ChartBar = ({ label, value, max, color = 'bg-teal-500', format = v => v }) => (
        <div className="flex items-center gap-2">
            <span className="w-20 text-[10px] text-gray-500 truncate">{label}</span>
            <div className="flex-1 h-4 bg-gray-100 rounded-full overflow-hidden">
                <div className={`h-full rounded-full transition-all ${color}`} style={{ width: `${max > 0 ? (value / max) * 100 : 0}%` }} />
            </div>
            <span className="w-12 text-[10px] font-semibold text-gray-700 text-right">{format(value)}</span>
        </div>
    );

    const signupsData = charts.signups_per_day || [];
    const revenueData = charts.revenue_per_day || [];
    const submissionsData = charts.submissions_per_day || [];

    const maxSignups = Math.max(...signupsData.map(d => d.count), 1);
    const maxRevenue = Math.max(...revenueData.map(d => d.total), 1);
    const maxSubmissions = Math.max(...submissionsData.map(d => d.count), 1);

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h2 className="text-xl font-semibold text-gray-900">Dashboard</h2>
                <Link to="/admin/audit-log" className="text-xs text-teal-600 hover:underline">Audit Log →</Link>
            </div>

            {/* Stats Grid */}
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
                <StatCard label="Users" value={stats.total_users} icon="👥" />
                <StatCard label="Interpretations" value={stats.total_interpretations} icon="🧪" />
                <StatCard label="Payments" value={stats.total_payments} icon="💳" />
                <StatCard label="Referrals" value={stats.total_referrals} icon="🔗" />
                <StatCard label="Revenue" value={`₦${(stats.total_revenue || 0).toLocaleString()}`} icon="💰" />
                <StatCard label="Providers" value={stats.total_providers} icon="🏥" />
                <StatCard label="Appointments" value={stats.total_appointments} icon="📅" />
                <StatCard label="Feedback" value={stats.total_feedback} icon="💬" />
            </div>

            {/* Charts Row */}
            <div className="grid gap-6 lg:grid-cols-3">
                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 className="font-semibold text-gray-900 mb-4">New Users (14d)</h3>
                    <div className="space-y-1.5 max-h-60 overflow-y-auto">
                        {signupsData.map(d => <ChartBar key={d.date} label={d.date} value={d.count} max={maxSignups} color="bg-blue-500" />)}
                    </div>
                    {signupsData.length === 0 && <p className="text-sm text-gray-400">No data</p>}
                </div>

                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 className="font-semibold text-gray-900 mb-4">Revenue / Day (₦)</h3>
                    <div className="space-y-1.5 max-h-60 overflow-y-auto">
                        {revenueData.map(d => <ChartBar key={d.date} label={d.date} value={d.total} max={maxRevenue} color="bg-green-500" format={v => `₦${Number(v).toLocaleString()}`} />)}
                    </div>
                    {revenueData.length === 0 && <p className="text-sm text-gray-400">No data</p>}
                </div>

                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 className="font-semibold text-gray-900 mb-4">Submissions / Day</h3>
                    <div className="space-y-1.5 max-h-60 overflow-y-auto">
                        {submissionsData.map(d => <ChartBar key={d.date} label={d.date} value={d.count} max={maxSubmissions} color="bg-teal-500" />)}
                    </div>
                    {submissionsData.length === 0 && <p className="text-sm text-gray-400">No data</p>}
                </div>
            </div>

            {/* Recent activity */}
            <div className="grid gap-6 lg:grid-cols-2">
                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 className="font-semibold text-gray-900 mb-3">Recent Interpretations</h3>
                    {recentInterps.length === 0 ? (
                        <p className="text-sm text-gray-400">No interpretations yet.</p>
                    ) : (
                        <div className="divide-y divide-gray-100">
                            {recentInterps.map((r) => (
                                <div key={r.id} className="py-2 flex justify-between items-center text-sm">
                                    <span className="text-gray-700">{r.user_name}</span>
                                    <span className="text-gray-400 text-xs">{r.panel_name}</span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 className="font-semibold text-gray-900 mb-3">Recent Payments</h3>
                    {recentPayments.length === 0 ? (
                        <p className="text-sm text-gray-400">No payments yet.</p>
                    ) : (
                        <div className="divide-y divide-gray-100">
                            {recentPayments.map((p) => (
                                <div key={p.id} className="py-2 flex justify-between text-sm">
                                    <span className="text-gray-700">{p.user_name}</span>
                                    <span className="text-green-600 font-medium">{p.amount}</span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {/* Top referral providers */}
            {topReferrals.length > 0 && (
                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 className="font-semibold text-gray-900 mb-3">Top Referral Providers</h3>
                    <div className="grid gap-2 sm:grid-cols-5">
                        {topReferrals.map((r) => (
                            <div key={r.provider_name} className="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 text-sm">
                                <span className="text-gray-700 truncate">{r.provider_name}</span>
                                <span className="font-semibold text-teal-600 ml-2">{r.total}</span>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}

function StatCard({ label, value, icon }) {
    return (
        <div className="rounded-xl border border-gray-200 bg-white p-4 hover:shadow-md transition-shadow">
            <p className="text-[10px] text-gray-500 uppercase tracking-wide">{label}</p>
            <div className="mt-2 flex items-end gap-2">
                <span className="text-base">{icon}</span>
                <span className="text-xl font-bold text-gray-900">{value}</span>
            </div>
        </div>
    );
}