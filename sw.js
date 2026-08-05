/* ═══════════════════════════════════════════════════════════════════
   sw.js — que la interfaz cargue aunque la red vaya mal

   En una casa rural el router se cae. En un bajo con paredes gruesas la
   wifi va y viene. Lo que no puede pasar es que en ese momento la
   aplicación salga sin estilos y sin iconos, que era exactamente lo que
   ocurría cuando esta lista se quedaba corta.

   ── Lo que NO se cachea, y por qué ──────────────────────────────────
     api/          el estado tiene que estar fresco siempre; una cola de
                   hace cinco minutos es peor que ninguna cola
     data/         los vídeos ya están en disco; duplicarlos llenaría el
                   navegador para nada
     pruebas/      la suite se ejecuta contra lo que hay, no contra una
                   copia de ayer
     otro origen   YouTube y las carátulas van directas

   ── La trampa de esta lista ─────────────────────────────────────────
   Si se añade un archivo al proyecto y no se añade aquí, no pasa nada…
   hasta la noche que falla el router. Entonces falta justo ese. Por eso
   hay una prueba que compara esta lista con lo que piden de verdad
   index.html y proyector.php, y falla si alguno se ha quedado fuera.

   Y al tocar la lista hay que **subir el número de la caché**: el
   navegador solo se trae la lista nueva si cambia el nombre. Si no, la
   gente se queda con la versión vieja sin enterarse — que es peor que no
   cachear nada, porque además no se nota.
   ═══════════════════════════════════════════════════════════════════ */

/* v5 — el proyecto se partió en módulos (comandos, señales, canción,
   actuación, cronómetro) y la pantalla del público en siete piezas.
   Ninguna de las trece estaba en la lista.

   v7 — el rename a OpenKaraoke Center. Cambiar el nombre de la caché aquí
   no tiene riesgo: `activate` borra TODAS las que no se llamen como esta,
   filtrando por diferencia y no por prefijo. Si filtrara por prefijo, la
   `karaoke-v6` de todo el mundo se quedaría ocupando disco para siempre y
   nadie lo notaría nunca. */
const CACHE = 'okc-v27';

const BASE = [
  './', './index.html', './pedir.php', './proyector.php',

  './css/base.css', './css/operador.css', './css/ahora.css', './css/interpretacion.css',
  './css/proyector.css', './css/temas.css',

  './js/simbolos.js', './js/qr.js', './js/estados.js', './js/nucleo.js', './js/idioma.js',
  './js/senales.js', './js/cancion.js', './js/actuacion.js',
  './js/almacen-servidor.js', './js/comandos.js', './js/interfaz.js', './js/filtro.js', './js/fase.js',
  './js/busqueda.js', './js/cola.js', './js/reproductor.js',
  './js/cronometro.js', './js/evento.js', './js/atajos.js', './js/copiloto.js', './js/app.js',
  './js/termometro.js', './js/mc.js', './js/diagnostico.js', './js/paneles.js', './js/instalar.js', './js/ambiente.js', './js/textos.js', './js/show.js',
  /* Las cartas de reto son un archivo de datos, pero se cachea igual: sin
     él, con el router caído el Modo Show se queda sin botón. */
  './retos.json', './ejemplos.json', './mc.json', './idiomas/en.json',
  /* Los sonidos del maestro de ceremonias. Se cachean porque su momento
     es justo el hueco entre dos canciones, que es cuando peor va la red
     —el vídeo siguiente se está cargando— y un aplauso que llega dos
     segundos tarde no es un aplauso. */
  './assets/mc/aplausos.mp3', './assets/mc/redoble.mp3',
  './assets/mc/fanfarria.mp3', './assets/mc/remate.mp3',
  './assets/mc/campana.mp3', './assets/mc/tension.mp3',

  './js/proyector/ajustes.js', './js/proyector/reproductor.js',
  './js/proyector/audio.js', './js/proyector/carteles.js',
  './js/proyector/escenas.js', './js/proyector/servidor.js',

  './manifest.webmanifest', './manifest-tele.webmanifest',
  './manifest-pedir.webmanifest',
  './iconos/icono.svg',
  './iconos/icono-192.png', './iconos/icono-512.png',
  './iconos/icono-tele-192.png', './iconos/icono-tele-512.png',
  './iconos/icono-pedir-192.png', './iconos/icono-pedir-512.png'
];

self.addEventListener('install', e => {
  /* Uno a uno en vez de `addAll`: con `addAll`, si UN archivo falla —un
     404 por una errata en la lista— se cae la instalación entera y el
     service worker no llega a existir. Un fallo tonto dejaba sin caché a
     todo lo demás. Así se instala lo que hay y lo que falte se dice por
     consola. */
  e.waitUntil(
    caches.open(CACHE)
      .then(c => Promise.all(BASE.map(u =>
        c.add(u).catch(err => console.warn('[sw] no he podido cachear', u, err))
      )))
      .then(() => self.skipWaiting())
  );
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
  if (u.pathname.includes('/api/')) return;          // el estado, siempre fresco
  if (u.pathname.includes('/data/')) return;         // vídeos y estado en disco
  if (u.pathname.includes('/pruebas/')) return;      // la suite, nunca en caché

  /* Red primero, caché como red de seguridad. Al revés —caché primero—
     iría más rápido, pero estarías mirando una versión vieja sin saberlo,
     y eso cuesta más tiempo del que ahorra. */
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
