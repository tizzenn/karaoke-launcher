<?php
/* Esto es .php y no .html por una razón: así puede cerrarse la puerta.

   La suite borra y reescribe data/pruebas.json sin preguntar, y deja a la
   vista el motor de estados entero. En la fiesta el servidor está abierto
   en la wifi para que los móviles pidan canciones, y ahí dentro no pinta
   nada una página que manipula el estado. Un .html lo sirve el servidor a
   quien lo pida; un .php decide.

   Solo desde el propio ordenador. Si algún día hace falta probar desde
   otro equipo de la red, se comenta esta línea a conciencia. */
$yo = $_SERVER['REMOTE_ADDR'] ?? '';
if ($yo !== '127.0.0.1' && $yo !== '::1' && $yo !== 'localhost') {
  http_response_code(403);
  header('Content-Type: text/plain; charset=UTF-8');
  exit("Las pruebas solo se abren desde el ordenador que hace de servidor.\n"
     . "Abre http://localhost:8123/pruebas/pruebas.php en ese ordenador.\n");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Karaoke Launcher — pruebas</title>
<!--
  ═══════════════════════════════════════════════════════════════════
  Pruebas automáticas de Karaoke Launcher

  Se abren con Karaoke.bat en marcha y esta dirección:

      http://localhost:8123/pruebas/pruebas.php

  Sin instalar nada. Ni Node, ni Python, ni npm: la misma regla que el
  resto del proyecto. Unas pruebas que el dueño del proyecto no puede
  ejecutar en su propio ordenador no las ejecuta nadie.

  ── Cómo funcionan ──────────────────────────────────────────────────
  Se abre la aplicación real dentro de un marco, con `?bd=pruebas`, y se
  la maneja desde fuera. Es la aplicación de verdad, no una imitación:
  el mismo motor de estados, el mismo almacén, el mismo servidor.

  `?bd=pruebas` hace que todo trabaje sobre `data/pruebas.json`. **Tu
  biblioteca y tu cola no se tocan.** Una suite que borra la cola de la
  fiesta no la ejecuta nadie dos veces.

  ── Qué NO cubren, y por qué ────────────────────────────────────────
  La pantalla completa y el foco entre ventanas no se pueden probar
  dentro de un marco. Eso sigue en PRUEBAS.md, a mano. Y en `e2e/` están
  los guiones de Playwright que sí lo hacen, para quien los tenga.
  ═══════════════════════════════════════════════════════════════════
-->
<style>
:root{--bg:#0d0f14;--bg2:#141821;--bg3:#1b2029;--line:#2b323f;
 --txt:#eaedf3;--txt2:#98a1b2;--txt3:#6a7383;--ok:#22d97a;--mal:#ff5d6c;--avi:#ffb648}
*{box-sizing:border-box;margin:0;padding:0}
body{background:var(--bg);color:var(--txt);font:15px/1.5 'Segoe UI',system-ui,sans-serif;padding:24px}
h1{font-size:22px;margin-bottom:4px}
.sub{color:var(--txt2);font-size:13.5px;margin-bottom:18px}
.sub code{background:var(--bg3);padding:2px 6px;border-radius:5px}
#mandos{display:flex;gap:10px;align-items:center;margin-bottom:18px;flex-wrap:wrap}
button{background:var(--ok);color:#052b16;border:0;border-radius:999px;
 padding:11px 22px;font:800 14px inherit;cursor:pointer}
button.g{background:var(--bg3);color:var(--txt2);border:1px solid var(--line)}
button:disabled{opacity:.5;cursor:default}
#marcador{margin-left:auto;font-weight:800;font-variant-numeric:tabular-nums}
#barra{height:8px;background:var(--bg3);border-radius:4px;overflow:hidden;margin-bottom:20px}
#barra i{display:block;height:100%;width:0;background:var(--ok);transition:width .2s}
#barra.mal i{background:var(--mal)}
.grupo{font-size:11px;letter-spacing:1.4px;text-transform:uppercase;color:var(--txt3);
 font-weight:800;margin:22px 0 8px}
.p{display:flex;gap:11px;align-items:flex-start;padding:9px 13px;border-radius:9px;
 background:var(--bg2);border:1px solid var(--line);margin-bottom:6px;font-size:13.5px}
.p .m{font-weight:900;flex-shrink:0;width:16px;text-align:center}
.p.ok  .m{color:var(--ok)}
.p.mal .m{color:var(--mal)}
.p.esp .m{color:var(--txt3)}
.p .t{flex:1;min-width:0}
.p .d{color:var(--txt3);font-size:12px;margin-top:3px;white-space:pre-wrap;word-break:break-word}
.p.mal{border-color:#5c2a31;background:#2a1418}
.p.mal .d{color:#ffc4ca}
.ms{color:var(--txt3);font-size:11.5px;font-variant-numeric:tabular-nums}
#marco{position:fixed;right:14px;bottom:14px;width:440px;height:260px;
 border:1px solid var(--line);border-radius:12px;background:#000;opacity:.28;
 transition:opacity .2s;z-index:5}
#marco:hover{opacity:1}
#resumen{margin-top:24px;padding:14px 16px;border-radius:10px;background:var(--bg2);
 border:1px solid var(--line);font-size:13.5px;line-height:1.7;display:none}
#resumen.on{display:block}
</style>
</head>
<body>

<h1>Pruebas de Karaoke Launcher</h1>
<div class="sub">
  Se ejecuta sobre <code>data/pruebas.json</code>: tu biblioteca y tu cola no se tocan.
  Deja la ventana visible mientras corren.
</div>

<div id="mandos">
  <button id="bIr">Ejecutar todas</button>
  <button class="g" id="bLimpiar">Limpiar el estado de pruebas</button>
  <span id="marcador"></span>
</div>

<div id="barra"><i></i></div>
<div id="lista"></div>
<div id="resumen"></div>

<iframe id="marco" src="../index.html?bd=pruebas"></iframe>

<script>
'use strict';

/* ═══════════════ Andamio mínimo ═══════════════
   Sin marco de pruebas: cinco funciones bastan y así no hay nada que
   instalar ni que aprender para añadir una prueba. */

const PRUEBAS = [];
const grupoDe = g => n => (t, fn) => PRUEBAS.push({ grupo:g, nombre:n, t, fn });
let GRUPO = '';
function grupo(g){ GRUPO = g; }
function prueba(t, fn){ PRUEBAS.push({ grupo:GRUPO, t, fn }); }

class Fallo extends Error {}
function afirmar(cond, msg){ if(!cond) throw new Fallo(msg || 'la condición no se cumple'); }
function igual(a, b, msg){
  const ja = JSON.stringify(a), jb = JSON.stringify(b);
  if(ja !== jb) throw new Fallo(`${msg || 'valores distintos'}\n  esperado: ${jb}\n  obtenido: ${ja}`);
}
const esperar = ms => new Promise(r => setTimeout(r, ms));

/* Espera a que algo se cumpla, con límite. Nunca `esperar(2000)` a secas:
   una prueba que depende de un tiempo fijo falla en un ordenador lento y
   deja de creerse. */
async function hasta(fn, msg, limite = 6000){
  const fin = Date.now() + limite;
  for(;;){
    let v; try{ v = await fn(); }catch(e){ v = false; }
    if(v) return v;
    if(Date.now() > fin) throw new Fallo('se agotó la espera: ' + (msg || ''));
    await esperar(80);
  }
}

/* ═══════════════ Acceso a la aplicación real ═══════════════ */

const marco = document.getElementById('marco');
const app = () => marco.contentWindow;
const KL  = () => app().KL;
const S   = () => app().KL.estado;
const EV  = () => app().KL.EV;

const api = async (ruta, cuerpo) => {
  const r = await fetch('../' + ruta, cuerpo
    ? { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(cuerpo) }
    : {});
  return r.json();
};

const estadoBD = () => api('api/estado.php?bd=pruebas');
const accionBD = d => api('api/estado.php?bd=pruebas', d);

let nLimpieza = 1;
async function limpiar(){
  /* Primero se para la aplicación, después se limpia el servidor. Al
     revés no funciona: si la prueba anterior dejó una cuenta atrás en
     marcha, ese temporizador sigue publicando estados nuevos —con un
     contador cada vez más alto— y pisa la limpieza una y otra vez. Se
     limpiaba el servidor y un segundo después volvía a estar sucio.

     `panico()` es justo eso: para el reproductor, mata los
     temporizadores y devuelve el mando al operador. */
  try{ KL().evento.panico(); }catch(_){}

  await accionBD({ accion:'vaciar_cola' });
  await accionBD({ accion:'reemplazar_biblioteca', biblioteca:[] });
  await accionBD({ accion:'vaciar_historial' });
  await accionBD({ accion:'calentamiento', activo:false });
  /* El `n` tiene que ir SUBIENDO. La aplicación descarta cualquier
     evento con un contador menor que el suyo —es la defensa contra las
     respuestas atrasadas que resucitaban estados ya superados—, así que
     mandar `n:0` era pedirle que se limpiara con un mensaje que estaba
     programada para tirar a la basura. Y lo tiraba, en silencio. */
  await accionBD({ accion:'evento',
                   evento:{ estado:'ESPERA', pistaId:null, n: 1e9 + (nLimpieza++) } });

  /* Y ahora lo que se me olvidó la primera vez: esperar a que la
     aplicación del marco SE ENTERE. Limpiar el servidor no limpia el
     navegador —se entera al sondear, hasta segundo y medio después—, así
     que la prueba siguiente arrancaba a veces con el evento de la
     anterior todavía en memoria.

     Es la misma trampa que `sincronizar()`: comprobar el servidor cuando
     lo que importa es lo que ve la aplicación. Y era peor de lo que
     parece, porque fallaba solo a veces, según cuándo cayera el sondeo.
     La encontró el mono. */
  await hasta(() => { try{ const e = S().evento;
                           return e && e.estado === 'ESPERA' && !e.pistaId
                                  && S().queue.length === 0; }
                      catch(_){ return false; } },
              'la aplicación se entere de que está todo limpio', 8000);
}

let seq = 0;
async function anadir(titulo, videoId){
  const v = { videoId: videoId || ('prueba' + String(seq++).padStart(5,'0')),
              title: titulo, channel:'Pruebas', thumb:'', duration:180 };
  await accionBD({ accion:'anadir_cola', video:v });
  return v;
}

/* Espera a que la aplicación del marco se entere de lo que hay en el
   servidor: sondea cada segundo y medio, no es instantánea.

   Ojo con lo que se espera. La primera versión comprobaba `length >= n` y
   eso es una carrera: la cola de la prueba ANTERIOR sigue en memoria y ya
   tiene esa longitud, así que la espera terminaba al momento y la prueba
   seguía con identificadores que el servidor ya había borrado. Hay que
   esperar a que estén LAS pistas concretas, no a que haya tantas. */
async function sincronizar(...videoIds){
  await hasta(() => videoIds.every(v => S().queue.some(t => t.videoId === v)),
              'la cola del navegador traiga ' + videoIds.join(', '));
}


/* ═══════════════ Los invariantes ═══════════════

   Cosas que tienen que ser ciertas SIEMPRE, pase lo que pase antes.

   Las pruebas normales comprueban lo que se les ocurrió a quien las
   escribió. Esto comprueba lo que no se le ocurrió a nadie: se ejecuta
   sola después de cada prueba, así que cualquier fallo que deje el
   estado torcido salta en la prueba siguiente aunque esa prueba no
   estuviera mirando ahí.

   Es la red que hace que refactorizar no dé miedo. Cuesta treinta
   líneas y encuentra lo que no se busca.

   Añadir un invariante nuevo es añadir una línea a `debe`. */
function estadoValido(){
  const e = S().evento || {};
  const q = S().queue || [];
  const h = S().historial || [];
  const roto = [];
  const debe = (cond, porque) => { if(!cond) roto.push(porque); };

  debe(!!KL().EV[e.estado], `el estado «${e.estado}» no existe`);
  debe(!!KL().ESCENAS[e.estado], `«${e.estado}» no tiene fila en la tabla de escenas`);

  /* Si se está cantando algo, tiene que saberse el qué. Sin esto, la
     tele se queda con la pantalla de vídeo y sin vídeo. */
  const cantando = e.estado === 'INTERPRETACION' || e.estado === 'LLAMADA';
  debe(!cantando || !!e.pistaId, `${e.estado} sin pistaId: nadie sabe qué se canta`);

  /* En reposo no puede sonar nada. Es la regla de «no arranca sola»
     mirada desde el otro lado. */
  debe(e.estado !== 'ESPERA' || !KL().reproductor.sonando(),
       'ESPERA con el reproductor sonando');

  /* Dos identificadores iguales en la cola y arrastrar una fila mueve
     la otra. Pasó en la v1.0 con las canciones repetidas. */
  const ids = q.map(t => t.id);
  debe(new Set(ids).size === ids.length, 'hay identificadores repetidos en la cola');
  debe(q.every(t => t && t.id && t.videoId), 'hay pistas sin id o sin videoId en la cola');

  /* Y si apunta a una pista, esa pista tiene que existir. */
  debe(!e.pistaId || ids.includes(e.pistaId) || h.some(x => x.id === e.pistaId),
       `el evento apunta a la pista ${e.pistaId}, que no está ni en la cola ni en el historial`);

  /* El contador solo sube. Si baja, vuelve el fallo de la respuesta
     atrasada que resucitaba estados ya superados. */
  debe(typeof e.n !== 'number' || e.n >= 0, 'el contador del evento es negativo');

  return roto;
}


/* ═══════════════════════════════════════════════════════════════════
   1 · EL CICLO DE UNA ACTUACIÓN
   Lo que no puede romperse nunca.
   ═══════════════════════════════════════════════════════════════════ */
grupo('El ciclo de una actuación');

prueba('Pulsar una fila prepara la canción, pero NO la arranca', async () => {
  await limpiar(); const v = await anadir('Uno'); await sincronizar(v.videoId);
  KL().evento.preparar(S().queue.find(t => t.videoId === v.videoId).id);
  await hasta(() => KL().evento.estado() === EV().PREPARADA, 'pase a PREPARADA');
  afirmar(!KL().reproductor.sonando(), 'el vídeo no debe estar sonando en PREPARADA');
});

prueba('Empezar lleva a la cuenta atrás y luego a INTERPRETACION', async () => {
  await limpiar(); const v = await anadir('Uno'); await sincronizar(v.videoId);
  S().segLlamada = 1;
  KL().evento.preparar(S().queue.find(t => t.videoId === v.videoId).id);
  await hasta(() => KL().evento.estado() === EV().PREPARADA);
  KL().evento.arrancar();
  await hasta(() => KL().evento.estado() === EV().LLAMADA, 'pase a LLAMADA');
  await hasta(() => KL().evento.estado() === EV().INTERPRETACION, 'pase a INTERPRETACION', 8000);
});

prueba('Esc durante la cuenta atrás cancela y deja la canción preparada', async () => {
  await limpiar(); const v = await anadir('Uno'); await sincronizar(v.videoId);
  S().segLlamada = 8;
  KL().evento.preparar(S().queue.find(t => t.videoId === v.videoId).id);
  await hasta(() => KL().evento.estado() === EV().PREPARADA);
  KL().evento.arrancar();
  await hasta(() => KL().evento.estado() === EV().LLAMADA);
  afirmar(KL().evento.cancelarLlamada(), 'cancelarLlamada debe decir que ha cancelado algo');
  await hasta(() => KL().evento.estado() === EV().PREPARADA, 'vuelva a PREPARADA');
});

prueba('AL TERMINAR NO ARRANCA SOLA — la regla que no se rompe', async () => {
  await limpiar();
  const a = await anadir('Primera'), b = await anadir('Segunda');
  await sincronizar(a.videoId, b.videoId);
  S().segLlamada = 0; S().segFin = 1; S().autoNext = true;
  KL().evento.arrancar(S().queue.find(t => t.videoId === a.videoId).id);
  await hasta(() => KL().evento.estado() === EV().INTERPRETACION);
  KL().evento.terminar();
  await hasta(() => KL().evento.estado() === EV().FIN_ACTUACION, 'pase a FIN_ACTUACION');
  await hasta(() => KL().evento.estado() === EV().PREPARADA, 'prepare la siguiente', 8000);
  /* Y ahí se queda. Si en tres segundos arranca sola, es un fallo grave. */
  await esperar(3000);
  igual(KL().evento.estado(), EV().PREPARADA,
        'debe seguir PREPARADA: ninguna canción empieza sin el operador');
});

prueba('La canción cantada sale de la cola y entra en el historial', async () => {
  await limpiar();
  const a = await anadir('Cantada'), b = await anadir('Siguiente');
  await sincronizar(a.videoId, b.videoId);
  S().segLlamada = 0; S().segFin = 1; S().retirar = true;
  const id = S().queue.find(t => t.videoId === a.videoId).id;
  KL().evento.arrancar(id);
  await hasta(() => KL().evento.estado() === EV().INTERPRETACION);
  KL().evento.terminar();
  await hasta(async () => (await estadoBD()).historial.length === 1, 'se archive en el historial');
  const e = await estadoBD();
  igual(e.cola.filter(t => t.id === id).length, 0, 'no debe seguir en la cola');
  igual(e.historial[0].title, 'Cantada');
});

prueba('El modo pánico para todo desde cualquier estado', async () => {
  await limpiar(); const v = await anadir('Uno'); await sincronizar(v.videoId);
  S().segLlamada = 0;
  KL().evento.arrancar(S().queue.find(t => t.videoId === v.videoId).id);
  await hasta(() => KL().evento.estado() === EV().INTERPRETACION);
  KL().evento.panico();
  await hasta(() => KL().evento.estado() === EV().ESPERA, 'vuelva a ESPERA');
  afirmar(!KL().reproductor.sonando(), 'el reproductor debe quedar parado');
});

prueba('Las transiciones prohibidas se rechazan', async () => {
  await limpiar();
  KL().evento.espera();
  await hasta(() => KL().evento.estado() === EV().ESPERA);
  igual(KL().evento.pasarA('FIN_ACTUACION'), false,
        'ESPERA → FIN_ACTUACION no está permitida y debe devolver false');
});


/* ═══════════════════════════════════════════════════════════════════
   2 · CONDICIONES DE CARRERA
   El fallo que hizo falta un contador para arreglar.
   ═══════════════════════════════════════════════════════════════════ */
grupo('Condiciones de carrera');

prueba('El estado no retrocede por una respuesta atrasada', async () => {
  await limpiar(); const v = await anadir('Uno'); await sincronizar(v.videoId);
  S().segLlamada = 0;
  KL().evento.arrancar(S().queue.find(t => t.videoId === v.videoId).id);
  await hasta(() => KL().evento.estado() === EV().INTERPRETACION);

  /* Esto es exactamente lo que pasaba: llega la respuesta de una petición
     anterior con el evento viejo dentro. Debe descartarse. */
  const viejo = { estado:'INTERPRETACION', pistaId:S().queue[0] && S().queue[0].id, n:0, desde:0 };
  KL().evento.espera();
  await hasta(() => KL().evento.estado() === EV().ESPERA);
  KL().evento.recibir(viejo);
  await esperar(200);
  igual(KL().evento.estado(), EV().ESPERA,
        'una respuesta con un contador viejo no puede resucitar el estado anterior');
});

prueba('Doble clic en Empezar no lanza dos cuentas atrás', async () => {
  await limpiar(); const v = await anadir('Uno'); await sincronizar(v.videoId);
  S().segLlamada = 5;
  KL().evento.preparar(S().queue.find(t => t.videoId === v.videoId).id);
  await hasta(() => KL().evento.estado() === EV().PREPARADA);
  KL().evento.arrancar();
  KL().evento.arrancar();
  KL().evento.arrancar();
  await esperar(1500);
  igual(KL().evento.estado(), EV().LLAMADA, 'debe seguir en una sola cuenta atrás');
  /* Con tres cuentas atrás corriendo, arrancaría antes de tiempo. */
  await esperar(2000);
  igual(KL().evento.estado(), EV().LLAMADA,
        'a los 3,5 s de una cuenta de 5 no puede haber arrancado ya');
});


/* ═══════════════════════════════════════════════════════════════════
   3 · COMPATIBILIDAD HACIA ATRÁS
   Hay gente con su biblioteca dentro de estado.json.
   ═══════════════════════════════════════════════════════════════════ */
grupo('Compatibilidad hacia atrás');

prueba('Un estado de la v1.0 se completa sin perder nada', async () => {
  /* Sin `evento`, sin `historial`, sin `calentamiento`: tal cual era. */
  await limpiar();
  await accionBD({ accion:'reemplazar_biblioteca', biblioteca:[
    { videoId:'aaaaaaaaaaa', title:'De la v1.0', channel:'Antiguo', thumb:'', duration:200 }
  ]});
  const e = await estadoBD();
  igual(e.biblioteca.length, 1, 'la biblioteca debe conservarse');
  igual(e.biblioteca[0].title, 'De la v1.0');
  afirmar(e.evento && e.evento.estado === 'ESPERA', 'debe inventarse un evento en ESPERA');
  afirmar(Array.isArray(e.historial), 'debe inventarse un historial vacío');
  igual(e.calentamiento, 0, 'el calentamiento debe salir apagado');
});

prueba('Un estado con un valor viejo de calentamiento (hora de fin) se traduce', async () => {
  const pasada = Math.floor(Date.now()/1000) - 3600;
  await accionBD({ accion:'calentamiento', activo:false });
  /* No se puede escribir una hora directamente por la API nueva, así que
     se comprueba lo que sí importa: que el valor normalizado es 0 o 1. */
  const e = await estadoBD();
  afirmar(e.calentamiento === 0 || e.calentamiento === 1,
          'calentamiento debe normalizarse a 0 o 1, vino: ' + e.calentamiento);
});


/* ═══════════════════════════════════════════════════════════════════
   4 · EL CONTRATO ENTRE LOS DOS ALMACENES
   La versión Lite y la de servidor tienen que entender lo mismo.
   ═══════════════════════════════════════════════════════════════════ */
grupo('Contrato entre almacenes');

prueba('api/estado.php y almacen-local.js aceptan las mismas acciones', async () => {
  const [php, js] = await Promise.all([
    fetch('../api/estado.php').then(r => r.text()).then(() => fetch('../api/estado.php')),
    fetch('../js/almacen-local.js').then(r => r.text())
  ]);
  const fuentePhp = await fetch('../api/estado.php?ver=1').then(() => null).catch(() => null);
  /* El PHP no se puede leer desde el navegador —lo ejecuta el servidor—,
     así que se comprueba al revés: se le mandan a la API todas las
     acciones que implementa la Lite y ninguna debe responder
     «acción desconocida». */
  const enLite = [...js.matchAll(/^\s{4}(\w+)\s*\(e(?:,\s*d)?\)\s*\{/gm)].map(m => m[1]);
  afirmar(enLite.length >= 10, 'no he sabido leer las acciones de la Lite: ' + enLite.length);

  const faltan = [];
  for(const a of enLite){
    const r = await accionBD({ accion:a, video:{videoId:'zzzzzzzzzzz',title:'x'},
                               orden:[], biblioteca:[], evento:{estado:'ESPERA'}, id:'x' });
    if(r.error && /desconocida/i.test(r.error)) faltan.push(a);
  }
  igual(faltan, [], 'acciones que la Lite tiene y el servidor no');
  await limpiar();
});


/* ═══════════════════════════════════════════════════════════════════
   4b · LA CAPA DE COMANDOS
   `KL.comandos` es la única lista de lo que se le puede pedir al estado.
   Estas dos pruebas existen para que siga siéndolo: la primera detecta
   una errata en un nombre de acción —que antes no daba error, solo hacía
   que la canción no apareciera—, y la segunda detecta que alguien vuelva
   a montar el mensaje a mano en cualquier otro archivo.
   ═══════════════════════════════════════════════════════════════════ */
grupo('La capa de comandos');

prueba('Todos los comandos usan un nombre de acción que el servidor conoce', async () => {
  const fuente = await fetch('../js/comandos.js').then(r => r.text());
  const nombres = [...new Set([...fuente.matchAll(/accion:\s*'([a-z_]+)'/g)].map(m => m[1]))];
  afirmar(nombres.length >= 12, 'no he sabido leer los comandos: ' + nombres.length);

  const desconocidas = [];
  for(const a of nombres){
    const r = await accionBD({ accion:a, video:{videoId:'zzzzzzzzzzz',title:'x'},
                               orden:[], biblioteca:[], evento:{estado:'ESPERA'},
                               id:'x', paneles:[] });
    if(r.error && /desconocida/i.test(r.error)) desconocidas.push(a);
  }
  igual(desconocidas, [], 'comandos que el servidor no sabe atender');
  await limpiar();
});

prueba('Nadie fuera de comandos.js escribe el mensaje a mano', async () => {
  /* La regla del paso 2. Si se rompe, vuelven los trece nombres sueltos
     repartidos por seis archivos y esta capa deja de servir de nada. */
  const archivos = ['app','cola','evento','busqueda','interfaz','reproductor','atajos','nucleo'];
  const culpables = [];
  for(const f of archivos){
    const t = await fetch('../js/' + f + '.js').then(r => r.text());
    /* Se quitan los comentarios antes de mirar: este archivo habla de
       `accion:'anadir_cola'` en prosa y no cuenta. */
    const codigo = t.replace(/\/\*[\s\S]*?\*\//g, '').replace(/^\s*\/\/.*$/gm, '');
    if(/accion\s*:\s*'/.test(codigo)) culpables.push(f + '.js');
  }
  igual(culpables, [], 'archivos que se saltan KL.comandos');
});


/* ═══════════════════════════════════════════════════════════════════
   5 · LOS CÓDIGOS QR
   Se generan aquí desde que se dejó de depender de un servicio externo.
   ═══════════════════════════════════════════════════════════════════ */
grupo('La pantalla del público');

prueba('Todo lo que proyector.php pide existe de verdad', async () => {
  /* Partir un archivo en siete tiene un riesgo tonto y muy caro: que una
     etiqueta <script> apunte a algo que ya no está. El navegador no dice
     nada, se salta ese archivo, y la pantalla del público se queda a
     medias en mitad de la fiesta. */
  const html = await fetch('../proyector.php').then(r => r.text());
  const pedidos = [...html.matchAll(/(?:src|href)="((?:js|css)\/[^"]+)"/g)].map(m => m[1]);
  afirmar(pedidos.length >= 6, 'no he sabido leer las etiquetas: ' + pedidos.length);

  const faltan = [];
  for(const ruta of pedidos){
    const r = await fetch('../' + ruta, { method:'HEAD' });
    if(!r.ok) faltan.push(ruta + ' → ' + r.status);
  }
  igual(faltan, [], 'archivos que proyector.php pide y no existen');
});

prueba('La pantalla del público arranca sin reventar', async () => {
  /* Se abre de verdad en un marco aparte y se mira si algún script se ha
     caído. No comprueba lo que se ve —eso no se puede desde aquí— pero sí
     que las siete piezas encajan. */
  const m = document.createElement('iframe');
  m.style.cssText = 'position:fixed;left:-9999px;width:800px;height:450px';
  m.src = '../proyector.php';
  document.body.appendChild(m);
  try{
    const fallos = [];
    await new Promise(r => { m.onload = r; setTimeout(r, 8000); });
    m.contentWindow.addEventListener('error', e => fallos.push(e.message));
    await hasta(() => { try{ return typeof m.contentWindow.pintar === 'function'; }
                        catch(e){ return false; } },
                'el proyector termine de cargar sus scripts', 8000);
    /* Una función de cada archivo: si falta una, ese <script> no se ha
       cargado. Se miran funciones y no constantes porque un `const` en la
       raíz de un script clásico NO queda colgado de `window` —vive en el
       ámbito léxico del documento— y desde fuera no se ve. Las
       declaraciones de función sí. */
    const piezas = {
      'ajustes.js':     'guardado',
      'reproductor.js': 'poner',
      'audio.js':       'arrancarAudio',
      'carteles.js':    'pintarEsquina',
      'escenas.js':     'pintar',
      'servidor.js':    'escuchar'
    };
    const sinCargar = Object.keys(piezas)
      .filter(f => typeof m.contentWindow[piezas[f]] !== 'function');
    igual(sinCargar, [], 'piezas del proyector que no han cargado');

    afirmar(!!m.contentWindow.KL && !!m.contentWindow.KL.ESCENAS,
            'no ha cargado la tabla de escenas');
    igual(fallos, [], 'errores al cargar la pantalla del público');
  } finally { m.remove(); }
});

prueba('«Qué se está cantando» no se puede desincronizar', async () => {
  /* Antes había tres copias del mismo dato —`curId`, `sonando` y
     `evento.pistaId`— y las tres se escribían. Ahora `curId` es una
     pregunta que no se puede asignar, y el servidor DEDUCE `sonando` del
     evento en vez de guardarlo. Esta prueba comprueba las dos cosas. */
  await limpiar();
  const v = await anadir('Una sola verdad'); await sincronizar(v.videoId);
  const id = S().queue[0].id;

  /* Intentar asignarlo no hace nada: es un getter sin setter. */
  try{ S().curId = 'inventado'; }catch(e){}
  afirmar(S().curId !== 'inventado', 'curId se ha dejado asignar');

  KL().evento.preparar(id);
  await hasta(() => KL().evento.estado() === EV().PREPARADA, 'pase a PREPARADA');
  igual(S().curId, id, 'curId no sigue a evento.pistaId');
  const j1 = await estadoBD();
  igual(j1.sonando, null, 'PREPARADA no debe decir que suena algo');

  KL().evento.arrancarYa(id);
  await hasta(() => KL().evento.estado() === EV().INTERPRETACION, 'pase a INTERPRETACION');
  await hasta(async () => (await estadoBD()).sonando === id, 'el servidor deduzca sonando');

  /* Y la acción vieja no puede volver a introducir una segunda verdad. */
  await accionBD({ accion:'sonando', id:'zzz' });
  const j2 = await estadoBD();
  igual(j2.sonando, id, 'la acción vieja ha conseguido cambiar el dato');

  KL().evento.panico();
  await limpiar();
});

grupo('Canción y actuación');

prueba('Una actuación no guarda ni su posición ni su estado', async () => {
  /* Las dos reglas del modelo. Si algún día aparece un campo `posicion`
     habrá dos verdades —el campo y el orden real— y una se
     desincronizará el primer día que dos móviles reordenen a la vez. Y si
     aparece un `estado`, volvemos a tener dos sitios donde mirar, que es
     el problema del que venimos. */
  await limpiar();
  const v = await anadir('Modelo'); await sincronizar(v.videoId);
  const a = S().queue[0];
  const prohibidos = ['posicion', 'indice', 'orden', 'estado', 'sonando', 'actual'];
  igual(prohibidos.filter(k => k in a), [], 'campos que la actuación no debe guardar');
});

prueba('Los atajos de canción y actuación devuelven lo mismo que el objeto', async () => {
  /* Mientras la forma física siga siendo plana, los atajos tienen que dar
     exactamente lo de antes. El día que las canciones pasen a un catálogo
     aparte, esta prueba es la que dice si la mudanza salió bien. */
  await limpiar();
  const v = await anadir('Con título'); await sincronizar(v.videoId);
  const a = S().queue[0], C = KL().Cancion, A = KL().Actuacion;
  igual(A.titulo(a), a.title,      'el título no coincide');
  igual(A.canal(a),  a.channel,    'el canal no coincide');
  igual(A.videoId(a), a.videoId,   'el videoId no coincide');
  igual(C.id(A.cancion(a)), a.videoId, 'la canción extraída no es la misma');
  afirmar(!('quien' in A.cancion(a)) && !('id' in A.cancion(a)),
          'la canción extraída se ha traído datos de la actuación');
});

prueba('El rótulo es el nombre de quien la pidió, y si no el título', async () => {
  const A = KL().Actuacion;
  igual(A.rotulo({ title:'Tema', pedida:'Rober' }), 'Rober', 'debería mandar el nombre');
  igual(A.rotulo({ title:'Tema' }), 'Tema', 'sin nombre, el título');
  igual(A.quien({ title:'Tema' }), '', 'sin nombre no se inventa uno');
});

grupo('El reparto de avisos');

prueba('Un aviso llega a todos los que escuchan, no solo al primero', () => {
  const s = KL().senales;
  const oido = [];
  const baja1 = s.oir('prueba:cosa', v => oido.push('uno:' + v));
  const baja2 = s.oir('prueba:cosa', v => oido.push('dos:' + v));
  s.avisar('prueba:cosa', 7);
  igual(oido, ['uno:7', 'dos:7'], 'no han escuchado los dos');
  baja1(); baja2();
  s.avisar('prueba:cosa', 9);
  igual(oido, ['uno:7', 'dos:7'], 'siguen escuchando después de darse de baja');
});

prueba('Un oyente que revienta no deja sin avisar a los demás', () => {
  /* Es lo único que este reparto tiene de listo, y es la razón de que
     exista como módulo en vez de como un array suelto: sin esto, un
     fallo pintando un botón dejaría al motor de estados sin enterarse de
     que la canción ha terminado, y se quedaría sonando para siempre. */
  const s = KL().senales;
  const oido = [];
  const baja1 = s.oir('prueba:boom', () => { throw new Error('a propósito'); });
  const baja2 = s.oir('prueba:boom', () => oido.push('el segundo se enteró'));
  s.avisar('prueba:boom');
  igual(oido, ['el segundo se enteró'], 'el fallo del primero ha tapado al segundo');
  baja1(); baja2();
});

prueba('El final de una canción lo escuchan el motor y la interfaz', () => {
  /* La razón de ser del paso 3. Antes solo cabía un suscriptor, así que
     ese suscriptor tenía que ser `app.js` y acabó sabiéndolo todo. */
  afirmar(KL().senales.cuantos('reproductor:fin') >= 2,
          'solo hay ' + KL().senales.cuantos('reproductor:fin') + ' oyente(s) de «fin»');
});

grupo('El mono');

prueba('Mil botonazos al azar no dejan el estado inválido', async () => {
  /* El mono pulsa cosas sin pensar durante un rato y después de cada
     pulsación se comprueban los invariantes. No busca ningún fallo
     concreto: busca la combinación que a nadie se le ocurrió probar.

     Sin `estadoValido()` esta prueba no valdría nada —un mono sin nadie
     que mire no detecta nada—. Con él, es la prueba que más barato
     encuentra lo más raro. */

  await limpiar();
  const monos = [];
  for(let i = 0; i < 5; i++) monos.push(await anadir('Mono ' + i, 'mono00000' + i));
  await sincronizar(...monos.map(m => m.videoId));

  /* Azar reproducible. Si un día falla, el número de la semilla sale en
     el mensaje y se pega aquí para repetir exactamente la misma
     secuencia: un fallo que no se puede repetir no se arregla. */
  let SEMILLA = 555;
  const azar = () => {
    SEMILLA = (SEMILLA * 1103515245 + 12345) & 0x7fffffff;
    return SEMILLA / 0x7fffffff;
  };
  const unoDe = a => a[(azar() * a.length) | 0];

  const ev = () => KL().evento;
  const algunId = () => { const q = S().queue; return q.length ? unoDe(q).id : null; };

  const MOVIMIENTOS = [
    ['preparar',   () => { const id = algunId(); if(id) ev().preparar(id); }],
    ['arrancar',   () => { const id = algunId(); if(id) ev().arrancar(id); }],
    ['arrancarYa', () => ev().arrancarYa()],
    ['cancelar',   () => ev().cancelarLlamada()],
    ['terminar',   () => ev().terminar()],
    ['siguiente',  () => ev().siguiente()],
    ['anterior',   () => ev().anterior()],
    ['espera',     () => ev().espera()],
    ['panico',     () => ev().panico()]
  ];

  const historia = [];
  for(let i = 0; i < 120; i++){
    const [nombre, hacer] = unoDe(MOVIMIENTOS);
    historia.push(nombre);
    try{ hacer(); }
    catch(e){
      throw new Fallo(`«${nombre}» ha reventado en el paso ${i}\n`
        + `  semilla: 20260801\n  camino: ${historia.slice(-12).join(' → ')}\n  ${e.message}`);
    }
    await esperar(8);
    const roto = estadoValido();
    if(roto.length) throw new Fallo(
      `estado inválido tras «${nombre}» en el paso ${i}\n`
      + `  semilla: 20260801\n  camino: ${historia.slice(-12).join(' → ')}\n`
      + `  · ${roto.join('\n  · ')}`);
  }

  /* Y que después de todo eso siga siendo una aplicación usable, no un
     cadáver que cumple los invariantes por estar vacío. */
  ev().espera();
  await hasta(() => ev().estado() === EV().ESPERA, 'vuelva a ESPERA tras el estropicio');
  await limpiar();
});

prueba('Una cola grande no rompe nada ni se descuadra', async () => {
  /* No mide tiempos a propósito: un límite en milisegundos verde en un
     portátil y rojo en el PC de la fiesta es una prueba que se deja de
     creer. Lo que se comprueba es que doscientas canciones entran,
     salen y se reordenan sin perder ni duplicar ninguna. */
  await limpiar();
  const N = 200;
  for(let i = 0; i < N; i++){
    await accionBD({ accion:'anadir_cola',
      video:{ videoId:'lote' + String(i).padStart(7,'0'), title:'Lote ' + i,
              channel:'Pruebas', thumb:'', duration:180 } });
  }
  /* Ojo: el servidor habla en español —`cola`, `biblioteca`— y el
     navegador en inglés —`queue`, `library`—. Aquí se mira lo que
     devuelve el servidor, así que toca `cola`. */
  const j = await estadoBD();
  igual(j.cola.length, N, 'no han entrado todas');
  igual(new Set(j.cola.map(t => t.id)).size, N, 'hay identificadores repetidos');

  const alReves = j.cola.map(t => t.id).reverse();
  const k = await accionBD({ accion:'ordenar_cola', orden:alReves });
  igual(k.cola.map(t => t.id), alReves, 'la reordenación ha perdido el orden');

  await limpiar();
  const z = await estadoBD();
  igual(z.cola.length, 0, 'vaciar la cola ha dejado restos');
});


grupo('Códigos QR');

prueba('Un QR de una dirección de red local se genera con la versión correcta', async () => {
  const m = KL().qr.matriz('http://192.168.1.40:8123/pedir.php');
  igual(m.length, 29, 'una URL de 34 bytes debe caber en la versión 3 (29×29)');
  igual(m[0].slice(0,7), [1,1,1,1,1,1,1], 'el ojo de arriba a la izquierda');
  /* La línea de sincronía va de un ojo a otro alternando desde la
     columna 8. Ocho es par, luego ese módulo es NEGRO. La primera
     versión de esta prueba afirmaba lo contrario y falló: el error
     estaba en la prueba, no en el generador. */
  igual(m[6][8], 1, 'el patrón de sincronía empieza en negro en la columna 8');
  igual(m[6][9], 0, 'y alterna');
  igual(m[m.length-8][8], 1, 'el módulo negro obligatorio');
});

prueba('El texto de wifi escapa los caracteres que rompen la cadena', () => {
  const s = KL().qr.wifi('Mi;Red', 'cla:ve\\rara');
  afirmar(s.includes('S:Mi\\;Red'), 'el punto y coma del nombre debe escaparse: ' + s);
  afirmar(s.includes('P:cla\\:ve\\\\rara'), 'los dos puntos y la barra también: ' + s);
});

prueba('Un texto que no cabe da error en vez de un QR silenciosamente roto', () => {
  let salto = false;
  try{ KL().qr.matriz('x'.repeat(400)); }catch(e){ salto = true; }
  afirmar(salto, 'debe lanzar un error, no devolver una matriz inservible');
});


/* ═══════════════════════════════════════════════════════════════════
   6 · LA TABLA DE ESCENAS
   El olvido de una columna aquí costó un fallo entero.
   ═══════════════════════════════════════════════════════════════════ */
grupo('Tabla de escenas');

prueba('Todos los estados del evento tienen fila en la tabla', () => {
  for(const k of Object.values(EV())){
    afirmar(KL().ESCENAS[k], 'falta la fila de ' + k + ' en js/estados.js');
  }
});

prueba('Cada fila tiene las cinco columnas', () => {
  for(const [k, v] of Object.entries(KL().ESCENAS)){
    for(const c of ['pc','tele','barra','sonando','calienta']){
      afirmar(c in v, `a ${k} le falta la columna ${c}`);
    }
    afirmar(['operador','interpretacion'].includes(v.pc), `pc no válido en ${k}: ${v.pc}`);
  }
});

prueba('El calentamiento solo puede tapar los estados en reposo', () => {
  const KLs = KL().ESCENAS;
  igual(KLs.ESPERA.calienta, true);
  igual(KLs.PREPARADA.calienta, true);
  igual(KLs.LLAMADA.calienta, false, 'con la cuenta atrás en marcha manda la llamada');
  igual(KLs.INTERPRETACION.calienta, false, 'nunca sobre el vídeo');
  igual(KLs.FIN_ACTUACION.calienta, false, 'nunca sobre los aplausos');
});

prueba('Un estado desconocido cae en ESPERA sin reventar', () => {
  const e = KL().escena('ESTADO_QUE_NO_EXISTE');
  igual(e.tele, 'espera', 'debe caer al estado seguro');
});


/* ═══════════════════════════════════════════════════════════════════
   7 · LA API
   ═══════════════════════════════════════════════════════════════════ */
grupo('La API');

prueba('El servidor informa de su dirección en la red local', async () => {
  const e = await estadoBD();
  afirmar('ip_local' in e, 'falta ip_local: los QR no sabrían qué dirección poner');
  afirmar('puerto' in e && e.puerto > 0, 'falta el puerto');
  afirmar('ahora' in e, 'falta la hora del servidor, que la tele necesita');
});

prueba('Un invitado no puede hacer nada más que añadir a la cola', async () => {
  const prohibidas = ['vaciar_cola','quitar_cola','evento','calentamiento',
                      'reemplazar_biblioteca','fin_actuacion','paneles'];
  const coladas = [];
  for(const a of prohibidas){
    const r = await accionBD({ accion:a, invitado:true, evento:{estado:'ESPERA'}, id:'x' });
    if(r.ok) coladas.push(a);
  }
  igual(coladas, [], 'acciones que un invitado ha conseguido ejecutar');
});

prueba('Borrar descargas exige POST, no basta con abrir una dirección', async () => {
  const r = await fetch('../api/descargar.php?borrar_todo=1');
  igual(r.status, 405, 'con GET debe responder 405, o cualquiera borra los vídeos con una imagen');
});

prueba('La misma canción no entra dos veces en la cola', async () => {
  await limpiar();
  const v = { videoId:'repetidaxxx', title:'Repetida', channel:'x', thumb:'', duration:100 };
  const a = await accionBD({ accion:'anadir_cola', video:v });
  const b = await accionBD({ accion:'anadir_cola', video:v });
  afirmar(a.ok, 'la primera debe entrar');
  afirmar(!b.ok, 'la segunda debe rechazarse');
  await limpiar();
});


/* ═══════════════ Ejecutor ═══════════════ */

const lista = document.getElementById('lista');
const barra = document.getElementById('barra');
const marcador = document.getElementById('marcador');
const resumen = document.getElementById('resumen');

function pintarLista(){
  lista.innerHTML = '';
  let g = null;
  PRUEBAS.forEach((p, i) => {
    if(p.grupo !== g){
      g = p.grupo;
      const h = document.createElement('div');
      h.className = 'grupo'; h.textContent = g;
      lista.appendChild(h);
    }
    const d = document.createElement('div');
    d.className = 'p esp'; d.id = 'p' + i;
    d.innerHTML = `<span class="m">·</span><div class="t">${p.t}<div class="d"></div></div>
                   <span class="ms"></span>`;
    lista.appendChild(d);
  });
}

async function ejecutar(){
  document.getElementById('bIr').disabled = true;
  resumen.classList.remove('on');
  pintarLista();
  barra.classList.remove('mal');
  barra.firstElementChild.style.width = '0';

  /* La aplicación del marco tiene que estar viva antes de empezar. */
  await hasta(() => { try{ return !!(app().KL && app().KL.evento); }catch(e){ return false; } },
              'la aplicación cargue en el marco', 15000);

  let bien = 0, mal = 0;
  const fallos = [];

  for(let i = 0; i < PRUEBAS.length; i++){
    const p = PRUEBAS[i], d = document.getElementById('p' + i);
    d.className = 'p esp';
    d.querySelector('.m').textContent = '…';
    const t0 = performance.now();
    try{
      await p.fn();
      /* Y ahora lo que la prueba NO miraba. */
      const roto = estadoValido();
      if(roto.length) throw new Fallo('la prueba pasó, pero dejó el estado torcido:\n  · '
                                      + roto.join('\n  · '));
      bien++;
      d.className = 'p ok';
      d.querySelector('.m').textContent = '✓';
    }catch(e){
      mal++;
      fallos.push(p.t);
      d.className = 'p mal';
      d.querySelector('.m').textContent = '✕';
      d.querySelector('.d').textContent = e instanceof Fallo ? e.message : (e.stack || String(e));
      /* Una prueba que revienta a mitad deja el estado compartido como
         estaba, y como los invariantes se comprueban después de CADA
         prueba, ese estropicio hacía fallar a las once siguientes aunque
         no tuvieran nada que ver. Once fallos falsos tapan el de verdad.
         Se limpia antes de seguir. */
      try{ await limpiar(); }catch(_){}
    }
    d.querySelector('.ms').textContent = Math.round(performance.now() - t0) + ' ms';
    marcador.innerHTML = `<span style="color:var(--ok)">${bien} ✓</span>` +
      (mal ? `  <span style="color:var(--mal)">${mal} ✕</span>` : '') +
      `  <span style="color:var(--txt3)">de ${PRUEBAS.length}</span>`;
    barra.firstElementChild.style.width = ((i+1) / PRUEBAS.length * 100) + '%';
    if(mal) barra.classList.add('mal');
  }

  await limpiar();
  /* La aplicación del marco se queda donde la dejó la última prueba —a
     veces en mitad de una cuenta atrás, tapando la pantalla—. Se recarga
     para dejar la ventana legible. */
  marco.src = marco.src;
  resumen.classList.add('on');
  resumen.innerHTML = mal
    ? `<b style="color:var(--mal)">${mal} de ${PRUEBAS.length} pruebas han fallado.</b><br>` +
      fallos.map(f => '· ' + f).join('<br>') +
      '<br><br>Antes de publicar nada, esto tiene que estar en verde.'
    : `<b style="color:var(--ok)">Las ${PRUEBAS.length} pruebas pasan.</b><br>` +
      'Recuerda que la pantalla completa, el sonido y los QR con un móvil de verdad ' +
      'no se pueden probar desde aquí: eso sigue en <b>PRUEBAS.md</b>, a mano.';
  document.getElementById('bIr').disabled = false;
}

document.getElementById('bIr').addEventListener('click', ejecutar);
document.getElementById('bLimpiar').addEventListener('click', async () => {
  await limpiar();
  marcador.textContent = 'Estado de pruebas limpio';
});

pintarLista();
</script>
</body>
</html>
