import React, { useEffect, useState } from 'react';
import {
    subscribeToPush,
    getNotificationPermission,
    hasNotificationPermission,
} from '../../lib/pwa';

/**
 * A one-time, delayed opt-in modal for push notifications.
 *
 * Shows ~5 seconds after an authenticated, online user opens the app —
 * but only once per browser and only when notifications are still
 * in the "default" (not yet decided) permission state.
 */

const DISMISS_KEY = 'labdoc_notification_prompt_dismissed';
const SHOW_DELAY_MS = 5000; // 5 seconds after the user comes online

export default function NotificationSubscribeModal({ active }) {
    const [open, setOpen] = useState(false);
    const [subscribing, setSubscribing] = useState(false);
    const [error, setError] = useState(false);

    useEffect(() => {
        if (!active) return;

        // Unsupported browsers
        if (!('PushManager' in window) || !('Notification' in window)) return;

        // Never nag the user again once they've dismissed/subscribed/denied
        if (localStorage.getItem(DISMISS_KEY)) return;

        // Only prompt when the user hasn't decided yet ("default").
        // "granted" means they are already (or can be silently) subscribed;
        // "denied" means we cannot subscribe anyway.
        if (getNotificationPermission() !== 'default') return;

        const timer = setTimeout(() => setOpen(true), SHOW_DELAY_MS);
        return () => clearTimeout(timer);
    }, [active]);

    useEffect(() => {
        if (!open) return;
        const onKey = (e) => {
            if (e.key === 'Escape') dismiss(true);
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [open]);

    const dismiss = (remember = true) => {
        setOpen(false);
        setError(false);
        if (remember) localStorage.setItem(DISMISS_KEY, '1');
    };

    const handleEnable = async () => {
        setSubscribing(true);
        setError(false);

        // This triggers the browser's native "Allow / Block" prompt.
        const subscription = await subscribeToPush();
        setSubscribing(false);

        if (subscription || hasNotificationPermission()) {
            // Granted — mark done and close.
            localStorage.setItem(DISMISS_KEY, '1');
            setOpen(false);
            return;
        }

        if (getNotificationPermission() === 'denied') {
            // User explicitly blocked it in the native prompt.
            localStorage.setItem(DISMISS_KEY, '1');
            setOpen(false);
            return;
        }

        // Still "default" — likely dismissed the native prompt or an error
        // occurred. Allow a retry without permanently hiding the modal.
        setError(true);
    };

    if (!open) return null;

    return (
        <div className="fixed inset-0 z-[60] flex items-center justify-center p-4">
            {/* Backdrop */}
            <div
                className="absolute inset-0 bg-black/40 backdrop-blur-sm"
                onClick={() => dismiss(true)}
            />

            {/* Modal */}
            <div className="relative w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden animate-fade-in-up">
                {/* Header */}
                <div className="relative bg-gradient-to-br from-teal-500 to-emerald-600 px-6 pt-8 pb-6 text-center">
                    <button
                        onClick={() => dismiss(true)}
                        className="absolute top-3 right-3 p-1.5 rounded-full text-white/80 hover:bg-white/15 hover:text-white transition-colors"
                        aria-label="Close"
                    >
                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <div className="mx-auto w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center mb-3">
                        <svg className="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round">
                            <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9" />
                        </svg>
                    </div>

                    <h2 className="text-xl font-bold text-white">Never miss an update</h2>
                    <p className="mt-1 text-sm text-white/85">
                        Enable notifications for instant alerts.
                    </p>
                </div>

                {/* Body */}
                <div className="px-6 py-6">
                    <ul className="space-y-3">
                        {[
                            'Lab result interpretations as soon as they are ready',
                            'Medication, appointment and immunization reminders',
                            'Important account and health alerts',
                        ].map((item) => (
                            <li key={item} className="flex items-start gap-3">
                                <span className="mt-0.5 w-5 h-5 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center shrink-0">
                                    <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}>
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                                <span className="text-sm text-neutral-600 leading-relaxed">{item}</span>
                            </li>
                        ))}
                    </ul>

                    {error && (
                        <p className="mt-4 text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                            We couldn't enable notifications. Please try again or check your browser settings.
                        </p>
                    )}

                    <div className="mt-6 flex flex-col space-y-2">
                        <button
                            onClick={handleEnable}
                            disabled={subscribing}
                            className="w-full rounded-xl bg-gradient-to-r from-teal-500 to-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-teal-500/20 hover:from-teal-600 hover:to-emerald-700 disabled:opacity-60 transition-all"
                        >
                            {subscribing ? (
                                <span className="inline-flex items-center gap-2">
                                    <span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                                    Enabling…
                                </span>
                            ) : (
                                'Enable notifications'
                            )}
                        </button>

                        <button
                            onClick={() => dismiss(true)}
                            disabled={subscribing}
                            className="w-full rounded-xl px-4 py-2.5 text-sm font-medium text-neutral-500 hover:bg-neutral-50 hover:text-neutral-700 transition-colors"
                        >
                            Not now
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}