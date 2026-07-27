import React, { useState, useCallback, useRef } from 'react';
import useAuthStore from '../stores/authStore';
import { useNavigate } from 'react-router-dom';

const GOOGLE_CLIENT_ID =
  import.meta.env.VITE_GOOGLE_CLIENT_ID || '__YOUR_GOOGLE_CLIENT_ID__';

// Generate a random nonce for token verification
function generateNonce() {
  return Array.from(crypto.getRandomValues(new Uint8Array(16)))
    .map((b) => b.toString(16).padStart(2, '0'))
    .join('');
}

export default function GoogleSignInButton({ text = 'Sign in with Google' }) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const loginWithGoogle = useAuthStore((s) => s.loginWithGoogle);
  const navigate = useNavigate();
  const popupRef = useRef(null);

  const handleClick = useCallback(() => {
    setError(null);

    // Close any existing popup
    if (popupRef.current && !popupRef.current.closed) {
      popupRef.current.close();
    }

    const nonce = generateNonce();
    const redirectUri = window.location.origin;
    const width = 500;
    const height = 600;
    const left = window.screenX + (window.outerWidth - width) / 2;
    const top = window.screenY + (window.outerHeight - height) / 2;

    // Google OAuth 2.0 Implicit Flow URL
    const authUrl =
      `https://accounts.google.com/o/oauth2/v2/auth?` +
      `client_id=${encodeURIComponent(GOOGLE_CLIENT_ID)}` +
      `&redirect_uri=${encodeURIComponent(redirectUri)}` +
      `&response_type=id_token` +
      `&scope=${encodeURIComponent('openid profile email')}` +
      `&nonce=${nonce}` +
      `&prompt=select_account`;

    const popup = window.open(
      authUrl,
      'google-signin',
      `width=${width},height=${height},left=${left},top=${top},toolbar=no,menubar=no`,
    );

    popupRef.current = popup;

    if (!popup) {
      setError(
        'Pop-up was blocked. Please allow pop-ups for this site and try again.',
      );
      return;
    }

    // Poll for the popup to redirect back to our origin with id_token in the hash
    const pollTimer = setInterval(() => {
      try {
        // Check if popup has redirected back to our domain
        if (popup.closed) {
          clearInterval(pollTimer);
          setError('Sign-in window was closed. Please try again.');
          return;
        }

        // Try to read the hash from the popup (only works if it's on the same origin)
        let hash;
        try {
          hash = popup.location.hash;
        } catch {
          // Cross-origin: popup hasn't returned to our domain yet
          return;
        }

        if (hash) {
          clearInterval(pollTimer);

          const params = new URLSearchParams(hash.substring(1));
          const idToken = params.get('id_token');

          if (idToken) {
            popup.close();
            handleToken(idToken);
          } else {
            const errorParam = params.get('error');
            popup.close();
            setError(errorParam || 'Google sign-in was cancelled.');
          }
        }
      } catch {
        // Ignore cross-origin errors during polling
      }
    }, 500);

    // Timeout after 3 minutes
    setTimeout(() => {
      clearInterval(pollTimer);
      if (popup && !popup.closed) {
        popup.close();
      }
    }, 180000);
  }, [loginWithGoogle, navigate]);

  const handleToken = async (idToken) => {
    setLoading(true);
    setError(null);
    try {
      await loginWithGoogle(idToken);
      const user = useAuthStore.getState().user;
      if (user?.roles?.includes('admin')) {
        navigate('/admin');
      } else {
        navigate('/dashboard');
      }
    } catch (err) {
      console.error('Google sign-in failed:', err);
      setError(err?.message || 'Google sign-in failed. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      {error && (
        <div className="mb-3 rounded-xl bg-danger-50 border border-danger-200 px-3 py-2 text-xs text-danger-700 font-medium text-center">
          {error}
        </div>
      )}

      <button
        type="button"
        onClick={handleClick}
        disabled={loading}
        className="w-full flex items-center justify-center gap-3 rounded-xl border border-neutral-300 bg-white px-4 py-2.5 text-sm font-semibold text-neutral-700 shadow-xs hover:bg-neutral-50 transition disabled:opacity-60"
      >
        {loading ? (
          <>
            <svg
              className="h-4 w-4 animate-spin text-neutral-500"
              viewBox="0 0 24 24"
              fill="none"
            >
              <circle
                className="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                strokeWidth="4"
              />
              <path
                className="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
              />
            </svg>
            Signing in...
          </>
        ) : (
          <>
            <svg
              className="h-5 w-5"
              viewBox="0 0 24 24"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"
                fill="#4285F4"
              />
              <path
                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                fill="#34A853"
              />
              <path
                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                fill="#FBBC05"
              />
              <path
                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                fill="#EA4335"
              />
            </svg>
            {text}
          </>
        )}
      </button>
    </div>
  );
}