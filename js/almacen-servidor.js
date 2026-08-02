/* ═══════════════════════════════════════════════════════════════════
   almacen-servidor.js — los datos viven en data/estado.json

   El almacén de la versión con PHP. La biblioteca y la cola están en
   el servidor, así que el PC del karaoke y los móviles de la fiesta
   ven exactamente lo mismo.

   Expone la misma interfaz que almacen-local.js:

     iniciar({aplicar, aviso})   quién repinta y quién avisa de fallos
     cargar()                    trae el estado y lo aplica
     accion(datos)               manda un cambio y aplica lo que vuelve
     escuchar()                  se entera de lo que piden los móviles

   Quien lo use no debería saber cuál de los dos almacenes tiene
   detrás. Ese es el único punto donde la Lite y la versión con
   servidor se diferencian.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

var KL = window.KL || {};
window.KL = KL;

KL.almacen = (function () {

  /* Las pruebas abren la aplicación con `?bd=pruebas` y todo el almacén
     trabaja sobre un archivo aparte. Sin esto, ejecutar la suite borraría
     la biblioteca de la fiesta. */
  const BD = (new URLSearchParams(location.search).get('bd') || '').match(/^[a-z_]{1,20}$/);
  const RUTA = 'api/estado.php' + (BD ? '?bd=' + BD[0] : '');
  const SEP  = BD ? '&' : '?';

  /* Cada vuelta del sondeo deja respirar al servidor: php -S atiende
     una petición cada vez, y en la fiesta hay varios aparatos. */
  const ESPERA_SONDEO = 1500;
  const ESPERA_TRAS_FALLO = 4000;

  let version = -1;
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

  /* Si fetch falla del todo —«NetworkError», «Failed to fetch»— es que
     no hay nadie escuchando: se ha cerrado la ventana de Karaoke.bat, o
     la página se abrió con doble clic. Decirlo así evita mandar a
     revisar la clave de la API, que es lo último que falla. */
  async function peticion(url, cuerpo) {
    const opciones = cuerpo
      ? { method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(cuerpo) }
      : {};

    let r;
    try {
      r = await fetch(url, opciones);
    } catch (err) {
      throw new Error(location.protocol === 'file:'
        ? 'Has abierto la aplicación con doble clic. Ciérrala y arranca Karaoke.bat: sin servidor no funciona nada.'
        : 'El karaoke no está en marcha. Abre Karaoke.bat y NO cierres la ventana negra.');
    }

    const j = await r.json()
      .catch(() => ({ ok: false, error: 'respuesta ilegible del servidor' }));
    if (!j.ok) throw new Error(j.error || ('error ' + r.status));
    return j;
  }

  /* La versión que hemos pintado. El sondeo pregunta por ella. */
  function anotarVersion(e) {
    version = e.version ?? version;
    return e;
  }

  async function cargar() {
    try {
      aplicar(anotarVersion(await peticion(RUTA)));
    } catch (err) {
      aviso('⚠ No hay servidor: ' + err.message);
    }
  }

  async function accion(datos) {
    try {
      aplicar(anotarVersion(await peticion(RUTA, datos)));
    } catch (err) {
      aviso('⚠ ' + err.message);
    }
  }

  /* Si alguien pide una canción desde el móvil, aparece aquí solo. */
  async function escuchar() {
    for (;;) {
      try {
        const e = await peticion(RUTA + SEP + 'desde=' + version);
        if ((e.version ?? 0) > version) aplicar(anotarVersion(e));
        await new Promise(r => setTimeout(r, ESPERA_SONDEO));
      } catch (err) {
        await new Promise(r => setTimeout(r, ESPERA_TRAS_FALLO));
      }
    }
  }

  /* `peticion` se expone porque la búsqueda y la descarga hablan con el
     mismo servidor y merecen el mismo trato de errores. Cuando se
     separen buscador.js y descargas.js, se lo llevarán ellos. */
  return { tipo: 'servidor', iniciar, cargar, accion, escuchar, peticion };
})();
