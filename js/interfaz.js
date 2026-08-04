/* ═══════════════════════════════════════════════════════════════════
   interfaz.js — el vocabulario de la aplicación

   Aquí terminó el paso 6, y no como empezó.

   El plan decía «encapsular módulos» y este archivo iba a ser el último.
   Al medirlo salió esto: `S` se usa 186 veces fuera de aquí, `toast` 38,
   `qGet` 18. Encapsularlo de verdad significa escribir
   `KL.interfaz.toast(...)` doscientas cuarenta veces.

   Y eso sería **peor código**. `S` y `toast` no son globales por
   descuido: son el vocabulario del proyecto, como `$` lo es en las
   páginas que usan jQuery. Un vocabulario compartido no es acoplamiento,
   es un idioma; obligar a decir el apellido completo cada vez no
   desacopla nada, solo hace el texto más largo y más difícil de leer.

   Así que la decisión es la contraria, y a conciencia:

     · Este archivo va dentro de una función, como los demás.
     · Y publica **a propósito** una lista corta y cerrada de nombres,
       que está escrita abajo del todo y en ningún otro sitio.

   La diferencia con antes no es cuántos nombres hay sueltos. Es que
   antes eran treinta y nadie sabía cuáles hacían falta, y ahora son
   dieciséis, están enumerados, y hay una prueba que falla si aparece el
   diecisiete. Un global sin permiso es el que hace daño; uno declarado y
   contado, no.

   Se carga después del almacén y antes que el resto: busqueda.js,
   cola.js, reproductor.js y evento.js dan por hecho que este vocabulario
   existe. Ese es el motivo de que el orden de las etiquetas <script> en
   index.html esté escrito y no sea casualidad.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

(function () {

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

/* ---- Modales ---------------------------------------------------------
   Se llamaban `open` y `close`, y eso costó un fallo de los buenos.

   Al encapsular este archivo (paso 6) hubo que publicar el vocabulario a
   mano con `Object.assign(window, {...})`, y ahí dentro iban `open` y
   `close`. Es decir: **se sobrescribió `window.open`**, que es la función
   del navegador que abre ventanas.

   Consecuencia: el botón de la pantalla del público dejó de funcionar.
   `window.open('proyector.php?…')` acababa llamando a esta función, que
   se lo pasaba a `querySelector` y reventaba con «no es un selector
   válido». El botón parecía muerto y no lo estaba.

   Antes no pasaba porque una función global declarada con `const` NO
   sustituye a `window.open`: vive en el ámbito léxico, al lado. Asignarla
   a `window` sí lo sustituye. Encapsular y volver a exponer no es lo
   mismo que no encapsular.

   Ahora se llaman `abrir` y `cerrar`, y ningún nombre del vocabulario
   puede coincidir con uno del navegador: hay una prueba que lo comprueba. */
const abrir  = s => $(s).classList.add('on');
const cerrar = s => $(s).classList.remove('on');

/* ---- Consultas sobre el estado --------------------------------------- */
const inLib = vid => S.library.some(t => t.videoId === vid);
const qGet  = id  => S.queue.find(t => t.id === id);

/* ---- Cada espacio tiene su cola ---------------------------------------
   En el servidor hay una sola lista y cada actuación lleva escrito a qué
   espacio pertenece. Aquí se mira siempre la del espacio activo.

   Se hace filtrando y no con tres listas separadas porque el orden es el
   del array —y eso no cambia al filtrar— y porque así un archivo de la
   versión anterior sigue valiendo: lo que no traiga espacio es karaoke,
   que es lo único que había.

   Lo importante para quien lo usa: cambiar de espacio devuelve la cola
   exactamente donde la dejaste. No es un filtro sobre la misma lista, es
   otro sitio de trabajo. */
const colaDe = esp => (S.queue || []).filter(t => (t.espacio || 'karaoke') === esp);
const cola   = () => colaDe(S.modo);

/* La que va detrás de una dada, DENTRO de su espacio. Si no hay nadie,
   null: el aviso se apaga en vez de mentir. */
