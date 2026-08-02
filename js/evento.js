/* ═══════════════════════════════════════════════════════════════════
   evento.js — el motor de estados

   Esta es la pieza central de la versión 1.1, y conviene entender por
   qué existe antes de tocarla.

   La aplicación NO abre y cierra pantallas. Mantiene un único estado del
   evento, y cada superficie decide qué pinta a partir de él:

     ESPERA          el operador manda; la tele enseña la cola
     PREPARADA       pista cargada y quieta, esperando el Play
     LLAMADA         cuenta atrás: «ahora canta…», 5, 4, 3…
     INTERPRETACION  suena; el monitor del PC es del cantante
     FIN_ACTUACION   los segundos entre canción y canción

   Solo hay DOS superficies físicas: el monitor del PC y la tele. El
   operador y el cantante comparten el primero — es la misma ventana
   cambiando de modo, como un PowerPoint al pulsar F5.

   ── REGLA QUE NO SE ROMPE ────────────────────────────────────────────
   **Ninguna canción empieza sola. Nunca.** El único camino a
   INTERPRETACION es `arrancar()`, y a `arrancar()` solo se llega desde
   un gesto del operador: el botón Play, la barra espaciadora o el botón
   «Empezar» de la barra de abajo.

   El ciclo automático llega hasta PREPARADA y ahí se para. La canción
   queda cargada, la tele anuncia quién va, y la aplicación espera.
   Empezar en cuanto el vídeo anterior termina pilla al cantante
   subiendo, hablando o de espaldas, y obliga a rebobinar delante de
   todo el mundo. Los segundos que se ahorran no compensan.

   Si añades un camino nuevo a INTERPRETACION, tiene que salir de algo
   que haya pulsado una persona.

   ── Por qué existe LLAMADA ───────────────────────────────────────────
   Entre el «Empezar» del operador y la primera nota hacen falta unos
   segundos reales: el cantante está sentado, hay que darle el micro, la
   gente sigue aplaudiendo. Sin ese hueco, el operador acaba dando al
   Play tarde a propósito, que es peor.

   La cuenta atrás sale en las dos pantallas, se puede cancelar con Esc
   —el operador se equivoca de canción más de lo que parece— y no cuesta
   nada en carga porque el vídeo ya está precargado desde PREPARADA.
   Con `segLlamada = 0` no hay cuenta y se arranca directo.

   Lo que hace que esto salga barato: el estado se guarda dentro de
   data/estado.json, que la tele ya sondea cada segundo y medio desde la
   v1.0. No hay ningún canal de comunicación nuevo.

   Quien manda es el PC. La tele solo lee. Si mandaran las dos, se
   pelearían por decidir la siguiente canción.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

KL.evento = (function () {

  const S  = KL.estado;
  const EV = KL.EV;

  /* Transiciones permitidas. Cualquier otra es un error de programación,
     no un caso que haya que tolerar en silencio. */
  const PERMITIDO = {
    [EV.ESPERA]:         [EV.PREPARADA, EV.LLAMADA, EV.INTERPRETACION, EV.ESPERA],
    [EV.PREPARADA]:      [EV.LLAMADA, EV.INTERPRETACION, EV.ESPERA, EV.PREPARADA],
    [EV.LLAMADA]:        [EV.INTERPRETACION, EV.PREPARADA, EV.ESPERA],
    [EV.INTERPRETACION]: [EV.FIN_ACTUACION, EV.ESPERA, EV.PREPARADA],
    [EV.FIN_ACTUACION]:  [EV.PREPARADA, EV.LLAMADA, EV.ESPERA, EV.INTERPRETACION]
  };

  let cuenta = null;        // temporizador de FIN_ACTUACION
  let restan = 0;
  let llamada = null;       // temporizador de LLAMADA
  let quedan = 0;
  let hidratado = false;    // ¿hemos recibido ya el estado del servidor?
  /* La pantalla completa se pide UNA vez y no se suelta entre canciones:
     el navegador solo la concede tras un gesto del usuario, y en el
     encadenado automático no hay ninguno. */
  let pantallaPedida = false;

  /* ---- Por qué el estado lleva un contador ---------------------------
     Un cambio de estado dispara DOS escrituras seguidas —«sonando» y el
     estado del evento— y cada una devuelve el estado completo. La
     primera respuesta llega con el evento ANTERIOR dentro, y al
     aplicarla el motor retrocedía: se pulsaba «Volver al operador» y la
     aplicación se quedaba en INTERPRETACION, como si no se hubiera
     pulsado nada. Pasaba de verdad, y era invisible salvo mirando el
     estado a mano.

     Con un contador que solo sube, una respuesta atrasada se reconoce y
     se descarta. Comparar por hora no vale: el servidor sella en
     segundos y varios cambios caben en el mismo segundo. */
  let contador = 0;

  const estado = () => S.evento.estado || EV.ESPERA;

  /* ---- Cambio de estado ---------------------------------------------- */

  function pasarA(nuevo, opc){
    opc = opc || {};
    const viejo = estado();
    if(!(PERMITIDO[viejo] || []).includes(nuevo)){
      console.warn('[evento] transición no permitida:', viejo, '→', nuevo);
      return false;
    }

    S.evento = {
      estado:  nuevo,
      pistaId: opc.pistaId !== undefined ? opc.pistaId : S.evento.pistaId,
      recien:  opc.recien  !== undefined ? opc.recien  : S.evento.recien,
      segundos: opc.segundos || 0,
      n:       ++contador,
      desde:   Math.floor(Date.now() / 1000)
    };

    pintar();
    /* La tele se entera por el sondeo que ya existía. */
    if(!opc.silencioso) KL.comandos.publicarEvento(S.evento);
    return true;
  }

  /* Lo que llega del servidor. Solo refresca el aspecto: los mandos del
     reproductor son locales y del PC. Si esto moviera el vídeo, cada
     vuelta del sondeo lo cortaría por la mitad. */
  function recibir(ev){
    if(!ev || !ev.estado) return;

    /* Respuesta atrasada de una petición anterior: trae un estado que ya
       hemos dejado atrás. Descartarla, o el motor retrocede. */
    if(hidratado && (ev.n || 0) < contador) return;
    contador = Math.max(contador, ev.n || 0);

    S.evento = {
      estado:  ev.estado,
      pistaId: ev.pistaId ?? null,
      recien:   ev.recien   ?? null,
      segundos: ev.segundos ?? 0,
      n:        ev.n        ?? 0,
      desde:    ev.desde    ?? 0
    };

    if(!hidratado){
      hidratado = true;
      /* Recargar el operador en mitad de una canción significa que algo ha
         ido mal. Fingir que sigue sonando sería mentir: se deja la pista
         lista para volver a lanzarla con un Play. */
      if(ev.estado === EV.INTERPRETACION || ev.estado === EV.LLAMADA
         || ev.estado === EV.FIN_ACTUACION){
        const t = qGet(ev.pistaId);
        if(t) return preparar(t.id);
        return pasarA(EV.ESPERA, { pistaId:null });
      }
      if(ev.estado === EV.PREPARADA && qGet(ev.pistaId)) return preparar(ev.pistaId);
    }
    pintar();
  }

  /* ---- Las cuatro paradas del ciclo ----------------------------------- */

  /* 1. PREPARADA — la pista queda cargada y quieta.
        Aquí está la precarga: el vídeo se pide ahora, no al pulsar Play.
        El silencio entre canciones es el enemigo real de una fiesta. */
  function preparar(id){
    const t = qGet(id) || S.queue[0];
    if(!t) return pasarA(EV.ESPERA, { pistaId:null });
    pararCuenta();
    /* El estado PRIMERO: es quien dice qué se está preparando, y desde que
       `curId` se deduce de él, cargarlo antes dejaría al reproductor
       preguntando por una pista que el evento todavía no señala. */
    pasarA(EV.PREPARADA, { pistaId:t.id });
    KL.reproductor.cargar(t, { arrancar:false });
    KL.$('#vbT').textContent = KL.Actuacion.titulo(t);
    draw();
    return true;
  }

  /* 2. LLAMADA — el operador ha pulsado Empezar.
        Se anuncia en las dos pantallas y se cuenta atrás. Esto NO es una
        espera técnica: el vídeo ya está cargado desde PREPARADA. Es el
        hueco que necesita una persona para levantarse y coger el micro.
        Cancelable con Esc.

        La pantalla completa se pide aquí, y no al arrancar el vídeo,
        porque este es el momento en que hay un gesto del usuario detrás:
        el navegador no la concede de otra manera. */
  function arrancar(id){
    const t = qGet(id) || qGet(S.evento.pistaId) || S.queue[0];
    if(!t){ toast('La cola está vacía'); return false; }
    pararCuenta();
    pedirPantallaCompleta();

    /* Si aún no estaba preparada —se ha pulsado otra fila, o se venía de
       ESPERA— se carga ahora, quieta.

       La pregunta correcta es «¿qué tiene cargado el reproductor?», y esa
       la contesta el reproductor. Antes se le preguntaba al estado, que
       no lo sabe: sabe lo que DEBERÍA estar cargado. */
    const cargada = KL.reproductor.pistaActual();
    if(!cargada || cargada.id !== t.id){
      KL.reproductor.cargar(t, { arrancar:false });
      KL.$('#vbT').textContent = KL.Actuacion.titulo(t);
    }

    if(!S.segLlamada) return arrancarYa(t.id);

    pasarA(EV.LLAMADA, { pistaId:t.id, segundos:S.segLlamada });
    quedan = S.segLlamada;
    pintarLlamada();
    clearInterval(llamada);
    llamada = setInterval(() => {
      quedan--;
      pintarLlamada();
      if(quedan <= 0){ pararLlamada(); arrancarYa(t.id); }
    }, 1000);
    draw();
    return true;
  }

  function pararLlamada(){ clearInterval(llamada); llamada = null; quedan = 0; }

  /* Cancelar la llamada: pasa más de lo que parece —canción equivocada,
     el cantante se raja— y es mucho mejor que tener que parar el vídeo ya
     empezado delante de todo el mundo. */
  function cancelarLlamada(){
    if(estado() !== EV.LLAMADA) return false;
    pararLlamada();
    const t = qGet(S.evento.pistaId);
    t ? preparar(t.id) : espera();
    toast('Cancelado. Sigue preparada.');
    return true;
  }

  /* 3. INTERPRETACION — ahora sí suena. Solo se llega aquí desde
        arrancar(), o sea, desde algo que ha pulsado el operador. */
  function arrancarYa(id){
    const t = qGet(id);
    if(!t) return false;
    document.body.classList.remove('fallo-video');
    pararCuenta(); pararLlamada();

    KL.$('#vbT').textContent = KL.Actuacion.titulo(t);
    KL.reproductor.play();

    /* Una sola escritura. Antes eran dos —`sonando` y `evento`— y por eso
       hizo falta el contador monótono. El contador se queda, que protege
       de otras cosas, pero ya no está tapando esto. */
    pasarA(EV.INTERPRETACION, { pistaId:t.id });
    pedirPantallaCompleta();
    arrancarCronometro();
    draw();
    const fila = KL.$(`#que .it[data-id="${t.id}"]`);
    if(fila) fila.scrollIntoView({ block:'nearest', behavior:'smooth' });
    return true;
  }

  /* 4. FIN_ACTUACION — la canción ha terminado.
        Se retira de la cola (si así está configurado), se guarda en el
        historial y el operador recupera el monitor durante unos segundos
        para preparar lo siguiente sin prisa. */
  function terminar(){
    if(estado() !== EV.INTERPRETACION) return;
    const t = qGet(S.curId);
    const recien = t ? { title:KL.Actuacion.titulo(t), channel:KL.Actuacion.canal(t) } : null;
    const sig = pistaTrasId(S.curId);

    KL.reproductor.parar();
    pasarA(EV.FIN_ACTUACION, { pistaId:sig ? sig.id : null, recien });

    /* Una sola escritura para retirar, archivar y soltar «sonando»: si
       fueran tres, un móvil pidiendo a la vez podría colarse en medio. */
    KL.comandos.terminarActuacion(t ? t.id : null, S.retirar);

    if(!S.autoNext){ pararCuenta(); pasarA(EV.ESPERA, { pistaId:null }); return; }
    cuentaAtras();
  }

  /* 5. Vuelta al principio del ciclo: se PREPARA la siguiente, no se
        arranca. La cuenta atrás solo decide cuánto rato se queda la
        pantalla de aplausos en la tele. */
  function cuentaAtras(){
    pararCuenta();
    restan = S.segFin;
    pintarCuenta();
    cuenta = setInterval(() => {
      restan--;
      pintarCuenta();
      if(restan <= 0){
        pararCuenta();
        const sig = S.queue[0] ? (qGet(S.evento.pistaId) || S.queue[0]) : null;
        /* preparar(), no arrancar(). Aquí es donde se para el automatismo. */
        sig ? preparar(sig.id) : pasarA(EV.ESPERA, { pistaId:null });
      }
    }, 1000);
  }

  function pararCuenta(){ clearInterval(cuenta); cuenta = null; restan = 0; }

  /* ---- Saltos manuales ------------------------------------------------ */

  function siguiente(){
    const sig = pistaTrasId(S.curId);
    if(!sig){ return espera(); }
    estado() === EV.INTERPRETACION ? arrancar(sig.id) : preparar(sig.id);
  }

  function anterior(){
    /* Como en cualquier reproductor: si ya han pasado cuatro segundos,
       «anterior» es volver al principio de esta, no a la de antes. */
    if(estado() === EV.INTERPRETACION && KL.reproductor.posicion() > 4){
      KL.reproductor.buscar(0); return;
    }
    const i = S.queue.findIndex(t => t.id === S.curId);
    if(i > 0) estado() === EV.INTERPRETACION ? arrancar(S.queue[i-1].id) : preparar(S.queue[i-1].id);
  }

  function espera(){
    pararCuenta(); pararLlamada();
    document.body.classList.remove('fallo-video');
    KL.reproductor.parar();
    pasarA(EV.ESPERA, { pistaId:null, recien:null });
    draw();
  }

  /* Modo pánico. Alguien se raja a mitad, se ha puesto la canción
     equivocada, o entra alguien a hablar: una tecla y todo para, sale de
     la pantalla completa y vuelve el operador. Pasa en todas las
     fiestas y cuesta diez líneas. */
  function panico(){
    soltarPantallaCompleta();
    espera();
    toast('Todo parado. El micro es tuyo.');
  }

  /* ---- Pantalla completa ---------------------------------------------
     Sobre el documento entero, no sobre la ventanita de vídeo: así el
     modo Operador de los segundos entre canciones también se ve grande,
     y el cambio de modo es solo un atributo.                           */

  function pedirPantallaCompleta(){
    if(pantallaPedida || document.fullscreenElement) { pantallaPedida = true; return; }
    document.documentElement.requestFullscreen()
      .then(() => { pantallaPedida = true; })
      .catch(() => { /* el navegador exige un gesto: se seguirá sin ella */ });
  }

  function soltarPantallaCompleta(){
    pantallaPedida = false;
    if(document.fullscreenElement) document.exitFullscreen().catch(() => {});
  }

  /* ---- Pintar --------------------------------------------------------- */

  /* Qué se ve aquí en cada estado sale de js/estados.js, no de una
     cadena de `if` repartida por el archivo. La tele lee esa misma
     tabla. */
  function pintar(){
    const e = estado();
    const esc = KL.escena(e);
    document.body.dataset.evento = e;
    document.body.dataset.pantalla = esc.pc;
    /* La LLAMADA ya se ve en modo Interpretación: la cuenta atrás sale
       sobre el vídeo cargado, no sobre el puesto de mando, y así no hay
       un salto de interfaz justo cuando empieza a sonar. */
    if(esc.pc === 'interpretacion') KL.$('#vb').classList.remove('hide');
    /* En reposo la ventanita de vídeo estorba: enseña un rectángulo negro
       encima de la cola sin decir nada. Vuelve sola en cuanto hay algo. */
    if(e === EV.ESPERA && !S.openVideo) KL.$('#vb').classList.add('hide');
    pintarCuenta();
  }

  function pintarLlamada(){
    const caja = KL.$('#llamada');
    if(!caja) return;
    const t = qGet(S.evento.pistaId);
    caja.innerHTML =
      '<div class="et">Ahora canta</div>' +
      `<div class="tit">${esc(t ? KL.Actuacion.titulo(t) : '')}</div>` +
      `<div class="num">${Math.max(0, quedan)}</div>` +
      '<div class="pie">Esc para cancelar</div>';
  }

  function pintarCuenta(){
    const caja = KL.$('#cuenta');
    if(!caja) return;
    const barra = KL.escena(estado()).barra;
    const sig = qGet(S.evento.pistaId);

    if(barra === 'fin'){
      const quien = S.evento.recien ? S.evento.recien.title : '';
      caja.innerHTML =
        KL.icono('comprobado') +
        `<span class="txt">Se acabó <b>${esc(quien)}</b>. ` +
        (sig ? `Preparando <b>${esc(KL.Actuacion.titulo(sig))}</b>` : 'No queda nada en la cola') + '</span>' +
        `<span class="seg">${Math.max(0, restan)} s</span>` +
        (sig ? '<button class="btn g" id="bYaPrep">Preparar ya</button>' : '');
      const ya = KL.$('#bYaPrep');
      if(ya) ya.addEventListener('click', () => preparar(sig.id));
    } else if(barra === 'preparada'){
      const t = qGet(S.evento.pistaId);
      /* Este es el único botón que arranca una canción, junto al Play de
         la barra y la barra espaciadora. Se pulsa cuando el cantante ya
         está delante del micro, no antes. */
      caja.innerHTML =
        KL.icono('microfono') +
        `<span class="txt">Preparada: <b>${esc(t ? KL.Actuacion.titulo(t) : '')}</b>` +
        ' — arranca cuando esté delante del micro</span>' +
        '<button class="btn" id="bYa">Empezar</button>';
      const ya = KL.$('#bYa');
      if(ya) ya.addEventListener('click', () => arrancar());
    }
  }

  /* ---- Lo que el reproductor cuenta y aquí se decide -------------------
     Que una canción se acabe, o que la conexión no dé para seguir, son
     dos maneras de que termine una actuación. La decisión es de este
     archivo, no del que pinta botones, y ahora puede escucharla
     directamente sin pasar por `app.js`.

     `terminar()` ya se protege sola: si no estamos en INTERPRETACION no
     hace nada. Por eso puede escucharse sin más comprobaciones. */
  KL.senales.oir('reproductor:fin', () => terminar());

  /* Se da por perdida ESTA canción, pero la siguiente queda PREPARADA, no
     sonando. Nadie empieza a cantar sin que el operador lo mande. */
  KL.senales.oir('reproductor:rendicion', () => terminar());

  return { iniciar:pintar, recibir, estado, pasarA,
           preparar, arrancar, arrancarYa, cancelarLlamada,
           terminar, siguiente, anterior, espera, panico,
           soltarPantallaCompleta };
})();
