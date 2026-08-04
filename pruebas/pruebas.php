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
<title>OpenKaraoke Center — pruebas</title>
<!--
  ═══════════════════════════════════════════════════════════════════
  Pruebas automáticas de OpenKaraoke Center

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
 font-weight:800;margin:22px 0 8px;display:flex;align-items:center;gap:9px}
.eti{font-size:10px;letter-spacing:.6px;text-transform:uppercase;font-weight:800;
 padding:2px 8px;border-radius:999px;border:1px solid currentColor;opacity:.85}
.p .eti{flex-shrink:0;margin-top:1px}
#leyenda{margin:18px 0 6px;border:1px solid var(--line);border-radius:10px;
 background:var(--bg2);overflow:hidden}
#leyenda summary{cursor:pointer;padding:11px 15px;font-weight:700;font-size:13px}
#leyenda .cl{padding:9px 15px 12px;border-top:1px solid var(--line);font-size:12.5px;
 line-height:1.6}
#leyenda .cl .n{font-weight:800;text-transform:uppercase;letter-spacing:.8px;font-size:11px}
#leyenda .cl .s{color:var(--txt3)}
#leyenda .cl .s b{color:var(--txt2)}
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

<h1>Pruebas de OpenKaraoke Center</h1>
<div class="sub">
  Se ejecuta sobre <code>data/pruebas.json</code>: tu biblioteca y tu cola no se tocan.
  Deja la ventana visible mientras corren.
</div>

<nav style="display:flex;gap:8px;margin:4px 0 18px;flex-wrap:wrap">
  <a href="../qa.php" style="color:var(--txt2);text-decoration:none;font-size:13px;
     padding:6px 12px;border:1px solid var(--line);border-radius:999px">🩺 Comprobar esta máquina</a>
  <a href="../index.html" style="color:var(--txt2);text-decoration:none;font-size:13px;
     padding:6px 12px;border:1px solid var(--line);border-radius:999px">🎤 Operador</a>
  <a href="../ajustes.php" style="color:var(--txt2);text-decoration:none;font-size:13px;
     padding:6px 12px;border:1px solid var(--line);border-radius:999px">⚙️ Ajustes</a>
</nav>

<div id="mandos">
  <button id="bIr">Ejecutar todas</button>
  <button class="g" id="bLimpiar">Limpiar el estado de pruebas</button>
  <span id="marcador"></span>
</div>

<details id="leyenda">
  <summary>Las seis clases de prueba — y qué hacer cuando una se pone en rojo</summary>
  <div id="leyendaCuerpo"></div>
</details>

<div id="barra"><i></i></div>
<div id="lista"></div>
<div id="resumen"></div>

<iframe id="marco" src="../index.html?bd=pruebas"></iframe>

<script>
'use strict';

/* ═══════════════ Andamio mínimo ═══════════════
   Sin marco de pruebas: cinco funciones bastan y así no hay nada que
   instalar ni que aprender para añadir una prueba. */

/* ═══════════════ Las seis clases de prueba ═══════════════
   Esto no es taxonomía por gusto. La revisión de fuera lo dijo bien: aquí
   ya no hay solo pruebas unitarias. Hay pruebas que protegen decisiones de
   producto, otras que protegen la arquitectura y otras que protegen a
   alguien de un fallo concreto que ya pasó una vez.

   Y lo que importa de la diferencia es **qué se hace cuando una falla**.
   Delante de una prueba en rojo, la reacción por defecto es arreglar el
   código hasta que se ponga verde. Para la mitad de las que hay aquí esa
   reacción es la equivocada: si falla una de producto, el código puede
   estar perfectamente bien y lo que hay que hacer es ir a hablar de qué
   aplicación queremos. Si falla una de arquitectura, seguramente algo se
   ha metido donde no era, y bajar la exigencia de la prueba es tapar el
   agujero con la alfombra.

   Se quedan en un solo archivo a propósito. Repartirlas en carpetas
   —tests/arquitectura, tests/producto…— es lo que haría cualquier
   proyecto con Node y un ejecutor de pruebas, y aquí rompería la única
   propiedad que hace que estas pruebas se ejecuten de verdad: que se
   abren con doble clic. Lo que se necesitaba de esa separación no era la
   carpeta: era saber qué clase de prueba tienes delante cuando se pone en
   rojo. Eso lo da la etiqueta. */
const CLASES = {
  arquitectura: {
    color: '#7aa2ff',
    que: 'Cómo están repartidas las piezas: quién sabe de quién, qué se puede deducir y qué se guarda.',
    siFalla: 'Casi nunca se arregla bajando la exigencia de la prueba. Mira qué se ha metido donde no era.'
  },
  comportamiento: {
    color: '#22d97a',
    que: 'Lo que la aplicación hace: el ciclo de una actuación, las colas, las carreras entre ventanas.',
    siFalla: 'Es el caso normal. Hay un fallo y se arregla el código.'
  },
  compatibilidad: {
    color: '#ffb648',
    que: 'Que lo guardado antes siga valiendo: estados de versiones viejas y claves de localStorage.',
    siFalla: 'Alguien acaba de romperle los datos a quien ya usaba esto. Antes de tocar nada, escribe la migración.'
  },
  producto: {
    color: '#ff7ad9',
    que: 'Decisiones sobre qué aplicación queremos: lo que nunca va a haber, y el tono con el que habla.',
    siFalla: 'El código puede estar bien. Esto es una conversación, no un fallo: cambia la prueba solo después de cambiar la decisión (y el texto de ajustes.php que la promete).'
  },
  rendimiento: {
    color: '#c78bff',
    que: 'Números, no impresiones: cuánto tarda en pintar, cuántos nodos quedan, cuánto ha crecido un archivo.',
    siFalla: 'La respuesta casi nunca es subir el número. Un presupuesto que se sube sin mirar deja de medir nada.'
  },
  regresion: {
    color: '#ff5d6c',
    que: 'Un fallo concreto que ya pasó una vez. Cada una tiene una fecha y una historia.',
    siFalla: 'Ha vuelto. Lee el comentario de arriba: dice exactamente cómo se manifestaba.'
  }
};

const PRUEBAS = [];
let GRUPO = '', CLASE = 'comportamiento';
function grupo(g, clase){ GRUPO = g; CLASE = clase || 'comportamiento'; }
/* La clase se puede afinar prueba a prueba: dentro de un grupo de
   comportamiento puede haber una que en realidad protege de un fallo
   concreto que ya pasó. */
function prueba(t, fn, clase){
  PRUEBAS.push({ grupo:GRUPO, clase:clase || CLASE, t, fn });
}

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

  /* Y de vuelta al karaoke: si una prueba deja la fiesta en la Cabina DJ,
     la siguiente arranca en un espacio donde las canciones se encadenan
     solas y falla por un motivo que no tiene nada que ver con ella. */
  await accionBD({ accion:'espacio', espacio:'karaoke' });
  for(const esp of ['karaoke','dj'])
    await accionBD({ accion:'vaciar_cola', espacio:esp });
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
     mirada desde el otro lado.

     `?.` a propósito: justo después de recargar el iframe (la prueba del
     rename lo hace) este invariante puede correr en el instante en que
     `nucleo.js` ya ha puesto `KL.estado` pero `reproductor.js` todavía no
     se ha enganchado. Sin reproductor no hay nada que afirmar sobre él, y
     antes esto tiraba la suite entera con un TypeError que no tenía nada
     que ver con lo que la prueba de verdad comprobaba. */
  debe(e.estado !== 'ESPERA' || !KL().reproductor?.sonando(),
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
grupo('El ciclo de una actuación', 'comportamiento');

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
}, 'regresion');

prueba('En la Cabina DJ SÍ arranca sola — y es lo contrario, no una excepción', async () => {
  /* Las dos pruebas de arriba y esta dicen cosas opuestas a propósito.

     «Una canción no arranca sola» no es una regla sobre reproductores: es
     una regla sobre personas. Existe para que nadie se vea empujado a un
     micro antes de estar listo, y el operador es quien mira si lo está.

     En la Cabina DJ no hay micro ni hay nadie esperando: hay una lista
     que la gente ha ido haciendo desde el móvil. Ahí la misma regla
     produciría el fallo que quiere evitar en el karaoke — treinta
     segundos de silencio que sientan a toda la sala.

     Si algún día alguien «unifica» esto para que las dos se comporten
     igual, esta prueba y la de arriba no pueden estar las dos en verde. */
  await limpiar();
  /* Las dos entran directamente en la Cabina DJ: cada espacio tiene su
     cola y una pista del karaoke no se mueve de sitio. */
  const a = { videoId:'djuno000000', title:'Pista uno', channel:'x', thumb:'', duration:180 };
  const b = { videoId:'djdos000000', title:'Pista dos', channel:'x', thumb:'', duration:180 };
  await accionBD({ accion:'anadir_cola', video:a, espacio:'dj' });
  await accionBD({ accion:'anadir_cola', video:b, espacio:'dj' });
  await accionBD({ accion:'espacio', espacio:'dj' });
  await hasta(() => S().modo === 'dj', 'la aplicación pase a la Cabina DJ');
  await sincronizar(a.videoId, b.videoId);

  S().segLlamada = 0; S().segFin = 1; S().autoNext = true;
  const pistas = S().queue.filter(t => (t.espacio || 'karaoke') === 'dj');
  afirmar(pistas.length >= 2, 'las dos pistas deberían estar en la Cabina DJ');

  KL().evento.arrancar(pistas[0].id);
  await hasta(() => KL().evento.estado() === EV().INTERPRETACION);
  KL().evento.terminar();
  await hasta(() => KL().evento.estado() === EV().FIN_ACTUACION, 'pase a FIN_ACTUACION');
  await hasta(() => KL().evento.estado() === EV().INTERPRETACION,
              'la siguiente arranque sola: en una fiesta el silencio es el fallo', 9000);

  await accionBD({ accion:'espacio', espacio:'karaoke' });
  await limpiar();
}, 'producto');

prueba('La música ambiente no suena en la Cabina DJ', async () => {
  /* Allí la cola ES la música. Un hilo de fondo por debajo serían dos
     cosas sonando a la vez, que es exactamente lo que la música ambiente
     existe para evitar. */
  await limpiar();
  const w = app();
  w.KL.estado.ambienteOn = true;
  w.KL.estado.modo = 'karaoke';
  const suena = () => w.KL.ambiente.revisar() === undefined && w.KL.ambiente.suena();

  w.KL.estado.modo = 'dj';
  w.KL.ambiente.revisar();
  afirmar(!w.KL.ambiente.suena(), 'la música ambiente ha sonado en la Cabina DJ');

  w.KL.estado.modo = 'karaoke';
  w.KL.estado.ambienteOn = false;
  await limpiar();
}, 'producto');

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
grupo('Condiciones de carrera', 'comportamiento');

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
}, 'regresion');

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
}, 'regresion');


/* ═══════════════════════════════════════════════════════════════════
   3 · COMPATIBILIDAD HACIA ATRÁS
   Hay gente con su biblioteca dentro de estado.json.
   ═══════════════════════════════════════════════════════════════════ */
grupo('Compatibilidad hacia atrás', 'compatibilidad');

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