function pistaTrasId(id){
  const c = cola();
  const i = c.findIndex(t => t.id === id);
  if(i < 0) return c[0] || null;
  return c[i+1] || null;
}
const siguientePista = () => pistaTrasId(S.curId);

/* ---- Llegada de datos ------------------------------------------------
   Lo que hay que repintar cuando llegan datos nuevos, venga el cambio de
   este PC o del móvil de alguien. El almacén no sabe dibujar; esto no
   sabe guardar.                                                         */
function aplicar(e){
  S.library   = e.biblioteca || [];
  /* Antes de pisar la cola vieja: si ha entrado una pista que antes no
     estaba (venga del móvil o de aquí mismo), es el dato que necesita el
     aviso del copiloto para saber "hace cuánto que no pide nadie". */
  const idsAntes = new Set(S.queue.map(t => t.id));
  const nueva = e.cola || [];
  if(nueva.some(t => !idsAntes.has(t.id))) S.ultimaAnadida = Date.now();
  S.queue     = nueva;
  S.historial = e.historial || [];
  if(e.peticiones !== undefined) S.peticiones = e.peticiones;
  if(e.con_clave  !== undefined){ S.conClave = e.con_clave; modo(); }
  if(e.edicion    !== undefined) aplicarEdicion(e.edicion);
  if(e.tema       !== undefined) aplicarTema(e.tema);
  /* El espacio viene del servidor: si lo cambia el operador desde otro
     aparato, esta pantalla le sigue. */
  if(e.espacio    !== undefined && e.espacio !== S.modo) aplicarModo(e.espacio);
  /* La Cabina DJ se configura en ajustes.php, que es otra página: sus
     valores llegan con el estado y así cambian sin recargar esta. */
  if(e.ambiente   !== undefined){
    S.ambiente = e.ambiente;
    if(KL.ambiente) KL.ambiente.aplicar(e.ambiente);
    if(KL.pintarCabinaDJ) KL.pintarCabinaDJ();
  }
  if(e.calentamiento !== undefined){
    S.calentamiento = e.calentamiento;
    document.body.classList.toggle('calentando', !!e.calentamiento);
  }
  /* La carta de reto del Modo Show: puede haberla sacado el operador
     desde el móvil mientras esta ventana miraba otra cosa. */
  if(e.show      !== undefined) S.show      = e.show;
  if(e.wifi      !== undefined) S.wifi      = e.wifi;
  if(e.termometro!== undefined) S.termometro= e.termometro;
  if(e.paneles   !== undefined) S.paneles   = e.paneles;
  /* Los cartelones los puede cambiar el operador desde su menú flotante,
     y este repintado es para cuando el cambio viene de fuera. */
  if(KL.panelesDeCalentamiento) KL.panelesDeCalentamiento();
  if(e.ip_local !== undefined) S.ipLocal = e.ip_local;
  if(e.puerto   !== undefined) S.puerto  = e.puerto;
  dibujarRed();

  /* El estado del evento manda sobre la interfaz, y llega del mismo
     sitio que la cola. Si el archivo es de una versión anterior y no lo
     trae, se asume ESPERA: la aplicación sigue arrancando. */
  if(e.evento) KL.evento.recibir(e.evento);

  draw();
}

/* ---- La tarjeta «Ahora» ----------------------------------------------
   Quién canta y quién va después, que es en lo único que piensa un
   operador. Y la fase de la noche, que hasta ahora solo estaba en su
   memoria.

   Todo se DEDUCE del estado (MODELO §6): aquí no se guarda ni se
   conmuta nada, así que no puede quedarse desfasado. */
