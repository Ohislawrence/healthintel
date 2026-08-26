import React, { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../lib/api';

const STATUS_COLORS = {
  open: 'bg-red-100 text-red-700',
  resolved: 'bg-green-100 text-green-700',
  ignored: 'bg-gray-100 text-gray-600',
};

const LEVEL_COLORS = {
  error: 'bg-red-50 text-red-600',
  warning: 'bg-amber-50 text-amber-600',
  info: 'bg-blue-50 text-blue-600',
};

export default function AdminErrorReports() {
  const [page, setPage] = useState(1);
  const [status, setStatus] = useState('open');
  const [level, setLevel] = useState('');
  const [source, setSource] = useState('');
  const [search, setSearch] = useState('');

  const { data, isLoading, refetch } = useQuery({
    queryKey: ['admin-error-reports', page, status, level, source, search],
    queryFn: () =>
      api.get('/admin/error-reports', {
        params: {
          page,
          status: status || undefined,
          level: level || undefined,
          source: source || undefined,
          search: search || undefined,
        },
      }),
  });

  const statusMutation = useMutation({
    mutationFn: ({ id, status: s }) => api.put(`/admin/error-reports/${id}`, { status: s }),
    onSuccess: () => refetch(),
  });

  const reports = data?.data || [];
  const pagination = data?.meta || {};

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-xl font-semibold text-gray-900">Error Logs</h2>
          <p className="text-sm text-gray-500 mt-1">Client-side and API errors reported by users.</p>
        </div>
        <button
          onClick={() => refetch()}
          className="text-sm text-teal-600 hover:underline"
        >
          Refresh
        </button>
      </div>

      {/* Filters */}
      <div className="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4">
        <div className="flex-1 min-w-[200px]">
          <label className="block text-xs font-medium text-gray-500 mb-1">Search</label>
          <input
            className="w-full rounded border border-gray-300 px-3 py-1.5 text-sm"
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1); }}
            placeholder="Message, type, or URL…"
          />
        </div>
        <div>
          <label className="block text-xs font-medium text-gray-500 mb-1">Status</label>
          <select className="rounded border border-gray-300 px-3 py-1.5 text-sm" value={status} onChange={(e) => { setStatus(e.target.value); setPage(1); }}>
            <option value="open">Open</option>
            <option value="resolved">Resolved</option>
            <option value="ignored">Ignored</option>
            <option value="">All</option>
          </select>
        </div>
        <div>
          <label className="block text-xs font-medium text-gray-500 mb-1">Level</label>
          <select className="rounded border border-gray-300 px-3 py-1.5 text-sm" value={level} onChange={(e) => { setLevel(e.target.value); setPage(1); }}>
            <option value="">All</option>
            <option value="error">Error</option>
            <option value="warning">Warning</option>
            <option value="info">Info</option>
          </select>
        </div>
        <div>
          <label className="block text-xs font-medium text-gray-500 mb-1">Source</label>
          <select className="rounded border border-gray-300 px-3 py-1.5 text-sm" value={source} onChange={(e) => { setSource(e.target.value); setPage(1); }}>
            <option value="">All</option>
            <option value="frontend">Frontend</option>
            <option value="api">API</option>
            <option value="server">Server</option>
          </select>
        </div>
      </div>

      {/* List */}
      {isLoading ? (
        <div className="h-20 animate-pulse rounded-xl bg-gray-100" />
      ) : reports.length === 0 ? (
        <div className="bg-white rounded-xl border border-gray-200 p-10 text-center text-gray-400">
          No error reports found.
        </div>
      ) : (
        <div className="space-y-3">
          {reports.map((r) => (
            <div key={r.id} className="bg-white rounded-xl border border-gray-200 p-4">
              <div className="flex items-start justify-between gap-3">
                <div className="min-w-0 flex-1">
                  <div className="flex items-center gap-2 flex-wrap">
                    <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${STATUS_COLORS[r.status] || STATUS_COLORS.open}`}>
                      {r.status}
                    </span>
                    <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${LEVEL_COLORS[r.level] || LEVEL_COLORS.error}`}>
                      {r.level}
                    </span>
                    <span className="text-xs text-gray-400 uppercase">{r.source}</span>
                    <span className="text-xs font-extrabold text-red-500">
                      ×{r.occurrences}
                    </span>
                  </div>

                  <p className="text-sm font-semibold text-gray-900 mt-2 break-words">{r.message}</p>

                  {r.type && <p className="text-xs font-mono text-gray-500 mt-1">{r.type}</p>}

                  <div className="text-xs text-gray-400 mt-2 flex items-center gap-3 flex-wrap">
                    {r.url && <span className="truncate max-w-[400px]">{r.url}</span>}
                    {r.last_seen_at && (
                      <span>Last seen: {new Date(r.last_seen_at).toLocaleString()}</span>
                    )}
                  </div>

                  {r.context && Object.keys(r.context).length > 0 && (
                    <details className="mt-2 text-xs">
                      <summary className="text-gray-400 cursor-pointer">Context</summary>
                      <pre className="mt-1 bg-gray-50 rounded p-2 overflow-x-auto text-[11px] text-gray-600">
                        {JSON.stringify(r.context, null, 2)}
                      </pre>
                    </details>
                  )}
                </div>

                <div className="shrink-0">
                  {r.status === 'open' && (
                    <div className="flex flex-col gap-2">
                      <button
                        onClick={() => statusMutation.mutate({ id: r.id, status: 'resolved' })}
                        className="text-xs font-semibold text-green-600 hover:underline"
                      >
                        Resolve
                      </button>
                      <button
                        onClick={() => statusMutation.mutate({ id: r.id, status: 'ignored' })}
                        className="text-xs font-semibold text-gray-500 hover:underline"
                      >
                        Ignore
                      </button>
                    </div>
                  )}
                  {(r.status === 'resolved' || r.status === 'ignored') && (
                    <button
                      onClick={() => statusMutation.mutate({ id: r.id, status: 'open' })}
                      className="text-xs font-semibold text-gray-400 hover:underline"
                    >
                      Reopen
                    </button>
                  )}
                </div>
              </div>
            </div>
          ))}
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