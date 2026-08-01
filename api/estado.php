<?php
/* ═══════════════════════════════════════════════════════════════════
   estado.php — biblioteca y cola, compartidas por todos

   Sustituye al localStorage: la cola vive en el servidor, así que el
   PC que canta y los móviles de los invitados ven exactamente lo mismo.

   GET                          → { biblioteca, cola, version }
   GET ?desde=N                 → responde solo si version > N (sondeo)
   POST {accion:'...' , ...}    → modifica
   ═══════════════════════════════════════════════════════════════════ */

declare(strict_types=1);
require __DIR__ . '/comun.php';

$cfg = cfg();

function estado_inicial(): array {
  return ['biblioteca' => [], 'cola' => [], 'sonando' => null, 'version' => 0];
}

/* ---- lectura --------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $e = leer_estado('estado', estado_inicial());

  /* `desde` se sigue aceptando, pero la respuesta sale al momento y es el
     cliente quien compara la versión y vuelve a preguntar al rato.

     Antes se retenía aquí la respuesta hasta 20 segundos. Sobre el servidor
     que trae PHP (`php -S`) eso no funciona en Windows: atiende una petición
     cada vez y no sabe crear procesos hijo, así que un solo dispositivo
     esperando dejaba clavada la aplicación entera. Medido: con un sondeo
     abierto, cargar otra página tardaba 27 segundos. Con el PC, la tele y
     dos móviles, la fiesta se queda parada.

     Preguntar cada segundo y medio cuesta unos milisegundos por vuelta y
     deja el servidor libre el resto del tiempo. */
  $e['peticiones'] = (bool)$cfg['peticiones'];
  $e['con_clave']  = trim((string)$cfg['api_key']) !== ''
                     && $cfg['api_key'] !== 'PON_AQUI_TU_CLAVE';
  salir_json(['ok' => true] + $e);
}

/* ---- escritura ------------------------------------------------------- */
$in     = cuerpo_json();
$accion = (string)($in['accion'] ?? '');
$invitado = $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($in['invitado']);

/* Los invitados solo pueden pedir canciones, nada más. */
if ($invitado) {
  if (!$cfg['peticiones']) error_json('las peticiones están cerradas ahora mismo', 403);
  if ($accion !== 'anadir_cola') error_json('acción no permitida para invitados', 403);
  $pass = (string)$cfg['clave_fiesta'];
  if ($pass !== '' && (string)($in['clave'] ?? '') !== $pass) {
    error_json('contraseña de la fiesta incorrecta', 403);
  }
}

function pista_desde(array $v, ?string $quien = null): array {
  return [
    'id'       => uid(),
    'videoId'  => (string)$v['videoId'],
    'title'    => (string)($v['title'] ?? 'Sin título'),
    'channel'  => (string)($v['channel'] ?? ''),
    'thumb'    => (string)($v['thumb'] ?? ''),
    'duration' => (int)($v['duration'] ?? 0),
    'local'    => video_local((string)$v['videoId']),
    'pedida'   => $quien,
  ];
}

$resultado = modificar_estado('estado', function (array $e) use ($in, $accion, $invitado, $cfg) {
  $e = $e ?: estado_inicial();
  $e['biblioteca'] = $e['biblioteca'] ?? [];
  $e['cola']       = $e['cola']       ?? [];

  switch ($accion) {

    case 'anadir_cola': {
      $v = $in['video'] ?? null;
      if (!is_array($v) || empty($v['videoId'])) error_json('falta el vídeo');

      $quien = trim((string)($in['quien'] ?? ''));
      if ($invitado) {
        if ($quien === '') error_json('dime tu nombre para saber de quién es la canción');
        $suyas = 0;
        foreach ($e['cola'] as $t) if (($t['pedida'] ?? null) === $quien) $suyas++;
        if ($suyas >= (int)$cfg['limite_por_invitado']) {
          error_json('Ya tienes ' . $suyas . ' canciones en la cola. Espera a que suenen.', 429);
        }
      }
      foreach ($e['cola'] as $t) {
        if ($t['videoId'] === $v['videoId']) error_json('esa canción ya está en la cola', 409);
      }
      $e['cola'][] = pista_desde($v, $quien !== '' ? $quien : null);
      break;
    }

    case 'quitar_cola': {
      $id = (string)($in['id'] ?? '');
      $e['cola'] = array_values(array_filter($e['cola'], fn($t) => $t['id'] !== $id));
      if (($e['sonando'] ?? null) === $id) $e['sonando'] = null;
      break;
    }

    case 'ordenar_cola': {
      $orden = $in['orden'] ?? [];
      if (!is_array($orden)) error_json('orden no válido');
      $porId = [];
      foreach ($e['cola'] as $t) $porId[$t['id']] = $t;
      $nueva = [];
      foreach ($orden as $id) if (isset($porId[$id])) { $nueva[] = $porId[$id]; unset($porId[$id]); }
      foreach ($porId as $t) $nueva[] = $t;   // por si llegó algo nuevo mientras
      $e['cola'] = $nueva;
      break;
    }

    case 'vaciar_cola':
      $e['cola'] = []; $e['sonando'] = null;
      break;

    case 'sonando':
      $e['sonando'] = $in['id'] ?? null;
      break;

    case 'anadir_biblioteca': {
      $v = $in['video'] ?? null;
      if (!is_array($v) || empty($v['videoId'])) error_json('falta el vídeo');
      foreach ($e['biblioteca'] as $t) if ($t['videoId'] === $v['videoId']) return $e;
      $e['biblioteca'][] = pista_desde($v);
      break;
    }

    case 'quitar_biblioteca': {
      $vid = (string)($in['videoId'] ?? '');
      $e['biblioteca'] = array_values(array_filter($e['biblioteca'], fn($t) => $t['videoId'] !== $vid));
      break;
    }

    case 'reemplazar_biblioteca': {
      $lista = $in['biblioteca'] ?? [];
      if (!is_array($lista)) error_json('biblioteca no válida');
      $e['biblioteca'] = array_map(fn($v) => pista_desde($v), array_filter($lista, 'is_array'));
      break;
    }

    case 'actualizar_pista': {
      /* Refresca título y carátula de una entrada ya guardada. */
      $vid = (string)($in['videoId'] ?? '');
      foreach (['biblioteca', 'cola'] as $k) {
        foreach ($e[$k] as &$t) {
          if ($t['videoId'] === $vid) {
            foreach (['title', 'channel', 'thumb', 'duration', 'local'] as $c) {
              if (isset($in[$c])) $t[$c] = $in[$c];
            }
          }
        }
        unset($t);
      }
      break;
    }

    default:
      error_json('acción desconocida: ' . $accion);
  }

  $e['version'] = (int)($e['version'] ?? 0) + 1;
  return $e;
});

salir_json(['ok' => true] + $resultado);
