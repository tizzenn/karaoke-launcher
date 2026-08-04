<?php
/* ═══════════════════════════════════════════════════════════════════
   buscar.php — intermediario con YouTube

   El navegador nunca ve la clave: pide aquí, y este archivo es quien
   habla con Google. Esto es lo que permite publicar el proyecto en
   GitHub sin regalarle la clave a nadie.

   GET ?q=texto           → busca (o resuelve si pegas un enlace)
   GET ?q=texto&fresco=1  → igual, pero saltándose la caché
   GET ?id=VIDEOID        → datos de un vídeo concreto
   GET ?cuota=1           → cuánta cuota de YouTube se lleva gastada hoy

   ── Sobre la cuota ────────────────────────────────────────────────
   Cada búsqueda cuesta 100 unidades de las 10.000 que da el plan
   gratuito al día: **99 búsquedas y se acabó**. Por eso aquí hay tres
   cosas antes de llamar a Google: los enlaces pegados van por oEmbed
   (gratis), las repetidas salen de la caché (gratis) y lo que sí se
   gasta se cuenta. Ver `cache.php`.
   ═══════════════════════════════════════════════════════════════════ */

declare(strict_types=1);
require __DIR__ . '/comun.php';
require __DIR__ . '/cache.php';
require __DIR__ . '/claves.php';

$cfg   = cfg();
$pool  = claves_disponibles($cfg);
/* $clave ya no decide si se puede buscar por texto: eso lo dice el pool
   (más abajo). Se conserva para el mensaje de «no hay ninguna clave» de
   cuando el pool está vacío de verdad. */
$clave = $pool[0]['key'] ?? '';

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

/* ---- cuánto llevamos gastado hoy ------------------------------------
   Barato y sin efectos: lo pregunta el diagnóstico y la propia página de
   resultados para poder decirlo en voz alta. */
if (isset($_GET['cuota'])) {
  salir_json(['ok' => true, 'cuota' => cuota_hoy(), 'diaria' => CUOTA_DIARIA,
              'coste' => COSTE_BUSCAR + COSTE_VIDEOS]);
}

/* ---- vaciar la caché ------------------------------------------------
   Por POST, como todo lo que destruye algo: una dirección que borra al
   abrirla la dispara cualquier cosa que precargue enlaces. */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['vaciar_cache'])) {
  salir_json(['ok' => true, 'borradas' => cache_vaciar()]);
}

/* ---- petición de un vídeo concreto ---------------------------------- */
if (isset($_GET['id'])) {
  $id = id_video((string)$_GET['id']);
  if (!$id) error_json('identificador de vídeo no válido');
  $v = por_oembed($id);
  /* «Ese vídeo no existe» es una MENTIRA si lo que ha pasado es que PHP
     no tiene certificados y no ha podido preguntar. Y es justo el caso de
     una instalación nueva. Un mensaje seguro de sí mismo que apunta al
     sitio equivocado hace perder más tiempo que no decir nada: manda a
     buscar el fallo donde no está. */
  if (!$v) error_json('No he podido leer los datos de ese vídeo.' . fallo_red()
                      . ' Puede que no exista, que sea privado, o que este '
                      . 'ordenador no llegue a YouTube.', 404);
  salir_json(['ok' => true, 'items' => [$v]]);
}

/* ---- búsqueda -------------------------------------------------------- */
$q = trim((string)($_GET['q'] ?? ''));
if ($q === '') error_json('falta el texto de búsqueda');

/* Si es un enlace pegado, ni tocamos la API. */
if ($id = id_video($q)) {
  $v = por_oembed($id);
  /* «Ese vídeo no existe» es una MENTIRA si lo que ha pasado es que PHP
     no tiene certificados y no ha podido preguntar. Y es justo el caso de
     una instalación nueva. Un mensaje seguro de sí mismo que apunta al
     sitio equivocado hace perder más tiempo que no decir nada: manda a
     buscar el fallo donde no está. */
  if (!$v) error_json('No he podido leer los datos de ese vídeo.' . fallo_red()
                      . ' Puede que no exista, que sea privado, o que este '
                      . 'ordenador no llegue a YouTube.', 404);
  salir_json(['ok' => true, 'items' => [$v], 'fuente' => 'enlace']);
}

