/* ═══════════════════════════════════════════════════════════════════
   app.js — cableado y arranque

   Último archivo que se carga. Conecta los botones, arranca el almacén y
   pone la aplicación en marcha. Todo lo de aquí da por hecho que el resto
   de módulos ya existe.

   ── Por qué está entero dentro de una función ────────────────────────
   Porque **nadie debe poder llamar a nada de aquí**. Este archivo es el
   final de la cadena, no una biblioteca. Mientras sus veinte funciones
   estuvieron sueltas en el ámbito global, otros módulos empezaron a
   usarlas —`api()`, el cronómetro— y eso ataba media aplicación al
   archivo que se carga el último: para probar el motor de estados había
   que cargar también los botones.

   Ahora esas dos se han mudado a su sitio (`KL.api`, `KL.cronometro`) y
   lo que queda no sale de aquí. Lo único que se publica es
   `KL.panelesDeCalentamiento`, y se publica porque la interfaz tiene que
   repintar los cartelones cuando llegan datos nuevos del servidor.

   Si algo no funciona al abrir, mira primero el orden de las etiquetas
   <script> en index.html.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

(function () {

/* ---- El almacén: de dónde vienen y adónde van los datos --------------- */
KL.almacen.iniciar({ aplicar, aviso: m => toast(m) });

const cargar   = () => KL.almacen.cargar();
const escuchar = () => KL.almacen.escuchar();

/* Aquí había un `accion(datos)` suelto que dejaba mandar cualquier cosa
   al estado. Se ha quitado a propósito: los cambios pasan por
   `KL.comandos`, que es la lista completa de lo que se puede pedir. Si
   falta algo, se añade allí una función con nombre. */

/* Buscar y descargar hablan con el mismo servidor que el almacén. */
const api = (url, cuerpo) => KL.almacen.peticion(url, cuerpo);

/* El cronómetro se mudó a js/cronometro.js: era el último hilo que ataba
   el motor de estados a este archivo. */

/* ---- «La conexión va justa: descarga las siguientes» -------------------
   El antiparones detecta que la red flojea varios segundos antes de que
   se note en pantalla, y esa información no sirve de nada para la canción
   que ya está sonando: sirve para las que vienen detrás.

   Descargar en mitad de una fiesta es justo lo que nadie se acuerda de
   hacer, porque cuando se te ocurre ya estás mirando un vídeo cortado
   delante de todo el mundo. Así que lo propone la aplicación, una vez, y
   con el botón hecho.

   No insiste: si se descarta, se calla un cuarto de hora. Un aviso que
   vuelve cada dos minutos se aprende a ignorar, y entonces ya no avisa
   de nada. */
function proponerDescargas(){
  const caja = $('#avisoRed');
  if(!caja || S.cortes < 1) return;
  if(Date.now() < S.avisoRedHasta) return;

  /* Las que vienen detrás de la que suena y aún no están en disco. */
  const i = S.queue.findIndex(t => t.id === S.curId);
  const siguientes = S.queue.slice(i + 1).filter(t => !t.local && !S.descargas[t.videoId]).slice(0, 3);

  if(!S.puedeDescargar){
    caja.innerHTML = icono('aviso') +
      '<span>La conexión está cortando. Con <b>yt-dlp</b> podrías descargar las canciones ' +
      'y seguir aunque se caiga internet.</span>' +
      '<button class="btn g" id="bRedAjustes">Cómo instalarlo</button>' +
      '<button class="btn g" id="bRedNo">Ahora no</button>';
  } else if(siguientes.length){
    caja.innerHTML = icono('aviso') +
      `<span>La conexión está cortando. Descargo las <b>${siguientes.length} siguientes</b> ` +
      'y la fiesta sigue aunque se caiga internet.</span>' +
      '<button class="btn" id="bRedBajar">Descargar</button>' +
      '<button class="btn g" id="bRedNo">Ahora no</button>';
  } else {
    caja.classList.remove('on');
    return;
  }
  caja.classList.add('on');

  const no = $('#bRedNo');
  if(no) no.addEventListener('click', () => {
    caja.classList.remove('on');
    S.avisoRedHasta = Date.now() + 15 * 60 * 1000;
  });
  const bajar = $('#bRedBajar');
  if(bajar) bajar.addEventListener('click', () => {
    caja.classList.remove('on');
    S.avisoRedHasta = Date.now() + 15 * 60 * 1000;
    siguientes.forEach(KL.cola.descargar);
  });
  const aj = $('#bRedAjustes');
  if(aj) aj.addEventListener('click', () => { caja.classList.remove('on'); $('#bCfg').click(); });
}

