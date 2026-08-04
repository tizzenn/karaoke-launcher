/* ════════════════════════════════════════════════════════════════════
   paneles.js — unirse a la fiesta, y cómo se ve la fiesta

   Dos ventanas que se abren, se miran y se cierran: los códigos para
   entrar y el tema. Ninguna decide nada.

   Están fuera de `app.js` porque `app.js` es el cableado —conecta un
   botón con la función a la que llama— y estas dos traían con ellas su
   tabla de contenidos, su HTML y sus textos. Lo que hizo saltar la
   alarma no fue leerlo: fue la prueba de presupuesto avisando de que
   `app.js` pasaba de 800 líneas. La respuesta correcta a eso casi nunca
   es subir el número.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

var KL = window.KL || {};
window.KL = KL;

(function () {

/* ---- QR para las peticiones -------------------------------------------
   Generado aquí mismo, sin salir a internet, y siempre con la dirección
   de red del servidor: la de la barra del navegador es `localhost` y
   solo sirve para quien está delante de este PC.                        */
/* ---- El tema, a un botón de distancia ---------------------------------
   Estaba solo dentro de `ajustes.php`, detrás de una contraseña opcional
   y en una página que hay que saber que existe. En la práctica eso
   significaba que los temas no existían: nadie va a buscar una función
   de la que no sabe nada.

   Cada tema se enseña con una muestra de sus colores y una frase de qué
   cambia. Un desplegable con cuatro nombres no dice nada — «Peques» no
   explica que además desaparece el QR y cambian todos los mensajes. */
/* Cada tema se describe por su OBJETIVO, no por su color. «Más rosa» no
   ayuda a elegir; «para un bar, que se lea» sí. */
const TEMAS_UI = [
  { id:'clasico', nombre:'Clásico',
    que:'Que se lea. Sobrio, nada brilla y nada se mueve. Para un bar a media luz.',
    colores:['#0d0f14','#22d97a','#141821'] },
  { id:'fiesta', nombre:'Fiesta',
    que:'Que se note que hay fiesta. Neón, degradados y cosas que laten.',
    colores:['#0a0713','#ff2fa8','#33e1ff'] },
  { id:'kids', nombre:'Peques',
    que:'Todo enorme y redondo, mucho contraste, sin QR y ni un mensaje que valore a nadie.',
    colores:['#fff3dc','#ff7a00','#ffd79a'] },
  { id:'show', nombre:'Show',
    que:'Que la tele parezca un escenario: negro, focos y letras gigantes.',
    colores:['#04060d','#ffc21a','#182543'] }
];

function pintarTemas(){
  const caja = $('#temaLista');
  if(!caja) return;
  caja.innerHTML = TEMAS_UI.map(t => `
    <button class="temaOp${t.id === S.tema ? ' on' : ''}" data-tema="${t.id}">
      <span class="muestra">${t.colores.map(c =>
        `<i style="background:${c}"></i>`).join('')}</span>
      <span class="txt"><b>${esc(t.nombre)}</b><small>${esc(t.que)}</small></span>
      ${t.id === S.tema ? icono('comprobado','sm') : ''}
    </button>`).join('');

  $$('#temaLista .temaOp').forEach(b => b.addEventListener('click', async () => {
    const nuevo = b.dataset.tema;
    if(nuevo === S.tema){ cerrar('#ovTema'); return; }
    /* Se pinta antes de que conteste el servidor: el cambio es visual y
       reversible, y esperar medio segundo a que vuelva el estado hace
       que parezca que el botón no ha hecho nada. Si la petición falla,
       el sondeo lo devuelve a su sitio en un segundo y medio. */
    aplicarTema(nuevo);
    pintarTemas();
    try{
      await KL.api('api/tema.php', { tema:nuevo });
      toast('Tema: ' + (TEMAS_UI.find(x => x.id === nuevo) || {}).nombre);
    }catch(e){ toast('⚠ ' + e.message); }
    cerrar('#ovTema');
  }));
}

$('#bTema').addEventListener('click', () => { pintarTemas(); abrir('#ovTema'); });

/* ---- Los dos QR -------------------------------------------------------
   Primero el de la wifi y después el de pedir, que es el orden en que se
   atasca la gente de verdad. Hasta que alguien no está en tu red, el QR
   de pedir canciones no le sirve absolutamente de nada, y es justo el
   momento en el que se abandona: nadie insiste dos veces con un código
   que no hace nada.

   El de la wifi solo se dibuja si hay contraseña. Sin ella el QR se
   genera igual pero no conecta, y un QR muerto es peor que ninguno:
   quien lo prueba concluye que la aplicación no funciona. */
function cajaQR(titulo, contenido, pie, texto){
  return `<div class="qrCaja">
    <h4>${titulo}</h4>
    <div class="qrPic">${contenido}</div>
    <div class="qrPie">${pie}</div>
    ${texto ? `<code>${esc(texto)}</code>` : ''}
  </div>`;
}

$('#bQR').addEventListener('click', () => {
  const url = urlPedir();
  const w = S.wifi || {};
  const hayWifi = !!(w.ssid && w.clave);

  const dePedir = !url
    ? cajaQR('Pedir canciones',
        `<div class="qrNo">${icono('aviso')}<span>Este PC no tiene dirección de
         red local. Conéctalo al router y vuelve a intentarlo.</span></div>`,
        'Sin red no hay peticiones desde el móvil.')
    : cajaQR('2 · Pedir canciones',
        KL.qr.svg(url, {borde:1}),
        'Ya en tu red, que apunten aquí y busquen su canción.',
        url);

  const deWifi = hayWifi
    ? cajaQR('1 · Conectarse al wifi',
        KL.qr.svg(KL.qr.wifi(w.ssid, w.clave), {borde:1}),
        'Apuntar con la cámara y aceptar la red <b>' + esc(w.ssid) + '</b>.')
    : cajaQR('1 · Conectarse al wifi',
        `<div class="qrNo">${icono('wifi-no')}<span>Falta la contraseña de tu
         wifi.</span></div>`,
        'Ponla en <b>Ajustes → Wifi de la fiesta</b> y este QR conectará ' +
        'el móvil de un invitado con solo apuntar la cámara.');

  $('#qrCuerpo').innerHTML = '<div class="qrFila">' + deWifi + dePedir + '</div>';
  abrir('#ovQR');
});


})();