/* Un enlace de YouTube que no lleva vídeo: una lista, un canal. Se dice
   qué pasa en vez de devolver «sin resultados», que delante de una lista
   de reproducción no ayuda a nadie. Y se dice ANTES de mirar la clave:
   este mensaje es cierto con clave y sin ella. */
if (enlace_de_youtube($q)) {
  error_json('Ese enlace de YouTube no lleva ningún vídeo dentro — '
    . 'parece una lista o un canal. Abre el vídeo que quieras y pega SU enlace.', 422);
}

if (!$pool) {
  /* El mensaje decía «no hay clave» y punto, y eso hacía pensar que la
     aplicación estaba rota. No lo está: le falta UNA de sus dos maneras
     de encontrar canciones, y la otra funciona perfectamente y además es
     gratis. Un error que no dice qué SÍ se puede hacer deja al usuario
     igual de parado que uno que no dice nada.

     Si hay claves configuradas pero TODAS agotadas hoy, el mensaje es
     otro: aquí no hace falta distinguirlo porque el bucle de más abajo
     ya habría encontrado una activa si la había — llegar aquí solo pasa
     cuando de verdad no hay ninguna clave utilizable. */
  error_json('Para buscar por texto hace falta una clave de YouTube, y todavía '
    . 'no hay ninguna disponible (o están todas agotadas por hoy). Mientras '
    . 'tanto puedes PEGAR AQUÍ EL ENLACE de un vídeo de YouTube: eso funciona '
    . 'sin clave y sin gastar cuota. Las claves se ponen en Ajustes.', 503);
}

/* El sufijo lo manda el cliente, porque depende del modo en uso —karaoke
   añade «karaoke», la Cabina DJ no añade nada— y ahora es un campo a la
   vista, no un ajuste escondido. Es una preferencia del aparato, no del
   servidor. Si no viene ninguno,
   manda el de la configuración, que es como funcionaba antes.

   `sufijo=` vacío en la dirección NO es lo mismo que no mandarlo: es el
   modo DJ diciendo «busca tal cual». Por eso se mira con isset. */
$sufijo = isset($_GET['sufijo']) ? trim((string)$_GET['sufijo']) : (string)$cfg['sufijo'];
$consulta = trim($q . ' ' . $sufijo);

/* Peques filtra: `safeSearch=strict` (más abajo) y, aparte, se descarta
   lo que YouTube marca con su restricción de edad máxima —dos filtros
   de Google, no uno propio, porque no hay forma de clasificar contenido
   mejor que quien lo aloja. El tema es de la fiesta y puede cambiar a
   media noche, así que lo manda el cliente en cada búsqueda: no es un
   ajuste del servidor. */
$esKids = ($_GET['tema'] ?? '') === 'kids';

/* La caché tiene que distinguir una búsqueda filtrada de una que no lo
   está: si «dibujos animados» se cachea sin filtrar y luego se pide
   igual desde Peques, la sirve tal cual y el filtro no habría servido de
   nada la segunda vez. La clave de caché lleva la marca; la consulta que
   de verdad viaja a Google, no — esa solo lleva palabras de búsqueda. */
$claveCache = $consulta . ($esKids ? ' ·peques' : '');

/* ---- Lo primero: ¿ya la hemos pagado? -------------------------------
   Antes de nada, porque cada búsqueda cuesta 100 de las 10.000 unidades
   diarias y en una fiesta la misma canción se busca varias veces. El
   porqué completo está en cache.php.

   `?fresco=1` se la salta: es la única manera de volver a preguntar por
   algo que se buscó hace tres semanas y ha cambiado. */
$fresco = !empty($_GET['fresco']);
if (!$fresco && ($hit = cache_leer($claveCache)) !== null) {
  cuota_sumar(0, true);
  salir_json(['ok' => true, 'items' => $hit['items'], 'fuente' => 'cache',
              'cuando' => $hit['cuando'], 'cuota' => cuota_hoy()]);
}

