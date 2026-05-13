const CACHE_NAME = 'flut-crm-v3';
const OFFLINE_URL = '/offline';

// Assets to pre-cache
const PRE_CACHE = [
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
    '/images/logo-flut.webp',
];

// Install: pre-cache essential assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(PRE_CACHE))
    );
    self.skipWaiting();
});

// Activate: clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

// Fetch: network-first for pages, cache-first for assets
self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Skip non-GET and external requests
    if (request.method !== 'GET' || !request.url.startsWith(self.location.origin)) return;

    // Static assets: cache-first
    if (request.url.match(/\.(png|jpg|jpeg|webp|svg|css|js|woff2?)(\?|$)/)) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request).then((response) => {
                const clone = response.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                return response;
            }))
        );
        return;
    }

    // Pages: network-first
    event.respondWith(
        fetch(request).catch(() => caches.match(request))
    );
});

// Push notifications
self.addEventListener('push', (event) => {
    console.log('[SW] Push received');
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch(e) {
        data = { title: 'CRM Flut', body: event.data ? event.data.text() : 'Nova mensagem' };
    }

    const title = data.title || 'CRM Flut';
    const body = data.body || 'Nova mensagem recebida';
    const options = {
        body: body,
        icon: '/icons/icon-192x192.png',
        badge: '/icons/icon-72x72.png',
        vibrate: [200, 100, 200],
        data: { url: data.url || '/chat' },
        tag: 'crm-' + title.replace(/[^a-zA-Z0-9]/g, '').substring(0, 20),
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Notification click: open the URL
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || '/';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (const client of windowClients) {
                if (client.url.includes(url) && 'focus' in client) return client.focus();
            }
            return clients.openWindow(url);
        })
    );
});
