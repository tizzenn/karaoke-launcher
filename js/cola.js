/* ═══════════════════════════════════════════════════════════════════
   cola.js — la biblioteca y la cola

   La biblioteca es la colección permanente, lo que se canta todas las
   noches. La cola es lo que va a sonar ahora. Confundirlas fue un error
   de una versión intermedia y costó rehacer la interfaz entera.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

/* Encapsulado en el paso 6. De las catorce funciones sueltas que había,
   siete las usa alguien de fuera y siete eran globales por inercia:
   pintar una fila o leer el porcentaje de una descarga no es asunto de
   nadie más. */
(function () {

/* ---- Acciones -------------------------------------------------------- */

function addQueue(v, quiet){
  KL.comandos.anadirALaCola(v).then(() => {
    if(!quiet) toast('Añadida a la cola');
    /* Si no había nada preparado, esta pasa a serlo sola. Un clic menos
       en el momento en que más prisa hay. */
    if(KL.evento.estado() === KL.EV.ESPERA && S.queue.length === 1){
      KL.evento.preparar(S.queue[0].id);
    }
  });
}

function toggleLib(v){
  const dentro = inLib(v.videoId);
  dentro ? KL.comandos.quitarDeLaBiblioteca(v.videoId)
         : KL.comandos.guardarEnLaBiblioteca(v);
  toast(dentro ? 'Quitada de la biblioteca' : 'Guardada en la biblioteca');
}

function libItems(){
  const f = S.filterLib.toLowerCase();
  return S.library.filter(c => !f || KL.Cancion.titulo(c).toLowerCase().includes(f)
                            || KL.Cancion.canal(c).toLowerCase().includes(f));
}

/* ---- Estado de descarga, en tres colores -----------------------------
   Gris = no puedo (falta yt-dlp). Azul = puedo. Verde = hecho. Tres
   estados y un vistazo; el porqué va en el título, para quien pregunte.
   Y mientras baja, el porcentaje: una descarga en marcha sin ningún
   indicio se confunde con una que ha fallado. */
function iconoDescarga(t){
  const enCurso = S.descargas[t.videoId];
  if(enCurso && !KL.Cancion.enDisco(t)){
    return `<span class="ib pct" title="Descargando…">${Math.round(enCurso.pct)}%</span>`;
  }
  if(KL.Cancion.enDisco(t)){
    return `<span class="ib est-ok" data-dl-ok title="Descargada: suena sin internet. Pulsa para borrar el archivo.">${icono('descargado')}</span>`;
  }
  if(!S.puedeDescargar){
    return `<span class="ib est-no" title="Descarga no disponible: falta yt-dlp. Míralo en Ajustes → Sistema.">${icono('descarga-no')}</span>`;
  }
  return `<button class="ib est-si dl" title="Descargar para cantar sin internet">${icono('descargar')}</button>`;
}

/* Quién la pidió, y solo si la pidió alguien desde el móvil. Las que pone
   el operador no llevan nada: ya sabe que son suyas, y una etiqueta
   «Operador» en cada fila sería ruido en todas menos en unas pocas.

   Esto se ve SOLO aquí, en el puesto de mando. En la pantalla del público
   no aparece ningún nombre: con el título ya se sabe a quién le toca. */
function quienPide(t){
  if(!KL.Actuacion.laPidio(t)) return '';
  return `<span class="pide">${icono('movil','sm')}${esc(KL.Actuacion.quien(t))}</span>`;
}

/* ---- Biblioteca ------------------------------------------------------ */

function drawLib(){
  const all = libItems();
  $('#libCount').textContent = S.library.length;
  $('#nLib').textContent = S.library.length + (S.filterLib ? ` · ${all.length} visibles` : '');

  if(!S.library.length){
    $('#lib').innerHTML = `<div class="empty"><div class="b">${icono('estrella')}</div>
      <p><b>Tu biblioteca está vacía.</b></p>
      <p>Busca una canción arriba y pulsa <b>Biblioteca</b> en el resultado.
      Lo que guardes aquí queda listo para el próximo karaoke sin volver a buscarlo.</p></div>`;
    return;
  }
  if(!all.length){
    $('#lib').innerHTML = `<div class="empty"><div class="b">${icono('buscar')}</div>
      <p>Nada coincide con «${esc(S.filterLib)}».</p></div>`;
    return;
  }

  $('#lib').innerHTML = all.map(t => `
    <div class="it" data-v="${esc(t.videoId)}">
      <span class="idx starred">${icono('estrella','sm')}</span>
      <img class="th" src="${esc(KL.Cancion.caratula(t))}" alt="" loading="lazy">
      <div class="meta"><div class="t">${esc(KL.Cancion.titulo(t))}</div><div class="c">${esc(KL.Cancion.canal(t))}</div></div>
      <div class="acts">
        <span class="dur">${t.duration ? fmt(t.duration) : ''}</span>
        ${t.noEmbed ? `<span class="ib warn" title="Este vídeo no permite incrustarse">${icono('aviso')}</span>` : ''}
        ${iconoDescarga(t)}
        <button class="ib aq" title="Añadir a la cola">${icono('mas')}</button>
        <button class="ib rm" title="Quitar de la biblioteca">${icono('papelera')}</button>
      </div>
    </div>`).join('');

  $$('#lib .it').forEach(el => {
    const t = S.library.find(x => x.videoId === el.dataset.v);
    el.addEventListener('click', ev => {
      const b = ev.target.closest('button, [data-dl-ok]');
      if(!b){ addQueue(t); return; }
      if(b.classList.contains('aq'))      addQueue(t);
      else if(b.classList.contains('dl')) descargar(t);
      else if(b.classList.contains('rm')) toggleLib(t);
      else if(b.hasAttribute('data-dl-ok')) borrarDescarga(t);
    });
  });
}

/* ---- Cola ------------------------------------------------------------ */

function drawQue(){
  const total = S.queue.reduce((a,t) => a + KL.Actuacion.segundos(t), 0);
  $('#nQue').textContent = S.queue.length ? `${S.queue.length} · ${fmt(total)}` : '0';

  if(!S.queue.length){
    $('#que').innerHTML = `<div class="empty"><div class="b">${icono('musica')}</div>
      <p><b>La cola está vacía.</b></p>
      <p>Añade canciones desde el buscador o pulsando una de tu biblioteca.
      Se encadenan solas, una detrás de otra.</p></div>`;
    return;
  }

  /* Suena / está preparada: las dos salen de la misma tabla que usan las
     dos pantallas, no de comparar cadenas a mano aquí. */
  const escena  = KL.escena(KL.evento.estado());
  const sonando = escena.sonando;
  const lista   = S.evento.pistaId;

  $('#que').innerHTML = S.queue.map((t,i) => `
    <div class="it ${sonando && t.id===S.curId ? 'play' : ''} ${!sonando && t.id===lista ? 'lista' : ''}"
         data-id="${t.id}" draggable="true">
      <span class="idx"><span class="num">${i+1}</span><span class="eq"><i></i><i></i><i></i></span></span>
      <img class="th" src="${esc(KL.Actuacion.caratula(t))}" alt="" loading="lazy">
      <div class="meta"><div class="t">${esc(KL.Actuacion.titulo(t))}</div>
        <div class="c">${quienPide(t)}${esc(KL.Actuacion.canal(t))}</div></div>
      <div class="acts">
        <span class="dur">${t.duration ? fmt(t.duration) : ''}</span>
        ${t.noEmbed ? `<span class="ib warn" title="No permite incrustarse: se abre en YouTube">${icono('aviso')}</span>` : ''}
        ${iconoDescarga(t)}
        <button class="ib st ${inLib(t.videoId)?'starred':''}" title="Guardar en la biblioteca">${icono(inLib(t.videoId)?'estrella':'estrella-borde')}</button>
        <button class="ib rm" title="Quitar de la cola">${icono('cerrar')}</button>
        <span class="ib grip" title="Arrastra para reordenar">${icono('arrastrar')}</span>
      </div>
    </div>`).join('');
  bindQueue();
}

function bindQueue(){
  let from = null;
  $$('#que .it').forEach(el => {
    const id = el.dataset.id;

    el.addEventListener('click', ev => {
      const b = ev.target.closest('button, [data-dl-ok]');
      /* Pulsar la fila prepara la canción; NO la lanza. Quien canta aún
         está subiendo, y arrancar el vídeo antes de tiempo obliga a
         rebobinar delante de todo el mundo. */
      if(!b){ KL.evento.preparar(id); return; }
      const t = qGet(id);
      if(b.classList.contains('st'))      toggleLib(t);
      else if(b.classList.contains('dl')) descargar(t);
      else if(b.hasAttribute('data-dl-ok')) borrarDescarga(t);
      else if(b.classList.contains('rm')){
        KL.comandos.quitarDeLaCola(id);
        if(S.curId === id) KL.evento.espera();
      }
    });

    el.addEventListener('dragstart', ev => {
      from = id; el.classList.add('drag');
      ev.dataTransfer.effectAllowed = 'move';
      ev.dataTransfer.setData('text/plain', id);
    });
    el.addEventListener('dragend', () => {
      from = null; $$('#que .it').forEach(n => n.classList.remove('drag','over'));
    });
    el.addEventListener('dragover', ev => {
      ev.preventDefault(); if(from && from !== id) el.classList.add('over');
    });
    el.addEventListener('dragleave', () => el.classList.remove('over'));
    el.addEventListener('drop', ev => {
      ev.preventDefault(); el.classList.remove('over');
      if(!from || from === id) return;
      const a = S.queue.findIndex(x => x.id === from), b = S.queue.findIndex(x => x.id === id);
      if(a < 0 || b < 0) return;
      S.queue.splice(b, 0, S.queue.splice(a, 1)[0]);
      draw();
      KL.comandos.ordenarLaCola(S.queue.map(x => x.id));
    });
  });
}

/* ---- Descargas -------------------------------------------------------
   Hoy api/descargar.php bloquea hasta que yt-dlp termina, así que con un
   vídeo largo la interfaz se queda quieta un minuto. Pasarlo a segundo
   plano es lo primero del sprint 4; mientras tanto, al menos se avisa de
   que está trabajando en lugar de parecer colgada.                     */

async function comprobarDescargas(){
  try{
    const j = await KL.api('api/descargar.php?comprobar=1');
    S.puedeDescargar = !!j.disponible;
    S.ytdlp = j;
  }catch(e){ S.puedeDescargar = false; }
  draw();
}

/* ---- Descargar sin congelar nada -------------------------------------
   El servidor lanza yt-dlp y devuelve el control al momento; desde aquí
   se pregunta cómo va cada segundo y medio, el mismo ritmo que el resto.

   Se pregunta por TODAS las descargas vivas en una sola vuelta y no una
   por cada una: `php -S` atiende una petición cada vez, y tres descargas
   sondeando por su cuenta le quitarían el sitio a la tele y a los
   móviles. Que es exactamente el problema que esto venía a arreglar. */
let sondeoDesc = null;

async function descargar(t){
  if(!S.puedeDescargar){
    toast('Falta yt-dlp. Míralo en Ajustes → Sistema.');
    return;
  }
  if(S.descargas[t.videoId]) return;          // ya está en marcha
  S.descargas[t.videoId] = { pct:0, titulo:KL.Cancion.titulo(t) };
  drawLib(); drawQue();
  try{
    const j = await KL.api('api/descargar.php', { videoId:t.videoId });
    if(j.estado === 'hecho'){
      delete S.descargas[t.videoId];
      await KL.comandos.actualizarPista(t.videoId, { local:j.local });
      toast('Ya la tenías descargada');
      return;
    }
    toast('Descargando «' + KL.Cancion.titulo(t).slice(0,26) + '». Puedes seguir usando el karaoke.');
    vigilarDescargas();
  }catch(e){
    delete S.descargas[t.videoId];
    drawLib(); drawQue();
    toast('⚠ ' + e.message);
  }
}

function vigilarDescargas(){
  if(sondeoDesc) return;
  sondeoDesc = setInterval(async () => {
    const ids = Object.keys(S.descargas);
    if(!ids.length){ clearInterval(sondeoDesc); sondeoDesc = null; return; }

    for(const vid of ids){
      let j;
      try{ j = await KL.api('api/descargar.php?progreso=' + encodeURIComponent(vid)); }
      catch(e){ continue; }

      if(j.estado === 'hecho'){
        const titulo = S.descargas[vid].titulo;
        delete S.descargas[vid];
        await KL.comandos.actualizarPista(vid, { local:j.local });
        toast('✓ «' + titulo.slice(0,26) + '» descargada. Ya suena sin internet.');
      } else if(j.estado === 'error' || j.estado === 'no'){
        delete S.descargas[vid];
        toast('⚠ ' + (j.error || 'La descarga no ha llegado a arrancar.'));
        drawLib(); drawQue();
      } else {
        S.descargas[vid].pct = j.pct || 0;
        drawLib(); drawQue();
      }
    }
  }, 1500);
}

async function borrarDescarga(t){
  if(!confirm('¿Borrar el archivo descargado de «' + KL.Cancion.titulo(t) + '»?\n\nLa canción sigue en la lista; volverá a sonar desde YouTube.')) return;
  try{
    await KL.api('api/descargar.php?borrar=' + encodeURIComponent(t.videoId), { borrar:1 });
    await KL.comandos.actualizarPista(t.videoId, { local:null });
    toast('Descarga borrada');
  }catch(e){ toast('⚠ ' + e.message); }
}

async function borrarTodasLasDescargas(){
  if(!confirm('¿Borrar TODOS los vídeos descargados?\n\nLas canciones no se pierden: volverán a sonar desde YouTube.')) return;
  try{
    const j = await KL.api('api/descargar.php?borrar_todo=1', { borrar:1 });
    await cargar();
    toast(j.borrados + ' archivos borrados');
  }catch(e){ toast('⚠ ' + e.message); }
}

/* La puerta, y solo esto:
     anadir / alternarBiblioteca   los usan el buscador y los botones
     items                         «añadir toda la biblioteca»
     pintarBiblioteca / pintarCola las llama la interfaz al llegar datos
     comprobarDescargas            al arrancar, para el semáforo
     descargar / borrarTodas       los botones de descarga */
KL.cola = {
  anadir: addQueue,
  alternarBiblioteca: toggleLib,
  items: libItems,
  pintarBiblioteca: drawLib,
  pintarCola: drawQue,
  comprobarDescargas,
  descargar,
  borrarTodas: borrarTodasLasDescargas
};

})();
