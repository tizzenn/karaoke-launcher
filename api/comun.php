<?php
/* Utilidades compartidas por el resto de la API. */

declare(strict_types=1);

const DIR_DATOS = __DIR__ . '/../data';

function cfg(): array {
  static $c = null;
  if ($c === null) $c = require __DIR__ . '/config.php';
  return $c;
}

function salir_json($datos, int $codigo = 200): void {
  http_response_code($codigo);
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store');
  echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

function error_json(string $mensaje, int $codigo = 400): void {
  salir_json(['ok' => false, 'error' => $mensaje], $codigo);
}

function cuerpo_json(): array {
  $raw = file_get_contents('php://input');
  if ($raw === '' || $raw === false) return [];
  $d = json_decode($raw, true);
  return is_array($d) ? $d : [];
}

/* ---- Lectura y escritura de estado con bloqueo -----------------------
   Varias personas pueden estar añadiendo a la cola desde el móvil a la
   vez. Sin bloqueo, dos peticiones simultáneas se pisan y se pierde una.
   ---------------------------------------------------------------------- */

function ruta_estado(string $nombre): string {
  if (!preg_match('/^[a-z_]+$/', $nombre)) error_json('nombre no válido');
  if (!is_dir(DIR_DATOS)) @mkdir(DIR_DATOS, 0775, true);
  return DIR_DATOS . '/' . $nombre . '.json';
}

function leer_estado(string $nombre, array $porDefecto = []): array {
  $f = ruta_estado($nombre);
  if (!is_file($f)) return $porDefecto;
  $t = @file_get_contents($f);
  if ($t === false || $t === '') return $porDefecto;
  $d = json_decode($t, true);
  return is_array($d) ? $d : $porDefecto;
}

/* Abre el archivo, lo bloquea, aplica $fn al contenido y lo vuelve a
   escribir. Nadie más puede tocarlo mientras tanto. */
function modificar_estado(string $nombre, callable $fn) {
  $f  = ruta_estado($nombre);
  $fh = fopen($f, 'c+');
  if (!$fh) error_json('no puedo abrir ' . basename($f), 500);

  if (!flock($fh, LOCK_EX)) { fclose($fh); error_json('archivo ocupado', 503); }

  $t = stream_get_contents($fh);
  $d = ($t === '' || $t === false) ? [] : (json_decode($t, true) ?: []);

  $nuevo = $fn($d);

  ftruncate($fh, 0);
  rewind($fh);
  fwrite($fh, json_encode($nuevo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
  fflush($fh);
  flock($fh, LOCK_UN);
  fclose($fh);
  return $nuevo;
}

/* ---- Ayudas varias -------------------------------------------------- */

function id_video(string $s): ?string {
  $s = trim($s);
  if (preg_match('/^[A-Za-z0-9_-]{11}$/', $s)) return $s;
  if (preg_match('#(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})#', $s, $m))
    return $m[1];
  return null;
}

function uid(): string {
  return base_convert((string)time(), 10, 36) . bin2hex(random_bytes(3));
}

function iso_a_segundos(string $iso): int {
  if (!preg_match('/^PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?$/', $iso, $m)) return 0;
  return ((int)($m[1] ?? 0)) * 3600 + ((int)($m[2] ?? 0)) * 60 + ((int)($m[3] ?? 0));
}

/* Petición GET sencilla, con cURL si está disponible.

   Si falla, deja el motivo en $GLOBALS['ultimo_fallo_red']. Sin eso, un PHP
   sin certificados raíz —el caso del PHP portable recién descargado— daba
   «no he podido contactar con YouTube», que manda a mirar la wifi cuando el
   problema está en la instalación y no se arregla solo. */
function traer(string $url): ?string {
  $GLOBALS['ultimo_fallo_red'] = null;

  if (function_exists('curl_init')) {
    $c = curl_init($url);
    curl_setopt_array($c, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT        => 12,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_USERAGENT      => 'KaraokeLauncher/1.0',
    ]);
    $r = curl_exec($c);
    /* curl_close() está obsoleta desde PHP 8.5 y no hacía nada desde la 8.0:
       el recurso se libera solo. Llamarla imprimía un aviso dentro del JSON
       y el navegador ya no podía leer la respuesta. */
    if ($r === false) {
      $n = curl_errno($c);
      $GLOBALS['ultimo_fallo_red'] = $n === CURLE_SSL_CACERT
        || $n === CURLE_PEER_FAILED_VERIFICATION
        ? 'Falta el archivo de certificados (cacert.pem) en la carpeta php. '
          . 'Ejecuta Preparar.bat otra vez y lo deja puesto.'
        : 'cURL ' . $n . ': ' . curl_error($c);
      return null;
    }
    return $r;
  }

  $ctx = stream_context_create(['http' => ['timeout' => 12, 'user_agent' => 'KaraokeLauncher/1.0']]);
  $r = @file_get_contents($url, false, $ctx);
  if ($r === false) $GLOBALS['ultimo_fallo_red'] = 'no hay salida a internet desde PHP';
  return $r === false ? null : $r;
}

/* El porqué del último fallo de red, para poder decirlo en pantalla. */
function fallo_red(): string {
  $m = $GLOBALS['ultimo_fallo_red'] ?? null;
  return $m ? ' ' . $m : '';
}

/* Nombre del archivo local de un vídeo descargado, si existe. */
function video_local(string $id): ?string {
  $dir = DIR_DATOS . '/videos';
  foreach (['mp4', 'webm', 'mkv'] as $ext) {
    if (is_file("$dir/$id.$ext")) return "data/videos/$id.$ext";
  }
  return null;
}
