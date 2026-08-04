/* ===================================================================
   escenas.js — qué se ve, y solo eso

   Este archivo no decide nada: lee la fila que le toca en `js/estados.js`
   y la obedece. Cuando decidía por su cuenta, añadir el estado LLAMADA
   obligaba a acordarse de tocarlo, y no me acordé: la pantalla de
   calentamiento se quedó encima de la cuenta atrás.
   =================================================================== */
'use strict';

/* ---- Escenas ---------------------------------------------------------- */
/* Las escenas que son un cartel a pantalla completa. La de vídeo no está
   aquí porque es «ninguna»: se apagan todas y queda el vídeo debajo. */
const CARTELES = ['calent', 'espera', 'llamada', 'aplausos'];

/* El apagón y el confeti son opcionales (Ajustes → La tele → «Efectos
   escénicos»). `true` hasta que llegue el primer estado del servidor,
   que es exactamente lo que ya hacían antes de existir el ajuste. */
let efectosOn = true;

function mostrarEscena(cual){
  /* El confeti y el apagón solo se lanzan al ENTRAR en aplausos, no en
     cada sondeo mientras ya está ahí — si no, la lluvia no pararía nunca
     durante los segundos que dura la cuenta atrás de la tele. */
  if(cual === 'aplausos' && document.body.dataset.escena !== 'aplausos'){
    lanzarConfeti();
    destelloBlackout();
  }
  document.body.dataset.escena = cual || 'video';
  CARTELES.forEach(id => $('#' + id).classList.toggle('oculto', id !== cual));
  $('#franja').classList.toggle('oculto', !!cual);
  /* En la llamada y en los aplausos, la lista de próximas sobra: la
     pantalla ya está diciendo exactamente qué viene, y verlo dos veces
     con numeraciones distintas confunde. */
  /* Y en la de espera igual: la lista grande del centro ya empieza por la
     siguiente, así que la esquina repetía las mismas tres canciones con la
     misma numeración. La caja de «a continuación» solo tiene sentido
     encima del vídeo, que es cuando no hay ninguna lista a la vista. */
  if(cual) $('#proximas').classList.add('oculto');
  /* En el calentamiento el halo va mucho más marcado: ahí el objetivo es
     precisamente que la gente vea que la pantalla les responde. Durante
     la canción tiene que notarse sin robarle atención al vídeo. */
  document.documentElement.style.setProperty(
    '--ganancia', String(cual === 'calent' ? GAN_CALENT : GAN_CANCION));
  if(cual === 'calent'){ if(!panelT) girarPaneles(); }
  else pararPaneles();
}

/* ---- Confeti de los aplausos -------------------------------------------
   Piezas reales, cada una con su propio color, ancho, giro, deriva
   lateral y tiempo — no un patrón que se repite igual cada vez, que se
   nota enseguida en una fiesta que dura horas y ve esto treinta veces.
   Los cuatro colores salen de los tokens del tema activo (`--ac`,
   `--star`, `--info` y blanco), así que cambian solos con Clásico,
   Fiesta, Peques o Show sin tocar esta función. */
const CONFETI_COLORES = ['var(--ac)', 'var(--star)', 'var(--info)', '#fff'];
const CONFETI_PIEZAS = 34;

