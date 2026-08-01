<?php
/* ═══════════════════════════════════════════════════════════════════
   buscar.php — intermediario con YouTube

   El navegador nunca ve la clave: pide aquí, y este archivo es quien
   habla con Google. Esto es lo que permite publicar el proyecto en
   GitHub sin regalarle la clave a nadie.

   GET ?q=texto           → busca (o resuelve si pegas un enlace)
   GET ?id=VIDEOID        → datos de un vídeo concreto
   ═══════════════════════════════════════════════════════════════════ */

declare(strict_types=1);
require __DIR__ . '/comun.php';

$cfg   = cfg();
$clave = trim((string)$cfg['api_key']);

/* ---- resolver un vídeo suelto sin gastar cuota de la API -------------
   oEmbed es público y no consume cuota. Para un enlace pegado sobra. */
function por_oembed(string $id): ?array {
  $r = traer('https://www.youtube.com/oembed?format=json&url='
             . rawurlencode("https://www.youtube.com/watch?v=$id"));
  if ($r === null) return null;
  $j = json_decode($r, true);
  if (!is_array($j) || empty($j['title'])) return null;
  return [
    'videoId'  => $id,
    'title'    => $j['title'],
    'channel'  => $j['author_name'] ?? '',
    'thumb'    => $j['thumbnail_url'] ?? "https://i.ytimg.com/vi/$id/mqdefault.jpg",
    'duration' => 0,
    'local'    => video_local($id),
  ];
}

/* ---- petición de un vídeo concreto ---------------------------------- */
if (isset($_GET['id'])) {
  $id = id_video((string)$_GET['id']);
  if (!$id) error_json('identificador de vídeo no válido');
  $v = por_oembed($id);
  if (!$v) error_json('ese vídeo no existe o es privado', 404);
  salir_json(['ok' => true, 'items' => [$v]]);
}

/* ---- búsqueda -------------------------------------------------------- */
$q = trim((string)($_GET['q'] ?? ''));
if ($q === '') error_json('falta el texto de búsqueda');

/* Si es un enlace pegado, ni tocamos la API. */
if ($id = id_video($q)) {
  $v = por_oembed($id);
  if (!$v) error_json('ese vídeo no existe o es privado', 404);
  salir_json(['ok' => true, 'items' => [$v], 'fuente' => 'enlace']);
}

if ($clave === '' || $clave === 'PON_AQUI_TU_CLAVE') {
  error_json('No hay clave de API configurada. Ponla en api/config.php.', 503);
}

$consulta = trim($q . ' ' . (string)$cfg['sufijo']);

$url = 'https://www.googleapis.com/youtube/v3/search?part=snippet&type=video'
     . '&videoEmbeddable=true&maxResults=20&q=' . rawurlencode($consulta)
     . '&key=' . rawurlencode($clave);

$r = traer($url);
if ($r === null) error_json('No he podido contactar con YouTube.' . fallo_red(), 502);

$j = json_decode($r, true);

if (isset($j['error'])) {
  $motivo = $j['error']['errors'][0]['reason'] ?? '';
  $msg = match ($motivo) {
    'quotaExceeded'   => 'Cuota diaria de la API agotada. Vuelve mañana o usa enlaces pegados.',
    'keyInvalid'      => 'La clave de la API no es válida.',
    'accessNotConfigured' => 'La YouTube Data API v3 no está habilitada en ese proyecto de Google.',
    default           => $j['error']['message'] ?? 'error de la API',
  };
  error_json($msg, 502);
}

$items = $j['items'] ?? [];
if (!$items) salir_json(['ok' => true, 'items' => []]);

/* Segunda llamada para las duraciones. Si falla, no pasa nada. */
$duraciones = [];
$ids = array_values(array_filter(array_map(fn($i) => $i['id']['videoId'] ?? null, $items)));
if ($ids) {
  $r2 = traer('https://www.googleapis.com/youtube/v3/videos?part=contentDetails&id='
              . implode(',', $ids) . '&key=' . rawurlencode($clave));
  if ($r2 !== null) {
    foreach ((json_decode($r2, true)['items'] ?? []) as $v) {
      $duraciones[$v['id']] = iso_a_segundos($v['contentDetails']['duration'] ?? '');
    }
  }
}

$salida = [];
foreach ($items as $i) {
  $id = $i['id']['videoId'] ?? null;
  if (!$id) continue;
  $s = $i['snippet'];
  $salida[] = [
    'videoId'  => $id,
    'title'    => html_entity_decode($s['title'], ENT_QUOTES, 'UTF-8'),
    'channel'  => html_entity_decode($s['channelTitle'], ENT_QUOTES, 'UTF-8'),
    'thumb'    => $s['thumbnails']['medium']['url'] ?? $s['thumbnails']['default']['url'],
    'duration' => $duraciones[$id] ?? 0,
    'local'    => video_local($id),
  ];
}

salir_json(['ok' => true, 'items' => $salida, 'fuente' => 'api']);
