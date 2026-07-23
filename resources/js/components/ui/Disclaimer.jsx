import React from 'react';

/**
 * Persistent, non-dismissible disclaimer shown on every page
 * that displays medical information.
 */
export default function Disclaimer({ compact = false }) {
    if (compact) {
        return (
            <div className="flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 border border-amber-200">
                <svg className="h-4 w-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <span>This is not a medical diagnosis. Please consult a licensed healthcare professional.</span>
            </div>
        );
    }

    return (
        <div className="rounded-xl bg-amber-50 px-5 py-4 border-2 border-amber-200">
            <div className="flex items-start gap-3">
                <svg className="h-6 w-6 shrink-0 text-amber-500 mt-0.5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <div>
                    <h3 className="font-semibold text-amber-900">Important Notice</h3>
                    <p className="mt-1 text-sm text-amber-800 leading-relaxed">
                        This information is for educational purposes only. It is <strong>not a medical diagnosis</strong>.
                        Please consult a licensed healthcare professional for proper evaluation and treatment.
                    </p>
                </div>
            </div>
        </div>
    );
}
