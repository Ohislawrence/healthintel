import React from 'react';

/**
 * Support WhatsApp number in international format (digits only, no "+").
 */
const WHATSAPP_NUMBER = '2349022239628';

// Prefilled message sent to the support line.
const DEFAULT_MESSAGE = 'Hello HealthIntel, I need help.';

export default function WhatsAppFloat() {
    const href = `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(DEFAULT_MESSAGE)}`;

    return (
        <a
            href={href}
            target="_blank"
            rel="noopener noreferrer"
            aria-label="Chat with us on WhatsApp"
            title="Chat with us on WhatsApp"
            className="fixed z-40 bottom-20 right-4 md:bottom-6 md:right-6 flex items-center justify-center w-12 h-12 md:w-14 md:h-14 rounded-full text-white hover:scale-105 active:scale-95 transition-transform"
            style={{ background: '#25D366', boxShadow: '0 8px 24px rgba(37, 211, 102, 0.4)' }}
        >
            <svg
                viewBox="0 0 24 24"
                className="w-7 h-7 md:w-8 md:h-8"
                fill="currentColor"
                aria-hidden="true"
            >
                <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.87 9.87 0 0 0 4.74 1.21c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2zm0 18.15c-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.26 8.26 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24 2.2 0 4.27.86 5.82 2.42a8.18 8.18 0 0 1 2.41 5.83c0 4.54-3.7 8.23-8.22 8.23zm4.52-6.16c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.12-.17.25-.64.81-.78.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43.12-.14.17-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.43.06-.66.31-.22.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.56.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.14-1.18-.06-.11-.22-.17-.47-.29z" />
            </svg>
        </a>
    );
}