<?php
/* ═══════════════════════════════════════════════════════════════════
   proyector.php — la pantalla del televisor

   Se abre en una segunda ventana y se arrastra a la tele. Muestra el
   vídeo a pantalla completa, quién canta ahora y quién va después.

   Esta pantalla NO manda: sigue a «sonando», que decide el PC. Si
   mandaran las dos, se pelearían por la cola. Aquí no se escribe nada
   en el estado, solo se lee.

   El sonido sale de aquí, porque es lo que está enchufado a la tele.
   El portátil se queda mudo — hay un aviso que lo recuerda.
   ═══════════════════════════════════════════════════════════════════ */

require __DIR__ . '/api/comun.php';
$cfg = cfg();

/* La dirección para los móviles, para enseñarla mientras no suena nada. */
$host = preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
$puerto = (int)($_SERVER['SERVER_PORT'] ?? 8123);
$urlPedir = 'http://' . $host . ($puerto == 80 ? '' : ':' . $puerto) . '/pedir.php';
$hayPeticiones = (bool)$cfg['peticiones'];
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#000">
<title>Karaoke — pantalla</title>
<style>
:root{--bg:#000;--bg2:#0d0f14;--bg3:#1b2029;--line:#2b323f;
--txt:#eaedf3;--txt2:#98a1b2;--txt3:#6a7383;--ac:#22d97a;--dang:#ff5d6c}
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;overflow:hidden}
body{background:var(--bg);color:var(--txt);
 font-family:'Segoe UI',system-ui,-apple-system,Roboto,Arial,sans-serif}
body.quieto{cursor:none}

/* ---- el vídeo ocupa la pantalla entera; lo demás flota encima ---- */
#escena{position:fixed;inset:0;background:#000}
#yt,#vlocal{position:absolute;inset:0;width:100%;height:100%;border:0;background:#000}
#vlocal{object-fit:contain}
.oculto{display:none!important}

/* ---- franja de abajo: quién canta y quién va después ---- */
#franja{position:fixed;left:0;right:0;bottom:0;padding:4.6vh 4vw 3.4vh;
 background:linear-gradient(to top,rgba(0,0,0,.93) 55%,rgba(0,0,0,0));
 display:flex;align-items:flex-end;gap:4vw;
 transition:opacity .5s;pointer-events:none}
#franja.fuera{opacity:0}
.ahora{flex:1;min-width:0}
.et{font-size:1.5vw;letter-spacing:.35vw;text-transform:uppercase;
 color:var(--ac);font-weight:800;margin-bottom:.7vh}
.canta{font-size:5.2vw;font-weight:800;line-height:1.02;
 white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
 text-shadow:0 .3vh 2vh rgba(0,0,0,.9)}
.tema{font-size:2vw;color:var(--txt2);margin-top:1vh;font-weight:600;
 white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.luego{text-align:right;max-width:34vw;flex-shrink:0}
.luego .et{color:var(--txt3)}
.luego .n{font-size:2.6vw;font-weight:800;line-height:1.1;
 white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.luego .t{font-size:1.5vw;color:var(--txt3);margin-top:.6vh;
 white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

/* ---- pantalla de espera: no suena nada ---- */
#espera{position:fixed;inset:0;background:var(--bg2);
 display:flex;flex-direction:column;align-items:center;justify-content:center;
 gap:3vh;padding:6vh 6vw;text-align:center}
#espera h1{font-size:7vw;font-weight:800;letter-spacing:-.15vw}
#espera .sub{font-size:2.2vw;color:var(--txt2);max-width:60vw;line-height:1.5}
#espera .url{font-size:2.8vw;font-weight:800;color:var(--ac);
 background:var(--bg3);border:.2vh solid var(--line);border-radius:1.4vh;
 padding:1.6vh 3vw;word-break:break-all}
#lista{margin-top:1vh;width:min(74vw,1100px);max-height:34vh;overflow:hidden}
#lista .f{display:flex;gap:1.6vw;align-items:center;padding:1.1vh 1.6vw;
 border-bottom:.1vh solid var(--line);font-size:1.9vw;text-align:left}
#lista .f:last-child{border-bottom:0}
#lista .n{color:var(--txt3);font-weight:800;width:2.5vw;flex-shrink:0}
#lista .t{flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#lista .q{color:var(--ac);font-weight:800;flex-shrink:0}

/* ---- el botón de arrancar: los navegadores no dejan sonar sin un clic ---- */
#arranque{position:fixed;inset:0;z-index:50;background:rgba(0,0,0,.94);
 display:flex;flex-direction:column;align-items:center;justify-content:center;
 gap:3vh;text-align:center;padding:6vw;cursor:pointer}
#arranque .b{background:var(--ac);color:#052b16;border:0;border-radius:2vh;
 padding:2.6vh 6vw;font-size:3.4vw;font-weight:800;font-family:inherit;cursor:pointer}
