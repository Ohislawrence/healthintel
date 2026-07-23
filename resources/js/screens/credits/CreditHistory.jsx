import React from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import useAuthStore from '../../stores/authStore';

export default function CreditHistory() {
    const { user } = useAuthStore();
    const { data, isLoading } = useQuery({
        queryKey: ['credit-ledger'],
        queryFn: () => api.get('/payment/summary'),
    });
    const summary = data?.data?.summary || {};
    const transactions = summary?.transactions || [];

    return (
        <div className="space-y-5">
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Credits</p>
                    <p className="text-sm font-medium text-neutral-500 mt-0.5">Your credit balance and history</p>
                </div>
            </div>

            {/* Balance Card */}
            <div className="card overflow-hidden border-0 shadow-lg" style={{ boxShadow: '0 6px 24px rgba(15,118,110,0.12)' }}>
                <div className="gradient-teal p-5 text-center">
                    <span className="text-xs font-extrabold text-white/60 uppercase tracking-widest">Available Credits</span>
                    <p className="text-5xl font-extrabold text-white tracking-tighter mt-2">{user?.credits ?? 0}</p>
                    <Link to="/credits/buy" className="mt-4 inline-flex items-center gap-1.5 bg-white/20 rounded-xl px-4 py-2 text-sm font-bold text-white hover:bg-white/30 transition-all">
                        + Buy More
                    </Link>
                </div>
            </div>

            {/* Transactions */}
            <div>
                <p className="text-sm font-bold text-neutral-900 mb-3">Transaction History</p>
                {isLoading ? (
                    <div className="card p-8 text-center">
                        <div className="skeleton h-4 w-48 mx-auto rounded" />
                    </div>
                ) : transactions.length === 0 ? (
                    <div className="card p-8 text-center">
                        <span className="text-3xl block mb-3">◆</span>
                        <p className="text-sm font-bold text-neutral-900 mb-1">No transactions yet</p>
                        <p className="text-xs text-neutral-500">Your credit activity will appear here</p>
                    </div>
                ) : (
                    <div className="card overflow-hidden">
                        {transactions.map((tx, index) => (
                            <div
                                key={tx.id || index}
                                className={`flex items-center justify-between px-4 py-3 ${
                                    index < transactions.length - 1 ? 'border-b border-neutral-100' : ''
                                }`}
                            >
                                <div>
                                    <p className="text-sm font-semibold text-neutral-900 capitalize">
                                        {tx.action_type?.replace(/_/g, ' ') || 'Transaction'}
                                    </p>
                                    <p className="text-xs text-neutral-400 mt-0.5">
                                        {new Date(tx.created_at).toLocaleDateString('en-US', {
                                            month: 'short', day: 'numeric', year: 'numeric',
                                        })}
                                    </p>
                                </div>
                                <span className={`text-sm font-bold ${tx.credits_delta > 0 ? 'text-success-600' : 'text-danger-600'}`}>
                                    {tx.credits_delta > 0 ? '+' : ''}{tx.credits_delta}
                                </span>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}