// Unified service worker for Bremac POS
// Use `var` and global self properties to make the script idempotent and
// avoid SyntaxErrors if the file is accidentally concatenated/served twice.
var CACHE_NAME = (self.__BREMAC_CACHE_NAME__ = self.__BREMAC_CACHE_NAME__ || 'bremac-pos-cache-v3');
var ASSETS_TO_CACHE = (self.__BREMAC_ASSETS__ = self.__BREMAC_ASSETS__ || [
  './offline.html',
  './icons/icon-192.png',
  './icons/icon-512.png',
  './css/app.css',
  './js/app.js',
  './favicon.ico'
]);

// Helpful debug log to identify which SW file the browser actually evaluated
try {
  // eslint-disable-next-line no-console
  console.log('Bremac SW loaded', CACHE_NAME);
} catch (e) {}

self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    const cache = await caches.open(CACHE_NAME);
    // Add assets one-by-one and ignore failures so install doesn't fail entirely
    await Promise.all(ASSETS_TO_CACHE.map(async (url) => {
      try {
        // Use Request to preserve relative resolution
        const req = new Request(url, { credentials: 'same-origin' });
        const res = await fetch(req);
        if (res && res.ok) {
          await cache.put(req, res.clone());
        } else {
          // skip non-ok responses
          console.warn('SW: skipping cache of', url, 'status:', res && res.status);
        }
      } catch (e) {
        // network or CORS error, skip this asset
        console.warn('SW: failed to cache', url, e);
      }
    }));
  })());
  // Activate the new worker as soon as it's finished installing
  try { self.skipWaiting(); } catch (e) { /* noop */ }
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  event.respondWith(
    (async () => {
      const req = event.request;

      const cached = await caches.match(req);
      if (cached) return cached;

      try {
        return await fetch(req);
      } catch (err) {
        // Only show the offline page for navigations / HTML documents.
        const accept = (req.headers && req.headers.get && req.headers.get('accept')) || '';
        const wantsHtml = req.mode === 'navigate' || accept.includes('text/html');

        if (wantsHtml) {
          const offline = await caches.match('./offline.html');
          if (offline) return offline;
        }

        // For API/XHR/fetch calls, return a valid Response so the promise resolves.
        return new Response('', {
          status: 503,
          statusText: 'Service Unavailable',
        });
      }
    })()
  );
});
