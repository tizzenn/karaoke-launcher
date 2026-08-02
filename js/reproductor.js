/* ═══════════════════════════════════════════════════════════════════
   reproductor.js — poner una pista, sea de donde sea

   Aquí hay dos cosas y conviene no mezclarlas:

   1. Las FUENTES. Cada una sabe reproducir un tipo de pista y expone
      siempre los mismos ocho métodos. YouTube y los archivos de disco
      son las dos que existen hoy.

   2. El REPRODUCTOR. Elige la fuente que toca, le pasa la pista y avisa
      al motor de evento cuando algo pasa. No sabe de YouTube.

   Por qué esta separación, si hoy solo hay YouTube: porque el resto de
   la aplicación —la cola, el motor de evento, la pantalla pública— ya no
   vuelve a mencionar YouTube ni una vez. Añadir Vimeo, Twitch, una
   carpeta del NAS o un VLC por su interfaz HTTP es escribir un objeto
   nuevo aquí abajo y registrarlo. Nada más se entera.

   Lo que NO se ha hecho, a propósito: cambiar el modelo de datos. Una
   pista sigue teniendo `videoId` y `local`, porque cambiar esas claves
   rompería los archivos ya guardados de la gente. `detectar()` deduce la
   fuente de lo que hay; el día que haga falta, una pista podrá traer un
   campo `fuente` explícito y esta función lo respetará.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

KL.fuentes = {};

/* ---------------------------------------------------------------------
   Fuente: YouTube incrustado
   --------------------------------------------------------------------- */
