// ── SERVICE WORKER — Anime Store ──────────────────────────────
// Red primero: siempre muestra datos frescos (precios, stock, carrito).
// Si no hay conexión, muestra la última versión guardada de la página.

const CACHE = 'animestore-v1';

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys()
            .then(keys => Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (e) => {
    const req = e.request;
    // Solo GET de nuestra propia web; nunca la API ni el admin
    if (req.method !== 'GET' || new URL(req.url).origin !== location.origin) return;
    if (/\/(api|admin)\//.test(req.url)) return;

    e.respondWith(
        fetch(req)
            .then(res => {
                if (res.ok && req.mode === 'navigate') {
                    const copia = res.clone();
                    caches.open(CACHE).then(c => c.put(req, copia));
                }
                return res;
            })
            .catch(() => caches.match(req))
    );
});
