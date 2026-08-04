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
    /* En qué espacio está la fiesta: karaoke o dj. Era una
       preferencia de cada aparato, y con una cola por espacio deja de
       poder serlo: si el operador está en la Cabina DJ y la tele sigue
       enseñando la cola del karaoke, están contando cosas distintas. */
    'espacio'    => 'karaoke',
    'calentamiento' => 0,
    'paneles'       => null,   // null = todos; si no, lista de los activos
    /* El Modo Show: la carta de reto que está en pantalla, y nada más.
       Vive aquí porque la carta la enseña la tele y la saca el operador
       desde otro aparato. Vacío mientras no se use, que es casi siempre. */
    'show'          => ['reto' => null],
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

/* Los dos espacios. Cada uno tiene su propia cola: cambiar de espacio no
   es filtrar la misma lista, es cambiar de sitio de trabajo y encontrarlo
   como lo dejaste.

   Fueron tres. «Freestyle» se quitó en la v1.2 porque no era un espacio:
   se diferenciaba de Karaoke solo en la palabra que se le pegaba a la
   búsqueda, y eso ahora es un campo de texto a la vista.

   `ESPACIOS_VIEJOS` traduce lo que ya está guardado. No se descarta ni
   una canción: una pista de una fiesta anterior marcada «freestyle»
   aparece en el Karaoke, que es donde se cantaba. Borrar datos de alguien
   para simplificar un `switch` no es una opción. */
const ESPACIOS = ['karaoke', 'dj'];
const ESPACIOS_VIEJOS = ['freestyle' => 'karaoke', 'mc' => 'karaoke'];

function espacio_valido($e) {
  $e = (string)$e;
  $e = ESPACIOS_VIEJOS[$e] ?? $e;
  return in_array($e, ESPACIOS, true) ? $e : 'karaoke';
}

/* ---- El Modo Show ----------------------------------------------------
   Cartas de reto, y NADA MÁS.

   Aquí hubo un marcador de equipos a medio escribir. Se ha quitado, y el
   motivo merece quedarse: `DECISIONES.md` promete que en esta aplicación
   nunca hay puntuaciones ni clasificaciones, y `ajustes.php` se lo dice
   al usuario con esas palabras. Me convencí de que un marcador de
   EQUIPOS no contaba porque nadie recibe nota por su voz — y era verdad,
   pero no era el punto. El punto es que en cuanto hay un número en
   pantalla, la sala mira el número. Las cartas de reto hacen lo que se
   quería del Modo Show —que la gente se lance— sin que nadie pierda.

   Una promesa escrita en la pantalla de ajustes vale más que una función
   que a lo mejor se usa una noche.

   `n` sube en cada carta para que la tele sepa que es OTRA aunque el
   texto se repita: sin eso, sacar dos veces seguidas el mismo reto no
   volvía a animar nada y parecía que el botón se había roto. */
