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

/* Un icono del sprite local, listo para meter en una plantilla.
   Nunca se enlazan iconos de un CDN: la aplicación tiene que verse con
   el router caído, que es justo la noche en que más falta hace. */
KL.icono = (id, clase) =>
  `<svg class="ic${clase ? ' ' + clase : ''}" aria-hidden="true"><use href="#ic-${id}"></use></svg>`;

/* ---- Estados del evento ---------------------------------------------
   La aplicación no cambia de pantallas: cambia de estado, y cada
   superficie —el monitor del PC y la tele— decide qué pinta a partir de
   él. Ver js/evento.js.                                                */

KL.EV = {
  ESPERA:         'ESPERA',          // nadie cantando, el operador manda
  PREPARADA:      'PREPARADA',       // pista cargada, esperando el Play
  LLAMADA:        'LLAMADA',         // cuenta atrás: que al cantante le dé tiempo
  INTERPRETACION: 'INTERPRETACION',  // sonando
  FIN_ACTUACION:  'FIN_ACTUACION'    // los segundos entre canción y canción
};

/* ---- Estado en memoria ----------------------------------------------
   Lo que la interfaz está enseñando ahora mismo. Quién lo guarda en
   disco es cosa del almacén: este objeto no sabe si detrás hay un
   servidor o el navegador.                                            */

KL.estado = {
  apiKey: '', suffix: 'karaoke',
  openVideo: true, alsoStar: false,
  quality: 'medium', antiCut: true, view: 'full', libCol: true,
  avisoSig: 'der',        // esquina del aviso «después canta»: der, izq o no
  modo: 'karaoke',        // karaoke | dj — y nada más (ver js/filtro.js)
  /* Un filtro por espacio, editable y A LA VISTA desde la v1.2: antes
     era un «sufijo» escondido en Ajustes, que es lo mismo que no existir.
     DJ vacío a propósito: quien pincha quiere la canción, no una versión
     de nada. Se guardan los dos, no solo el del espacio activo, para que
     cambiar de espacio y volver no obligue a reescribirlo. */
  sufijos: { karaoke:'karaoke', dj:'' },
  /* Los perfiles que guarda el operador, por espacio. Los de fábrica NO
     están aquí: viven en js/filtro.js, que es quien sabe de esto. Aquí
     solo lo suyo, que es lo único que hay que recordar. */
  perfiles: { karaoke:[], dj:[] },
  /* Los aplausos duraban 15 segundos y sobraban: la siguiente queda
     PREPARADA y ahí se espera al operador, así que ese rato no lo decide
     el reloj, lo decide él. Cinco bastan para el aplauso. */
  segFin: 5,
  /* La cuenta atrás va APAGADA por defecto, y es un cambio de criterio.
     Un 3-2-1 en la pantalla le dice al cantante «tienes que empezar a
     cantar CUANDO ESTO LLEGUE A CERO», y casi nadie está listo: todavía
     está cogiendo el micro, saludando, o esperando a que la gente se
     calle. Ese agobio no lo pone la canción, lo pone el contador.

     El estado PREPARADA ya hace ese trabajo sin prisa. El operador mira,
     ve que la persona está lista, y pulsa Empezar. Quien la quiera puede
     volver a activarla en los ajustes. */
  segLlamada: 0,
  retirar: true,          // ¿sale de la cola la canción ya cantada?
  calentamiento: 0,       // el rato de antes de empezar: encendido o no
  descargas: {},          // vídeos bajándose ahora mismo: {videoId:{pct}}
  cortes: 0,              // veces que el antiparones ha tenido que actuar
  avisoRedHasta: 0,       // hasta cuándo no se vuelve a insistir
  avisoCopilotoHasta: 0,  // ídem, para el aviso de "hace rato que no piden"
  ultimaAnadida: Date.now(), // cuándo entró la última canción a la cola de este aparato
  paneles: null,          // cartelones activos en la tele; null = todos
  show: { reto: null },   // Modo Show: la carta que está en la tele
  wifi: { ssid:'', clave:'' },  // solo para dibujar el QR de conexión
  termometro: { on:true, niveles:[] },  // el pulso de la fiesta en la tele
  ambienteOn: false,      // Cabina DJ encendida en ESTE aparato
  sonidoEn: 'pc',         // 'pc' o 'tele': por dónde salen los altavoces
  /* Antes 0,7: los segundos que la tele tardaba en arrancar. Ya no hace
     falta compensar eso —la tele salta a donde va la canción—, así que
     ahora es una calibración de pantalla y por defecto vale cero. */
  desfaseTele: 0,
  efecto: 'halo',         // halo | borde | barras | ninguno
  efectoCancion: 1,       // cuánto se nota mientras suena una canción
  efectoCalent: 2.2,      // cuánto se nota en el calentamiento
  library: [], seeded: false,
  queue: [],
  historial: [],
  autoNext: true,
  filterLib: '',
  results: [], sel: null,
  edicion: '',            // «A Veiga Edition» y similares; vacío en la pública
  evento: { estado: 'ESPERA', pistaId: null, recien: null, desde: 0 }
};

