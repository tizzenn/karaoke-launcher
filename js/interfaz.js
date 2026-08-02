/* ═══════════════════════════════════════════════════════════════════
   interfaz.js — el pegamento

   Enlaces al núcleo, avisos, modales y las funciones de dibujo que usa
   todo el mundo. Se carga después del almacén y antes que el resto:
   busqueda.js, cola.js, reproductor.js y evento.js dan por hecho que
   `S`, `toast()` y `KL.comandos` existen.

   Son scripts clásicos, no módulos ES: lo que se declara aquí arriba lo
   ven todos los demás. Ese es el motivo de que el orden de las etiquetas
   <script> en index.html esté escrito y no sea casualidad.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

const S = KL.estado;
const { $, $$, uid, fmt, iso, esc, unesc, icono, EV } = KL;

const load         = KL.prefs.cargar;
const guardarPrefs = KL.prefs.guardar;
function save(){ /* lo guarda el almacén */ }

/* ---- Avisos ---------------------------------------------------------- */
let tT;
function toast(m){
  const t = $('#toast');
  t.textContent = m;
  t.classList.add('on');
  clearTimeout(tT);
  tT = setTimeout(() => t.classList.remove('on'), 2300);
}

/* ---- Modales --------------------------------------------------------- */
const open  = s => $(s).classList.add('on');
const close = s => $(s).classList.remove('on');

/* ---- Consultas sobre el estado --------------------------------------- */
const inLib = vid => S.library.some(t => t.videoId === vid);
const qGet  = id  => S.queue.find(t => t.id === id);

/* La que va detrás de una dada. Si no hay nadie, null: el aviso se apaga
   en vez de mentir. */
function pistaTrasId(id){
  const i = S.queue.findIndex(t => t.id === id);
  if(i < 0) return S.queue[0] || null;
  return S.queue[i+1] || null;
}
const siguientePista = () => pistaTrasId(S.curId);

/* ---- Llegada de datos ------------------------------------------------
   Lo que hay que repintar cuando llegan datos nuevos, venga el cambio de
   este PC o del móvil de alguien. El almacén no sabe dibujar; esto no
   sabe guardar.                                                         */
function aplicar(e){
  S.library   = e.biblioteca || [];
  S.queue     = e.cola || [];
  S.historial = e.historial || [];
  if(e.peticiones !== undefined) S.peticiones = e.peticiones;
  if(e.con_clave  !== undefined){ S.conClave = e.con_clave; modo(); }
  if(e.edicion    !== undefined) aplicarEdicion(e.edicion);
  if(e.calentamiento !== undefined){
    S.calentamiento = e.calentamiento;
    document.body.classList.toggle('calentando', !!e.calentamiento);
  }
  if(e.paneles   !== undefined) S.paneles   = e.paneles;
  if(e.panelFijo !== undefined) S.panelFijo = e.panelFijo;
  if(typeof pintarPaneles === 'function') pintarPaneles();
  if(e.ip_local !== undefined) S.ipLocal = e.ip_local;
  if(e.puerto   !== undefined) S.puerto  = e.puerto;
  dibujarRed();

  /* El estado del evento manda sobre la interfaz, y llega del mismo
     sitio que la cola. Si el archivo es de una versión anterior y no lo
     trae, se asume ESPERA: la aplicación sigue arrancando. */
  if(e.evento) KL.evento.recibir(e.evento);

  draw();
}

function draw(){
  drawLib();
  drawQue();
  drawNP();
  dibujarSiguiente();
}

/* ---- Modo karaoke / DJ / MC -----------------------------------------
   Lo único que cambia es el color principal y la palabra que se añade a
   las búsquedas. El color se resuelve entero en CSS a partir del
   atributo; aquí no se toca ninguna regla.                             */
/* ---- Los tres espacios -----------------------------------------------
   No son «modos»: son tres cosas distintas que se hacen con música en una
   fiesta, y cada una busca otra cosa.

   El color solo no basta para saber en cuál estás. Alguien que abre la
   aplicación por primera vez ve verde, morado o naranja y no deduce nada:
   hay que ponerle el nombre delante. Por eso cada espacio trae también un
   rótulo y un texto para el buscador.

   `mc` era el nombre viejo del tercero y se traduce al cargar: hay
   preferencias guardadas con esa palabra. */
const ESPACIOS = {
  karaoke: {
    nombre: 'Karaoke', icono: 'microfono', sufijo: 'karaoke',
    lema: 'Canciones con letra para cantar',
    buscar: 'Buscar karaokes, o pegar un enlace de YouTube…'
  },
  dj: {
    nombre: 'Cabina DJ', icono: 'musica', sufijo: '',
    lema: 'La música de la fiesta cuando no canta nadie',
    buscar: 'Buscar música o videoclips para la fiesta…'
  },
  freestyle: {
    nombre: 'Freestyle', icono: 'freestyle', sufijo: 'instrumental',
    lema: 'Instrumentales y bases para cantar, rapear o tocar encima',
    buscar: 'Buscar instrumentales o bases…'
  }
};