function normalizar_show($s): array {
  $s = is_array($s) ? $s : [];
  $r = $s['reto'] ?? null;
  $reto = null;
  if (is_array($r) && trim((string)($r['texto'] ?? '')) !== '') {
    $reto = ['texto' => trim(mb_substr((string)$r['texto'], 0, 160)),
             'n'     => max(0, (int)($r['n'] ?? 0))];
  }
  return ['reto' => $reto];
}

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
  $e['espacio']   = espacio_valido($e['espacio'] ?? '');
  /* Las pistas de antes de las colas separadas no traen espacio: son del
     karaoke, que es lo único que había. */
  foreach (['cola', 'historial'] as $donde) {
    foreach ($e[$donde] as $i => $t) {
      $e[$donde][$i]['espacio'] = espacio_valido($t['espacio'] ?? '');
    }
  }
  /* ---- «Descargada» NO se guarda: se mira el disco --------------------
     Esto ha fallado dos veces, y las dos por lo mismo: había un campo
     `local` guardado en el JSON que decía si la canción estaba bajada, y
     un archivo en `data/videos/` que decía la verdad. Dos fuentes para el
     mismo dato, y cuando dejaban de coincidir el operador veía el icono
     de descargada, pulsaba para borrar y el servidor le contestaba que no
     existe. Peor todavía: creía que podía cantarla sin internet.

     La misma regla que ya vale para `sonando` y para `curId` — lo que se
     puede deducir no se guarda — aplicada al sitio donde faltaba. Ahora
     `local` se recalcula en cada lectura mirando si el archivo está. Si
     alguien borra un vídeo desde el explorador de Windows, la aplicación
     se entera en el siguiente sondeo sin que nadie le diga nada.

     Cuesta un `is_file()` por pista y vuelta, que sobre una cola de
     veinte canciones es ruido comparado con leer el JSON. */
  foreach (['cola', 'biblioteca', 'historial'] as $donde) {
    foreach ($e[$donde] as $i => $t) {
      if (!empty($t['videoId'])) $e[$donde][$i]['local'] = video_local((string)$t['videoId']);
    }
  }

  $e['show'] = normalizar_show($e['show'] ?? null);
  $e['paneles']   = is_array($e['paneles'] ?? null) ? array_values($e['paneles']) : null;
  /* Aquí estaba `panelFijo`: el cartelón que se quedaba quieto sin
     rotar. Se ha quitado porque hacía lo mismo que dejar marcado uno
     solo, y dos formas de conseguir el mismo resultado son dos formas de
     dudar. Un estado compartido menos. */
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
  $e['tema']       = in_array($cfg['tema'] ?? '', ['clasico','fiesta','kids','show'], true)
                     ? $cfg['tema'] : 'clasico';
  /* La hora del servidor viaja con el estado: la tele puede tener el
     reloj desajustado y la cuenta atrás del calentamiento saldría mal. */
  $e['ahora']      = time();
  /* La wifi de la fiesta, para que el operador pueda enseñar el QR que
     conecta un móvil a la red. Va aquí y no en una petición aparte
     porque es un dato de la fiesta y viaja con todo lo demás.

     La clave viaja EN CLARO por la red local, y eso es aceptable por lo
     que es: la contraseña de la wifi a la que ya está conectado quien la
     recibe. Quien pueda leer esta respuesta ya está dentro. */
  /* Quién ha pasado por aquí. Se anota antes de contestar para que la
     propia respuesta ya lo incluya. */
  anotar_presencia((string)($_GET['quien'] ?? ''));
  $e['presencia'] = presencia();
  /* El apagón y el confeti los pinta la tele, que no puede leer
     ajustes.json — viaja con el estado igual que el termómetro. */
  $e['efectos'] = (bool)($cfg['efectos_escenicos'] ?? true);
  /* El termómetro viaja con el estado porque lo pinta la tele y lo
     pinta el móvil, y ninguno de los dos puede leer los ajustes. */
  $e['termometro'] = [
    'on' => (bool)($cfg['termometro'] ?? true),
    'niveles' => array_values(array_filter(
      is_array($cfg['termometro_niveles'] ?? null) ? $cfg['termometro_niveles'] : [],
      fn($n) => is_array($n) && trim((string)($n['texto'] ?? '')) !== ''))
  ];
  $e['wifi'] = ['ssid'  => trim((string)($cfg['wifi_ssid'] ?? '')) ?: ssid_actual(),
                'clave' => (string)($cfg['wifi_clave'] ?? '')];
  /* La dirección de este PC en la red local y el puerto: es lo que hay
     que enseñar a los móviles. NO se puede deducir de la URL del
     navegador, porque Karaoke.bat abre la aplicación en localhost. */
  /* La Cabina DJ: qué suena cuando no canta nadie. Viaja con el estado
     porque el operador tiene que poder cambiarla sin recargar, y porque
     la lista normalizada la calcula el servidor: el navegador no tiene
     por qué saber que un canal `UC…` es la lista `UU…`. */
  $e['ambiente'] = [
    'fuente'  => (string)($cfg['ambiente_fuente'] ?? 'youtube'),
    'lista'   => lista_ambiente((string)($cfg['ambiente_lista'] ?? '')),
    'carpeta' => trim((string)($cfg['ambiente_carpeta'] ?? '')) !== '',
    'volumen' => (int)($cfg['ambiente_volumen'] ?? 35),
    'auto'    => !empty($cfg['ambiente_auto']),
  ];
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