function lanzarConfeti(){
  const caja = $('#confeti');
  if(!caja) return;
  caja.innerHTML = '';
  if(!efectosOn) return;
  if(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  for(let i = 0; i < CONFETI_PIEZAS; i++){
    const p = document.createElement('span');
    p.className = 'confeti-p' + (Math.random() < .4 ? ' ronda' : '');
    p.style.left = (2 + Math.random() * 96) + '%';
    p.style.setProperty('--cc', CONFETI_COLORES[i % CONFETI_COLORES.length]);
    p.style.setProperty('--cw', (.5 + Math.random() * .7) + 'vw');
    p.style.setProperty('--cx', (Math.random() * 16 - 8) + 'vw');
    p.style.setProperty('--cr', Math.round(360 + Math.random() * 540) + 'deg');
    p.style.setProperty('--cd', (1.8 + Math.random() * 1.3).toFixed(2) + 's');
    p.style.setProperty('--ct', (Math.random() * .7).toFixed(2) + 's');
    caja.appendChild(p);
  }
}

/* ---- El "blackout" teatral --------------------------------------------
   Tapa el corte en seco de `parar()` al terminar el vídeo: opaco al
   instante (sin transición, para que no se vea el chasquido A TRAVÉS del
   fundido de entrada), aguanta un momento con la escena de aplausos ya
   montada detrás, y se retira con un fundido. El resultado es que el
   público ve un apagón breve y luego la celebración — nunca el frame
   congelado de YouTube ni el hueco en blanco del sondeo. */
let blackoutT1 = null, blackoutT2 = null;
function destelloBlackout(){
  const b = $('#blackout');
  if(!b || !efectosOn) return;
  if(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  clearTimeout(blackoutT1); clearTimeout(blackoutT2);
  b.classList.remove('desvanece');
  b.classList.add('on');
  blackoutT1 = setTimeout(() => b.classList.add('desvanece'), 350);
  blackoutT2 = setTimeout(() => b.classList.remove('on', 'desvanece'), 700);
}

function pintarProximas(cola, desdeId){
  const i = cola.findIndex(t => t.id === desdeId);
  const sig = (i >= 0 ? cola.slice(i + 1) : cola).slice(0, 3);
  const caja = $('#proximas');
  if(!sig.length){ caja.classList.add('oculto'); return; }
  caja.classList.remove('oculto');
  const cab = KL.TEXTOS.de(document.documentElement.dataset.tema || 'clasico', 'proximas');
  caja.innerHTML = '<div class="cab">' + esc(cab) + '</div>' + sig.map((t, n) =>
    `<div class="f"><span class="n">${n+1}</span><span class="t">${esc(KL.Actuacion.titulo(t))}</span></div>`).join('');
}

/* ---- La carta de reto del Modo Show ----------------------------------
   Encima de todo y sin tocar las escenas: el reto se lee mientras se
   canta, así que no puede ser una escena que tape el vídeo.

   `n` es lo que distingue una carta de la siguiente aunque el texto sea
   el mismo. Sin ese número, sacar dos veces seguidas el mismo reto no
   volvía a animar nada y parecía que el botón del operador se había
   roto. */
let cartaN = -1;
function pintarCarta(e){
  const caja = $('#carta');
  if(!caja) return;
  const r = (e.show || {}).reto || null;
  /* Y con el tema Show puesto, no con cualquiera. Las cartas de reto son
     del Modo Show: si el operador cambia a Clásico con una carta en
     pantalla, la carta se queda ahí colgada como un cartel que nadie
     sabe quitar — que es exactamente lo que pasaba.

     No se borra del estado al cambiar de tema a propósito: si vuelves a
     Show, la carta sigue donde estaba. Lo que cambia es si se enseña. */
  const enShow = (e.tema || 'clasico') === 'show';
  if(!enShow || !r || !r.texto){
    caja.classList.add('oculto'); cartaN = -1;
    document.body.classList.remove('conCarta');
    return;
  }
  document.body.classList.add('conCarta');
  if(r.n !== cartaN){
    cartaN = r.n;
    $('#cartaTxt').textContent = r.texto;
    /* Reiniciar la animación: quitar la clase, forzar un reflujo y
       volver a ponerla. Sin el reflujo el navegador junta las dos
       operaciones y no anima nada. */
    caja.classList.remove('entra');
    void caja.offsetWidth;
    caja.classList.add('entra');
  }
  caja.classList.remove('oculto');
}

/* Con la carta puesta, la escena baja: la tira ocupa la parte de arriba
   y el título se le metía debajo. Se hace con una clase en el body para
   que lo resuelva el CSS y no haya que medir nada. */

function pintar(e, forzar){
  ultimo = e;
  version = e.version ?? version;
  if(e.ahora) desfase = e.ahora - Math.floor(Date.now()/1000);
  if(e.tema) aplicarTemaTele(e.tema);
  if(e.efectos !== undefined) efectosOn = !!e.efectos;

  /* Solo las del espacio en el que está la fiesta. Cada espacio tiene su
     cola, y si la tele enseña la del karaoke mientras el operador está en
     la Cabina DJ, están contando cosas distintas delante de la gente. */
  const donde = e.espacio || 'karaoke';
  const cola = (e.cola || []).filter(t => (t.espacio || 'karaoke') === donde);
  const ev = e.evento || {estado:'ESPERA'};
  /* Qué toca enseñar sale de la tabla de js/estados.js, la misma que usa
     el operador. Incluida la columna `calienta`, que dice si el
     calentamiento puede tapar esta escena: antes eso era una condición
     suelta aquí abajo y se quedó sin actualizar al añadir un estado. */
  const escena = KL.escena(ev.estado);
  const enCalentamiento = !!e.calentamiento && escena.calienta;

  aplicarSeleccion(e);
  pintarCarta(e);
  /* El pulso de la fiesta. Se cuentan las que esperan en el espacio
     activo: son las que la gente puede ver que vienen. */
  if(KL.termometro)
    KL.termometro.pintar($('#termometro'),
      (e.cola || []).filter(t => (t.espacio || 'karaoke') === (e.espacio || 'karaoke')).length,
      e.tema, e.termometro);
  pintarProximas(cola, e.sonando);

  /* --- Calentamiento --- */
  if(enCalentamiento){
    if(sonandoId !== null || forzar){ parar(); sonandoId = null; }
    mostrarEscena('calent');
    pintarCalentamiento(e);
    $('#proximas').classList.add('oculto');   // la cola ya se ve en grande
    return;
  }

  /* --- Llamada: la cuenta atrás ---
     El segundo exacto lo lleva el PC, pero aquí se recalcula a partir de
     `evento.desde`: si la tele se abre a mitad de una cuenta atrás, entra
     con el número que toca en vez de empezar de cero. */
  if(escena.tele === 'llamada'){
    if(sonandoId !== null || forzar){ parar(); sonandoId = null; }
    mostrarEscena('llamada');
    const t = cola.find(x => x.id === ev.pistaId);
    $('#llamQuien').textContent = KL.Actuacion.quien(t);
    $('#llamTit').textContent   = KL.Actuacion.titulo(t);
    pintarNumero();
    return;
  }

  /* --- Fin de actuación: aplausos --- */
  if(escena.tele === 'aplausos'){
    if(sonandoId !== null || forzar){ parar(); sonandoId = null; }
    mostrarEscena('aplausos');
    $('#finQue').textContent = ev.recien ? ev.recien.title : '';
    const sig = cola.find(t => t.id === ev.pistaId) || cola[0];
    const T2 = c => KL.TEXTOS.de(document.documentElement.dataset.tema || 'clasico', c);
    $('#finSig').textContent = sig ? T2('aContinuacion') + KL.Actuacion.titulo(sig) : T2('seAcabo');
    return;
  }

  const hoy = cola.find(t => t.id === e.sonando);

  /* --- Nada sonando --- */
  if(!hoy || escena.tele !== 'video'){
    if(sonandoId !== null || forzar){ parar(); sonandoId = null; }
    mostrarEscena('espera');
    const preparada = escena.barra === 'preparada' ? cola.find(t => t.id === ev.pistaId) : null;
    const T = c => KL.TEXTOS.de(document.documentElement.dataset.tema || 'clasico', c);
    $('#esperaTit').innerHTML = preparada ? T('ahoraCanta') : T('esperaTitulo');
    /* Aquí ponía «Hay 4 canciones esperando», que es exactamente lo que
       el termómetro viene a sustituir: la aplicación contando su estado
       interno delante de gente que no sabe que hay una aplicación. Y con
       un número, que es lo peor — en cuanto lo lees te pones a calcular
       cuánto falta para la tuya.

       Con canciones esperando, el subtítulo se calla: lo que hay que
       decir sobre el ambiente lo dice el termómetro de abajo, y decirlo
       dos veces con dos tonos distintos es peor que no decirlo. */
    $('#esperaSub').textContent = preparada
      ? KL.Actuacion.titulo(preparada) + T('alMicro')
      : (cola.length ? '' : T('esperaVacia'));
    $('#esperaSub').classList.toggle('oculto', !preparada && !!cola.length);
    /* La lista empieza DESPUÉS de la que está preparada. Salía dos veces:
       arriba en grande —«Ahora canta X»— y otra vez como número 1 de la
       lista de abajo. Quien lo mira desde el fondo de la sala no está
       leyendo un listado, está buscando cuándo le toca, y ver la misma
       canción dos veces no ayuda: el 1 tiene que ser la siguiente. */
    const desde = preparada ? cola.findIndex(t => t.id === preparada.id) + 1 : 0;
    const restan = cola.slice(desde, desde + 6);
    $('#esperaLista').innerHTML = restan.map((t, n) =>
      `<div class="f"><span class="n">${n+1}</span><span class="t">${esc(KL.Actuacion.titulo(t))}</span></div>`).join('');
    return;
  }

  /* --- Suena algo --- */
  mostrarEscena(null);
  /* «Quién» en la franja de abajo: el nombre de quien la pidió si lo hay,
     y si no el título. Es la misma regla que en el aviso del operador. */
  $('#quien').textContent = KL.Actuacion.rotulo(hoy);
  $('#tema').textContent  = KL.Actuacion.canal(hoy);

  /* Solo se recarga si ha cambiado de canción: si no, cada vuelta del
     sondeo cortaría el vídeo por la mitad. */
  if(hoy.id !== sonandoId || forzar){
    sonandoId = hoy.id;
    if(arrancado) poner(hoy, ev);
    $('#franja').classList.remove('fuera');
    clearTimeout(pintar.t);
    pintar.t = setTimeout(() => $('#franja').classList.add('fuera'), 12000);
  }
}

/* La cuenta atrás late aquí, no en cada sondeo: preguntar al servidor
   una vez por segundo para dibujar un número sería tirar el servidor por
   la ventana, y `php -S` atiende una petición cada vez. */
let numT = null;
function pintarNumero(){
  clearInterval(numT);
  const paso = () => {
    const ev = (ultimo && ultimo.evento) || {};
    if(ev.estado !== 'LLAMADA'){ clearInterval(numT); return; }
    const total = ev.segundos || 5;
    const van = Math.floor(Date.now()/1000) + desfase - (ev.desde || 0);
    $('#llamNum').textContent = Math.max(0, total - van);
  };
  paso();
  numT = setInterval(paso, 250);
}