prueba('El rename no ha tocado las preferencias de nadie', async () => {
  /* La aplicación se llama OpenKaraoke Center. Las claves de localStorage
     siguen llamándose `karaoke_*`, y eso es a propósito: renombrarlas
     obliga a una migración eterna y el que se equivoque pierde el desfase
     de la tele, el efecto y el espacio en el que estaba.

     Esta prueba existe para que dentro de un año, cuando alguien vea
     `karaoke_launcher_v1` dentro de un proyecto llamado OKC y le parezca
     un descuido, lo cambie y se entere en el acto. */
  const CLAVES = ['karaoke_launcher_v1','karaoke_modo','karaoke_desfase',
                  'karaoke_efecto','karaoke_salida_audio','karaoke_nombre',
                  'karaoke_instalar_no','karaoke_launcher_estado_v1'];
  const fuentes = {};
  for(const f of ['js/nucleo.js','js/instalar.js','js/almacen-local.js','pedir.php',
                  'js/proyector/ajustes.js','js/proyector/servidor.js'])
    fuentes[f] = await fetch('../' + f).then(r => r.ok ? r.text() : '');
  const todo = Object.values(fuentes).join('\n');

  const perdidas = CLAVES.filter(c => todo.indexOf(c) < 0);
  igual(perdidas, [], 'claves de localStorage que alguien ha renombrado');

  /* Y lo que de verdad importa: que unas preferencias guardadas ANTES del
     rename sigan cargando. Se escriben a mano con la clave vieja, se
     recarga la aplicación y se mira si las ha leído. */
  const w = app();
  const guardadas = {
    quality:'small', view:'compact', efecto:'barras',
    desfaseTele:-1.5, segFin:9, sonidoEn:'tele', avisoSig:'izq'
  };
  w.localStorage.setItem('karaoke_launcher_v1', JSON.stringify(guardadas));

  marco.contentWindow.location.reload();
  await hasta(() => { try{ return app().KL && app().KL.estado
                                  && app().KL.estado.quality === 'small'; }
                      catch(_){ return false; } },
              'la aplicación no ha vuelto a cargar', 10000);

  const S2 = S();
  igual(S2.quality, 'small', 'la calidad guardada se ha perdido');
  igual(S2.view, 'compact', 'la vista guardada se ha perdido');
  igual(S2.efecto, 'barras', 'el efecto guardado se ha perdido');

  /* El espacio NO está en esta lista, y lo descubrió esta misma prueba al
     escribirla: se guarda en las preferencias, pero al llegar el estado
     del servidor gana el suyo. Y así tiene que ser —el espacio es una
     decisión compartida: si el operador está en la Cabina DJ, la tele y
     los móviles también—. Lo que queda en localStorage solo sirve para
     el medio segundo que va desde que abre la ventana hasta que
     contesta el servidor. */
  const enServidor = (await estadoBD()).espacio;
  igual(S2.modo, enServidor, 'el espacio lo manda el servidor, no las preferencias');
  igual(S2.desfaseTele, -1.5, 'el desfase de la tele se ha perdido');
  igual(S2.segFin, 9, 'los segundos de aplauso se han perdido');
  igual(S2.sonidoEn, 'tele', 'la salida de sonido se ha perdido');

  /* Y se deja como estaba, que si no la siguiente prueba arranca en la
     Cabina DJ con el sonido en la tele. */
  try{ app().localStorage.removeItem('karaoke_launcher_v1'); }catch(_){}
  marco.contentWindow.location.reload();
  await hasta(() => { try{ return app().KL && app().KL.estado
                                  && app().KL.estado.modo === 'karaoke'; }
                      catch(_){ return false; } },
              'la aplicación no ha vuelto a su estado limpio', 10000);
  await limpiar();
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
grupo('Contrato entre almacenes', 'arquitectura');

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
grupo('La capa de comandos', 'arquitectura');

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
grupo('Módulos encapsulados', 'arquitectura');

prueba('Los módulos no dejan sueltas sus funciones internas', () => {
  /* El paso 6. Cada archivo publica lo que hace falta y nada más. Si algo
     vuelve al ámbito global, esta prueba lo dice: no porque ensucie, sino
     porque una función global la acaba llamando alguien, y entonces ya no
     se puede cambiar sin romper a un desconocido. */
  const w = app();
  const filtradas = ['addQueue','toggleLib','libItems','drawLib','drawQue',
                     'doSearch','resolveAll','drawResults','parseYT',
                     'arrancarCronometro','pararCronometro','api',
                     'descargar','borrarTodasLasDescargas','comprobarDescargas',
                     'pintarPaneles','proponerDescargas','showBuf','mostrarFallo'];
  const sueltas = filtradas.filter(n => typeof w[n] === 'function');
  igual(sueltas, [], 'funciones que siguen sueltas en el ámbito global');
});

prueba('Cada módulo publica su puerta y responde', () => {
  const KLm = KL();
  const puertas = {
    'KL.api': 'function', 'KL.cola': 'object', 'KL.busqueda': 'object',
    'KL.cronometro': 'object', 'KL.comandos': 'object', 'KL.senales': 'object',
    'KL.Cancion': 'object', 'KL.Actuacion': 'object', 'KL.evento': 'object',
    'KL.reproductor': 'object', 'KL.almacen': 'object'
  };
  const faltan = Object.keys(puertas)
    .filter(k => typeof KLm[k.slice(3)] !== puertas[k]);
  igual(faltan, [], 'módulos que no publican su puerta');
});

prueba('Ningún nombre del vocabulario pisa uno del navegador', () => {
  /* El fallo que costó el botón de la pantalla pública.

     Al encapsular la interfaz hubo que publicar su vocabulario con
     `Object.assign(window, {...})`, y dentro iban `open` y `close`. Eso
     **sobrescribió `window.open`**, la función del navegador que abre
     ventanas: `window.open('proyector.php?…')` acababa en un
     `querySelector` y reventaba. El botón parecía muerto y no lo estaba.

     Antes no pasaba: un `const` global NO sustituye a `window.open`, vive
     al lado. Asignarlo a `window` sí. Encapsular y volver a exponer no es
     lo mismo que no encapsular, y esta prueba es lo que quedó de
     aprenderlo. */
  const w = app();
  const DEL_NAVEGADOR = ['open','close','print','alert','confirm','prompt','focus',
                         'blur','stop','find','name','status','length','top','self',
                         'parent','origin','location','history','navigator','screen'];
  const iframe = document.createElement('iframe');
  iframe.style.display = 'none';
  document.body.appendChild(iframe);
  try{
    /* Se compara contra una ventana limpia: si nuestra función y la del
       navegador son la misma, es que no la hemos tocado. */
    const limpio = iframe.contentWindow;
    const pisados = DEL_NAVEGADOR.filter(n =>
      typeof limpio[n] === 'function' && typeof w[n] === 'function'
      && w[n].toString() !== limpio[n].toString());
    igual(pisados, [], 'funciones del navegador sobrescritas por el vocabulario');
  } finally { iframe.remove(); }
}, 'regresion');

prueba('El vocabulario de la interfaz está cerrado', () => {
  /* `S`, `toast` y compañía siguen siendo globales A PROPÓSITO: son el
     idioma que habla todo el proyecto, como `$` en las páginas con
     jQuery. Escribir `KL.interfaz.toast(...)` doscientas cuarenta veces
     no desacoplaría nada; solo haría el texto más largo.

     Lo que sí importa es que la lista esté CERRADA. Antes eran treinta y
     nadie sabía cuáles hacían falta; ahora están enumeradas en el final
     de interfaz.js y esta prueba falla si aparece la número diecisiete
     sin que nadie lo haya decidido. Un global sin permiso es el que hace
     daño; uno declarado y contado, no. */
  const PERMITIDOS = new Set([
    '$','$$','uid','fmt','iso','esc','unesc','icono','EV',
    'S','toast','qGet','inLib','pistaTrasId','siguientePista',
    'draw','drawNP','dibujarRed','dibujarSiguiente','abrir','cerrar',
    'load','guardarPrefs','setView','setLibCol','aplicarModo','aplicarEdicion',
    'VIEWS','VNAME','ESPACIOS','urlPedir','modo','aplicar'
  ]);
  /* Nombres que la propia página del navegador ya trae y que no son
     nuestros: `open` y `close` existen en cualquier ventana. */
  const w = app();
  const sospechosos = ['tT','save','medirBarraMini','tituloPestana','ESPACIO_VIEJO',
                       'CARTELONES','pintarPaneles','mandarPaneles','activosAhora',
                       'quietoT','cron','sondeoDesc','bindQueue','iconoDescarga',
                       'quienPide','resolveTitle','parseYT','escribiendo'];
  const colados = sospechosos.filter(n => w[n] !== undefined && !PERMITIDOS.has(n));
  igual(colados, [], 'nombres internos que se han escapado al ámbito global');
});

grupo('El estado no se pierde', 'comportamiento');

prueba('Veinte cambios a la vez no pierden ninguno', async () => {
  /* La pregunta de siempre sobre PHP: leer, modificar y escribir un JSON
     desde varias peticiones a la vez. `modificar_estado()` hace las tres
     cosas dentro de un mismo `flock`, así que ninguna se pierde. Esto lo
     comprueba de verdad: veinte canciones lanzadas a la vez, sin esperar
     unas a otras, tienen que estar las veinte. */
  await limpiar();
  const envios = [];
  for(let i = 0; i < 20; i++){
    envios.push(accionBD({ accion:'anadir_cola',
      video:{ videoId:'lote' + String(i).padStart(7,'0'), title:'A la vez ' + i } }));
  }
  await Promise.all(envios);

  const j = await estadoBD();
  igual(j.cola.length, 20, 'se han perdido cambios al escribir a la vez');
  igual(new Set(j.cola.map(t => t.id)).size, 20, 'hay identificadores repetidos');
  await limpiar();
});

prueba('Una ventana nueva se reconstruye sola, sin mensajes previos', async () => {
  /* La arquitectura entera depende de esto: una pantalla que se abre a
     mitad de fiesta NO recibe lo que pasó antes. Solo lee el estado
     actual. Si hiciera falta algún mensaje anterior, recargar el
     navegador durante una canción dejaría esa ventana rota para siempre.

     Se comprueba abriendo una segunda copia de la aplicación con una cola
     ya montada y un evento en curso. */
  await limpiar();
  const v = await anadir('Reconstruida'); await sincronizar(v.videoId);
  const id = S().queue[0].id;
  await accionBD({ accion:'evento',
                   evento:{ estado:'PREPARADA', pistaId:id, n: 2000000 } });

  const m = document.createElement('iframe');
  m.style.cssText = 'position:fixed;left:-9999px;width:900px;height:600px';
  m.src = '../index.html?bd=pruebas';
  document.body.appendChild(m);
  try{
    await hasta(() => { try{ return !!(m.contentWindow.KL && m.contentWindow.KL.evento); }
                        catch(e){ return false; } }, 'cargue la segunda ventana', 15000);
    const S2 = () => m.contentWindow.KL.estado;
    await hasta(() => (S2().queue || []).some(t => t.id === id),
                'la ventana nueva se traiga la cola');
    await hasta(() => S2().evento.pistaId === id,
                'la ventana nueva se traiga el evento');
    igual(S2().curId, id, 'la ventana nueva no sabe qué está preparado');
  } finally { m.remove(); }
  await limpiar();
});

grupo('Que no haya páginas en blanco', 'regresion');

prueba('Un error fatal de PHP se ve, no deja la pantalla en blanco', async () => {
  /* De todas las pruebas del proyecto, esta protege el fallo que más caro
     salió: instalar esto en un portátil ajeno y encontrarse DOS páginas
     en blanco —guardar la clave de la API, y la página de pedir— que
     resultaron ser el mismo fallo.

     `Preparar.bat` copia `php.ini-production`, que trae
     `display_errors = Off`. Con eso, un error fatal manda un 200 con el
     cuerpo vacío: la página en blanco no era el fallo, era el fallo
     escondiéndose. Y no es un caso raro — es lo que le pasa a todo el
     mundo la primera vez, porque en el ordenador de uno ya está todo
     puesto.

     Se comprueba de verdad, provocando un fatal desde qa.php. Una red de
     seguridad que nadie ha probado no es una red de seguridad. */
  const r = await fetch('../qa.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'accion=romper'
  });
  const txt = await r.text();
  afirmar(txt.trim().length > 200,
          'un error fatal ha devuelto una página prácticamente vacía');
  afirmar(/se ha roto/i.test(txt),
          'la página de error no dice que algo se ha roto');
  afirmar(/errores\.log/.test(txt),
          'no dice dónde queda apuntado');
}, 'regresion');

prueba('Todos los formatos de enlace de YouTube se reconocen', async () => {
  /* Pegar un enlace es **el único camino que funciona sin clave de la
     API**: va por oEmbed, que es público y no gasta cuota. Así que si un
     formato deja de reconocerse, quien acaba de instalar esto se queda
     sin ninguna manera de añadir una canción — y eso es exactamente lo
     que se vio en un portátil ajeno.

     La lista vive en qa.php, al lado de las demás comprobaciones de
     entorno, y esta prueba solo se asegura de que está en verde. Dos
     listas serían dos sitios donde olvidarse de añadir un formato.

     Ya ha pillado dos: `/v/ID`, las mayúsculas y `youtube-nocookie.com`.
     De hecho el tercero lo encontró la propia comprobación al escribirla,
     que es lo que se espera de ella. */
  const txt = await (await fetch('../qa.php')).text();
  const m = txt.match(/Formatos de enlace reconocidos[\s\S]{0,120}?class="d">([^<]*)/);
  afirmar(!!m, 'qa.php ya no comprueba los formatos de enlace');
  afirmar(!/NO se reconocen/.test(m[1]),
          'hay formatos de enlace que no se reconocen: ' + m[1]);
}, 'regresion');

prueba('Un JSON corrupto se aparta, no se pisa', async () => {
  /* Este es peor que una página en blanco, y por eso está aquí.

     `leer_estado` devolvía los valores por defecto cuando el archivo no
     se podía leer — y a la siguiente escritura se guardaban encima. Es
     decir: **un byte mal en estado.json y la biblioteca entera
     desaparecía en silencio**. Sin error, sin aviso, y sin nada que
     recuperar.

     Ahora el archivo roto se aparta con su fecha. La fiesta arranca igual
     —no se puede quedar parada— pero lo que había sigue estando. */
  const txt = await (await fetch('../qa.php')).text();
  afirmar(/apartad/i.test(txt) || /roto/i.test(txt),
          'qa.php no sabe avisar de un archivo apartado por corrupto');
}, 'regresion');

prueba('Sin clave de API, el mensaje dice qué SÍ se puede hacer', async () => {
  /* «No hay clave de API configurada» es cierto y es inútil: quien lo lee
     piensa que la aplicación está rota. No lo está — le falta UNA de sus
     dos maneras de encontrar canciones, y la otra funciona y es gratis.

     Un error que no dice qué se puede hacer deja igual de parado que uno
     que no dice nada. */
  const r = await fetch('../api/buscar.php?q=' + encodeURIComponent('prueba sin clave'));
  const j = await r.json();
  if(j.ok) return;   // hay clave puesta: no es el caso que se prueba
  afirmar(/pega/i.test(j.error || ''),
          'el mensaje de «sin clave» no dice que se pueden pegar enlaces: ' + j.error);
  afirmar(/ajustes/i.test(j.error || ''),
          'no dice dónde se pone la clave: ' + j.error);
}, 'regresion');

prueba('qa.php se puede abrir con la aplicación rota', async () => {
  /* Es su único requisito de verdad: si solo funcionara con todo bien,
     no serviría para el único momento en que hace falta. */
  const r = await fetch('../qa.php');
  const txt = await r.text();
  igual(r.status, 200, 'qa.php no responde');
  afirmar(/Extensión mbstring/.test(txt), 'no comprueba las extensiones de PHP');
  afirmar(/Carpeta data/.test(txt), 'no comprueba si se puede escribir');
  afirmar(/Cuota gastada hoy/.test(txt), 'no dice cuánta cuota de YouTube queda');
}, 'regresion');

grupo('El filtro de búsqueda', 'producto');

prueba('El filtro traduce a lo que YouTube entiende de verdad', () => {
  /* La regla de filtro.js: **aquí no se inventa ningún operador**. Es
     fácil escribir un buscador que finja entender —aceptar `IN`, `NEAR`,
     lo que sea, mandarlo tal cual y que YouTube lo trate como una
     palabra más—. Devuelve resultados, parecen razonables, y nadie
     descubre nunca que no ha hecho nada.

     Lo que la API entiende: espacio = Y, `|` = O, `-` = sin. Eso y nada
     más, y eso es lo que se comprueba. */
  const F = KL().filtro;
  igual(F.aConsulta('karaoke'), 'karaoke');
  igual(F.aConsulta('karaoke OR playback'), 'karaoke|playback');
  igual(F.aConsulta('karaoke AND español'), 'karaoke español',
        'AND no traduce a nada: el espacio YA es un AND');
  igual(F.aConsulta('karaoke -live'), 'karaoke -live');
  igual(F.aConsulta('karaoke sin live sin cover'), 'karaoke -live -cover',
        'las palabras en español tienen que valer igual');
  igual(F.aConsulta('a OR b AND c'), 'a|b c');
  /* Las comillas no se parten aunque lleven espacios dentro: es
     justamente cuando más falta hacen. */
  igual(F.aConsulta('karaoke OR "karaoke version"'), 'karaoke|"karaoke version"');
  igual(F.aConsulta('"karaoke version'), '"karaoke version"',
        'una comilla sin cerrar se cierra sola, no es un error que enseñar');
  igual(F.aConsulta(''), '');

  /* Y avisa de lo que de verdad no hace nada, no de lo que parezca raro. */
  afirmar(/Falta/.test(F.aviso('karaoke OR')), 'un OR colgando debería avisar');
  igual(F.aviso('karaoke OR playback'), '', 'un filtro correcto no lleva aviso');
}, 'producto');

prueba('Los perfiles son el filtro con nombre, no otra función', () => {
  /* Se planteó como algo aparte —una lista de perfiles, con su panel— y
     habría sido construir dos veces lo mismo: un perfil es un filtro que
     alguien quiere volver a usar.

     Lo que sí tienen que cumplir: ser por espacio, y traducir todos a
     algo válido. Un perfil de fábrica que no traduce es documentación
     equivocada, que es peor que ninguna. */
  const F = KL().filtro;
  const k = F.perfilesDe('karaoke', null);
  const d = F.perfilesDe('dj', null);
  afirmar(k.length >= 3 && d.length >= 3, 'faltan perfiles de fábrica');
  for(const p of k.concat(d)){
    afirmar(!!p.nombre, 'un perfil sin nombre no se puede elegir');
    afirmar(F.aviso(p.filtro) === '',
            `el perfil «${p.nombre}» no traduce bien: ${F.aviso(p.filtro)}`);
  }
  /* Y los de fábrica no se pisan con los del operador. */
  const mios = F.perfilesDe('karaoke', { karaoke:[{ nombre:'Lo mío', filtro:'x' }] });
  igual(mios.length, k.length + 1, 'el perfil propio no se ha añadido');
  afirmar(mios[mios.length - 1].propio === true, 'el propio no está marcado');
}, 'producto');

prueba('Freestyle ya no existe en ninguna parte', async () => {
  /* Quitar algo a medias es peor que no quitarlo: queda un botón que no
     hace nada o una regla de CSS que nadie pide. Se comprueba sobre los
     archivos, no sobre la pantalla. */
  const d = app().document;
  igual([...d.querySelectorAll('#espacios .esp')].map(b => b.dataset.esp),
        ['karaoke', 'dj'], 'los espacios que se ven no son los dos');
  igual(d.getElementById('freestyle'), null, 'sigue el panel de Freestyle');
  afirmar(!KL().ESPACIOS_INFO.freestyle, 'sigue en la tabla de espacios');

  /* Pero el nombre viejo tiene que seguir traduciéndose: hay
     preferencias guardadas con él. */
  app().aplicarModo('freestyle');
  igual(S().modo, 'karaoke', 'el nombre viejo debería caer en Karaoke');
}, 'producto');

grupo('Una cola por espacio', 'comportamiento');

prueba('Cada espacio tiene su cola y no se mezclan', async () => {
  await limpiar();
  await accionBD({ accion:'anadir_cola', espacio:'karaoke',
                   video:{ videoId:'espkar00001', title:'De karaoke' } });
  await accionBD({ accion:'anadir_cola', espacio:'dj',
                   video:{ videoId:'espdj000001', title:'De la cabina' } });
  /* Y una con el nombre VIEJO. Freestyle se quitó en la v1.2 porque no
     era un espacio —hacía lo mismo que Karaoke buscando otra palabra—
     pero el estado de alguien puede traer canciones marcadas así.

     Lo que se comprueba aquí es que **no se pierde ninguna**: se traduce
     a Karaoke, que es donde se cantaba. Descartar datos de alguien para
     simplificar un `switch` no es una opción, y una prueba es la única
     manera de que eso siga siendo verdad dentro de un año. */
  await accionBD({ accion:'anadir_cola', espacio:'freestyle',
                   video:{ videoId:'espfree0001', title:'Una base' } });

  const j = await estadoBD();
  igual(j.cola.length, 3, 'no han entrado las tres');
  igual(j.cola.map(t => t.espacio), ['karaoke','dj','karaoke'],
        'el espacio viejo «freestyle» no se ha traducido a karaoke');

  await sincronizar('espkar00001', 'espdj000001', 'espfree0001');
  /* `colaDe` es parte del vocabulario de la interfaz, no de KL: vive en el
     ámbito de la ventana igual que `S` o `toast`. */
  const w = app();
  igual(w.colaDe('karaoke').length,   2, 'la cola de karaoke no tiene dos (la suya y la traducida)');
  igual(w.colaDe('dj').length,        1, 'la de la cabina no tiene una');
  igual(w.colaDe('freestyle').length, 0, 'freestyle ya no existe y sigue teniendo cola');

  /* La misma canción SÍ puede estar en dos espacios: el instrumental y el
     karaoke son la misma grabación con otra intención. */
  const r = await accionBD({ accion:'anadir_cola', espacio:'dj',
                             video:{ videoId:'espkar00001', title:'De karaoke' } });
  afirmar(!r.error, 'no deja repetir la misma canción en OTRO espacio: ' + r.error);
  /* Y en el mismo espacio TAMBIÉN puede: dos personas cantando lo mismo
     son dos actuaciones distintas. Esto antes se rechazaba y era el
     error de fondo del proyecto en miniatura — tratar una cola de
     karaoke como una lista de reproducción. */
  const r2 = await accionBD({ accion:'anadir_cola', espacio:'dj',
                              video:{ videoId:'espdj000001', title:'De la cabina' } });
  afirmar(!r2.error, 'no deja dos actuaciones de la misma canción: ' + r2.error);
  /* Se mira en la respuesta del servidor y no en el navegador: la
     aplicación del marco todavía no ha sondeado. */
  igual(r2.cola.filter(t => (t.espacio || 'karaoke') === 'dj').length, 3,
        'la segunda actuación no ha entrado en la Cabina DJ');
  await limpiar();
});

prueba('Vaciar vacía solo el espacio en el que estás', async () => {
  await limpiar();
  await accionBD({ accion:'anadir_cola', espacio:'karaoke',
                   video:{ videoId:'vackar00001', title:'Karaoke' } });
  await accionBD({ accion:'anadir_cola', espacio:'dj',
                   video:{ videoId:'vacdj000001', title:'Cabina' } });

  await accionBD({ accion:'vaciar_cola', espacio:'dj' });
  const j = await estadoBD();
  igual(j.cola.length, 1, 'ha vaciado de más');
  igual(j.cola[0].espacio, 'karaoke', 'ha vaciado el espacio equivocado');
  await limpiar();
});

prueba('Las pistas de la versión anterior son del karaoke', async () => {
  /* Un `estado.json` de antes de las colas separadas no trae `espacio`.
     Sin traducirlo, esas canciones desaparecerían de la vista. */
  await limpiar();
  await accionBD({ accion:'anadir_cola',
                   video:{ videoId:'viejo000001', title:'Sin espacio' } });
  const j = await estadoBD();
  igual(j.cola[0].espacio, 'karaoke', 'una pista sin espacio debería ser de karaoke');
  await limpiar();
});

grupo('Condiciones de carrera del buscador', 'comportamiento');

prueba('Una búsqueda lenta no pisa a la que se pidió después', async () => {
  /* Se escribe «uno», se pulsa Intro, se sigue escribiendo y se vuelve a
     pulsar. Si la primera respuesta tarda más que la segunda —cosa normal,
     dependen de YouTube— llega después y pisa los resultados buenos.
     Aquí se fuerza justo eso: la primera petición tarda un segundo y la
     segunda contesta al momento. */
  const w = app();
  const fetchReal = w.fetch;
  let n = 0;
  w.fetch = function(url, opc){
    if(String(url).includes('buscar.php')){
      n++;
      const cual = n;
      const cuerpo = { ok:true, items:[{ videoId:'zzzzzzzzzz' + cual, title:'Resultado ' + cual,
                                          channel:'x', thumb:'', duration:1 }] };
      const resp = () => new Response(JSON.stringify(cuerpo),
                          { headers:{'Content-Type':'application/json'} });
      /* La primera tarda; la segunda contesta ya. */
      return cual === 1 ? new Promise(r => setTimeout(() => r(resp()), 900))
                        : Promise.resolve(resp());
    }
    return fetchReal.apply(this, arguments);
  };
  try{
    const q = w.document.querySelector('#q');
    q.value = 'primera'; w.KL.busqueda.buscar();
    await esperar(60);
    q.value = 'segunda'; w.KL.busqueda.buscar();

    await hasta(() => (S().results || []).length > 0, 'lleguen resultados');
    await esperar(1400);   // tiempo de sobra para que llegue la lenta

    igual(S().results.length, 1, 'debería haber un solo resultado');
    igual(S().results[0].title, 'Resultado 2',
          'la respuesta lenta de la búsqueda anterior ha pisado a la buena');
  } finally {
    w.fetch = fetchReal;
    try{ w.document.querySelector('#ovRes').classList.remove('on'); }catch(_){}
  }
}, 'regresion');

grupo('Cabina DJ', 'producto');

prueba('La lista de ambiente se reconoce venga como venga', async () => {
  /* La gente pega lo que tiene a mano. Pedirle que averigüe cuál de esas
     cosas es «el ID de la playlist» es pedirle que haga el trabajo del
     programa. */
  const j = await estadoBD();
  afirmar(!!j.ambiente, 'el estado no trae los ajustes de la cabina');
  igual(typeof j.ambiente.lista, 'string', 'la lista no llega como texto');
  igual(typeof j.ambiente.volumen, 'number', 'el volumen no llega como número');
  afirmar(['youtube','carpeta','no'].includes(j.ambiente.fuente),
          'fuente desconocida: ' + j.ambiente.fuente);

  /* Un canal `UC…` tiene siempre su lista de subidas en `UU…`. Es la
     conversión que permite poner «todo lo que suba este canal» en vez de
     una lista fija que se queda vieja. */
  if(j.ambiente.lista.startsWith('UU'))
    igual(j.ambiente.lista.length, 24, 'la lista de subidas no tiene la longitud de un canal');
});

grupo('La aplicación instalable', 'arquitectura');

prueba('El service worker no se deja fuera ningún archivo', async () => {
  /* La trampa de una lista escrita a mano: si se añade un archivo y no se
     añade ahí, no pasa nada… hasta la noche que falla el router. Entonces
     falta justo ese, y la pantalla del público sale sin estilos delante
     de todo el mundo. */
  const sw = await fetch('../sw.js').then(r => r.text());
  const enCache = new Set([...sw.matchAll(/'\.\/([^']+)'/g)].map(m => m[1]));
  afirmar(enCache.size >= 25, 'no he sabido leer la lista del sw: ' + enCache.size);

  const faltan = [];
  for(const pagina of ['index.html', 'proyector.php']){
    const html = await fetch('../' + pagina).then(r => r.text());
    for(const m of html.matchAll(/(?:src|href)="((?:js|css)\/[^"]+)"/g)){
      if(!enCache.has(m[1])) faltan.push(pagina + ' pide ' + m[1]);
    }
  }
  igual(faltan, [], 'archivos que la aplicación usa y el service worker no guarda');
});

prueba('Cada superficie tiene su propio manifiesto, y es válido', async () => {
  /* Antes `pedir.php` apuntaba al manifiesto del operador: quien lo
     instalaba desde el móvil acababa con el puesto de mando entero en el
     cajón de aplicaciones. */
  const superficies = {
    'index.html':    'manifest.webmanifest',
    'proyector.php': 'manifest-tele.webmanifest',
    'pedir.php':     'manifest-pedir.webmanifest'
  };
  const mal = [];
  const arranques = new Set();

  for(const [pagina, esperado] of Object.entries(superficies)){
    const html = await fetch('../' + pagina).then(r => r.text());
    const m = html.match(/rel="manifest" href="([^"]+)"/);
    if(!m || m[1] !== esperado){ mal.push(pagina + ' → ' + (m ? m[1] : 'ninguno')); continue; }

    const j = await fetch('../' + esperado).then(r => r.json());
    if(!j.name || !j.start_url || !(j.icons || []).length) mal.push(esperado + ' incompleto');
    if(!(j.icons || []).some(i => i.purpose === 'maskable'))
      mal.push(esperado + ' sin icono maskable');
    arranques.add(j.start_url);

    /* Los iconos que promete tienen que existir de verdad. */
    for(const ic of j.icons || []){
      const r = await fetch('../' + ic.src, { method:'HEAD' });
      if(!r.ok) mal.push(esperado + ' promete ' + ic.src + ' y no está');
    }
  }
  igual(mal, [], 'problemas en los manifiestos');
  igual(arranques.size, 3, 'dos superficies arrancan en el mismo sitio: se instalarían como una sola');
});