/* ---- Aviso de atasco -------------------------------------------------- */
function showBuf(on, txt, sub){
  const b = $('#buf');
  if(on){
    $('#bufT').textContent = txt || 'Recuperando la reproducción…';
    $('#bufS').textContent = sub || 'La conexión ha bajado';
    b.classList.add('on');
  } else b.classList.remove('on');
}

/* ---- El reproductor avisa, y aquí solo se PINTA -----------------------
   Lo que hay que DECIDIR cuando una canción termina o se da por perdida
   ya no está aquí: lo escucha `evento.js`, que es de quien es esa
   decisión. Ahora que un aviso puede tener varios oyentes, cada uno
   escucha en su casa.

   De «fin» se enteran los dos: el motor para cerrar la actuación y este
   archivo para parar el cronómetro de la barra. Ninguno de los dos sabe
   del otro. */
KL.reproductor.iniciar({

  onSonando: si => {
    /* Sonando = no hay nada que pulsar aquí. El botón se apaga en vez de
       desaparecer: si desaparece, el resto de la barra salta de sitio
       justo cuando el operador está mirando otra cosa. */
    $('#bPlay').classList.toggle('cantando', si);
    $('#bPlay').title = si
      ? 'Se está cantando. Pausar aquí descuadraría la tele: usa Terminar o Ctrl+.'
      : 'Empezar la actuación preparada (Espacio)';
    if(si) showBuf(false);
  },

  onAtasco: (t, s) => showBuf(true, t, s),
  onRedFloja: () => { S.cortes++; proponerDescargas(); },
  onRecuperado: () => showBuf(false),

  onRendicion: () => toast('⚠ La conexión no aguanta este vídeo. Dejo lista la siguiente.'),

  onError: codigo => {
    /* Un fallo de reproducción NO puede resolverse solo con un aviso que
       se va a los dos segundos: pasa con el vídeo a pantalla completa,
       delante de todo el mundo, y el operador necesita botones. */
    mostrarFallo(codigo);
    /* 101/150 = el dueño no permite incrustar. 100 = borrado o privado.
       Se marca la canción para que se vea y se pasa a la siguiente. */
    const t = S.curId && qGet(S.curId);
    if(t){
      t.noEmbed = true;
      const l = S.library.find(x => x.videoId === t.videoId);
      if(l) l.noEmbed = true;
    }
    /* Aquí antes se llamaba a siguiente(), y estando en INTERPRETACION
       eso ARRANCABA la siguiente canción sin que nadie lo pidiera: se
       colaba por la puerta de atrás la única cosa que no debe pasar. */
    draw();
  }
});

/* ---- Los tres espacios ------------------------------------------------
   Cambiar de espacio es una decisión de un clic, no un ajuste escondido:
   durante una fiesta se pasa de karaoke a poner música y vuelta. */
$$('#espacios .esp').forEach(b =>
  b.addEventListener('click', () => {
    aplicarModo(b.dataset.esp);
    toast(({karaoke:'Karaoke · canciones con letra',
            dj:'Cabina DJ · la música entre actuaciones',
            freestyle:'Freestyle · instrumentales y bases'})[S.modo]);
  }));

