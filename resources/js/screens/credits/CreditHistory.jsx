import React from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import useAuthStore from '../../stores/authStore';

export default function CreditHistory() {
    const { user } = useAuthStore();

    const { data, isLoading } = useQuery({
        queryKey: ['credits-summary'],
        queryFn: () => api.get('/credits/summary'),
    });

    const balance = data?.data?.balance ?? user?.credits ?? 0;
    const transactions = data?.data?.transactions || [];
    const payments = data?.data?.recent_payments || [];

    const actionLabels = {
        signup_bonus: 'Signup Bonus',
        credit_purchase: 'Credit Purchase',
        lab_interpretation: 'Lab Interpretation',
        refund: 'Refund',
    };

    const paymentStatusColors = {
        pending: 'bg-yellow-50 text-yellow-700',
        success: 'bg-green-50 text-green-700',
        failed: 'bg-red-50 text-red-700',
    };

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-xl font-semibold text-gray-900">Credits & History</h2>
                    <p className="mt-1 text-sm text-gray-500">Your current balance</p>
                </div>
                <Link
                    to="/credits/buy"
                    className="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700"
                >
                    Buy credits
                </Link>
            </div>

            {/* Balance card */}
            <div className="rounded-xl bg-gradient-to-r from-teal-500 to-teal-600 p-6 text-white">
                <p className="text-sm opacity-80">Available Credits</p>
                <p className="mt-1 text-4xl font-bold">{balance}</p>
            </div>

            {/* Transaction history */}
            <div className="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <h3 className="px-6 py-4 text-sm font-semibold text-gray-700 uppercase tracking-wide border-b border-gray-100">
                    Recent Transactions
                </h3>
                {isLoading ? (
                    <div className="p-6 space-y-3">
                        {[1, 2, 3].map((i) => (
                            <div key={i} className="h-8 animate-pulse rounded bg-gray-100" />
                        ))}
                    </div>
                ) : transactions.length === 0 ? (
                    <p className="px-6 py-8 text-sm text-gray-500 text-center">No transactions yet.</p>
                ) : (
                    <div className="divide-y divide-gray-50">
                        {transactions.map((tx) => (
                            <div key={tx.id} className="flex items-center justify-between px-6 py-3">
                                <div>
                                    <p className="text-sm font-medium text-gray-900">
                                        {actionLabels[tx.action_type] || tx.action_type}
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        {new Date(tx.created_at).toLocaleDateString()}
                                    </p>
                                </div>
                                <span
                                    className={`text-sm font-semibold ${
                                        tx.credits_delta > 0 ? 'text-green-600' : 'text-red-600'
                                    }`}
                                >
                                    {tx.credits_delta > 0 ? '+' : ''}{tx.credits_delta}
                                </span>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {/* Payment history */}
            <div className="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <h3 className="px-6 py-4 text-sm font-semibold text-gray-700 uppercase tracking-wide border-b border-gray-100">
                    Payment History
                </h3>
                {payments.length === 0 ? (
                    <p className="px-6 py-8 text-sm text-gray-500 text-center">No payments yet.</p>
                ) : (
                    <div className="divide-y divide-gray-50">
                        {payments.map((p) => (
                            <div key={p.id} className="flex items-center justify-between px-6 py-3">
                                <div>
                                    <p className="text-sm font-medium text-gray-900">
                                        ₦{(p.amount_kobo / 100).toLocaleString()}
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        {new Date(p.created_at).toLocaleDateString()}
                                    </p>
                                </div>
                                <span
                                    className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium ${
                                        paymentStatusColors[p.status] || 'bg-gray-50 text-gray-600'
                                    }`}
                                >
                                    {p.status}
                                </span>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