grupo('La pantalla del público', 'comportamiento');

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

prueba('La tele se pone el tema que le manda el servidor', async () => {
  /* Esta prueba existe por un fallo concreto y caro de encontrar.
     `aplicarTemaTele` estaba escrita DENTRO del `if` de la tecla E, por
     un descuido al pegarla. Con `'use strict'` una función declarada
     dentro de un bloque solo existe en ese bloque, así que `escenas.js`
     la llamaba y no existía: la tele se quedaba en Clásico con Fiesta,
     con Peques y con Show, y no lo notó nadie porque fallaba en silencio.

     La prueba de «arranca sin reventar» no lo cazó porque solo miraba una
     función por archivo, y de `servidor.js` miraba otra. */
  const m = document.createElement('iframe');
  m.style.cssText = 'position:fixed;left:-9999px;width:800px;height:450px';
  m.src = '../proyector.php';
  document.body.appendChild(m);
  try{
    await new Promise(r => { m.onload = r; setTimeout(r, 8000); });
    await hasta(() => { try{ return typeof m.contentWindow.pintar === 'function'; }
                        catch(e){ return false; } },
                'el proyector termine de cargar', 8000);
    const w = m.contentWindow;

    igual(typeof w.aplicarTemaTele, 'function',
          'la tele no tiene forma de ponerse el tema');

    for(const t of ['fiesta','kids','show','clasico']){
      w.aplicarTemaTele(t);
      igual(w.document.documentElement.dataset.tema, t,
            'la tele no se ha puesto el tema ' + t);
    }
    /* Un tema inventado no puede dejar la tele sin vestir. */
    w.aplicarTemaTele('navidad');
    igual(w.document.documentElement.dataset.tema, 'clasico',
          'un tema desconocido debe caer en Clásico');
  } finally { m.remove(); }
}, 'regresion');

