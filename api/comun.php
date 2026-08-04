<?php
/* Utilidades compartidas por el resto de la API. */

declare(strict_types=1);

const DIR_DATOS = __DIR__ . '/../data';

/* ═══════════════════════════════════════════════════════════════════
   NUNCA UNA PÁGINA EN BLANCO

   Esto se escribió después de instalar el proyecto por primera vez en un
   portátil ajeno. Aparecieron dos fallos «distintos»: guardar la clave de
   la API dejaba la pantalla en blanco, y la página de pedir salía en
   blanco en el móvil. Dos síntomas, dos flujos que no se tocan.

   Y era **el mismo fallo las dos veces**. `Preparar.bat` copia
   `php.ini-production`, que trae `display_errors = Off`. Con eso, un
   error fatal de PHP no imprime nada: manda un 200 con el cuerpo vacío.
   La pantalla en blanco no era el fallo — era el fallo escondiéndose.

   Y no es un caso raro: es lo que le pasa a TODO el mundo la primera vez
   que instala esto, porque en el ordenador de uno ya está todo puesto.
   Un programa que se instala con doble clic no puede permitirse un modo
   de fallo mudo: quien lo sufre no tiene una consola, tiene una fiesta
   dentro de media hora.

   Así que aquí se hacen dos cosas, y las dos ANTES de nada:

     1. Los errores se registran siempre en `data/errores.log`, salgan o
        no por pantalla. Un fallo que ocurrió una vez a las dos de la
        mañana tiene que poder mirarse al día siguiente.
     2. Un error fatal imprime una página que dice qué ha pasado, dónde y
        qué hacer. En HTML si el que pedía era un navegador, en JSON si
        era la propia aplicación — porque un `fetch` que recibe HTML
        donde esperaba JSON produce otro error distinto y despista más.

   No se toca `display_errors`: los avisos y las notas siguen calladas,
   que es lo correcto delante de invitados. Lo que deja de estar callado
   es lo que ROMPE la página.
   ═══════════════════════════════════════════════════════════════════ */
(function () {
  if (defined('KL_ERRORES_PUESTOS')) return;
  define('KL_ERRORES_PUESTOS', true);

  @ini_set('log_errors', '1');
  if (!is_dir(DIR_DATOS)) @mkdir(DIR_DATOS, 0777, true);
  if (is_dir(DIR_DATOS) && is_writable(DIR_DATOS))
    @ini_set('error_log', DIR_DATOS . '/errores.log');

  register_shutdown_function(function () {
    $e = error_get_last();
    if (!$e || !in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR,
                                      E_COMPILE_ERROR, E_USER_ERROR], true)) return;

    /* Si ya se ha enviado algo, la página está a medias y no en blanco:
       se deja como está y basta con el registro. Escribir encima solo
       serviría para partir el HTML por la mitad. */
    if (headers_sent()) return;

    $donde = basename((string)($e['file'] ?? '')) . ':' . (int)($e['line'] ?? 0);
    $qué   = (string)($e['message'] ?? 'error desconocido');

    /* Una pista de las que valen: falta una extensión de PHP. Es EL
       fallo de primera instalación —quien ya tenía PHP puesto no pasó
       por Preparar.bat y su php.ini no trae mbstring— y el mensaje de
       PHP («Call to undefined function mb_substr()») no le dice nada a
       quien solo quería montar un karaoke. */
    $pista = '';
    if (preg_match('/undefined function (\w+?)_/', $qué, $m)) {
      $ext = ['mb' => 'mbstring', 'curl' => 'curl', 'openssl' => 'openssl',
              'json' => 'json', 'iconv' => 'iconv'][$m[1]] ?? null;
      if ($ext) $pista = 'Le falta a PHP la extensión «' . $ext . '». '
        . 'Abre php\\php.ini, busca la línea «;extension=' . $ext . '» y '
        . 'quítale el punto y coma del principio. Luego cierra y vuelve a '
        . 'abrir Karaoke.bat.';
    }

    $json = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api/')
         || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

    http_response_code(500);
    if ($json) {
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok' => false, 'error' => $qué, 'donde' => $donde,
                        'pista' => $pista], JSON_UNESCAPED_UNICODE);
      return;
    }
    header('Content-Type: text/html; charset=utf-8');
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><meta charset="utf-8">'
       . '<title>Algo se ha roto</title>'
       . '<style>body{font:16px/1.6 system-ui,sans-serif;background:#12141a;'
       . 'color:#e8eaf0;margin:0;padding:8vh 6vw}h1{font-size:24px;margin:0 0 8px}'
       . 'code{background:#1d2029;padding:2px 6px;border-radius:5px;font-size:14px}'
       . 'p{max-width:62ch}.pista{background:#1d2029;border-left:4px solid #ffb020;'
       . 'padding:14px 18px;border-radius:0 8px 8px 0;margin:22px 0}'
       . 'a{color:#7cc4ff}</style>'
       . '<h1>Algo se ha roto en el servidor</h1>'
       . '<p>Esto no debería pasar, y antes salía como una página en blanco, '
       . 'que era peor. Aquí está lo que ha ocurrido:</p>'
       . '<p><code>' . $h($qué) . '</code><br><small>en ' . $h($donde) . '</small></p>'
       . ($pista ? '<div class="pista"><b>Lo más probable:</b><br>' . $h($pista) . '</div>' : '')
       . '<p>Queda apuntado en <code>data/errores.log</code>. '
       . 'En <a href="/qa.php">qa.php</a> hay una comprobación de todo lo que '
       . 'esta aplicación necesita.</p>';
  });
})();

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

