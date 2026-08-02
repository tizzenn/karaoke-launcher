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

/* ---- Dónde se guarda el estado ---------------------------------------
   Normalmente en `data/estado.json`. Las pruebas piden `?bd=pruebas` y
   trabajan sobre `data/pruebas.json`, para no tocar la biblioteca de
   nadie: una suite que borra la cola de la fiesta no la ejecuta nadie
   dos veces.

   El nombre se valida con la misma regla que `ruta_estado()`, así que no
   se puede escapar de la carpeta. Y esto no abre ninguna puerta nueva:
   quien esté en la red local ya tiene el puesto de mando entero. */
$BD = preg_match('/^[a-z_]{1,20}$/', (string)($_GET['bd'] ?? '')) ? $_GET['bd'] : 'estado';

function estado_inicial(): array {
  return [
    'biblioteca' => [], 'cola' => [],
    'historial'  => [],
    'evento'     => evento_inicial(),
    'calentamiento' => 0,
    'paneles'       => null,   // null = todos; si no, lista de los activos
    'panelFijo'     => null,   // uno fijo, sin rotar
    'version'    => 0,
  ];
}

/* ---- El estado del evento -------------------------------------------
   Es el corazón de la v1.1: la aplicación no abre pantallas, mantiene un
   estado y cada superficie decide qué pinta a partir de él. Vive aquí
   dentro, y no en un archivo aparte, para que viaje con el mismo sondeo
   que ya usaban la tele y los móviles desde la v1.0. Cero canales
   nuevos.

   `recien` guarda título y canal de la que acaba de terminar, porque al
   retirarse de la cola ya no habría dónde buscarlos y la tele necesita
   decir «se acabó X, ahora va Y». */
function evento_inicial(): array {
  return ['estado' => 'ESPERA', 'pistaId' => null, 'recien' => null,
          'desde' => 0, 'segundos' => 0, 'n' => 0];
}

const ESTADOS_EVENTO = ['ESPERA', 'PREPARADA', 'LLAMADA', 'INTERPRETACION', 'FIN_ACTUACION'];

/* Los archivos de las versiones anteriores no traen `evento` ni
   `historial`. Se rellenan al vuelo en vez de rechazarlos: la
   compatibilidad hacia atrás no es opcional, hay gente con su biblioteca
   dentro de ese archivo. */
function completar(array $e): array {
  $e['biblioteca'] = $e['biblioteca'] ?? [];
  $e['cola']       = $e['cola']       ?? [];
  $e['historial']  = $e['historial']  ?? [];
  $e['evento']     = is_array($e['evento'] ?? null) ? $e['evento'] : evento_inicial();
  /* Hasta la primera versión de la v1.1 aquí se guardaba una hora de
     fin. Si aparece una, se traduce: futura = encendido, pasada = no. */
  $c = (int)($e['calentamiento'] ?? 0);
  $e['calentamiento'] = $c > 1 ? ($c > time() ? 1 : 0) : ($c ? 1 : 0);
  $e['paneles']   = is_array($e['paneles'] ?? null) ? array_values($e['paneles']) : null;
  $e['panelFijo'] = isset($e['panelFijo']) && $e['panelFijo'] !== null
                    ? (string)$e['panelFijo'] : null;
  if (!in_array($e['evento']['estado'] ?? '', ESTADOS_EVENTO, true)) {
    $e['evento'] = evento_inicial();
  }
  return $e;
}

/* ---- lectura --------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $e = completar(leer_estado($BD, estado_inicial()));

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
  /* La marca de la edición —«A Veiga Edition»— es un ajuste, no otra
     versión del programa. Aquí viaja igual que cualquier otro. */
  $e['edicion']    = (string)($cfg['edicion'] ?? '');
  /* La hora del servidor viaja con el estado: la tele puede tener el
     reloj desajustado y la cuenta atrás del calentamiento saldría mal. */
  $e['ahora']      = time();
  /* La dirección de este PC en la red local y el puerto: es lo que hay
     que enseñar a los móviles. NO se puede deducir de la URL del
     navegador, porque Karaoke.bat abre la aplicación en localhost. */
  $e['ip_local']   = ip_local();
  $e['puerto']     = (int)($_SERVER['SERVER_PORT'] ?? 8123);
  salir_json(['ok' => true] + con_sonando($e));
}

/* «Qué se está cantando» se DEDUCE del evento, no se guarda.

   Se guardaba aparte y eso son dos verdades: al volver del vídeo al
   operador había que escribir las dos, la respuesta de la primera llegaba
   con el evento viejo y pisaba el nuevo. Se tapó con un contador
   monótono; el contador se queda —protege de otras cosas— pero la causa
   ya no está.

   Sigue viajando en la respuesta porque lo leen la pantalla del público y
   la página de los invitados, y porque puede haber una pestaña abierta
   desde antes del cambio. Deducido no puede contradecir al evento.

   Solo hay canción sonando en INTERPRETACION: en la cuenta atrás está
   señalada, pero todavía no suena, y decir lo contrario pondría el vídeo
   en la tele antes de tiempo. */
