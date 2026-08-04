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
  const c = cola();
  const i = c.findIndex(t => t.id === S.curId);
  const siguientes = c.slice(i + 1).filter(t => !t.local && !S.descargas[t.videoId]).slice(0, 3);

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
    /* Aquí se apagaba el botón de Empezar, y estuvo mal desde el primer
       día por el motivo de siempre (MODELO §6): **«se está cantando» ya
       tiene dueño** —el evento— y esto era una segunda fuente.

       Se notó al terminar una canción entera. `onSonando(false)` lo
       dispara YouTube al PAUSAR; al TERMINAR manda `ENDED`, que es otro
       aviso. Así que el botón se apagaba al empezar y no se volvía a
       encender nunca: la siguiente quedaba preparada, el operador miraba
       la barra y veía un botón gris con cara de deshabilitado.

       Ahora el aspecto del botón lo decide el estado, más abajo. Aquí
       solo queda lo que de verdad es del reproductor: si suena, ya no
       está atascado. */
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

/* ---- Música ambiente --------------------------------------------------
   Suena aquí solo si este ordenador es el que da el sonido. Si lo da la
   tele, es la tele la que pone la música: sonando en las dos se oiría
   doble y desfasado. */
KL.ambiente.iniciar({ doySonido: () => S.sonidoEn !== 'tele' });

/* ---- Maestro de ceremonias --------------------------------------------
   Suena donde suena la música: en el aparato que da el sonido de la sala.
   Un «un aplauso para Marta» que sale del portátil mientras el sonido va
   por la tele no lo oye nadie. */
KL.mc.iniciar({ doySonido: () => S.sonidoEn !== 'tele' });

let djT = null;
function pintarDJ(){
  const b = $('#bDJ');
  if(!b) return;
  const hayFuente = S.ambiente && S.ambiente.fuente !== 'no';
  const suena = KL.ambiente.suena();
  b.classList.toggle('on', S.ambienteOn && hayFuente);
  b.classList.toggle('sonando', suena);
  b.classList.toggle('apagado', !hayFuente);

  const que = suena ? KL.ambiente.queSuena() : '';
  b.title = !hayFuente
    ? 'Música ambiente: no hay ninguna lista configurada. Se pone en Ajustes → Música ambiente.'
    : suena
      ? 'Sonando' + (que ? ': ' + que : ' la música ambiente') + '. Pulsa para callarla.'
      : (S.ambienteOn
          ? 'Música ambiente encendida: sonará cuando no cante nadie.'
          : 'Música ambiente apagada. Pulsa para encenderla.');

  /* El título del vídeo tarda en llegar; se vuelve a mirar mientras suene.
     Un intervalo y no un sondeo permanente: si no hay música, no hay
     nada que preguntar. */
  clearTimeout(djT);
  if(suena) djT = setTimeout(pintarDJ, 4000);
}

$('#bDJ').addEventListener('click', () => {
  if(!S.ambiente || S.ambiente.fuente === 'no'){
    toast('No hay ninguna lista configurada. Ponla en Ajustes → Música ambiente.');
    return;
  }
  S.ambienteOn = !S.ambienteOn;
  guardarPrefs();
  KL.ambiente.revisar();
  pintarDJ();
  toast(S.ambienteOn
    ? (S.ambiente.auto
        ? 'Música ambiente encendida. Se callará en cada canción y volverá al terminar.'
        : 'Música ambiente encendida. Se callará al empezar la próxima canción.')
    : 'Música ambiente apagada');
});

/* ---- Los dos espacios --------------------------------------------------
   Cambiar de espacio es una decisión de un clic, no un ajuste escondido:
   durante una fiesta se pasa de karaoke a poner música y vuelta.

   Eran tres. Freestyle se quitó porque no era un espacio: hacía lo mismo
   que Karaoke buscando otra palabra, y buscar otra palabra ahora es
   escribirla en el campo de filtro. */
$$('#espacios .esp').forEach(b =>
  b.addEventListener('click', () => {
    aplicarModo(b.dataset.esp);
    /* Y se lo decimos a todo el mundo: con una cola por espacio, la tele
       tiene que enseñar la que toca. Dejó de ser una preferencia de este
       aparato el día que cambiar de espacio cambió lo que hay que ver. */
    KL.comandos.cambiarDeEspacio(S.modo);
    toast(({karaoke:'Karaoke · canciones con letra',
            dj:'Cabina DJ · una fiesta sin karaoke, la lista la hacen todos'})[S.modo]);
  }));

