/* ═══════════════════════════════════════════════════════════════════
   atajos.js — durante una fiesta el ratón sobra

   El operador tiene una mano en el teclado y la otra sujetando algo. Y
   cuando llega el momento de dar el Play, hay tres personas mirando.
   Todo lo que se hace más de una vez por canción tiene tecla.

     Intro        buscar (con el foco en la barra)
     /            saltar a la barra de búsqueda
     Espacio      Play / Pausa
     →  ←         siguiente / anterior
     Ctrl+.       PÁNICO: para todo y vuelve al operador
     Esc          durante la cuenta atrás, la cancela
     F            pantalla completa
     Esc          cerrar lo que haya abierto; si no hay nada, salir del
                  modo Interpretación
     V            cambiar de vista (completa, compacta, mini)

   Ojo con Espacio: si se dispara mientras alguien escribe en el
   buscador, corta la canción en mitad de la actuación. De ahí la
   comprobación de `escribiendo`.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

/* Cerrado: nadie llama a nada de aquí. Los atajos se enganchan al
   documento y ahí se acaban. */
(function () {

function escribiendo(){
  const a = document.activeElement;
  return !!a && (['INPUT','TEXTAREA','SELECT'].includes(a.tagName) || a.isContentEditable);
}

function hayModalAbierto(){ return $$('.ov.on').length > 0; }

document.addEventListener('keydown', ev => {
  const esc_ = ev.key === 'Escape';

  /* El pánico funciona siempre, incluso escribiendo: es su razón de ser. */
  if(ev.ctrlKey && (ev.key === '.' || ev.code === 'Period')){
    ev.preventDefault();
    KL.evento.panico();
    return;
  }

  if(esc_){
    if(hayModalAbierto()){ $$('.ov.on').forEach(o => o.classList.remove('on')); return; }
    /* Cancelar la cuenta atrás no es lo mismo que el pánico: la canción
       sigue preparada y el vídeo cargado. Es el caso frecuente —canción
       equivocada, el cantante se raja— y merece salida propia. */
    if(KL.evento.cancelarLlamada()) return;
    if(document.body.dataset.pantalla === 'interpretacion'){ KL.evento.panico(); }
    return;
  }

  if(escribiendo()) return;

  if(ev.key === '/'){ ev.preventDefault(); $('#q').focus(); return; }

  if(ev.code === 'Space'){
    ev.preventDefault();
    const e = KL.evento.estado();
    if(e === KL.EV.INTERPRETACION) KL.reproductor.alternar();
    else if(e === KL.EV.LLAMADA) KL.evento.arrancarYa(KL.estado.evento.pistaId);
    else KL.evento.arrancar();
    return;
  }

  if(ev.key === 'ArrowRight'){ ev.preventDefault(); KL.evento.siguiente(); return; }
  if(ev.key === 'ArrowLeft'){  ev.preventDefault(); KL.evento.anterior();  return; }

  if(ev.key === 'f' || ev.key === 'F'){
    ev.preventDefault();
    if(document.fullscreenElement) document.exitFullscreen().catch(() => {});
    else document.documentElement.requestFullscreen().catch(() => {});
    return;
  }

  if(ev.key === 'v' || ev.key === 'V'){ $('#bView').click(); }
});

})();