function dibujarAhora(){
  if(!KL.fase) return;
  const f = KL.fase.de(KL.fase.delEstado(S));
  document.body.dataset.fase = f.id;
  const ic = $('#faseIcono'); if(ic) ic.textContent = f.icono;
  const nb = $('#faseNombre'); if(nb) nb.textContent = f.nombre;
  const ps = $('#fasePista');  if(ps) ps.textContent = f.pista;

  /* AHORA: quien canta. Si no canta nadie, la que esta preparada — que
     es «quien va a cantar», la misma pregunta un momento antes. */
  const ev = S.evento || {};
  const cantando = ev.estado === 'INTERPRETACION' || ev.estado === 'LLAMADA';
  const actual = qGet(ev.pistaId) || null;
  const c = cola();

  const pon = (caja, quien, tit, t, etiqueta) => {
    const q = $(quien), d = $(tit), box = $(caja);
    if(!q || !d || !box) return;
    box.classList.toggle('vacia', !t);
    q.textContent = t ? KL.Actuacion.rotulo(t) : '—';
    d.textContent = t ? (KL.Actuacion.laPidio(t) ? KL.Actuacion.titulo(t)
                                                 : KL.Actuacion.canal(t)) : etiqueta;
  };
  pon('#afAhora', '#afAhoraQuien', '#afAhoraTit', actual,
      c.length ? 'Elige una y dale a Empezar' : 'No hay nadie en la cola');
  /* «Despues» se cuenta DESDE la actual, no desde el principio: si no,
     con una cancion sonando enseñaba la que ya esta sonando. */
  /* Sin nada preparado, la «siguiente» es la PRIMERA de la cola, no la
     segunda. Ponia c[1] y se saltaba una: con la cola llena y nadie
     preparado, la primera cancion no aparecia por ningun lado. */
  const sig = actual ? pistaTrasId(actual.id) : (c[0] || null);
  pon('#afSig', '#afSigQuien', '#afSigTit', sig, 'Nadie mas por ahora');

  /* La etiqueta cambia con el momento, porque no significa lo mismo
     «Ahora» mientras alguien canta que mientras se prepara. */
  const et = $('#afAhora .et');
  if(et) et.textContent = cantando ? 'Cantando' : (actual ? 'Preparada' : 'Ahora');

  dibujarTestigos();
}
KL.repintarAhora = dibujarAhora;

/* Los testigos: no son controles, son para no tener que preguntarse
   nada. El operador mira y sabe que la sala no se ha quedado muda. */
function dibujarTestigos(){
  const caja = $('#testigos');
  if(!caja) return;
  const ev = (S.evento || {}).estado || 'ESPERA';
  const sonandoCancion = ev === 'INTERPRETACION';
  /* La musica de espera solo se anuncia cuando de verdad toca: durante
     una cancion se aparta sola, y decir que suena seria mentir. */
  const ambiente = !!S.ambienteOn && !sonandoCancion && S.modo === 'karaoke';
  const tg = (on, txt, tit) =>
    `<span class="tg${on ? ' on' : ''}" title="${esc(tit)}"><span class="pt"></span>${esc(txt)}</span>`;
  caja.innerHTML =
    tg(ambiente, 'Música de espera',
       ambiente ? 'La sala no está en silencio: suena la música de fondo'
                : 'Ahora mismo no suena música de fondo') +
    tg(!!S.calentamiento, 'Calentamiento',
       'La tele explica cómo pedir canciones y enseña la cola') +
    tg(!!S.peticiones, 'Peticiones abiertas',
       'La gente puede pedir desde el móvil');
}

