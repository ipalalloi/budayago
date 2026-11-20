const CACHE = 'budayago-shell-v008-1';
const shell = ['assets/heritage.css', 'assets/heritage.js', 'assets/icon-192.png', 'assets/icon-512.png', 'offline.html'];
const urls = shell.map(path => new URL(path, self.registration.scope).href);
self.addEventListener('install', event => {
  event.waitUntil(caches.open(CACHE).then(cache => cache.addAll(urls)));
});
self.addEventListener('activate', event => {
  event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(key =>
    key.startsWith('budayago-shell-') && key !== CACHE).map(key => caches.delete(key)))).then(() => self.clients.claim()));
});
self.addEventListener('fetch', event => {
  const req = event.request;
  if (req.method !== 'GET') return;
  if (urls.includes(req.url)) {
    event.respondWith(caches.open(CACHE).then(async cache => (await cache.match(req)) || fetch(req)));
  } else if (req.mode === 'navigate' && new URL(req.url).origin === self.location.origin) {
    event.respondWith(fetch(req).catch(() => caches.open(CACHE).then(cache => cache.match(urls[4]))));
  }
});
