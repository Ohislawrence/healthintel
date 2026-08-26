import axios from 'axios';

const TOKEN_KEY = 'labdoc_token';
const endpoint = '/api/error-report';

let queue = [];
let flushing = false;

function send(payload) {
    try {
        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                Authorization: localStorage.getItem(TOKEN_KEY)
                    ? `Bearer ${localStorage.getItem(TOKEN_KEY)}`
                    : '',
            },
            body: JSON.stringify(payload),
            keepalive: true,
        }).catch(() => {});
    } catch {
        // Never let error reporting itself throw.
    }
}

function flush() {
    if (flushing || queue.length === 0) return;
    flushing = true;
    const batch = queue.splice(0, queue.length);
    batch.forEach(send);
    flushing = false;
}

function report(payload) {
    queue.push(payload);
    if (queue.length >= 5) {
        flush();
    } else {
        // Throttle to avoid flooding the network during a burst of errors.
        setTimeout(flush, 1000);
    }
}

export function captureError(type, message, context = {}) {
    report({
        level: 'error',
        source: 'frontend',
        type,
        message: String(message || 'Unknown error').slice(0, 5000),
        context,
        url: window.location.href,
        occurred_at: new Date().toISOString(),
    });
}

export function initErrorReporting() {
    // Global uncaught errors + unhandled promise rejections
    window.addEventListener('error', (event) => {
        if (!event.error || !event.message) return;
        captureError(event.error.name || 'Error', event.message, {
            file: event.filename,
            line: event.lineno,
            col: event.colno,
        });
    });

    window.addEventListener('unhandledrejection', (event) => {
        const reason = event.reason;
        const message =
            (reason && reason.message) ||
            (typeof reason === 'string' ? reason : 'Unhandled promise rejection');
        captureError((reason && reason.name) || 'UnhandledRejection', message, {
            stack: reason && reason.stack ? String(reason.stack).slice(0, 2000) : null,
        });
    });

    // React Query / axios API errors are captured via the api interceptor,
    // but add a global fallback for any axios errors that escape.
    axios.interceptors.response.use(
        (response) => response,
        (error) => {
            if (error && error.response?.status >= 500) {
                captureError('APIError', `[${error.response.status}] ${error.config?.url || 'request'}`, {
                    status: error.response.status,
                    url: error.config?.url,
                });
            }
            return Promise.reject(error);
        },
    );
}

export default { captureError, initErrorReporting };