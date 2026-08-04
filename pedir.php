<?php
require __DIR__ . '/api/comun.php';
$cfg = cfg();
$pideClave = trim((string)$cfg['clave_fiesta']) !== '';
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#1E2329">
<title>📱 Pedir una canción</title>
<!-- El de los invitados. Antes apuntaba al del operador: quien lo
     instalaba desde el movil acababa con el puesto de mando en el
     cajon de aplicaciones. -->
<link rel="manifest" href="manifest-pedir.webmanifest">
<link rel="apple-touch-icon" href="iconos/icono-pedir-192.png">
<style>
/* ═══════════════════════════════════════════════════════════════════
   Rediseño UX (2026-08-03). Nada de funcionalidad ha cambiado: mismo
   buscador, mismo nombre, misma cola, mismo termómetro, misma
   contraseña de fiesta si la hay. Lo que cambia es que esto ya no
   parece un buscador de YouTube: parece la puerta de una fiesta.

   Filosofía en una pregunta: ¿esto hace que alguien que nunca ha usado
   la aplicación tenga más ganas de pedir una canción? Si la respuesta
   es no, no entra. Nada de categorías rápidas, nada de filtros nuevos,
   nada de configuración — solo que lo que ya había se entienda y se
   sienta bien a la primera mirada, con el móvil en una mano, de pie,
   con poca luz y música de fondo.
   ═══════════════════════════════════════════════════════════════════ */
:root{--bg:#1E2329;--bg2:#2B3138;--bg3:#353D46;--bg4:#3F4852;--line:#3A414B;
--txt:#F3F5F7;--txt2:#A8B2BD;--txt3:#7C8794;--ac:#5A8DEE;--ac2:#4676D6;--dang:#ff5d6c}
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent}
body{margin:0;background:var(--bg);color:var(--txt);font-size:16px;
 font-family:'Segoe UI',system-ui,-apple-system,Roboto,Arial,sans-serif;
 padding:0 0 40px;-webkit-text-size-adjust:100%}
header{padding:22px 20px 16px;text-align:center}
header h1{margin:0 0 4px;font-size:22px;display:flex;align-items:center;justify-content:center;gap:9px}
header p{margin:0;color:var(--txt2);font-size:14px}
.wrap{max-width:560px;margin:0 auto;padding:0 18px}

/* ---- La bienvenida: solo la primera vez de la sesión --------------------
   Un tap y desaparece para siempre en ese móvil, esa noche. No vuelve a
   salir aunque se busque diez veces — sería una pantalla de carga que
   solo molesta a partir de la segunda vez. */
#bienvenida{padding:14vh 24px 8vh;text-align:center}
#bienvenida .emoji{font-size:15vw;line-height:1;margin-bottom:18px}
#bienvenida h2{margin:0 0 12px;font-size:28px;font-weight:800;text-wrap:balance}
#bienvenida p{margin:0 0 34px;color:var(--txt2);font-size:16px;line-height:1.5}
#bienvenida button{width:100%;max-width:320px;height:58px;background:var(--ac);
 color:#fff;border:none;border-radius:999px;font-weight:800;font-size:17px;cursor:pointer}
#bienvenida button:active{transform:scale(.97)}
body.entrado #bienvenida{display:none}
body:not(.entrado) #app{display:none}

/* ---- A qué lista pides -------------------------------------------------
   Antes esto no existía: la petición seguía siempre al espacio activo del
   operador, sin que el invitado pudiera elegir. Decisión revisada
   (2026-08-04): una fiesta pasa de música a karaoke y vuelve, así que
   conviene poder dejar algo listo en la otra lista sin esperar a que el
   operador cambie. Dos píldoras, la misma idea que los tres modos de
   cSonido en Ajustes. */
#selEspacio{display:flex;gap:8px;margin:16px 0 4px}
#selEspacio button{flex:1;height:44px;border-radius:999px;border:1px solid var(--line);
 background:var(--bg2);color:var(--txt2);font-size:13.5px;font-weight:700;cursor:pointer;
 display:flex;align-items:center;justify-content:center;gap:6px}
