/* ═══════════════════════════════════════════════════════════════════
   nucleo.js — lo que necesita todo el mundo

   Primer archivo que se carga. Crea el espacio KL y deja dentro el
   estado en memoria, las utilidades y las preferencias del aparato.

   Es un script clásico, no un módulo ES, y eso es deliberado: los
   módulos ES no se pueden pegar unos detrás de otros, y la versión
   Lite tiene que seguir siendo un único archivo HTML. Cargados por
   separado con <script src> o concatenados en un solo archivo, estos
   se comportan igual.

   Regla: aquí no se toca el DOM ni se habla con el servidor.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

var KL = window.KL || {};
window.KL = KL;

/* ---- Utilidades ------------------------------------------------------ */

KL.$  = s => document.querySelector(s);
KL.$$ = s => [...document.querySelectorAll(s)];

KL.uid = () => Date.now().toString(36) + Math.random().toString(36).slice(2, 7);

/* Segundos a 3:07 o a 1:02:30. */
KL.fmt = function (s) {
  s = Math.max(0, Math.floor(s || 0));
  const h = s / 3600 | 0, m = s % 3600 / 60 | 0, x = s % 60;
  return h ? `${h}:${String(m).padStart(2, '0')}:${String(x).padStart(2, '0')}`
           : `${m}:${String(x).padStart(2, '0')}`;
};

/* La duración de YouTube viene como PT3M7S. */
KL.iso = function (t) {
  const m = /^PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?$/.exec(t || '');
  return m ? (+m[1] || 0) * 3600 + (+m[2] || 0) * 60 + (+m[3] || 0) : 0;
};

/* Los títulos de YouTube llegan con comillas y símbolos: se escapa
   todo lo que venga de fuera antes de meterlo en el HTML. */
KL.esc = s => String(s ?? '').replace(/[&<>"']/g,
  c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

KL.unesc = s => String(s ?? '')
  .replace(/&amp;/g, '&').replace(/&quot;/g, '"').replace(/&#39;/g, "'")
  .replace(/&lt;/g, '<').replace(/&gt;/g, '>');

KL.miniatura = id => `https://i.ytimg.com/vi/${id}/mqdefault.jpg`;

/* ---- Estado en memoria ----------------------------------------------
   Lo que la interfaz está enseñando ahora mismo. Quién lo guarda en
   disco es cosa del almacén: este objeto no sabe si detrás hay un
   servidor o el navegador.                                            */

KL.estado = {
  apiKey: '', suffix: 'karaoke',
  openVideo: true, alsoStar: false,
  quality: 'medium', antiCut: true, view: 'full', libCol: true,
  avisoSig: 'der',        // esquina del aviso «después canta»: der, izq o no
  library: [], seeded: false,
  queue: [],
  curId: null,            // pista de la cola en reproducción
  autoNext: true,
  filterLib: '',
  results: [], sel: null
};

/* ---- Preferencias del aparato ---------------------------------------
   Estas NO se comparten: la calidad de vídeo o la vista que prefieres
   son tuyas, no de la fiesta. Por eso van en localStorage y no en el
   estado compartido.                                                   */

KL.prefs = (function () {
  const CLAVE = 'karaoke_launcher_v1';
  const S = KL.estado;

  function cargar() {
    try {
      const d = JSON.parse(localStorage.getItem(CLAVE) || 'null');
      if (!d) return;
      S.quality   = d.quality || 'medium';
      S.antiCut   = d.antiCut !== false;
      S.openVideo = d.openVideo !== false;
      S.view      = d.view || 'full';
      S.libCol    = d.libCol !== false;
      S.autoNext  = d.autoNext !== false;
      if (['der', 'izq', 'no'].includes(d.avisoSig)) S.avisoSig = d.avisoSig;
    } catch (e) { /* navegador sin localStorage: se usan los valores por defecto */ }
  }

  function guardar() {
    try {
      localStorage.setItem(CLAVE, JSON.stringify({
        quality: S.quality, antiCut: S.antiCut, openVideo: S.openVideo,
        view: S.view, autoNext: S.autoNext, libCol: S.libCol,
        avisoSig: S.avisoSig
      }));
    } catch (e) { /* modo privado: no se recuerdan, pero se puede usar igual */ }
  }

  return { cargar, guardar };
})();
