/**
 * HealthIntel PWA Registration & Push Notification Manager
 *
 * Handles:
 * - Service Worker registration
 * - Push notification subscription management
 * - PWA install prompt
 * - Offline/Online status tracking
 * - Notification click handling
 */

// Will be fetched from server
let VAPID_PUBLIC_KEY = null;

// ── State ────────────────────────────────────────────────────
let swRegistration = null;
let deferredPrompt = null;
let notificationPermission = 'default';

/**
 * Fetch the VAPID public key from the server.
 */
async function fetchVapidKey() {
  if (VAPID_PUBLIC_KEY) return VAPID_PUBLIC_KEY;

  try {
    const resp = await fetch('/api/push/vapid-public-key');
    if (resp.ok) {
      const data = await resp.json();
      VAPID_PUBLIC_KEY = data?.publicKey || data?.data?.publicKey || data?.ok?.publicKey;
      if (VAPID_PUBLIC_KEY) {
        console.log('[PWA] VAPID key fetched from server');
        return VAPID_PUBLIC_KEY;
      }
    }
  } catch (e) {
    console.warn('[PWA] Could not fetch VAPID key from server:', e);
  }

  // Fallback — this key must match config/webpush.php
  VAPID_PUBLIC_KEY = window.HEALTHINTEL_VAPID_KEY || '';
  return VAPID_PUBLIC_KEY;
}

// ── Utility: Convert base64 to Uint8Array for VAPID ─────────
function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);
  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  return outputArray;
}

// ── Utility: Convert an ArrayBuffer/BufferSource to base64url ─
function arrayBufferToBase64Url(buffer) {
  const bytes = new Uint8Array(buffer);
  let binary = '';
  for (let i = 0; i < bytes.length; i++) {
    binary += String.fromCharCode(bytes[i]);
  }
  return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

// ── API Helper ──────────────────────────────────────────────
async function apiPost(url, data) {
  const token = localStorage.getItem('labdoc_token');
  const headers = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  };
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }
  const response = await fetch(url, {
    method: 'POST',
    headers,
    body: JSON.stringify(data),
    credentials: 'include',
  });
  if (!response.ok) {
    throw new Error(`API error: ${response.status}`);
  }
  return response.json();
}

// ── Register Service Worker ─────────────────────────────────
export async function registerSW() {
  if (!('serviceWorker' in navigator)) {
    console.warn('[PWA] Service Workers not supported');
    return null;
  }

  if (swRegistration) {
    return swRegistration;
  }

  try {
    swRegistration = await navigator.serviceWorker.register('/sw.js', {
      scope: '/',
      updateViaCache: 'none',
    });

    console.log('[PWA] Service Worker registered:', swRegistration.scope);

    // Handle SW updates
    swRegistration.addEventListener('updatefound', () => {
      const newWorker = swRegistration.installing;
      if (!newWorker) return;

      newWorker.addEventListener('statechange', () => {
        if (
          newWorker.state === 'installed' &&
          navigator.serviceWorker.controller
        ) {
          console.log('[PWA] New content available - please refresh');
          window.dispatchEvent(
            new CustomEvent('pwa:update-available', {
              detail: { registration: swRegistration },
            })
          );
        }
      });
    });

    // Listen for messages from SW (notification clicks, etc.)
    navigator.serviceWorker.addEventListener('message', (event) => {
      if (event.data?.type === 'NOTIFICATION_CLICK') {
        const url = event.data.url || '/dashboard';
        if (window.location.pathname !== url) {
          window.location.href = url;
        }
      }
    });

    // If there's already a pending subscription, re-sync it.
    // Guard against environments where pushManager is unavailable
    // (iOS Safari < 16.4, or insecure contexts) to avoid a TypeError.
    navigator.serviceWorker.ready.then((registration) => {
      if (!registration.pushManager) return;
      registration.pushManager.getSubscription().then((sub) => {
        if (sub) {
          notifyServerOfSubscription(sub);
        }
      });
    });

    return swRegistration;
  } catch (error) {
    console.error('[PWA] Service Worker registration failed:', error);
    return null;
  }
}

// ── Install Prompt Management ───────────────────────────────
export function listenForInstallPrompt() {
  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredPrompt = event;
    window.dispatchEvent(
      new CustomEvent('pwa:install-ready', { detail: { prompt: event } })
    );
  });

  window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    console.log('[PWA] App installed successfully');
    window.dispatchEvent(new CustomEvent('pwa:installed'));
  });

  // Fallback: the Blade shell may have captured the prompt before React mounted.
  // Re-dispatch so React components that mount later can still show an install button.
  if (!deferredPrompt && window.__pwaInstallPrompt) {
    deferredPrompt = window.__pwaInstallPrompt;
    window.dispatchEvent(
      new CustomEvent('pwa:install-ready', { detail: { prompt: deferredPrompt } })
    );
  }
}

/**
 * Trigger the install prompt (call this from a UI button)
 */
export async function promptInstall() {
  if (!deferredPrompt && window.__pwaInstallPrompt) {
    deferredPrompt = window.__pwaInstallPrompt;
  }
  if (!deferredPrompt) return false;

  try {
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    deferredPrompt = null;
    window.__pwaInstallPrompt = null;
    return outcome === 'accepted';
  } catch {
    return false;
  }
}