/* Los dos atajos de Freestyle escriben el filtro por ti. «Instrumental» es
   la canción sin voz, para cantarla encima; una «base» es para tocar o
   improvisar. Es la misma búsqueda con otra palabra, y nadie tiene por qué
   saber cuál. */
$$('#freestyle .fbtn').forEach(b =>
  b.addEventListener('click', () => {
    S.sufijos.freestyle = b.dataset.suf;
    aplicarModo('freestyle');
    $$('#freestyle .fbtn').forEach(o => o.classList.toggle('on', o === b));
    if($('#q').value.trim()) KL.busqueda.buscar();
  }));

/* ---- Búsqueda --------------------------------------------------------- */
$('#q').addEventListener('keydown', e => { if(e.key === 'Enter') KL.busqueda.buscar(); });
$('#bAddSel').addEventListener('click', () => {
  if(S.sel === null){ toast('Selecciona antes un vídeo'); return; }
  KL.cola.anadir(S.results[S.sel]); close('#ovRes');
});

/* ---- Biblioteca ------------------------------------------------------- */
$('#fLib').addEventListener('input', e => { S.filterLib = e.target.value; KL.cola.pintarBiblioteca(); });
$('#bFix').addEventListener('click', () => { toast('Pidiendo títulos a YouTube…'); KL.busqueda.titulos(true); });
$('#bAddAll').addEventListener('click', () => {
  const a = KL.cola.items();
  if(!a.length){ toast('No hay nada que añadir'); return; }
  a.forEach(t => KL.cola.anadir(t, true));
  toast(`${a.length} canciones a la cola`);
});
$('#bLibCol').addEventListener('click', () => setLibCol(!S.libCol));

/* ---- Cola ------------------------------------------------------------- */
$('#bClearQ').addEventListener('click', () => {
  if(!S.queue.length) return;
  if(confirm('¿Vaciar la cola por completo?')){ KL.evento.espera(); KL.comandos.vaciarLaCola(); }
});
$('#bShuffleQ').addEventListener('click', () => {
  if(S.queue.length < 2) return;
  for(let i = S.queue.length - 1; i > 0; i--){
    const j = Math.random() * (i + 1) | 0;
    [S.queue[i], S.queue[j]] = [S.queue[j], S.queue[i]];
  }
  draw();
  KL.comandos.ordenarLaCola(S.queue.map(x => x.id));
  toast('Cola mezclada');
});
$('#bDelAll').addEventListener('click', KL.cola.borrarTodas);

/* ---- Transporte ------------------------------------------------------- */
/* El botón grande es EMPEZAR, y mientras se canta no hace nada.

   Pausar era lo que había antes, y era una trampa: pausa la copia de ESTE
   ordenador, no la de la tele. Las dos pantallas llevan su propio vídeo
   —aquí no se envía una señal, se reproduce lo mismo dos veces— así que
   pausar aquí las descuadra y ya no vuelven a juntarse solas. Un desfase
   de medio segundo entre la letra y lo que se oye arruina la canción.

   Si hay que salir, están Terminar y el botón de pánico, que paran las
   dos a la vez porque pasan por el estado. */
$('#bPlay').addEventListener('click', () => {
  const e = KL.evento.estado();
  if(e === KL.EV.INTERPRETACION){
    toast('Mientras se canta no se pausa: descuadraría la tele. Usa Terminar o Ctrl+.');
    return;
  }
  /* Durante la cuenta atrás, el botón se la salta: a veces el cantante ya
     está delante y esperar es absurdo. */
  if(e === KL.EV.LLAMADA){ KL.evento.arrancarYa(S.evento.pistaId); return; }
  KL.evento.arrancar();
});
$('#bNext').addEventListener('click', () => KL.evento.siguiente());
$('#bPrev').addEventListener('click', () => KL.evento.anterior());
$('#bAuto').addEventListener('click', e => {
  S.autoNext = !S.autoNext;
  e.currentTarget.classList.toggle('on', S.autoNext);
  guardarPrefs();
  toast(S.autoNext
    ? 'Al terminar, la siguiente queda preparada (sigue haciendo falta darle a Empezar)'
    : 'Al terminar no se prepara nada: la cola espera');
});
$('#sk').addEventListener('click', ev => {
  const d = KL.reproductor.duracion();
  if(!d) return;
  const r = ev.currentTarget.getBoundingClientRect();
  KL.reproductor.buscar(d * ((ev.clientX - r.left) / r.width));
});

