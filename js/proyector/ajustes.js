/* ===================================================================
   ajustes.js — lo que esta pantalla decide por su cuenta

   El desfase con el ordenador, el efecto que reacciona al ruido, si esta
   pantalla da sonido. Son decisiones de ESTE aparato, no de la fiesta: la
   tele y el monitor no tienen por qué llevar el mismo desfase, y solo uno
   de los dos tiene altavoces.

   Por eso viven en la dirección y en localStorage, y no en el estado
   compartido. Se ponen desde el operador al abrir la ventana y se quedan
   por si alguien recarga a mano.
   =================================================================== */
'use strict';

'use strict';
const $ = s => document.querySelector(s);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c =>
  ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const fmt = s => { s = Math.max(0, Math.floor(s||0));
  const m = s/60|0, x = s%60; return m + ':' + String(x).padStart(2,'0'); };

/* `CFG` lo escribe PHP; está en el propio proyector.php. */

/* El modo —karaoke, dj, mc— llega en la dirección y solo cambia el color.
   Se recuerda por si alguien recarga la ventana de la tele a mano. */
(function(){
  const p = (new URLSearchParams(location.search).get('modo') || '').trim();
  const vale = m => ['karaoke','dj','mc'].includes(m) ? m : null;
  const m = vale(p) || vale(localStorage.getItem('karaoke_modo')) || 'karaoke';
  try{ localStorage.setItem('karaoke_modo', m); }catch(e){}
  document.documentElement.dataset.modo = m;
})();

let version = -1, arrancado = false, sonandoId = null, ultimo = null;

/* ¿Da esta pantalla el sonido? Lo dice el operador al abrir la ventana,
   por la dirección, y se recuerda por si alguien la recarga a mano.
   El aparato que no da el sonido se calla, pero sigue reproduciendo. */
/* ---- Sincronía con el ordenador --------------------------------------
   Esto YA NO compensa el arranque. Antes sí: las dos pantallas
   arrancaban «a la vez» y este número intentaba tapar la diferencia. No
   funcionaba, porque esa diferencia no es fija —depende de cuándo caiga
   el sondeo y de lo que tarde YouTube en soltar el primer fotograma— y
   compensar con una constante algo que varía es apuntar a un blanco que
   se mueve.

   Ahora el reproductor del cantante publica en qué instante estaba en el
   segundo cero, y esta pantalla salta a donde va. El arranque se cuadra
   solo y este número deja de hacer falta para eso.

   Se queda para lo único que el software NO puede saber ni corregir: que
   la electrónica de una tele tarde 250 ms en pintar lo que el navegador
   ya ha dibujado, o que el audio salga por un receptor con retardo. Eso
   ocurre DESPUÉS del navegador y no hay forma de detectarlo desde aquí.

   Por eso ya no se llama «retraso» sino CALIBRACION, y por eso el valor
   por defecto ha pasado de 0,7 a **cero**: en el caso normal —mismo
   ordenador, dos ventanas— no hay nada que calibrar. Se sigue afinando
   con + y - viendo las dos pantallas a la vez, que es la única forma
   honesta de ajustar algo que ocurre fuera del programa. */
let CALIBRACION = (function(){
  const p = parseFloat(new URLSearchParams(location.search).get('desfase'));
  if(Number.isFinite(p)){
    try{ localStorage.setItem('karaoke_desfase', String(p)); }catch(e){}
    return p;
  }
  /* La clave sigue siendo `karaoke_desfase`: quien ya tenía la suya
     afinada no la pierde. Se renombra lo que lee una persona. */
  const g = parseFloat(localStorage.getItem('karaoke_desfase'));
  return Number.isFinite(g) ? g : 0;
})();

function aplicarEfecto(){
  document.body.dataset.efecto = EFECTO;
  const b = $('#barras');
  if(EFECTO === 'barras' && !b.children.length){
    b.innerHTML = Array.from({length:32}, () => '<i></i>').join('');
  }
}

/* ---- El efecto y su intensidad ---------------------------------------
   Llegan del operador por la dirección, como el sonido y el desfase, y se
   recuerdan por si alguien recarga la ventana a mano. */
function guardado(clave, porDefecto, valida){
  const p = new URLSearchParams(location.search).get(clave);
  const usa = v => { try{ localStorage.setItem('karaoke_' + clave, String(v)); }catch(e){} return v; };
  if(p !== null && valida(p)) return usa(valida(p));
  try{
    const g = localStorage.getItem('karaoke_' + clave);
    if(g !== null && valida(g)) return valida(g);
  }catch(e){}
  return porDefecto;
}
const numero = (min, max) => v => {
  const n = parseFloat(v);
  return Number.isFinite(n) ? Math.min(max, Math.max(min, n)) : null;
};
const EFECTOS = ['halo', 'borde', 'barras', 'ninguno'];
let EFECTO   = guardado('efecto', 'halo', v => EFECTOS.includes(v) ? v : null);
const GAN_CANCION = guardado('gc', 1,   numero(0, 4));
const GAN_CALENT  = guardado('gk', 2.2, numero(0, 4));

const MUDA = (function(){
  const p = new URLSearchParams(location.search).get('sonido');
  if(p === 'pc' || p === 'tele'){
    try{ localStorage.setItem('karaoke_salida_audio', p); }catch(e){}
    return p === 'pc';
  }
  try{ return localStorage.getItem('karaoke_salida_audio') === 'pc'; }catch(e){ return false; }
})();
let yt = null, listo = false, desfase = 0;   // desfase con el reloj del servidor
