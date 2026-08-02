<?php
require __DIR__ . '/api/comun.php';
$cfg = cfg();
$pideClave = trim((string)$cfg['clave_fiesta']) !== '';
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#0d0f14">
<title>📱 Pedir una canción</title>
<link rel="manifest" href="manifest.webmanifest">
<style>
:root{--bg:#0d0f14;--bg2:#141821;--bg3:#1b2029;--bg4:#252b38;--line:#2b323f;
--txt:#eaedf3;--txt2:#98a1b2;--txt3:#6a7383;--ac:#22d97a;--dang:#ff5d6c}
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent}
body{margin:0;background:var(--bg);color:var(--txt);font-size:16px;
 font-family:'Segoe UI',system-ui,-apple-system,Roboto,Arial,sans-serif;
 padding:0 0 40px;-webkit-text-size-adjust:100%}
header{padding:22px 18px 16px;text-align:center;background:var(--bg2);border-bottom:1px solid var(--line)}
header h1{margin:0 0 4px;font-size:21px;display:flex;align-items:center;justify-content:center;gap:9px}
header p{margin:0;color:var(--txt3);font-size:13px}
.wrap{max-width:620px;margin:0 auto;padding:16px}
input,button{font-family:inherit;font-size:16px;color:inherit}
.f{margin-bottom:13px}
.f label{display:block;font-size:12.5px;font-weight:700;color:var(--txt2);margin-bottom:6px}
.f input{width:100%;padding:15px 16px;background:var(--bg3);border:1px solid var(--line);
 border-radius:12px;outline:none}
.f input:focus{border-color:var(--ac)}
.b{width:100%;padding:16px;background:var(--ac);color:#052b16;border:none;border-radius:12px;
 font-weight:800;font-size:16px;cursor:pointer}
.b:active{transform:scale(.985)}
.b[disabled]{opacity:.5}
.r{display:flex;gap:12px;align-items:center;padding:11px;background:var(--bg2);
 border:1px solid var(--line);border-radius:13px;margin-bottom:10px}
.r img{width:104px;height:59px;border-radius:8px;object-fit:cover;background:var(--bg4);flex-shrink:0}
.r .m{min-width:0;flex:1}
.r .t{font-size:14px;font-weight:650;line-height:1.3;
 display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.r .c{font-size:11.5px;color:var(--txt3);margin-top:3px}
.r button{background:var(--bg4);border:1px solid var(--line);color:var(--txt2);
 border-radius:999px;padding:11px 15px;font-size:13px;font-weight:800;white-space:nowrap;cursor:pointer}
h2{font-size:13px;letter-spacing:1.4px;text-transform:uppercase;color:var(--txt3);
 margin:26px 0 12px;font-weight:800}
.q{display:flex;gap:11px;align-items:center;padding:10px 12px;background:var(--bg2);
 border:1px solid var(--line);border-radius:12px;margin-bottom:8px}
.q .n{width:24px;text-align:center;color:var(--txt3);font-size:13px;font-weight:700}
.q .t{font-size:13.5px;font-weight:600;line-height:1.3;flex:1;min-width:0;
 display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.q .w{font-size:11px;color:var(--ac);font-weight:700;white-space:nowrap}
.q.now{border-color:var(--ac);background:rgba(34,217,122,.1)}
.msg{padding:13px 15px;border-radius:11px;font-size:14px;margin-bottom:13px;line-height:1.5}
.msg.e{background:#2c1418;border:1px solid #5c2630;color:#ffc4ca}
.msg.o{background:#0f2e1e;border:1px solid #1c6b41;color:#a9f0c8}
.vacio{color:var(--txt3);font-size:14px;text-align:center;padding:26px}
.load{text-align:center;padding:26px;color:var(--txt3);font-size:14px}
</style>
</head>
<body>

<header>
  <h1>🎤 Pide tu canción</h1>
  <p>Se añade a la cola del karaoke</p>
</header>

<div class="wrap">
  <div id="aviso"></div>

  <div class="f">
    <label>¿Cómo te llamas?</label>
    <input id="quien" type="text" placeholder="Tu nombre" autocomplete="nickname" maxlength="24">
  </div>

<?php if ($pideClave): ?>
  <div class="f">
    <label>Contraseña de la fiesta</label>
    <input id="clave" type="password" placeholder="La que te han dicho" autocomplete="off">
  </div>
<?php endif; ?>

  <div class="f">
    <label>Canción</label>
    <input id="q" type="text" placeholder="Artista y título, o pega un enlace de YouTube"
           enterkeyhint="search" autocomplete="off">
  </div>
  <button class="b" id="buscar">Buscar</button>

  <div id="res"></div>

  <h2>En la cola</h2>
  <div id="cola" class="load">Cargando…</div>
</div>

<script>
'use strict';
const $ = s => document.querySelector(s);
const PIDE_CLAVE = <?= $pideClave ? 'true' : 'false' ?>;

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
  $('#res').innerHTML = '<div class="load">Buscando…</div>';
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
    $('#res').innerHTML = '<div class="vacio">Nada encontrado. Prueba a pegar el enlace del vídeo de YouTube.</div>';
    return;
  }
  $('#res').innerHTML = items.map((v,i)=>`
    <div class="r">
      <img src="${esc(v.thumb)}" alt="" loading="lazy">
      <div class="m">
        <div class="t">${esc(v.title)}</div>
        <div class="c">${esc(v.channel)}</div>
      </div>
      <button data-i="${i}">Pedir</button>
    </div>`).join('');

  $('#res').querySelectorAll('button').forEach(b=>{
    b.addEventListener('click', ()=> pedir(items[+b.dataset.i], b));
  });
}

async function pedir(v, boton){
  const quien = $('#quien').value.trim();
  if(!quien){ aviso('Escribe tu nombre primero, para saber de quién es la canción.','e');
              $('#quien').focus(); return; }
  boton.disabled = true; boton.textContent = '…';
  try{
    await api('api/estado.php', {
      accion:'anadir_cola', invitado:true, video:v, quien:quien,
      clave: PIDE_CLAVE ? ($('#clave')?.value || '') : ''
    });
    aviso('✓ Pedida. Ya está en la cola.','o');
    $('#res').innerHTML = ''; $('#q').value = '';
    cargarCola();
  }catch(e){
    aviso(e.message,'e');
    boton.disabled = false; boton.textContent = 'Pedir';
  }
}

let version = -1;
function pintarCola(e){
  version = e.version ?? version;
  const c = e.cola || [];
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
  try{ pintarCola(await api('api/estado.php')); }
  catch(e){ $('#cola').className='vacio'; $('#cola').textContent='No hay conexión con el karaoke.'; }
}

/* La cola se refresca sola cuando alguien pide algo.
   Con pausa entre vueltas: el servidor de PHP atiende de una en una y en la
   fiesta hay varios móviles preguntando a la vez. */
async function escuchar(){
  while(true){
    try{
      const e = await api('api/estado.php?desde='+version);
      if((e.version??0) > version) pintarCola(e);
      await new Promise(r=>setTimeout(r,2000));
    }catch(err){ await new Promise(r=>setTimeout(r,5000)); }
  }
}

$('#buscar').addEventListener('click', buscar);
$('#q').addEventListener('keydown', e=>{ if(e.key==='Enter') buscar(); });

cargarCola().then(escuchar);
</script>
</body>
</html>