function pista_desde(array $v, ?string $quien = null, string $espacio = 'karaoke'): array {
  return [
    'id'       => uid(),
    'espacio'  => espacio_valido($espacio),
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
        /* ---- El instante en que el vídeo estaba en el segundo cero ----
           Esto SÍ lo pone el cliente, y es la única cosa del evento que
           el servidor no puede saber: es el momento exacto en el que el
           reproductor del operador empezó a sonar de verdad, medido por
           él. `desde` no sirve para esto —lo pone el servidor con
           granularidad de un segundo, y aquí medio segundo se oye—.

           Con este número la pantalla del público no intenta «arrancar a
           la vez», que es imposible entre dos reproductores de YouTube:
           calcula por dónde va la canción y salta ahí. Llegue cuando
           llegue, acaba alineada. */
        't0'       => isset($ev['t0']) ? (float)$ev['t0'] : null,
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
      /* Decisión de producto revisada (2026-08-04): una fiesta se mueve
         entre música y karaoke y vuelve — no son dos fiestas selladas.
         El invitado ya puede elegir espacio explícitamente en su
         petición (antes se ignoraba `espacio` y se forzaba siempre el
         activo). Sigue sin poder mandar cualquier cosa: `espacio_valido()`
         solo deja pasar 'karaoke' o 'dj', nunca texto libre, y si no
         manda nada se cae al espacio activo, que sigue siendo el
         comportamiento de siempre. */
      $espacio = espacio_valido($in['espacio'] ?? ($invitado ? $e['espacio'] : ''));

      /* ---- Repetidas: la unidad no es la canción, es la actuación ----
         Aquí antes se rechazaba cualquier canción que ya estuviera en la
         cola, y era el error de fondo del proyecto en miniatura: tratar
         una cola de karaoke como una lista de reproducción.

         **Si Ana canta «Vivir mi vida» y una hora después María también
         quiere cantarla, eso no es una canción repetida: son dos
         actuaciones distintas.** Y es de las cosas más normales que
         pasan en un karaoke. Bloquearlo es decirle a María que llega
         tarde a algo que no era una carrera.

         Lo que sí es casi siempre un error es que **la misma persona**
         pida dos veces lo mismo: ha pulsado dos veces, o no ha visto que
         ya estaba. Eso se para, y no como un fallo sino ayudando.

         El operador no se bloquea nunca: tiene la cola delante, ve el
         duplicado y sabe lo que hace. Si se ha equivocado, se lo
         pregunta su propia pantalla antes de mandar nada.

         La misma canción en dos espacios distintos no es repetida: el
         instrumental en Freestyle y el karaoke en Karaoke son la misma
         grabación con otra intención. */
      $modo = in_array($cfg['repetidas'] ?? 'siempre',
                       ['siempre', 'avisar', 'no'], true)
              ? $cfg['repetidas'] : 'siempre';

      $mismas = 0; $suya = false;
      foreach ($e['cola'] as $t) {
        if ($t['videoId'] !== $v['videoId']) continue;
        if (($t['espacio'] ?? 'karaoke') !== $espacio) continue;
        $mismas++;
        if ($quien !== '' && ($t['pedida'] ?? null) === $quien) $suya = true;
      }

      /* Caso 1: ya la has pedido tú. Se para siempre, y el texto no dice
         que hayas hecho nada mal. */
      if ($suya) {
        error_json('Esa canción ya está esperándote en la cola.', 409);
      }
      /* Caso 4: el dueño ha decidido que en su fiesta no se repite. */
      if ($modo === 'no' && $mismas > 0) {
        error_json('En esta fiesta cada canción suena una vez. Elige otra y seguimos.', 409);
      }

      $e['cola'][] = pista_desde($v, $quien !== '' ? $quien : null, $espacio);

      /* Caso 3: varias personas han elegido lo mismo. No se impide nada
         —eso es una fiesta funcionando— pero se dice, porque a quien
         acaba de pedirla le puede apetecer saberlo. Es información, no
         un aviso: viaja como un campo suelto y quien la pinta decide. */
      if ($mismas >= 1) {
        $e['_varias'] = ['videoId' => (string)$v['videoId'], 'cuantas' => $mismas + 1];
      }
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

    case 'vaciar_cola': {
      /* Vaciar vacía EL ESPACIO en el que estás. Vaciar los dos de una
         vez, estando en uno, sería una sorpresa muy cara. */
      $cual = in_array($in['espacio'] ?? '', ESPACIOS, true) ? $in['espacio'] : null;
      $e['cola'] = $cual === null ? []
                 : array_values(array_filter($e['cola'],
                     fn($t) => ($t['espacio'] ?? 'karaoke') !== $cual));
      break;
    }

    case 'espacio':
      $e['espacio'] = espacio_valido($in['espacio'] ?? '');
      break;

    /* ---- Modo Show: sacar una carta de reto -----------------------
       Se manda el texto ya elegido, no «dame una carta»: el archivo de
       retos lo lee el operador, que es quien puede enseñárselo antes de
       publicarlo. El servidor solo lo reparte y lleva la cuenta.

       Nótese lo que NO se acepta: ningún identificador de canción, de
       pista ni de invitado. Una carta no se le engancha a nadie. */
    case 'show': {
      $s = $e['show'] ?? [];
      if (array_key_exists('reto', $in)) {
        $r = $in['reto'];
        $s['reto'] = (is_array($r) && trim((string)($r['texto'] ?? '')) !== '')
          ? ['texto' => $r['texto'],
             /* El contador lo lleva el servidor: si lo llevara el cliente,
                dos ventanas mandarían el mismo número y la tele se
                quedaría sin animar una de las dos cartas. */
             'n' => (int)(($e['show']['reto']['n'] ?? 0)) + 1]
          : null;
      }
      $e['show'] = normalizar_show($s);
      break;
    }

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
      /* Refresca título y carátula de una entrada ya guardada.

         `array_key_exists` y NO `isset`, y esto costó un fallo real: en
         PHP `isset($x)` es FALSE cuando `$x` existe y vale null. Al
         borrar un vídeo descargado, el operador manda `local: null` —«ya
         no está en disco»— y aquí se tiraba en silencio. La canción
         seguía enseñando el icono de descargada y, peor, cualquiera
         habría jurado que el archivo estaba ahí.

         La diferencia entre «no me consta» y «me consta que ya no» es
         justo lo que hay que respetar en una función que actualiza
         campos sueltos. */
      $vid = (string)($in['videoId'] ?? '');
      foreach (['biblioteca', 'cola'] as $k) {
        foreach ($e[$k] as &$t) {
          if ($t['videoId'] === $vid) {
            /* `local` ya no está en esta lista, y no es un olvido: lo
               decide el disco en `completar()`. Aceptarlo aquí volvería a
               crear las dos fuentes de verdad que causaron el fallo. */
            foreach (['title', 'channel', 'thumb', 'duration'] as $c) {
              if (array_key_exists($c, $in)) $t[$c] = $in[$c];
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
