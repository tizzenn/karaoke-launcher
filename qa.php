<?php
/* ═══════════════════════════════════════════════════════════════════
   qa.php — el panel desde el que se prueba todo

   `pruebas/pruebas.php` corre DENTRO de la aplicación y mide el DOM.
   Los guiones de `pruebas/e2e/` conducen un Chrome de verdad. Falta una
   tercera cosa, y es la que se echó de menos instalando el proyecto en un
   portátil ajeno por primera vez:

   > **Comprobar que la MÁQUINA puede correr esto**, antes de acusar al
   > programa.

   Aquel día salieron dos «fallos» que eran el mismo: PHP se caía y
   `display_errors=Off` lo convertía en una página en blanco. Ninguna
   prueba de las que hay podía verlo, porque todas dan por hecho que la
   aplicación arranca.

   Esta página no prueba la aplicación: prueba **el suelo sobre el que
   se apoya**, y de paso deja el estado en un punto conocido para que las
   pruebas de Chrome no empiecen cada una en un sitio distinto.

   Se puede abrir con la aplicación rota. Es su único requisito de
   verdad, y por eso aquí dentro no se llama a nada que pueda caerse sin
   estar envuelto.
   ═══════════════════════════════════════════════════════════════════ */

declare(strict_types=1);

/* Esta página SÍ enseña sus propios errores. Es la única del proyecto
   que lo hace: es una herramienta de diagnóstico, y una herramienta de
   diagnóstico que se calla no sirve para nada. */
@ini_set('display_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/api/comun.php';

$DATOS = __DIR__ . '/data';

/* ---- Acciones: dejar el estado en un punto conocido ------------------
   Todas por POST. Una dirección que borra la cola al abrirla la dispara
   cualquier cosa que precargue enlaces, y aquí hay botones que tiran
   datos. */
$hecho = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  $a = (string)($_POST['accion'] ?? '');

  /* «Romper» va FUERA del try, y esa no es una sutileza: dentro, el
     `catch (Throwable)` de abajo atrapaba el error y pintaba la página
     normal con un aviso. Es decir, el botón que comprueba la red de
     seguridad **no llegaba a probarla nunca**, y la prueba automática
     lo cazó al primer intento.

     Es el mismo tipo de fallo que viene a proteger: algo que parece
     funcionar porque su síntoma se traga por el camino. */
  if ($a === 'romper') {
    /* Y con los errores apagados, como los tiene una instalación de
       verdad: la gracia es ver EXACTAMENTE lo que vería quien acaba de
       instalar esto, no una versión mejorada para el que programa. */
    @ini_set('display_errors', '0');
    funcion_que_no_existe_a_proposito();
  }

  try {
    switch ($a) {
      case 'estado_limpio':
        modificar_estado('estado', function ($e) {
          $e['cola'] = []; $e['historial'] = [];
          $e['evento'] = evento_inicial();
          $e['calentamiento'] = 0;
          return $e;
        });
        $hecho = 'Cola e historial vacíos, evento en ESPERA.';
        break;

      case 'vaciar_cache':
        require_once __DIR__ . '/api/cache.php';
        $hecho = cache_vaciar() . ' búsquedas en caché borradas.';
        break;

      case 'vaciar_log':
        @unlink($DATOS . '/errores.log');
        $hecho = 'Registro de errores vaciado.';
        break;

    }
  } catch (Throwable $ex) {
    $hecho = 'ERROR: ' . $ex->getMessage();
  }
}

/* ---- Las comprobaciones ---------------------------------------------
   Cada una devuelve [estado, título, detalle]. El estado es 'ok',
   'aviso' o 'mal', y NUNCA se promedian: si algo está en rojo, el
   resumen está en rojo. Un semáforo en ámbar por media de un verde y un
   rojo es un semáforo que miente. */
$C = [];
$chk = function (string $grupo, string $titulo, $estado, string $detalle) use (&$C) {
  $C[$grupo][] = ['estado' => $estado, 'titulo' => $titulo, 'detalle' => $detalle];
};

/* — PHP y sus extensiones — */
$chk('PHP', 'Versión de PHP',
     version_compare(PHP_VERSION, '8.0', '>=') ? 'ok' : 'mal',
     PHP_VERSION . (version_compare(PHP_VERSION, '8.0', '>=')
       ? '' : ' — hace falta 8.0 o superior'));