/* ---- Leer un archivo de estado --------------------------------------
   Que no exista es normal: es el primer arranque, y se devuelven los
   valores por defecto sin decir nada. Copiar la carpeta a otro ordenador
   tiene que funcionar sin más.

   Que exista y esté ROTO es otra cosa muy distinta, y hasta ahora se
   trataban igual. Se devolvían los valores por defecto… y a la siguiente
   escritura se guardaban encima. Es decir: **un byte mal en el JSON y la
   biblioteca entera desaparecía en silencio**, sin error, sin aviso y sin
   posibilidad de recuperarla.

   Una página en blanco es molesta; esto es peor, porque no se nota hasta
   que ya no hay nada que hacer.

   Ahora el archivo roto se aparta con otro nombre en vez de morir debajo
   del nuevo. Se sigue arrancando —la fiesta no se puede quedar parada—
   pero lo que había queda ahí, con su fecha, y `qa.php` lo enseña. */
function leer_estado(string $nombre, array $porDefecto = []): array {
  $f = ruta_estado($nombre);
  if (!is_file($f)) return $porDefecto;
  $t = @file_get_contents($f);
  if ($t === false || $t === '') return $porDefecto;
  $d = json_decode($t, true);
  if (is_array($d)) return $d;

  $roto = DIR_DATOS . '/' . $nombre . '.roto-' . date('Ymd-His') . '.json';
  @rename($f, $roto);
  error_log('[OKC] ' . basename($f) . ' estaba corrupto (' . json_last_error_msg()
            . '). Apartado en ' . basename($roto) . ' para no perderlo.');
  return $porDefecto;
}

/* ---- Primer arranque -------------------------------------------------
   Lo que haga falta, se crea. Nunca se pregunta y nunca se avisa: copiar
   la carpeta a otro ordenador y hacer doble clic tiene que bastar.

   Se llama desde aquí, al cargar, y no desde `Preparar.bat`: un .bat que
   crea carpetas solo protege a quien lo ejecuta, y el caso que falla es
   justamente el de quien copia la carpeta y abre Karaoke.bat directamente. */
(function () {
  foreach ([DIR_DATOS, DIR_DATOS . '/videos', DIR_DATOS . '/cache'] as $d)
    if (!is_dir($d)) @mkdir($d, 0775, true);
})();

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

/* ---- Reconocer un enlace de YouTube ---------------------------------
   Esta función importa más de lo que parece: **pegar un enlace es el
   único camino que funciona sin clave de la API**, porque va por oEmbed
   y no gasta cuota. Si aquí falla un formato, quien no tiene clave se
   queda sin manera de añadir una canción — y eso fue justo lo que se vio
   en una instalación nueva.

   La `i` del final no es un detalle: quien copia un enlace de un correo
   o de un mensaje se lo trae a veces en mayúsculas, y hasta hoy
   `HTTPS://YOUTU.BE/…` no se reconocía. Tampoco `/v/ID`, que es el
   formato viejo de incrustar y sigue circulando por foros.

   Todos los formatos están en una prueba. No es por gusto: YouTube
   añade parámetros nuevos cada temporada, y lo que hay que poder hacer
   es añadir una fila y ver que nada se rompe. */