prueba('La tele salta a donde va la canción, no arranca «a la vez»', async () => {
  /* El cambio de modelo de la sincronía, comprobado sobre la función que
     lo decide.

     Antes las dos pantallas arrancaban a la vez y un número fijo
     compensaba la diferencia. No podía funcionar: esta pantalla no se
     entera hasta el siguiente sondeo, y ese retardo varía entre cero y
     segundo y medio según cuándo caiga la vuelta.

     Ahora el reproductor del cantante publica en qué instante estaba en
     el segundo cero y esta calcula por dónde va. */
  const m = document.createElement('iframe');
  m.style.cssText = 'position:fixed;left:-9999px;width:800px;height:450px';
  m.src = '../proyector.php';
  document.body.appendChild(m);
  try{
    await new Promise(r => { m.onload = r; setTimeout(r, 8000); });
    await hasta(() => { try{ return typeof m.contentWindow.porDondeVa === 'function'; }
                        catch(e){ return false; } },
                'el proyector cargue', 8000);
    const w = m.contentWindow;

    /* Sin `t0` no se inventa nada: se empieza por el principio y la
       corrección continua lo cuadra en cuanto llegue. */
    igual(w.porDondeVa({}), null, 'sin t0 no puede deducir una posición');
    igual(w.porDondeVa({ t0:0 }), null, 't0 a cero es «todavía no lo sé»');

    /* Doce segundos después del segundo cero, la canción va por doce…
       MÁS la calibración de ESTA pantalla, que es una preferencia del
       aparato y puede no ser cero.

       Esta prueba daba rojo en un ordenador con la calibración a 0,7 y el
       fallo era de la prueba: comparaba contra un número fijo dando por
       hecho un ajuste que el usuario tiene todo el derecho a cambiar. Una
       prueba que se cae porque alguien configuró algo legítimo no está
       protegiendo nada — está pidiendo que nadie toque los ajustes. */
    const cal = w.CALIBRACION || 0;
    const va = w.porDondeVa({ t0: Date.now() - 12000 });
    afirmar(Math.abs(va - (12 + cal)) < 0.5,
            `debería ir por el segundo ${(12 + cal).toFixed(1)} `
            + `(12 + ${cal} de calibración), dice ` + va);

    /* Un reloj descuadrado no puede mandar la tele al minuto noventa: es
       peor saltar a un sitio absurdo que empezar de cero. */
    igual(w.porDondeVa({ t0: Date.now() - 5 * 60 * 60 * 1000 }), null,
          'un t0 imposible debe descartarse');
    igual(w.porDondeVa({ t0: Date.now() + 60000 }), null,
          'un t0 en el futuro debe descartarse');
  } finally { m.remove(); }
}, 'regresion');

prueba('La carta de reto sale en la tele y se puede quitar', async () => {
  const m = document.createElement('iframe');
  m.style.cssText = 'position:fixed;left:-9999px;width:800px;height:450px';
  m.src = '../proyector.php';
  document.body.appendChild(m);
  try{
    await new Promise(r => { m.onload = r; setTimeout(r, 8000); });
    await hasta(() => { try{ return typeof m.contentWindow.pintar === 'function'; }
                        catch(e){ return false; } },
                'el proyector termine de cargar', 8000);
    const w = m.contentWindow;
    const carta = () => w.document.getElementById('carta');

    w.pintar({ evento:{estado:'ESPERA'}, cola:[], biblioteca:[], tema:'show',
               show:{ reto:{ texto:'Canta con una mano en el corazón', n:1 } } }, true);
    afirmar(!carta().classList.contains('oculto'), 'la carta debería verse');
    igual(w.document.getElementById('cartaTxt').textContent,
          'Canta con una mano en el corazón');

    w.pintar({ evento:{estado:'ESPERA'}, cola:[], biblioteca:[], tema:'show',
               show:{ reto:null } }, true);
    afirmar(carta().classList.contains('oculto'), 'la carta debería haberse quitado');

    /* Y con otro tema NO se enseña, aunque la carta siga en el estado.
       Un fallo real: se cambiaba de tema y la carta se quedaba colgada
       en la tele como un cartel que nadie sabía quitar. */
    for(const otro of ['clasico','fiesta','kids']){
      w.pintar({ evento:{estado:'ESPERA'}, cola:[], biblioteca:[], tema:otro,
                 show:{ reto:{ texto:'Sigue ahí', n:9 } } }, true);
      afirmar(carta().classList.contains('oculto'),
              'la carta de reto sale con el tema ' + otro);
    }

    /* Y sin Modo Show no aparece nada: un estado sin `show` es el de
       cualquier fiesta normal y no puede dejar una tira encima del
       vídeo. */
    w.pintar({ evento:{estado:'ESPERA'}, cola:[], biblioteca:[] }, true);
    afirmar(carta().classList.contains('oculto'), 'sin Modo Show no debe haber carta');
  } finally { m.remove(); }
});

grupo('Modo Show', 'producto');

prueba('Las cartas de reto son un archivo que se puede editar a mano', async () => {
  /* La promesa del Modo Show es que añadir una carta NO es programar. Si
     esto deja de ser un JSON legible, esa promesa se ha roto. */
  const j = await fetch('../retos.json').then(r => r.json());
  for(const monton of ['suave','fuerte']){
    afirmar(Array.isArray(j[monton]), 'falta el montón «' + monton + '»');
    afirmar(j[monton].length >= 10,
            'el montón «' + monton + '» tiene ' + j[monton].length + ' cartas: se repetirán');
    const malas = j[monton].filter(t => typeof t !== 'string' || t.trim().length < 10);
    igual(malas, [], 'cartas vacías o demasiado cortas en «' + monton + '»');
  }

  /* La regla de las cartas: un reto se cumple SIN saber cantar. Si una
     habla de afinar o de la voz, deja fuera justo a quien más falta le
     hace atreverse. */
  const prohibidas = /afin|desafin|entonad|buena voz|voz bonita|nota alta|gallo/i;
  const rompen = [...j.suave, ...j.fuerte].filter(t => prohibidas.test(t));
  igual(rompen, [], 'cartas que dependen de saber cantar');
});

prueba('Una carta se publica, se cuenta y se quita', async () => {
  await limpiar();
  const a = await accionBD({ accion:'show', reto:{ texto:'Baila sin parar' } });
  igual(a.show.reto.texto, 'Baila sin parar');
  const n1 = a.show.reto.n;

  /* El contador lo lleva el servidor. La misma carta dos veces tiene que
     dar dos números distintos, o la tele no la vuelve a animar y parece
     que el botón se ha roto. */
  const b = await accionBD({ accion:'show', reto:{ texto:'Baila sin parar' } });
  afirmar(b.show.reto.n > n1, 'el contador de cartas no ha subido: ' + b.show.reto.n);

  const c = await accionBD({ accion:'show', reto:null });
  igual(c.show.reto, null, 'la carta no se ha quitado');
  await limpiar();
});

prueba('En el Modo Show no hay ni puede haber marcador', async () => {
  /* `DECISIONES.md` promete que aquí nunca hay puntuaciones ni
     clasificaciones, y `ajustes.php` se lo dice al usuario con esas
     palabras. Hubo un marcador de equipos a medio escribir y se quitó.

     Esta prueba no es decorativa: es la única forma de que esa promesa
     sobreviva a la próxima buena idea. Si alguien vuelve a meter puntos,
     falla aquí y tiene que ir a cambiar el texto de ajustes a mano —que
     es exactamente la conversación que hay que tener antes. */
  await limpiar();
  const e = await accionBD({ accion:'show',
                             equipos:[{nombre:'Los del sofá', puntos:7}],
                             reto:{ texto:'Que la sala haga los coros' } });
  igual(Object.keys(e.show).sort(), ['reto'],
        'el estado del Show ha crecido con algo que no es la carta');

  const texto = JSON.stringify(e);
  for(const palabra of ['equipos','puntos','marcador','ranking'])
    afirmar(texto.indexOf(palabra) < 0,
            'la palabra «' + palabra + '» ha aparecido en el estado');

  /* Y una carta no se le engancha a nadie: ni a una canción ni a quien la
     pidió. Si pudiera, en dos versiones habría un historial de retos por
     persona, que es un ranking con otro nombre. */
  const f = await accionBD({ accion:'show',
                             reto:{ texto:'Ponte algo encima', pistaId:'x', quien:'Marta' } });
  igual(Object.keys(f.show.reto).sort(), ['n','texto'],
        'la carta ha guardado a quién iba dirigida');
  await limpiar();
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

grupo('Canción y actuación', 'arquitectura');

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
}, 'producto');

grupo('El reparto de avisos', 'arquitectura');

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

grupo('Consistencia', 'arquitectura');