/* ---- Vistas y ventana de vídeo ---------------------------------------- */
$('#bView').addEventListener('click', () => {
  const v = VIEWS[(VIEWS.indexOf(S.view) + 1) % VIEWS.length];
  setView(v); toast(VNAME[v]);
});
$('#bVid').addEventListener('click', () => $('#vb').classList.toggle('hide'));
$('#vbX').addEventListener('click', () => $('#vb').classList.add('hide'));
$('#salirInterp').addEventListener('click', () => {
  if(!KL.evento.cancelarLlamada()) KL.evento.panico();
});

/* ---- El vídeo que no arranca -----------------------------------------
   YouTube pinta su error dentro del marco y a veces no avisa por la API.
   Se tapa con una pantalla propia y cuatro salidas claras: en una fiesta
   nadie va a abrir la consola del navegador. */
function mostrarFallo(codigo){
  const t = S.curId && qGet(S.curId);
  $('#falloTxt').textContent = codigo === 'sin arrancar'
    ? 'YouTube no ha llegado a reproducirlo. Puede ser la conexión, un bloqueador de '
      + 'anuncios, o que el vídeo esté restringido. Suele arreglarse reintentando.'
    : 'El dueño de este vídeo no permite verlo fuera de YouTube (error ' + codigo + ').';
  document.body.classList.add('fallo-video');
  $('#bFalloYT').style.display = t ? '' : 'none';
}
function ocultarFallo(){ document.body.classList.remove('fallo-video'); }

$('#bFalloReintentar').addEventListener('click', () => {
  ocultarFallo();
  const t = S.curId && qGet(S.curId);
  if(t) KL.evento.arrancarYa(t.id);
});
$('#bFalloYT').addEventListener('click', () => {
  const t = S.curId && qGet(S.curId);
  if(t) window.open('https://www.youtube.com/watch?v=' + t.videoId, '_blank');
});
$('#bFalloSaltar').addEventListener('click', () => { ocultarFallo(); KL.evento.terminar(); });
$('#bFalloSalir').addEventListener('click', () => { ocultarFallo(); KL.evento.panico(); });

/* Pantalla completa de la ventana entera, no del iframe: así el aviso de
   quién canta después sigue viéndose. Con el botón de YouTube, el vídeo
   tapa la pantalla él solo y nada nuestro puede pintarse encima. */
$('#vbFull').addEventListener('click', () => {
  if(document.fullscreenElement){ document.exitFullscreen(); return; }
  $('#vb').classList.remove('hide');
  $('#vb').requestFullscreen().catch(() => toast('Tu navegador no me deja poner la pantalla completa'));
});
$('#bYT').addEventListener('click', () => {
  const t = S.curId && qGet(S.curId);
  t ? window.open('https://www.youtube.com/watch?v=' + t.videoId, '_blank')
    : toast('No hay nada en reproducción');
});

/* ---- Dos pantallas: el PC manda, la tele suena ------------------------
   El PC sigue reproduciendo su copia aunque esté mudo, porque es quien
   sabe cuándo acaba una canción y encadena la siguiente. Callarlo no es
   pararlo, y esa diferencia es justo la que hace que funcione. */