foreach ([
  'mbstring' => 'Sin ella, guardar los ajustes deja la pantalla en blanco.',
  'curl'     => 'Sin ella no se puede buscar en YouTube.',
  'openssl'  => 'Sin ella no se puede abrir ninguna dirección https.',
  'json'     => 'Sin ella no funciona nada en absoluto.',
] as $ext => $porque) {
  $hay = extension_loaded($ext);
  $chk('PHP', 'Extensión ' . $ext, $hay ? 'ok' : 'mal',
       $hay ? 'cargada'
            : $porque . ' Abre php\php.ini, busca «;extension=' . $ext
              . '» y quítale el punto y coma.');
}

$de = ini_get('display_errors');
$chk('PHP', 'display_errors', 'ok',
     ($de === '' || $de === '0' || strtolower((string)$de) === 'off')
       ? 'Apagado — correcto delante de invitados. Los fallos fatales se '
         . 'ven igual: los recoge api/comun.php y quedan en data/errores.log.'
       : 'Encendido. Va bien para desarrollar; en una fiesta, mejor apagado.');

$cai = (string)ini_get('curl.cainfo');
$chk('PHP', 'Certificados (curl.cainfo)',
     $cai !== '' && is_file($cai) ? 'ok' : 'aviso',
     $cai !== '' && is_file($cai) ? basename($cai)
       : 'Sin certificados, PHP no puede abrir direcciones https y la '
         . 'búsqueda falla culpando a la wifi. Los pone Preparar.bat.');

/* — Carpetas y archivos — */
$chk('Archivos', 'Carpeta data',
     is_dir($DATOS) && is_writable($DATOS) ? 'ok' : 'mal',
     !is_dir($DATOS) ? 'No existe.'
       : (is_writable($DATOS) ? 'Se puede escribir.'
          : 'NO se puede escribir: no se guardará ni un ajuste ni una canción.'));

foreach (['ajustes.json' => false, 'estado.json' => false] as $f => $obligatorio) {
  $ruta = $DATOS . '/' . $f;
  if (!is_file($ruta)) {
    $chk('Archivos', $f, 'ok', 'Todavía no existe; se crea al primer guardado.');
    continue;
  }
  $txt = (string)@file_get_contents($ruta);
  $j   = json_decode($txt, true);
  $chk('Archivos', $f, is_array($j) ? 'ok' : 'mal',
       is_array($j)
         ? number_format(strlen($txt) / 1024, 1) . ' kB, se lee bien'
         : 'ESTÁ CORRUPTO (' . json_last_error_msg() . '). Es la causa '
           . 'típica de que media aplicación salga en blanco. Bórralo y '
           . 'vuelve a empezar, o restaura una copia.');
}

$cfgOk = is_file(__DIR__ . '/api/config.php');
$chk('Archivos', 'api/config.php', $cfgOk ? 'ok' : 'mal',
     $cfgOk ? 'presente' : 'FALTA. Sin él no arranca nada.');

/* Todo lo que index.html carga tiene que existir. Un <script> que da 404
   no da error visible: simplemente falta media aplicación. */
$falta = [];
$html = (string)@file_get_contents(__DIR__ . '/index.html');
if (preg_match_all('/(?:src|href)="((?:js|css)\/[^"]+)"/', $html, $m))
  foreach (array_unique($m[1]) as $rel)
    if (!is_file(__DIR__ . '/' . $rel)) $falta[] = $rel;
$chk('Archivos', 'Lo que carga el operador', $falta ? 'mal' : 'ok',
     $falta ? 'FALTAN: ' . implode(', ', $falta)
            : 'Todos los .js y .css referenciados existen.');

/* — Los formatos de enlace de YouTube —
   Esto se comprueba aquí y no solo en la suite por un motivo concreto:
   **pegar un enlace es el único camino que funciona sin clave de la
   API**. Si un formato deja de reconocerse, quien no tiene clave se
   queda sin ninguna manera de añadir una canción — y eso pasó en una
   instalación nueva.

   YouTube añade parámetros cada temporada. La lista está aquí para que
   añadir uno sea añadir una fila. */
