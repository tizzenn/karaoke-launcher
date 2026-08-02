/* ===================================================================
   reproductor.js — la segunda copia del vídeo

   Aquí no se recibe una señal del ordenador: se reproduce lo mismo por
   segunda vez, con su propio reproductor. Por eso existe el desfase, y
   por eso esta pantalla puede ir muda mientras el PC da el sonido.
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

function poner(pista){
  const v = $('#vlocal');
  v.muted = MUDA;
  if(listo && yt){
    try{ MUDA ? yt.mute() : yt.unMute(); if(!MUDA && yt.setVolume) yt.setVolume(100); }catch(e){}
  }
  /* Un desfase negativo significa que esta pantalla va ADELANTADA: no se
     puede empezar antes del segundo cero, así que se espera. */
  const salto = Math.max(0, DESFASE);
  const espera = Math.max(0, -DESFASE) * 1000;

  if(pista.local){
    if(listo && yt) yt.stopVideo();
    $('#yt').classList.add('oculto');
    v.classList.remove('oculto');
    v.src = pista.local;
    const arranca = () => {
      try{ v.currentTime = salto; }catch(e){}
      v.play().catch(() => aviso('Pulsa la pantalla para que suene', 'mal'));
    };
    espera ? setTimeout(arranca, espera) : arranca();
    conectarAnalizadorVideo(v);
  } else {
    v.pause(); v.removeAttribute('src'); v.classList.add('oculto');
    $('#yt').classList.remove('oculto');
    const arranca = () => {
      if(listo && yt) yt.loadVideoById({videoId: pista.videoId, startSeconds: salto});
    };
    espera ? setTimeout(arranca, espera) : arranca();
  }
}
function parar(){
  const v = $('#vlocal');
  v.pause(); v.removeAttribute('src'); v.classList.add('oculto');
  if(listo && yt) yt.stopVideo();
  $('#yt').classList.add('oculto');
}