function pintarMudo(){
  const mudo = KL.reproductor.estaMudo();
  const b = $('#vbMudo');
  b.innerHTML = icono(mudo ? 'silencio' : 'volumen');
  b.classList.toggle('est-no', mudo);
  b.title = mudo
    ? 'Este PC está en SILENCIO. Pulsa para devolverle el sonido.'
    : 'Este PC da el sonido. Pulsa para silenciarlo (si suena por la tele).';
  /* Que se vea sin abrir nada: si el PC está mudo y encima es quien
     debería sonar, eso es un problema y no un detalle. */
  const av = $('#avisoMudo');
  if(av) av.classList.toggle('on', mudo && S.sonidoEn === 'pc');
}
$('#bDesmutear').addEventListener('click', () => {
  KL.reproductor.mudo(false); pintarMudo(); toast('Sonido devuelto a este PC');
});
$('#vbMudo').addEventListener('click', () => {
  KL.reproductor.mudo(!KL.reproductor.estaMudo());
  pintarMudo();
  toast(KL.reproductor.estaMudo() ? 'Este PC en silencio' : 'Vuelve el sonido a este PC');
});
/* ---- De dónde sale el sonido ------------------------------------------
   Hasta ahora el PC se callaba solo al abrir la pantalla pública. Es lo
   correcto cuando la tele tiene altavoces… y exactamente lo contrario
   cuando la segunda pantalla es un proyector mudo y quien suena es el
   equipo del ordenador. Se quedaba una fiesta en silencio y el botón
   para arreglarlo estaba escondido en la ventanita del vídeo.

   Ahora se elige en Ajustes y manda esa elección. El aparato que NO da
   el sonido se calla, pero sigue reproduciendo: el PC es quien detecta
   el final de la canción y encadena la siguiente, y callarlo no es
   pararlo. */
function aplicarSalidaAudio(){
  const enTele = S.sonidoEn === 'tele';
  KL.reproductor.mudo(enTele);
  pintarMudo();
}

$('#bProy').addEventListener('click', () => {
  /* La pantalla pública recibe por la dirección quién da el sonido, y se
     calla ella si no le toca. Así no hace falta ningún canal nuevo. */
  const url = location.pathname.replace(/[^/]*$/, '')
            + 'proyector.php?sonido=' + encodeURIComponent(S.sonidoEn)
            + '&desfase=' + encodeURIComponent(S.desfaseTele)
            + '&efecto='  + encodeURIComponent(S.efecto)
            + '&gc=' + encodeURIComponent(S.efectoCancion)
            + '&gk=' + encodeURIComponent(S.efectoCalent)
            /* El modo va también: ahora que la tele carga base.css, cambiar a
               DJ o a MC le cambia el color a ella igual que al operador.
               Antes la paleta estaba repetida en su hoja y se quedaba
               siempre en verde, pasara lo que pasara aquí. */
            + '&modo=' + encodeURIComponent(S.modo);
  window.open(url, 'karaoke_proyector', 'width=1280,height=720');
  aplicarSalidaAudio();
  toast(S.sonidoEn === 'tele'
    ? 'Arrástrala a la tele. Este PC queda mudo: el sonido sale de allí'
    : 'Arrástrala a la tele. El sonido sigue saliendo por este PC');
});

/* ---- Calentamiento ----------------------------------------------------
   El rato de antes de empezar. Se puede encender y apagar a mano, pero
   además se apaga solo en cuanto arranca la primera canción: si ya se
   está cantando, el rato de antes se ha acabado y nadie debería tener
   que acordarse de nada. */
$('#bCal').addEventListener('click', () => {
  const encender = !S.calentamiento;
  document.body.classList.remove('paneles-ocultos');
  KL.comandos.calentamiento(encender);
  toast(encender
    ? 'Calentamiento: la tele ya explica cómo pedir canciones'
    : 'Calentamiento terminado');
});

