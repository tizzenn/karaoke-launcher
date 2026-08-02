/* ===================================================================
   carteles.js — los QR y los cartelones del calentamiento

   Los códigos QR se generan aquí mismo, sin pedirle nada a nadie: en una
   fiesta puede no haber internet, y un QR que depende de un servicio
   externo es un QR que un día sale en blanco.

   Los cuatro cartelones rotan solos, y el operador puede apagar
   cualquiera o dejar uno fijo desde su menú, en directo.
   =================================================================== */
'use strict';

/* ---- QR ---------------------------------------------------------------
   Generados aquí mismo, sin salir a internet: lo que codifican es una
   dirección de la red local y la clave de la wifi, y son justo lo que
   hace falta la noche que el router de fuera falle.                    */
function cajaQR(texto, pie){
  return `<div class="qrcaja">${KL.qr.svg(texto, {borde:1})}<div class="pie">${esc(pie)}</div></div>`;
}
const hayWifiQR = () => !!(CFG.ssid && CFG.clave);
const puedenPedir = () => CFG.peticiones && !CFG.esLocal;

function pintarEsquina(){
  const caja = $('#esquina');
  if(!puedenPedir()){
    caja.innerHTML = `<div id="sinred"><svg class="ic"><use href="#ic-wifi-no"></use></svg>
      <span>${CFG.esLocal
        ? 'Sin red: hoy no se pueden enviar canciones desde el móvil'
        : 'Peticiones cerradas'}</span></div>`;
    return;
  }
  caja.innerHTML =
    (hayWifiQR() ? cajaQR(KL.qr.wifi(CFG.ssid, CFG.clave), 'Wifi') : '') +
    cajaQR(CFG.urlPedir, 'Pide aquí');
}

function pintarPasos(){
  if(hayWifiQR()){
    $('#picWifi').innerHTML = KL.qr.svg(KL.qr.wifi(CFG.ssid, CFG.clave), {borde:1});
    $('#tWifi').textContent = 'Conéctate a «' + CFG.ssid + '»';
  } else if(CFG.ssid){
    $('#tWifi').textContent = 'Conéctate a «' + CFG.ssid + '»';
    $('#dWifi').textContent = 'Pregunta la contraseña al de la casa';
  }
  if(puedenPedir()) $('#picPedir').innerHTML = KL.qr.svg(CFG.urlPedir, {borde:1});
  $('#dEnviar').textContent = CFG.limite
    ? `Hasta ${CFG.limite} canciones por persona en la cola`
    : 'Tu canción entra en la cola';
  if(CFG.conClave) $('#dPedir').textContent = 'Te pedirá la contraseña de la fiesta';
}

/* ---- Paneles del calentamiento ---------------------------------------- */
/* ---- Qué cartelones se enseñan ---------------------------------------
   Los elige el operador en directo desde su menú flotante: un cartelón
   puede estorbar sobre la marcha —el de la cola cuando está vacía, el de
   los datos cuando no dicen nada— y esperar a la siguiente fiesta para
   arreglarlo no sirve de nada.

   `activos = null` significa todos. Una lista vacía significa ninguno, y
   es una elección legítima: deja la tele con el cartel limpio. Por eso se
   distingue una cosa de la otra. */
const TODOS = ['pedir', 'cola', 'funciona', 'datos'];
let activos = null, fijo = null;
let panel = 0, panelT = null;

const listaActiva = () =>
  (activos === null ? TODOS : TODOS.filter(k => activos.includes(k)));

function girarPaneles(){
  clearInterval(panelT);
  /* Con uno fijo, o con uno solo activo, no hay nada que rotar: dejar el
     temporizador vivo solo serviría para repintar por gusto. */
  const lista = listaActiva();
  if(!fijo && lista.length > 1){
    panelT = setInterval(() => { panel++; pintarPanel(); }, 15000);
  }
  pintarPanel();
}
function pararPaneles(){ clearInterval(panelT); panelT = null; }

function pintarPanel(){
  const lista = listaActiva();
  const clave = fijo && lista.includes(fijo)
    ? fijo
    : (lista.length ? lista[panel % lista.length] : null);

  document.querySelectorAll('#calent .panel').forEach(p =>
    p.classList.toggle('on', p.dataset.k === clave));
  $('#puntos').innerHTML = lista.map(k =>
    `<i class="${k === clave ? 'on' : ''}"></i>`).join('');
}

/* Cambiar la selección desde el operador no puede reiniciar la rotación
   cada vuelta del sondeo: solo cuando de verdad cambia algo. */
let selAnterior = '';
function aplicarSeleccion(e){
  const nueva = JSON.stringify([e.paneles ?? null, e.panelFijo ?? null]);
  if(nueva === selAnterior) return;
  selAnterior = nueva;
  activos = Array.isArray(e.paneles) ? e.paneles : null;
  fijo    = e.panelFijo || null;
  panel   = 0;
  if(document.body.dataset.escena === 'calent') girarPaneles();
}

const CONSEJOS = [
  'Si no encuentras tu canción, pega el <b>enlace de YouTube</b> en el buscador: funciona igual.',
  'Busca por <b>título y grupo</b>. Cuanto más concreto, antes sale la versión buena.',
  'La cola se respeta: <b>el orden es el que se ve</b> en la pantalla.',
  'No hace falta instalar nada. Es una <b>página web</b>, se abre y ya está.'
];

function pintarCalentamiento(e){
  const cola = e.cola || [];
  const total = cola.reduce((a, t) => a + KL.Actuacion.segundos(t), 0);

  /* Aquí había una cuenta atrás. Se quitó: nadie sabe a qué hora empieza
     de verdad una fiesta, y cuando se agotaba, la tele cambiaba el
     calentamiento —útil— por la pantalla de espera —vacía—. Ahora dura
     hasta que suena la primera canción. Lo que se enseña en su lugar es
     lo único que sí es verdad: cuánta música hay ya pedida. */
  $('#reloj').innerHTML = cola.length
    ? `<b>${cola.length}</b> ${cola.length === 1 ? 'canción' : 'canciones'} en la cola`
    : 'Empezamos <b>en breve</b>';

  $('#colaSub').textContent = cola.length
    ? `${cola.length} ${cola.length === 1 ? 'canción pedida' : 'canciones pedidas'} · ${fmt(total)} de música`
    : 'Todavía no hay ninguna. Sé el primero.';
  $('#colaLista').innerHTML = cola.length
    ? cola.slice(0, 9).map((t, i) => `
        <div class="f"><span class="n">${i+1}</span>
        <span class="t">${esc(KL.Actuacion.titulo(t))}</span>
        <span class="d">${KL.Actuacion.segundos(t) ? fmt(KL.Actuacion.segundos(t)) : ''}</span></div>`).join('')
    : '<div class="vacia">La cola está vacía · escanea el QR y estrénala</div>';

  $('#nodoCola').textContent = cola.length ? `${cola.length} esperando` : 'esperando turno';
  $('#dCola').textContent = cola.length;
  $('#dBib').textContent = (e.biblioteca || []).length;
  $('#dMin').textContent = total ? fmt(total) : '—';
  $('#consejo').innerHTML = CONSEJOS[Math.floor(Date.now() / 15000) % CONSEJOS.length];
}
