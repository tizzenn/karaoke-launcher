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
  /* Si ya está en la cola de este espacio, se PREGUNTA. El servidor ya
     no lo rechaza —dos personas cantando lo mismo son dos actuaciones
     distintas— y el operador tiene la cola delante: sabe si es un doble
     clic suyo o si de verdad la quieren dos. */
  if(cola().some(t => t.videoId === v.videoId)){
    if(!confirm(KL.TEXTOS.pregunta('yaEstaEnLaCola',
                { titulo: KL.Cancion.titulo(v) }))) return;
  }
  KL.comandos.anadirALaCola(v).then(() => {
    if(!quiet) toast('Añadida a la cola');
    /* Si no había nada preparado, esta pasa a serlo sola. Un clic menos
       en el momento en que más prisa hay. */
    /* Solo si es la primera de ESTE espacio: añadir la primera canción a
       la Cabina DJ no debe preparar nada si el karaoke ya estaba en marcha. */
    const c = cola();
    if(KL.evento.estado() === KL.EV.ESPERA && c.length === 1){
      KL.evento.preparar(c[0].id);
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
      Lo que guardes aquí queda listo para el próximo karaoke sin volver a buscarlo.</p>
      <p style="margin-top:16px"><button class="btn g" id="bEjemplos">Montar una fiesta de ejemplo</button></p>
      <p class="h" style="max-width:440px;margin:8px auto 0">Deja una noche a medias:
      tres canciones en la cola con quién las pidió, una ya cantada, cuatro en la
      biblioteca y el calentamiento encendido. Sirve para ver cómo se usa esto y para
      comprobar que todo funciona antes de que llegue nadie. Se deshace con
      <b>Vaciar</b>.</p></div>`;
    const be = $('#bEjemplos');
    if(be) be.addEventListener('click', montarFiestaDeEjemplo);
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

/* ---- La fiesta de ejemplo --------------------------------------------
   Y aquí está la diferencia que más se nota entre las dos formas de
   hacer esto.

   Una **biblioteca** de ejemplo enseña una lista de títulos, y al verla
   sigues sin saber cómo se usa el programa. Una **fiesta** de ejemplo
   monta una noche a medias: tres canciones esperando con nombre de quien
   las pidió, una ya cantada en el historial, cuatro guardadas en la
   biblioteca y la pantalla de calentamiento encendida. Al abrirla por
   primera vez la reacción es «ah, así se usa» en vez de «vale, ¿y ahora
   qué?».

   Y de paso queda probado de una sentada todo lo que puede fallar: la
   clave de la API, la búsqueda, la cola, el historial, el calentamiento y
   las dos pantallas. Que es para lo que existe de verdad — no para
   impresionar, sino para que cinco minutos después de instalar sepas si
   va a funcionar esta noche.

   ── Por qué se buscan y no vienen escritas ──────────────────────────
   En `ejemplos.json` hay títulos, no identificadores de YouTube. Un
   identificador de hoy puede estar borrado o bloqueado dentro de seis
   meses, y lo primero que vería alguien recién instalado sería un vídeo
   que no se ve. Cuesta ocho búsquedas —ochocientas unidades de las diez
   mil diarias— y se hace una vez.

   Van de una en una y no en paralelo: `php -S` atiende una petición cada
   vez, y ocho a la vez dejarían la aplicación clavada justo mientras se
   enseña por primera vez. */
async function montarFiestaDeEjemplo(){
  if(!S.conClave){
    toast('Hace falta la clave de YouTube. Está en Ajustes de la fiesta.');
    return;
  }
  const b = $('#bEjemplos');
  let plan;
  try{
    plan = await fetch('ejemplos.json', { cache:'no-cache' }).then(r => r.json());
  }catch(e){ toast('⚠ No encuentro ejemplos.json'); return; }

  const cola   = (plan.cola || []).slice(0, 6);
  const antes  = (plan.yaCantadas || []).slice(0, 3);
  const biblio = (plan.biblioteca || []).slice(0, 8);
  const total  = cola.length + antes.length + biblio.length;
  let hecho = 0;

  const buscar = async titulo => {
    if(b) b.textContent = 'Montando la fiesta… ' + (++hecho) + ' de ' + total;
    try{
      const j = await KL.api('api/buscar.php?q=' + encodeURIComponent(titulo)
                             + '&sufijo=' + encodeURIComponent('karaoke'));
      return (j.items || [])[0] || null;
    }catch(e){ return null; }
  };

  /* 1. Lo ya cantado. Se añade y se cierra en el acto, que es exactamente
        lo que pasa en una fiesta de verdad: entra en la cola, se canta y
        pasa al historial. */
  for(const x of antes){
    const v = await buscar(x.titulo);
    if(!v) continue;
    await KL.comandos.anadirALaCola(Object.assign({}, v, { pedida:x.quien || null }), 'karaoke');
    const t = (S.queue || []).find(q => q.videoId === v.videoId);
    if(t) await KL.comandos.terminarActuacion(t.id, true);
  }

  /* 2. Lo que está esperando. */
  for(const x of cola){
    const v = await buscar(x.titulo);
    if(v) await KL.comandos.anadirALaCola(Object.assign({}, v, { pedida:x.quien || null }), 'karaoke');
  }

  /* 3. La biblioteca: lo que se queda de una noche para la siguiente. */
  for(const titulo of biblio){
    const v = await buscar(titulo);
    if(v && !inLib(v.videoId)) await KL.comandos.guardarEnLaBiblioteca(v);
  }

  /* 4. Y el calentamiento encendido, que es como empieza una fiesta de
        verdad: la tele explicando cómo pedir mientras llega la gente. */
  if(plan.calentamiento) await KL.comandos.calentamiento(true);

  toast(hecho
    ? 'Fiesta de ejemplo montada. Cuando quieras, «Vaciar» y a la tuya.'
    : '⚠ No he podido montar nada. Mira la clave de YouTube en Ajustes.');
}

/* ---- Cola ------------------------------------------------------------ */

function drawQue(){
  const pendientes = cola();
  const total = pendientes.reduce((a,t) => a + KL.Actuacion.segundos(t), 0);
  $('#nQue').textContent = pendientes.length ? `${pendientes.length} · ${fmt(total)}` : '0';

  /* Y cuántas esperan en los otros dos, sin abrirlos. */
  $$('#espacios .esp').forEach(b => {
    const n = colaDe(b.dataset.esp).length;
    b.dataset.cuantas = n || '';
  });

  if(!pendientes.length){
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

  const fila = (t,i) => `
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
    </div>`;

  /* La escaleta: "Ahora" es la que suena o está preparada, "Después" la
     que sigue, "Luego" el resto. Es solo una lectura distinta de la
     misma cola — el orden real y el arrastre no cambian, solo se
     intercalan tres rótulos. Si nada está preparado (cola recién
     llegada, nadie ha tocado nada todavía) no hay "Ahora" que señalar y
     se ve como lista simple, tal cual antes. */
  const ahoraI = pendientes.findIndex(t =>
    (sonando && t.id === S.curId) || (!sonando && t.id === lista));

  if(ahoraI === -1){
    $('#que').innerHTML = pendientes.map(fila).join('');
  } else {
    let html = pendientes.slice(0, ahoraI).map(fila).join('');
    html += `<div class="escaleta-h ahora">Ahora</div>` + fila(pendientes[ahoraI], ahoraI);
    if(ahoraI + 1 < pendientes.length){
      html += `<div class="escaleta-h">Después</div>` + fila(pendientes[ahoraI+1], ahoraI+1);
    }
    if(ahoraI + 2 < pendientes.length){
      const luego = pendientes.length - ahoraI - 2;
      html += `<div class="escaleta-h">Luego · ${luego}</div>`
        + pendientes.slice(ahoraI + 2).map(fila).join('');
    }
    $('#que').innerHTML = html;
  }
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

/* ---- Historial ---------------------------------------------------------
   Cien pistas como mucho (el servidor las recorta), lo cantado en ESTA
   fiesta, para no repetir sin querer y para que el operador sepa qué ha
   pasado. Es de solo lectura salvo «Pedir otra vez» y «Vaciar». */

function drawHist(){
  const h = S.historial || [];
  $('#histF').textContent = h.length ? `${h.length} cantadas` : '';

  if(!h.length){
    $('#hist').innerHTML = `<div class="load">Todavía no ha cantado nadie esta fiesta.</div>`;
    return;
  }

  $('#hist').innerHTML = h.map((t,i) => `
    <div class="rs" data-i="${i}">
      <img src="${esc(KL.Actuacion.caratula(t))}" alt="" loading="lazy">
      <div style="min-width:0">
        <div class="t">${esc(KL.Actuacion.titulo(t))}</div>
        <div class="c">${quienPide(t)}${t.cantada_en ? horaDe(t.cantada_en) : ''}</div>
      </div>
      <div class="ba">
        <button class="aq">${icono('mas','sm')} Pedir otra vez</button>
      </div>
    </div>`).join('');

  $('#hist').querySelectorAll('.rs').forEach(el => {
    const t = h[+el.dataset.i];
    el.querySelector('.aq').addEventListener('click', () => addQueue(t));
  });
}

function horaDe(segundosUnix){
  return new Date(segundosUnix * 1000).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function vaciarHistorial(){
  if(!confirm(KL.TEXTOS.pregunta('vaciarHistorial'))) return;
  KL.comandos.vaciarElHistorial();
}

/* ---- Descargas -------------------------------------------------------
   api/descargar.php lanza yt-dlp en segundo plano y devuelve el control
   al momento; ver comprobarDescargas() y vigilarDescargas() más abajo. */

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

/* ---- Descargar la cola entera de una vez -----------------------------
   Para preparar la fiesta con antelación y no depender de la wifi esa
   noche. No hace nada nuevo: llama a descargar(t) por cada pista que
   todavía no esté en el disco, una detrás de otra. Toda la
   infraestructura de verdad —el sondeo que pregunta por todas las
   descargas vivas a la vez, la que avisa cuando termina cada una— ya
   existía y no se toca; esto es solo el botón que faltaba. */
async function descargarCola(){
  if(!S.puedeDescargar){
    toast('Falta yt-dlp. Míralo en Ajustes → Sistema.');
    return;
  }
  const pendientes = (S.queue || []).filter(t => !t.local && !S.descargas[t.videoId]);
  if(!pendientes.length){ toast('No hay nada pendiente de descargar en la cola.'); return; }
  toast('Descargando ' + pendientes.length + ' canciones de la cola. Puedes seguir usando el karaoke.');
  for(const t of pendientes){
    /* Un respiro entre cada arranque: el servidor de PHP atiende una
       petición cada vez, y lanzar diez descargas en el mismo instante le
       quitaría el turno a la tele y a los móviles pidiendo. */
    await descargar(t);
    await new Promise(r => setTimeout(r, 400));
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
  if(!confirm(KL.TEXTOS.pregunta('borrarDescarga', { titulo: KL.Cancion.titulo(t) }))) return;
  try{
    await KL.api('api/descargar.php?borrar=' + encodeURIComponent(t.videoId), { borrar:1 });
    /* No se le dice al servidor «ya no está descargada»: se le pide que
       vuelva a mirar. El disco es quien lo sabe. */
    await KL.comandos.refrescarPista(t.videoId);
    toast('Descarga borrada');
  }catch(e){ toast('⚠ ' + e.message); }
}

async function borrarTodasLasDescargas(){
  if(!confirm(KL.TEXTOS.pregunta('borrarTodasLasDescargas'))) return;
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
     descargar / descargarCola / borrarTodas   los botones de descarga */
KL.cola = {
  anadir: addQueue,
  alternarBiblioteca: toggleLib,
  items: libItems,
  pintarBiblioteca: drawLib,
  pintarCola: drawQue,
  pintarHistorial: drawHist,
  vaciarHistorial,
  montarFiestaDeEjemplo,
  comprobarDescargas,
  descargar,
  descargarCola,
  borrarTodas: borrarTodasLasDescargas
};

})();