/* ---- Mando de los cartelones -----------------------------------------
   El operador decide en directo qué se ve en la tele. Un cartelón puede
   estorbar sobre la marcha —el de la cola cuando está vacía, el de los
   datos cuando no dicen nada— y no vale con arreglarlo para la próxima
   fiesta.

   «Solo este» fija uno y detiene la rotación: sirve para dejar el de los
   QR mientras la gente llega, que es cuando de verdad hace falta. */
const CARTELONES = [
  { k:'pedir',    n:'Cómo pedir canciones', d:'Los tres pasos y los QR' },
  { k:'cola',     n:'La cola ahora mismo',  d:'En directo, se ve crecer' },
  { k:'funciona', n:'Cómo funciona',        d:'El circuito móvil → pantalla' },
  { k:'datos',    n:'Datos y consejos',     d:'Cifras y avisos que rotan' }
];

function activosAhora(){
  return Array.isArray(S.paneles) ? S.paneles : CARTELONES.map(c => c.k);
}

function pintarPaneles(){
  const caja = $('#listaPaneles');
  if(!caja) return;
  const act = activosAhora();
  caja.innerHTML = CARTELONES.map(c => `
    <div class="fila">
      <input type="checkbox" id="cp_${c.k}" ${act.includes(c.k) ? 'checked' : ''}>
      <label for="cp_${c.k}">${esc(c.n)}<small>${esc(c.d)}</small></label>
      <button class="ver ${S.panelFijo === c.k ? 'act' : ''}" data-k="${c.k}">Solo este</button>
    </div>`).join('');

  caja.querySelectorAll('input').forEach(inp => inp.addEventListener('change', () => {
    const lista = CARTELONES.filter(c => $('#cp_' + c.k).checked).map(c => c.k);
    /* Si se apaga el que estaba fijo, la rotación vuelve sola: dejarlo
       fijo en un cartelón apagado sería enseñar una pantalla en blanco. */
    const fijo = lista.includes(S.panelFijo) ? S.panelFijo : null;
    mandarPaneles(lista, fijo);
  }));
  caja.querySelectorAll('.ver').forEach(b => b.addEventListener('click', () => {
    const k = b.dataset.k;
    const nuevo = S.panelFijo === k ? null : k;
    const lista = activosAhora().includes(k) ? activosAhora() : activosAhora().concat(k);
    mandarPaneles(lista, nuevo);
  }));

  $('#pieRotacion').textContent = S.panelFijo
    ? 'Fijo en uno, sin rotar'
    : (act.length > 1 ? `Rotando ${act.length} cada 15 s`
                      : (act.length ? 'Solo queda uno' : 'Ninguno: la tele queda limpia'));
}

function mandarPaneles(lista, fijo){
  S.paneles = lista; S.panelFijo = fijo || null;
  pintarPaneles();
  KL.comandos.cartelones(lista, fijo);
}

$('#bRotar').addEventListener('click', () => mandarPaneles(CARTELONES.map(c => c.k), null));
$('#bCerrarPaneles').addEventListener('click', () => {
  document.body.classList.add('paneles-ocultos');
  toast('Mando oculto. Vuelve a pulsar el botón del calentamiento para verlo.');
});

/* ---- Pestañas (móvil) -------------------------------------------------- */
$$('#tabs button').forEach(b => b.addEventListener('click', () => {
  $$('#tabs button').forEach(x => x.classList.remove('on'));
  b.classList.add('on');
  $('#pLib').classList.toggle('act', b.dataset.p === 'lib');
  $('#pQue').classList.toggle('act', b.dataset.p === 'que');
}));

/* ---- Modales ----------------------------------------------------------- */
$$('.ov').forEach(o => o.addEventListener('click', ev => {
  if(ev.target === o || ev.target.hasAttribute('data-x')) o.classList.remove('on');
}));