#selEspacio button.on{background:rgba(90,141,238,.14);border-color:var(--ac);color:var(--ac)}

/* ---- El buscador: el elemento que manda ------------------------------- */
.buscadorCaja{margin:18px 0 6px}
.buscadorCaja label{display:block;font-size:12.5px;font-weight:700;color:var(--txt2);margin-bottom:7px}
#q{width:100%;height:56px;padding:0 20px;background:var(--bg2);border:2px solid var(--line);
 border-radius:999px;outline:none;font-size:16px;color:var(--txt)}
#q::placeholder{color:var(--txt3)}
#q:focus{border-color:var(--ac)}
.b{width:100%;height:52px;margin-top:10px;background:var(--ac);color:#fff;border:none;
 border-radius:999px;font-weight:800;font-size:16px;cursor:pointer}
.b:active{transform:scale(.98)}
.b[disabled]{opacity:.5}

/* ---- Quién eres, más discreto que el buscador -------------------------
   Sigue haciendo falta -el servidor no acepta una petición sin nombre-,
   pero no tiene que competir con lo que se usa a cada rato. */
.quienCaja{margin:16px 0}
.quienCaja label{display:block;font-size:12px;font-weight:700;color:var(--txt3);margin-bottom:6px}
.quienCaja input{width:100%;height:44px;padding:0 15px;background:var(--bg2);border:1px solid var(--line);
 border-radius:12px;outline:none;font-size:15px;color:var(--txt)}
.quienCaja input:focus{border-color:var(--ac)}

/* ---- Resultados como tarjetas, con aire -------------------------------- */
.r{display:flex;flex-direction:column;gap:10px;padding:14px;background:var(--bg2);
 border:1px solid var(--line);border-radius:16px;margin-bottom:12px}
.r .fila{display:flex;gap:14px;align-items:center}
.r img{width:96px;height:54px;border-radius:10px;object-fit:cover;background:var(--bg4);flex-shrink:0}
.r .m{min-width:0;flex:1}
.r .t{font-size:15px;font-weight:700;line-height:1.3;
 display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.r .c{font-size:12.5px;color:var(--txt3);margin-top:4px}
.r button{width:100%;height:48px;background:var(--ac);border:none;color:#fff;
 border-radius:999px;font-size:15px;font-weight:800;cursor:pointer}
.r button:active{transform:scale(.98)}

h2{font-size:12.5px;letter-spacing:1.4px;text-transform:uppercase;color:var(--txt3);
 margin:30px 0 12px;font-weight:800}
.q{display:flex;gap:11px;align-items:center;padding:11px 13px;background:var(--bg2);
 border:1px solid var(--line);border-radius:14px;margin-bottom:8px}
.q .n{width:22px;text-align:center;color:var(--txt3);font-size:13px;font-weight:700;flex-shrink:0}
.q .t{font-size:13.5px;font-weight:600;line-height:1.3;flex:1;min-width:0;
 display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.q .w{font-size:11px;color:var(--ac);font-weight:700;white-space:nowrap}
.q.now{border-color:var(--ac);background:rgba(90,141,238,.12)}

.msg{padding:14px 16px;border-radius:14px;font-size:14.5px;margin-bottom:14px;line-height:1.5}
.msg.e{background:#2c1418;border:1px solid #5c2630;color:#ffc4ca}
.vacio{color:var(--txt3);font-size:14px;text-align:center;padding:26px}
.load{text-align:center;padding:26px;color:var(--txt3);font-size:14px}

/* ---- La confirmación: un momento, no una ventana -----------------------
   Nada de alert(), nada de modal con fondo oscurecido. Sustituye al
   buscador un instante y vuelve solo al escribir otra vez. */
#confirmacion{text-align:center;padding:10vh 20px 6vh}
#confirmacion .emoji{font-size:14vw;line-height:1;margin-bottom:14px}
#confirmacion h3{margin:0 0 10px;font-size:22px;font-weight:800}
#confirmacion p{margin:0 0 26px;color:var(--txt2);font-size:15px;line-height:1.5}
#confirmacion button{height:48px;padding:0 26px;background:var(--bg3);border:1px solid var(--line);
 color:var(--txt);border-radius:999px;font-weight:700;font-size:14.5px;cursor:pointer}

/* El termómetro: lo primero que se ve al llegar. Dice cómo va la noche
   sin nombrar nada de la aplicación. */
#termometro{margin:20px 0 8px;padding:16px 18px;background:var(--bg2);border:1px solid var(--line);
 border-radius:16px}
#termometro.oculto{display:none}
#termometro .tmTexto{display:flex;align-items:center;gap:10px;font-size:16.5px;
 font-weight:800;margin-bottom:10px}