function id_video(string $s): ?string {
  $s = trim($s);
  if (preg_match('/^[A-Za-z0-9_-]{11}$/', $s)) return $s;
  if (preg_match('#(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)'
                 . '|youtu\.be/)([A-Za-z0-9_-]{11})#i', $s, $m))
    return $m[1];
  return null;
}

/* Un enlace de YouTube que NO lleva ningún vídeo dentro. Se distingue
   para poder decir qué pasa: «no he encontrado nada» delante de una
   lista de reproducción es una respuesta que no ayuda a nadie. */
function enlace_de_youtube(string $s): bool {
  return (bool)preg_match('#(?:^|//|\.)(?:youtu\.be|youtube\.com|youtube-nocookie\.com)/#i', trim($s));
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
      CURLOPT_USERAGENT      => 'OpenKaraokeCenter/1.1',
    ]);
    $r = curl_exec($c);
    /* curl_close() está obsoleta desde PHP 8.5 y no hacía nada desde la 8.0:
       el recurso se libera solo. Llamarla imprimía un aviso dentro del JSON
       y el navegador ya no podía leer la respuesta. */
    if ($r === false) {
      $n = curl_errno($c);
      /* ── El fallo que se comía a sí mismo ──────────────────────────
         Aquí ponía `$n === CURLE_PEER_FAILED_VERIFICATION`, sin más. Y
         **esa constante no existe en todas las versiones de curl**: en la
         que no está, PHP lanza un error fatal.

         Lo grave no es la constante. Es DÓNDE estaba: dentro del código
         que atiende «no he podido conectar». O sea, el camino de error se
         rompía justo cuando había un error, y con `display_errors=Off`
         eso es una página en blanco.

         Y el disparador es exactamente el estado de una instalación
         nueva: sin `cacert.pem`, curl no puede verificar a Google y
         devuelve este error. Es decir, el ordenador que MÁS necesitaba
         leer el mensaje —«te faltan los certificados, ejecuta
         Preparar.bat»— era el único que no podía verlo.

         Lo encontró una prueba de instalación limpia en una carpeta
         vacía, no una de las noventa y nueve que ya había: todas ellas
         daban por hecho un entorno que ya funcionaba.

         Los números van por `defined()`. Un error de red no puede
         depender de con qué curl se compiló PHP. */
      $sinCertificados = in_array($n, array_filter([
        defined('CURLE_SSL_CACERT') ? CURLE_SSL_CACERT : null,
        defined('CURLE_PEER_FAILED_VERIFICATION') ? CURLE_PEER_FAILED_VERIFICATION : null,
        defined('CURLE_SSL_CACERT_BADFILE') ? CURLE_SSL_CACERT_BADFILE : null,
        60, 77,   // los mismos números, por si la constante no existe
      ], fn($v) => $v !== null), true);

      $GLOBALS['ultimo_fallo_red'] = $sinCertificados
        ? 'Falta el archivo de certificados (cacert.pem) en la carpeta php. '
          . 'Ejecuta Preparar.bat otra vez y lo deja puesto.'
        : 'cURL ' . $n . ': ' . curl_error($c);
      return null;
    }
    return $r;
  }

  $ctx = stream_context_create(['http' => ['timeout' => 12, 'user_agent' => 'OpenKaraokeCenter/1.1']]);
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

/* ---- Quién está conectado ahora mismo --------------------------------
   Para el panel de diagnóstico hace falta saber si la tele está abierta y
   si hay móviles mirando. Es lo primero que se pregunta cuando algo no
   va, y hasta ahora no había forma de contestarlo.

   Se guarda en un archivo aparte y NO en `estado.json`, por dos motivos.
   El primero es que no es estado de la fiesta: es información sobre los
   aparatos, y si estuviera dentro subiría la versión del estado cada vez
   que alguien respira, y todas las pantallas se repintarían enteras
   cuatro veces por minuto para nada.

   El segundo es que aquí NO hace falta bloqueo. Una marca de tiempo que
   se pierde por una escritura simultánea se vuelve a escribir diez
   segundos después. Pagar un `flock` por esto sería caro y no compraría
   nada — y `php -S` atiende una petición cada vez.

   Se escribe como mucho una vez cada diez segundos por superficie: el
   sondeo pasa cada segundo y medio, y anotar «sigo aquí» siete veces
   seguidas no dice nada que no dijera la primera. */