/* ---- Ajustes ----------------------------------------------------------- */
$('#bCfg').addEventListener('click', () => {
  $('#cSuf').value      = S.suffix;
  $('#cAuto').checked   = S.openVideo;
  $('#cStar').checked   = S.alsoStar;
  $('#cQual').value     = S.quality;
  $('#cAntiCut').checked= S.antiCut;
  $('#cSig').value      = S.avisoSig;
  $('#cModo').value     = S.modo;
  $('#cSegFin').value   = S.segFin;
  $('#cSegLlam').value  = S.segLlamada;
  $('#cSonido').value   = S.sonidoEn;
  $('#cDesfase').value  = S.desfaseTele;
  $('#cEfecto').value   = S.efecto;
  $('#cGanCancion').value = S.efectoCancion;
  $('#cGanCalent').value  = S.efectoCalent;
  $('#cRetirar').checked= S.retirar;
  pintarSuperpoderes();
  open('#ovCfg');
});
/* Cambiar de modo en el desplegable enseña su sufijo al momento: si no,
   parece que el campo no tiene nada que ver con el modo. */
$('#cModo').addEventListener('change', e => {
  $('#cSuf').value = S.sufijos[e.target.value] ?? '';
});

$('#bSaveCfg').addEventListener('click', () => {
  /* Al guardar, el sufijo se apunta en el modo que esté activo. */
  S.suffix = $('#cSuf').value.trim();
  S.sufijos[$('#cModo').value] = S.suffix;
  S.openVideo = $('#cAuto').checked;
  S.alsoStar  = $('#cStar').checked;
  S.quality   = $('#cQual').value;
  S.antiCut   = $('#cAntiCut').checked;
  S.avisoSig  = $('#cSig').value;
  S.retirar   = $('#cRetirar').checked;
  S.segFin    = Math.min(120, Math.max(3, +$("#cSegFin").value || 5));
  S.segLlamada= Math.min(15, Math.max(0, +$('#cSegLlam').value || 0));
  S.sonidoEn  = $('#cSonido').value === 'tele' ? 'tele' : 'pc';
  S.desfaseTele = Math.min(5, Math.max(-5, +$('#cDesfase').value || 0));
  S.efecto        = $('#cEfecto').value;
  S.efectoCancion = Math.min(4, Math.max(0, +$('#cGanCancion').value || 0));
  S.efectoCalent  = Math.min(4, Math.max(0, +$('#cGanCalent').value || 0));
  aplicarSalidaAudio();
  aplicarModo($('#cModo').value);
  $('#cSuf').value = S.suffix;
  dibujarSiguiente();
  KL.reproductor.calidad(S.quality);
  guardarPrefs();
  close('#ovCfg');
  toast('Ajustes guardados');
});

/* Panel de superpoderes: qué hay instalado y qué desbloquea. Un semáforo
   dice más que una lista, y esconderlo solo consigue que la gente crea
   que la aplicación está rota cuando en realidad le falta una pieza.
   El panel completo, con enlaces de descarga y comprobación de PHP y
   FFmpeg, llega en el sprint 5. */
function pintarSuperpoderes(){
  const caja = $('#poderes');
  if(!caja) return;
  const y = S.ytdlp || {};
  const fila = (ok, nombre, que, extra) => `
    <div class="sw" style="align-items:flex-start;gap:9px">
      <span class="${ok ? 'est-ok' : 'est-no'}" style="margin-top:1px">${icono(ok ? 'comprobado' : 'cerrar','sm')}</span>
      <span><b style="color:var(--txt)">${nombre}</b> — ${que}
      ${extra ? `<div class="h" style="margin-top:3px">${extra}</div>` : ''}</span>
    </div>`;
  caja.innerHTML =
    fila(true, 'PHP', 'la aplicación funciona; sin él no arranca nada') +
    fila(S.puedeDescargar, 'yt-dlp',
         S.puedeDescargar
           ? 'descarga local activada: la fiesta sigue aunque caiga internet'
           : 'sin él no se pueden descargar canciones',
         S.puedeDescargar
           ? 'Versión ' + esc(y.version || '?') + ' · ' + (y.descargados || 0) + ' vídeos en disco'
           : 'Descárgalo de <b>github.com/yt-dlp/yt-dlp/releases</b>, ponlo junto a Karaoke.bat. ' +
             'No necesita Python: lleva el suyo dentro.');
}