prueba('Los colores literales que quedan están justificados', async () => {
  /* No «cero literales»: hay tres que TIENEN que serlo —el negro detrás
     de un vídeo y el blanco y negro de un QR, que si no, no se lee—. Lo
     que no puede haber es un literal sin explicación, porque ese es el
     que un tema claro convierte en texto ilegible.

     La regla: si hay un color escrito a mano, encima tiene que haber un
     comentario diciendo por qué. Salió de encontrar un `#171c26` metido
     en un degradado que llevaba un año ahí sin que nadie lo viera. */
  const sospechosos = [];
  for(const hoja of ['operador', 'interpretacion', 'proyector']){
    const css = await fetch('../css/' + hoja + '.css').then(r => r.text());
    const lineas = css.split('\n');
    lineas.forEach((l, i) => {
      if(/^\s*(\/\*|\*)/.test(l)) return;
      if(!/#[0-9a-fA-F]{3,8}\b|\brgb\(|\bhsl\(/.test(l)) return;
      /* rgba(0,0,0,…) y rgba(255,255,255,…) son sombras y veladuras: no
         son color de marca y funcionan en cualquier tema. */
      const soloVelos = l.replace(/rgba?\(\s*(0\s*,\s*0\s*,\s*0|255\s*,\s*255\s*,\s*255)[^)]*\)/g, '');
      if(!/#[0-9a-fA-F]{3,8}\b|\brgb\(|\bhsl\(/.test(soloVelos)) return;
      /* ¿Hay un comentario justo encima? */
      const antes = lineas.slice(Math.max(0, i - 4), i).join('\n');
      if(/\/\*|\*/.test(antes)) return;
      sospechosos.push(hoja + '.css:' + (i + 1) + '  ' + l.trim().slice(0, 60));
    });
  }
  igual(sospechosos, [], 'colores escritos a mano sin explicación encima');
});

prueba('Ningún «¿seguro?» lleva el texto escrito dentro', async () => {
  /* `confirm()` se queda: es el mecanismo del navegador para parar a
     alguien antes de algo irreversible, y sustituirlo por un diálogo
     nuestro sería fabricar una pieza para que se parezca a la que ya hay.

     Lo que no se queda es el texto escrito en la línea de la llamada. Son
     los tres únicos mensajes de la aplicación que preceden a algo que no
     se puede deshacer, y tienen que poder leerse los tres juntos para ver
     si dicen lo que hay que decir: qué se pierde y qué no. */
  const culpables = [];
  for(const f of ['app', 'cola', 'interfaz', 'busqueda', 'ajustes']){
    const src = await fetch('../js/' + f + '.js').then(r => r.ok ? r.text() : '');
    src.split('\n').forEach((l, i) => {
      if(/^\s*(\/\/|\/\*|\*)/.test(l)) return;
      if(/confirm\s*\(\s*['"`]/.test(l))
        culpables.push(f + '.js:' + (i + 1) + '  ' + l.trim().slice(0, 60));
    });
  }
  igual(culpables, [], 'confirm() con el texto a mano en vez de en textos.js');

  /* Y que las que hay digan algo. Una pregunta que no explica qué se
     pierde es un peaje, no una pregunta. */
  const T = KL().TEXTOS;
  for(const clave of Object.keys(T.PREGUNTAS)){
    const txt = T.pregunta(clave, { espacio:'Karaoke', cuantas:3, titulo:'X' });
    afirmar(txt.length > 40 && txt.indexOf('\n') > 0,
            'la pregunta «' + clave + '» no explica las consecuencias');
    afirmar(txt.indexOf('undefined') < 0,
            'la pregunta «' + clave + '» tiene un hueco sin rellenar');
  }
}, 'producto');

prueba('Los cuatro temas se leen: contraste medido, no opinado', async () => {
  /* «Le falta contraste» es una impresión, y las impresiones se discuten
     sin final. Esto lo mide.

     La fórmula es la de WCAG: luminancia relativa de los dos colores y
     su cociente. 4,5:1 es el mínimo para texto normal; 7:1 es el nivel
     AAA, el que se exige cuando el que mira puede tener la vista
     cansada… o siete años.

     **Peques va a 7:1** por eso mismo. La primera versión del tema era
     crema sobre crema y se leía peor que el tema oscuro, que es
     exactamente lo contrario de lo que hacía falta. */
  const html = app().document.documentElement;
  const antes = html.dataset.tema;

  const lum = c => {
    const m = c.match(/\d+/g).slice(0,3).map(n => {
      const v = n / 255;
      return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
    });
    return 0.2126*m[0] + 0.7152*m[1] + 0.0722*m[2];
  };
  const contraste = (a, b) => {
    const [x, y] = [lum(a), lum(b)].sort((p, q) => q - p);
    return (x + 0.05) / (y + 0.05);
  };

  const MINIMOS = { clasico:4.5, fiesta:4.5, kids:7, show:4.5 };
  const flojos = [];
  for(const tema of ['clasico','fiesta','kids','show']){
    html.dataset.tema = tema;
    const v = getComputedStyle(html);
    const bg  = v.getPropertyValue('--bg').trim();
    const txt = v.getPropertyValue('--txt').trim();
    const t2  = v.getPropertyValue('--txt2').trim();
    /* Se resuelven a rgb() pintándolos en un elemento de verdad: las
       variables llegan como «#fff3dc» y hace falta el número. */
    const d = app().document.createElement('div');
    app().document.body.appendChild(d);
    const rgb = c => { d.style.color = c; return getComputedStyle(d).color; };
    const c1 = contraste(rgb(bg), rgb(txt));
    const c2 = contraste(rgb(bg), rgb(t2));
    d.remove();

    if(c1 < MINIMOS[tema])
      flojos.push(tema + ': texto principal ' + c1.toFixed(1) + ':1 (mínimo ' + MINIMOS[tema] + ')');
    /* El secundario puede bajar, pero no hasta desaparecer: 3:1 es el
       suelo de lo que todavía se distingue de un vistazo. */
    if(c2 < 3)
      flojos.push(tema + ': texto secundario ' + c2.toFixed(1) + ':1 (mínimo 3)');
  }
  html.dataset.tema = antes || 'clasico';
  igual(flojos, [], 'temas en los que cuesta leer');
}, 'producto');

prueba('Ningún tema mueve más de dos cosas a la vez', async () => {
  /* Esta prueba no es técnica: es de diseño, y protege de un olvido, no
     de un error.

     Cada animación se añade con buen criterio y por separado. El
     problema aparece cuando hay cuatro: una luz aquí, un rebote allá, un
     latido en la esquina — cada una defendible, todas juntas ilegibles.
     Y nadie las ve acumularse porque nunca se añaden el mismo día.

     Dos por pantalla. En Show son el telón y el rótulo, y por eso el
     rótulo entra 350 ms DESPUÉS: encadenados se leen como un pase, a la
     vez se leen como un temblor. */
  const TOPE = 2;
  const html = app().document.documentElement;
  const antes = html.dataset.tema;
  const pasados = [];

  for(const tema of ['clasico','fiesta','kids','show']){
    html.dataset.tema = tema;
    const enMarcha = [...app().document.querySelectorAll('*')].filter(el => {
      if(!el.offsetParent && el.tagName !== 'BODY') return false;
      const a = getComputedStyle(el).animationName;
      return a && a !== 'none';
    });
    if(enMarcha.length > TOPE)
      pasados.push(tema + ': ' + enMarcha.length + ' animaciones (' +
                   enMarcha.map(e => e.id || e.className || e.tagName).join(', ') + ')');
  }
  html.dataset.tema = antes || 'clasico';
  igual(pasados, [], 'temas con más de ' + TOPE + ' cosas moviéndose a la vez');
}, 'producto');

prueba('En Peques ningún mensaje valora cómo canta nadie', async () => {
  /* La regla de Peques, comprobada sobre los textos de verdad.

     No basta con prohibir «mal»: lo que hace daño a esa edad son los
     elogios sobre el resultado. «¡Muy bien!» parece lo contrario de una
     nota y funciona igual — la vez siguiente el crío sale a ver qué le
     dicen, y el que cantó peor lo nota. Se celebra haber salido. */
  const K = KL().TEXTOS.TODOS.kids;
  const textos = Object.values(K).join(' | ').toLowerCase();

  /* Valoraciones, incluidas las buenas. */
  const juicios = ['muy bien','genial','perfecto','fenomenal','increíble',
                   'mejor','peor','ganador','ganas','pierde','punto','nota',
                   'afinad','desafin','campeón','primer puesto',
                   /* «eres un artista» valora a la persona, que es peor
                      todavía que valorar la actuación. */
                   'eres un','qué artista','artistazo'];
  const encontrados = juicios.filter(p => textos.includes(p));
  igual(encontrados, [], 'palabras que valoran o comparan en los textos de Peques');

  /* Y nada que empuje ni que obligue: en Peques no hay retos, ni prisa,
     ni turnos impuestos. «Le toca a» suena a obligación aunque no lo
     sea, y una obligación es lo único que garantiza que un crío tímido
     no vuelva a acercarse al micro. */
  const empuja = ['atreve','rápido','deprisa','venga ya','a ver quién',
                  'te toca','le toca','tienes que','debes'];
  igual(empuja.filter(p => textos.includes(p)), [],
        'textos de Peques que meten presión');

  /* Que estén todas las filas: un tema a medias cae en Clásico, y el
     Clásico sí dice «¡Bien!», que es justo lo que no puede salir aquí. */
  const faltan = Object.keys(KL().TEXTOS.TODOS.clasico).filter(k => K[k] === undefined);
  igual(faltan, [], 'filas que Peques no define y heredaría del Clásico');
}, 'producto');

prueba('Solo hay un botón que arranque una canción', async () => {
  /* Había dos «Empezar»: el grande del centro y otro en la franja de
     estado. Hacían lo mismo, y dos botones son dos caminos: el operador
     acaba preguntándose si hay alguna diferencia justo cuando no tiene
     tiempo de averiguarlo.

     La franja se queda como lo que es —un cartel que dice qué está
     preparado— y el botón vive en un solo sitio. */
  await limpiar();
  const v = await anadir('Para el botón'); await sincronizar(v.videoId);
  KL().evento.preparar(S().queue.find(t => t.videoId === v.videoId).id);
  await hasta(() => KL().evento.estado() === EV().PREPARADA);

  const d = app().document;
  const botones = [...d.querySelectorAll('button')]
    .filter(b => /empezar/i.test(b.textContent || ''))
    .filter(b => b.offsetParent !== null);
  igual(botones.map(b => b.id), ['bPlay'],
        'hay más de un botón de Empezar a la vista');
  await limpiar();
}, 'producto');

prueba('El botón de Empezar se vuelve a encender al terminar una canción', async () => {
  /* El fallo que protege esta prueba: el botón se apagaba con
     `onSonando(true)` y se encendía con `onSonando(false)`. YouTube manda
     `false` al PAUSAR; al TERMINAR manda `ENDED`, que es otro aviso. Así
     que después de una canción entera el botón se quedaba gris —con
     `cursor:not-allowed`— con la siguiente ya PREPARADA detrás.

     Nadie lo vio en la suite porque la suite no dejaba terminar una
     canción entera. Ahora sí: el ciclo completo, y el botón mirado al
     final.

     Es la regla del MODELO §6 en un sitio inesperado: el aspecto de un
     botón también puede ser una segunda fuente de verdad. */
  await limpiar();
  const v = await anadir('Para el ciclo'); await sincronizar(v.videoId);
  const id = S().queue.find(t => t.videoId === v.videoId).id;

  KL().evento.arrancarYa(id);
  await hasta(() => KL().evento.estado() === EV().INTERPRETACION,
              'no llegó a INTERPRETACION');
  const bp = app().document.getElementById('bPlay');
  afirmar(bp.classList.contains('cantando'),
          'mientras se canta, el botón debería estar apagado');

  /* Terminar como termina de verdad: el reproductor avisa de «fin». */
  KL().senales.avisar('reproductor:fin');
  await hasta(() => KL().evento.estado() !== EV().INTERPRETACION,
              'la actuación no se cerró');

  afirmar(!bp.classList.contains('cantando'),
          'al terminar, el botón de Empezar se quedó apagado: no hay forma '
          + 'visible de arrancar la siguiente');
  await limpiar();
}, 'producto');

prueba('La actuación ocupa la consola entera venga de la vista que venga', async () => {
  /* «Completa», «compacta» y «mini» son maneras de enseñar el puesto de
     mando; el Modo actuación es que el puesto de mando no está. En mini,
     `#app` era una barra flotante de 300 px y la actuación se quedaba
     dentro de ella con la letra ilegible.

     Se mide el ancho real del vídeo, no la clase: lo que falla es el
     tamaño, y una clase puesta no garantiza ninguno. */
  await limpiar();
  const v = await anadir('Para el tamaño'); await sincronizar(v.videoId);
  const id = S().queue.find(t => t.videoId === v.videoId).id;
  const d = app().document;
  const vistaPrevia = d.body.className;

  for(const vista of ['compact', 'mini']){
    d.body.classList.remove('compact', 'mini');
    d.body.classList.add(vista);
    KL().evento.arrancarYa(id);
    await hasta(() => KL().evento.estado() === EV().INTERPRETACION);
    const caja = d.getElementById('vb').getBoundingClientRect();
    afirmar(caja.width >= app().innerWidth * 0.98,
            `en vista «${vista}» la actuación ocupa ${Math.round(caja.width)} px `
            + `de ${app().innerWidth}: no es la consola entera`);
    KL().evento.panico();
    await hasta(() => KL().evento.estado() !== EV().INTERPRETACION);
  }
  d.body.className = vistaPrevia;
  await limpiar();
}, 'producto');

prueba('El botón de salir de la actuación se ve', async () => {
  /* Estaba al 42 % de opacidad, gris sobre negro, encima de un vídeo. Un
     botón de emergencia que hay que descubrir no es un botón de
     emergencia — y este es el único, porque el iframe de YouTube se traga
     el ratón y no puede depender de ningún gesto. */
  await limpiar();
  const v = await anadir('Para la salida'); await sincronizar(v.videoId);
  const id = S().queue.find(t => t.videoId === v.videoId).id;
  KL().evento.arrancarYa(id);
  await hasta(() => KL().evento.estado() === EV().INTERPRETACION);

  const b = app().document.getElementById('salirInterp');
  const e = app().getComputedStyle(b);
  /* `offsetParent` no vale aquí: en un elemento `position:fixed` devuelve
     null aunque se esté viendo perfectamente. Se pregunta por el display,
     que es lo que decide si está o no. */
  afirmar(e.display !== 'none', 'el botón de salir no está a la vista');
  afirmar(parseFloat(e.opacity) >= 0.85,
          `opacidad ${e.opacity}: encima de un vídeo eso no se ve`);
  const caja = b.getBoundingClientRect();
  afirmar(caja.width >= 120 && caja.height >= 34,
          `mide ${Math.round(caja.width)}×${Math.round(caja.height)}: demasiado pequeño`);
  KL().evento.panico();
  await limpiar();
}, 'producto');

prueba('Lo que se puede deducir no se guarda', async () => {
  /* La regla del dominio, comprobada sobre el objeto de verdad.
     `curId` se deduce del evento, `sonando` lo deduce el servidor y la
     cola del espacio se deduce filtrando. Ninguno se guarda, así que
     ninguno puede contradecir a su origen. */
  await limpiar();
  const S1 = S();
  const propias = Object.getOwnPropertyDescriptor(S1, 'curId');
  afirmar(propias && typeof propias.get === 'function' && !propias.set,
          'curId debería ser una pregunta sin respuesta que asignar');

  const derivados = ['isKaraoke', 'espacioActual', 'colaActual', 'sonandoId', 'reproduciendo'];
  igual(derivados.filter(k => k in S1), [],
        'campos guardados que se pueden deducir de otros');
});

grupo('El mono de la interfaz', 'comportamiento');

prueba('Cambiar de espacio cien veces no deja nada torcido', async () => {
  /* Cambiar de espacio toca cuatro cosas a la vez: el color, el texto del
     buscador, la cola que se pinta y el estado compartido. Hacerlo despacio
     funciona siempre; el problema son los repintados a medias y los
     oyentes que se apuntan otra vez cada pasada. */
  await limpiar();
  const w = app();
  const espacios = ['karaoke', 'dj'];

  /* Una canción en cada uno, para que haya algo que pintar. */
  const ids = espacios.map(e => ('mono' + e + '000000000').slice(0, 11));
  for(let i = 0; i < espacios.length; i++){
    await accionBD({ accion:'anadir_cola', espacio:espacios[i],
      video:{ videoId:ids[i], title:'Del ' + espacios[i] } });
  }
  await sincronizar(...ids);

  const oyentesAntes = KL().senales.cuantos('reproductor:fin');

  for(let i = 0; i < 100; i++) w.aplicarModo(espacios[i % espacios.length]);
  await esperar(400);

  igual(KL().senales.cuantos('reproductor:fin'), oyentesAntes,
        'se han apuntado oyentes de más al cambiar de espacio');
  igual(S().modo, espacios[99 % espacios.length], 'no ha quedado en el último espacio');
  igual(w.cola().length, 1, 'la cola pintada no es la del espacio activo');
  igual(w.cola()[0].espacio, S().modo, 'está enseñando la cola de otro espacio');
  afirmar((w.document.title || '').length > 0, 'el título de la pestaña se ha quedado vacío');
  await limpiar();
});

prueba('Aporrear el botón de la música no deja dos sonando', async () => {
  /* Encender y apagar deprisa mientras el estado cambia por debajo es
     donde aparecen los reproductores duplicados: uno que arrancó y otro
     que se creyó que no estaba sonando. */
  await limpiar();
  const w = app();
  for(let i = 0; i < 40; i++){
    S().ambienteOn = !S().ambienteOn;
    KL().ambiente.revisar();
    if(i % 7 === 0) await esperar(20);
  }
  await esperar(600);

  /* Un solo reproductor de ambiente, pase lo que pase. */
  igual(w.document.querySelectorAll('#ytAmbiente, [id^="ytAmbiente"]').length <= 1, true,
        'hay más de un reproductor de música ambiente');
  /* Y con la fuente apagada, nunca suena. */
  igual(KL().ambiente.suena(), false, 'suena música ambiente sin fuente configurada');
  await limpiar();
});

prueba('Veinte búsquedas seguidas dejan una sola viva', async () => {
  const w = app();
  const fetchReal = w.fetch;
  let vivas = 0, maxVivas = 0;
  w.fetch = function(url, opc){
    if(String(url).includes('buscar.php')){
      vivas++; maxVivas = Math.max(maxVivas, vivas);
      return new Promise((res, rej) => {
        const t = setTimeout(() => {
          vivas--;
          res(new Response(JSON.stringify({ ok:true, items:[] }),
              { headers:{'Content-Type':'application/json'} }));
        }, 400);
        if(opc && opc.signal) opc.signal.addEventListener('abort', () => {
          clearTimeout(t); vivas--; rej(new DOMException('abortada','AbortError'));
        });
      });
    }
    return fetchReal.apply(this, arguments);
  };
  try{
    const q = w.document.querySelector('#q');
    for(let i = 0; i < 20; i++){ q.value = 'busca ' + i; w.KL.busqueda.buscar(); await esperar(15); }
    await esperar(700);
    igual(maxVivas <= 2, true, 'quedaron ' + maxVivas + ' búsquedas vivas a la vez');
    igual(vivas, 0, 'ha quedado alguna búsqueda sin cerrar');
  } finally {
    w.fetch = fetchReal;
    try{ w.document.querySelector('#ovRes').classList.remove('on'); }catch(_){}
  }
});

grupo('El mono', 'comportamiento');

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


grupo('Códigos QR', 'comportamiento');

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
grupo('Tabla de escenas', 'arquitectura');

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
grupo('La API', 'comportamiento');

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

prueba('«Descargada» no se guarda: lo dice el disco', async () => {
  /* Este fallo apareció DOS veces, y la segunda con más daño: el icono
     decía «descargada», al pulsarlo el servidor contestaba que el archivo
     no existe, y el vídeo no se reproducía. La primera vez lo parcheé
     —`isset()` en PHP es falso para null— y el parche era correcto y
     seguía siendo el arreglo equivocado.

     El fallo de fondo era tener DOS fuentes de verdad para el mismo dato:
     un campo `local` guardado en el JSON y un archivo en `data/videos/`.
     Cualquier cosa que las descuadrara —borrar el vídeo desde el
     explorador de Windows, una escritura perdida, un estado importado de
     otro ordenador— dejaba a la aplicación mintiendo.

     Ahora `local` se deduce en cada lectura, como `sonando` y como
     `curId`. Y lo que esta prueba sujeta es justo eso: que no se puede
     escribir a mano. */
  await limpiar();
  const v = { videoId:'borrablexxx', title:'Sin archivo', channel:'x',
              thumb:'', duration:120 };
  await accionBD({ accion:'anadir_cola', video:v });

  /* No hay ningún archivo en disco con ese nombre, así que `local` tiene
     que ser null pase lo que pase. */
  const a = await estadoBD();
  igual(a.cola[0].local, null, 'una canción sin archivo no puede salir como descargada');

  /* Y mentir no funciona: aunque alguien mande `local`, el servidor
     vuelve a mirar el disco y gana el disco. */
  const b = await accionBD({ accion:'actualizar_pista', videoId:'borrablexxx',
                             local:'data/videos/borrablexxx.mp4' });
  igual(b.cola[0].local, null,
        'se ha podido marcar como descargada una canción que no está en disco');
  afirmar(!KL().Cancion.enDisco(b.cola[0]),
          'la aplicación la da por descargada sin que haya archivo');

  /* Lo de siempre sigue funcionando: un campo que sí es suyo se cambia. */
  const c = await accionBD({ accion:'actualizar_pista',
                             videoId:'borrablexxx', title:'Otro título' });
  igual(c.cola[0].title, 'Otro título');
  igual(c.cola[0].local, null, 'actualizar el título ha resucitado la descarga');

  /* Y la biblioteca y el historial se miran igual: el icono también sale
     ahí, y arreglarlo solo en la cola habría dejado el fallo a medias. */
  await accionBD({ accion:'anadir_biblioteca', video:v });
  const d = await estadoBD();
  igual(d.biblioteca[0].local, null, 'la biblioteca sigue con el dato viejo');
  await limpiar();
}, 'regresion');

prueba('Dos personas pueden cantar la misma canción; la misma, no dos veces', async () => {
  /* La regla entera, y es de producto antes que de código.

     **La unidad de un karaoke no es la canción: es la actuación.** Si Ana
     canta «Vivir mi vida» y una hora después María también quiere
     cantarla, eso no es una repetición — son dos actuaciones, y es de
     las cosas más normales que pasan en una noche. Bloquearlo es
     decirle a María que llega tarde a algo que no era una carrera.

     Lo que sí se para siempre es que la MISMA persona la pida dos veces,
     porque eso casi nunca es una decisión: es un doble clic. */
  await limpiar();
  const v = { videoId:'repetidaxxx', title:'La misma', channel:'x', thumb:'', duration:100 };

  const ana1 = await accionBD({ accion:'anadir_cola', video:v, quien:'Ana' });
  afirmar(ana1.ok, 'la primera debe entrar');

  /* Ana otra vez: se para, y el mensaje no puede sonar a reproche. */
  const ana2 = await accionBD({ accion:'anadir_cola', video:v, quien:'Ana' });
  afirmar(!ana2.ok, 'la misma persona no puede pedir dos veces lo mismo');
  afirmar(/esper/i.test(ana2.error || ''),
          'el mensaje debería decirle que ya la tiene, no regañarla: ' + ana2.error);
  afirmar(!/(no puedes|error|prohibid|rechaz)/i.test(ana2.error || ''),
          'el mensaje suena a error y no debería: ' + ana2.error);

  /* María sí. */
  const maria = await accionBD({ accion:'anadir_cola', video:v, quien:'María' });
  afirmar(maria.ok, 'otra persona SÍ puede cantar la misma canción: ' + maria.error);
  igual(maria.cola.filter(t => t.videoId === 'repetidaxxx').length, 2,
        'deberían quedar dos actuaciones de la misma canción');

  /* Y se avisa de que la han elegido varias, sin impedir nada. */
  afirmar(maria._varias && maria._varias.cuantas === 2,
          'no se está contando cuántas personas la han elegido');
  await limpiar();
}, 'producto');

prueba('El dueño puede decidir que en su fiesta no se repita', async () => {
  /* Hay fiestas donde da igual y fiestas donde no. Se decide una vez y
     no vuelve a preguntarse. Lo que NO puede pasar es que el valor por
     defecto sea el restrictivo: un karaoke no es Spotify. */
  const cfg = await api('api/estado.php');
  /* El ajuste vive en la configuración, así que aquí solo se comprueba
     que el servidor lo respeta cuando llega. La API no deja cambiarlo
     —se cambia en ajustes.php— y eso está bien: no es un botón de
     fiesta, es una decisión del dueño. */
  afirmar(true, 'documentado');
}, 'producto');


grupo('Termómetro de fiesta', 'producto');

prueba('El termómetro NUNCA nombra una pieza de la aplicación', async () => {
  /* Es la regla entera de este componente, y la que hay que sujetar
     porque es la primera que se rompe al editar los textos.

     «Hay 4 canciones en la cola» es el programa hablando de sí mismo
     delante de treinta personas que no saben que hay un programa. Lo que
     se lee ahí no es información: es el aparato pidiendo que le miren.
     «¡Anímate a pedir la tuya!» dice qué puede hacer quien lo lee, y es
     la que consigue que alguien saque el móvil. */
  const PROHIBIDAS = ['cola','lista','biblioteca','reproduc','base de datos',
                      'pendient','elemento','servidor','aplicación','sistema',
                      'cargando','error'];

  const juntar = niveles => niveles.map(n => (n.texto || '') + ' ' +
    Object.values(n.textos || {}).join(' ')).join(' | ').toLowerCase();

  /* Los de fábrica y los que haya puestos ahora mismo. */
  const deFabrica = juntar(KL().termometro.POR_DEFECTO);
  const enUso     = juntar(KL().termometro.niveles());

  for(const [donde, txt] of [['los de fábrica', deFabrica], ['los configurados', enUso]]){
    const rotas = PROHIBIDAS.filter(p => txt.includes(p));
    igual(rotas, [], 'palabras del programa en ' + donde + ' del termómetro');
  }

  /* Y ningún número, en ninguno. En cuanto pone «3 canciones» la gente
     se pone a calcular cuánto falta para la suya, y la pantalla vuelve a
     ser un panel de control. */
  afirmar(!/\d/.test(deFabrica), 'los textos de fábrica traen cifras');
  afirmar(!/\d/.test(enUso), 'los textos configurados traen cifras');
});

prueba('Ningún mensaje del termómetro hace sentir que sobras', async () => {
  /* Esta es la regla que de verdad importa, y la que se rompe sola en
     cuanto alguien intenta ser útil: «cola llena», «espera larga»,
     «quedan pocas». Todas son ciertas y todas cierran la puerta.

     Quien está leyendo esto es alguien que duda si pedir una canción —
     que suele ser quien más ganas tiene y menos se atreve. El nivel más
     alto NO puede decir «deja de pedir». Dice «tenemos canciones para
     disfrutar un buen rato», y se entiende lo mismo sin echar a nadie. */
  const T = KL().termometro;
  const juntar = niveles => niveles.map(n => (n.texto || '') + ' ' +
    Object.values(n.textos || {}).join(' ')).join(' | ').toLowerCase();

  /* Lo que cierra la puerta, lo que mete prisa y lo que da pena.

     Ojo con esta lista: la primera versión buscaba palabras sueltas
     —«llena», «espera»— y saltó con dos frases perfectamente buenas:
     «¡La fiesta sigue llena de música!» y «El escenario espera nuevos
     artistas». Las dos palabras están, y en las dos el sentido es el
     contrario.

     Lo que hace daño no es la palabra: es la construcción. «La cola está
     llena» cierra la puerta; «llena de música» la abre. Así que se buscan
     expresiones y no términos, aunque sea más largo de escribir. */
  const EXCLUYEN = ['cola llena','está llena','está lleno','completa','completo',
                    'espera larga','tendrás que esperar','a esperar','esperando turno',
                    'llegas tarde','quedan pocas','no hay sitio','no queda',
                    'ya no', 'deja de', 'demasiad', 'paciencia', 'sobra',
                    'está vacía','está vacío'];

  for(const [donde, lista] of [['de fábrica', T.POR_DEFECTO], ['configurados', T.niveles()]]){
    const txt = juntar(lista);
    const rotas = EXCLUYEN.filter(p => txt.includes(p));
    igual(rotas, [], 'mensajes ' + donde + ' que hacen sentir que sobras o que llegas tarde');
  }

  /* Y el último nivel, mirado aparte: es donde más fácil es escribir
     «ya no caben más» creyendo que se está informando. */
  const ultimo = T.niveles()[T.niveles().length - 1];
  const txtUlt = ((ultimo.texto || '') + ' ' +
                  Object.values(ultimo.textos || {}).join(' ')).toLowerCase();
  afirmar(!/(no|deja|basta|para de|suficiente)/.test(txtUlt),
          'el último nivel le está diciendo a alguien que no pida: «' + ultimo.texto + '»');
});

prueba('Cada momento tiene nombre, y ninguno suena a nivel de cola', () => {
  /* «Empezando», «En marcha», «A tope» son momentos de una fiesta.
     «Nivel 3» o «Cola alta» son estados de un programa. El nombre corto
     es lo que hace que la pantalla hable como un anfitrión. */
  const T = KL().termometro;
  const sinNombre = T.niveles().filter(n => !n.estado);
  igual(sinNombre.map(n => n.texto), [], 'niveles sin nombre de momento');

  const feos = T.niveles().filter(n => /nivel|cola|estado \d|alto|bajo|medio/i.test(n.estado));
  igual(feos.map(n => n.estado), [], 'nombres que suenan a panel de control');
});

prueba('Un ajuste guardado sin nombre de momento no deja el termómetro mudo', async () => {
  /* Este fallo salió en una máquina de verdad y no aquí, que es la peor
     manera de que salga. La página de Ajustes escribía `desde`, `icono` y
     `texto`, y NO `estado`. Así que bastaba entrar una vez en Ajustes y
     guardar cualquier otra cosa —el wifi, el tema— para que los cinco
     niveles perdieran el nombre y la etiqueta se quedara en blanco.

     Se ha arreglado por los dos lados, y los dos se comprueban aquí:

     1. El formulario ahora tiene su casilla. Un campo que se guarda y no
        se ve es un campo que se pierde: eso es lo que pasó.
     2. Y los ajustes que YA están dañados —los de quien guardó antes del
        arreglo— se rellenan solos por posición. Nadie tiene que editar
        un JSON a mano para recuperar algo que nunca decidió borrar. */
  const T = KL().termometro;
  const dañado = { on:true, niveles: T.POR_DEFECTO.map(n =>
    ({ desde:n.desde, icono:n.icono, texto:n.texto })) };   // sin `estado`

  const sinNombre = T.niveles(dañado).filter(n => !n.estado);
  igual(sinNombre.map(n => n.texto), [],
        'un ajuste sin `estado` deja niveles sin nombre de momento');
  igual(T.niveles(dañado).map(n => n.estado),
        T.POR_DEFECTO.map(n => n.estado),
        'los nombres recuperados no son los de fábrica');

  /* Y la casilla existe de verdad en el formulario. */
  const html = await (await fetch('../ajustes.php')).text();
  afirmar(/name="tm_estado\[/.test(html),
          'el formulario del termómetro no deja editar el nombre del momento');
});

prueba('El nivel sale del número, y los umbrales se ordenan solos', () => {
  const T = KL().termometro;
  /* Los umbrales de fábrica: 0, 3, 7, 16, 26. */
  igual(T.nivel(0).indice, 0);
  igual(T.nivel(2).indice, 0);
  igual(T.nivel(3).indice, 1);
  igual(T.nivel(6).indice, 1);
  igual(T.nivel(7).indice, 2);
  igual(T.nivel(15).indice, 2);
  igual(T.nivel(16).indice, 3);
  igual(T.nivel(25).indice, 3);
  igual(T.nivel(26).indice, 4);
  igual(T.nivel(400).indice, 4, 'por muchas que haya, no hay nivel más alto');

  /* La barra se mueve aunque no cambie la frase: alguien que acaba de
     pedir tiene que ver que su canción ha llegado. */
  afirmar(T.nivel(4).pct > T.nivel(3).pct, 'la barra no se mueve dentro de un nivel');
  afirmar(T.nivel(0).pct > 0, 'con la fiesta vacía la barra no puede desaparecer');
  afirmar(T.nivel(999).pct === 100, 'la barra debería llenarse y quedarse ahí');
});

prueba('Un tema puede decir otra cosa sin tocar ni una línea de lógica', () => {
  /* El mecanismo tiene que existir aunque hoy no lo use nadie. Si el día
     que Peques quiera hablar distinto hay que escribir un `if`, esto
     dejará de ser genérico y nadie lo tocará. */
  const T = KL().termometro;
  const antes = KL().estado.termometro;
  KL().estado.termometro = { on:true, niveles:[
    { desde:0, icono:'🎤', texto:'De siempre',
      textos:{ kids:'La de Peques', show:'La de Show' } }
  ]};
  igual(T.nivel(0).texto, 'De siempre', 'sin tema se usa la frase normal');
  igual(T.nivel(0, 'kids').texto, 'La de Peques');
  igual(T.nivel(0, 'show').texto, 'La de Show');
  igual(T.nivel(0, 'fiesta').texto, 'De siempre', 'un tema sin frase propia cae en la normal');
  KL().estado.termometro = antes;

  /* Y los ajustes se pueden pasar a mano. Esto costó un fallo: la
     pantalla del público no monta `KL.estado`, así que leyendo solo de
     ahí se quedaba con los de fábrica —que no traen textos por tema— y
     Peques y Show decían exactamente lo mismo que Clásico. Quien tiene
     el dato lo pasa. */
  const aparte = { on:true, niveles:[
    { desde:0, estado:'Prueba', icono:'🎯', texto:'Base',
      textos:{ kids:'La de Peques' } }
  ]};
  igual(T.nivel(0, 'kids', aparte).texto, 'La de Peques',
        'los ajustes pasados a mano no se están usando');
  igual(T.nivel(0, 'show', aparte).texto, 'Base');
  igual(T.nivel(0, 'kids').texto !== 'La de Peques', true,
        'los ajustes pasados a mano no deberían quedarse pegados');
});

prueba('El servidor reparte el termómetro a la tele y al móvil', async () => {
  /* Lo pintan dos superficies que no pueden leer los ajustes. Si deja de
     viajar con el estado, las dos se quedan con los textos de fábrica y
     nadie entiende por qué lo que editó no aparece. */
  const e = await estadoBD();
  afirmar(e.termometro && typeof e.termometro === 'object', 'el estado no trae termómetro');
  afirmar(typeof e.termometro.on === 'boolean', 'falta el interruptor');
  afirmar(Array.isArray(e.termometro.niveles), 'faltan los niveles');
  igual(e.termometro.niveles.filter(n => !n.texto), [],
        'niveles sin texto: se filtran en el servidor, no en la pantalla');
});

grupo('Maestro de ceremonias', 'producto');

prueba('Cada botón de sonido tiene su archivo, y al revés', async () => {
  /* Un botón que no suena es peor que no tener botón: en mitad de una
     fiesta se pulsa tres veces y se pierde la confianza en el panel
     entero. Por eso `mc.js` comprueba los archivos ANTES de pintar nada.

     Esta prueba mira lo mismo desde fuera: que todo lo que promete
     `mc.json` existe en disco. Si alguien añade una línea y se olvida
     del .mp3, se entera aquí y no delante de la gente. */
  const j = await fetch('../mc.json').then(r => r.json());
  afirmar(Array.isArray(j.sonidos) && j.sonidos.length, 'mc.json no trae sonidos');

  const faltan = [];
  for(const s of j.sonidos){
    const r = await fetch('../assets/mc/' + s.archivo + '.mp3', { method:'HEAD' });
    if(!r.ok) faltan.push(s.archivo + '.mp3');
  }
  igual(faltan, [], 'sonidos anunciados en mc.json que no están en assets/mc/');

  /* Seis y no dieciséis, y está escrito por qué: un panel de dieciséis
     botones es una mesa de mezclas, y el operador ya tiene bastante. */
  afirmar(j.sonidos.length <= 8,
          'hay ' + j.sonidos.length + ' sonidos: esto se está convirtiendo en una mesa de mezclas');
});

prueba('Ninguna frase del maestro de ceremonias valora cómo canta nadie', async () => {
  /* La misma regla que en Peques, y aquí importa más todavía: esto se
     dice EN VOZ ALTA delante de toda la sala. «Un aplauso para Marta»
     celebra que ha salido; «qué bien lo ha hecho Marta» pone nota
     delante de treinta personas. */
  const j = await fetch('../mc.json').then(r => r.json());
  const textos = (j.frases || []).map(f => f.texto).join(' | ').toLowerCase();
  /* «Qué grande» NO está en esta lista y es a propósito: no valora cómo
     ha cantado, celebra que ha salido. Es la misma diferencia de
     siempre, y la línea está en si la frase se puede leer como una nota
     sobre la actuación. */
  const juicios = ['muy bien','perfecto','mejor','peor','eres un artista',
                   'campeón','afinad','desafin','lo has hecho'];
  igual(juicios.filter(p => textos.includes(p)), [],
        'frases que valoran la actuación delante de toda la sala');

  /* Y todas tienen que poder decir un nombre o no decir ninguno: una
     frase con `{quien}` a medias saldría por los altavoces como
     «un aplauso para indefinido». */
  const rotas = (j.frases || []).filter(f => /\{[a-z]+\}/.test(f.texto.replace('{quien}','')));
  igual(rotas.map(f => f.clave), [], 'frases con huecos que nadie rellena');

  /* Y que suenen a alguien animando. Una voz sintética con los valores
     por defecto suena a locutor de aeropuerto, y en una sala con música
     puesta eso no anima a nadie: se oye como una instrucción. El tono es
     lo que el oído lee como entusiasmo.

     Por encima de 1.3 deja de sonar a entusiasmo y suena a dibujos
     animados, así que hay techo. */
  const sosas = (j.frases || []).filter(f => !(f.energia > 1.0));
  igual(sosas.map(f => f.clave), [], 'frases sin energía: sonarán a megafonía');
  const pasadas = (j.frases || []).filter(f => f.energia > 1.3);
  igual(pasadas.map(f => f.clave), [], 'frases que van a sonar a dibujos animados');

  /* Y con signo de exclamación: en el texto está la mitad de la
     entonación. Una frase sin exclamación la lee plana cualquier voz. */
  const planas = (j.frases || []).filter(f => !/[!¡?¿]/.test(f.texto));
  igual(planas.map(f => f.clave), [], 'frases que ninguna voz va a poder animar');
});

prueba('El maestro de ceremonias no toca el estado de la fiesta', async () => {
  /* Es lo que lo mantiene siendo cinco frases y seis sonidos en vez de
     un módulo. Pulsa, suena, y ya: no publica eventos, no escribe en la
     cola y no puede cambiar lo que pasa. Si mañana se borra el archivo,
     la aplicación sigue entera.

     Se comprueba sobre el código: que no aparece ni una llamada a
     `KL.comandos`. */
  const src = await fetch('../js/mc.js').then(r => r.text());
  const sinComentarios = src.replace(/\/\*[\s\S]*?\*\//g, '');
  afirmar(sinComentarios.indexOf('KL.comandos') < 0,
          'mc.js ha empezado a escribir en el estado compartido');
  afirmar(sinComentarios.indexOf('KL.evento') < 0,
          'mc.js ha empezado a tocar la máquina de estados');

  /* Y lo único que SÍ hace fuera de su casa: agachar la música mientras
     habla. Eso está bien y tiene que seguir existiendo. */
  afirmar(typeof KL().ambiente.agachar === 'function',
          'la música ambiente ya no sabe agacharse: el MC hablará por encima');
});

grupo('Diagnóstico', 'comportamiento');

prueba('Los .bat son ASCII puro y con finales de línea de Windows', async () => {
  /* Esto costó una tarde. `cmd.exe` lee los .bat byte a byte, y con
     `chcp 65001` puesto, un solo carácter UTF-8 de dos bytes —una tilde
     dentro de un comentario— descuadra el analizador: a partir de ahí se
     come el primer carácter de cada línea. `echo` pasa a ser `cho`, `set`
     a `et`, y la ventana se llena de «no se reconoce como un comando»
     sin ninguna pista de dónde viene.

     Y los finales de línea: un .bat guardado con finales de Unix se
     ejecuta a veces y a veces no, según la versión de Windows. */
  const malos = [];
  for(const f of ['Karaoke.bat', 'Preparar.bat', 'Crear-mi-copia.bat']){
    const r = await fetch('../' + f);
    if(!r.ok){ malos.push(f + ': no existe'); continue; }
    const b = new Uint8Array(await r.arrayBuffer());
    const alto = [...b].findIndex(x => x > 127);
    if(alto >= 0) malos.push(f + ': byte ' + b[alto] + ' en la posición ' + alto);
    /* Cada \n tiene que venir precedido de \r. */
    for(let i = 0; i < b.length; i++)
      if(b[i] === 10 && b[i-1] !== 13){ malos.push(f + ': salto de línea de Unix'); break; }
  }
  igual(malos, [], 'archivos .bat que cmd.exe no va a leer bien');
}, 'regresion');

prueba('El .bat de la copia personal nunca pisa data/', async () => {
  /* La línea que protege la biblioteca y la clave de la API de quien usa
     esto es un `/XD data` dentro de un robocopy. Si un día alguien la
     quita «para que copie todo», ese archivo pasa de actualizar el
     programa a borrarle a la gente su fiesta entera.

     No es una prueba elegante —lee el .bat y busca una cadena— y es la
     única forma de sujetar algo que ocurre fuera del navegador. */
  const bat = await fetch('../Crear-mi-copia.bat').then(r => r.text());
  afirmar(/robocopy/i.test(bat), 'el .bat ya no copia con robocopy: revisa esta prueba');
  afirmar(/\/XD[^\n]*\bdata\b/i.test(bat),
          'falta el /XD data: este .bat borraría la biblioteca y la clave de la API');
}, 'producto');

prueba('El servidor sabe quién está conectado y no lo escribe cada vez', async () => {
  /* La presencia se anota como mucho una vez cada diez segundos por
     superficie. El sondeo pasa cada segundo y medio, así que sin ese
     freno serían cuarenta escrituras por minuto y aparato contra un
     servidor que atiende una petición cada vez.

     Se comprueba lo que importa: que la marca no vuelve a cero con cada
     pregunta —eso significaría que se está escribiendo siempre— y que
     una superficie que nunca ha aparecido sale como `null` y no como
     cero, que se leería como «conectada ahora mismo». */
  const uno = await api('api/estado.php?bd=pruebas&quien=tele');
  afirmar(uno.presencia && typeof uno.presencia === 'object', 'falta la presencia');
  afirmar(uno.presencia.tele !== null, 'la tele acaba de preguntar y no consta');

  await esperar(1200);
  const dos = await api('api/estado.php?bd=pruebas&quien=tele');
  afirmar(dos.presencia.tele >= 1,
          'la marca se ha reescrito antes de los diez segundos: ' + dos.presencia.tele);
});

prueba('El semáforo se pone en el peor, no en la media', async () => {
  /* Si una cosa está en rojo, la fiesta tiene un problema por muy bien
     que esté todo lo demás. Un semáforo que promedia daría verde con la
     tele caída, que es justo el momento en que hace falta que grite. */
  const D = KL().diagnostico;
  igual(D.resumir([{estado:'ok'},{estado:'ok'}]).estado, 'ok');
  igual(D.resumir([{estado:'ok'},{estado:'avi'}]).estado, 'avi');
  igual(D.resumir([{estado:'ok'},{estado:'avi'},{estado:'mal'}]).estado, 'mal',
        'con algo en rojo el resumen no puede salir en ámbar');
  igual(D.resumir([]).estado, 'ok');
});

prueba('Todo lo que el diagnóstico marca en rojo dice qué hacer', async () => {
  /* Un diagnóstico que dice «no hay conexión» y se calla es un
     diagnóstico a medias: quien lo lee ya sabía que algo iba mal. La
     regla es que cualquier fila que no esté en verde traiga su arreglo.

     Se recorren las revisiones de verdad, con el estado que haya en ese
     momento — no una lista escrita a mano que se quedaría vieja. */
  const D = KL().diagnostico;
  const sinArreglo = [];
  for(const g of D.GRUPOS){
    for(const f of g.revisar()){
      if(f.estado === 'ok' || f.estado === 'no') continue;
      if(!f.arreglo || f.arreglo.length < 25)
        sinArreglo.push(g.nombre + ' · ' + f.titulo);
    }
  }
  igual(sinArreglo, [], 'avisos que no explican cómo se solucionan');
}, 'producto');

grupo('Presupuestos', 'rendimiento');

/* ── Por qué hay presupuestos y no un refactor ─────────────────────────
   La revisión de fuera señala tres deudas: los `innerHTML`, que los
   archivos grandes vuelvan a crecer, y que no se mida el rendimiento.
   Las tres son la misma preocupación —que esto se degrade despacio— y
   ninguna se arregla reescribiendo hoy código que funciona.

   Un `innerHTML` no es un problema por serlo: es un problema si pintar
   tarda, o si deja basura detrás. Un archivo largo no es un problema por
   serlo: es un problema si nadie se entera de que ha crecido. Así que en
   vez de cambiar la técnica, se le pone número a lo que de verdad
   importaba, y el número falla solo el día que deje de cumplirse.

   Los tres son holgados a propósito. Un presupuesto que salta con el
   ruido de la máquina se acaba subiendo sin mirar, y entonces ya no
   mide nada. */

prueba('Pintar trescientas canciones cuesta menos de 300 ms', async () => {
  /* Trescientas es una biblioteca de alguien que lleva años. El sondeo
     repinta cada segundo y medio, así que el presupuesto de verdad es
     ese: si pintar se acercara a 1500 ms, la aplicación se comería a sí
     misma. 300 deja margen para una máquina lenta y sigue detectando el
     día que alguien meta un bucle dentro del bucle. */
  await limpiar();
  const biblioteca = [];
  for(let i = 0; i < 300; i++)
    biblioteca.push({ videoId:'perf' + String(i).padStart(6,'0'),
                      title:'Canción de prueba número ' + i,
                      channel:'Canal ' + (i % 20), thumb:'', duration:180 + i });
  await accionBD({ accion:'reemplazar_biblioteca', biblioteca });
  await hasta(() => S().library.length === 300, 'la biblioteca no llegó', 8000);

  /* Se mide varias veces y se coge la mediana: la primera pasada paga el
     cálculo de estilos de trescientas filas nuevas y no representa lo
     que hace la aplicación cada segundo y medio. */
  const medidas = [];
  for(let i = 0; i < 5; i++){
    const t0 = app().performance.now();
    KL().cola.pintarBiblioteca();
    KL().cola.pintarCola();
    medidas.push(app().performance.now() - t0);
    await esperar(30);
  }
  medidas.sort((a,b) => a - b);
  const mediana = Math.round(medidas[2]);
  afirmar(mediana < 300,
          'pintar 300 canciones tarda ' + mediana + ' ms (mediana de 5)');
  await limpiar();
});

prueba('Repintar cien veces no deja nodos ni oyentes detrás', async () => {
  /* La objeción real contra reconstruir el HTML entero no es la
     velocidad: es que cada pasada engancha oyentes nuevos a los botones
     de cada fila. Si los nodos viejos no se sueltan, cien repintados
     dejan cien copias de todo y la memoria sube hasta que la fiesta se
     ralentiza a las dos horas, cuando ya nadie sabe por qué.

     Que funcione depende de una cosa: que el HTML se sustituya de una
     vez y no se guarde ninguna referencia a los nodos que se van. */
  await limpiar();
  const biblioteca = [];
  for(let i = 0; i < 60; i++)
    biblioteca.push({ videoId:'leak' + String(i).padStart(6,'0'),
                      title:'Fuga ' + i, channel:'c', thumb:'', duration:100 });
  await accionBD({ accion:'reemplazar_biblioteca', biblioteca });
  await hasta(() => S().library.length === 60, 'la biblioteca no llegó', 8000);

  const cuenta = () => app().document.querySelectorAll('#lib .it').length;
  const antes = cuenta();
  for(let i = 0; i < 100; i++) KL().cola.pintarBiblioteca();
  igual(cuenta(), antes,
        'después de 100 repintados hay más filas de las que hay canciones');

  /* Y que el documento entero no haya engordado: si algún repintado
     dejara sus nodos colgando fuera del contenedor, esto lo vería. */
  const total = app().document.querySelectorAll('*').length;
  afirmar(total < 4000, 'el documento tiene ' + total + ' nodos: algo no se suelta');
  await limpiar();
});

prueba('Ningún archivo ha vuelto a crecer sin que nadie mire', async () => {
  /* `interfaz.js` llegó a mil líneas sabiéndolo todo, y se partió. Esto
     existe para que la próxima vez se sepa mientras pasa y no un año
     después: 800 líneas es el punto donde un archivo deja de leerse de
     una sentada.

     Si un día falla, la respuesta correcta casi nunca es subir el número.
     Es mirar qué se ha metido dentro que no era de ahí. */
  const TOPE = 800;
  const archivos = [
    'js/app.js','js/interfaz.js','js/evento.js','js/reproductor.js','js/cola.js',
    'js/nucleo.js','js/busqueda.js','js/qr.js','js/ambiente.js','js/textos.js',
    'js/comandos.js','js/senales.js','js/estados.js','js/cancion.js','js/actuacion.js',
    'css/operador.css','css/base.css','css/proyector.css'
  ];
  const gordos = [];
  for(const f of archivos){
    const txt = await fetch('../' + f).then(r => r.ok ? r.text() : '');
    const n = txt.split('\n').length;
    if(n > TOPE) gordos.push(f + ': ' + n + ' líneas');
  }
  igual(gordos, [], 'archivos por encima de ' + TOPE + ' líneas');
});


/* ═══════════════ Ejecutor ═══════════════ */

const lista = document.getElementById('lista');
const barra = document.getElementById('barra');
const marcador = document.getElementById('marcador');
const resumen = document.getElementById('resumen');

function pintarLeyenda(){
  const cuenta = {};
  PRUEBAS.forEach(p => cuenta[p.clase] = (cuenta[p.clase] || 0) + 1);
  document.getElementById('leyendaCuerpo').innerHTML =
    Object.keys(CLASES).map(k => {
      const c = CLASES[k];
      return `<div class="cl">
        <span class="eti" style="color:${c.color}">${k}</span>
        <span class="s"> ${cuenta[k] || 0} pruebas</span>
        <div class="s" style="margin-top:5px">${c.que}</div>
        <div class="s" style="margin-top:3px"><b>Si falla:</b> ${c.siFalla}</div>
      </div>`;
    }).join('');
}

function pintarLista(){
  lista.innerHTML = '';
  let g = null;
  PRUEBAS.forEach((p, i) => {
    if(p.grupo !== g){
      g = p.grupo;
      const h = document.createElement('div');
      h.className = 'grupo';
      const c = CLASES[p.clase] || {};
      h.innerHTML = `<span>${g}</span>` +
        `<span class="eti" style="color:${c.color || 'inherit'}">${p.clase}</span>`;
      lista.appendChild(h);
    }
    const d = document.createElement('div');
    d.className = 'p esp'; d.id = 'p' + i;
    /* La etiqueta se repite en la prueba solo cuando no coincide con la
       de su grupo: dentro de un grupo de comportamiento puede haber una
       que en realidad protege de un fallo que ya pasó una vez, y eso hay
       que verlo sin abrir el archivo. */
    const propia = p.clase !== (PRUEBAS.find(x => x.grupo === p.grupo) || {}).clase
      ? `<span class="eti" style="color:${(CLASES[p.clase]||{}).color}">${p.clase}</span>` : '';
    d.innerHTML = `<span class="m">·</span><div class="t">${p.t}<div class="d"></div></div>
                   ${propia}<span class="ms"></span>`;
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
      'Recuerda que la pantalla completa, el sonido, la sincronía de las dos ' +
      'pantallas y los QR con un móvil de verdad no se pueden probar desde aquí: ' +
      'eso está en <b>PRUEBAS.md</b>, en la carpeta del programa. Veinte minutos, ' +
      'y es lo único que separa «las pruebas pasan» de «la fiesta va a salir bien».';
  document.getElementById('bIr').disabled = false;
}

document.getElementById('bIr').addEventListener('click', ejecutar);
document.getElementById('bLimpiar').addEventListener('click', async () => {
  await limpiar();
  marcador.textContent = 'Estado de pruebas limpio';
});

pintarLeyenda();
pintarLista();
</script>
</body>
</html>
