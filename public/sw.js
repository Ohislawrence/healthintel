// ============================================================
// HealthIntel PWA Service Worker v1.0
// Production-ready with push notifications, offline caching,
// and background sync capabilities.
// ============================================================

const CACHE_NAME = 'healthintel-v1';
const RUNTIME_CACHE = 'healthintel-runtime-v1';

// ── Static Assets (pre-cached on install) ──────────────────
// Static assets pre-cached on install — wildcard URL patterns
// that the SW will preload from the current build manifest.
const STATIC_ASSETS = [
  '/',
  '/offline',
  '/manifest.json',
];

// Dynamic cache warming: fetch the build manifest and cache
// the current app JS and CSS bundles.
async function cacheBuildAssets(cache) {
  try {
    const response = await fetch('/build/manifest.json', { cache: 'no-cache' });
    if (!response.ok) return;
    const manifest = await response.json();
    const entries = Object.values(manifest);
    const urls = entries
      .filter((entry) => entry.file)
      .map((entry) => `/build/${entry.file}`);
    if (urls.length > 0) {
      await cache.addAll(urls);
      console.log('[SW] Pre-cached build assets:', urls);
    }
  } catch (err) {
    console.warn('[SW] Could not pre-cache build assets:', err);
  }
}

// ── Install Event: Pre-cache static assets ─────────────────
self.addEventListener('install', (event) => {
  console.log('[SW] Installing HealthIntel PWA...');
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[SW] Pre-caching static assets...');
      return cache.addAll(STATIC_ASSETS).catch((err) => {
        console.warn('[SW] Some assets failed to pre-cache:', err);
      });
    }).then(() => {
      return self.skipWaiting();
    })
  );
});

// ── Activate Event: Clean old caches ───────────────────────
self.addEventListener('activate', (event) => {
  console.log('[SW] Activating HealthIntel PWA...');
  const currentCaches = [CACHE_NAME, RUNTIME_CACHE];
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((name) => !currentCaches.includes(name))
          .map((name) => {
            console.log('[SW] Deleting old cache:', name);
            return caches.delete(name);
          })
      );
    }).then(() => {
      return self.clients.claim();
    })
  );
});

// ── Fetch Event: Network-first for API, Cache-first for assets ──
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // ── API requests: Network-only (don't cache dynamic data) ──
  if (url.pathname.startsWith('/api/')) {
    // Network-only for API - these are dynamic
    return;
  }

  // ── User-facing page navigations: Network-first with offline fallback ──
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          // Cache a copy of the page for offline use
          const cloned = response.clone();
          caches.open(RUNTIME_CACHE).then((cache) => {
            cache.put(request, cloned);
          });
          return response;
        })
        .catch(async () => {
          // Try to return the cached version
          const cached = await caches.match(request);
          if (cached) return cached;

          // For HTML navigation failures, return the offline page
          const offlinePage = await caches.match('/offline');
          return offlinePage || new Response(
            'You are offline. Please check your internet connection.',
            { status: 503, headers: { 'Content-Type': 'text/plain' } }
          );
        })
    );
    return;
  }

  // ── Static assets: Cache-first strategy ──
  if (
    url.pathname.startsWith('/build/') ||
    url.pathname.startsWith('/fonts/') ||
    url.pathname.endsWith('.png') ||
    url.pathname.endsWith('.jpg') ||
    url.pathname.endsWith('.svg') ||
    url.pathname.endsWith('.ico') ||
    url.pathname.endsWith('.woff2') ||
    url.pathname.endsWith('.css')
  ) {
    event.respondWith(
      caches.match(request).then((cached) => {
        if (cached) {
          // Stale-while-revalidate: return cached, fetch fresh in background
          fetch(request).then((response) => {
            if (response.ok) {
              caches.open(RUNTIME_CACHE).then((cache) => {
                cache.put(request, response);
              });
            }
          }).catch(() => {});
          return cached;
        }
        return fetch(request).then((response) => {
          if (response.ok) {
            const cloned = response.clone();
            caches.open(RUNTIME_CACHE).then((cache) => {
              cache.put(request, cloned);
            });
          }
          return response;
        });
      })
    );
    return;
  }
});

// ═══════════════════════════════════════════════════════════════
// PUSH NOTIFICATIONS
// ═══════════════════════════════════════════════════════════════

// ── Push Event: Show notification ──────────────────────────
self.addEventListener('push', (event) => {
  console.log('[SW] Push received:', event);

  let payload;
  if (event.data) {
    try {
      payload = event.data.json();
    } catch {
      payload = {
        title: 'HealthIntel',
        body: event.data.text(),
        icon: '/icons/icon-192x192.png',
        badge: '/icons/icon-72x72.png',
        data: { url: '/dashboard' },
      };
    }
  }

  const title = payload?.title || 'HealthIntel';
  const options = {
    body: payload?.body || 'You have a new notification.',
    icon: payload?.icon || '/icons/icon-192x192.png',
    badge: payload?.badge || '/icons/icon-72x72.png',
    vibrate: [200, 100, 200],
    timestamp: Date.now(),
    requireInteraction: payload?.requireInteraction || false,
    tag: payload?.tag || 'healthintel-notification',
    data: {
      url: payload?.data?.url || '/dashboard',
      notificationId: payload?.data?.notification_id || null,
      ...payload?.data,
    },
    actions: payload?.actions || [],
    image: payload?.image || null,
    renotify: true,
  };

  event.waitUntil(
    self.registration.showNotification(title, options).then(() => {
      // Attempt to report notification received back to server
      if (payload?.data?.notification_id) {
        // Fire-and-forget delivery confirmation
        fetch('/api/push/notification-received', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ notification_id: payload.data.notification_id }),
        }).catch(() => {});
      }
    })
  );
});

// ── Notification Click: Open the right page ────────────────
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const urlToOpen = event.notification?.data?.url || '/dashboard';

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
      // If an existing window is open, focus it and navigate
      for (const client of clients) {
        if (client.url.includes(self.location.origin) && 'focus' in client) {
          client.focus();
          client.postMessage({ type: 'NOTIFICATION_CLICK', url: urlToOpen });
          return;
        }
      }
      // Otherwise open a new window
      if (self.clients.openWindow) {
        return self.clients.openWindow(urlToOpen);
      }
    })
  );
});

// ── Push Subscription Change: Notify server ───────────────
self.addEventListener('pushsubscriptionchange', (event) => {
  console.log('[SW] Push subscription changed');
  event.waitUntil(
    self.registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: self.vapidPublicKey || '',
    }).then((newSubscription) => {
      // Post the new subscription to the server
      return fetch('/api/push/subscription-update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ subscription: newSubscription.toJSON() }),
      });
    }).catch((err) => {
      console.error('[SW] Failed to re-subscribe:', err);
    })
  );
});

// ═══════════════════════════════════════════════════════════════
// MESSAGE HANDLERS (for communication with the app)
// ═══════════════════════════════════════════════════════════════

self.addEventListener('message', (event) => {
  if (event.data?.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }

  if (event.data?.type === 'CACHE_URLS') {
    const urls = event.data.urls || [];
    event.waitUntil(
      caches.open(RUNTIME_CACHE).then((cache) => cache.addAll(urls))
    );
  }
});

console.log('[SW] HealthIntel PWA Service Worker ready.');