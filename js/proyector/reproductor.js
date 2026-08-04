/* ===================================================================
   reproductor.js — la segunda copia del vídeo

   Aquí no se recibe una señal del ordenador: se reproduce lo mismo por
   segunda vez, con su propio reproductor, y por eso esta pantalla puede
   ir muda mientras el PC da el sonido.

   ── Cómo se sincroniza, y por qué así ────────────────────────────────
   NO se intenta arrancar a la vez que la otra pantalla. Eso es imposible
   entre dos reproductores de YouTube en dos ventanas: uno tarda 800 ms
   en soltar el primer fotograma y el otro 2 s, y además esta pantalla no
   se entera de que hay que arrancar hasta el siguiente sondeo.

   Lo que se hace es lo contrario: el reproductor del cantante publica
   **el instante en el que estaba en el segundo cero** (`evento.t0`), y
   esta pantalla calcula por dónde va la canción y salta ahí. Llegue
   tarde o pronto, converge.

   Y como converger una vez no basta —dos reproductores nunca corren
   exactamente al mismo ritmo—, se vuelve a comprobar cada pocos segundos
   y se corrige solo si el desvío es lo bastante grande para verse. Un
   `seekTo` cada dos por tres se nota más que el desfase que arregla.

   `CALIBRACION` (antes «retraso») ya no compensa el arranque: eso lo
   resuelve `t0`. Queda para lo único que el software no puede saber —que
   una tele tarde 250 ms en pintar lo que el navegador ya dibujó— y por
   eso en el 90 % de las instalaciones vale cero.
   =================================================================== */
'use strict';

/* ---- Reproductor ------------------------------------------------------ */
window.onYouTubeIframeAPIReady = function(){
  yt = new YT.Player('yt', {
    /* `origin` evita que YouTube rechace la comunicación con la página y
       pinte su propio «Se ha producido un error» dentro del marco. */
    playerVars:{autoplay:1, controls:0, rel:0, modestbranding:1,
                playsinline:1, iv_load_policy:3, disablekb:1,
                origin: location.origin},
    events:{
      onReady: () => {
        listo = true;
        if(MUDA) try{ yt.mute(); }catch(e){}
        if(ultimo) pintar(ultimo, true);
      },
      /* 101/150 = el dueño no deja incrustarlo. Aquí no pasamos a la
         siguiente: manda el ordenador, y allí ya salta el aviso. */
      onError: () => aviso('Ese vídeo no se puede ver aquí. Míralo en el ordenador.', 'mal')
    }
  });
};
(function(){ const s = document.createElement('script');
  s.src = 'https://www.youtube.com/iframe_api'; document.head.appendChild(s); })();

let avisoT = null;
function aviso(txt, tipo){
  clearTimeout(avisoT);
  let d = $('#mensaje');
  if(!txt){ if(d) d.remove(); return; }
  if(!d){ d = document.createElement('div'); d.id = 'aviso'; document.body.appendChild(d); }
  d.className = tipo || '';
  d.textContent = txt;
  avisoT = setTimeout(() => { const x = $('#mensaje'); if(x) x.remove(); }, 6000);
}

/* Por dónde debería ir la canción AHORA, según el reloj del cantante.
   Devuelve null si todavía no hay `t0` —el operador acaba de pulsar y su
   reproductor aún no ha soltado el primer fotograma—; en ese caso se
   empieza por el principio, que es lo correcto, y la primera corrección
   lo cuadra en cuanto llegue. */
function porDondeVa(ev){
  const t0 = ev && ev.t0;
  if(!t0) return null;
  const seg = (Date.now() - t0) / 1000 + CALIBRACION;
  /* Un valor absurdo —reloj de la tele descuadrado media hora— no se
     obedece: es peor saltar al minuto noventa que empezar de cero. */
  if(!isFinite(seg) || seg < -2 || seg > 60 * 60 * 4) return null;
  return Math.max(0, seg);
}

function poner(pista, ev){
  const v = $('#vlocal');
  v.muted = MUDA;
  if(listo && yt){
    try{ MUDA ? yt.mute() : yt.unMute(); if(!MUDA && yt.setVolume) yt.setVolume(100); }catch(e){}
  }
  const salto = porDondeVa(ev) || 0;

  if(pista.local){
    if(listo && yt) yt.stopVideo();
    $('#yt').classList.add('oculto');
    v.classList.remove('oculto');
    v.src = pista.local;
    try{ v.currentTime = salto; }catch(e){}
    v.play().catch(() => aviso('Pulsa la pantalla para que suene', 'mal'));
    conectarAnalizadorVideo(v);
  } else {
    v.pause(); v.removeAttribute('src'); v.classList.add('oculto');
    $('#yt').classList.remove('oculto');
    if(listo && yt) yt.loadVideoById({videoId: pista.videoId, startSeconds: salto});
  }
}

/* ---- La corrección continua -----------------------------------------
   Se mira cada pocos segundos y solo se toca si el desvío se ve. El
   umbral no es un número redondo por gusto: por debajo de un cuarto de
   segundo nadie distingue dos vídeos desincronizados, y cada `seekTo`
   cuesta un parpadeo. Corregir de más se nota MÁS que el problema. */
const DESVIO_MAXIMO = 0.35;
const CADA = 4000;
let vigilanciaT = null;

function vigilarSincronia(){
  clearInterval(vigilanciaT);
  vigilanciaT = setInterval(() => {
    const ev = (ultimo && ultimo.evento) || {};
    if(ev.estado !== 'INTERPRETACION' || !arrancado) return;
    const debe = porDondeVa(ev);
    if(debe === null) return;

    const v = $('#vlocal');
    const local = v && !v.classList.contains('oculto') && v.src;
    let va = null;
    if(local) va = v.currentTime;
    else if(listo && yt){ try{ va = yt.getCurrentTime(); }catch(e){ va = null; } }
    if(va === null || !isFinite(va) || va <= 0) return;

    const desvio = debe - va;
    if(Math.abs(desvio) < DESVIO_MAXIMO) return;
    /* Y si el desvío es enorme, casi siempre es que esta pantalla acaba
       de abrirse a mitad de canción. Da igual: el salto es el mismo. */
    if(local){ try{ v.currentTime = debe; }catch(e){} }
    else { try{ yt.seekTo(debe, true); }catch(e){} }
  }, CADA);
}
vigilarSincronia();
function parar(){
  const v = $('#vlocal');
  v.pause(); v.removeAttribute('src'); v.classList.add('oculto');
  if(listo && yt) yt.stopVideo();
  $('#yt').classList.add('oculto');
}