/* ---- El filtro --------------------------------------------------------
   Lo que se escribe aquí se le añade a cada búsqueda. Se enseña debajo,
   ya traducido, mientras se escribe: es lo que convierte esto en algo que
   se aprende usándolo en vez de leyendo un manual. Escribes OR, ves
   aparecer la barra, y ya sabes lo que hace OR.

   Y se guarda POR ESPACIO. Ir a la Cabina DJ y volver no puede obligar a
   reescribir el filtro: MODELO §1, cada espacio conserva su mesa. */
/* Los perfiles del espacio, en la lista desplegable del campo. Se
   repintan al cambiar de espacio: un perfil de karaoke en la Cabina DJ no
   significa nada. */
KL.pintarPerfiles = function(){
  const ps = KL.filtro.perfilesDe(S.modo, S.perfiles);
  $('#perfiles').innerHTML = ps.map(p =>
    `<option value="${esc(p.filtro)}">${esc(p.nombre)}${p.propio ? ' ★' : ''}</option>`
  ).join('');
};

KL.pintarFiltro = function(){
  const v = $('#filtro').value;
  const pie = $('#filtroPie');
  const aviso = KL.filtro.aviso(v);
  const q = KL.filtro.aConsulta(v);
  $('#bFiltroX').hidden = !v.trim();
  pie.classList.toggle('mal', !!aviso);
  if(aviso){ pie.textContent = aviso; return; }
  /* Cuando no traduce nada —una sola palabra— no se enseña: repetir
     «karaoke → karaoke» es ruido, y el sitio de abajo lo necesita el
     aviso cuando haga falta. */
  pie.textContent = (q && q !== v.trim()) ? 'Se busca: ' + q : '';

  /* El truco avanzado, una vez. No es un enlace permanente porque el
     sitio de abajo lo necesita la traducción: se enseña cuando el campo
     está vacío, que es justo cuando alguien podría no saber qué poner. */
  if(!v.trim())
    pie.innerHTML = '<span class="pista">Acepta <b>AND</b>, <b>OR</b> y '
      + '<b>-</b> para excluir · '
      + '<a href="docs/BUSQUEDA.md" target="_blank">cómo buscar mejor</a></span>';
};

$('#filtro').addEventListener('input', () => {
  S.suffix = $('#filtro').value;
  S.sufijos[S.modo] = S.suffix;
  KL.pintarFiltro();
  guardarPrefs();
});
$('#filtro').addEventListener('keydown', e => {
  if(e.key === 'Enter' && $('#q').value.trim()) KL.busqueda.buscar();
});
$('#bFiltroX').addEventListener('click', () => {
  $('#filtro').value = '';
  $('#filtro').dispatchEvent(new Event('input'));
  $('#filtro').focus();
});

/* Guardar un perfil es guardar un texto con un nombre. Se pide el nombre
   con un prompt y no con un diálogo propio a propósito: esto se hace una
   vez cada muchas fiestas, y una ventana bien hecha para eso es trabajo
   que no se nota. */
$('#bFiltroGuardar').addEventListener('click', () => {
  const filtro = $('#filtro').value.trim();
  if(!filtro){ toast('Escribe primero el filtro que quieras guardar'); return; }
  const nombre = (prompt('¿Cómo se llama este perfil?\n\n' + filtro) || '').trim();
  if(!nombre) return;
  S.perfiles = S.perfiles || {};
  const lista = (S.perfiles[S.modo] || []).filter(p => p.nombre !== nombre);
  lista.push({ nombre, filtro });
  S.perfiles[S.modo] = lista;
  guardarPrefs();
  KL.pintarPerfiles();
  toast('Perfil «' + nombre + '» guardado');
});

