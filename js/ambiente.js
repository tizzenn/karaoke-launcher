/* ═══════════════════════════════════════════════════════════════════
   ambiente.js — música ambiente: que no haya silencios en el karaoke

   OJO CON EL NOMBRE. Esto **no** es la Cabina DJ como espacio: la Cabina
   DJ es otra fiesta, una en la que nadie canta y la lista la hacéis
   entre todos desde el móvil. Lo de este archivo es el hilo de fondo del
   karaoke — pero desde 2026-08-05 SUENA la cola de esa Cabina DJ, no una
   lista aparte.

   ── Por qué ya no hay una lista de YouTube configurada a mano ────────
   La había, con su propio canal por defecto (DJ Noize) y su propio campo
   en Ajustes. Y era una tontería: si ya existe la Cabina DJ —una lista
   de música real que la propia fiesta va llenando desde el móvil—,
   configurar una SEGUNDA lista aparte para el mismo propósito («que
   suene algo cuando no canta nadie») es mantener dos verdades sobre lo
   mismo, y eso es justo lo que este proyecto intenta no hacer en ningún
   otro sitio (MODELO §6). Ahora el hueco entre canciones de karaoke lo
   rellena la MISMA cola que ya suena en la Cabina DJ, en un reproductor
   aparte y en silencio de fondo — sin tocar ni consumir esa cola real:
   solo la escucha.

   Las dos cosas se llamaron «Cabina DJ» durante meses y esa palabra
   acabó significando dos cosas incompatibles: «la música que se aparta
   cuando alguien va a cantar» y «la fiesta en la que nadie canta». Quien
   leía «Cabina DJ» esperaba un VirtualDJ y encontraba un control de
   volumen automático. Los nombres internos —`ambiente`, `ambienteOn`—
   no se han tocado: se renombra lo que lee una persona.

   Lo que apaga una fiesta no es una canción mala. Es el hueco entre dos:
   treinta segundos de nada mientras alguien busca la siguiente, y la
   gente se sienta.

   Esto suena mientras **no hay ninguna actuación en marcha ni
   preparándose**, se aparta solo en cuanto el operador prepara una, y
   vuelve cuando termina. El operador no tiene que acordarse de nada.

   ── Dónde suena, y por qué no en los dos sitios ─────────────────────
   Este archivo lo cargan la pantalla del operador y la del público, y
   cada una decide si le toca a ella preguntando `KL.ambiente.doySonido`.
   Solo suena en la que da el sonido. Sonando en las dos se oiría doble y
   desfasado, porque son dos reproductores distintos con dos conexiones
   distintas: exactamente el mismo problema que el desfase del vídeo,
   pero con música encima de la conversación.

   ── El fundido no es un adorno ──────────────────────────────────────
   Cortar la música de golpe se oye como un fallo: la gente mira la
   pantalla pensando que algo se ha roto. Bajarla en un segundo se lee
   como «va a empezar algo», que es justo lo que va a pasar.

   ── Por qué no arranca sola al abrir ────────────────────────────────
   Ningún navegador deja sonar nada sin que el usuario haya tocado la
   página. No es un fallo que haya que rodear: es lo que impide que
   cincuenta pestañas te griten a la vez. Así que se espera al primer
   clic —que en la práctica llega enseguida— y mientras tanto se calla.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

var KL = window.KL || {};
window.KL = KL;

KL.ambiente = (function () {

  /* Cada página pone esto a su manera. Por defecto, no. */
  let doySonido = () => false;

  let ajustes = { fuente: 'no', colaDj: [], carpeta: false, volumen: 35, auto: false };
  let yt = null, listo = false, sonando = false, hubGesto = false;
  let audio = null, locales = [], iLocal = 0;
  let fundido = null;
  /* Si la cola de la Cabina DJ cambia MIENTRAS suena de fondo, no se
     recarga en el acto —eso se oiría como un corte—: se espera a que se
     apague sola (empieza una actuación) y se recoloca la lista en ese
     silencio, que es cuando nadie lo puede notar. */
  let pendienteDj = false;

  /* ---- El fundido, y por qué es largo ---------------------------------
     Novecientos milisegundos era técnicamente un fundido y a efectos
     prácticos un corte: se oía como un fallo, no como una transición.

     Ahora son tres segundos al bajar y dos al subir, y la asimetría es a
     propósito. Bajar despacio ES el aviso de que va a empezar algo: la
     gente deja de hablar antes de que suene la primera nota. Subir tarda
     menos porque al terminar una canción el silencio incomoda enseguida,
     pero tampoco de golpe: entrar a plena potencia justo después de los
     aplausos pisa la conversación que acaba de empezar.

     Y hay una razón que no es estética: con el fundido corto no se
     distinguía la música de fondo de la canción del karaoke. Con tres
     segundos, cualquiera que esté en la sala nota que **eso de antes era
     otra cosa**. */
  const MS_BAJAR = 3000;
  const MS_SUBIR = 2000;

  /* ---- El reproductor de fondo -----------------------------------------
     Un segundo iframe de YouTube, sin vídeo visible y a un volumen que
     deja hablar. No se reutiliza el del karaoke a propósito: cargar aquí
     la cola de la Cabina DJ borraría la canción que está preparada, y
     preparar existe precisamente para que el vídeo esté listo antes de
     que nadie pulse nada.

     Tampoco se toca la cola REAL de la Cabina DJ: esto es una copia de
     sus identificadores, en un reproductor aparte que solo escucha. Ese
     hilo de fondo no hace avanzar ni consume nada de lo que de verdad
     está esperando su turno cuando alguien abra la Cabina DJ en serio. */
  function crearYT() {
    if (yt || !ajustes.colaDj || !ajustes.colaDj.length) return;
    const hueco = document.createElement('div');
    hueco.id = 'ytAmbiente';
    hueco.style.cssText = 'position:fixed;width:1px;height:1px;left:-9999px;top:0';
    document.body.appendChild(hueco);

    const arrancar = () => {
      yt = new YT.Player('ytAmbiente', {
        host: 'https://www.youtube-nocookie.com',
        playerVars: {
          autoplay: 0, controls: 0, playsinline: 1,
          origin: location.origin
        },
        events: {
          onReady: () => {
            listo = true;
            try {
              yt.setVolume(ajustes.volumen);
              yt.cuePlaylist({ playlist: ajustes.colaDj, index: 0 });
              yt.setShuffle(true);
            } catch (e) {}
            revisar();
          },
          /* Si un vídeo de la cola está caído, YouTube se para en seco.
             Aquí no hay nadie mirando: se pasa al siguiente y ya. */
          onError: () => { try { yt.nextVideo(); } catch (e) {} }
        }
      });
    };

    if (window.YT && YT.Player) arrancar();
    else {
      const anterior = window.onYouTubeIframeAPIReady;
      window.onYouTubeIframeAPIReady = function () {
        if (anterior) try { anterior(); } catch (e) {}
        arrancar();
      };
    }
  }

  /* Cuando la cola de la Cabina DJ cambia y es seguro hacerlo (no está
     sonando de fondo ahora mismo), se pone al día sin recrear el
     reproductor entero. */
  function refrescarListaDj() {
    pendienteDj = false;
    if (!yt || !listo || ajustes.fuente !== 'dj') return;
    try { yt.cuePlaylist({ playlist: ajustes.colaDj, index: 0 }); } catch (e) {}
  }

  /* ---- Carpeta del ordenador ------------------------------------------- */
  async function cargarLocales() {
    if (audio || !ajustes.carpeta) return;
    try {
      const j = await KL.api('api/ambiente.php');
      locales = j.pistas || [];
    } catch (e) { locales = []; }
    if (!locales.length) return;

    /* Orden aleatorio una vez, no una canción al azar cada vez: así no
       se repite la misma dos veces seguidas, que canta muchísimo. */
    for (let i = locales.length - 1; i > 0; i--) {
      const j = Math.random() * (i + 1) | 0;
      [locales[i], locales[j]] = [locales[j], locales[i]];
    }
    audio = new Audio();
    audio.volume = ajustes.volumen / 100;
    audio.addEventListener('ended', siguienteLocal);
    audio.addEventListener('error', siguienteLocal);
    listo = true;
    revisar();
  }

  function siguienteLocal() {
    if (!locales.length) return;
    iLocal = (iLocal + 1) % locales.length;
    audio.src = locales[iLocal];
    if (sonando) audio.play().catch(() => {});
  }

  /* ---- Volumen con rampa ------------------------------------------------ */
  function volumenActual() {
    if (audio) return audio.volume * 100;
    try { return yt ? yt.getVolume() : 0; } catch (e) { return 0; }
  }

  function volumenA(destino, alTerminar) {
    clearInterval(fundido);
    const paso = 40;
    let v = volumenActual();
    const pasos = Math.max(1, Math.round((destino < v ? MS_BAJAR : MS_SUBIR) / paso));
    const delta = (destino - v) / pasos;
    let n = 0;
    fundido = setInterval(() => {
      v += delta; n++;
      const puesto = Math.max(0, Math.min(100, v));
      if (audio) audio.volume = puesto / 100;
      else if (yt) try { yt.setVolume(puesto); } catch (e) {}
      if (n >= pasos) { clearInterval(fundido); fundido = null; if (alTerminar) alTerminar(); }
    }, paso);
  }

  /* ---- Agacharse mientras habla el maestro de ceremonias -----------
     No es apagar: es bajar a un cuarto durante unos segundos. Cortar la
     música y devolverla se oye como un fallo; agacharla se lee como que
     alguien va a decir algo, que es exactamente lo que va a pasar.

     Y es rápido a propósito —300 ms, no los tres segundos del fundido
     grande—: aquí no se quiere avisar de nada, se quiere dejar hueco
     para una frase que ya ha empezado. */
  let agachado = false;
  function agachar(si) {
    if (agachado === !!si) return;
    agachado = !!si;
    if (!sonando) return;
    const destino = agachado ? Math.round(ajustes.volumen * 0.25) : ajustes.volumen;
    clearInterval(fundido);
    const paso = 30, pasos = Math.max(1, Math.round(300 / paso));
    let v = volumenActual();
    const delta = (destino - v) / pasos;
    let n = 0;
    fundido = setInterval(() => {
      v += delta; n++;
      const puesto = Math.max(0, Math.min(100, v));
      if (audio) audio.volume = puesto / 100;
      else if (yt) try { yt.setVolume(puesto); } catch (e) {}
      if (n >= pasos) { clearInterval(fundido); fundido = null; }
    }, paso);
  }

  function encender() {
    if (sonando || !listo || !hubGesto) return;
    sonando = true;
    if (audio) {
      if (!audio.src) audio.src = locales[iLocal];
      audio.volume = 0;
      audio.play().catch(() => { sonando = false; });
    } else if (yt) {
      try { yt.setVolume(0); yt.playVideo(); } catch (e) {}
    }
    volumenA(ajustes.volumen);
  }

  function apagar() {
    if (!sonando) return;
    sonando = false;
    volumenA(0, () => {
      if (audio) audio.pause();
      else if (yt) try { yt.pauseVideo(); } catch (e) {}
      /* El silencio recién llegado es el único momento en que se puede
         recolocar la cola de la Cabina DJ sin que nadie lo note. */
      if (pendienteDj) refrescarListaDj();
    });
  }

  /* ---- Cuándo toca sonar ------------------------------------------------
     En ESPERA y en FIN_ACTUACION. En cuanto hay algo preparado, la
     música estorba: el operador está mirando a quien va a cantar, no a
     la pantalla.

     El calentamiento es ESPERA, así que la música acompaña mientras la
     gente llega y va pidiendo. Que es exactamente cuando más falta hace.

     FIN_ACTUACION se sumó (2026-08-04, transiciones más teatrales): es
     el mismo hueco que ESPERA en espíritu — nada preparado todavía, el
     operador recuperando el monitor entre una actuación y la siguiente
     —, solo que dura unos segundos en vez de minutos. Sonar ahí en vez
     de en silencio es lo que hace que el corte entre canciones no se
     note como un corte. */
  function deberiaSonar() {
    /* Tres condiciones, y las tres tienen dueño distinto: la fuente la
       elige el dueño en los ajustes, el interruptor lo maneja el operador
       en mitad de la fiesta, y quién da el sonido depende del aparato. */
    if (ajustes.fuente === 'no') return false;
    if (ajustes.fuente === 'dj' && !(ajustes.colaDj && ajustes.colaDj.length)) return false;
    if (!KL.estado.ambienteOn) return false;
    if (!doySonido()) return false;
    /* Y una cuarta, que no es de dueño sino de concepto: en la Cabina DJ
       esto NO suena. Allí la cola ES la música, y poner un hilo de fondo
       por debajo sería sonar dos cosas a la vez. La música ambiente es
       una herramienta del karaoke: rellena los huecos entre actuaciones.
       Donde no hay actuaciones, no hay huecos que rellenar. */
    if (KL.estado.modo === 'dj') return false;
    const e = (KL.estado && KL.estado.evento && KL.estado.evento.estado) || 'ESPERA';
    return e === 'ESPERA' || e === 'FIN_ACTUACION';
  }

  function revisar() {
    if (deberiaSonar()) { encender(); return; }
    apagar();

    /* Y aquí está la diferencia entre «se calla» y «se apaga».

       Si NO vuelve sola, dejar el interruptor encendido sería mentir: la
       música se callaría al empezar la canción y volvería al terminarla,
       que es justo lo que el operador quiso evitar al configurarlo así.
       Se apaga de verdad, y el botón lo enseña.

       Solo cuenta cuando el motivo de callarse es que ha empezado una
       actuación. Si se calla porque el aparato no da el sonido, el
       interruptor no tiene por qué cambiar. */
    const enMarcha = (KL.estado.evento || {}).estado !== 'ESPERA';
    if (!ajustes.auto && enMarcha && KL.estado.ambienteOn) {
      KL.estado.ambienteOn = false;
      if (KL.prefs) KL.prefs.guardar();
      if (KL.pintarCabinaDJ) KL.pintarCabinaDJ();
    }
  }

  /* Qué está sonando ahora mismo, para que el botón lo diga. Con tres
     ventanas y música de fondo, «¿esto de dónde sale?» es una pregunta
     que se hace mucho. */
  function queSuena() {
    if (!sonando) return '';
    if (audio) {
      try { return decodeURIComponent((audio.src || '').split('/').pop()).slice(0, 40); }
      catch (e) { return 'una pista de la carpeta'; }
    }
    try { return (yt.getVideoData() || {}).title || ''; } catch (e) { return ''; }
  }

  /* ---- Ajustes que llegan del servidor ---------------------------------- */
  function aplicar(nuevos) {
    if (!nuevos) return;
    const fuenteAntes = ajustes.fuente;
    const djAntes = (ajustes.colaDj || []).join(',');
    ajustes = Object.assign({}, ajustes, nuevos);
    const djDespues = (ajustes.colaDj || []).join(',');

    /* Cambiar de FUENTE obliga a rehacer el reproductor entero — pasa una
       vez, al configurarlo, no en mitad de una fiesta. Que la cola de la
       Cabina DJ gane o pierda una canción, en cambio, es continuo toda la
       noche: eso NO destruye nada, se recoloca en caliente (ver
       refrescarListaDj) y con cuidado de no cortar lo que ya suena. */
    if (fuenteAntes !== ajustes.fuente && yt) {
      try { yt.destroy(); } catch (e) {}
      yt = null; listo = false; sonando = false;
      const h = document.getElementById('ytAmbiente');
      if (h) h.remove();
    }
    if (ajustes.fuente === 'dj') crearYT();
    else if (ajustes.fuente === 'carpeta') cargarLocales();

    if (djAntes !== djDespues) {
      if (sonando) pendienteDj = true;
      else refrescarListaDj();
    }
    if (!sonando && listo) revisar();
    else if (sonando) volumenA(ajustes.volumen);
  }

  function iniciar(opciones) {
    doySonido = (opciones && opciones.doySonido) || doySonido;

    /* El primer gesto del usuario desbloquea el audio. Una vez, y a
       escuchar el estado a partir de ahí. */
    const desbloquear = () => { hubGesto = true; revisar(); };
    addEventListener('pointerdown', desbloquear, { once: true });
    addEventListener('keydown', desbloquear, { once: true });

    KL.senales.oir('evento:cambio', revisar);
  }

  return { iniciar, aplicar, revisar, agachar, suena: () => sonando, queSuena };
})();