#termometro .tmIcono{font-size:24px;line-height:1}
#termometro .tmBarra{height:9px;border-radius:999px;overflow:hidden;
 background:var(--bg4);border:1px solid var(--line)}
#termometro .tmBarra i{display:block;height:100%;background:var(--ac);
 border-radius:999px;transition:width 1.2s cubic-bezier(.3,.9,.3,1)}
#termometro .tmEstado{margin-top:8px;font-size:11px;font-weight:800;
 letter-spacing:.8px;text-transform:uppercase}
#termometro[data-nivel="0"] .tmBarra i{background:var(--tm-0,#7ecb92)}
#termometro[data-nivel="1"] .tmBarra i{background:var(--tm-1,#22d97a)}
#termometro[data-nivel="2"] .tmBarra i{background:var(--tm-2,#4da3ff)}
#termometro[data-nivel="3"] .tmBarra i{background:var(--tm-3,#a78bfa)}
#termometro[data-nivel="4"] .tmBarra i{background:var(--tm-4,#ff9f43)}
#termometro[data-nivel="0"] .tmEstado{color:var(--tm-0,#7ecb92)}
#termometro[data-nivel="1"] .tmEstado{color:var(--tm-1,#22d97a)}
#termometro[data-nivel="2"] .tmEstado{color:var(--tm-2,#4da3ff)}
#termometro[data-nivel="3"] .tmEstado{color:var(--tm-3,#a78bfa)}
#termometro[data-nivel="4"] .tmEstado{color:var(--tm-4,#ff9f43)}

@media (prefers-reduced-motion: reduce){ * { transition:none !important; animation:none !important } }
</style>
</head>
<body>

<div id="bienvenida">
  <div class="emoji">🎤</div>
  <h2>¿Te animas a cantar?</h2>
  <p>Busca una canción y añádela a la fiesta.</p>
  <button id="bEmpezar">Empezar</button>
</div>

<div id="app">
<header>
  <h1 id="titulo">🎤 Pide tu canción</h1>
  <p id="subtitulo">Se añade a la cola del karaoke</p>
</header>

<div class="wrap">
  <div id="aviso"></div>

  <div id="selEspacio">
    <button type="button" data-esp="karaoke">🎤 Karaoke</button>
    <button type="button" data-esp="dj">🎧 Música ambiente</button>
  </div>

  <div id="pantallaBusqueda">
    <div class="buscadorCaja">
      <label for="q">¿Qué te apetece cantar?</label>
      <input id="q" type="text" placeholder="Busca una canción o un artista…"
             enterkeyhint="search" autocomplete="off">
      <button class="b" id="buscar">Buscar</button>
    </div>

    <div class="quienCaja">
      <label>¿Cómo te llamas?</label>
      <input id="quien" type="text" placeholder="Tu nombre" autocomplete="nickname" maxlength="24">
    </div>

<?php if ($pideClave): ?>
    <div class="quienCaja">
      <label>Contraseña de la fiesta</label>
      <input id="clave" type="password" placeholder="La que te han dicho" autocomplete="off">
    </div>
