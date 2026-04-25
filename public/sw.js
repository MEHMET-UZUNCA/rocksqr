// Minimal service worker — install + activate (offline cache yok, sadece PWA installable olsun diye)
self.addEventListener('install', (e) => { self.skipWaiting(); });
self.addEventListener('activate', (e) => { e.waitUntil(self.clients.claim()); });
self.addEventListener('fetch', () => {});