/**
 * Check if the app is already installed
 */
export function isInstalled() {
  return (
    window.matchMedia('(display-mode: standalone)').matches ||
    window.navigator.standalone === true
  );
}

// ── Push Notifications ──────────────────────────────────────
/**
 * Request notification permission and subscribe to push notifications.
 * Returns the subscription object or null.
 */
export async function subscribeToPush() {
  if (!('PushManager' in window)) {
    console.warn('[PWA] Push notifications not supported');
    return null;
  }

  try {
    // Request permission
    const permission = await Notification.requestPermission();
    notificationPermission = permission;

    if (permission !== 'granted') {
      console.warn('[PWA] Notification permission denied');
      return null;
    }

    // Get or wait for service worker registration
    const registration =
      swRegistration || (await navigator.serviceWorker.ready);

    // Fetch VAPID key first — needed to detect key rotation.
    const key = await fetchVapidKey();
    if (!key) {
      console.warn('[PWA] No VAPID public key available — skipping push subscription');
      return null;
    }

    // Check existing subscription.
    // Guard against registration.pushManager being undefined.
    if (!registration.pushManager) {
      console.warn('[PWA] pushManager unavailable on registration');
      return null;
    }
    let subscription = await registration.pushManager.getSubscription();

    // Detect whether the existing subscription was created under a different
    // applicationServerKey. Browsers expose it via subscription.options;
    // fall back to our own localStorage tracking when it isn't available.
    const storedKey = localStorage.getItem('labdoc_vapid_public_key');
    const subKey = subscription?.options?.applicationServerKey
      ? arrayBufferToBase64Url(subscription.options.applicationServerKey)
      : null;

    const keyMismatch =
      (subKey && subKey !== key) ||
      (!subKey && storedKey && storedKey !== key);

    if (subscription && keyMismatch) {
      // The public key changed on the server — the old subscription is now
      // invalid (push services reject it with 403). Discard and re-subscribe.
      console.log('[PWA] VAPID key changed — re-subscribing to push');
      try {
        await apiPost('/api/push/unsubscribe', {
          endpoint: subscription.endpoint,
        }).catch(() => {});
        await subscription.unsubscribe().catch(() => {});
      } catch (e) {
        console.warn('[PWA] Failed to clear stale subscription:', e);
      }
      subscription = null;
    }

    if (subscription) {
      localStorage.setItem('labdoc_vapid_public_key', key);
      console.log('[PWA] Already subscribed to push');
      return subscription;
    }

    // Subscribe
    subscription = await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(key),
    });

    console.log('[PWA] Push subscription created');
    localStorage.setItem('labdoc_vapid_public_key', key);

    // Send subscription to server
    await notifyServerOfSubscription(subscription);

    return subscription;
  } catch (error) {
    console.error('[PWA] Push subscription failed:', error);
    return null;
  }
}

/**
 * Unsubscribe from push notifications and notify server.
 */
export async function unsubscribeFromPush() {
  try {
    const registration =
      swRegistration || (await navigator.serviceWorker.ready);

    if (!registration.pushManager) {
      return true;
    }

    const subscription = await registration.pushManager.getSubscription();
    if (!subscription) {
      console.log('[PWA] No subscription to unsubscribe');
      return true;
    }

    await subscription.unsubscribe();
    console.log('[PWA] Unsubscribed from push');

    // Notify server to remove subscription
    await apiPost('/api/push/unsubscribe', {
      endpoint: subscription.endpoint,
    }).catch(() => {});

    return true;
  } catch (error) {
    console.error('[PWA] Unsubscribe failed:', error);
    return false;
  }
}

/**
 * Check current push notification permission status.
 */
export function getNotificationPermission() {
  if ('Notification' in window) {
    return Notification.permission;
  }
  return 'denied';
}

/**
 * Determine if the user has granted notification permission.
 */
export function hasNotificationPermission() {
  return getNotificationPermission() === 'granted';
}

// ── Internal: Post subscription to server ───────────────────
async function notifyServerOfSubscription(subscription) {
  try {
    await apiPost('/api/push/subscribe', {
      subscription: subscription.toJSON(),
    });
    console.log('[PWA] Subscription saved to server');
  } catch (err) {
    console.warn('[PWA] Failed to save subscription to server:', err);
  }
}

// ── Offline/Online Status ───────────────────────────────────
export function initNetworkListeners() {
  const notify = (online) => {
    window.dispatchEvent(
      new CustomEvent('pwa:network-change', {
        detail: { online },
      })
    );
  };

  window.addEventListener('online', () => notify(true));
  window.addEventListener('offline', () => notify(false));
}

/**
 * Check current online status.
 */
export function isOnline() {
  return navigator.onLine;
}

// ── Initialize Everything ───────────────────────────────────
export async function initPWA() {
  await registerSW();
  listenForInstallPrompt();
  initNetworkListeners();

  // Auto-subscribe to push if user is already logged in and has permission
  if (hasNotificationPermission() && localStorage.getItem('labdoc_token')) {
    await subscribeToPush();
  }
}

export default {
  registerSW,
  initPWA,
  subscribeToPush,
  unsubscribeFromPush,
  promptInstall,
  isInstalled,
  isOnline,
  getNotificationPermission,
  hasNotificationPermission,
  listenForInstallPrompt,
};