$ENLACES = [
  'https://youtu.be/dQw4w9WgXcQ',
  'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
  'youtube.com/watch?v=dQw4w9WgXcQ',
  'https://youtube.com/shorts/dQw4w9WgXcQ',
  'https://music.youtube.com/watch?v=dQw4w9WgXcQ',
  'https://m.youtube.com/watch?v=dQw4w9WgXcQ',
  'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=30s',
  'https://youtu.be/dQw4w9WgXcQ?si=AbCdEfGh',
  'https://www.youtube.com/v/dQw4w9WgXcQ',
  'HTTPS://YOUTU.BE/dQw4w9WgXcQ',
  'https://www.youtube.com/watch?list=PL1&v=dQw4w9WgXcQ',
  'https://www.youtube.com/live/dQw4w9WgXcQ',
  'https://youtube.com/watch?app=desktop&v=dQw4w9WgXcQ',
  'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
  'dQw4w9WgXcQ',
];
$malos = array_values(array_filter($ENLACES, fn($u) => id_video($u) !== 'dQw4w9WgXcQ'));
$chk('YouTube', 'Formatos de enlace reconocidos', $malos ? 'mal' : 'ok',
     $malos ? 'NO se reconocen: ' . implode(' · ', $malos)
            : count($ENLACES) . ' formatos, todos bien. Pegar un enlace es lo '
              . 'único que funciona sin clave de la API, así que esto importa.');

/* Y que una lista de reproducción se distinga de un vídeo: delante de
   una lista, «sin resultados» no es una respuesta que ayude. */
$chk('YouTube', 'Una lista no se confunde con un vídeo',
     (id_video('https://www.youtube.com/playlist?list=PL123') === null
      && enlace_de_youtube('https://www.youtube.com/playlist?list=PL123')) ? 'ok' : 'mal',
     'Se reconoce como enlace de YouTube pero sin vídeo dentro, para poder decirlo.');

/* — Archivos apartados por venir rotos — */
$rotos = glob($DATOS . '/*.roto-*.json') ?: [];
if ($rotos) {
  $chk('Archivos', 'Archivos apartados por estar corruptos', 'aviso',
       count($rotos) . ': ' . implode(', ', array_map('basename', $rotos))
       . '. La aplicación siguió arrancando con los valores de fábrica y '
       . 'apartó el original en vez de escribir encima. Si te falta algo, '
       . 'está ahí dentro.');
}

/* — Las claves de la API y la cuota — */
$cfg = cfg();
if (is_file(__DIR__ . '/api/claves.php')) {
  require_once __DIR__ . '/api/claves.php';
  $todas = claves_lista($cfg);
  $vivas = claves_disponibles($cfg);
  $chk('YouTube', 'Claves de la API',
       !$todas ? 'aviso' : ($vivas ? 'ok' : 'mal'),
       !$todas
         ? 'Ninguna configurada. Los enlaces pegados sí funcionan: van por '
           . 'oEmbed, que es gratis.'
         : count($vivas) . ' de ' . count($todas) . ' disponibles ahora mismo'
           . ($vivas ? ' (' . implode(', ', array_column($vivas, 'nombre')) . ').'
                     : '. Todas agotadas o desactivadas: la búsqueda por '
                       . 'texto no funcionará hasta mañana o hasta que se '
                       . 'active otra en Ajustes.'));
} else {
  $clave = trim((string)($cfg['api_key'] ?? ''));
  $tieneClave = $clave !== '' && $clave !== 'PON_AQUI_TU_CLAVE';
  $chk('YouTube', 'Clave de la API', $tieneClave ? 'ok' : 'aviso',
       $tieneClave ? 'Configurada (' . substr($clave, 0, 8) . '…). Nunca sale de este ordenador.'
         : 'Sin clave no se puede buscar. Los enlaces pegados sí funcionan: '
           . 'van por oEmbed, que es gratis.');
}

if (is_file(__DIR__ . '/api/cache.php')) {
  require_once __DIR__ . '/api/cache.php';
  $c = cuota_hoy();
  $gastado = (int)$c['unidades'];
  $pct = (int)round($gastado / CUOTA_DIARIA * 100);
  $chk('YouTube', 'Cuota gastada hoy',
       $pct >= 90 ? 'mal' : ($pct >= 60 ? 'aviso' : 'ok'),
       number_format($gastado) . ' de ' . number_format(CUOTA_DIARIA)
       . ' unidades (' . $pct . '%). Cada búsqueda cuesta '
       . (COSTE_BUSCAR + COSTE_VIDEOS) . ', así que caben unas '
       . floor(CUOTA_DIARIA / (COSTE_BUSCAR + COSTE_VIDEOS)) . ' al día.');
  $enCache = count(glob(__DIR__ . '/data/cache/*.json') ?: []);
  $chk('YouTube', 'Caché de búsquedas', 'ok',
       $enCache . ' guardadas · ' . (int)$c['ahorradas']
       . ' búsquedas servidas hoy sin gastar cuota.');
}