/* ---- Búsqueda --------------------------------------------------------- */
$('#q').addEventListener('keydown', e => { if(e.key === 'Enter') KL.busqueda.buscar(); });
$('#bAddSel').addEventListener('click', () => {
  if(S.sel === null){ toast('Selecciona antes un vídeo'); return; }
  KL.cola.anadir(S.results[S.sel]); cerrar('#ovRes');
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
  const lista = cola();
  if(!lista.length) return;
  /* Se dice EN CUÁL, porque ahora hay tres y vaciar la equivocada es caro. */
  const nombre = (ESPACIOS[S.modo] || {}).nombre || 'esta';
  if(confirm(KL.TEXTOS.pregunta('vaciarCola', { espacio:nombre, cuantas:lista.length }))){
    KL.evento.espera();
    KL.comandos.vaciarLaCola();
  }
});
$('#bShuffleQ').addEventListener('click', () => {
  const lista = cola();
  if(lista.length < 2) return;
  /* Se mezcla SOLO este espacio, pero el orden se manda entero: el
     servidor guarda una sola lista y reordenar a medias la descuadraría.
     Así que se baraja el trozo y se recomponen las tres en su sitio. */
  const mezclado = lista.slice();
  for(let i = mezclado.length - 1; i > 0; i--){
    const j = Math.random() * (i + 1) | 0;
    [mezclado[i], mezclado[j]] = [mezclado[j], mezclado[i]];
  }
  let k = 0;
  S.queue = S.queue.map(t => (t.espacio || 'karaoke') === S.modo ? mezclado[k++] : t);
  draw();
  KL.comandos.ordenarLaCola(S.queue.map(x => x.id));
  toast('Cola mezclada');
});
$('#bDescargarCola').addEventListener('click', KL.cola.descargarCola);
$('#bHist').addEventListener('click', () => { abrir('#ovHist'); KL.cola.pintarHistorial(); });
$('#bVaciarHist').addEventListener('click', KL.cola.vaciarHistorial);
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
/* El aspecto del botón se DEDUCE del estado, no se conmuta a mano desde
   los avisos del reproductor. Es la regla del MODELO §6 aplicada a un
   botón: si «se está cantando» ya vive en el evento, no puede haber un
   segundo sitio que opine, porque acaban discrepando — y aquí discrepaban
   justo en el peor momento, con la siguiente canción ya preparada.

   Se apaga solo en INTERPRETACION. En LLAMADA no: ahí el botón sirve para
   saltarse la cuenta atrás. */