/* ---- Preferencias del aparato ---------------------------------------
   Estas NO se comparten: la calidad de vídeo o la vista que prefieres
   son tuyas, no de la fiesta. Por eso van en localStorage y no en el
   estado compartido.

   El estado del evento sí es compartido, y por eso NO está aquí: vive
   en data/estado.json y lo ve también la tele.                        */

/* ---- Hablar con el servidor -----------------------------------------
   Buscar y descargar usan el mismo canal que el almacén: mismo manejo de
   errores, mismo mensaje cuando no hay nadie escuchando.

   Vivía suelta en `app.js` y por eso `busqueda.js` y `cola.js` dependían
   del último archivo que se carga, que es justo el que no debería tener
   nada que nadie necesite. Se resuelve tarde —dentro de la función— para
   no obligar a que el almacén esté montado al leer este archivo. */
KL.api = (url, cuerpo, opc) => KL.almacen.peticion(url, cuerpo, opc);

/* ---- «Qué se está cantando» tiene UN dueño --------------------------
   Aquí había un campo `curId` que se escribía a mano en cinco sitios, y
   el servidor guardaba además un `sonando` con el mismo dato. Tres copias
   de la misma verdad, y la prueba de que eso no sale gratis fue un fallo
   real: al volver del vídeo al operador había que escribir `sonando` y
   `evento` por separado, la primera respuesta llegaba con el evento viejo
   y pisaba el nuevo.

   Ahora no es un campo: es una pregunta con una sola respuesta posible.
   No se puede asignar, así que no puede desincronizarse. El dueño es el
   evento, y solo la máquina de estados lo cambia.

   Se mantiene el nombre `curId` a propósito: renombrarlo en veintitrés
   sitios a la vez no aporta nada y esconde el cambio de verdad, que es
   este. */
Object.defineProperty(KL.estado, 'curId', {
  enumerable: true,
  get() { return (this.evento && this.evento.pistaId) || null; }
});

KL.prefs = (function () {
  /* ── Esta clave NO se renombra, y es una decisión ──────────────────
     Al pasar a OpenKaraoke Center la tentación era dejarlo todo
     conjuntado: `okc_prefs_v1` y a correr. Habría costado una migración
     que hay que mantener para siempre, y el precio de equivocarse lo
     paga el usuario perdiendo el desfase de la tele, el efecto y el
     espacio en el que estaba — justo lo que más cuesta volver a ajustar.

     A cambio no gana nadie: esto no se lee, lo lee el navegador. Se
     renombra lo que la gente ve; lo que solo ve la máquina se queda
     quieto. Hay una prueba que lo sujeta, porque dentro de un año esto
     va a parecer un descuido y no lo es.

     El mismo criterio vale para las otras siete claves `karaoke_*`
     (modo, desfase, efecto, salida de audio, nombre en el móvil…). */
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
      S.retirar   = d.retirar !== false;
      if (['der', 'izq', 'no'].includes(d.avisoSig)) S.avisoSig = d.avisoSig;
      if (['karaoke', 'dj', 'mc'].includes(d.modo))  S.modo = d.modo;
      if (d.sufijos && typeof d.sufijos === 'object') {
        for (const k of ['karaoke', 'dj', 'mc']) {
          if (typeof d.sufijos[k] === 'string') S.sufijos[k] = d.sufijos[k];
        }
      }
      if (['pc', 'tele'].includes(d.sonidoEn)) S.sonidoEn = d.sonidoEn;
      S.ambienteOn = d.ambienteOn === true;
      if (Number.isFinite(+d.desfaseTele)) S.desfaseTele = Math.min(5, Math.max(-5, +d.desfaseTele));
      if (['halo','borde','barras','ninguno'].includes(d.efecto)) S.efecto = d.efecto;
      if (Number.isFinite(+d.efectoCancion)) S.efectoCancion = Math.min(4, Math.max(0, +d.efectoCancion));
      if (Number.isFinite(+d.efectoCalent))  S.efectoCalent  = Math.min(4, Math.max(0, +d.efectoCalent));
      /* Menos de 3 segundos no da tiempo ni a levantarse; más de 120 es
         una fiesta parada. Se recorta en vez de rechazarlo. */
      if (Number.isFinite(+d.segFin)) S.segFin = Math.min(120, Math.max(3, +d.segFin));
      if (Number.isFinite(+d.segLlamada)) S.segLlamada = Math.min(15, Math.max(0, +d.segLlamada));
    } catch (e) { /* navegador sin localStorage: se usan los valores por defecto */ }
  }

  function guardar() {
    try {
      localStorage.setItem(CLAVE, JSON.stringify({
        quality: S.quality, antiCut: S.antiCut, openVideo: S.openVideo,
        view: S.view, autoNext: S.autoNext, libCol: S.libCol,
        avisoSig: S.avisoSig, modo: S.modo, ambienteOn: S.ambienteOn, sufijos: S.sufijos, segFin: S.segFin, segLlamada: S.segLlamada, retirar: S.retirar,
        sonidoEn: S.sonidoEn, desfaseTele: S.desfaseTele,
        efecto: S.efecto, efectoCancion: S.efectoCancion, efectoCalent: S.efectoCalent
      }));
    } catch (e) { /* modo privado: no se recuerdan, pero se puede usar igual */ }
  }

  return { cargar, guardar };
})();
