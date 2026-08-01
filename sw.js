/* Service worker: la interfaz carga aunque la red vaya mal.
   Los vídeos NO se cachean (los descargados ya están en disco). */
/* v2 añade la pantalla del televisor. Al cambiar el nombre de la caché,
   el navegador se trae la lista nueva y tira la vieja. */
const CACHE = 'karaoke-v2';
const BASE = [
  './', './index.html', './pedir.php', './proyector.php',
  './manifest.webmanifest', './iconos/icono.svg'
];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(BASE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys()
      .then(k => Promise.all(k.filter(n => n !== CACHE).map(n => caches.delete(n))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', e => {
  const u = new URL(e.request.url);
  if (e.request.method !== 'GET') return;
  if (u.origin !== location.origin) return;          // YouTube, carátulas: directo
  if (u.pathname.includes('/api/')) return;          // estado siempre fresco
  if (u.pathname.includes('/data/videos/')) return;  // vídeos: nunca en caché

  // Red primero, caché como red de seguridad.
  e.respondWith(
    fetch(e.request)
      .then(r => {
        const copia = r.clone();
        caches.open(CACHE).then(c => c.put(e.request, copia)).catch(() => {});
        return r;
      })
      .catch(() => caches.match(e.request).then(r => r || caches.match('./index.html')))
  );
});
