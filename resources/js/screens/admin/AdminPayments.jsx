import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';

const STATUS_STYLES = {
  success: 'bg-green-50 text-green-700',
  pending: 'bg-yellow-50 text-yellow-700',
  failed: 'bg-red-50 text-red-700',
  cancelled: 'bg-gray-50 text-gray-500',
};

export default function AdminPayments() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [providerFilter, setProviderFilter] = useState('');
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ['admin-payments', page, search, statusFilter, providerFilter],
    queryFn: () =>
      api.get('/admin/payments', {
        params: {
          page,
          search: search || undefined,
          status: statusFilter || undefined,
          provider: providerFilter || undefined,
        },
      }),
  });

  const reconcileMutation = useMutation({
    mutationFn: (id) => api.post(`/admin/payments/${id}/reconcile`),
    onSuccess: (res) => {
      queryClient.invalidateQueries({ queryKey: ['admin-payments'] });
      const p = res?.data?.payment || res?.payment;
      alert(
        `Reconciled.\nStatus: ${p?.status ?? '—'}\nCredits granted: ${p?.credits_granted ? 'Yes ✓' : 'No (nothing to grant)'}`
      );
    },
    onError: (err) => alert(err?.message || 'Reconciliation failed'),
  });

  const payments = data?.data || [];
  const pagination = data?.meta || {};

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold text-gray-900">Payments</h2>
        <span className="text-sm text-gray-500">{pagination.total || 0} total</span>
      </div>

      {/* Filters */}
      <div className="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4">
        <div className="flex-1 min-w-[200px]">
          <label className="block text-xs font-medium text-gray-500 mb-1">Search</label>
          <input
            className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm"
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1); }}
            placeholder="Reference, provider ref, name, email…"
          />
        </div>
        <div>
          <label className="block text-xs font-medium text-gray-500 mb-1">Status</label>
          <select
            className="rounded border border-gray-300 px-3 py-1.5 text-sm"
            value={statusFilter}
            onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }}
          >
            <option value="">All</option>
            <option value="success">Success</option>
            <option value="pending">Pending</option>
            <option value="failed">Failed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div>
          <label className="block text-xs font-medium text-gray-500 mb-1">Provider</label>
          <select
            className="rounded border border-gray-300 px-3 py-1.5 text-sm"
            value={providerFilter}
            onChange={(e) => { setProviderFilter(e.target.value); setPage(1); }}
          >
            <option value="">All</option>
            <option value="paystack">Paystack</option>
            <option value="flutterwave">Flutterwave</option>
            <option value="nomba">Nomba</option>
          </select>
        </div>
        {(search || statusFilter || providerFilter) && (
          <button
            onClick={() => { setSearch(''); setStatusFilter(''); setProviderFilter(''); setPage(1); }}
            className="rounded border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50"
          >
            Clear
          </button>
        )}
      </div>

      {isLoading ? (
        <div className="h-20 animate-pulse rounded-xl bg-gray-100" />
      ) : payments.length === 0 ? (
        <div className="rounded-xl border border-gray-200 bg-white p-10 text-center text-gray-500">
          No payments found.
        </div>
      ) : (
        <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-left">
              <tr>
                <th className="px-4 py-3 font-medium text-gray-500">User</th>
                <th className="px-4 py-3 font-medium text-gray-500">Reference</th>
                <th className="px-4 py-3 font-medium text-gray-500">Provider</th>
                <th className="px-4 py-3 font-medium text-gray-500">Amount</th>
                <th className="px-4 py-3 font-medium text-gray-500">Package</th>
                <th className="px-4 py-3 font-medium text-gray-500">Status</th>
                <th className="px-4 py-3 font-medium text-gray-500">Credited</th>
                <th className="px-4 py-3 font-medium text-gray-500 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {payments.map((p) => (
                <tr key={p.id} className="hover:bg-gray-50 align-top">
                  <td className="px-4 py-3">
                    <div className="font-medium text-gray-900">{p.user?.name ?? '—'}</div>
                    <div className="text-xs text-gray-500">{p.user?.email ?? ''}</div>
                  </td>
                  <td className="px-4 py-3">
                    <div className="font-mono text-xs text-gray-700">{p.reference}</div>
                    {p.provider_reference && (
                      <div className="font-mono text-[10px] text-gray-400">{p.provider_reference}</div>
                    )}
                  </td>
                  <td className="px-4 py-3 text-gray-600 uppercase text-xs">{p.provider}</td>
                  <td className="px-4 py-3 font-medium text-gray-900">
                    {p.currency} {(p.amount_naira ?? 0).toLocaleString()}
                  </td>
                  <td className="px-4 py-3 text-gray-600">
                    {p.package ? `${p.package.name} (${p.package.credits}c)` : '—'}
                  </td>
                  <td className="px-4 py-3">
                    <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${STATUS_STYLES[p.status] || 'bg-gray-50 text-gray-500'}`}>
                      {p.status}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    {p.credits_granted ? (
                      <span className="inline-flex rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Yes</span>
                    ) : (
                      <span className="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">No</span>
                    )}
                  </td>
                  <td className="px-4 py-3 text-right whitespace-nowrap">
                    <button
                      onClick={() => {
                        if (confirm(`Reconcile payment ${p.reference}?`)) reconcileMutation.mutate(p.id);
                      }}
                      disabled={reconcileMutation.isPending}
                      className="rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-teal-700 disabled:opacity-50 transition-colors"
                    >
                      {reconcileMutation.isPending ? '…' : 'Reconcile'}
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
            <button
              key={p}
              onClick={() => setPage(p)}
              className={`rounded px-3 py-1 text-xs ${page === p ? 'bg-teal-600 text-white' : 'bg-white border text-gray-600'}`}
            >
              {p}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}