/* ---- Copia de seguridad ------------------------------------------------ */
$('#bExp').addEventListener('click', () => {
  const blob = new Blob([JSON.stringify({
    app:'karaoke-launcher', version:'1.1',
    date:new Date().toISOString(), library:S.library, queue:S.queue
  }, null, 2)], { type:'application/json' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'karaoke-copia.json';
  a.click();
  setTimeout(() => URL.revokeObjectURL(a.href), 1000);
  toast('Copia descargada');
});
$('#bImp').addEventListener('click', () => $('#file').click());
$('#file').addEventListener('change', ev => {
  const f = ev.target.files[0];
  if(!f) return;
  const rd = new FileReader();
  rd.onload = () => {
    try{
      const d = JSON.parse(rd.result);
      if(Array.isArray(d.library)) KL.comandos.reemplazarLaBiblioteca(d.library);
      close('#ovCfg');
      toast('Copia importada');
    }catch(e){ toast('⚠ El archivo no es válido'); }
  };
  rd.readAsText(f);
  ev.target.value = '';
});

/* ---- QR para las peticiones -------------------------------------------
   Generado aquí mismo, sin salir a internet, y siempre con la dirección
   de red del servidor: la de la barra del navegador es `localhost` y
   solo sirve para quien está delante de este PC.                        */
$('#bQR').addEventListener('click', () => {
  const url = urlPedir();
  if(!url){
    $('#qrurl').textContent = '';
    $('#qrimg').innerHTML = '<div style="color:#B71C1C;font-size:13px;max-width:200px;padding:20px 0">'
      + 'Este PC no tiene dirección de red local. Conéctalo al router y vuelve a intentarlo.</div>';
    open('#ovQR');
    return;
  }
  $('#qrurl').textContent = url;
  $('#qrimg').innerHTML = '<div style="width:200px;height:200px">' + KL.qr.svg(url, {borde:1}) + '</div>';
  open('#ovQR');
});

/* ---- El ratón encima del vídeo, en modo Interpretación ------------------ */
let quietoT = null;
addEventListener('mousemove', () => {
  document.body.classList.remove('quieto');
  clearTimeout(quietoT);
  quietoT = setTimeout(() => document.body.classList.add('quieto'), 3000);
});

/* Si el usuario sale de la pantalla completa por su cuenta (F11, Esc del
   navegador), no dejamos el modo Interpretación colgado. */
document.addEventListener('fullscreenchange', () => {
  if(!document.fullscreenElement && document.body.dataset.pantalla === 'interpretacion'){
    KL.evento.soltarPantallaCompleta();
  }
});

/* ---- PWA --------------------------------------------------------------- */
if('serviceWorker' in navigator){
  window.addEventListener('load', () => navigator.serviceWorker.register('sw.js').catch(() => {}));
}

/* ═══════════════════ ARRANQUE ═══════════════════ */
load();
aplicarModo(S.modo);
setView(S.view);
setLibCol(S.libCol);
aplicarSalidaAudio();
dibujarRed();
$('#bAuto').classList.toggle('on', S.autoNext);
$('#cSegFin').value = S.segFin;
KL.evento.iniciar();
draw();
if(window.innerWidth > 900) setTimeout(() => $('#q').focus(), 120);

/* El estado viene del servidor, y a partir de ahí escuchamos cambios. */
cargar().then(() => { dibujarRed(); KL.cola.comprobarDescargas(); }).then(escuchar);

/* Lo ÚNICO que sale de este archivo. La interfaz repinta los cartelones
   cuando el servidor dice que han cambiado —los puede tocar otro
   aparato— y necesita esta puerta. */
KL.panelesDeCalentamiento = pintarPaneles;

})();