<?php endif; ?>

    <div id="res"></div>
  </div>

  <div id="confirmacion" style="display:none"></div>

  <!-- El termómetro, arriba de todo lo que se ve al llegar: es lo
       primero que le dice a un invitado cómo va la noche, y de paso si
       hace falta que pida algo. Nunca dice «hay 4 en la cola». -->
  <div id="termometro" class="oculto"></div>

  <h2>Lo que viene</h2>
  <div id="cola" class="load">Cargando…</div>
</div>
</div>

<script>
'use strict';
const $ = s => document.querySelector(s);
const $$ = s => document.querySelectorAll(s);
const PIDE_CLAVE = <?= $pideClave ? 'true' : 'false' ?>;

/* La bienvenida se ve una vez por pestaña, no una vez por móvil: volver
   a abrir el enlace media hora después para pedir otra no tiene que
   volver a explicar qué es esto, pero cerrar el navegador y volver sí
   puede -es indistinguible de la primera vez para quien lo usa-. */
try{
  if(sessionStorage.getItem('karaoke_entrado')) document.body.classList.add('entrado');
}catch(e){ document.body.classList.add('entrado'); }
$('#bEmpezar').addEventListener('click', () => {
  document.body.classList.add('entrado');
  try{ sessionStorage.setItem('karaoke_entrado', '1'); }catch(e){}
  $('#q').focus();
});

/* El nombre se recuerda para no escribirlo cada vez. */
try{ $('#quien').value = localStorage.getItem('karaoke_nombre') || ''; }catch(e){}
$('#quien').addEventListener('input', e => {
  try{ localStorage.setItem('karaoke_nombre', e.target.value); }catch(x){}
});

