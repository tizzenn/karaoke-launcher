<?php
/* ═══════════════════════════════════════════════════════════════════
   descargar.php — guardar vídeos en local con yt-dlp

   Una vez descargado, el karaoke funciona sin internet y no hay
   error 153, ni cortes, ni cuota de API que valga.

   GET  ?comprobar=1     → ¿está yt-dlp disponible?
   POST {videoId:'...'}  → descarga y responde con la ruta local
   GET  ?estado=VIDEOID  → ¿ya está descargado?
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

$bin = ruta_bin((string)$cfg['yt_dlp']);
$dirVideos = DIR_DATOS . '/videos';

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
        . 'ponlo junto a Karaoke.bat y escribe su ruta en api/config.php.'
      : null,
  ]);
}

/* ---- ¿ya está descargado? ------------------------------------------- */
if (isset($_GET['estado'])) {
  $id = id_video((string)$_GET['estado']);
  if (!$id) error_json('identificador no válido');
  salir_json(['ok' => true, 'local' => video_local($id)]);
}

/* ---- descarga -------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_json('usa POST para descargar', 405);

$in = cuerpo_json();
$id = id_video((string)($in['videoId'] ?? ''));
if (!$id) error_json('identificador de vídeo no válido');

if ($ya = video_local($id)) {
  salir_json(['ok' => true, 'local' => $ya, 'nuevo' => false]);
}

if (version_ytdlp($bin) === null) {
  error_json('yt-dlp no está disponible. Mira api/config.php.', 503);
}

if (!is_dir($dirVideos)) @mkdir($dirVideos, 0775, true);
if (!is_writable($dirVideos)) error_json('no puedo escribir en data/videos', 500);

$altura = (int)$cfg['altura_max'];

/* Descargar suele tardar más que el límite de PHP por defecto. */
@set_time_limit(0);
@ini_set('max_execution_time', '0');

/* El nombre de salida va fijo, sin la plantilla %(ext)s de yt-dlp: en
   Windows, escapeshellarg() cambia cada % por un espacio para que cmd no
   expanda variables, y el archivo acababa llamándose «id. (ext)s.mp4». Se
   descargaba bien y luego nadie lo encontraba. Con --remux-video siempre
   sale mp4, así que la extensión la sabemos de antemano. */
$destino = "$dirVideos/$id.mp4";

$cmd = escapeshellarg($bin)
     . ' -f ' . escapeshellarg("bestvideo[height<=$altura]+bestaudio/best[height<=$altura]/best")
     . ' --merge-output-format mp4 --remux-video mp4'
     . ' --no-playlist --no-warnings --no-progress'
     . ' --socket-timeout 20 --retries 3'
     . ' -o ' . escapeshellarg($destino)
     . ' ' . escapeshellarg("https://www.youtube.com/watch?v=$id")
     . ' 2>&1';

$salida = @shell_exec($cmd);

$local = video_local($id);
if (!$local) {
  $motivo = 'yt-dlp no ha podido descargarlo.';
  if ($salida && stripos($salida, 'ffmpeg') !== false) {
    $motivo .= ' Parece que falta ffmpeg para juntar vídeo y audio.';
  } elseif ($salida && stripos($salida, 'private') !== false) {
    $motivo .= ' El vídeo es privado.';
  }
  error_json($motivo . ' Detalle: ' . trim(substr((string)$salida, -300)), 502);
}

salir_json(['ok' => true, 'local' => $local, 'nuevo' => true]);