KL.fuentes.youtube = (function () {

  let yt = null, listo = false, cb = {};
  /* Sin esto, un cue() que llega antes de que la API esté lista se pierde
     en silencio y la canción no arranca nunca. */
  let pendiente = null;

  const QUAL = ['hd720','large','medium','small','tiny'];
  const BUF  = { since:0, step:0, last:0, watch:null };

  function qIndex(q){ const i = QUAL.indexOf(q); return i < 0 ? 2 : i; }

  function iniciar(callbacks){
    cb = callbacks || {};
    window.onYouTubeIframeAPIReady = function(){
      yt = new YT.Player('yt', {
        /* `origin` no es opcional en la práctica: sin él, YouTube rechaza
           la comunicación con la página en algunas configuraciones y
           pinta su propio «Se ha producido un error» dentro del marco,
           sin avisar por la API. Cuesta nada y quita un fallo entero. */
        playerVars:{ autoplay:0, rel:0, modestbranding:1, playsinline:1,
                     iv_load_policy:3, origin: location.origin },
        events:{
          onReady: () => {
            listo = true;
            calidad(KL.estado.quality);
            if(pendiente){ const p = pendiente; pendiente = null; cargar(p.pista, p.opc); }
          },
          onStateChange: e => {
            if(e.data === YT.PlayerState.PLAYING){
              clearTimeout(arranqueT);
              cb.onSonando && cb.onSonando(true);
            }
            if(e.data === YT.PlayerState.PAUSED) { cb.onSonando && cb.onSonando(false); }
            if(e.data === YT.PlayerState.ENDED)  { pararVigilancia(); cb.onFin && cb.onFin(); }
          },
          onError: e => {
            /* 101/150 = el dueño no permite incrustar. 100 = borrado o privado. */
            cb.onError && cb.onError(e && e.data);
          }
        }
      });
    };
    const s = document.createElement('script');
    s.src = 'https://www.youtube.com/iframe_api';
    document.head.appendChild(s);
  }

  /* `arrancar:false` deja el vídeo cargado y quieto. Es la precarga del
     estado PREPARADA: el silencio entre canciones es el enemigo real de
     una fiesta, y con esto baja a cero. */
  function cargar(pista, opc){
    opc = opc || {};
    if(!listo || !yt){ pendiente = { pista, opc }; return; }
    BUF.last = 0;
    const cfg = {
      videoId: pista.videoId,
      startSeconds: opc.desde || 0,
      suggestedQuality: KL.estado.quality === 'auto' ? undefined : KL.estado.quality
    };
    try{
      if(opc.arrancar){ yt.loadVideoById(cfg); vigilar(); }
      else            { yt.cueVideoById(cfg); }
    }catch(e){}
  }

  function play(){
    try{ if(listo && yt){ yt.playVideo(); vigilar(); vigilarArranque(); } }catch(e){}
  }

  /* ---- El vídeo que no arranca y no se queja --------------------------
     Cuando YouTube pinta su «Se ha producido un error. Vuelve a
     intentarlo más tarde», a veces NO dispara onError: la API se queda
     tan tranquila y la aplicación esperando una canción que no va a
     sonar nunca. Delante de una fiesta eso son treinta segundos de nadie
     entendiendo nada.

     Así que se comprueba: a los 6 segundos de dar al play, si el estado
     no es «reproduciendo» ni «cargando», se reintenta una vez; a los 12,
     se da por perdida y se avisa como cualquier otro error. */
  let arranqueT = null;
  function vigilarArranque(){
    clearTimeout(arranqueT);
    let reintentado = false;
    const mirar = () => {
      if(!listo || !yt || !yt.getPlayerState) return;
      const st = yt.getPlayerState();
      if(st === 1 || st === 3) return;              // 1 PLAYING, 3 BUFFERING
      if(!reintentado){
        reintentado = true;
        try{ yt.playVideo(); }catch(e){}
        arranqueT = setTimeout(mirar, 6000);
        return;
      }
      cb.onError && cb.onError('sin arrancar');
    };
    arranqueT = setTimeout(mirar, 6000);
  }
  function pausa(){ clearTimeout(arranqueT); try{ if(listo && yt) yt.pauseVideo(); }catch(e){} }
  function parar(){
    pararVigilancia(); clearTimeout(arranqueT);
    try{ if(listo && yt) yt.stopVideo(); }catch(e){}
  }
  function sonando(){ try{ return listo && yt && yt.getPlayerState() === 1; }catch(e){ return false; } }
  function posicion(){ try{ return (listo && yt && yt.getCurrentTime()) || 0; }catch(e){ return 0; } }
  function duracion(){ try{ return (listo && yt && yt.getDuration()) || 0; }catch(e){ return 0; } }
  function buscar(seg){ try{ if(listo && yt) yt.seekTo(seg, true); }catch(e){} }
  /* Quitar el silencio NO basta. El reproductor incrustado de YouTube
     recuerda el volumen por dominio: si alguna vez se quedó a cero —o si
     lo dejó a cero otra pestaña— el vídeo se ve y no se oye, sin ningún
     indicio de que esté silenciado. Por eso, además de `unMute`, se pone
     el volumen al máximo explícitamente. */
  function mudo(si){
    try{
      if(!listo || !yt) return;
      if(si){ yt.mute(); }
      else  { yt.unMute(); if(yt.setVolume) yt.setVolume(100); }
    }catch(e){}
  }
  function calidad(q){
    if(!listo || !yt || !yt.setPlaybackQuality) return;
    if(q && q !== 'auto'){ try{ yt.setPlaybackQuality(q); }catch(e){} }
  }

  function mostrar(si){
    KL.$('#yt').style.display = si ? 'block' : 'none';
  }

  /* ---- Antiparones ---------------------------------------------------
     YouTube no avisa de que la red va mal: entra en BUFFERING y se queda
     ahí. Vigilamos ese estado y actuamos en escalera, cada escalón menos
     destructivo que el siguiente. La posición se guarda mientras suena,
     así que nunca se vuelve al principio.                              */
  function pararVigilancia(){ clearInterval(BUF.watch); BUF.watch = null; }

  function vigilar(){
    pararVigilancia();
    BUF.since = 0; BUF.step = 0;
    BUF.watch = setInterval(() => {
      if(!listo || !yt || !yt.getPlayerState) return;
      const st = yt.getPlayerState();

      if(st === 1){                                  // 1 = PLAYING
        BUF.last = yt.getCurrentTime() || BUF.last;
        if(BUF.since){ BUF.since = 0; BUF.step = 0; cb.onRecuperado && cb.onRecuperado(); }
        return;
      }
      if(st !== 3) return;                           // 3 = BUFFERING
      if(!KL.estado.antiCut) return;

      if(!BUF.since){ BUF.since = Date.now(); return; }
      const secs = (Date.now() - BUF.since) / 1000;
      const avisa = (t, s) => cb.onAtasco && cb.onAtasco(t, s);

      if(secs > 3 && BUF.step === 0){                // bajar un escalón
        BUF.step = 1;
        const cur = (yt.getPlaybackQuality && yt.getPlaybackQuality()) || 'medium';
        const nx  = QUAL[Math.min(qIndex(cur) + 1, QUAL.length - 1)];
        calidad(nx);
        avisa('Bajando calidad para no cortar', 'La conexión ha bajado · ' + nx);
        /* El antiparones ya sabe que la red va justa antes de que se note
           en la pantalla. Ese aviso vale mucho más para las canciones que
           vienen detrás que para esta, que ya está sonando. */
        cb.onRedFloja && cb.onRedFloja();
        return;
      }
      if(secs > 7 && BUF.step === 1){                // reintento en el mismo punto
        BUF.step = 2;
        avisa('Reintentando…', 'Recuperando desde ' + KL.fmt(BUF.last));
        try{ yt.seekTo(Math.max(0, BUF.last - 1), true); yt.playVideo(); }catch(e){}
        return;
      }
      if(secs > 14 && BUF.step === 2){               // recarga en el punto guardado
        BUF.step = 3;
        avisa('Recargando el vídeo', 'Desde ' + KL.fmt(BUF.last) + ' a calidad baja');
        /* La pista la sabe este módulo: es la que él mismo cargó. Antes se
           la pedía al estado global, que es dar un rodeo para acabar en el
           mismo sitio... salvo cuando no acababa. */
        const t = KL.reproductor.pistaActual();
        if(t){
          try{ yt.loadVideoById({ videoId:t.videoId, startSeconds:Math.max(0, BUF.last - 2),
                                  suggestedQuality:'small' }); }catch(e){}
        }
        return;
      }
      if(secs > 25 && BUF.step === 3){               // nos rendimos
        BUF.step = 4;
        cb.onRecuperado && cb.onRecuperado();
        cb.onRendicion && cb.onRendicion();
      }
    }, 1000);
  }

  return { id:'youtube', iniciar, cargar, play, pausa, parar, sonando,
           posicion, duracion, buscar, mudo, calidad, mostrar };
})();

