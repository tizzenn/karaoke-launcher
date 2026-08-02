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

function mostrarEscena(cual){
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

function pintarProximas(cola, desdeId){
  const i = cola.findIndex(t => t.id === desdeId);
  const sig = (i >= 0 ? cola.slice(i + 1) : cola).slice(0, 3);
  const caja = $('#proximas');
  if(!sig.length){ caja.classList.add('oculto'); return; }
  caja.classList.remove('oculto');
  caja.innerHTML = '<div class="cab">A continuación</div>' + sig.map((t, n) =>
    `<div class="f"><span class="n">${n+1}</span><span class="t">${esc(KL.Actuacion.titulo(t))}</span></div>`).join('');
}

function pintar(e, forzar){
  ultimo = e;
  version = e.version ?? version;
  if(e.ahora) desfase = e.ahora - Math.floor(Date.now()/1000);

  const cola = e.cola || [];
  const ev = e.evento || {estado:'ESPERA'};
  /* Qué toca enseñar sale de la tabla de js/estados.js, la misma que usa
     el operador. Incluida la columna `calienta`, que dice si el
     calentamiento puede tapar esta escena: antes eso era una condición
     suelta aquí abajo y se quedó sin actualizar al añadir un estado. */
  const escena = KL.escena(ev.estado);
  const enCalentamiento = !!e.calentamiento && escena.calienta;

  aplicarSeleccion(e);
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
    $('#finSig').textContent = sig ? 'A continuación: ' + KL.Actuacion.titulo(sig) : 'Se acabó la cola';
    return;
  }

  const hoy = cola.find(t => t.id === e.sonando);

  /* --- Nada sonando --- */
  if(!hoy || escena.tele !== 'video'){
    if(sonandoId !== null || forzar){ parar(); sonandoId = null; }
    mostrarEscena('espera');
    const preparada = escena.barra === 'preparada' ? cola.find(t => t.id === ev.pistaId) : null;
    $('#esperaTit').innerHTML = preparada ? 'Ahora <span class="ac">canta</span>' : '🎤 Karaoke';
    $('#esperaSub').textContent = preparada
      ? KL.Actuacion.titulo(preparada) + '  ·  al micro'
      : (cola.length
          ? 'Hay ' + cola.length + (cola.length === 1 ? ' canción esperando.' : ' canciones esperando.')
          : 'Escanea el QR y pide la tuya.');
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
    if(arrancado) poner(hoy);
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