/* Los nombres viejos siguen llegando desde localStorage. */
const ESPACIO_VIEJO = { mc: 'freestyle' };

function aplicarModo(m){
  m = ESPACIO_VIEJO[m] || m;
  if(!ESPACIOS[m]) m = 'karaoke';
  const e = ESPACIOS[m];
  S.modo = m;
  /* El sufijo va con el espacio: es la mitad de lo que significa. Sin
     esto, «DJ» solo cambiaba el color y seguía buscando karaokes. */
  S.suffix = S.sufijos[m] ?? e.sufijo;
  document.documentElement.dataset.modo = m;

  /* Y aquí está lo que faltaba: DECIRLO. */
  const rot = $('#espacio');
  if(rot) rot.innerHTML = '<span class="lema">' + esc(e.lema) + '</span>';
  const q = $('#q');
  if(q) q.placeholder = e.buscar;
  $$('#espacios .esp').forEach(b =>
    b.classList.toggle('on', b.dataset.esp === m));
  /* Los atajos de Freestyle solo tienen sentido en Freestyle. */
  const fs = $('#freestyle');
  if(fs) fs.classList.toggle('on', m === 'freestyle');

  const campo = $('#cSuf');
  if(campo) campo.value = S.suffix;
  guardarPrefs();
}

/* La marca de la edición: la única diferencia visible entre la versión
   pública y la de casa, y vive en la pública. Si `edicion` viene vacía
   —el caso normal— se ve el título de siempre. */
function aplicarEdicion(txt){
  S.edicion = String(txt || '');
  if(S.edicion){
    document.body.dataset.edicion = S.edicion;
    $('#edicion').textContent = S.edicion;
  } else {
    delete document.body.dataset.edicion;
  }
}

/* ---- Vistas ---------------------------------------------------------- */
const VIEWS = ['full','compact','mini'];
const VNAME = { full:'Vista completa', compact:'Compacta · solo la cola', mini:'Mini · barra discreta' };

function setView(v){
  S.view = v; guardarPrefs();
  document.body.classList.toggle('compact', v==='compact');
  document.body.classList.toggle('mini',    v==='mini');
  $('#bView').innerHTML = icono(v==='mini' ? 'pantalla-completa' : 'disposicion');
  $('#bView').title = 'Disposición: ' + VNAME[v] + '  (tecla V)';
  medirBarraMini();
}

/* Cuánto mide la barra flotante de la vista mini, para que la ventana del
   vídeo se apoye justo encima y no le tape los botones. Se mide en vez de
   suponerlo: la barra cambia de alto según haya canción cargada o no, y
   una constante en el CSS acertaba en un caso y fallaba en el otro. */
function medirBarraMini(){
  if(!document.body.classList.contains('mini')) return;
  const app = $('#app');
  if(app) document.documentElement.style.setProperty('--alto-mini', app.offsetHeight + 'px');
}
addEventListener('resize', medirBarraMini);
if(window.ResizeObserver){
  addEventListener('DOMContentLoaded', () => {
    const app = $('#app');
    if(app) new ResizeObserver(medirBarraMini).observe(app);
  });
}

/* La biblioteca se pliega a una estrellita para no molestar en la fiesta. */
function setLibCol(on){
  S.libCol = !!on; guardarPrefs();
  document.body.classList.toggle('libcol', S.libCol);
  $('#bLibCol').title = S.libCol ? 'Abrir la biblioteca' : 'Plegar la biblioteca';
}

/* ---- Estado de la clave ---------------------------------------------- */
/* La insignia solo aparece cuando falta la clave, que es cuando hay algo
   que hacer. Decir «YouTube» con la clave puesta era decorar: ocupaba
   sitio en la cabecera y no cambiaba ninguna decisión. */
function modo(){
  const b = $('#modo');
  b.style.display = S.conClave ? 'none' : '';
  b.textContent = S.conClave ? 'YouTube' : 'Sin clave';
  b.className = 'badge ' + (S.conClave ? 'live' : 'demo');
  b.title = S.conClave ? 'Clave configurada en el servidor'
                       : 'Sin clave: pega enlaces de YouTube, o pulsa aquí para configurarla';
  b.style.cursor = S.conClave ? 'default' : 'pointer';
  b.onclick = S.conClave ? null : () => location.href = 'ajustes.php';
}

