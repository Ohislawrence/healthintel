import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';

export default function AdminUsers() {
    const [page, setPage] = useState(1);
    const [selectedUser, setSelectedUser] = useState(null);
    const [creditAmount, setCreditAmount] = useState(5);
    const [showModal, setShowModal] = useState(false);
    const queryClient = useQueryClient();

    const { data, isLoading } = useQuery({
        queryKey: ['admin-users', page],
        queryFn: () => api.get('/admin/users', { params: { page } }),
    });

    const grantMutation = useMutation({
        mutationFn: ({ id, credits }) => api.post(`/admin/users/${id}/credit`, { credits }),
        onSuccess: (res) => {
            queryClient.invalidateQueries({ queryKey: ['admin-users'] });
            setShowModal(false);
            setSelectedUser(null);
            alert(`${res?.message || `Granted ${creditAmount} credits!`}\nNew balance: ${res?.data?.new_balance}`);
        },
        onError: (err) => alert(err?.message || 'Failed to grant credits'),
    });

    const users = data?.data || [];
    const pagination = data?.meta || {};

    const openModal = (user) => {
        setSelectedUser(user);
        setCreditAmount(5);
        setShowModal(true);
    };

    const handleGrant = () => {
        if (!selectedUser) return;
        grantMutation.mutate({ id: selectedUser.id, credits: creditAmount });
    };

    return (
        <div className="space-y-6">
            <h2 className="text-xl font-semibold text-gray-900">Users</h2>

            {isLoading ? (
                <div className="h-20 animate-pulse rounded-xl bg-gray-100" />
            ) : (
                <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium text-gray-500">Name</th>
                                <th className="px-4 py-3 font-medium text-gray-500">Email</th>
                                <th className="px-4 py-3 font-medium text-gray-500">Roles</th>
                                <th className="px-4 py-3 font-medium text-gray-500">Credits</th>
                                <th className="px-4 py-3 font-medium text-gray-500">Joined</th>
                                <th className="px-4 py-3 font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {users.map((u) => (
                                <tr key={u.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3 font-medium text-gray-900">{u.name}</td>
                                    <td className="px-4 py-3 text-gray-500">{u.email}</td>
                                    <td className="px-4 py-3">
                                        <span className="inline-flex gap-1">
                                            {(u.roles || []).map((r) => (
                                                <span key={r.name} className="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium uppercase">{r.name}</span>
                                            ))}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 font-medium text-teal-600">{u.credits ?? 0}</td>
                                    <td className="px-4 py-3 text-gray-400">{new Date(u.created_at).toLocaleDateString()}</td>
                                    <td className="px-4 py-3">
                                        <button
                                            onClick={() => openModal(u)}
                                            className="rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-teal-700 transition-colors"
                                        >
                                            Grant Credits
                                        </button>
                                    </td>
                                </tr>
                            ))}
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

            {/* Grant Credits Modal */}
            {showModal && selectedUser && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40" onClick={() => setShowModal(false)}>
                    <div className="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl" onClick={(e) => e.stopPropagation()}>
                        <h3 className="text-lg font-semibold text-gray-900">
                            Grant Credits to {selectedUser.name}
                        </h3>
                        <p className="mt-1 text-sm text-gray-500">
                            Current balance: <span className="font-medium text-teal-600">{selectedUser.credits ?? 0} credits</span>
                        </p>

                        <div className="mt-4">
                            <label className="block text-sm font-medium text-gray-700">Credits to add</label>
                            <input
                                type="number"
                                min={1}
                                max={1000}
                                value={creditAmount}
                                onChange={(e) => setCreditAmount(parseInt(e.target.value) || 1)}
                                className="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-200 outline-none"
                            />
                            <p className="mt-1 text-xs text-gray-400">1 – 1,000 credits per grant</p>
                        </div>

                        <div className="mt-6 flex gap-3">
                            <button
                                onClick={() => setShowModal(false)}
                                className="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={handleGrant}
                                disabled={grantMutation.isPending}
                                className="flex-1 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50 transition-colors"
                            >
                                {grantMutation.isPending ? 'Granting...' : `Grant ${creditAmount} Credits`}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}