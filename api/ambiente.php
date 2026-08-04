<?php
/* ═══════════════════════════════════════════════════════════════════
   ambiente.php — qué hay en la carpeta de música de fondo

   Devuelve las pistas de la carpeta configurada, como direcciones que el
   navegador pueda pedir.

   ── Por qué no se sirve la carpeta tal cual ─────────────────────────
   Porque puede estar en cualquier sitio del disco —`D:\Musica`, un disco
   externo— y el servidor solo publica la carpeta del proyecto. Así que
   los archivos se sirven desde aquí mismo, uno a uno y por índice, sin
   que el nombre del archivo viaje nunca en la dirección.

   Eso cierra de paso la puerta obvia: no hay forma de pedir
   `?f=../../ajustes.json`, porque no se acepta ningún nombre. Solo un
   número, y solo de la lista que este archivo ha construido.
   ═══════════════════════════════════════════════════════════════════ */

declare(strict_types=1);
require __DIR__ . '/comun.php';

$cfg = cfg();
$carpeta = trim((string)($cfg['ambiente_carpeta'] ?? ''));

const EXT_AMBIENTE = ['mp3', 'm4a', 'ogg', 'wav', 'opus', 'flac'];

function pistas_ambiente(string $carpeta): array {
  if ($carpeta === '' || !is_dir($carpeta)) return [];
  $fuera = [];
  foreach (scandir($carpeta) ?: [] as $n) {
    if ($n === '.' || $n === '..') continue;
    $ruta = $carpeta . DIRECTORY_SEPARATOR . $n;
    if (!is_file($ruta)) continue;
    if (!in_array(strtolower(pathinfo($n, PATHINFO_EXTENSION)), EXT_AMBIENTE, true)) continue;
    $fuera[] = $ruta;
  }
  /* Orden estable: el índice tiene que significar lo mismo entre una
     petición y la siguiente, o el navegador pediría otra canción. */
  sort($fuera, SORT_STRING);
  return $fuera;
}

$lista = pistas_ambiente($carpeta);

/* ---- Servir una pista ------------------------------------------------- */
if (isset($_GET['p'])) {
  $i = (int)$_GET['p'];
  if ($i < 0 || $i >= count($lista)) { http_response_code(404); exit; }
  $ruta = $lista[$i];

  $tipos = ['mp3'=>'audio/mpeg', 'm4a'=>'audio/mp4', 'ogg'=>'audio/ogg',
            'wav'=>'audio/wav', 'opus'=>'audio/ogg', 'flac'=>'audio/flac'];
  $ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));

  header('Content-Type: ' . ($tipos[$ext] ?? 'application/octet-stream'));
  header('Content-Length: ' . filesize($ruta));
  /* Sin esto no se puede adelantar dentro de una canción larga, y algunos
     navegadores se niegan a reproducir del todo. */
  header('Accept-Ranges: bytes');
  readfile($ruta);
  exit;
}

/* ---- La lista --------------------------------------------------------- */
salir_json([
  'ok'      => true,
  'carpeta' => $carpeta,
  'existe'  => $carpeta !== '' && is_dir($carpeta),
  'pistas'  => array_map(fn($i) => 'api/ambiente.php?p=' . $i, array_keys($lista)),
]);