#arranque .p{font-size:1.9vw;color:var(--txt2);max-width:52vw;line-height:1.6}
#arranque .p b{color:var(--txt)}

/* ---- avisos ---- */
#aviso{position:fixed;top:3vh;left:50%;transform:translateX(-50%);z-index:40;
 background:rgba(0,0,0,.9);border:.2vh solid var(--line);border-radius:1.4vh;
 padding:1.6vh 3vw;font-size:1.9vw;font-weight:700;max-width:80vw;text-align:center}
#aviso.mal{border-color:var(--dang);color:#ffc4ca}
#aviso.bien{border-color:var(--ac);color:#a9f0c8}

/* En un monitor pequeño el vw se queda enano: subimos el mínimo. */
@media (max-width:900px){
  .canta{font-size:8vw}.tema{font-size:3.4vw}.et{font-size:2.6vw}
  .luego .n{font-size:4vw}.luego .t{font-size:2.6vw}
  #espera h1{font-size:11vw}#espera .sub,#espera .url{font-size:4vw}
  #lista .f{font-size:3.4vw}#arranque .b{font-size:6vw}#arranque .p{font-size:3.4vw}
}
</style>
</head>
<body>

<div id="escena">
  <div id="yt"></div>
  <video id="vlocal" class="oculto" playsinline></video>
</div>

<div id="espera">
  <h1>🎤 Karaoke</h1>
  <div class="sub" id="esperaSub">Elige una canción en el ordenador y empieza la fiesta.</div>
<?php if ($hayPeticiones): ?>
  <div class="url"><?= htmlspecialchars($urlPedir, ENT_QUOTES, 'UTF-8') ?></div>
  <div class="sub">Entra desde el móvil y pide la tuya.</div>
<?php endif; ?>
  <div id="lista"></div>
</div>

<div id="franja" class="fuera">
  <div class="ahora">
    <div class="et">Ahora canta</div>
    <div class="canta" id="quien">—</div>
    <div class="tema" id="tema"></div>
  </div>
  <div class="luego oculto" id="luego">
    <div class="et">Después</div>
    <div class="n" id="luegoQuien"></div>
    <div class="t" id="luegoTema"></div>
  </div>
</div>

<div id="arranque">
  <button class="b" id="empezar">Encender la pantalla</button>
  <div class="p">
    Pulsa una vez y ya se queda. El navegador no deja que suene el vídeo
    hasta que alguien toca la pantalla.<br><br>
    <b>El sonido sale de aquí.</b> Baja el volumen del ordenador, que lleva
    el mismo vídeo por su cuenta.
  </div>
</div>