const PRESENCIA_CADA = 10;

function anotar_presencia(string $quien): void {
  if (!preg_match('/^(operador|tele|movil)$/', $quien)) return;
  $ruta = DIR_DATOS . '/presencia.json';
  $ahora = time();
  $p = [];
  if (is_file($ruta)) {
    $j = json_decode((string)@file_get_contents($ruta), true);
    if (is_array($j)) $p = $j;
  }
  if (($p[$quien] ?? 0) > $ahora - PRESENCIA_CADA) return;
  $p[$quien] = $ahora;
  @file_put_contents($ruta, json_encode($p), LOCK_EX);
}

/* Segundos desde que se vio cada superficie. `null` = nunca. */
function presencia(): array {
  $ruta = DIR_DATOS . '/presencia.json';
  $p = is_file($ruta) ? json_decode((string)@file_get_contents($ruta), true) : [];
  if (!is_array($p)) $p = [];
  $ahora = time();
  $out = [];
  foreach (['operador', 'tele', 'movil'] as $q) {
    $out[$q] = isset($p[$q]) ? max(0, $ahora - (int)$p[$q]) : null;
  }
  return $out;
}

/* ---- El nombre de la red wifi ----------------------------------------
   Vivía dentro de proyector.php, y ahí solo lo podía usar la tele. Ahora
   lo necesitan dos sitios —la tele para dibujar el QR y la página de
   ajustes para sugerirlo—, así que se muda aquí: un concepto, un sitio.

   La contraseña NO se detecta y no se va a intentar. Windows no la
   entrega sin permisos de administrador, y pedir que el karaoke se
   ejecute como administrador para dibujar un QR no compensa ni de lejos.
 */
function ssid_actual(): string {
  if (stripos(PHP_OS_FAMILY, 'Windows') === false) return '';
  if (!function_exists('shell_exec')) return '';
  $s = @shell_exec('netsh wlan show interfaces 2>&1');
  if (!$s) return '';
  /* La salida está traducida al idioma del sistema; se busca la línea
     que empieza por SSID pero no por «BSSID». */
  foreach (preg_split('/\R/', $s) as $linea) {
    if (preg_match('/^\s*SSID\s*:\s*(.+?)\s*$/i', $linea, $m)) return $m[1];
  }
  return '';
}

/* ---- La dirección de este PC en la red local -------------------------
   Hace falta para el QR y para el aviso del operador, y NO se puede
   deducir de la URL del navegador: `Karaoke.bat` abre la aplicación en
   `localhost` a propósito —es lo que siempre funciona en el propio PC—,
   así que mirar `location.hostname` decía «no hay red» aunque la hubiera
   y los móviles llegaran perfectamente. El servidor escucha en 0.0.0.0;
   lo que hay que averiguar es con qué dirección se le ve desde fuera.

   Se prueba primero por nombre de equipo, que no necesita ejecutar nada,
   y solo si eso falla se recurre a ipconfig. Se descartan la de bucle y
   las 169.254.x, que son las que asigna Windows cuando NO hay red: dar
   una de esas sería peor que no dar ninguna. */
