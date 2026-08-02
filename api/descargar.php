<?php
/* ═══════════════════════════════════════════════════════════════════
   descargar.php — guardar vídeos en local con yt-dlp

   Una vez descargado, el karaoke funciona sin internet y no hay
   error 153, ni cortes, ni cuota de API que valga.

   ── En segundo plano, y por qué ──────────────────────────────────────
   Antes esto descargaba y no devolvía nada hasta terminar. Con un vídeo
   largo son cuarenta segundos o un minuto con la aplicación congelada, y
   además `php -S` atiende **una petición cada vez**: mientras duraba la
   descarga, la tele no se refrescaba y los móviles no podían pedir. La
   fiesta entera se paraba por bajar una canción.

   Ahora se lanza el proceso, se devuelve el control al momento y la
   interfaz pregunta cómo va. yt-dlp escribe su salida en un registro por
   vídeo y el progreso se lee de ahí.

     POST {videoId}        → arranca la descarga y responde en el acto
     GET  ?progreso=ID     → como va: en_curso / hecho / error
     GET  ?comprobar=1     → ¿está yt-dlp disponible?
     GET  ?estado=VIDEOID  → ¿ya está descargado?
     POST ?borrar=ID       → borra el archivo de un vídeo
     POST ?borrar_todo=1   → borra todas las descargas
   ═══════════════════════════════════════════════════════════════════ */

declare(strict_types=1);
require __DIR__ . '/comun.php';

$cfg = cfg();

/* En Windows, cmd no encuentra un nombre suelto entre comillas: recibe
   ""yt-dlp.exe"" y responde que no lo reconoce, aunque esté al lado. Con la
   ruta completa sí. Si el archivo no está junto a la aplicación se deja el
   nombre tal cual, para que lo busque en el PATH. */
function ruta_bin(string $bin): string {
  foreach ([$bin, dirname(__DIR__) . '/' . $bin] as $candidato) {
    if (is_file($candidato) && ($r = realpath($candidato))) return $r;
  }
  return $bin;
}

$bin       = ruta_bin((string)$cfg['yt_dlp']);
$dirVideos = DIR_DATOS . '/videos';
$dirTrabajo = DIR_DATOS . '/descargas';   // registros de progreso

const ES_WINDOWS = PHP_OS_FAMILY === 'Windows';

function hay_shell(): bool {
  return function_exists('shell_exec')
      && !in_array('shell_exec', array_map('trim', explode(',', (string)ini_get('disable_functions'))), true);
}

function version_ytdlp(string $bin): ?string {
  if (!hay_shell()) return null;
  $salida = @shell_exec(escapeshellarg($bin) . ' --version 2>&1');
  if (!$salida) return null;
  $salida = trim($salida);
  return preg_match('/^\d{4}\.\d{2}\.\d{2}/', $salida) ? $salida : null;
}

/* ---- comprobación --------------------------------------------------- */
if (isset($_GET['comprobar'])) {
  $v = version_ytdlp($bin);
  salir_json([
    'ok'          => true,
    'disponible'  => $v !== null,
    'version'     => $v,
    'shell'       => hay_shell(),
    'descargados' => is_dir($dirVideos) ? count(glob("$dirVideos/*.{mp4,webm,mkv}", GLOB_BRACE)) : 0,
    'ayuda'       => $v === null
      ? 'No encuentro yt-dlp. Descarga yt-dlp.exe de github.com/yt-dlp/yt-dlp/releases, '
        . 'ponlo junto a Karaoke.bat. No necesita Python: lleva el suyo dentro.'
      : null,
  ]);
}

/* ---- borrar una descarga -------------------------------------------
   La canción NO se pierde: sigue en la cola y en la biblioteca, y
   volverá a sonar desde YouTube. Lo que se borra es el archivo.

   Solo por POST. Con GET, a cualquiera conectado a la wifi le bastaba
   con que un móvil cargara una imagen apuntando a esta dirección para
   borrar los vídeos de la fiesta sin tocar nada. Las acciones que
   destruyen algo no se piden con un enlace. */
if (isset($_GET['borrar'])) {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_json('usa POST para borrar', 405);
  $id = id_video((string)$_GET['borrar']);
  if (!$id) error_json('identificador no válido');
  $borrados = 0;
  foreach (['mp4', 'webm', 'mkv'] as $ext) {
    $f = "$dirVideos/$id.$ext";
    if (is_file($f) && @unlink($f)) $borrados++;
  }
  @unlink("$dirTrabajo/$id.log");
  if (!$borrados) error_json('no había ningún archivo de ese vídeo', 404);
  salir_json(['ok' => true, 'borrados' => $borrados]);
}

/* ---- borrar todas las descargas -------------------------------------
   Al final de una temporada de fiestas esto puede ser mucho disco. Solo
   toca data/videos y solo los tres formatos que genera yt-dlp: nada de
   barrer la carpeta entera por si alguien ha dejado algo suyo dentro. */
if (isset($_GET['borrar_todo'])) {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_json('usa POST para borrar', 405);
  $borrados = 0; $bytes = 0;
  foreach (glob("$dirVideos/*.{mp4,webm,mkv}", GLOB_BRACE) ?: [] as $f) {
    $t = @filesize($f) ?: 0;
    if (@unlink($f)) { $borrados++; $bytes += $t; }
  }
  foreach (glob("$dirTrabajo/*.log") ?: [] as $f) @unlink($f);
  salir_json(['ok' => true, 'borrados' => $borrados, 'bytes' => $bytes]);
}

/* ---- ¿ya está descargado? ------------------------------------------- */
if (isset($_GET['estado'])) {
  $id = id_video((string)$_GET['estado']);
  if (!$id) error_json('identificador no válido');
  salir_json(['ok' => true, 'local' => video_local($id)]);
}

