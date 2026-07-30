import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../../lib/api';

export default function AdminUsers() {
    const [page, setPage] = useState(1);
    const [trashPage, setTrashPage] = useState(1);
    const [tab, setTab] = useState('active'); // 'active' | 'trashed'
    const [selectedUser, setSelectedUser] = useState(null);
    const [creditAmount, setCreditAmount] = useState(5);
    const [showCreditModal, setShowCreditModal] = useState(false);
    const [confirmAction, setConfirmAction] = useState(null); // { type: 'delete'|'restore'|'force', user }
    const queryClient = useQueryClient();

    const { data, isLoading } = useQuery({
        queryKey: ['admin-users', tab, tab === 'trashed' ? trashPage : page],
        queryFn: () => {
            if (tab === 'trashed') {
                return api.get('/admin/users/trashed', { params: { page: trashPage } });
            }
            return api.get('/admin/users', { params: { page } });
        },
    });

    const grantMutation = useMutation({
        mutationFn: ({ id, credits }) => api.post(`/admin/users/${id}/credit`, { credits }),
        onSuccess: (res) => {
            queryClient.invalidateQueries({ queryKey: ['admin-users'] });
            setShowCreditModal(false);
            setSelectedUser(null);
            alert(`${res?.message || `Granted ${creditAmount} credits!`}\nNew balance: ${res?.data?.new_balance}`);
        },
        onError: (err) => alert(err?.message || 'Failed to grant credits'),
    });

    const softDeleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/admin/users/${id}`),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['admin-users'] });
            setConfirmAction(null);
        },
        onError: (err) => alert(err?.message || 'Failed to deactivate user'),
    });

    const restoreMutation = useMutation({
        mutationFn: (id) => api.post(`/admin/users/${id}/restore`),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['admin-users'] });
            setConfirmAction(null);
        },
        onError: (err) => alert(err?.message || 'Failed to restore user'),
    });

    const forceDeleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/admin/users/${id}/force`),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['admin-users'] });
            setConfirmAction(null);
        },
        onError: (err) => alert(err?.message || 'Failed to permanently delete user'),
    });

    const users = data?.data || [];
    const pagination = data?.meta || {};

    const openCreditModal = (user) => {
        setSelectedUser(user);
        setCreditAmount(5);
        setShowCreditModal(true);
    };

    const handleGrant = () => {
        if (!selectedUser) return;
        grantMutation.mutate({ id: selectedUser.id, credits: creditAmount });
    };

    const handlePageChange = (newPage) => {
        if (tab === 'trashed') {
            setTrashPage(newPage);
        } else {
            setPage(newPage);
        }
    };

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h2 className="text-xl font-semibold text-gray-900">Users</h2>
                <div className="flex gap-1 rounded-lg bg-gray-100 p-1">
                    <button
                        onClick={() => { setTab('active'); setPage(1); }}
                        className={`rounded-md px-4 py-1.5 text-sm font-medium transition-colors ${tab === 'active' ? 'bg-white text-teal-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'}`}
                    >
                        Active
                    </button>
                    <button
                        onClick={() => { setTab('trashed'); setTrashPage(1); }}
                        className={`rounded-md px-4 py-1.5 text-sm font-medium transition-colors ${tab === 'trashed' ? 'bg-white text-red-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'}`}
                    >
                        Trashed
                    </button>
                </div>
            </div>

            {isLoading ? (
                <div className="h-20 animate-pulse rounded-xl bg-gray-100" />
            ) : users.length === 0 ? (
                <div className="rounded-xl border border-gray-200 bg-white p-10 text-center text-gray-500">
                    {tab === 'trashed' ? 'No trashed users.' : 'No users found.'}
                </div>
            ) : (
                <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium text-gray-500">Name</th>
                                <th className="px-4 py-3 font-medium text-gray-500">Email</th>
                                <th className="px-4 py-3 font-medium text-gray-500">Roles</th>
                                <th className="px-4 py-3 font-medium text-gray-500">Credits</th>
                                {tab === 'trashed' && (
                                    <th className="px-4 py-3 font-medium text-gray-500">Deleted</th>
                                )}
                                <th className="px-4 py-3 font-medium text-gray-500">Joined</th>
                                <th className="px-4 py-3 font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {users.map((u) => (
                                <tr key={u.id} className={`hover:bg-gray-50 ${tab === 'trashed' ? 'bg-red-50/30' : ''}`}>
                                    <td className="px-4 py-3 font-medium text-teal-700 hover:text-teal-800">
                                        <Link to={`/admin/users/${u.id}`}>{u.name}</Link>
                                    </td>
                                    <td className="px-4 py-3 text-gray-500">{u.email}</td>
                                    <td className="px-4 py-3">
                                        <span className="inline-flex gap-1">
                                            {(u.roles || []).map((r) => (
                                                <span key={r.name} className="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium uppercase">{r.name}</span>
                                            ))}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 font-medium text-teal-600">{u.credits ?? 0}</td>
                                    {tab === 'trashed' && (
                                        <td className="px-4 py-3 text-gray-400 text-xs">
                                            {u.deleted_at ? new Date(u.deleted_at).toLocaleDateString() : '—'}
                                        </td>
                                    )}
                                    <td className="px-4 py-3 text-gray-400">{new Date(u.created_at).toLocaleDateString()}</td>
                                    <td className="px-4 py-3">
                                        <div className="flex gap-1.5">
                                            {tab === 'active' ? (
                                                <>
                                                    <button
                                                        onClick={() => openCreditModal(u)}
                                                        className="rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-teal-700 transition-colors"
                                                    >
                                                        Grant Credits
                                                    </button>
                                                    <button
                                                        onClick={() => setConfirmAction({ type: 'delete', user: u })}
                                                        className="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 transition-colors"
                                                    >
                                                        Deactivate
                                                    </button>
                                                </>
                                            ) : (
                                                <>
                                                    <button
                                                        onClick={() => setConfirmAction({ type: 'restore', user: u })}
                                                        className="rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-teal-700 transition-colors"
                                                    >
                                                        Restore
                                                    </button>
                                                    <button
                                                        onClick={() => setConfirmAction({ type: 'force', user: u })}
                                                        className="rounded-lg border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50 transition-colors"
                                                    >
                                                        Delete Forever
                                                    </button>
                                                </>
                                            )}
                                        </div>
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
                        <button
                            key={p}
                            onClick={() => handlePageChange(p)}
                            className={`rounded px-3 py-1 text-xs ${(tab === 'trashed' ? trashPage : page) === p ? 'bg-teal-600 text-white' : 'bg-white border text-gray-600'}`}
                        >
                            {p}
                        </button>
                    ))}
                </div>
            )}

            {/* Credit Modal */}
            {showCreditModal && selectedUser && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40" onClick={() => setShowCreditModal(false)}>
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
                                onClick={() => setShowCreditModal(false)}
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

            {/* Confirmation Modal (Delete / Restore / Force Delete) */}
            {confirmAction && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40" onClick={() => setConfirmAction(null)}>
                    <div className="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl" onClick={(e) => e.stopPropagation()}>
                        {confirmAction.type === 'delete' && (
                            <>
                                <h3 className="text-lg font-semibold text-gray-900">Deactivate User</h3>
                                <p className="mt-2 text-sm text-gray-600">
                                    This will deactivate <strong>{confirmAction.user.name}</strong> and move them to the trash. They will not be able to log in.
                                </p>
                                <div className="mt-6 flex gap-3">
                                    <button
                                        onClick={() => setConfirmAction(null)}
                                        className="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        onClick={() => softDeleteMutation.mutate(confirmAction.user.id)}
                                        disabled={softDeleteMutation.isPending}
                                        className="flex-1 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50 transition-colors"
                                    >
                                        {softDeleteMutation.isPending ? 'Deactivating...' : 'Deactivate'}
                                    </button>
                                </div>
                            </>
                        )}
                        {confirmAction.type === 'restore' && (
                            <>
                                <h3 className="text-lg font-semibold text-gray-900">Restore User</h3>
                                <p className="mt-2 text-sm text-gray-600">
                                    This will restore <strong>{confirmAction.user.name}</strong> to active status. They will be able to log in again.
                                </p>
                                <div className="mt-6 flex gap-3">
                                    <button
                                        onClick={() => setConfirmAction(null)}
                                        className="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        onClick={() => restoreMutation.mutate(confirmAction.user.id)}
                                        disabled={restoreMutation.isPending}
                                        className="flex-1 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50 transition-colors"
                                    >
                                        {restoreMutation.isPending ? 'Restoring...' : 'Restore'}
                                    </button>
                                </div>
                            </>
                        )}
                        {confirmAction.type === 'force' && (
                            <>
                                <h3 className="text-lg font-semibold text-red-700">Permanently Delete User</h3>
                                <p className="mt-2 text-sm text-gray-600">
                                    This will <strong className="text-red-700">permanently</strong> delete {confirmAction.user.name} and all associated data (health profile, credits, push subscriptions, health metrics).
                                    <br /><br />
                                    Lab submissions and payments will be preserved but unlinked. This cannot be undone.
                                </p>
                                <div className="mt-6 flex gap-3">
                                    <button
                                        onClick={() => setConfirmAction(null)}
                                        className="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        onClick={() => forceDeleteMutation.mutate(confirmAction.user.id)}
                                        disabled={forceDeleteMutation.isPending}
                                        className="flex-1 rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800 disabled:opacity-50 transition-colors"
                                    >
                                        {forceDeleteMutation.isPending ? 'Deleting...' : 'Delete Forever'}
                                    </button>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}