function ip_local(): ?string {
  static $ip = false;
  if ($ip !== false) return $ip;

  $vale = function (?string $d): bool {
    if (!$d || !filter_var($d, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return false;
    if (str_starts_with($d, '127.') || str_starts_with($d, '169.254.')) return false;
    return $d !== '0.0.0.0';
  };

  $candidatas = [];

  $host = @gethostname();
  if ($host) {
    $d = @gethostbyname($host);
    if ($vale($d)) $candidatas[] = $d;
  }

  if (!$candidatas && function_exists('shell_exec')) {
    $cmd = stripos(PHP_OS_FAMILY, 'Windows') !== false
      ? 'ipconfig'
      : 'ip -4 -o addr 2>/dev/null || hostname -I 2>/dev/null || ifconfig 2>/dev/null';
    $salida = @shell_exec($cmd);
    if ($salida && preg_match_all('/\b(\d{1,3}(?:\.\d{1,3}){3})\b/', $salida, $m)) {
      foreach ($m[1] as $d) if ($vale($d)) $candidatas[] = $d;
    }
  }

  /* Entre varias tarjetas —wifi, cable, VirtualBox, WSL— gana una de red
     doméstica de verdad. Las 192.168.x son las de casi cualquier router. */
  usort($candidatas, function ($a, $b) {
    $peso = function ($d) {
      if (str_starts_with($d, '192.168.')) return 0;
      if (str_starts_with($d, '10.'))      return 1;
      if (preg_match('/^172\.(1[6-9]|2\d|3[01])\./', $d)) return 2;
      return 3;
    };
    return $peso($a) <=> $peso($b);
  });

  return $ip = ($candidatas[0] ?? null);
}

/* ═══════════════════════════════════════════════════════════════════
   Normalizar lo que se escribe en «música ambiente»

   La gente pega lo que tiene a mano: la dirección de una lista, la de un
   canal, un `@usuario`, o solo el identificador. Pedirle que averigüe
   cuál de esas cosas es «el ID de la playlist» es pedirle que haga el
   trabajo del programa.

   Devuelve el identificador de una lista de reproducción, o cadena vacía
   si no se ha reconocido nada.

   ── El truco del canal ──────────────────────────────────────────────
   Un canal `UCxxxx` tiene siempre una lista con todas sus subidas cuyo
   identificador es el mismo cambiando `UC` por `UU`. Es una convención
   vieja de YouTube y sigue funcionando. Gracias a eso, poner la
   dirección de un canal equivale a poner «todo lo que suba, según lo
   suba», que para música de fondo es justo lo que se quiere: una lista
   fija se queda vieja y a los tres meses suena siempre lo mismo.

   Lo que NO se acepta: un `@usuario`. Desde el servidor no se puede
   traducir a su identificador sin pedírselo a YouTube, y esto tiene que
   funcionar con el router caído.
   ═══════════════════════════════════════════════════════════════════ */
function lista_ambiente(string $texto): string {
  $t = trim($texto);
  if ($t === '') return '';

  /* ?list=... dentro de una dirección */
  if (preg_match('~[?&]list=([A-Za-z0-9_-]{10,})~', $t, $m)) return $m[1];

  /* /channel/UCxxxx → su lista de subidas */
  if (preg_match('~/channel/(UC[A-Za-z0-9_-]{20,})~', $t, $m)) return 'UU' . substr($m[1], 2);

  /* Pegado a secas: una lista, o un canal */
  if (preg_match('~^UC[A-Za-z0-9_-]{20,}$~', $t)) return 'UU' . substr($t, 2);
  if (preg_match('~^[A-Za-z0-9_-]{10,}$~', $t)) return $t;

  return '';
}

/* ═══════════════════════════════════════════════════════════════════
   Guardar un JSON sin poder dejarlo a medias

   `file_put_contents` escribe encima del archivo bueno. Si el proceso se
   corta a mitad —se va la luz, alguien cierra la ventana negra, el disco
   se llena— lo que queda es un JSON truncado, y un JSON truncado no se
   puede leer: se pierden **todos** los ajustes, incluida la clave de la
   API. En mitad de una fiesta.

   Aquí se escribe en un archivo temporal al lado y se renombra encima.
   `rename` dentro del mismo sistema de archivos es atómico: o está el de
   antes o está el nuevo, nunca medio archivo. El bloqueo evita además que
   dos guardados simultáneos se pisen.

   El estado de la fiesta —cola, biblioteca, evento— no usa esto: usa
   `modificar_estado()`, que lee y escribe dentro de un mismo `flock` para
   que dos peticiones a la vez no pierdan cambios. Son dos problemas
   distintos: allí importa no perder actualizaciones, aquí no dejar el
   archivo roto.
   ═══════════════════════════════════════════════════════════════════ */
function guardar_json_atomico(string $ruta, array $datos): bool {
  $dir = dirname($ruta);
  if (!is_dir($dir) && !@mkdir($dir, 0775, true)) return false;

  $texto = json_encode($datos,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  if ($texto === false) return false;

  /* El temporal va en la MISMA carpeta a propósito: `rename` solo es
     atómico dentro del mismo sistema de archivos, y el temporal del
     sistema puede estar en otro disco. */
  $tmp = $ruta . '.' . getmypid() . '.tmp';

  $fh = @fopen($tmp, 'w');
  if (!$fh) return false;
  $ok = flock($fh, LOCK_EX)
        && fwrite($fh, $texto) !== false
        && fflush($fh);
  flock($fh, LOCK_UN);
  fclose($fh);

  if (!$ok) { @unlink($tmp); return false; }
  if (!@rename($tmp, $ruta)) { @unlink($tmp); return false; }
  return true;
}