KL.senales.oir('evento:cambio', ev => {
  /* La tarjeta «Ahora» y la fase se repintan con cada cambio de estado,
     no solo cuando llegan datos del servidor: una transicion local tiene
     que verse al momento, no dentro de segundo y medio. */
  if(KL.repintarAhora) KL.repintarAhora();
  const cantando = (ev || {}).estado === KL.EV.INTERPRETACION;
  $('#bPlay').classList.toggle('cantando', cantando);
  /* En Cabina DJ no hay "actuación" -MODELO.md lo dice literal: "¿Hay
     actuaciones? No"-, así que el título no puede hablar de eso. Es el
     mismo botón y la misma acción real (arrancar la reproducción); solo
     cambia la palabra según el espacio (auditoría UX, 2026-08-03). */
  const esDJ = S.modo === 'dj';
  $('#bPlay').title = cantando
    ? 'Se está cantando. Pausar aquí descuadraría la tele: usa Terminar o Ctrl+.'
    : (esDJ ? 'Empezar la canción preparada (Espacio)'
            : 'Empezar la actuación preparada (Espacio)');
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
/* Un fallo de reproducción pasa con el vídeo a pantalla completa y la
   gente mirando. El mensaje tiene que decir QUÉ HACER, y para eso tiene
   que decir qué ha pasado de verdad: todos los fallos daban el mismo
   texto —«el dueño no permite verlo fuera de YouTube»— incluso cuando el
   problema era un archivo descargado que ya no está en el disco. Eso
   manda al operador a buscar otra canción cuando bastaba con volver a
   descargarla. */
const FALLOS = {
  'sin arrancar':
    'YouTube no ha llegado a reproducirlo. Puede ser la conexión, un bloqueador de '
    + 'anuncios, o que el vídeo esté restringido. Suele arreglarse reintentando.',
  'archivo':
    'El archivo descargado no se puede abrir: se ha movido, se ha borrado o está a '
    + 'medias. Bórralo desde la lista y vuelve a descargarlo, o quítalo del disco '
    + 'para que suene otra vez desde YouTube.',
  '2':   'La dirección del vídeo no es válida (error 2).',
  '5':   'Este vídeo no se puede reproducir en el navegador (error 5).',
  '100': 'El vídeo ya no existe: lo han borrado o lo han puesto en privado (error 100).',
  '101': 'El dueño de este vídeo no permite verlo fuera de YouTube (error 101).',
  '150': 'El dueño de este vídeo no permite verlo fuera de YouTube (error 150).'
};

function mostrarFallo(codigo){
  const t = S.curId && qGet(S.curId);
  $('#falloTxt').textContent = FALLOS[String(codigo)]
    || ('No se ha podido reproducir (error ' + codigo + '). Prueba a reintentar o salta a la siguiente.');
  document.body.classList.add('fallo-video');
  /* «Abrir en YouTube» no tiene sentido si el problema es un archivo del
     disco… salvo que justamente ahí está la solución: verlo en YouTube.
     Se deja siempre que haya pista. */
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

   Aquí había además un botón «Solo este» que fijaba uno y paraba la
   rotación. Se ha quitado: hacía exactamente lo mismo que dejar marcado
   uno solo, y dos caminos para el mismo resultado son dos motivos para
   dudar justo cuando no hay tiempo. Si quieres dejar fijo el de los QR
   mientras llega la gente, desmarca los otros tres. */
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
    </div>`).join('');

  caja.querySelectorAll('input').forEach(inp => inp.addEventListener('change', () => {
    mandarPaneles(CARTELONES.filter(c => $('#cp_' + c.k).checked).map(c => c.k));
  }));

  $('#pieRotacion').textContent =
    act.length > 1 ? `Rotando ${act.length} cada 15 s`
                   : (act.length ? 'Solo ese, fijo en la tele'
                                 : 'Ninguno: la tele queda limpia');
}

function mandarPaneles(lista){
  S.paneles = lista;
  pintarPaneles();
  KL.comandos.cartelones(lista);
}

$('#bRotar').addEventListener('click', () => mandarPaneles(CARTELONES.map(c => c.k)));
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
  $('#cAuto').checked   = S.openVideo;
  $('#cStar').checked   = S.alsoStar;
  $('#cQual').value     = S.quality;
  $('#cAntiCut').checked= S.antiCut;
  $('#cSig').value      = S.avisoSig;
  /* El sufijo es del espacio en el que estás, y se dice cuál: sin eso,
     el campo parece global y se edita el equivocado. */
  const nom = (ESPACIOS[S.modo] || {}).nombre || 'Karaoke';
  $('#cSegFin').value   = S.segFin;
  $('#cSegLlam').value  = S.segLlamada;
  $('#cSonido').value   = S.sonidoEn;
  $('#cDesfase').value  = S.desfaseTele;
  $('#cEfecto').value   = S.efecto;
  $('#cGanCancion').value = S.efectoCancion;
  $('#cGanCalent').value  = S.efectoCalent;
  $('#cRetirar').checked= S.retirar;
  KL.diagnostico.pintar();
  abrir('#ovCfg');
});
$('#bSaveCfg').addEventListener('click', () => {
  /* Al guardar, el sufijo se apunta en el modo que esté activo. */
  S.sufijos[S.modo] = S.suffix;
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
  dibujarSiguiente();
  KL.reproductor.calidad(S.quality);
  guardarPrefs();
  cerrar('#ovCfg');
  toast('Ajustes guardados');
});

/* El panel de diagnóstico vive en `js/diagnostico.js`. Aquí solo se
   llama: qué hay que comprobar y cómo se dice no es asunto del archivo
   que conecta botones. */
/* ---- Copia de seguridad ------------------------------------------------ */
$('#bExp').addEventListener('click', () => {
  const blob = new Blob([JSON.stringify({
    app:'openkaraoke-center', version:'1.1',
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
      cerrar('#ovCfg');
      toast('Copia importada');
    }catch(e){ toast('⚠ El archivo no es válido'); }
  };
  rd.readAsText(f);
  ev.target.value = '';
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
pintarDJ();
dibujarRed();
$('#bAuto').classList.toggle('on', S.autoNext);
$('#cSegFin').value = S.segFin;
KL.evento.iniciar();
draw();
if(window.innerWidth > 900) setTimeout(() => $('#q').focus(), 120);

/* El estado viene del servidor, y a partir de ahí escuchamos cambios. */
cargar().then(() => {
  dibujarRed();
  KL.cola.comprobarDescargas();
  /* Enlace desde qa.php: «Cargar fiesta de ejemplo» ya no duplica la
     lógica en PHP (se guardaban títulos sueltos, sin resolver contra
     YouTube, y biblioteca/cola quedaban en blanco). Llega aquí y usa la
     función de siempre, que sí busca cada título de verdad. Se limpia
     la URL para que un F5 no la repita. */
  if(new URLSearchParams(location.search).get('ejemplo') === '1'){
    history.replaceState(null, '', location.pathname);
    KL.cola.montarFiestaDeEjemplo();
  }
}).then(escuchar);

/* Lo ÚNICO que sale de este archivo. La interfaz repinta los cartelones
   cuando el servidor dice que han cambiado —los puede tocar otro
   aparato— y necesita esta puerta. */
KL.panelesDeCalentamiento = pintarPaneles;
KL.pintarCabinaDJ = pintarDJ;

})();
