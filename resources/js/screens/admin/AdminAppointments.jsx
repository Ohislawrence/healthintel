import React, { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../lib/api';

export default function AdminAppointments() {
    const [page, setPage] = useState(1);

    const { data, isLoading, refetch } = useQuery({
        queryKey: ['admin-appointments', page],
        queryFn: () => api.get('/admin/appointments', { params: { page } }),
    });

    const updateMutation = useMutation({
        mutationFn: ({ id, payload }) => api.put(`/admin/appointments/${id}`, payload),
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
            <h2 className="text-xl font-semibold text-gray-900">Appointments</h2>

            {isLoading ? (
                <div className="h-20 animate-pulse rounded-xl bg-gray-100" />
            ) : (
                <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left">
                            <tr>
                                <th className="px-3 py-3 font-medium text-gray-500">User</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Title</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Doctor / Facility</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Date</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Status</th>
                                <th className="px-3 py-3 font-medium text-gray-500">Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {appointments.map((a) => (
                                <tr key={a.id} className="hover:bg-gray-50">
                                    <td className="px-3 py-2 font-medium text-gray-900">{a.user?.name || '—'}</td>
                                    <td className="px-3 py-2">{a.title}</td>
                                    <td className="px-3 py-2 text-gray-500">{a.doctor_name || '—'} {a.facility_name ? `· ${a.facility_name}` : ''}</td>
                                    <td className="px-3 py-2 text-gray-500">{a.appointment_date ? new Date(a.appointment_date).toLocaleDateString() : '—'}</td>
                                    <td className="px-3 py-2">
                                        <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                                            a.status === 'completed' ? 'bg-green-100 text-green-700' :
                                            a.status === 'cancelled' ? 'bg-red-100 text-red-700' :
                                            'bg-blue-100 text-blue-700'
                                        }`}>{a.status || 'upcoming'}</span>
                                    </td>
                                    <td className="px-3 py-2">
                                        <button onClick={() => toggleStatus(a)} className="text-xs text-teal-600 hover:underline">
                                            {a.status === 'completed' ? 'Mark upcoming' : a.status === 'cancelled' ? 'Mark upcoming' : 'Mark completed'}
                                        </button>
                                    </td>
                                </tr>
                            ))}
                            {appointments.length === 0 && (
                                <tr><td colSpan={6} className="px-3 py-8 text-center text-gray-400">No appointments found.</td></tr>
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