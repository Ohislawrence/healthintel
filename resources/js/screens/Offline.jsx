import React from 'react';
import { Link } from 'react-router-dom';

export default function Offline() {
    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4">
            <div className="text-center max-w-md">
                <div className="text-6xl mb-6">📡</div>
                <h1 className="text-2xl font-bold text-gray-900 mb-3">
                    You're Offline
                </h1>
                <p className="text-gray-600 mb-8">
                    It looks like you've lost your internet connection. Some features
                    will be limited until you're back online.
                </p>
                <div className="space-y-3">
                    <button
                        onClick={() => window.location.reload()}
                        className="w-full bg-teal-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-teal-700 transition-colors"
                    >
                        Try Again
                    </button>
                    <Link
                        to="/dashboard"
                        className="block w-full text-teal-600 px-6 py-3 rounded-lg font-medium hover:bg-teal-50 transition-colors border border-teal-200"
                    >
                        Go to Dashboard
                    </Link>
                </div>
            </div>
        </div>
    );
}