const esc = s => String(s??'').replace(/[&<>"']/g,c=>
  ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

function aviso(txt, tipo){
  $('#aviso').innerHTML = txt ? `<div class="msg ${tipo}">${esc(txt)}</div>` : '';
  if(txt) window.scrollTo({top:0, behavior:'smooth'});
}

async function api(url, cuerpo){
  const o = cuerpo ? {method:'POST', headers:{'Content-Type':'application/json'},
                      body:JSON.stringify(cuerpo)} : {};
  /* Al invitado no le sirve «NetworkError»: o se ha ido de la wifi, o han
     cerrado el karaoke. Lo que puede hacer es distinto en cada caso, pero
     mirar el móvil no es una de las dos. */
  let r;
  try{
    r = await fetch(url, o);
  }catch(err){
    throw new Error('No llego al karaoke. Comprueba que sigues en la misma wifi de la casa.');
  }
  const j = await r.json().catch(()=>({ok:false,error:'el servidor no responde bien'}));
  if(!j.ok) throw new Error(j.error || ('error '+r.status));
  return j;
}

async function buscar(){
  const q = $('#q').value.trim();
  if(!q){ $('#q').focus(); return; }
  aviso('');
  $('#res').innerHTML = '<div class="load">🎵 Buscando canciones…</div>';
  try{
    const j = await api('api/buscar.php?q='+encodeURIComponent(q));
    pintar(j.items || []);
  }catch(e){
    $('#res').innerHTML = '';
    aviso(e.message, 'e');
  }
}

function pintar(items){
  if(!items.length){
    $('#res').innerHTML = '<div class="vacio">😅 No encontramos esa canción.<br>Prueba con otro artista o una palabra diferente.</div>';
    return;
  }
  $('#res').innerHTML = items.map((v,i)=>`
    <div class="r">
      <div class="fila">
        <img src="${esc(v.thumb)}" alt="" loading="lazy">
        <div class="m">
          <div class="t">${esc(v.title)}</div>
          <div class="c">${esc(v.channel)}</div>
        </div>
      </div>
      <button data-i="${i}">🎤 Cantar</button>
    </div>`).join('');

  $('#res').querySelectorAll('button').forEach(b=>{
    b.addEventListener('click', ()=> pedir(items[+b.dataset.i], b));
  });
}

/* La confirmación sustituye a la pantalla de búsqueda un momento — no es
   un cartel encima, es lo único que hay hasta que se decide seguir. Un
   único botón para volver: no hace falta ningún camino más. */
function mostrarConfirmacion(texto){
  $('#pantallaBusqueda').style.display = 'none';
  const c = $('#confirmacion');
  c.style.display = '';
  c.innerHTML = `
    <div class="emoji">🎉</div>
    <h3>¡Perfecto!</h3>
    <p>${esc(texto)}</p>
    <button id="bOtra">Buscar otra</button>`;
  $('#bOtra').addEventListener('click', () => {
    c.style.display = 'none';
    $('#pantallaBusqueda').style.display = '';
    $('#q').focus();
  });
}

async function pedir(v, boton){
  const quien = $('#quien').value.trim();
  if(!quien){ aviso('Escribe tu nombre primero, para saber de quién es la canción.','e');
              $('#quien').focus(); return; }
  boton.disabled = true; boton.textContent = '…';
  const esp = espacioVista();
  try{
    const r = await api('api/estado.php', {
      accion:'anadir_cola', invitado:true, video:v, quien:quien, espacio:esp,
      clave: PIDE_CLAVE ? ($('#clave')?.value || '') : ''
    });
    /* «Hemos apuntado tu canción», no «ya está en la cola». La canción
       no pertenece a una lista: pertenece a quien la ha pedido.

       Y si ya la había elegido alguien más, se dice — sin impedir nada.
       Que dos personas quieran cantar lo mismo no es un problema: son
       dos actuaciones, y a quien acaba de pedirla puede apetecerle
       saberlo. */
    const varias = r && r._varias && r._varias.videoId === v.videoId
                   ? r._varias.cuantas : 0;
    /* El texto de confirmación depende de a qué lista se ha mandado —la
       elegida, no siempre la activa—: en Karaoke hay alguien esperando su
       turno delante del micro, en Cabina DJ no hay actuación que anunciar
       — solo una lista que sigue sonando sola. Si se ha mandado a un
       espacio que hoy no es el activo, se lo decimos: no va a sonar ya
       mismo, y eso no es un fallo, es justo lo que ha elegido. */
    const paraLuego = esp !== espacioActivo;
    const texto = esp === 'dj'
      ? (paraLuego
          ? 'Apuntada para la música ambiente. Sonará cuando se active esa lista.'
          : 'Tu canción ya suena en la sesión. Entrará en su turno.')
      : (paraLuego
          ? 'Apuntada para el karaoke. Entrará en la cola cuando se active ese espacio.'
          : varias >= 2
            ? 'Tu canción ya forma parte de la fiesta. Esta también la ha elegido otra persona — cada versión será distinta.'
            : 'Tu canción ya forma parte de la fiesta. Te avisamos cuando te toque.');
    mostrarConfirmacion(texto);
    $('#res').innerHTML = ''; $('#q').value = '';
    cargarCola();
  }catch(e){
    aviso(e.message,'e');
    boton.disabled = false; boton.textContent = '🎤 Cantar';
  }
}

let version = -1;
let espacioActivo = 'karaoke';   // lo que el operador tiene puesto ahora mismo
let espacioElegido = null;       // lo que el invitado ha elegido, si ha tocado algo

/* Antes de elegir nada, el invitado sigue viendo y pidiendo al espacio
   activo — el comportamiento de siempre. En cuanto toca una píldora, esa
   elección manda para él, y no vuelve a cambiar sola aunque el operador
   cambie de espacio: la ha elegido a propósito, quizás para dejar algo
   listo antes de que la fiesta llegue ahí. Se recuerda en esta pestaña
   (`sessionStorage`), igual que el nombre en `localStorage` se recuerda
   entre visitas. */
try{ espacioElegido = sessionStorage.getItem('karaoke_espacio') || null; }catch(e){}

function espacioVista(){ return espacioElegido || espacioActivo; }

function textosEspacio(esp){
  return esp === 'dj'
    ? { titulo:'🎧 Añade música', sub:'Se añade a la lista que suena ahora. Entra sola, sin turnos.' }
    : { titulo:'🎤 Pide tu canción', sub:'Se añade a la cola del karaoke. El operador la lanza cuando te toque.' };
}

function pintarSelector(){
  const esp = espacioVista();
  $$('#selEspacio button').forEach(b => b.classList.toggle('on', b.dataset.esp === esp));
  const t = textosEspacio(esp);
  $('#titulo').textContent = t.titulo;
  $('#subtitulo').textContent = t.sub;
}

$$('#selEspacio button').forEach(b => b.addEventListener('click', () => {
  espacioElegido = b.dataset.esp;
  try{ sessionStorage.setItem('karaoke_espacio', espacioElegido); }catch(e){}
  pintarSelector();
  pintarColaDesdeCache();
}));

function pintarEspacio(esp){
  if(esp === espacioActivo) return;
  espacioActivo = esp;
  /* Si el invitado no ha elegido nada todavía, el selector sigue
     reflejando el activo — es exactamente lo que hacía este rótulo antes
     de que existiera la elección manual. */
  if(!espacioElegido) pintarSelector();
}

let ultimoEstado = null;
function pintarColaDesdeCache(){ if(ultimoEstado) pintarCola(ultimoEstado); }

function pintarCola(e){
  ultimoEstado = e;
  version = e.version ?? version;
  pintarEspacio(e.espacio || 'karaoke');
  if(!espacioElegido) pintarSelector();  // por si es la primera vez que se sabe el activo
  /* La lista y el termómetro muestran el espacio que se está VIENDO —el
     elegido, o el activo si no se ha tocado nada—, no siempre el activo:
     si un invitado ha elegido Cabina DJ para dejar algo listo, tiene que
     poder ver esa lista, no la de Karaoke. */
  const esp = espacioVista();
  const c = (e.cola || []).filter(t => (t.espacio || 'karaoke') === esp);
  if(window.KL && KL.termometro){
    KL.termometro.pintar($('#termometro'), c.length, e.tema, e.termometro);
  }
  if(!c.length){ $('#cola').className='vacio'; $('#cola').textContent='La cola está vacía. Estrénala tú.'; return; }
  $('#cola').className='';
  $('#cola').innerHTML = c.map((t,i)=>`
    <div class="q ${t.id===e.sonando?'now':''}">
      <span class="n">${t.id===e.sonando?'♪':(i+1)}</span>
      <span class="t">${esc(t.title)}</span>
      ${t.pedida?`<span class="w">${esc(t.pedida)}</span>`:''}
    </div>`).join('');
}

async function cargarCola(){
  try{ pintarCola(await api('api/estado.php?quien=movil')); }
  catch(e){ $('#cola').className='vacio'; $('#cola').textContent='No hay conexión con el karaoke.'; }
}

/* La cola se refresca sola cuando alguien pide algo.
   Con pausa entre vueltas: el servidor de PHP atiende de una en una y en la
   fiesta hay varios móviles preguntando a la vez. */
async function escuchar(){
  while(true){
    try{
      const e = await api('api/estado.php?quien=movil&desde='+version);
      if((e.version??0) > version) pintarCola(e);
      await new Promise(r=>setTimeout(r,2000));
    }catch(err){ await new Promise(r=>setTimeout(r,5000)); }
  }
}

$('#buscar').addEventListener('click', buscar);
$('#q').addEventListener('keydown', e=>{ if(e.key==='Enter') buscar(); });

pintarSelector();
cargarCola().then(escuchar);
</script>
<!-- Instalable como aplicación aparte: un toque en el cajón del móvil en
     vez de buscar el enlace o volver a escanear el QR. -->
<script src="js/termometro.js"></script>
<script src="js/instalar.js"></script>
<script>
/* La página de pedir también cachea: si la wifi de la casa se cae un
   momento, el invitado no se queda con una pantalla en blanco. */
if('serviceWorker' in navigator){
  addEventListener('load', () => navigator.serviceWorker.register('sw.js').catch(() => {}));
}
</script>
</body>
</html>
