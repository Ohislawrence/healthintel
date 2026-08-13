import React, { useEffect, useState } from 'react';
import { isInstalled, promptInstall } from '../../lib/pwa';

/**
 * Shows an "Install app" button inside the SPA when the browser
 * supports the native PWA install prompt and the app isn't installed yet.
 */
export default function InstallAppButton({ compact = false }) {
    const [installReady, setInstallReady] = useState(() => !!window.__pwaInstallPrompt);
    const [installed, setInstalled] = useState(isInstalled());
    const [installing, setInstalling] = useState(false);

    useEffect(() => {
        if (isInstalled()) {
            setInstalled(true);
            return;
        }

        // Prompt may have already been captured by the Blade shell before React mounted.
        if (window.__pwaInstallPrompt) {
            setInstallReady(true);
        }

        const onInstallReady = () => setInstallReady(true);
        const onInstalled = () => {
            setInstalled(true);
            setInstallReady(false);
        };

        window.addEventListener('pwa:install-ready', onInstallReady);
        window.addEventListener('pwa:installed', onInstalled);
        return () => {
            window.removeEventListener('pwa:install-ready', onInstallReady);
            window.removeEventListener('pwa:installed', onInstalled);
        };
    }, []);

    const handleInstall = async () => {
        setInstalling(true);
        const accepted = await promptInstall();
        setInstalling(false);
        if (accepted) setInstalled(true);
    };

    if (installed || !installReady) return null;

    if (compact) {
        return (
            <button
                onClick={handleInstall}
                disabled={installing}
                className="p-2 rounded-lg text-teal-600 hover:bg-teal-50 transition-colors"
                aria-label="Install app"
                title="Install app"
            >
                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="7 10 12 15 17 10" />
                    <line x1="12" y1="15" x2="12" y2="3" />
                </svg>
            </button>
        );
    }

    return (
        <button
            onClick={handleInstall}
            disabled={installing}
            className="hidden sm:inline-flex items-center gap-1.5 rounded-xl bg-teal-50 px-3 py-1.5 text-sm font-semibold text-teal-700 hover:bg-teal-100 transition-colors"
        >
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                <polyline points="7 10 12 15 17 10" />
                <line x1="12" y1="15" x2="12" y2="3" />
            </svg>
            {installing ? 'Installing…' : 'Install app'}
        </button>
    );
}