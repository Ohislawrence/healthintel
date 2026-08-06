import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';

export default function AdminBenchmarks() {
    const { data, isLoading } = useQuery({ queryKey: ['admin-benchmarks'], queryFn: () => api.get('/admin/benchmarks') });
    const benchmarks = data?.data || [];

    if (isLoading) return <p className="text-gray-400 text-sm">Loading benchmarks...</p>;

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h2 className="text-xl font-bold text-gray-900">Clinical Benchmarks</h2>
                <button onClick={() => { window.location.reload(); }} className="text-sm text-teal-600 hover:text-teal-700">🔄 Refresh</button>
            </div>

            {benchmarks.length === 0 ? (
                <div className="bg-white border border-gray-200 rounded-xl p-8 text-center">
                    <p className="text-gray-400 text-sm">No benchmarks run yet.</p>
                    <p className="text-xs text-gray-400 mt-1">Run <code className="bg-gray-100 px-2 py-0.5 rounded">php artisan benchmark:clinical</code> from the server to generate benchmark results.</p>
                </div>
            ) : (
                <div className="space-y-4">
                    {benchmarks.map((b) => (
                        <div key={b.id} className="bg-white border border-gray-200 rounded-xl p-5">
                            <div className="flex items-center justify-between mb-3">
                                <div>
                                    <h3 className="font-bold text-sm">{b.name}</h3>
                                    <p className="text-xs text-gray-400">{b.model_used} · {b.dataset_version} · {b.total_questions} questions</p>
                                </div>
                                <span className={`text-lg font-bold ${b.accuracy >= 90 ? 'text-green-600' : b.accuracy >= 70 ? 'text-yellow-600' : 'text-red-600'}`}>{b.accuracy}%</span>
                            </div>
                            <div className="grid grid-cols-4 gap-3 mb-4">
                                <div className="text-center bg-green-50 rounded-lg p-2"><p className="text-xs text-gray-500">Correct</p><p className="text-sm font-bold text-green-700">{b.correct_answers}/{b.total_questions}</p></div>
                                <div className="text-center bg-blue-50 rounded-lg p-2"><p className="text-xs text-gray-500">Accuracy</p><p className="text-sm font-bold text-blue-700">{b.accuracy_formatted || b.accuracy + '%'}</p></div>
                                <div className="text-center bg-purple-50 rounded-lg p-2"><p className="text-xs text-gray-500">Avg Time</p><p className="text-sm font-bold text-purple-700">{Math.round(b.avg_response_time_ms || 0)}ms</p></div>
                                <div className="text-center bg-gray-50 rounded-lg p-2"><p className="text-xs text-gray-500">Status</p><p className={`text-sm font-bold ${b.status === 'completed' ? 'text-green-700' : 'text-yellow-700'}`}>{b.status}</p></div>
                            </div>
                            {b.specialty_breakdown && (
                                <div>
                                    <p className="text-xs font-bold text-gray-500 mb-2">Specialty Breakdown</p>
                                    <div className="space-y-1">
                                        {Object.entries(b.specialty_breakdown).map(([specialty, data]) => {
                                            const pct = data.total > 0 ? Math.round((data.correct / data.total) * 100) : 0;
                                            return (
                                                <div key={specialty} className="flex items-center gap-2 text-xs">
                                                    <span className="w-20 text-gray-600">{specialty}</span>
                                                    <div className="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                                        <div className={`h-full rounded-full ${pct >= 90 ? 'bg-green-500' : pct >= 70 ? 'bg-yellow-500' : 'bg-red-500'}`} style={{ width: `${pct}%` }} />
                                                    </div>
                                                    <span className="w-24 text-right text-gray-400">{data.correct}/{data.total} ({pct}%)</span>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}
                            <p className="text-xs text-gray-400 mt-3">Completed {b.completed_at ? new Date(b.completed_at).toLocaleString() : '—'}</p>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}