/* — Cantar sin internet: yt-dlp y ffmpeg —
   Antes esta página comprobaba PHP y sus extensiones, pero no lo que
   hace falta para descargar: quien tenía yt-dlp mal puesto no se
   enteraba hasta que pulsaba «Descargar» a media fiesta. Se reutiliza
   la misma lógica que ya resuelve la ruta en api/descargar.php, para no
   tener dos maneras de decidir «¿está esto instalado?» que puedan
   discrepar entre sí. */
if (is_file(__DIR__ . '/api/descargar.php')) {
  /* No se incluye descargar.php entero: ese archivo asume una petición
     HTTP con método y cuerpo, y aquí no los hay. Se copia solo la
     función de resolución de ruta, once líneas, para no forzar ese
     archivo a saber que también lo llama un diagnóstico. */
  $rutaBin = function (string $bin): string {
    $candidatos = [$bin, __DIR__ . '/' . $bin];
    if (PHP_OS_FAMILY === 'Windows' && !preg_match('/\.exe$/i', $bin)) {
      $candidatos[] = $bin . '.exe';
      $candidatos[] = __DIR__ . '/' . $bin . '.exe';
    }
    foreach ($candidatos as $c) if (is_file($c) && ($r = realpath($c))) return $r;
    return $bin;
  };
  $tieneShell = function_exists('shell_exec')
      && !in_array('shell_exec', array_map('trim', explode(',', (string)ini_get('disable_functions'))), true);

  $bin = $rutaBin((string)$cfg['yt_dlp']);
  $version = null;
  if ($tieneShell) {
    $salida = @shell_exec(escapeshellarg($bin) . ' --version 2>&1');
    if ($salida && preg_match('/^\d{4}\.\d{2}\.\d{2}/', trim($salida))) $version = trim($salida);
  }
  $chk('Descargas', 'yt-dlp', $version ? 'ok' : 'aviso',
       $version ? 'Versión ' . $version . '. Cantar sin internet, disponible.'
         : ($tieneShell
             ? 'No encontrado con la ruta configurada (' . $cfg['yt_dlp'] . '). '
               . 'Sin esto no se puede descargar, pero el karaoke funciona igual '
               . 'con internet. Ejecuta Preparar.bat o pon la ruta en Ajustes.'
             : 'shell_exec está desactivado en este PHP: sin él, yt-dlp no se '
               . 'puede lanzar aunque esté instalado.'));

  $ffBin = $rutaBin('ffmpeg');
  $hayFF = is_file($ffBin) || (function () {
    $r = @shell_exec('ffmpeg -version 2>&1');
    return $r && stripos($r, 'ffmpeg version') !== false;
  })();
  $chk('Descargas', 'ffmpeg', $hayFF ? 'ok' : 'aviso',
       $hayFF ? 'Presente. Sin él, yt-dlp descarga el vídeo pero no puede '
                . 'juntarlo con el audio.'
         : ($version
             ? 'FALTA. yt-dlp está pero sin ffmpeg no completa ninguna '
               . 'descarga — se queda a medias y parece que ha fallado yt-dlp. '
               . 'Ejecuta Preparar.bat para bajarlo.'
             : 'No encontrado (tampoco hay yt-dlp, así que hoy no hace falta).'));
}

/* — El registro de errores: lo primero que hay que mirar — */
$log = $DATOS . '/errores.log';
$lineas = [];
if (is_file($log)) {
  $todo = (array)@file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  $lineas = array_slice($todo, -12);
}
$chk('Errores', 'data/errores.log', $lineas ? 'aviso' : 'ok',
     $lineas ? count($lineas) . ' líneas recientes (abajo del todo)'
             : 'Vacío. Es la mejor noticia de esta página.');