function draw(){
  KL.cola.pintarBiblioteca();
  KL.cola.pintarCola();
  if($('#ovHist').classList.contains('on')) KL.cola.pintarHistorial();
  if(KL.proponerCopiloto) KL.proponerCopiloto();
  drawNP();
  dibujarAhora();
  dibujarSiguiente();
  /* La carta que está en la tele puede haberla sacado otro aparato. */
  if(KL.show) KL.show.pintar();
  /* El mismo pulso que ve la sala. Con la cola del espacio activo. */
  if(KL.termometro) KL.termometro.pintar($('#termometro'), cola().length, S.tema);
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
  /* ── Cabina DJ ─────────────────────────────────────────────────────
     Esto NO es «la música entre canciones de karaoke». Es otra fiesta:
     una en la que nadie canta y la lista la hace la gente desde el
     móvil. La cola es la sesión, y por eso aquí las canciones se
     encadenan solas — la regla de «una canción no arranca sola» existe
     para que nadie se vea empujado a un micro, y aquí no hay micro.

     La música de fondo del karaoke se llama **Música ambiente** y vive
     dentro del espacio Karaoke, no aquí. Durante meses las dos cosas se
     llamaron «Cabina DJ» y esa palabra significaba dos cosas
     incompatibles a la vez. */
  dj: {
    nombre: 'Cabina DJ', icono: 'plato', sufijo: '',
    lema: 'Una fiesta sin karaoke: la lista la hacéis entre todos desde el móvil',
    buscar: 'Buscar música o videoclips para la sesión…',
    /* Sin actuaciones: nadie sale a cantar, así que ni cuenta atrás ni
       aplausos, y la siguiente entra sola. Un silencio de treinta
       segundos en una fiesta sin karaoke es un fallo, no una pausa. */
    encadena: true
  },
  /* Aquí había un tercero, **Freestyle**, y se ha quitado. No por
     limpieza: por la definición de espacio que está escrita en el propio
     MODELO §1. Un espacio es «otra manera de usar la música». Freestyle
     se diferenciaba de Karaoke en una sola casilla de aquella tabla —qué
     palabra se le pegaba a la búsqueda— y coincidía en todas las demás:
     alguien canta, hay actuaciones, no arranca sola.

     Era un filtro disfrazado de espacio. Y encima llevaba dos botones
     —Instrumentales, Bases— que eran `filtro.js` escrito a mano y solo
     para él. Ahora ese campo está a la vista en los dos espacios, así que
     lo único que Freestyle sabía hacer se puede hacer desde Karaoke
     escribiendo «instrumental». */
};

/* La tabla se publica en KL, y no solo como global suelta, porque
   `evento.js` necesita saber si el espacio activo encadena las canciones
   solo. Es un dato del espacio, no del motor: si viviera dentro del motor
   habría que tocarlo cada vez que se añada un espacio. */
KL.ESPACIOS_INFO = ESPACIOS;

/* Los nombres viejos siguen llegando desde localStorage. `mc` fue el
   nombre del tercer espacio antes de llamarse Freestyle, y Freestyle ya
   no existe: los dos caen en Karaoke, que es donde se hace lo mismo
   escribiendo el filtro. Nadie pierde nada; como mucho tiene que
   reescribir una palabra. */
const ESPACIO_VIEJO = { mc: 'karaoke', freestyle: 'karaoke' };

function aplicarModo(m){
  m = ESPACIO_VIEJO[m] || m;
  if(!ESPACIOS[m]) m = 'karaoke';
  /* El icono de la esquina cambia con el espacio, y no es adorno: es lo
     primero que se ve al mirar la pantalla desde dos metros. Micrófono
     para cantar, plato para pinchar, metrónomo para tocar encima. Se
     reconoce el espacio antes de leer nada. */
  const marca = $('.logo .m .ic use');
  if(marca) marca.setAttribute('href', '#ic-' + ESPACIOS[m].icono);
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

  /* El filtro, a la vista y con la pista del espacio. Cada uno recuerda
     el suyo: ir a la Cabina DJ y volver no puede obligar a reescribirlo
     (MODELO §1, «cada espacio conserva su contexto de trabajo»). */
  const info = (KL.filtro && KL.filtro.POR_ESPACIO[m]) || { pista:'' };
  const f = $('#filtro');
  if(f){ f.value = S.suffix; f.placeholder = info.pista; }
  if(KL.pintarPerfiles) KL.pintarPerfiles();
  if(KL.pintarFiltro) KL.pintarFiltro();

  /* El botón «bAuto» pregunta si se prepara sola la siguiente. En la
     Cabina DJ esa pregunta no existe: aquí SIEMPRE encadena, lo diga o
     no el botón (ver evento.js:terminar()). Dejarlo activo confundiría:
     parecería que apagarlo corta la sesión, y no es verdad. */
  const bAuto = $('#bAuto');
  if(bAuto){
    bAuto.disabled = !!e.encadena;
    bAuto.title = e.encadena
      ? 'En la Cabina DJ la lista siempre encadena sola'
      : 'Al terminar una canción, dejar la siguiente preparada (no la arranca)';
  }

  guardarPrefs();
}