/* ---------------------------------------------------------------------
   Fuente: archivo en disco (lo que ha bajado yt-dlp)

   Un <video> normal. Ni internet, ni error 153, ni cortes. Y, de
   propina, su audio SÍ se puede analizar con la Web Audio API, cosa que
   el iframe de YouTube no permite por ser de otro origen — eso es lo que
   hará posible el efecto reactivo de la pantalla pública.
   --------------------------------------------------------------------- */
KL.fuentes.archivo = (function () {

  let v = null, cb = {};

  function el(){ return v || (v = KL.$('#vlocal')); }

  function iniciar(callbacks){
    cb = callbacks || {};
    el().addEventListener('ended', () => cb.onFin && cb.onFin());
    el().addEventListener('playing', () => cb.onSonando && cb.onSonando(true));
    el().addEventListener('pause',   () => cb.onSonando && cb.onSonando(false));
    el().addEventListener('error',   () => cb.onError && cb.onError('archivo'));
  }

  function cargar(pista, opc){
    opc = opc || {};
    el().src = pista.local;
    el().currentTime = opc.desde || 0;
    if(opc.arrancar) el().play().catch(() => {});
  }

  function play(){ el().play().catch(() => {}); }
  function pausa(){ el().pause(); }
  function parar(){ el().pause(); el().removeAttribute('src'); el().load(); }
  function sonando(){ return !el().paused && !el().ended; }
  function posicion(){ return el().currentTime || 0; }
  function duracion(){ return el().duration || 0; }
  function buscar(seg){ try{ el().currentTime = seg; }catch(e){} }
  function mudo(si){ el().muted = !!si; if(!si) el().volume = 1; }
  function calidad(){ /* un archivo tiene la que tiene */ }
  function mostrar(si){ el().style.display = si ? 'block' : 'none'; }

  return { id:'archivo', iniciar, cargar, play, pausa, parar, sonando,
           posicion, duracion, buscar, mudo, calidad, mostrar };
})();