/* El peor manda. */
$peor = 'ok';
foreach ($C as $g) foreach ($g as $c2) {
  if ($c2['estado'] === 'mal') $peor = 'mal';
  elseif ($c2['estado'] === 'aviso' && $peor !== 'mal') $peor = 'aviso';
}

$puerto = (int)($_SERVER['SERVER_PORT'] ?? 8123);
$ip = function_exists('ip_local') ? (ip_local() ?: 'localhost') : 'localhost';
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>QA · OpenKaraoke Center</title>
<style>
  :root{
    --bg:#0d0f14; --bg2:#151922; --bg3:#1d2230; --line:#2a3040;
    --txt:#e8eaf0; --txt2:#a8b0c0; --txt3:#6c7385;
    --ok:#3ecf8e; --aviso:#ffb020; --mal:#ff5c5c; --ac:#7cc4ff;
  }
  *{box-sizing:border-box}
  body{margin:0;padding:0 0 60px;background:var(--bg);color:var(--txt);
       font:15px/1.6 system-ui,-apple-system,Segoe UI,sans-serif}
  .wrap{max-width:980px;margin:0 auto;padding:0 22px}
  header{padding:34px 0 18px;border-bottom:1px solid var(--line);margin-bottom:26px}
  h1{margin:0 0 4px;font-size:26px;letter-spacing:-.2px}
  .sub{color:var(--txt3);font-size:14px}
  h2{font-size:13px;letter-spacing:.9px;text-transform:uppercase;
     color:var(--txt3);margin:32px 0 12px;font-weight:700}
  .resumen{display:flex;align-items:center;gap:14px;padding:16px 20px;
           border-radius:12px;margin:22px 0 6px;font-weight:700;font-size:17px}
  .resumen.ok{background:rgba(62,207,142,.12);color:var(--ok)}
  .resumen.aviso{background:rgba(255,176,32,.12);color:var(--aviso)}
  .resumen.mal{background:rgba(255,92,92,.12);color:var(--mal)}
  .punto{width:11px;height:11px;border-radius:50%;flex:none;margin-top:7px}
  .punto.ok{background:var(--ok)} .punto.aviso{background:var(--aviso)}
  .punto.mal{background:var(--mal)}
  .c{display:flex;gap:12px;padding:11px 16px;border-bottom:1px solid var(--line)}
  .c:last-child{border-bottom:0}
  .caja{background:var(--bg2);border:1px solid var(--line);border-radius:12px;
        overflow:hidden}
  .t{font-weight:600}
  .d{color:var(--txt2);font-size:13.5px}
  .c.mal .d{color:#ffc9c9}
  a.b,button.b{display:inline-block;margin:0 8px 8px 0;padding:10px 16px;
    background:var(--bg3);color:var(--txt);text-decoration:none;
    border:1px solid var(--line);border-radius:9px;font:inherit;font-size:14px;
    cursor:pointer}
  a.b:hover,button.b:hover{border-color:var(--ac);color:var(--ac)}
  button.b.d{border-color:#5a2b2b}
  button.b.d:hover{border-color:var(--mal);color:var(--mal)}
  pre{background:#080a0e;border:1px solid var(--line);border-radius:10px;
      padding:16px;overflow:auto;font-size:12.5px;line-height:1.5;color:#c6ccdb}
  .hecho{background:rgba(124,196,255,.12);color:var(--ac);padding:12px 18px;
         border-radius:10px;margin-bottom:18px}
  .nota{color:var(--txt3);font-size:13.5px;max-width:70ch}
  details{margin-top:10px}
  summary{cursor:pointer;color:var(--ac);font-size:14px}
  code{background:var(--bg3);padding:1px 6px;border-radius:5px;font-size:13px}
</style>
</head>
<body>
<div class="wrap">

<header>
  <h1>QA · OpenKaraoke Center</h1>
  <div class="sub">Esta página no prueba la aplicación. Prueba el suelo sobre el que se apoya.</div>
</header>

<?php if ($hecho): ?><div class="hecho"><?= $h($hecho) ?></div><?php endif; ?>

<div class="resumen <?= $peor ?>">
  <?= $peor === 'ok' ? '✓ Esta máquina puede correr el karaoke.'
      : ($peor === 'aviso' ? '! Funciona, pero hay algo que conviene mirar.'
         : '✕ Hay algo roto. Arréglalo antes de probar nada más.') ?>
</div>
<p class="nota">
  El resumen se pone en el <b>peor</b> de los resultados, nunca en la media.
  Un semáforo en ámbar por promediar un verde y un rojo es un semáforo que miente.
</p>

<?php foreach ($C as $grupo => $lista): ?>
  <h2><?= $h($grupo) ?></h2>
  <div class="caja">
    <?php foreach ($lista as $c2): ?>
      <div class="c <?= $c2['estado'] ?>">
        <span class="punto <?= $c2['estado'] ?>"></span>
        <div>
          <div class="t"><?= $h($c2['titulo']) ?></div>
          <div class="d"><?= $h($c2['detalle']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>

<h2>Las cuatro pantallas</h2>
<div>
  <a class="b" href="index.html" target="_blank">Operador</a>
  <a class="b" href="proyector.php" target="_blank">Pantalla del público</a>
  <a class="b" href="pedir.php" target="_blank">Pedir canciones</a>
  <a class="b" href="ajustes.php" target="_blank">Ajustes</a>
  <a class="b" href="pruebas/pruebas.php" target="_blank">Suite automática</a>
</div>
<p class="nota">
  Desde otro aparato de la misma wifi:
  <code>http://<?= $h($ip) ?>:<?= $puerto ?>/pedir.php</code>
</p>

<h2>Probar desde el móvil</h2>
<p class="nota">
  Escanéalo con la cámara del móvil — tiene que estar en la misma wifi que
  este PC. Si abre <b>Pedir canciones</b> y ve la cola, la red está bien;
  si no carga nada, el problema es de la wifi o del cortafuegos, no de la
  aplicación. Es el mismo QR que enseña la tele, generado aquí para
  probarlo sin tener que llegar al calentamiento.
</p>
<div id="qrMovil" style="width:180px"></div>
<script src="js/qr.js"></script>
<script>
  try {
    document.getElementById('qrMovil').innerHTML =
      KL.qr.svg(<?= json_encode('http://' . $ip . ':' . $puerto . '/pedir.php') ?>, { borde: 2 });
  } catch (e) {
    document.getElementById('qrMovil').textContent =
      'No se ha podido generar el QR aquí: usa la dirección de arriba a mano.';
  }
</script>

<h2>Dejar el estado en un punto conocido</h2>
<p class="nota">
  Para que cada prueba no empiece en un sitio distinto. Es la diferencia
  entre una prueba y una anécdota.
</p>
<form method="post">
  <!-- Ya no es una acción de este formulario: era un volcado directo de
       ejemplos.json a estado.json, con títulos sueltos en vez de vídeos
       resueltos -sin videoId, sin carátula-, así que biblioteca y cola
       quedaban en blanco (confirmado, 2026-08-04). La versión que
       funciona ya existía -montarFiestaDeEjemplo() en cola.js, la del
       botón «Montar una fiesta de ejemplo» cuando la biblioteca está
       vacía- y busca cada título de verdad contra YouTube. En vez de
       repetir esa lógica en PHP, este botón lleva a esa misma. -->
  <a class="b" href="index.html?ejemplo=1">Cargar fiesta de ejemplo</a>
  <button class="b d" name="accion" value="estado_limpio">Vaciar cola e historial</button>
  <button class="b d" name="accion" value="vaciar_cache">Vaciar caché de búsquedas</button>
  <button class="b d" name="accion" value="vaciar_log">Vaciar registro de errores</button>
  <button class="b" name="accion" value="romper"
          title="Provoca un error fatal a propósito">Probar la red de seguridad</button>
</form>
<p class="nota">
  <b>«Probar la red de seguridad»</b> provoca un error fatal a propósito. Tiene
  que salir una pantalla que explica qué ha pasado. Si sale una página en
  blanco, el arreglo de <code>api/comun.php</code> no está puesto — y esa
  página en blanco es exactamente el fallo que se vio instalando esto en
  un portátil ajeno.
</p>

<?php if ($lineas): ?>
<h2>Últimos errores registrados</h2>
<pre><?= $h(implode("\n", $lineas)) ?></pre>
<?php endif; ?>

<h2>El protocolo para Claude Code</h2>
<p class="nota">
  Copia esto entero y dáselo a Claude Code con la aplicación arrancada.
  Está escrito para que <b>no se pare en el primer fallo</b>, que es el
  error clásico de una sesión de pruebas.
</p>
<details open>
<summary>Ver el protocolo</summary>
<pre>
Actúa como ingeniero de QA. Usa la aplicación por la interfaz, como un
usuario real, con la consola del navegador abierta. NO te detengas en el
primer fallo: sigue y entrega un informe completo al final.

ANTES DE NADA
  Abre qa.php. Si algo sale en rojo, arréglalo o dilo, y no sigas: lo
  que venga después serán síntomas de eso.

CÓMO ANOTAR CADA FALLO
  pasos para reproducirlo · qué esperabas · qué ha pasado ·
  gravedad (crítica/alta/media/baja) · captura · errores de consola y de red

1 · ARRANQUE
  Las cuatro pantallas abren. Cero errores en consola, cero 404,
  ninguna página en blanco.

2 · BUSCADOR Y FILTRO
  Busca con: texto parcial, mayúsculas, acentos y sin ellos, espacios
  dobles, caracteres raros, texto larguísimo, texto que no existe.
  Filtro: AND, OR, comillas, «-» para excluir, los perfiles de fábrica,
  guardar un perfil propio, borrar el filtro.
  Comprueba que la línea de abajo enseña la traducción correcta.
  Repite la MISMA búsqueda: la segunda tiene que decir que viene de la
  caché y no gastar cuota (mírala en qa.php antes y después).

3 · ENLACES PEGADOS
  URL normal, corta (youtu.be), con parámetros, de lista, inválida.
  Con clave de API y sin ella. Sin clave, un enlace pegado DEBE seguir
  funcionando; si no, es un fallo.

4 · BIBLIOTECA Y COLA
  Añadir, quitar, favoritos, descargar, borrar la descarga, reordenar
  arrastrando, vaciar. Añadir la misma canción dos veces con el mismo
  nombre (debe pararse) y con otro nombre (debe entrar).
  Pide desde pedir.php en otra pestaña y comprueba que aparece.

5 · CICLO DE KARAOKE — la regla que no se rompe
  Calentamiento, empezar una actuación, dejarla terminar ENTERA.
  Al terminar: la consola vuelve sola, la siguiente queda PREPARADA y
  NO arranca. Espera diez segundos y compruébalo.
  El botón Empezar tiene que quedar encendido y pulsable.

6 · CICLO DE CABINA DJ — lo contrario, a propósito
  Al terminar una, la siguiente arranca SOLA. Compara los dos ciclos y
  anota cualquier otra diferencia que no sea esa.

7 · DURANTE UNA ACTUACIÓN
  Con el vídeo sonando: volver al operador, Esc, pausa, botón de pánico,
  cambiar de pestaña, redimensionar, recargar la página.
  Comprueba que el vídeo ocupa la ventana entera también con la vista
  compacta y con la mini.

8 · AJUSTES
  Cambia TODAS las opciones, guarda una por una y comprueba que se
  quedan. Guarda con la página de ajustes abierta en dos pestañas.
  Vuelve a entrar y comprueba que sigue todo.
  Ojo con el termómetro: los nombres de los momentos tienen que
  sobrevivir a un guardado.

9 · TEMAS
  Clásico, Fiesta, Peques y Show. La tele tiene que ponerse el mismo
  tema sola. Ni un texto ilegible ni un botón fuera de sitio.
  En Peques no puede haber ningún QR por ninguna parte.

10 · VENTANAS Y ESTRÉS
  Muy estrecha, muy ancha, pantalla completa, zoom al 50% y al 200%.
  Doble y triple clic en todo. Abrir el mismo diálogo varias veces.
  Recargar en mitad de una canción. Varias pestañas del operador a la vez.

11 · SIN RED
  Corta la wifi unos segundos con una canción sonando. Tiene que avisar
  y recuperarse sola al volver, sin recargar nada.

INFORME FINAL
  1. Críticos — impiden usar la aplicación
  2. Importantes — funciona, pero mal
  3. Usabilidad — confuso, mal colocado, falta información
  4. Mejoras — solo si no queda ningún fallo por resolver
</pre>
</details>

<h2>Y lo que esta página no cubre</h2>
<p class="nota">
  El sonido, la segunda pantalla de verdad, una cámara de móvil leyendo un
  QR y una persona delante del micro. Eso está en <code>PRUEBAS.md</code>,
  son veinte minutos, y no lo puede hacer ninguna máquina.
</p>

</div>
</body>
</html>