/* ---- cómo va una descarga -------------------------------------------
   El progreso sale del registro que va escribiendo yt-dlp. Se leen solo
   los últimos kilobytes: el archivo puede tener cientos de líneas y solo
   interesa la última. */
function progreso(string $log): array {
  if (!is_file($log)) return ['pct' => 0, 'texto' => ''];
  $t = '';
  $fh = @fopen($log, 'r');
  if ($fh) {
    $tam = filesize($log) ?: 0;
    if ($tam > 4096) fseek($fh, -4096, SEEK_END);
    $t = (string)stream_get_contents($fh);
    fclose($fh);
  }
  $pct = 0;
  if (preg_match_all('/\[download\]\s+([\d.]+)%/', $t, $m)) {
    $pct = (float)end($m[1]);
  }
  return ['pct' => $pct, 'cola' => $t];
}

if (isset($_GET['progreso'])) {
  $id = id_video((string)$_GET['progreso']);
  if (!$id) error_json('identificador no válido');

  if ($local = video_local($id)) {
    @unlink("$dirTrabajo/$id.log");
    salir_json(['ok' => true, 'estado' => 'hecho', 'local' => $local, 'pct' => 100]);
  }

  $log = "$dirTrabajo/$id.log";
  if (!is_file($log)) salir_json(['ok' => true, 'estado' => 'no', 'pct' => 0]);

  $p = progreso($log);

  /* yt-dlp escribe ERROR y termina. Si el registro lleva un rato sin
     crecer y no hay archivo, también se da por muerta: un proceso que
     desaparece no avisa de nada. */
  $muerta = (time() - (int)@filemtime($log)) > 90;
  if (stripos($p['cola'] ?? '', 'ERROR') !== false || $muerta) {
    $motivo = 'yt-dlp no ha podido descargarlo.';
    if (stripos($p['cola'] ?? '', 'ffmpeg') !== false) {
      $motivo .= ' Parece que falta ffmpeg para juntar el vídeo y el audio.';
    } elseif (stripos($p['cola'] ?? '', 'private') !== false) {
      $motivo .= ' El vídeo es privado.';
    } elseif ($muerta) {
      $motivo .= ' El proceso se ha quedado parado.';
    }
    salir_json(['ok' => true, 'estado' => 'error', 'pct' => $p['pct'], 'error' => $motivo]);
  }

  salir_json(['ok' => true, 'estado' => 'en_curso', 'pct' => $p['pct']]);
}

/* ---- arrancar la descarga -------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_json('usa POST para descargar', 405);

$in = cuerpo_json();
$id = id_video((string)($in['videoId'] ?? ''));
if (!$id) error_json('identificador de vídeo no válido');

if ($ya = video_local($id)) {
  salir_json(['ok' => true, 'estado' => 'hecho', 'local' => $ya, 'nuevo' => false]);
}

if (version_ytdlp($bin) === null) {
  error_json('yt-dlp no está disponible. Míralo en Ajustes → Sistema.', 503);
}

foreach ([$dirVideos, $dirTrabajo] as $d) if (!is_dir($d)) @mkdir($d, 0775, true);
if (!is_writable($dirVideos)) error_json('no puedo escribir en data/videos', 500);

$log = "$dirTrabajo/$id.log";

/* Si ya hay una en marcha para este vídeo, no se lanza otra: dos yt-dlp
   escribiendo el mismo archivo dejan un mp4 a medias que además parece
   bueno. Un registro parado más de minuto y medio se considera muerto. */
if (is_file($log) && (time() - (int)@filemtime($log)) < 90) {
  salir_json(['ok' => true, 'estado' => 'en_curso', 'pct' => progreso($log)['pct']]);
}

$altura  = (int)$cfg['altura_max'];
$destino = "$dirVideos/$id.mp4";

/* El nombre de salida va fijo, sin la plantilla %(ext)s de yt-dlp: en
   Windows, escapeshellarg() cambia cada % por un espacio para que cmd no
   expanda variables, y el archivo acababa llamándose «id. (ext)s.mp4». Se
   descargaba bien y luego nadie lo encontraba. Con --remux-video siempre
   sale mp4, así que la extensión la sabemos de antemano.

   `--newline` es lo que hace posible seguir el progreso: sin él, yt-dlp
   reescribe la misma línea con retornos de carro y el registro queda
   ilegible. */
$cmd = escapeshellarg($bin)
     . ' -f ' . escapeshellarg("bestvideo[height<=$altura]+bestaudio/best[height<=$altura]/best")
     . ' --merge-output-format mp4 --remux-video mp4'
     . ' --no-playlist --no-warnings --newline'
     . ' --socket-timeout 20 --retries 3'
     . ' -o ' . escapeshellarg($destino)
     . ' ' . escapeshellarg("https://www.youtube.com/watch?v=$id");

@file_put_contents($log, "[karaoke] arrancando\n");

if (ES_WINDOWS) {
  /* Se escribe un .bat y se lanza ese, en vez de meter el comando entero
     dentro de `start`. Anidar comillas en cmd es una fuente inagotable de
     fallos silenciosos, y aquí ya nos ha mordido una vez. */
  $bat = "$dirTrabajo/$id.bat";
  @file_put_contents($bat, "@echo off\r\n" . $cmd . " >> \"$log\" 2>&1\r\ndel \"%~f0\"\r\n");
  $fh = @popen('start "" /B "' . $bat . '"', 'r');
  if ($fh) pclose($fh);
} else {
  @exec($cmd . ' >> ' . escapeshellarg($log) . ' 2>&1 &');
}

salir_json(['ok' => true, 'estado' => 'en_curso', 'pct' => 0, 'nuevo' => true]);
