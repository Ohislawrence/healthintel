import React, { useEffect, useRef, useState } from 'react';
import { useSearchParams, Link } from 'react-router-dom';
import api from '../../lib/api';
import { trackEngagement } from '../../lib/engagement';
import useAuthStore from '../../stores/authStore';

export default function PaymentCallback() {
    const [searchParams] = useSearchParams();
    const { fetchUser } = useAuthStore();
    const [status, setStatus] = useState('verifying');
    const [message, setMessage] = useState('');
    const [cancelled, setCancelled] = useState(false);
    const trackedStatusRef = useRef({ success: false, failed: false, cancelled: false });

    useEffect(() => {
        // Flutterwave uses tx_ref + transaction_id; Paystack uses reference / trxref;
        // Nomba uses orderReference / orderId
        const txRef = searchParams.get('tx_ref');
        const transactionId = searchParams.get('transaction_id') || searchParams.get('id') || searchParams.get('orderId');
        const paystackRef = searchParams.get('reference') || searchParams.get('trxref');
        const nombaRef = searchParams.get('orderReference') || searchParams.get('orderId');

        const reference = txRef || paystackRef || nombaRef;

        if (!reference) {
            setStatus('error');
            setMessage('No payment reference found.');
            return;
        }

        const verify = async () => {
            try {
                let url = `/payment/verify?reference=${encodeURIComponent(reference)}`;
                if (txRef && transactionId) {
                    url += `&transaction_id=${encodeURIComponent(transactionId)}`;
                }
                await api.get(url);
                await fetchUser();
                setStatus('success');
                if (!trackedStatusRef.current.success) {
                    trackedStatusRef.current.success = true;
                    trackEngagement('payment_verified_success', 'payment_callback', { reference });
                }
            } catch (err) {
                const msg = err?.message || '';
                const isCancelled = /cancell/i.test(msg);
                setCancelled(isCancelled);
                setStatus('error');
                setMessage(msg || (isCancelled
                    ? 'Your payment was cancelled. No credits were added.'
                    : 'Payment verification failed. If you were debited, credits will be added automatically.'));

                if (isCancelled && !trackedStatusRef.current.cancelled) {
                    trackedStatusRef.current.cancelled = true;
                    trackEngagement('payment_verified_cancelled', 'payment_callback', { reference });
                }

                if (!isCancelled && !trackedStatusRef.current.failed) {
                    trackedStatusRef.current.failed = true;
                    trackEngagement('payment_verified_failed', 'payment_callback', { reference });
                }
            }
        };

        verify();
    }, [searchParams, fetchUser]);

    return (
        <div className="flex min-h-screen items-center justify-center bg-neutral-50 px-4">
            <div className="w-full max-w-sm text-center animate-fade-in-up">
                <Link to="/" className="block text-center mb-8">
                    <span className="text-2xl font-extrabold tracking-tight text-teal-700">Health</span>
                    <span className="text-2xl font-extrabold tracking-tight text-neutral-900">Intel</span>
                </Link>

                <div className="card p-6">
                    {status === 'verifying' && (
                        <>
                            <div className="w-12 h-12 border-4 border-teal-500 border-t-transparent rounded-full animate-spin mx-auto mb-4" />
                            <h2 className="text-lg font-bold text-neutral-900">Verifying payment...</h2>
                            <p className="text-sm text-neutral-500 mt-1">Please wait while we confirm your transaction.</p>
                        </>
                    )}

                    {status === 'success' && (
                        <>
                            <div className="w-16 h-16 rounded-full bg-success-50 flex items-center justify-center text-2xl text-success-600 font-bold mx-auto mb-4">✓</div>
                            <h2 className="text-lg font-bold text-neutral-900">Payment successful!</h2>
                            <p className="text-sm text-neutral-500 mt-2">Your credits are now available. Continue your health tasks while momentum is high.</p>

                            <div className="mt-4 rounded-xl border border-teal-100 bg-teal-50 p-3 text-left">
                                <p className="text-xs font-bold uppercase tracking-wider text-teal-700">What to do next</p>
                                <ul className="mt-2 space-y-1 text-sm text-teal-900">
                                    <li>Finish your pending lab interpretation.</li>
                                    <li>Run a symptom check if you need quick guidance.</li>
                                    <li>Share your referral link to earn from future top-ups.</li>
                                </ul>
                            </div>

                            <div className="mt-5 space-y-2">
                                <Link to="/lab-results" className="btn btn-primary w-full">
                                    Continue to Lab Results
                                </Link>
                                <Link to="/referral" className="btn btn-outline w-full">
                                    Invite Friends, Earn Credits
                                </Link>
                                <Link to="/credits" className="block text-sm font-semibold text-teal-600 hover:text-teal-700">
                                    View Credit History →
                                </Link>
                            </div>
                        </>
                    )}

                    {status === 'error' && (
                        <>
                            <div className="w-16 h-16 rounded-full bg-danger-50 flex items-center justify-center text-2xl text-danger-600 font-bold mx-auto mb-4">✕</div>
                            <h2 className="text-lg font-bold text-neutral-900">{cancelled ? 'Payment Cancelled' : 'Verification Failed'}</h2>
                            <p className="text-sm text-neutral-500 mt-2">{message || (cancelled ? 'Your payment was cancelled. No credits were added.' : 'We could not verify your payment.')}</p>
                            <div className="mt-5 space-y-2">
                                <Link to="/credits/buy" className="btn btn-outline w-full">
                                    Try Again
                                </Link>
                                <Link to="/dashboard" className="block text-sm font-semibold text-teal-600 hover:text-teal-700">
                                    Go to Dashboard
                                </Link>
                            </div>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}