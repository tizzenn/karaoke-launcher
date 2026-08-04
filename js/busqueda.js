/* ═══════════════════════════════════════════════════════════════════
   busqueda.js — encontrar el vídeo correcto a la primera

   Lo que se guarda es el vídeo, no la búsqueda. Al elegir un resultado
   se almacena su identificador, y reproducir es abrirlo directamente.
   Nada se busca dos veces. Ese es el origen de todo el rediseño.

   La barra hace de botón: escribir y pulsar Intro. El botón «Buscar en
   YouTube» que había aquí decía lo mismo dos veces y ocupaba el sitio
   del buscador.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

/* Encapsulado en el paso 6. De las seis funciones que había sueltas solo
   dos las llama alguien de fuera; las otras cuatro eran ámbito global por
   inercia, no por necesidad. */
(function () {

/* Extrae el ID de youtube.com/watch?v=, youtu.be/, /embed/, /shorts/ o
   de un ID pegado tal cual. Devuelve null si no es ninguna de esas.

   El dominio va anclado a propósito (2026-08-04, QA): la versión
   anterior buscaba «youtube.com/watch?v=» en cualquier parte de la
   cadena, así que «https://noesyoutube.com/watch?v=…» colaba —
   «youtube.com» es una subcadena de «noesyoutube.com». Ahora exige que
   justo antes venga el principio, «//» o un punto (para www./m.), y lo
   mismo para youtu.be. */
function parseYT(s){
  s = (s || '').trim();
  if(/^[A-Za-z0-9_-]{11}$/.test(s)) return s;
  const m = s.match(/(?:^|\/\/|\.)youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/)([A-Za-z0-9_-]{11})|(?:^|\/\/)youtu\.be\/([A-Za-z0-9_-]{11})/);
  return m ? (m[1] || m[2]) : null;
}

/* oEmbed: título y canal reales SIN clave de API. Es lo que arregla los
   «Pendiente — xxxx» y las carátulas que no cuadraban con el texto. */
async function resolveTitle(t){
  try{
    const r = await fetch('https://www.youtube.com/oembed?format=json&url='
              + encodeURIComponent('https://www.youtube.com/watch?v=' + t.videoId));
    if(!r.ok){ t.dead = r.status === 404 || r.status === 401; return false; }
    const j = await r.json();
    t.title   = j.title || t.title;
    t.channel = j.author_name || t.channel;
    if(j.thumbnail_url) t.thumb = j.thumbnail_url;
    t.resolved = true; t.dead = false;
    return true;
  }catch(e){ return false; }
}

async function resolveAll(force){
  const pend = S.library.filter(t => force || !t.title || /^Cargando|^Vídeo /.test(t.title));
  for(const t of pend){
    try{
      const j = await KL.api('api/buscar.php?id=' + encodeURIComponent(t.videoId));
      const v = j.items[0];
      await KL.comandos.actualizarPista(t.videoId,
              { title:v.title, channel:v.channel, thumb:v.thumb });
    }catch(e){}
  }
  toast('Títulos actualizados');
}

/* La búsqueda anterior se CANCELA, no solo se ignora.

   Ignorar la respuesta vieja evita que pise a la buena —que era el fallo—
   pero la petición sigue viajando, el servidor la atiende y, si hay clave
   de API, **gasta cuota de YouTube para nada**: cien unidades por
   búsqueda, y el plan gratuito da diez mil al día. Escribiendo deprisa se
   tiran tres o cuatro búsquedas por una. */
let enCurso = null;

async function search(text){
  /* El sufijo viaja en la dirección: depende del modo, que es una
     preferencia de este aparato y no del servidor. Vacío es una respuesta
     válida —el modo DJ busca tal cual— y por eso se manda siempre. */
  if(enCurso) enCurso.abort();
  enCurso = new AbortController();
  /* El tema viaja también: en Peques el servidor pide búsqueda segura y
     aparta lo que YouTube marca con la restricción de edad máxima. No es
     un ajuste del servidor porque el tema no lo es — es de la fiesta, y
     puede cambiar a media noche. */
  const j = await KL.api('api/buscar.php?q=' + encodeURIComponent(text)
                    + '&sufijo=' + encodeURIComponent(S.suffix || '')
                    + '&tema=' + encodeURIComponent(S.tema || 'clasico'),
                    null, { signal: enCurso.signal });
  return j.items || [];
}

/* Cada búsqueda lleva número, y solo pinta la última que se pidió.

   Sin esto hay una carrera de verdad: se escribe «queen», se pulsa
   Intro, se sigue escribiendo «quilapayun» y se vuelve a pulsar. Si la
   primera petición tarda más que la segunda —cosa normal, dependen de
   YouTube— llega después y **pisa los resultados buenos con los
   viejos**. La pantalla enseña «queen» habiendo buscado otra cosa, y no
   hay ningún error en ninguna parte: simplemente la respuesta equivocada
   fue la última en llegar.

   El arreglo no es cancelar la petición vieja —no se puede cancelar lo
   que ya viaja— sino ignorar su respuesta. */
let peticion = 0;

async function doSearch(){
  const text = $('#q').value.trim();
  if(!text){ $('#q').focus(); return; }
  const mia = ++peticion;
  S.sel = null;
  abrir('#ovRes');
  $('#res').innerHTML = '<div class="load"><div class="sp"></div>Buscando en YouTube…</div>';
  $('#resF').textContent = '';
  try{
    const items = await search(text);
    if(mia !== peticion) return;      // ha llegado tarde: ya hay otra búsqueda
    S.results = items;
    drawResults();
  }catch(e){
    if(mia !== peticion) return;      // el error también llega tarde
    if(e && e.name === 'AbortError') return;   // la cancelamos nosotros
    /* El consejo depende del fallo: si no hay servidor, el engranaje no
       arregla nada y solo despista. */
    const esDeClave = /clave|cuota|API|habilitada/i.test(e.message);
    const consejo = esDeClave
      ? 'Revísala en los ajustes del servidor.'
      : 'Mientras tanto puedes pegar el enlace de YouTube aquí mismo.';
    $('#res').innerHTML = `<div class="load" style="color:var(--dang)">⚠ ${esc(e.message)}
      <div style="margin-top:10px;color:var(--txt3)">${esc(consejo)}</div></div>`;
  }
}

function drawResults(){
  const r = S.results, box = $('#res');

  if(!r.length){
    box.innerHTML = S.conClave
      ? '<div class="load">Sin resultados. Prueba con otras palabras.</div>'
      : `<div class="load" style="line-height:1.7">
           <b style="color:var(--txt);font-size:14px">Sin clave de API solo busco en tu biblioteca.</b><br>
           Y ahí no hay nada que coincida con «${esc($('#q').value.trim())}».<br><br>
           <b style="color:var(--ac)">Solución rápida:</b> busca la canción en YouTube,
           copia el enlace y pégalo aquí en el buscador.<br>
           Funciona sin clave y la añade con su título y carátula reales.<br><br>
           <span style="color:var(--txt3)">Para buscar en todo YouTube desde aquí,
           pon tu clave en los ajustes del servidor.</span>
         </div>`;
    $('#resF').textContent = '';
    return;
  }

  box.innerHTML = r.map((x,i) => `
    <div class="rs" data-i="${i}">
      <img src="${esc(x.thumb)}" alt="" loading="lazy">
      <div style="min-width:0">
        <div class="t">${esc(x.title)}</div>
        <div class="c">${esc(x.channel)}${x.duration ? ' · ' + fmt(x.duration) : ''}</div>
      </div>
      <div class="ba">
        <button class="aq">${icono('mas','sm')} A la cola</button>
        <button class="al">${icono(inLib(x.videoId)?'estrella':'estrella-borde','sm')} ${inLib(x.videoId)?'Guardada':'Biblioteca'}</button>
      </div>
    </div>`).join('');

  $('#resF').textContent = `${r.length} resultados · 3 visibles, desliza para ver el resto`;

  box.querySelectorAll('.rs').forEach(el => {
    const i = +el.dataset.i, v = r[i];
    el.addEventListener('click', ev => {
      const b = ev.target.closest('button');
      if(b && b.classList.contains('aq')){ KL.cola.anadir(v); cerrar('#ovRes'); return; }
      if(b && b.classList.contains('al')){ KL.cola.alternarBiblioteca(v); drawResults(); return; }
      box.querySelectorAll('.rs').forEach(n => n.classList.remove('sel'));
      el.classList.add('sel'); S.sel = i;
    });
    el.addEventListener('dblclick', () => { KL.cola.anadir(v); cerrar('#ovRes'); });
  });
}

/* Lo que se usa desde fuera, y nada más:
     buscar()   lo llama el botón de la lupa y la tecla Intro
     titulos()  reconstruye los títulos contra YouTube desde los ajustes */
KL.busqueda = { buscar: doSearch, titulos: resolveAll };

})();