/* ---------------------------------------------------------------------
   El reproductor: elige fuente y hace de intermediario
   --------------------------------------------------------------------- */
KL.reproductor = (function () {

  let actual = null;        // la fuente en uso
  let pista  = null;        // la pista cargada
  let mudoSi = false;

  /* Qué fuente le toca a una pista. El día que una pista traiga `fuente`
     escrita —vimeo, twitch, nas…— manda ella; mientras tanto se deduce,
     que es lo que permite no tocar los datos ya guardados. */
  function detectar(t){
    if(!t) return KL.fuentes.youtube;
    if(t.fuente && KL.fuentes[t.fuente]) return KL.fuentes[t.fuente];
    if(t.local) return KL.fuentes.archivo;
    return KL.fuentes.youtube;
  }

  /* Los avisos salen por `KL.senales`, así que pueden escucharlos varios
     módulos a la vez. Las FUENTES de ahí abajo siguen con un solo
     callback a propósito: cada una tiene exactamente un suscriptor —este
     mediador— y meterlas en el reparto general solo serviría para que
     una fuente parada pudiera hablar por encima de la que suena.

     `iniciar(callbacks)` se mantiene por comodidad: registra el objeto
     entero de una vez. `onFin` acaba escuchando «reproductor:fin». */
  function iniciar(callbacks){
    KL.senales.oirObjeto('reproductor', callbacks);

    const soloLaQueSuena = (nombre, senal) => (...datos) => {
      /* Si no, el <video> al descargarse dispararía un 'fin' en mitad de
         la canción siguiente. */
      if(KL.fuentes[nombre] === actual) KL.senales.avisar('reproductor:' + senal, ...datos);
    };

    for(const nombre in KL.fuentes){
      KL.fuentes[nombre].iniciar({
        onFin:        soloLaQueSuena(nombre, 'fin'),
        onError:      soloLaQueSuena(nombre, 'error'),
        onSonando:    soloLaQueSuena(nombre, 'sonando'),
        onAtasco:     soloLaQueSuena(nombre, 'atasco'),
        onRedFloja:   soloLaQueSuena(nombre, 'redFloja'),
        onRecuperado: soloLaQueSuena(nombre, 'recuperado'),
        onRendicion:  soloLaQueSuena(nombre, 'rendicion')
      });
    }
  }

  function cargar(t, opc){
    const nueva = detectar(t);
    if(actual && actual !== nueva){ actual.parar(); actual.mostrar(false); }
    actual = nueva;
    pista  = t;
    actual.mostrar(true);
    actual.cargar(t, opc);
    actual.mudo(mudoSi);       // cada vídeo nuevo nace con volumen
  }

  function play(){ if(actual){ actual.play(); actual.mudo(mudoSi); } }
  function pausa(){ if(actual) actual.pausa(); }
  function alternar(){ if(!actual) return; actual.sonando() ? actual.pausa() : play(); }
  function parar(){ if(actual){ actual.parar(); } pista = null; }
  function sonando(){ return actual ? actual.sonando() : false; }
  function posicion(){ return actual ? actual.posicion() : 0; }
  function duracion(){ return actual ? actual.duracion() : 0; }
  function buscar(seg){ if(actual) actual.buscar(seg); }
  function calidad(q){ if(actual) actual.calidad(q); }
  function mudo(si){
    mudoSi = si === undefined ? mudoSi : !!si;
    if(actual) actual.mudo(mudoSi);
    return mudoSi;
  }
  const estaMudo = () => mudoSi;
  const fuenteDe = t => detectar(t).id;

  return { iniciar, cargar, play, pausa, alternar, parar, sonando,
           posicion, duracion, buscar, calidad, mudo, estaMudo, fuenteDe,
           pistaActual: () => pista };
})();
