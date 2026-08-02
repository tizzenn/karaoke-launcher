/* ===================================================================
   servidor.js — enterarse de lo que pasa, y arrancar

   Sondeo cada segundo y medio, no algo más fino: `php -S` atiende una
   petición cada vez y en la fiesta hay varios aparatos preguntando.

   Y el arranque necesita un clic. No es un capricho: sin una interacción
   del usuario, el navegador no deja que suene nada.
   =================================================================== */
'use strict';

/* ---- Servidor ---------------------------------------------------------- */
async function leer(url){
  const r = await fetch(url);
  const j = await r.json();
  if(!j.ok) throw new Error(j.error || ('error ' + r.status));
  return j;
}

async function escuchar(){
  /* Se pregunta cada segundo y medio y se suelta. Retener la conexión,
     que sería lo suyo, deja clavado al servidor de PHP: atiende de una en
     una y la tele estaría acaparándolo mientras los móviles esperan. */
  for(;;){
    try{
      const e = await leer('api/estado.php?desde=' + version);
      if((e.version ?? 0) > version) pintar(e);
      else if(document.body.dataset.escena === 'calent') pintarCalentamiento(ultimo || e);
      await new Promise(r => setTimeout(r, 1500));
    }catch(err){
      aviso('Sin conexión con el karaoke. Reintentando…', 'mal');
      await new Promise(r => setTimeout(r, 5000));
    }
  }
}

/* ---- Arranque ---------------------------------------------------------- */
$('#quienSuena').innerHTML = MUDA
  ? '<b>Esta pantalla va sin sonido.</b> El sonido sale por el ordenador; aquí solo se ve.'
  : '<b>El sonido sale de aquí.</b> El ordenador lleva el mismo vídeo por su cuenta, '
    + 'en silencio: es quien encadena la siguiente.';

$('#empezar').addEventListener('click', async () => {
  arrancado = true;
  const conMicro = $('#conMicro').checked;
  $('#arranque').remove();
  try{ await document.documentElement.requestFullscreen(); }catch(e){}
  if(conMicro) await conectarMicro();
  if(ultimo) pintar(ultimo, true);
});

/* El ratón encima de la tele se ve; se esconde cuando nadie lo mueve. */
let quietoT = null;
addEventListener('mousemove', () => {
  document.body.classList.remove('quieto');
  clearTimeout(quietoT);
  quietoT = setTimeout(() => document.body.classList.add('quieto'), 3000);
  if(sonandoId && document.body.dataset.escena === 'video'){
    $('#franja').classList.remove('fuera');
    clearTimeout(pintar.t);
    pintar.t = setTimeout(() => $('#franja').classList.add('fuera'), 12000);
  }
});

addEventListener('keydown', e => {
  if(e.key === 'f' || e.key === 'F'){
    document.fullscreenElement ? document.exitFullscreen()
                               : document.documentElement.requestFullscreen().catch(() => {});
  }
  /* Cuatro posiciones para la lista de próximas: en cada casa la tele
     tiene delante una lámpara o la cabeza de alguien en otro sitio. */
  if(e.key === 'l' || e.key === 'L'){
    const lados = ['der','dercen','abajo','izq','arriba'];
    const i = lados.indexOf(document.body.dataset.lado);
    document.body.dataset.lado = lados[(i + 1) % lados.length];
  }
  if(e.key === 'm' || e.key === 'M') conectarMicro();

  if(e.key === 'e' || e.key === 'E'){
    EFECTO = EFECTOS[(EFECTOS.indexOf(EFECTO) + 1) % EFECTOS.length];
    try{ localStorage.setItem('karaoke_efecto', EFECTO); }catch(x){}
    aplicarEfecto();
    aviso('Efecto: ' + EFECTO);
  }

  /* Afinar la sincronía con las dos pantallas delante, que es la única
     forma de ajustar esto de verdad. Se aplica al saltar en el momento,
     para poder comprobarlo sin esperar a la siguiente canción. */
  if(e.key === '+' || e.key === '=' || e.key === '-'){
    DESFASE = Math.round((DESFASE + (e.key === '-' ? -0.1 : 0.1)) * 10) / 10;
    DESFASE = Math.min(5, Math.max(-5, DESFASE));
    try{ localStorage.setItem('karaoke_desfase', String(DESFASE)); }catch(x){}
    aviso('Retraso de esta pantalla: ' + DESFASE.toFixed(1) + ' s');
    if(sonandoId && DESFASE > 0){
      const t = (ultimo.cola || []).find(x => x.id === sonandoId);
      if(t && !t.local && listo && yt){
        try{ yt.seekTo(Math.max(0, yt.getCurrentTime() + (e.key === '-' ? -0.1 : 0.1)), true); }catch(x){}
      } else if(t && t.local){
        try{ $('#vlocal').currentTime += (e.key === '-' ? -0.1 : 0.1); }catch(x){}
      }
    }
  }
});

aplicarEfecto();
pintarEsquina();
pintarPasos();
pintarPanel();
leer('api/estado.php').then(e => pintar(e, true)).catch(() => {}).then(escuchar);
