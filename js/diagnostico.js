/* ═══════════════════════════════════════════════════════════════════
   diagnostico.js — «¿está todo bien?»

   Esto era un panel de «superpoderes»: una lista de lo que hay
   instalado. Y esa lista contestaba a una pregunta que nadie se hace.

   Nadie abre los ajustes pensando «quiero usar un superpoder». Se abren
   pensando **«algo no funciona»**, casi siempre veinte minutos antes de
   que llegue la gente y con el abrigo todavía puesto. El panel tiene que
   contestar a eso, y contestarlo en la primera línea.

   ── Las tres cosas que hace, por orden ───────────────────────────────
   1. Un semáforo arriba con una frase. Verde, ámbar o rojo, y qué pasa.
   2. Los detalles agrupados por dónde puede estar el problema: la red,
      la reproducción, las herramientas.
   3. Para cada cosa que va mal, **qué hacer**. Un diagnóstico que dice
      «no hay conexión con YouTube» y se calla es un diagnóstico a
      medias: quien lo lee ya sabía que algo iba mal.

   ── La regla que lo mantiene útil ────────────────────────────────────
   **Solo se comprueba lo que se puede saber de verdad.** Nada de
   «probablemente». Si no hay forma de averiguar algo desde aquí, no
   aparece: una fila en ámbar permanente entrena a la gente a ignorar el
   panel entero, y entonces el rojo de verdad tampoco se ve.

   Por eso «la tele está conectada» se responde con los segundos que
   lleva sin pedir el estado —que es un hecho— y no con una suposición.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

var KL = window.KL || {};
window.KL = KL;

KL.diagnostico = (function () {

  /* Cuánto puede llevar una pantalla sin dar señales antes de que
     preocupe. El sondeo pasa cada segundo y medio y la presencia se
     anota cada diez, así que hasta doce segundos es funcionamiento
     normal. Treinta ya es que se ha cerrado la ventana o se ha caído la
     red. */
  const AVISO = 30;
  const MAL   = 90;

  const S = () => KL.estado;

  /* Cada revisión devuelve { estado, titulo, detalle, arreglo }.
     `estado`: 'ok' | 'avi' | 'mal' | 'no' (no aplica, no se pinta). */

  function red() {
    const r = [];
    const ip = S().ipLocal;
    r.push(ip
      ? { estado:'ok', titulo:'Dirección en la red local',
          detalle: ip + ':' + (S().puerto || 8123) }
      : { estado:'mal', titulo:'Sin dirección en la red local',
          detalle:'Los móviles no pueden llegar a este ordenador.',
          arreglo:'Conecta el PC al router (por wifi o por cable) y recarga esta página. ' +
                  'Sin red local, el QR de pedir canciones no sirve.' });

    const w = S().wifi || {};
    if (!(w.ssid && w.clave)) {
      r.push({ estado:'avi', titulo:'El QR de wifi no se puede dibujar',
               detalle:'Falta el nombre de la red o la contraseña.',
               arreglo:'Ponlos en Ajustes de la fiesta → Wifi. Es el paso donde más ' +
                       'gente se atasca: hasta que un invitado no está en tu red, el ' +
                       'QR de pedir no le sirve de nada.' });
    }
    return r;
  }

  function pantallas() {
    const p = S().presencia || {};
    const linea = (clave, nombre, queHacer) => {
      const s = p[clave];
      if (s === null || s === undefined)
        return { estado:'avi', titulo:nombre + ': sin abrir',
                 detalle:'Todavía no ha pedido el estado ni una vez.', arreglo:queHacer };
      if (s > MAL)
        return { estado:'mal', titulo:nombre + ': sin señales',
                 detalle:'Lleva ' + Math.round(s) + ' s sin preguntar nada.',
                 arreglo:queHacer };
      if (s > AVISO)
        return { estado:'avi', titulo:nombre + ': va con retraso',
                 detalle:'Última señal hace ' + Math.round(s) + ' s.',
                 arreglo:'Puede ser la red. Si sigue así, recarga esa pantalla.' };
      return { estado:'ok', titulo:nombre + ': conectada',
               detalle:'Última señal hace ' + Math.round(s) + ' s.' };
    };

    const r = [linea('tele', 'Pantalla del público',
      'Ábrela con el botón de la segunda pantalla, arrastra la ventana a la tele y pulsa F.')];

    /* Los móviles no son un problema si no hay ninguno: en muchas fiestas
       nadie pide desde el móvil y eso no es un fallo. Solo se informa. */
    const m = p.movil;
    if (m !== null && m !== undefined && m < MAL)
      r.push({ estado:'ok', titulo:'Móviles pidiendo',
               detalle:'Alguien ha mirado la página de pedir hace ' + Math.round(m) + ' s.' });
    return r;
  }

  function reproduccion() {
    const r = [];
    r.push(S().conClave
      ? { estado:'ok', titulo:'Buscar en YouTube', detalle:'La clave está puesta.' }
      : { estado:'avi', titulo:'No se puede buscar en YouTube',
          detalle:'Falta la clave de la API.',
          arreglo:'Se pone en Ajustes de la fiesta → Buscar en YouTube. Mientras tanto ' +
                  'se pueden pegar enlaces de YouTube a mano, que sigue funcionando.' });

    /* Cuánto lleva cortando la conexión. `cortes` lo cuenta el
       antiparones, así que es un hecho medido, no una impresión. */
    if (S().cortes > 0)
      r.push({ estado: S().cortes > 3 ? 'mal' : 'avi',
               titulo:'La conexión ha cortado ' + S().cortes + ' ' +
                      (S().cortes === 1 ? 'vez' : 'veces'),
               detalle:'El antiparones ha tenido que bajar la calidad.',
               arreglo:'Descarga las canciones de la cola con el icono de la flecha: ' +
                       'una vez en disco, ya no dependen de internet.' });

    const ev = S().evento || {};
    if (ev.estado === 'INTERPRETACION' && !ev.t0)
      r.push({ estado:'avi', titulo:'La tele no puede sincronizarse todavía',
               detalle:'El reproductor aún no ha dicho por dónde va.',
               arreglo:'Suele durar un segundo al empezar cada canción. Si se queda así, ' +
                       'el vídeo no está sonando de verdad en esta pantalla.' });
    return r;
  }

  function herramientas() {
    const y = S().ytdlp || {};
    return [
      { estado:'ok', titulo:'PHP', detalle:'La aplicación está en marcha.' },
      S().puedeDescargar
        ? { estado:'ok', titulo:'yt-dlp',
            detalle:'Versión ' + (y.version || '?') + ' · ' +
                    (y.descargados || 0) + ' vídeos en disco.' }
        : { estado:'avi', titulo:'yt-dlp no está',
            detalle:'No se pueden descargar canciones.',
            arreglo:'Descárgalo de github.com/yt-dlp/yt-dlp/releases y ponlo junto a ' +
                    'Karaoke.bat. No necesita Python: lleva el suyo dentro.' }
    ];
  }

  const GRUPOS = [
    { icono:'wifi',              nombre:'Red',            revisar:red },
    { icono:'segunda-pantalla',  nombre:'Pantallas',      revisar:pantallas },
    { icono:'play',              nombre:'Reproducción',   revisar:reproduccion },
    { icono:'ajustes',           nombre:'Herramientas',   revisar:herramientas }
  ];

  /* El resumen es el peor de todos, y esa es toda la lógica que tiene.
     Un semáforo que promedia no sirve: si una cosa está en rojo, la
     fiesta tiene un problema por muy bien que esté el resto. */
  function resumir(todo) {
    if (todo.some(f => f.estado === 'mal'))
      return { estado:'mal', txt:'Hay algo que va a dar problemas' };
    if (todo.some(f => f.estado === 'avi'))
      return { estado:'avi', txt:'Funciona, pero hay cosas a medias' };
    return { estado:'ok', txt:'Todo listo para la fiesta' };
  }

  function pintar() {
    const caja = document.getElementById('poderes');
    if (!caja) return;

    const grupos = GRUPOS.map(g => ({ g, filas: g.revisar().filter(f => f.estado !== 'no') }));
    const todo = grupos.flatMap(x => x.filas);
    const res = resumir(todo);

    const fila = f => `
      <div class="dgFila ${f.estado}">
        <span class="pt"></span>
        <div>
          <b>${esc(f.titulo)}</b>
          ${f.detalle ? `<div class="det">${esc(f.detalle)}</div>` : ''}
          ${f.arreglo ? `<div class="arr">${icono('aviso','sm')}<span>${f.arreglo}</span></div>` : ''}
        </div>
      </div>`;

    caja.innerHTML =
      `<div class="dgResumen ${res.estado}">
         <span class="pt"></span><b>${esc(res.txt)}</b>
       </div>` +
      grupos.filter(x => x.filas.length).map(x => `
        <div class="dgGrupo">
          <h5>${icono(x.g.icono,'sm')} ${esc(x.g.nombre)}</h5>
          ${x.filas.map(fila).join('')}
        </div>`).join('');
  }

  return { pintar, resumir, GRUPOS };
})();
