import React from 'react';
import { useParams, Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';

const StatusBadge = ({ status }) => {
    const map = {
        completed: 'bg-green-50 text-green-700',
        success: 'bg-green-50 text-green-700',
        pending: 'bg-amber-50 text-amber-700',
        failed: 'bg-red-50 text-red-700',
        cancelled: 'bg-gray-50 text-gray-600',
    };
    return (
        <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${map[status] || 'bg-gray-50 text-gray-600'}`}>
            {status}
        </span>
    );
};

export default function AdminUserDetail() {
    const { id } = useParams();

    const { data, isLoading, error } = useQuery({
        queryKey: ['admin-user', id],
        queryFn: () => api.get(`/admin/users/${id}`),
    });

    if (isLoading) {
        return <div className="flex justify-center py-20"><div className="h-8 w-8 animate-spin rounded-full border-4 border-teal-500 border-t-transparent" /></div>;
    }

    if (error || !data?.data) {
        return (
            <div className="text-center py-20">
                <h2 className="text-xl font-bold text-gray-900">User Not Found</h2>
                <Link to="/admin/users" className="mt-4 inline-block text-teal-600 font-medium">← Back to users</Link>
            </div>
        );
    }

    const { user, activity } = data.data;

    return (
        <div className="space-y-6">
            <Link to="/admin/users" className="text-sm font-semibold text-neutral-400 hover:text-neutral-600">
                ← Back to users
            </Link>

            {/* ── Profile Card ── */}
            <div className="bg-white rounded-xl border border-gray-200 p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h2 className="text-2xl font-bold text-gray-900">{user.name}</h2>
                        <p className="text-sm text-gray-500 mt-1">{user.email}</p>
                        {user.phone && <p className="text-sm text-gray-500">{user.phone}</p>}
                        <div className="flex items-center gap-3 mt-3">
                            <span className="inline-flex gap-1">
                                {(user.roles || []).map((r) => (
                                    <span key={r} className="rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-medium text-teal-700">{r}</span>
                                ))}
                            </span>
                            {user.email_verified_at ? (
                                <span className="text-xs text-green-600 font-medium">✓ Verified</span>
                            ) : (
                                <span className="text-xs text-amber-600 font-medium">⚠ Unverified</span>
                            )}
                        </div>
                    </div>
                    <div className="text-right">
                        <span className="text-xs font-bold text-neutral-400 uppercase tracking-wider">Credits</span>
                        <p className="text-3xl font-extrabold text-teal-700">{user.credits ?? 0}</p>
                    </div>
                </div>

                {/* Health Profile */}
                {user.health_profile && (
                    <div className="mt-6 pt-6 border-t border-gray-100">
                        <h3 className="text-sm font-semibold text-gray-700 mb-3">Health Profile {user.health_profile.profile_completed ? '✓' : '(Incomplete)'}</h3>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                            {user.health_profile.date_of_birth && (
                                <div><span className="text-gray-400">DOB:</span> <span className="text-gray-700">{user.health_profile.date_of_birth}</span></div>
                            )}
                            {user.health_profile.sex && (
                                <div><span className="text-gray-400">Sex:</span> <span className="text-gray-700">{user.health_profile.sex}</span></div>
                            )}
                            {user.health_profile.height_cm && (
                                <div><span className="text-gray-400">Height:</span> <span className="text-gray-700">{user.health_profile.height_cm} cm</span></div>
                            )}
                            {user.health_profile.weight_kg && (
                                <div><span className="text-gray-400">Weight:</span> <span className="text-gray-700">{user.health_profile.weight_kg} kg</span></div>
                            )}
                            {user.health_profile.blood_type && (
                                <div><span className="text-gray-400">Blood:</span> <span className="text-gray-700">{user.health_profile.blood_type}</span></div>
                            )}
                            {user.health_profile.is_pregnant && (
                                <div><span className="text-gray-400">Pregnant:</span> <span className="text-gray-700">Yes</span></div>
                            )}
                        </div>
                        {user.health_profile.medical_conditions && (
                            <p className="mt-2 text-xs text-gray-500"><strong>Conditions:</strong> {user.health_profile.medical_conditions}</p>
                        )}
                        {user.health_profile.current_medications && (
                            <p className="text-xs text-gray-500"><strong>Medications:</strong> {user.health_profile.current_medications}</p>
                        )}
                    </div>
                )}

                <div className="mt-4 pt-4 border-t border-gray-100 text-xs text-gray-400">
                    Joined {new Date(user.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}
                </div>
            </div>

            {/* ── Lab Submissions ── */}
            <SectionCard title="Lab Submissions" count={user.submissions?.length}>
                {user.submissions?.length > 0 ? (
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left"><tr>
                            <th className="px-4 py-2 font-medium text-gray-500">Panel</th>
                            <th className="px-4 py-2 font-medium text-gray-500">Type</th>
                            <th className="px-4 py-2 font-medium text-gray-500">Status</th>
                            <th className="px-4 py-2 font-medium text-gray-500">Date</th>
                        </tr></thead>
                        <tbody className="divide-y divide-gray-100">
                            {user.submissions.map((s) => (
                                <tr key={s.id}><td className="px-4 py-2 text-gray-900">{s.panel_name || 'N/A'}</td><td className="px-4 py-2 text-gray-500">{s.type}</td><td className="px-4 py-2"><StatusBadge status={s.status} /></td><td className="px-4 py-2 text-gray-400 text-xs">{new Date(s.created_at).toLocaleDateString()}</td></tr>
                            ))}
                        </tbody>
                    </table>
                ) : <EmptyState text="No submissions yet" />}
            </SectionCard>

            {/* ── Interpretations ── */}
            <SectionCard title="AI Interpretations" count={activity.interpretations?.length}>
                {activity.interpretations?.length > 0 ? (
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left"><tr><th className="px-4 py-2 font-medium text-gray-500">Panel</th><th className="px-4 py-2 font-medium text-gray-500">Status</th><th className="px-4 py-2 font-medium text-gray-500">Date</th></tr></thead>
                        <tbody className="divide-y divide-gray-100">
                            {activity.interpretations.map((i) => (
                                <tr key={i.id}><td className="px-4 py-2 text-gray-900">{i.panel_name}</td><td className="px-4 py-2"><StatusBadge status={i.status} /></td><td className="px-4 py-2 text-gray-400 text-xs">{i.created_at ? new Date(i.created_at).toLocaleDateString() : '—'}</td></tr>
                            ))}
                        </tbody>
                    </table>
                ) : <EmptyState text="No interpretations yet" />}
            </SectionCard>

            {/* ── Credit Ledger ── */}
            <SectionCard title="Credit Transactions" count={activity.credit_ledger?.length}>
                {activity.credit_ledger?.length > 0 ? (
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left"><tr><th className="px-4 py-2 font-medium text-gray-500">Type</th><th className="px-4 py-2 font-medium text-gray-500">Amount</th><th className="px-4 py-2 font-medium text-gray-500">Description</th><th className="px-4 py-2 font-medium text-gray-500">Date</th></tr></thead>
                        <tbody className="divide-y divide-gray-100">
                            {activity.credit_ledger.map((e) => (
                                <tr key={e.id}><td className="px-4 py-2"><span className={`rounded-full px-2 py-0.5 text-xs font-medium ${e.type === 'credit' || e.type === 'admin_grant' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'}`}>{e.type}</span></td><td className="px-4 py-2 font-medium">{e.amount > 0 ? '+' : ''}{e.amount}</td><td className="px-4 py-2 text-gray-500 max-w-xs truncate">{e.description}</td><td className="px-4 py-2 text-gray-400 text-xs">{new Date(e.created_at).toLocaleDateString()}</td></tr>
                            ))}
                        </tbody>
                    </table>
                ) : <EmptyState text="No transactions yet" />}
            </SectionCard>

            {/* ── Payments ── */}
            <SectionCard title="Payments" count={activity.payments?.length}>
                {activity.payments?.length > 0 ? (
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left"><tr><th className="px-4 py-2 font-medium text-gray-500">Reference</th><th className="px-4 py-2 font-medium text-gray-500">Amount</th><th className="px-4 py-2 font-medium text-gray-500">Status</th><th className="px-4 py-2 font-medium text-gray-500">Gateway</th><th className="px-4 py-2 font-medium text-gray-500">Date</th></tr></thead>
                        <tbody className="divide-y divide-gray-100">
                            {activity.payments.map((p) => (
                                <tr key={p.id}><td className="px-4 py-2 text-gray-900 font-mono text-xs">{p.reference}</td><td className="px-4 py-2 font-medium">₦{p.amount_naira?.toLocaleString()}</td><td className="px-4 py-2"><StatusBadge status={p.status} /></td><td className="px-4 py-2 text-gray-500">{p.gateway}</td><td className="px-4 py-2 text-gray-400 text-xs">{p.created_at ? new Date(p.created_at).toLocaleDateString() : '—'}</td></tr>
                            ))}
                        </tbody>
                    </table>
                ) : <EmptyState text="No payments yet" />}
            </SectionCard>

            {/* ── Appointments ── */}
            <SectionCard title="Appointments" count={activity.appointments?.length}>
                {activity.appointments?.length > 0 ? (
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left"><tr><th className="px-4 py-2 font-medium text-gray-500">Title</th><th className="px-4 py-2 font-medium text-gray-500">Doctor</th><th className="px-4 py-2 font-medium text-gray-500">Date</th><th className="px-4 py-2 font-medium text-gray-500">Status</th></tr></thead>
                        <tbody className="divide-y divide-gray-100">
                            {activity.appointments.map((a) => (
                                <tr key={a.id}><td className="px-4 py-2 text-gray-900">{a.title}</td><td className="px-4 py-2 text-gray-500">{a.doctor_name || a.facility_name || '—'}</td><td className="px-4 py-2 text-gray-500">{a.appointment_date}</td><td className="px-4 py-2"><StatusBadge status={a.status} /></td></tr>
                            ))}
                        </tbody>
                    </table>
                ) : <EmptyState text="No appointments" />}
            </SectionCard>

            {/* ── Feedback ── */}
            <SectionCard title="Feedback" count={activity.feedback?.length}>
                {activity.feedback?.length > 0 ? (
                    <div className="space-y-3">
                        {activity.feedback.map((f) => (
                            <div key={f.id} className="p-3 bg-gray-50 rounded-lg">
                                <p className="text-sm text-gray-700">{f.content}</p>
                                <div className="mt-1 flex items-center gap-3 text-xs text-gray-400">
                                    {f.rating && <span>Rating: {f.rating}/5</span>}
                                    <StatusBadge status={f.status} />
                                    <span>{new Date(f.created_at).toLocaleDateString()}</span>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : <EmptyState text="No feedback submitted" />}
            </SectionCard>

            {/* ── Health Metrics ── */}
            <SectionCard title="Health Trackers" count={activity.health_metrics?.length}>
                {activity.health_metrics?.length > 0 ? (
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left"><tr><th className="px-4 py-2 font-medium text-gray-500">Tracker</th><th className="px-4 py-2 font-medium text-gray-500">Value</th><th className="px-4 py-2 font-medium text-gray-500">Date</th></tr></thead>
                        <tbody className="divide-y divide-gray-100">
                            {activity.health_metrics.map((m) => (
                                <tr key={m.id}><td className="px-4 py-2 text-gray-900">{m.tracker_label || m.tracker_type}</td><td className="px-4 py-2 font-medium">{m.value} {m.unit || ''}</td><td className="px-4 py-2 text-gray-400 text-xs">{m.created_at ? new Date(m.created_at).toLocaleDateString() : '—'}</td></tr>
                            ))}
                        </tbody>
                    </table>
                ) : <EmptyState text="No tracker data" />}
            </SectionCard>

            {/* ── Admin Audit Logs ── */}
            <SectionCard title="Admin Actions" count={activity.audit_logs?.length}>
                {activity.audit_logs?.length > 0 ? (
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left"><tr><th className="px-4 py-2 font-medium text-gray-500">Action</th><th className="px-4 py-2 font-medium text-gray-500">Admin</th><th className="px-4 py-2 font-medium text-gray-500">Details</th><th className="px-4 py-2 font-medium text-gray-500">Date</th></tr></thead>
                        <tbody className="divide-y divide-gray-100">
                            {activity.audit_logs.map((l) => (
                                <tr key={l.id}><td className="px-4 py-2"><span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">{l.action}</span></td><td className="px-4 py-2 text-gray-500">{l.admin_name}</td><td className="px-4 py-2 text-gray-500 text-xs max-w-xs truncate">{JSON.stringify(l.metadata)}</td><td className="px-4 py-2 text-gray-400 text-xs">{new Date(l.created_at).toLocaleDateString()}</td></tr>
                            ))}
                        </tbody>
                    </table>
                ) : <EmptyState text="No admin actions" />}
            </SectionCard>

            {/* ── Referrals ── */}
            <SectionCard title="Referrals" count={activity.referrals?.length}>
                {activity.referrals?.length > 0 ? (
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left"><tr><th className="px-4 py-2 font-medium text-gray-500">Event</th><th className="px-4 py-2 font-medium text-gray-500">Date</th></tr></thead>
                        <tbody className="divide-y divide-gray-100">
                            {activity.referrals.map((r) => (
                                <tr key={r.id}><td className="px-4 py-2 text-gray-900">{r.event_type}</td><td className="px-4 py-2 text-gray-400 text-xs">{r.created_at ? new Date(r.created_at).toLocaleDateString() : '—'}</td></tr>
                            ))}
                        </tbody>
                    </table>
                ) : <EmptyState text="No referral activity" />}
            </SectionCard>
        </div>
    );
}

function SectionCard({ title, count, children }) {
    return (
        <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 className="font-semibold text-gray-900">{title}</h3>
                {count > 0 && <span className="text-xs text-gray-400">{count} entries</span>}
            </div>
            <div className="overflow-x-auto">{children}</div>
        </div>
    );
}

function EmptyState({ text }) {
    return <div className="px-6 py-6 text-center text-sm text-gray-400">{text}</div>;
}