$url_busqueda = 'https://www.googleapis.com/youtube/v3/search?part=snippet&type=video'
     . '&videoEmbeddable=true&maxResults=20&q=' . rawurlencode($consulta)
     . ($esKids ? '&safeSearch=strict' : '');

/* ---- Probar cada clave del pool, de más a menos prioridad -----------
   No es un reintento genérico: solo se pasa a la siguiente clave cuando
   el motivo del fallo es DE LA CLAVE (cuota, clave inválida, API sin
   habilitar…). Un fallo de red o un error que no depende de la clave
   fallaría igual con cualquiera de las otras, así que no merece la pena
   gastarlas — se corta ahí y se informa. */
$j = null; $clave = null;
foreach ($pool as $candidata) {
  $r = traer($url_busqueda . '&key=' . rawurlencode($candidata['key']));
  if ($r === null) {
    $j = ['__red' => true];
    continue;
  }
  cuota_sumar(COSTE_BUSCAR);
  $jj = json_decode($r, true);
  $motivo = $jj['error']['errors'][0]['reason'] ?? null;
  if ($motivo !== null && claves_motivo_de_clave($motivo)) {
    /* Se aparta por hoy sea cual sea el motivo, no solo cuota agotada:
       una clave invalida o sin la API habilitada tampoco va a empezar a
       funcionar a media busqueda, y "Probar claves" en Ajustes es donde
       se diagnostica de verdad. Total, mañana se vuelve a intentar sola. */
    claves_marcar_agotada($candidata['key']);
    $j = $jj;
    continue;
  }
  claves_sumar_uso($candidata['key']);
  $j = $jj;
  $clave = $candidata['key'];
  break;
}

if ($clave === null && ($j['__red'] ?? false)) {
  error_json('No he podido contactar con YouTube.' . fallo_red(), 502);
}

if (isset($j['error'])) {
  $motivo = $j['error']['errors'][0]['reason'] ?? '';
  $msg = match ($motivo) {
    'quotaExceeded'   => 'Cuota diaria agotada en todas las claves configuradas. '
                        . 'Vuelve mañana o usa enlaces pegados.',
    'keyInvalid'      => 'Ninguna clave configurada es válida.',
    'accessNotConfigured' => 'La YouTube Data API v3 no está habilitada en ese proyecto de Google.',
    default           => $j['error']['message'] ?? 'error de la API',
  };
  error_json($msg, 502);
}

$items = $j['items'] ?? [];
if (!$items) salir_json(['ok' => true, 'items' => [], 'cuota' => cuota_hoy()]);

/* Segunda llamada para las duraciones, con la misma clave que ha
   funcionado para la búsqueda. Si falla, no pasa nada. En Peques
   aprovecha la misma llamada para leer `contentRating.ytRating`: si
   YouTube lo marca `ytAgeRestricted` —su restricción de edad máxima—
   fuera. `safeSearch=strict` ya aparta casi todo antes; esto es una
   segunda pasada sobre lo poco que se cuela. */
$duraciones = [];
$restringidos = [];
$ids = array_values(array_filter(array_map(fn($i) => $i['id']['videoId'] ?? null, $items)));
if ($ids) {
  $r2 = traer('https://www.googleapis.com/youtube/v3/videos?part=contentDetails&id='
              . implode(',', $ids) . '&key=' . rawurlencode($clave));
  cuota_sumar(COSTE_VIDEOS);
  if ($r2 !== null) {
    foreach ((json_decode($r2, true)['items'] ?? []) as $v) {
      $duraciones[$v['id']] = iso_a_segundos($v['contentDetails']['duration'] ?? '');
      if ($esKids && ($v['contentDetails']['contentRating']['ytRating'] ?? '') === 'ytAgeRestricted') {
        $restringidos[$v['id']] = true;
      }
    }
  }
}

$salida = [];
foreach ($items as $i) {
  $id = $i['id']['videoId'] ?? null;
  if (!$id || isset($restringidos[$id])) continue;
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

cache_guardar($claveCache, $salida);
salir_json(['ok' => true, 'items' => $salida, 'fuente' => 'api', 'cuota' => cuota_hoy()]);
