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
      const e = await leer('api/estado.php?quien=tele&desde=' + version);
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

/* La marca de la esquina: quince segundos y se va sola. Suficiente para
   quien encendió sin leer nada; poco para que moleste desde el sofá. En
   la tele no puede quedarse un cartel toda la noche. */
function marcarVentana(){
  const d = document.createElement('div');
  d.id = 'marcaVentana';
  d.innerHTML = '<svg class="ic"><use href="#ic-tv"></use></svg> Pantalla del público';
  document.body.appendChild(d);
  setTimeout(() => d.classList.add('ida'), 15000);
  setTimeout(() => d.remove(), 16000);
}

$('#empezar').addEventListener('click', async () => {
  arrancado = true;
  const conMicro = $('#conMicro').checked;
  $('#arranque').remove();
  marcarVentana();
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

/* ---- El tema en la tele ----------------------------------------------
   Lo manda el servidor: la tele va vestida igual que el operador sin que
   nadie tenga que configurarla dos veces.

   Esta función estuvo **dentro** del `if` de la tecla E, pegada ahí por
   un descuido al escribirla. Con `'use strict'` una función declarada
   dentro de un bloque solo existe en ese bloque, así que `escenas.js` la
   llamaba y no existía: la tele se quedaba en Clásico con cualquier tema
   y nadie lo notó porque el fallo pasaba en silencio.

   Lo caro no fue el fallo: fue que no había ninguna prueba mirando la
   tele con un tema puesto. Ahora la hay. */
function aplicarTemaTele(t){
  document.documentElement.dataset.tema =
    ['clasico','fiesta','kids','show'].includes(t) ? t : 'clasico';
}

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

  /* Calibrar la pantalla con las dos delante, que es la única forma
     honesta de ajustar algo que ocurre fuera del programa. Ya no se toca
     casi nunca: el arranque lo cuadra `t0`, y esto solo hace falta si la
     tele tarda en pintar lo que el navegador ya ha dibujado.

     No hace falta saltar aquí: la corrección continua lo aplica sola en
     la siguiente vuelta, como mucho cuatro segundos después. */
  if(e.key === '+' || e.key === '=' || e.key === '-'){
    CALIBRACION = Math.round((CALIBRACION + (e.key === '-' ? -0.1 : 0.1)) * 10) / 10;
    CALIBRACION = Math.min(5, Math.max(-5, CALIBRACION));
    try{ localStorage.setItem('karaoke_desfase', String(CALIBRACION)); }catch(x){}
    aviso('Calibración de esta pantalla: ' + CALIBRACION.toFixed(1) + ' s');
  }
});

aplicarEfecto();
pintarEsquina();
pintarPasos();
pintarPanel();
leer('api/estado.php?quien=tele').then(e => pintar(e, true)).catch(() => {}).then(escuchar);

/* También la tele cachea: si el router parpadea a mitad de fiesta, la
   pantalla del público no se queda en blanco delante de todo el mundo. */
if('serviceWorker' in navigator){
  addEventListener('load', () => navigator.serviceWorker.register('sw.js').catch(() => {}));
}