<script>
'use strict';
const $ = s => document.querySelector(s);
const esc = s => String(s??'').replace(/[&<>"']/g,c=>
  ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

let version   = -1;    // última versión del estado que hemos pintado
let arrancado = false; // ¿ya ha pulsado alguien? sin eso no hay sonido
let sonandoId = null;  // qué pista tenemos puesta, para no recargarla en cada sondeo
let ultimo    = null;  // último estado recibido, por si arrancan tarde
let yt = null, listo = false;

/* ---- el reproductor de YouTube ---------------------------------------- */
window.onYouTubeIframeAPIReady = function(){
  yt = new YT.Player('yt', {
    playerVars:{autoplay:1, controls:0, rel:0, modestbranding:1,
                playsinline:1, iv_load_policy:3, disablekb:1},
    events:{
      onReady:()=>{ listo = true; if(ultimo) pintar(ultimo, true); },
      onError:e=>{
        /* 101/150 = el dueño no deja incrustarlo. Aquí no pasamos a la
           siguiente: manda el ordenador, y allí ya salta el aviso. */
        aviso('Ese vídeo no se puede ver aquí. Míralo en el ordenador.', 'mal');
      }
    }
  });
};
(function(){ const s=document.createElement('script');
  s.src='https://www.youtube.com/iframe_api'; document.head.appendChild(s); })();

/* ---- avisos que se van solos ------------------------------------------ */
let avisoT = null;
function aviso(txt, tipo){
  clearTimeout(avisoT);
  let d = $('#aviso');
  if(!txt){ if(d) d.remove(); return; }
  if(!d){ d = document.createElement('div'); d.id='aviso'; document.body.appendChild(d); }
  d.className = tipo || '';
  d.textContent = txt;
  avisoT = setTimeout(()=>{ const x=$('#aviso'); if(x) x.remove(); }, 6000);
}

/* ---- poner una pista -------------------------------------------------- */
function poner(pista){
  const v = $('#vlocal');
  if(pista.local){
    /* Descargada: del disco. Ni internet, ni error 153, ni cortes. */
    if(listo && yt) yt.stopVideo();
    $('#yt').classList.add('oculto');
    v.classList.remove('oculto');
    v.src = pista.local;
    v.play().catch(()=>aviso('Pulsa la pantalla para que suene', 'mal'));
  } else {
    v.pause(); v.removeAttribute('src'); v.classList.add('oculto');
    $('#yt').classList.remove('oculto');
    if(listo && yt) yt.loadVideoById({videoId: pista.videoId, startSeconds: 0});
  }
}

function parar(){
  const v = $('#vlocal');
  v.pause(); v.removeAttribute('src'); v.classList.add('oculto');
  if(listo && yt) yt.stopVideo();
  $('#yt').classList.add('oculto');
}

/* ---- pintar el estado -------------------------------------------------- */
function pintar(e, forzar){
  ultimo  = e;
  version = e.version ?? version;

  const cola = e.cola || [];
  const i    = cola.findIndex(t => t.id === e.sonando);
  const hoy  = i >= 0 ? cola[i] : null;
  const next = i >= 0 ? cola[i+1] : cola[0];

  /* --- nada sonando: pantalla de espera con la cola --- */
  if(!hoy){
    if(sonandoId !== null || forzar){ parar(); sonandoId = null; }
    $('#espera').classList.remove('oculto');
    $('#franja').classList.add('fuera');
    $('#esperaSub').textContent = cola.length
      ? 'Hay ' + cola.length + (cola.length===1 ? ' canción esperando.' : ' canciones esperando.')
      : 'Elige una canción en el ordenador y empieza la fiesta.';
    $('#lista').innerHTML = cola.slice(0,8).map((t,n)=>`
      <div class="f">
        <span class="n">${n+1}</span>
        <span class="t">${esc(t.title)}</span>
        ${t.pedida ? `<span class="q">${esc(t.pedida)}</span>` : ''}
      </div>`).join('');
    return;
  }

  /* --- suena algo --- */
  $('#espera').classList.add('oculto');
  $('#franja').classList.remove('fuera');

  /* Quien la pidió va en grande; si la puso el PC, el título manda. */
  $('#quien').textContent = hoy.pedida || hoy.title;
  $('#tema').textContent  = hoy.pedida ? hoy.title : (hoy.channel || '');

  if(next){
    $('#luego').classList.remove('oculto');
    $('#luegoQuien').textContent = next.pedida || next.title;
    $('#luegoTema').textContent  = next.pedida ? next.title : (next.channel || '');
  } else {
    $('#luego').classList.add('oculto');
  }

  /* Solo recargamos si ha cambiado de canción: si no, cada sondeo
     cortaría el vídeo por la mitad. */
  if(hoy.id !== sonandoId || forzar){
    sonandoId = hoy.id;
    if(arrancado) poner(hoy);
  }

  /* La franja estorba: se retira sola y vuelve al cambiar de canción. */
  clearTimeout(pintar.t);
  pintar.t = setTimeout(()=>$('#franja').classList.add('fuera'), 12000);
}

/* ---- hablar con el servidor -------------------------------------------- */
async function leer(url){
  const r = await fetch(url);
  const j = await r.json();
  if(!j.ok) throw new Error(j.error || ('error ' + r.status));
  return j;
}

async function escuchar(){
  /* Preguntamos cada segundo y medio y soltamos. Retener la conexión, que
     era lo suyo, deja clavado al servidor de PHP: atiende de una en una y
     la tele estaría acaparándolo mientras los móviles esperan. */
  while(true){
    try{
      const e = await leer('api/estado.php?desde=' + version);
      if((e.version ?? 0) > version) pintar(e);
      await new Promise(r => setTimeout(r, 1500));
    }catch(err){
      aviso('Sin conexión con el karaoke. Reintentando…', 'mal');
      await new Promise(r => setTimeout(r, 5000));
    }
  }
}

/* ---- arranque ---------------------------------------------------------- */
$('#empezar').addEventListener('click', async () => {
  arrancado = true;
  $('#arranque').remove();
  try{ await document.documentElement.requestFullscreen(); }catch(e){}
  if(ultimo) pintar(ultimo, true);
});

/* El ratón encima de la tele se ve; lo escondemos cuando nadie lo mueve. */
let quietoT = null;
addEventListener('mousemove', () => {
  document.body.classList.remove('quieto');
  clearTimeout(quietoT);
  quietoT = setTimeout(()=>document.body.classList.add('quieto'), 3000);
});

/* Al mover el ratón vuelve la franja, por si alguien pregunta quién canta. */
addEventListener('mousemove', () => {
  if(sonandoId){
    $('#franja').classList.remove('fuera');
    clearTimeout(pintar.t);
    pintar.t = setTimeout(()=>$('#franja').classList.add('fuera'), 12000);
  }
});

addEventListener('keydown', e => {
  if(e.key === 'f' || e.key === 'F'){
    if(document.fullscreenElement) document.exitFullscreen();
    else document.documentElement.requestFullscreen().catch(()=>{});
  }
});

leer('api/estado.php').then(e => pintar(e, true)).catch(()=>{}).then(escuchar);
</script>
</body>
</html>
