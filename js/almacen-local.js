/* ═══════════════════════════════════════════════════════════════════
   almacen-local.js — los datos viven en el navegador

   El almacén de la versión Lite: sin PHP, sin servidor, sin red. Todo
   se guarda en localStorage, así que el karaoke funciona desde un
   pendrive y no hace falta instalar nada.

   Expone exactamente la misma interfaz que almacen-servidor.js, con
   las mismas acciones que entiende api/estado.php. Esa igualdad es lo
   que permite que la cola, la biblioteca y el buscador sean el mismo
   código en las dos versiones.

   Lo que aquí no existe, y no es un olvido:
     · peticiones desde el móvil — no hay servidor al que pedir
     · escuchar() no hace nada    — un solo aparato, nada que sondear
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

var KL = window.KL || {};
window.KL = KL;

KL.almacen = (function () {

  const CLAVE = 'karaoke_launcher_estado_v1';

  let aplicar = () => {};
  let aviso   = () => {};

  /* `aplicar` sigue siendo uno solo: repintar la interfaz entera es un
     trabajo con dueño, y dos dueños repintando lo mismo es un parpadeo.
     Los AVISOS sí se reparten —«se ha caído el servidor» le interesa al
     que enseña el mensaje y a cualquiera que quiera reaccionar— y por
     eso salen también por `KL.senales`. */
  function iniciar(cb) {
    aplicar = cb.aplicar || aplicar;
    if (cb.aviso) KL.senales.oir('almacen:aviso', cb.aviso);
    aviso = (...d) => KL.senales.avisar('almacen:aviso', ...d);
  }

  function vacio() {
    return {
      biblioteca: [], cola: [], historial: [],
      evento: { estado: 'ESPERA', pistaId: null, recien: null, desde: 0 },
      version: 0
    };
  }

  function leer() {
    try {
      const d = JSON.parse(localStorage.getItem(CLAVE) || 'null');
      if (!d || typeof d !== 'object') return vacio();
      /* Un archivo guardado con una versión anterior no trae `evento` ni
         `historial`: se rellenan en vez de rechazarlo. */
      return {
        biblioteca: d.biblioteca || [],
        cola:       d.cola || [],
        historial:  d.historial || [],
        evento:     d.evento || vacio().evento,
        version:    d.version || 0
      };
    } catch (e) { return vacio(); }
  }

  /* El navegador puede estar lleno o en modo privado. Si no se puede
     guardar hay que decirlo: si no, el usuario cree que tiene su
     biblioteca a salvo y la pierde al cerrar. */
  function escribir(e) {
    e.version = (e.version || 0) + 1;
    try {
      localStorage.setItem(CLAVE, JSON.stringify(e));
    } catch (err) {
      aviso('⚠ No he podido guardar. Exporta una copia desde Ajustes antes de cerrar.');
    }
    return e;
  }

  /* Misma forma que devuelve api/estado.php, para que la interfaz no
     tenga que distinguir de dónde vienen los datos. */
  function respuesta(e) {
    return {
      ok: true,
      biblioteca: e.biblioteca, cola: e.cola,
      /* Deducido, igual que en api/estado.php: «qué se está cantando» es
         `evento.pistaId` y no un campo aparte que pueda contradecirlo. */
      sonando: (e.evento || {}).estado === 'INTERPRETACION'
               ? ((e.evento || {}).pistaId ?? null) : null,
      historial: e.historial, evento: e.evento,
      paneles: e.paneles ?? null,
      version: e.version,
      peticiones: false,                       // sin servidor no hay QR
      con_clave: KL.estado.apiKey !== ''
    };
  }

  function pistaDesde(v, quien) {
    return {
      id:       KL.uid(),
      videoId:  String(v.videoId),
      title:    String(v.title || 'Sin título'),
      channel:  String(v.channel || ''),
      thumb:    String(v.thumb || KL.miniatura(v.videoId)),
      duration: Number(v.duration || 0),
      local:    null,                          // sin yt-dlp no hay descargas
      pedida:   quien || null
    };
  }

  /* Las mismas acciones que api/estado.php, una por una. Si añades una
     allí, añádela aquí: la interfaz llama a las dos por igual. */
  const acciones = {

    anadir_cola(e, d) {
      const v = d.video;
      if (!v || !v.videoId) throw new Error('falta el vídeo');
      if (e.cola.some(t => t.videoId === v.videoId))
        throw new Error('esa canción ya está en la cola');
      e.cola.push(pistaDesde(v, d.quien));
    },

    quitar_cola(e, d) {
      e.cola = e.cola.filter(t => t.id !== d.id);
    },

    ordenar_cola(e, d) {
      const orden = d.orden || [];
      const porId = new Map(e.cola.map(t => [t.id, t]));
      const nueva = [];
      for (const id of orden) {
        if (porId.has(id)) { nueva.push(porId.get(id)); porId.delete(id); }
      }
      for (const t of porId.values()) nueva.push(t);   // lo que llegara mientras
      e.cola = nueva;
    },

    vaciar_cola(e) {
      e.cola = [];
    },

    /* Ya no guarda nada: se acepta para no responder «acción desconocida»
       a una pestaña abierta desde antes del cambio. */
    sonando() {},

    /* El motor de estados. Mismas acciones que api/estado.php: si añades
       una allí, añádela aquí. La interfaz llama a las dos por igual. */
    evento(e, d) {
      const ev = d.evento || {};
      if (!['ESPERA','PREPARADA','LLAMADA','INTERPRETACION','FIN_ACTUACION'].includes(ev.estado)) {
        throw new Error('estado de evento no válido');
      }
      e.evento = {
        estado:  ev.estado,
        pistaId: ev.pistaId ?? null,
        recien:  ev.recien  ?? null,
        desde:    Math.floor(Date.now() / 1000),
        segundos: Math.max(0, Math.min(15, ev.segundos || 0)),
        n:        ev.n || 0
      };
    },

    fin_actuacion(e, d) {
      const cantada = e.cola.find(t => t.id === d.id) || null;
      if (cantada) {
        e.historial.unshift({ ...cantada, cantada_en: Math.floor(Date.now() / 1000) });
        e.historial = e.historial.slice(0, 100);
      }
      if (d.retirar && d.id) e.cola = e.cola.filter(t => t.id !== d.id);
    },

    paneles(e, d) {
      e.paneles   = Array.isArray(d.paneles) ? d.paneles.map(String) : null;
    },

    vaciar_historial(e) {
      e.historial = [];
    },

    anadir_biblioteca(e, d) {
      const v = d.video;
      if (!v || !v.videoId) throw new Error('falta el vídeo');
      if (e.biblioteca.some(t => t.videoId === v.videoId)) return;
      e.biblioteca.push(pistaDesde(v, null));
    },

    quitar_biblioteca(e, d) {
      e.biblioteca = e.biblioteca.filter(t => t.videoId !== d.videoId);
    },

    reemplazar_biblioteca(e, d) {
      const lista = Array.isArray(d.biblioteca) ? d.biblioteca : [];
      e.biblioteca = lista.filter(v => v && v.videoId).map(v => pistaDesde(v, null));
    },

    /* Refresca título y carátula de algo ya guardado, en los dos sitios. */
    actualizar_pista(e, d) {
      for (const lista of [e.biblioteca, e.cola]) {
        for (const t of lista) {
          if (t.videoId !== d.videoId) continue;
          for (const campo of ['title', 'channel', 'thumb', 'duration', 'local']) {
            if (d[campo] !== undefined) t[campo] = d[campo];
          }
        }
      }
    }
  };

  async function cargar() {
    aplicar(respuesta(leer()));
  }

  async function accion(datos) {
    const nombre = datos && datos.accion;
    const fn = acciones[nombre];
    if (!fn) { aviso('⚠ acción desconocida: ' + nombre); return; }

    const e = leer();
    try {
      fn(e, datos);
    } catch (err) {
      aviso('⚠ ' + err.message);
      return;
    }
    aplicar(respuesta(escribir(e)));
  }

  /* Un solo aparato: no hay nadie que pueda cambiar nada por detrás. */
  async function escuchar() { /* nada que sondear */ }

  /* La versión Lite no tiene servidor al que preguntar, pero `KL.api`
     existe igual porque el buscador y las descargas lo llaman sin saber
     qué almacén hay detrás. Si no estuviera, la búsqueda reventaría con
     un «peticion is not a function» que no le dice nada a nadie.

     Así falla con una frase que sí explica qué se puede hacer: pegar el
     enlace funciona igual, porque el título se resuelve contra oEmbed y
     eso no pasa por ningún servidor nuestro. */
  function peticion() {
    return Promise.reject(new Error(
      'Sin servidor solo busco en tu biblioteca. Pega aquí el enlace de ' +
      'YouTube y la añado con su título y su carátula.'));
  }

  return { tipo: 'local', iniciar, cargar, accion, escuchar, peticion };
})();