/* ---- El tema -----------------------------------------------------------
   Cambia el aspecto Y las palabras. Vive en el servidor, no en cada
   aparato: la tele y el operador tienen que ir vestidos igual, y quien lo
   cambia lo cambia para la fiesta entera.

   Los textos que dependen del tema están en `KL.TEMAS`, no repartidos por
   las pantallas: si mañana hay un tema de Navidad, se añade una fila. */
const TEMAS_VALIDOS = ['clasico', 'fiesta', 'kids', 'show'];

function aplicarTema(t){
  if(!TEMAS_VALIDOS.includes(t)) t = 'clasico';
  S.tema = t;
  document.documentElement.dataset.tema = t;
  /* El Modo Show carga sus cartas la primera vez que el tema es Show, y
     no al abrir la aplicación: en Clásico ese archivo no se va a usar y
     pedirlo es una petición de más contra un servidor que atiende una
     cada vez. */
  if(KL.show) KL.show.iniciar();
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
   sitio en la cabecera y no cambiaba ninguna decisión.

   El texto es «Configura tu clave», no «Sin clave» (auditoría UX,
   2026-08-03): para quien abre esto por primera vez, la primera palabra
   que dice la aplicación de sí misma no debería ser una carencia. Sigue
   siendo la misma insignia, el mismo enlace a Ajustes, el mismo aviso de
   que los enlaces pegados funcionan igual sin ella — solo cambia cómo se
   nombra. */
function modo(){
  const b = $('#modo');
  b.style.display = S.conClave ? 'none' : '';
  b.textContent = S.conClave ? 'YouTube' : 'Configura tu clave';
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
    : '🎤 ' + e.nombre + ' · OpenKaraoke Center';
}

function drawNP(){
  tituloPestana();
  const t = S.curId && qGet(S.curId);
  $('#npT').textContent = t ? KL.Actuacion.titulo(t) : 'Nada en reproducción';
  $('#npC').textContent = t ? KL.Actuacion.canal(t) : 'Busca una canción para empezar';
  $('#npImg').src = t ? KL.Actuacion.caratula(t) : '';
  $('#npImg').style.visibility = t ? 'visible' : 'hidden';
  if(!t) KL.cronometro.limpiar();
}


/* ═══════════════════════════════════════════════════════════════════
   EL VOCABULARIO — la lista completa de lo que sale de este archivo

   Añadir un nombre aquí es una decisión, no un descuido: significa que
   pasa a formar parte del idioma que habla todo el proyecto. La prueba
   «El vocabulario de la interfaz está cerrado» falla si aparece uno que
   no esté en esta lista.
   ═══════════════════════════════════════════════════════════════════ */
Object.assign(window, {
  /* Los atajos del núcleo. No son de este archivo —viven en KL— pero se
     reparten desde aquí porque aquí es donde se abre el idioma. Cuando
     este archivo pasó a estar dentro de una función se llevó los atajos
     con él y la aplicación entera dejó de arrancar con un escueto «$$ is
     not defined». Un recordatorio de que encapsular no es gratis: hay que
     decir qué sale. */
  $, $$, uid, fmt, iso, esc, unesc, icono, EV,

  /* El estado en memoria y los avisos: esto es el idioma. */
  S, toast,

  /* Preguntas sobre el estado que se hacen en todas partes. */
  qGet, inLib, pistaTrasId, siguientePista, cola, colaDe,

  /* Pintar. */
  draw, drawNP, dibujarRed, dibujarSiguiente,

  /* Modales. */
  abrir, cerrar,

  /* Preferencias de este aparato y cómo se ven. */
  load, guardarPrefs, setView, setLibCol, aplicarModo, aplicarEdicion, aplicarTema,
  VIEWS, VNAME, ESPACIOS, urlPedir, modo,

  /* Lo que el almacén llama cuando llegan datos nuevos. */
  aplicar
});

})();