/* ---- Aviso de red al pie --------------------------------------------
   Lo que rompe las peticiones desde el móvil —el cortafuegos, una wifi
   de invitados con aislamiento, o abrir la página en localhost— no se
   descubre hasta que alguien lo intenta, y entonces ya hay cola en el
   micrófono. Por eso está siempre a la vista.

   Lo que importa NO es si el PC va por wifi o por cable: es si tiene una
   dirección de red local a la que puedan llegar los móviles. Un PC por
   cable al mismo router funciona perfectamente.

   Y tampoco importa la URL de ESTA ventana. Karaoke.bat abre la
   aplicación en `localhost` a propósito, porque es lo que funciona
   siempre en el propio PC; el servidor escucha en 0.0.0.0 y los móviles
   entran por la IP de la red. Mirar `location.hostname` decía «no hay
   red» con la red perfectamente montada. La dirección buena la calcula
   el servidor y llega en `ip_local`.                                   */
function dibujarRed(){
  const barra = $('#redbar');
  if(!barra) return;
  const puedePedir = S.peticiones && !!S.ipLocal;

  /* Durante el calentamiento la barra dice lo que está viendo la gente:
     el operador no tiene la tele delante y necesita saberlo. */
  if(S.calentamiento){
    barra.classList.remove('mal');
    barra.innerHTML = icono('tv') +
      '<span>La tele está en <b>calentamiento</b>: explica cómo pedir canciones y enseña la cola. ' +
      'Se quita sola al arrancar la primera.</span>';
    $('#bCal').classList.add('act');
    return;
  }
  $('#bCal').classList.remove('act');

  barra.classList.toggle('mal', !puedePedir);
  if(!S.ipLocal){
    barra.innerHTML = icono('wifi-no') +
      '<span>Este PC <b>no tiene dirección de red local</b>: los móviles no pueden llegar. ' +
      'Conéctalo al router, por wifi o por cable.</span>';
  } else if(!S.peticiones){
    barra.innerHTML = icono('wifi-no') +
      '<span>Peticiones desde el móvil <b>desactivadas</b> en los ajustes del servidor.</span>';
  } else {
    barra.innerHTML = icono('wifi') +
      `<span>Los móviles de tu red pueden pedir en <b>${esc(urlPedir())}</b></span>`;
  }
}

/* La dirección que hay que dar a los móviles. Siempre la del servidor,
   nunca la de la barra del navegador. */
function urlPedir(){
  if(!S.ipLocal) return '';
  const p = S.puerto && S.puerto !== 80 ? ':' + S.puerto : '';
  return 'http://' + S.ipLocal + p + location.pathname.replace(/[^/]*$/, '') + 'pedir.php';
}

/* ---- Aviso de quién canta después ------------------------------------ */
function dibujarSiguiente(){
  const vb = $('#vb');
  vb.classList.remove('nx-der','nx-izq','nx-no');
  vb.classList.add('nx-' + S.avisoSig);

  const t = siguientePista();
  const caja = $('#vnext');
  if(!t || S.avisoSig === 'no'){ caja.classList.remove('on'); return; }

  /* Si la pidió alguien, su nombre manda: es a quien hay que avisar. */
  $('#vnextQ').textContent = KL.Actuacion.rotulo(t);
  $('#vnextT').textContent = KL.Actuacion.laPidio(t)
                             ? KL.Actuacion.titulo(t)
                             : KL.Actuacion.canal(t);
  caja.classList.add('on');
}

/* ---- Lo que suena, en la barra de abajo ------------------------------- */
/* El título de la pestaña dice qué está sonando. Con tres ventanas
   abiertas —operador, tele, ajustes— la barra del navegador es lo único
   que las distingue, y de paso se ve la canción sin cambiar de ventana. */
function tituloPestana(){
  const t = S.curId && qGet(S.curId);
  const e = (typeof ESPACIOS !== 'undefined' && ESPACIOS[S.modo]) || { nombre:'Karaoke' };
  document.title = t
    ? '▶ ' + KL.Actuacion.titulo(t) + ' · ' + e.nombre
    : '🎤 ' + e.nombre + ' · Karaoke Launcher';
}

function drawNP(){
  tituloPestana();
  const t = S.curId && qGet(S.curId);
  $('#npT').textContent = t ? KL.Actuacion.titulo(t) : 'Nada en reproducción';
  $('#npC').textContent = t ? KL.Actuacion.canal(t) : 'Busca una canción para empezar';
  $('#npImg').src = t ? KL.Actuacion.caratula(t) : '';
  $('#npImg').style.visibility = t ? 'visible' : 'hidden';
  if(!t){ $('#skf').style.width='0'; $('#tc').textContent='0:00'; $('#tt').textContent='0:00'; }
}
