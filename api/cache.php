<?php
/* ═══════════════════════════════════════════════════════════════════
   cache.php — que la misma búsqueda no se pague dos veces

   ── El problema, con números ────────────────────────────────────────
   La YouTube Data API cobra **100 unidades por búsqueda** y el plan
   gratuito da **10.000 al día**. Eso son **99 búsquedas**. No 99 por
   hora: 99 en total, y a medianoche vuelta a empezar.

   En una fiesta de tres horas con gente pidiendo desde el móvil, 99 se
   agotan. Y cuando se agotan no hay aviso amable: la aplicación deja de
   encontrar canciones en mitad de la noche.

   ── Por qué una caché sirve tanto aquí ──────────────────────────────
   Porque las búsquedas de un karaoke **se repiten muchísimo**. Tres
   personas distintas piden «Bohemian Rhapsody» la misma noche; la misma
   canción se busca en cada fiesta; y el operador busca, mira, cierra y
   vuelve a buscar lo mismo dos minutos después. Todas esas son la misma
   petición.

   Y los resultados **no caducan de verdad**. El karaoke de una canción
   de 1975 es el mismo hoy que el mes que viene. Por eso el plazo es
   largo —treinta días— y no cinco minutos: un plazo corto tiene el coste
   de una caché y el ahorro de ninguna.

   ── Cómo está hecho ─────────────────────────────────────────────────
   Un archivo por consulta, con el nombre sacado del texto. Sin bloqueos
   y sin un índice central: dos búsquedas a la vez escriben archivos
   distintos y no se pisan. Escribir en temporal y renombrar, como todo
   lo demás del proyecto — un archivo a medias es peor que ninguno.

   Lo que NO se cachea: los enlaces pegados (van por oEmbed, que es
   gratis) y los errores (una cuota agotada guardada treinta días sería
   el peor error posible).
   ═══════════════════════════════════════════════════════════════════ */

declare(strict_types=1);

const CACHE_DIAS   = 30;
const CACHE_MAX    = 800;    // archivos; por encima se tira lo más viejo
/* Lo que cuesta cada llamada, según la tabla de Google. Está aquí y no
   escrito por ahí porque es un dato de Google, no una decisión nuestra:
   si cambia, cambia en una línea. */
const COSTE_BUSCAR = 100;
const COSTE_VIDEOS = 1;
const CUOTA_DIARIA = 10000;

function cache_dir(): string {
  $d = __DIR__ . '/../data/cache';
  if (!is_dir($d)) @mkdir($d, 0777, true);
  return $d;
}

/* La clave normaliza lo que a efectos de búsqueda es lo mismo: mayúsculas
   y espacios de más. «  Bohemian   Rhapsody » y «bohemian rhapsody» son
   una sola consulta, y pagarlas dos veces sería tirar 100 unidades por
   una tecla. */
function cache_clave(string $consulta): string {
  /* preg_replace con el modificador /u devuelve null si $consulta no es
     UTF-8 válido — no avisa, solo null — y trim(null) es un TypeError
     fatal desde PHP 8.1. Comprobado a mano con una consulta con bytes
     sueltos fuera de UTF-8: la aplicación respondía con una traza de PHP
     en crudo en vez de un mensaje de búsqueda. La cadena original sigue
     sirviendo para la clave de caché aunque no se pueda normalizar. */
  $limpio = preg_replace('/\s+/u', ' ', $consulta);
  if ($limpio === null) $limpio = $consulta;
  $n = mb_strtolower(trim($limpio), 'UTF-8') ?: trim($limpio);
  return sha1($n);
}

function cache_leer(string $consulta): ?array {
  $f = cache_dir() . '/' . cache_clave($consulta) . '.json';
  if (!is_file($f)) return null;
  if (time() - (int)@filemtime($f) > CACHE_DIAS * 86400) { @unlink($f); return null; }
  $j = json_decode((string)@file_get_contents($f), true);
  return is_array($j) && isset($j['items']) ? $j : null;
}

function cache_guardar(string $consulta, array $items): void {
  /* Una búsqueda sin resultados no se guarda. Casi siempre es una errata
     —o YouTube teniendo un mal momento— y dejarla fijada treinta días
     significa que corregir la errata y volver a intentarlo devuelve la
     misma nada. */
  if (!$items) return;
  $d = cache_dir();
  $f = $d . '/' . cache_clave($consulta) . '.json';
  $tmp = $f . '.' . getmypid() . '.tmp';
  $datos = ['consulta' => $consulta, 'cuando' => time(), 'items' => $items];
  if (@file_put_contents($tmp, json_encode($datos, JSON_UNESCAPED_UNICODE)) !== false)
    @rename($tmp, $f);
  cache_podar($d);
}

/* Se tira lo más viejo, no lo menos usado. Contar usos obligaría a
   escribir en cada lectura, y una caché que escribe al leer deja de ser
   barata. */
function cache_podar(string $d): void {
  $ar = glob($d . '/*.json') ?: [];
  if (count($ar) <= CACHE_MAX) return;
  usort($ar, fn($a, $b) => filemtime($a) <=> filemtime($b));
  foreach (array_slice($ar, 0, count($ar) - CACHE_MAX) as $viejo) @unlink($viejo);
}

function cache_vaciar(): int {
  $ar = glob(cache_dir() . '/*.json') ?: [];
  foreach ($ar as $f) @unlink($f);
  return count($ar);
}

/* ---- El contador de gasto -------------------------------------------
   Sin esto, «me preocupa el límite de la API» no se puede contestar más
   que con una sensación. Con esto se ve el número y se sabe si hay que
   preocuparse o no.

   Se guarda el día junto al gasto: al cambiar de fecha vuelve a cero
   solo, sin necesidad de que nadie limpie nada. */
function cuota_archivo(): string { return __DIR__ . '/../data/cuota.json'; }

function cuota_hoy(): array {
  $j = json_decode((string)@file_get_contents(cuota_archivo()), true);
  $hoy = date('Y-m-d');
  if (!is_array($j) || ($j['fecha'] ?? '') !== $hoy)
    return ['fecha' => $hoy, 'unidades' => 0, 'llamadas' => 0, 'ahorradas' => 0];
  return $j + ['unidades' => 0, 'llamadas' => 0, 'ahorradas' => 0];
}

function cuota_sumar(int $unidades, bool $ahorrada = false): void {
  $c = cuota_hoy();
  if ($ahorrada) $c['ahorradas']++;
  else { $c['unidades'] += $unidades; $c['llamadas']++; }
  $f = cuota_archivo();
  $tmp = $f . '.' . getmypid() . '.tmp';
  if (@file_put_contents($tmp, json_encode($c, JSON_UNESCAPED_UNICODE)) !== false)
    @rename($tmp, $f);
}
