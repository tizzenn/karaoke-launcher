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

  function iniciar(cb) {
    aplicar = cb.aplicar || aplicar;
    aviso   = cb.aviso   || aviso;
  }

  function vacio() {
    return { biblioteca: [], cola: [], sonando: null, version: 0 };
  }

  function leer() {
    try {
      const d = JSON.parse(localStorage.getItem(CLAVE) || 'null');
      if (!d || typeof d !== 'object') return vacio();
      return {
        biblioteca: d.biblioteca || [],
        cola:       d.cola || [],
        sonando:    d.sonando ?? null,
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
      sonando: e.sonando, version: e.version,
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
      if (e.sonando === d.id) e.sonando = null;
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
      e.cola = []; e.sonando = null;
    },

    sonando(e, d) {
      e.sonando = d.id ?? null;
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

  return { tipo: 'local', iniciar, cargar, accion, escuchar };
})();