function con_sonando(array $e): array {
  $ev = $e['evento'] ?? [];
  $e['sonando'] = (($ev['estado'] ?? '') === 'INTERPRETACION')
                  ? ($ev['pistaId'] ?? null)
                  : null;
  return $e;
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

$resultado = modificar_estado($BD, function (array $e) use ($in, $accion, $invitado, $cfg) {
  $e = completar($e ?: estado_inicial());

  switch ($accion) {

    /* ---- El motor de estados ---------------------------------------
       Solo el PC escribe aquí. La tele lee y obedece; si mandaran las
       dos, se pelearían por decidir la siguiente canción. */
    case 'evento': {
      $ev = $in['evento'] ?? null;
      if (!is_array($ev) || !in_array($ev['estado'] ?? '', ESTADOS_EVENTO, true)) {
        error_json('estado de evento no válido');
      }
      $e['evento'] = [
        'estado'  => (string)$ev['estado'],
        'pistaId' => isset($ev['pistaId']) ? (string)$ev['pistaId'] : null,
        'recien'  => is_array($ev['recien'] ?? null) ? [
          'title'   => (string)($ev['recien']['title']   ?? ''),
          'channel' => (string)($ev['recien']['channel'] ?? ''),
        ] : null,
        /* La marca de tiempo la pone el servidor, no el cliente: la tele
           dibuja la cuenta atrás por su cuenta y necesita un reloj común.
           El del PC del operador puede ir desajustado. */
        'desde'    => time(),
        'segundos' => max(0, min(15, (int)($ev['segundos'] ?? 0))),
        /* Contador que solo sube. Sirve al cliente para descartar
           respuestas atrasadas que traerían un estado ya superado. */
        'n'        => (int)($ev['n'] ?? 0),
      ];
      /* Arrancar la primera canción termina el calentamiento, sin que
         nadie tenga que acordarse de apagarlo. Es la señal más honesta
         que hay: si ya se está cantando, el rato de antes se acabó. */
      /* Con la cuenta atrás ya en marcha, el rato de antes se ha acabado:
         no hace falta esperar a que suene la primera nota. */
      if (in_array($e['evento']['estado'], ['LLAMADA', 'INTERPRETACION'], true)) {
        $e['calentamiento'] = 0;
      }
      break;
    }

    /* ---- Fin de una actuación --------------------------------------
       Retirar de la cola, archivar en el historial y soltar «sonando»
       van juntos en una sola escritura. Si fueran tres peticiones, un
       móvil pidiendo a la vez podría colarse en medio y dejar el estado
       a mitad. */
    case 'fin_actuacion': {
      $id = (string)($in['id'] ?? '');
      $retirar = !empty($in['retirar']);
      $cantada = null;
      foreach ($e['cola'] as $t) if (($t['id'] ?? '') === $id) { $cantada = $t; break; }

      if ($cantada) {
        $cantada['cantada_en'] = time();
        array_unshift($e['historial'], $cantada);
        /* Cien caben de sobra en una noche larga y el archivo no crece
           sin control. */
        $e['historial'] = array_slice($e['historial'], 0, 100);
      }
      if ($retirar && $id !== '') {
        $e['cola'] = array_values(array_filter($e['cola'], fn($t) => ($t['id'] ?? '') !== $id));
      }
      break;
    }

    case 'vaciar_historial':
      $e['historial'] = [];
      break;

    /* ---- Calentamiento ----------------------------------------------
       Un interruptor, no un temporizador.

       Al principio duraba unos minutos configurables. Era peor: si el
       tiempo se agotaba antes de que el operador arrancara —y se agota
       siempre, porque nadie sabe a qué hora empieza de verdad una
       fiesta—, la tele pasaba del calentamiento, que es útil, a la
       pantalla de espera, que está vacía. Justo al revés de lo que
       interesa.

       Ahora se enciende y se queda hasta que suena la primera canción,
       que es la única señal honesta de que el rato de antes se acabó. */
    case 'calentamiento':
      $e['calentamiento'] = !empty($in['activo']) ? 1 : 0;
      break;

    /* ---- Qué cartelones se enseñan --------------------------------
       El operador los enciende y apaga en directo, sin recargar la
       tele. Un cartelón puede estorbar sobre la marcha —el de la cola
       si está vacía, el de los datos si no dicen nada— y esperar a la
       siguiente fiesta para arreglarlo no sirve de nada.

       Lista vacía significa «ninguno», y eso es una elección legítima:
       deja la tele con el cartel de espera limpio. Por eso se distingue
       de null, que significa «todos». */
    case 'paneles': {
      $lista = $in['paneles'] ?? null;
      $e['paneles'] = is_array($lista)
        ? array_values(array_filter(array_map('strval', $lista), fn($x) => $x !== ''))
        : null;
      $e['panelFijo'] = isset($in['fijo']) && $in['fijo'] !== null && $in['fijo'] !== ''
        ? (string)$in['fijo'] : null;
      break;
    }

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
      $e['cola'] = [];
      break;

    /* La acción existía para guardar «qué se está cantando» aparte del
       evento. Ya no guarda nada: ese dato tiene un solo dueño y es
       `evento.pistaId`. Se sigue aceptando —sin hacer nada— porque una
       pestaña vieja que quedara abierta la mandaría, y responderle
       «acción desconocida» sería peor que ignorarla. */
    case 'sonando':
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

salir_json(['ok' => true] + con_sonando($resultado));
