import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';

export default function AdminAnalytics() {
    const { data, isLoading } = useQuery({
        queryKey: ['admin-analytics'],
        queryFn: () => api.get('/admin/analytics'),
    });

    const a = data?.data || {};

    if (isLoading) {
        return <div className="space-y-4">{Array.from({length:4}).map((_,i) => <div key={i} className="h-40 animate-pulse rounded-xl bg-gray-100" />)}</div>;
    }

    const ChartBar = ({ label, value, max, color = 'bg-teal-500', format = v => v, height = 4 }) => (
        <div className="flex items-center gap-2">
            <span className="w-20 text-[10px] text-gray-500 truncate">{label}</span>
            <div className={`flex-1 h-${height} bg-gray-100 rounded-full overflow-hidden`}>
                <div className={`h-full rounded-full transition-all ${color}`} style={{ width: `${max > 0 ? (value / max) * 100 : 0}%` }} />
            </div>
            <span className="w-12 text-[10px] font-semibold text-gray-700 text-right">{format(value)}</span>
        </div>
    );

    // Time-series maxes
    const maxSignups = Math.max(...(a.new_users_per_day || []).map(d => d.count), 1);
    const maxSubs = Math.max(...(a.submissions_per_day || []).map(d => d.count), 1);
    const maxRefs = Math.max(...(a.referrals_per_day || []).map(d => d.count), 1);
    const maxRev = Math.max(...(a.revenue_per_day || []).map(d => d.total), 1);

    const users = a.users || {};
    const revenue = a.revenue || {};
    const credits = a.credits || {};
    const engagement = a.engagement || {};
    const content = a.content || {};
    const health = a.health_distribution || [];

    const healthMax = Math.max(...health.map(d => d.count), 1);
    const healthColors = ['bg-blue-400', 'bg-green-500', 'bg-amber-500', 'bg-red-500'];

    const panelMax = Math.max(...(content.panel_usage || []).map(d => d.total), 1);

    return (
        <div className="space-y-6">
            <h2 className="text-xl font-semibold text-gray-900">Analytics — 360° View</h2>

            {/* ═══════════════════════════════════════════════ */}
            {/* SECTION 1: USER KPIs                         */}
            {/* ═══════════════════════════════════════════════ */}
            <div className="rounded-xl border border-gray-200 bg-white p-5">
                <h3 className="font-semibold text-gray-900 mb-4">👥 Users</h3>
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <KPI label="Total" value={users.total} />
                    <KPI label="New (7d)" value={users.new_7d} color="text-blue-600" />
                    <KPI label="New (30d)" value={users.new_30d} color="text-blue-600" />
                    <KPI label="Active (30d)" value={users.active_30d} color="text-teal-600" />
                    <KPI label="Profile Done" value={users.profile_completed} color="text-emerald-600" />
                    <KPI label="Conversion" value={`${revenue.conversion_rate || 0}%`} color="text-purple-600" />
                </div>
                {(users.role_breakdown || []).length > 0 && (
                    <div className="mt-3 flex gap-3 text-xs text-gray-500">
                        {users.role_breakdown.map(r => (
                            <span key={r.name} className="bg-gray-100 rounded-full px-2 py-0.5">
                                {r.name}: <strong>{r.count}</strong>
                            </span>
                        ))}
                    </div>
                )}
            </div>

            {/* ═══════════════════════════════════════════════ */}
            {/* SECTION 2: REVENUE KPIs                      */}
            {/* ═══════════════════════════════════════════════ */}
            <div className="rounded-xl border border-gray-200 bg-white p-5">
                <h3 className="font-semibold text-gray-900 mb-4">💰 Revenue</h3>
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <KPI label="Total Revenue" value={`₦${(revenue.total || 0).toLocaleString()}`} />
                    <KPI label="Revenue (7d)" value={`₦${(revenue['7d'] || 0).toLocaleString()}`} color="text-green-600" />
                    <KPI label="Revenue (30d)" value={`₦${(revenue['30d'] || 0).toLocaleString()}`} color="text-green-600" />
                    <KPI label="Transactions" value={revenue.total_transactions} />
                    <KPI label="ARPU (30d)" value={`₦${(revenue.arpu || 0).toLocaleString()}`} color="text-indigo-600" />
                    <KPI label="Conv. Rate" value={`${revenue.conversion_rate || 0}%`} color="text-purple-600" />
                </div>
                {(revenue.payment_methods || []).length > 0 && (
                    <div className="mt-3 flex gap-3 flex-wrap text-xs text-gray-500">
                        {revenue.payment_methods.map(pm => (
                            <span key={pm.method} className="bg-gray-100 rounded-full px-2 py-0.5">
                                {pm.method}: <strong>{pm.count}</strong> · ₦{pm.total.toLocaleString()}
                            </span>
                        ))}
                    </div>
                )}
            </div>

            {/* ═══════════════════════════════════════════════ */}
            {/* SECTION 3: CREDIT ECONOMY                    */}
            {/* ═══════════════════════════════════════════════ */}
            <div className="rounded-xl border border-gray-200 bg-white p-5">
                <h3 className="font-semibold text-gray-900 mb-4">💳 Credit Economy (30d)</h3>
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    <KPI label="Credits Sold" value={credits.sold_30d || 0} color="text-teal-600" />
                    <KPI label="Credits Used" value={credits.used_30d || 0} color="text-orange-600" />
                    <KPI label="Net Change" value={(credits.sold_30d || 0) - (credits.used_30d || 0)} color={(credits.sold_30d || 0) >= (credits.used_30d || 0) ? 'text-green-600' : 'text-red-600'} />
                    <KPI label="Avg / User" value={credits.avg_per_user || 0} />
                </div>
            </div>

            {/* ═══════════════════════════════════════════════ */}
            {/* SECTION 4: ENGAGEMENT                        */}
            {/* ═══════════════════════════════════════════════ */}
            <div className="rounded-xl border border-gray-200 bg-white p-5">
                <h3 className="font-semibold text-gray-900 mb-4">📱 Engagement</h3>
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-8 gap-3">
                    <KPI label="BMI Calc" value={engagement.bmi_count || 0} />
                    <KPI label="BMR Calc" value={engagement.bmr_count || 0} />
                    <KPI label="WHR Calc" value={engagement.whr_count || 0} />
                    <KPI label="Due Dates" value={engagement.due_date_count || 0} />
                    <KPI label="Appointments" value={engagement.appointments || 0} />
                    <KPI label="AI Checks (30d)" value={engagement.symptom_checks_30d || 0} color="text-purple-600" />
                    <KPI label="Feedback" value={(engagement.feedback_by_status || []).reduce((a,b) => a + b.count, 0)} />
                </div>
            </div>

            {/* ═══════════════════════════════════════════════ */}
            {/* SECTION 5: TIME-SERIES CHARTS                */}
            {/* ═══════════════════════════════════════════════ */}
            <div className="grid gap-6 lg:grid-cols-2">
                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 className="font-semibold text-gray-900 mb-4">📈 New Users / Day (30d)</h3>
                    <div className="space-y-1 max-h-64 overflow-y-auto">
                        {(a.new_users_per_day || []).map(d => <ChartBar key={d.date} label={d.date} value={d.count} max={maxSignups} color="bg-blue-500" />)}
                    </div>
                </div>
                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 className="font-semibold text-gray-900 mb-4">🧪 Submissions / Day (30d)</h3>
                    <div className="space-y-1 max-h-64 overflow-y-auto">
                        {(a.submissions_per_day || []).map(d => <ChartBar key={d.date} label={d.date} value={d.count} max={maxSubs} color="bg-teal-500" />)}
                    </div>
                </div>
            </div>
            <div className="grid gap-6 lg:grid-cols-2">
                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 className="font-semibold text-gray-900 mb-4">₦ Revenue / Day (30d)</h3>
                    <div className="space-y-1 max-h-64 overflow-y-auto">
                        {(a.revenue_per_day || []).map(d => <ChartBar key={d.date} label={d.date} value={d.total} max={maxRev} color="bg-green-500" format={v => `₦${Number(v).toLocaleString()}`} />)}
                    </div>
                </div>
                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 className="font-semibold text-gray-900 mb-4">🔗 Referral Clicks / Day (30d)</h3>
                    <div className="space-y-1 max-h-64 overflow-y-auto">
                        {(a.referrals_per_day || []).map(d => <ChartBar key={d.date} label={d.date} value={d.count} max={maxRefs} color="bg-amber-500" />)}
                    </div>
                </div>
            </div>

            {/* ═══════════════════════════════════════════════ */}
            {/* SECTION 6: CONTENT BREAKDOWN                 */}
            {/* ═══════════════════════════════════════════════ */}
            <div className="grid gap-6 lg:grid-cols-2">
                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 className="font-semibold text-gray-900 mb-4">🧪 Top Panels Used</h3>
                    <div className="space-y-2">
                        {(content.panel_usage || []).map(p => <ChartBar key={p.panel_name} label={p.panel_name} value={p.total} max={panelMax} color="bg-teal-500" />)}
                        {!content.panel_usage?.length && <p className="text-sm text-gray-400">No data</p>}
                    </div>
                </div>
                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 className="font-semibold text-gray-900 mb-4">🏥 Providers by Type</h3>
                    <div className="space-y-2">
                        {(content.providers_by_type || []).map(p => (
                            <div key={p.type} className="flex justify-between text-sm">
                                <span className="text-gray-600 capitalize">{p.type}</span>
                                <span className="font-semibold text-gray-900">{p.count}</span>
                            </div>
                        ))}
                    </div>
                    <div className="mt-3 flex gap-3 flex-wrap text-xs text-gray-500">
                        <span>Panels: <strong>{content.total_panels || 0}</strong></span>
                        <span>Symptoms: <strong>{content.total_symptoms || 0}</strong></span>
                        <span>Mappings: <strong>{content.total_symptom_mappings || 0}</strong></span>
                    </div>
                </div>
            </div>

            {/* Top Symptoms Checked */}
            {(content.top_symptoms || []).length > 0 && (
                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 className="font-semibold text-gray-900 mb-4">🩺 Top Symptoms Checked (30d)</h3>
                    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2">
                        {(content.top_symptoms || []).map(s => (
                            <div key={s.name} className="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm">
                                <span className="text-gray-700 truncate">{s.name}</span>
                                <span className="font-semibold text-teal-600 ml-2">{s.count}</span>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* ═══════════════════════════════════════════════ */}
            {/* SECTION 7: HEALTH DISTRIBUTION               */}
            {/* ═══════════════════════════════════════════════ */}
            {health.length > 0 && healthMax > 0 && (
                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 className="font-semibold text-gray-900 mb-4">❤️ BMI Distribution (users with lab data)</h3>
                    <div className="grid grid-cols-4 gap-3">
                        {health.map((h, i) => (
                            <div key={h.category} className="text-center">
                                <div className="text-2xl font-bold" style={{color: healthColors[i].replace('bg-', '#').replace('-400','').replace('-500','')}}>
                                    {h.count}
                                </div>
                                <div className="text-xs text-gray-500">{h.category}</div>
                                <div className="mt-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div className={`h-full rounded-full ${healthColors[i]}`} style={{width: `${(h.count / healthMax) * 100}%`}} />
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}

function KPI({ label, value, color = 'text-gray-900' }) {
    return (
        <div className="bg-gray-50 rounded-xl p-3">
            <p className="text-[10px] text-gray-500 uppercase tracking-wide">{label}</p>
            <p className={`mt-1 text-lg font-bold ${color}`}>{value ?? '—'}</p>
        </div>
    );
}