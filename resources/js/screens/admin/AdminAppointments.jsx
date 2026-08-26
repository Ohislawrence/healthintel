import React, { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../lib/api';

const STATUS_STYLES = {
    pending: 'bg-amber-100 text-amber-700',
    confirmed: 'bg-green-100 text-green-700',
    declined: 'bg-red-100 text-red-700',
    upcoming: 'bg-blue-100 text-blue-700',
    completed: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
};

export default function AdminAppointments() {
    const [page, setPage] = useState(1);
    const [statusFilter, setStatusFilter] = useState('');

    const { data, isLoading, refetch } = useQuery({
        queryKey: ['admin-appointments', page, statusFilter],
        queryFn: () => api.get('/admin/appointments', { params: { page, status: statusFilter || undefined } }),
    });

    const updateMutation = useMutation({
        mutationFn: ({ id, payload }) => api.put(`/admin/appointments/${id}`, payload),
        onSuccess: () => refetch(),
    });

    const decisionMutation = useMutation({
        mutationFn: ({ id, decision }) => api.post(`/admin/appointments/${id}/decision`, { decision }),
        onSuccess: () => refetch(),
    });

    const appointments = data?.data || [];
    const pagination = data?.meta || {};

    const toggleStatus = (appt) => {
        const next = appt.status === 'completed' ? 'upcoming' : appt.status === 'cancelled' ? 'upcoming' : 'completed';
        updateMutation.mutate({ id: appt.id, payload: { status: next } });
    };

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h2 className="text-xl font-semibold text-gray-900">Appointments</h2>
                <select
                    value={statusFilter}
                    onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }}
                    className="rounded border border-gray-300 px-3 py-1.5 text-sm"
                >
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="declined">Declined</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="upcoming">Upcoming</option>
                </select>
            </div>

            {isLoading ? (
                <div className="h-20 animate-pulse rounded-xl bg-gray-100" />
            ) : (
                <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left">
                            <tr>
                                <th className="px-3 py-3 font-medium text-gray-500">User</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Title</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Provider</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Date</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Status</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Credits</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {appointments.map((a) => (
                                <tr key={a.id} className="hover:bg-gray-50">
                                    <td className="px-3 py-2 font-medium text-gray-900">{a.user?.name || '—'}</td>
                                    <td className="px-3 py-2">{a.title}</td>
                                    <td className="px-3 py-2 text-gray-500">{a.provider?.name || '—'}</td>
                                    <td className="px-3 py-2 text-gray-500">
                                        {a.appointment_date ? `${new Date(a.appointment_date).toLocaleDateString()}${a.appointment_time ? ` ${a.appointment_time}` : ''}` : '—'}
                                    </td>
                                    <td className="px-3 py-2">
                                        <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${STATUS_STYLES[a.status] || STATUS_STYLES.upcoming}`}>{a.status || 'upcoming'}</span>
                                    </td>
                                    <td className="px-3 py-2 text-gray-500">
                                        {a.credits_charged > 0 ? (
                                            <span>{a.credits_charged}{a.refunded_at ? ' ↺ refunded' : ''}</span>
                                        ) : '—'}
                                    </td>
                                    <td className="px-3 py-2 space-x-2 whitespace-nowrap">
                                        {a.status === 'pending' && (
                                            <>
                                                <button
                                                    onClick={() => decisionMutation.mutate({ id: a.id, decision: 'confirm' })}
                                                    disabled={decisionMutation.isPending}
                                                    className="text-xs text-green-600 font-semibold hover:underline"
                                                >
                                                    Confirm
                                                </button>
                                                <button
                                                    onClick={() => decisionMutation.mutate({ id: a.id, decision: 'decline' })}
                                                    disabled={decisionMutation.isPending}
                                                    className="text-xs text-red-500 font-semibold hover:underline"
                                                >
                                                    Decline
                                                </button>
                                            </>
                                        )}
                                        {(a.status === 'upcoming' || a.status === 'confirmed' || a.status === 'completed' || a.status === 'cancelled') && (
                                            <button onClick={() => toggleStatus(a)} className="text-xs text-teal-600 hover:underline">
                                                {a.status === 'completed' || a.status === 'cancelled' ? 'Mark upcoming' : 'Mark completed'}
                                            </button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                            {appointments.length === 0 && (
                                <tr><td colSpan={7} className="px-3 py-8 text-center text-gray-400">No appointments found.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            )}

            {pagination?.last_page > 1 && (
                <div className="flex justify-center gap-2">
                    {Array.from({ length: pagination.last_page }, (_, i) => i + 1).map((p) => (
                        <button key={p} onClick={() => setPage(p)} className={`rounded px-3 py-1 text-xs ${page === p ? 'bg-teal-600 text-white' : 'bg-white border text-gray-600'}`}>{p}</button>
                    ))}
                </div>
            )}
        </div>
    );
}