<?php
declare(strict_types=1);
require __DIR__ . '/api/comun.php';
require __DIR__ . '/api/claves.php';

$cfg     = cfg();
$archivo = DIR_DATOS . '/ajustes.json';
$aviso   = null;
$tipo    = 'o';

/* ---- puerta con contraseña, si la hay ------------------------------- */
$claveAjustes = (string)$cfg['clave_ajustes'];
session_start();
$entrado = $claveAjustes === '' || !empty($_SESSION['ajustes_ok']);

if (!$entrado && ($_POST['entrar'] ?? '') !== '') {
  if (hash_equals($claveAjustes, (string)($_POST['clave'] ?? ''))) {
    $_SESSION['ajustes_ok'] = true;
    $entrado = true;
  } else {
    $aviso = 'Contraseña incorrecta.';
    $tipo  = 'e';
  }
}

/* ---- guardar --------------------------------------------------------- */
if ($entrado && ($_POST['guardar'] ?? '') !== '') {
  $nuevo = [
    /* Ya no hay campo para esto en el formulario: lo sustituyen las
       filas de 'api_keys' de más abajo. Se conserva el valor guardado
       tal cual, sin campo editable, por si algo externo lo lee todavía
       — perderlo al guardar cualquier otra cosa sería silencioso. */
    'api_key'             => (string)($cfg['api_key'] ?? ''),
    'api_keys'            => (function () {
      $out = [];
      foreach (($_POST['apik_key'] ?? []) as $i => $key) {
        $key = trim((string)$key);
        if ($key === '') continue;
        $out[] = [
          'nombre'      => trim(mb_substr((string)($_POST['apik_nombre'][$i] ?? ''), 0, 30)) ?: 'Sin nombre',
          'key'         => $key,
          'activa'      => isset($_POST['apik_activa'][$i]),
          /* Quién es su dueño, no cómo se llama la clave: distinto del
             "nombre" de arriba. Sirve para saber de un vistazo que la
             tercera es "la del bar" o "la de Marta" cuando hay varias. */
          'propietario' => trim(mb_substr((string)($_POST['apik_propietario'][$i] ?? ''), 0, 30)),
        ];
      }
      return $out;
    })(),
    /* Ya no hay campo para esto en esta pagina: el filtro vive en la
       barra del operador. Se conserva el valor guardado en vez de
       vaciarlo — lo sigue usando quien busque sin mandar filtro (la
       pagina de pedir del movil). Perderlo al guardar otra cosa seria el
       peor tipo de fallo: silencioso. */
    'sufijo'              => (string)($cfg['sufijo'] ?? 'karaoke'),
    'edicion'             => trim(mb_substr((string)($_POST['edicion'] ?? ''), 0, 40)),
    'peticiones'          => isset($_POST['peticiones']),
    'clave_fiesta'        => trim((string)($_POST['clave_fiesta'] ?? '')),
    'limite_por_invitado' => max(1, min(20, (int)($_POST['limite'] ?? 3))),
    'yt_dlp'              => trim((string)($_POST['yt_dlp'] ?? 'yt-dlp')) ?: 'yt-dlp',
    'altura_max'          => in_array((int)($_POST['altura'] ?? 480), [240,360,480,720], true)
                             ? (int)$_POST['altura'] : 480,
    'clave_ajustes'       => trim((string)($_POST['clave_ajustes'] ?? '')),
    /* La wifi de la fiesta. Solo sirve para dibujar el QR que conecta al
       móvil de un invitado: no se usa para nada más y no sale de este PC. */
    /* ---- Termometro de fiesta ------------------------------------
       Se guardan los niveles que traigan texto. Uno vacio no es un
       error: es la forma de quitar un nivel sin tener que editar el
       archivo a mano.

       `textos` —la frase distinta por tema— se conserva tal cual. No se
       edita en este formulario, y perderla al guardar seria el peor
       tipo de fallo: silencioso y en un sitio donde nadie mira. */
    'repetidas'           => in_array($_POST['repetidas'] ?? 'siempre',
                                      ['siempre','avisar','no'], true)
                             ? $_POST['repetidas'] : 'siempre',
    'efectos_escenicos'   => isset($_POST['efectos_escenicos']),
    'termometro'          => isset($_POST['termometro']),
    'termometro_niveles'  => (function () use ($cfg) {
      $viejos = is_array($cfg['termometro_niveles'] ?? null) ? $cfg['termometro_niveles'] : [];
      $out = [];
      foreach (($_POST['tm_texto'] ?? []) as $i => $texto) {
        $texto = trim((string)$texto);
        if ($texto === '') continue;
        /* `estado` es el nombre corto del momento —«En marcha»— y se
           EDITA aquí. Estuvo fuera del formulario y se perdia al primer
           guardado: la pagina escribia solo desde/icono/texto, asi que
           quien tocara cualquier otra cosa de Ajustes se quedaba con
           cinco niveles sin nombre y el termometro mudo.

           La leccion no es «acordarse de copiarlo»: es que **un campo que
           se guarda y no se ve es un campo que se pierde**. Por eso ahora
           tiene su casilla en vez de arrastrarse a escondidas. */
        $n = ['desde'  => max(0, min(999, (int)($_POST['tm_desde'][$i] ?? 0))),
              'icono'  => trim(mb_substr((string)($_POST['tm_icono'][$i] ?? ''), 0, 4)),
              'estado' => trim(mb_substr((string)($_POST['tm_estado'][$i] ?? ''), 0, 22)),
              'texto'  => mb_substr($texto, 0, 60)];
        /* Si lo dejo en blanco, se conserva el que hubiera antes de tocar
           nada. Vaciar el nombre no es una eleccion util: no existe un
           momento sin nombre. */
        if ($n['estado'] === '') $n['estado'] = trim((string)($viejos[$i]['estado'] ?? ''));
        if (!empty($viejos[$i]['textos']) && is_array($viejos[$i]['textos']))
          $n['textos'] = $viejos[$i]['textos'];
        $out[] = $n;
      }
      usort($out, fn($a, $b) => $a['desde'] <=> $b['desde']);
      return $out;
    })(),
    'wifi_ssid'           => trim((string)($_POST['wifi_ssid'] ?? '')),
    'wifi_clave'          => (string)($_POST['wifi_clave'] ?? ''),
    'ambiente_fuente'     => in_array($_POST['ambiente_fuente'] ?? 'youtube',
                                      ['youtube','carpeta','no'], true)
                             ? $_POST['ambiente_fuente'] : 'youtube',
    /* Se guarda LO QUE ESCRIBIÓ, no lo normalizado: si mañana se cambia
       cómo se reconoce una dirección, el texto original sigue ahí. */
    'ambiente_lista'      => trim((string)($_POST['ambiente_lista'] ?? '')),
    'ambiente_carpeta'    => trim((string)($_POST['ambiente_carpeta'] ?? '')),
    'ambiente_auto'       => isset($_POST['ambiente_auto']),
    'tema'                => in_array($_POST['tema'] ?? 'clasico',
                                      ['clasico','fiesta','kids','show'], true)
                             ? $_POST['tema'] : 'clasico',
    'ambiente_volumen'    => max(0, min(100, (int)($_POST['ambiente_volumen'] ?? 35))),
  ];

  $claveInvalida = null;
  foreach ($nuevo['api_keys'] as $c) {
    if (!preg_match('/^AIza[A-Za-z0-9_\-]{30,}$/', $c['key'])) { $claveInvalida = $c; break; }
  }
  if ($claveInvalida) {
    $aviso = 'La clave «' . $claveInvalida['nombre'] . '» no tiene pinta de ser válida: '
           . 'las de Google empiezan por «AIza».';
    $tipo  = 'e';
  } else {
    /* Temporal + rename: escribir encima del archivo bueno significa que
       un corte a mitad deja un JSON truncado, y eso son TODOS los ajustes
       perdidos, la clave de la API incluida. */
    $ok = guardar_json_atomico($archivo, $nuevo);
    if ($ok === false) {
      $aviso = 'No he podido escribir en la carpeta data. Comprueba los permisos.';
      $tipo  = 'e';
    } else {
      $aviso = 'Guardado. Ya puedes volver al karaoke.';
      $cfg   = require __DIR__ . '/api/config.php';
    }
  }
}

/* ---- comprobar todas las claves del formulario contra Google ---------
   Prueba lo que hay ESCRITO en el formulario en ese momento, aunque
   todavía no se haya guardado — igual que hacía con la clave única, y
   por el mismo motivo: para saber si vale la pena guardarla antes de
   guardarla. */
if ($entrado && ($_POST['probar'] ?? '') !== '') {
  $nombres = $_POST['apik_nombre'] ?? [];
  $llaves  = $_POST['apik_key'] ?? [];
  $lineas  = [];
  $huboMal = false;
  foreach ($llaves as $i => $k) {
    $k = trim((string)$k);
    if ($k === '') continue;
    $nombre = trim((string)($nombres[$i] ?? '')) ?: 'Sin nombre';
    $r = traer('https://www.googleapis.com/youtube/v3/search?part=snippet&type=video'
               . '&maxResults=1&q=karaoke&key=' . rawurlencode($k));
    $j = $r ? json_decode($r, true) : null;
    if ($j === null) {
      $lineas[] = "✗ $nombre: no he podido conectar con Google.";
      $huboMal = true;
    } elseif (isset($j['error'])) {
      $motivo = $j['error']['errors'][0]['reason'] ?? '';
      $texto = match ($motivo) {
        'quotaExceeded'       => 'válida, pero la cuota de hoy está agotada.',
        'keyInvalid'          => 'no vale. Revísala en Google Cloud.',
        'accessNotConfigured' => 'falta habilitar «YouTube Data API v3» en ese proyecto.',
        'ipRefererBlocked'    => 'tiene restricciones que bloquean a este servidor.',
        default               => 'Google dice: ' . ($j['error']['message'] ?? 'error'),
      };
      $lineas[] = ($motivo === 'quotaExceeded' ? '⚠ ' : '✗ ') . "$nombre: $texto";
      if ($motivo !== 'quotaExceeded') $huboMal = true;
    } else {
      $lineas[] = "✓ $nombre: correcta.";
    }
  }
  if (!$lineas) {
    $aviso = 'Escribe al menos una clave antes de probarla.'; $tipo = 'e';
  } else {
    $aviso = implode("\n", $lineas);
    $tipo  = $huboMal ? 'e' : 'o';
  }
}

$v = fn(string $k, $d = '') => htmlspecialchars((string)($cfg[$k] ?? $d), ENT_QUOTES, 'UTF-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>⚙️ Ajustes · OpenKaraoke Center</title>
<style>
:root{--bg:#0d0f14;--bg2:#141821;--bg3:#1b2029;--bg4:#252b38;--line:#2b323f;
--txt:#eaedf3;--txt2:#98a1b2;--txt3:#6a7383;--ac:#22d97a}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--txt);font-size:15px;
 font-family:'Segoe UI',system-ui,-apple-system,Roboto,Arial,sans-serif;padding:0 0 60px}
header{background:var(--bg2);border-bottom:1px solid var(--line);padding:20px 0}
.w{max-width:680px;margin:0 auto;padding:0 20px}
h1{margin:0;font-size:20px;display:flex;align-items:center;gap:10px}
h1 small{font-size:12px;color:var(--txt3);font-weight:500}
h2{font-size:12px;letter-spacing:1.6px;text-transform:uppercase;color:var(--ac);
 margin:34px 0 14px;font-weight:800}
.card{background:var(--bg2);border:1px solid var(--line);border-radius:14px;padding:22px}
.f{margin-bottom:18px}
.f:last-child{margin-bottom:0}
label{display:block;font-size:13px;font-weight:700;color:var(--txt2);margin-bottom:7px}
input[type=text],input[type=password],select{width:100%;padding:12px 14px;background:var(--bg3);
 border:1px solid var(--line);border-radius:10px;color:var(--txt);font-size:14.5px;
 font-family:inherit;outline:none}
input:focus,select:focus{border-color:var(--ac)}
.h{font-size:12.5px;color:var(--txt3);margin-top:8px;line-height:1.65}
.h a{color:var(--ac)}
.sw{display:flex;align-items:center;gap:11px;font-size:14px;color:var(--txt2);cursor:pointer}
.sw input{width:18px;height:18px;accent-color:var(--ac)}
/* Flotante al fondo (2026-08-04): la página tiene más de ochocientas
   líneas de ajustes, y Guardar estaba al final del todo — encontrarlo
   significaba bajar del todo primero. `position:sticky` en vez de
   `fixed` porque no debe tapar nada mientras se sube: solo se queda
   pegado abajo cuando el resto del contenido ya ha pasado. El ancho
   completo (`100vw`, roto del `max-width:680px` de `.w`) es para que la
   barra no se vea flotando a la mitad de la pantalla en un monitor
   ancho — se relee a la misma columna con el padding. */
.btns{
  display:flex; gap:10px; flex-wrap:wrap; align-items:center;
  position:sticky; bottom:0; z-index:20;
  margin:26px calc(50% - 50vw) 0; width:100vw;
  padding:16px 20px; padding-left:max(20px, calc(50vw - 340px));
  padding-right:max(20px, calc(50vw - 340px));
  background:var(--bg2); border-top:1px solid var(--line);
  box-shadow:0 -10px 26px rgba(0,0,0,.4);
}
button,.b{padding:13px 24px;border-radius:999px;font-weight:800;font-size:14px;
 border:none;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-block}
.p{background:var(--ac);color:#052b16}
.g{background:var(--bg3);color:var(--txt2);border:1px solid var(--line)}
.msg{padding:14px 17px;border-radius:11px;font-size:14px;margin:18px 0;line-height:1.55;white-space:pre-line}
.msg.e{background:#2c1418;border:1px solid #5c2630;color:#ffc4ca}
.msg.o{background:#0f2e1e;border:1px solid #1c6b41;color:#a9f0c8}
ol{color:var(--txt2);font-size:13.5px;line-height:2;padding-left:22px;margin:0}
ol a{color:var(--ac)}
code{background:var(--bg3);border:1px solid var(--line);border-radius:6px;
 padding:2px 7px;font-size:12.5px}
#indice{position:sticky;top:0;z-index:5;display:flex;flex-wrap:wrap;gap:6px;
 padding:11px 0;margin-bottom:6px;background:var(--bg);
 border-bottom:1px solid var(--line)}
#indice a{padding:7px 14px;border-radius:999px;background:var(--bg2);
 border:1px solid var(--line);color:var(--txt2);text-decoration:none;
 font-size:13px;font-weight:600}
#indice a:hover{border-color:var(--ac);color:var(--ac)}
.gtit{margin:34px 0 0;font-size:12px;letter-spacing:2.2px;text-transform:uppercase;
 color:var(--txt3);border-top:1px solid var(--line);padding-top:20px}
h2{scroll-margin-top:60px}
</style>
</head>
<body>

<header><div class="w"><h1>⚙ Ajustes <small>OpenKaraoke Center v1.1</small></h1></div></header>

<div class="w">

<?php if ($entrado): ?>
<!-- Un índice, y no un menú de pestañas. La página completa se puede leer
     de arriba abajo de una sentada, que es como se configura esto la
     primera vez; el índice sirve para la segunda, cuando ya sabes qué
     vienes a cambiar.

     Aquí ya no hay ninguna sección llamada «Servidor». Nadie piensa «voy
     a configurar el servidor»: piensa «voy a poner la wifi». Los nombres
     son lo que la gente viene a hacer. -->
<nav id="indice">
  <a href="#g-la-fiesta">La fiesta</a>
  <a href="#g-invitados">Invitados</a>
  <a href="#repetidas">Coincidencias</a>
  <a href="#g-musica">Música</a>
  <a href="#g-video">Vídeo</a>
  <a href="#g-avanzado">Avanzado</a>
</nav>
<?php endif; ?>

<?php if ($aviso): ?>
  <div class="msg <?= $tipo ?>"><?= htmlspecialchars($aviso, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (!$entrado): ?>

  <h2>// Contraseña</h2>
  <div class="card">
    <form method="post">
      <div class="f">
        <label>Estos ajustes están protegidos</label>
        <input type="password" name="clave" autofocus placeholder="Contraseña">
      </div>
      <div class="btns"><button class="p" name="entrar" value="1">Entrar</button></div>
    </form>
  </div>

<?php else: ?>

  <form method="post">

    <div class="seccion" id="g-la-fiesta"><h3 class="gtit">La fiesta</h3></div>
    <h2 id="la-fiesta">// Tema — cómo se ve y cómo habla</h2>
    <div class="card">
      <div class="f">
        <label>Cómo se ve y cómo habla</label>
        <select name="tema">
          <?php foreach (['clasico' => 'Clásico — oscuro y sobrio, para adultos',
                          'fiesta'  => 'Fiesta — más color, más contraste',
                          'kids'    => 'Peques — claro, letras grandes, sin QR',
                          'show'    => 'Show — estética de concurso, con cartas de reto'] as $k => $t): ?>
            <option value="<?= $k ?>" <?= ($cfg['tema'] ?? 'clasico') === $k ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
        <div class="h">
          Un tema no es solo el color: <b>cambia las palabras</b>. En Peques la
          aplicación no dice «Fin de actuación», dice «¡Lo has hecho genial!», el
          fondo es claro —un salón a las seis de la tarde no es una sala a oscuras—,
          los botones son grandes y <b>no aparece ningún QR</b>: los niños no
          organizan la cola, eso lo hace un adulto.<br><br>
          Nunca hay puntuaciones ni clasificaciones en ningún tema, y eso
          incluye a Show: lo que trae es un botón que saca en la tele una
          <b>carta de reto</b> —«canta con una mano en el corazón», «que toda la
          sala haga los coros»— para quien va a salir. Las cartas están en
          <code>retos.json</code>, se editan con el Bloc de notas y ninguna
          depende de saber cantar.<br><br>
          <b>Lo que no cambia es cómo se usa</b>: buscar, elegir, añadir, cantar.
          El tema es de la fiesta, no de cada aparato: la tele se pone igual sola.
        </div>
      </div>
    </div>

    <h2 id="edicion">// Rótulo de la cabecera</h2>
    <div class="card">
      <div class="f">
        <label>Marca de esta edición</label>
        <input type="text" name="edicion" value="<?= $v('edicion') ?>" maxlength="40"
               placeholder="Vacío = título normal">
        <div class="h">
          Si escribes algo aquí, sustituye el título de la esquina superior
          izquierda por un rótulo de espray con ese texto — útil para una
          fiesta con nombre propio («Cumple de Nadia», «San Juan 2026»). Es la
          única diferencia entre esta copia y la que se publica: la misma
          aplicación, un ajuste distinto. Vacío deja el título de siempre.
        </div>
      </div>
    </div>

    <div class="seccion" id="g-invitados"><h3 class="gtit">Invitados</h3></div>
    <h2 id="repetidas">// Cuando dos personas eligen lo mismo</h2>
    <div class="card">
      <div class="f">
        <label>Si alguien pide una canción que ya ha elegido otra persona</label>
        <select name="repetidas">
          <?php foreach ([
            'siempre' => 'Permitir siempre — dos personas pueden cantar la misma',
            'avisar'  => 'Permitir, pero decir que ya la había elegido alguien',
            'no'      => 'No permitir: cada canción suena una vez'] as $k => $t): ?>
            <option value="<?= $k ?>" <?= ($cfg['repetidas'] ?? 'siempre') === $k ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
        <div class="h">
          <b>La unidad de un karaoke no es la canción: es la actuación.</b> Si Ana
          canta «Vivir mi vida» y una hora después María también quiere cantarla,
          eso no es una repetición — son dos actuaciones distintas, y es de las
          cosas más normales que pasan en una noche. Bloquearlo es decirle a María
          que llega tarde a algo que no era una carrera.
          <br><br>
          Por eso viene en <b>permitir siempre</b>, y por eso esto no se llama
          «canciones repetidas»: una canción elegida por tres personas no está
          repetida, está <b>compartida</b>. Un karaoke no es una lista de
          reproducción.
          <br><br>
          Lo único que se para pase lo que pase, y no depende de esto: que
          <b>la misma persona</b> pida dos veces lo mismo. Eso casi siempre es un
          doble clic, y se le dice sin que parezca que ha hecho algo mal —
          «esa canción ya está esperándote». Al operador no se le bloquea nunca:
          tiene la cola delante, así que su propia pantalla le pregunta y decide.
        </div>
      </div>
    </div>

    <h2 id="efectos">// Efectos escénicos</h2>
    <div class="card">
      <div class="f">
        <label class="sw">
          <input type="checkbox" name="efectos_escenicos" <?= ($cfg['efectos_escenicos'] ?? true) ? 'checked' : '' ?>>
          Apagón y confeti en la pantalla pública al terminar cada actuación
        </label>
        <div class="h">
          Un instante en negro —como se apagan un poco las luces de un
          escenario— y luego confeti al pasar a los aplausos, en vez del
          corte seco de siempre. Se puede apagar si en tu sala molesta el
          negro momentáneo o si prefieres la pantalla más sobria.
        </div>
      </div>
    </div>

    <h2 id="termometro">// Termómetro de fiesta</h2>
    <div class="card">
      <div class="f">
        <label class="sw">
          <input type="checkbox" name="termometro" <?= ($cfg['termometro'] ?? true) ? 'checked' : '' ?>>
          Enseñar el termómetro en la tele y en el móvil
        </label>
        <div class="h">
          La pantalla del público <b>nunca habla de la aplicación</b>. No dice
          «hay 4 canciones en la cola» —eso es el programa hablando de sí mismo
          delante de treinta personas que no saben que hay un programa— sino
          cómo va la noche: <b>«¡Anímate a pedir la tuya!»</b>.
          <br><br>
          Misma cifra por debajo, otra cosa completamente distinta en la sala.
          La primera te informa de un estado interno; la segunda te dice qué
          puedes hacer, y es la que consigue que alguien saque el móvil.
          <br><br>
          Tampoco hay ningún número a la vista: una barra que sube dice «esto va
          bien» sin que nadie tenga que contar nada desde el fondo del salón.
        </div>
      </div>

      <div class="f">
        <label>Qué se dice en cada momento</label>
        <div class="h" style="margin-bottom:10px">
          <b>Desde</b> es a partir de cuántas canciones esperando se enseña esa
          frase. <b>Momento</b> es el nombre corto que se ve en la etiqueta
          —«Empezando», «En marcha», «A tope»—: tiene que sonar a un rato de
          una fiesta, nunca a un nivel de una lista. Deja el texto en blanco
          para quitar un nivel.
          <br>
          Y una regla que conviene respetar al escribir: <b>no nombres nunca una
          pieza del programa</b> —ni cola, ni lista, ni biblioteca—. Habla de la
          fiesta y de lo que puede hacer la gente.
        </div>
        <?php
          $niv = is_array($cfg['termometro_niveles'] ?? null) ? $cfg['termometro_niveles'] : [];
          while (count($niv) < 5) $niv[] = ['desde' => 0, 'icono' => '', 'texto' => ''];
          foreach ($niv as $i => $n): ?>
          <div style="display:flex;gap:8px;margin-bottom:8px;align-items:center">
            <input type="number" name="tm_desde[<?= $i ?>]" min="0" max="999"
                   value="<?= (int)($n['desde'] ?? 0) ?>"
                   style="width:80px" title="Desde cuántas canciones">
            <input type="text" name="tm_icono[<?= $i ?>]" maxlength="4"
                   value="<?= htmlspecialchars((string)($n['icono'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   style="width:64px;text-align:center;font-size:19px" title="Un emoji">
            <input type="text" name="tm_estado[<?= $i ?>]" maxlength="22"
                   value="<?= htmlspecialchars((string)($n['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   style="width:130px" placeholder="Momento"
                   title="El nombre corto del momento: Empezando, En marcha, A tope…">
            <input type="text" name="tm_texto[<?= $i ?>]" maxlength="60"
                   value="<?= htmlspecialchars((string)($n['texto'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   style="flex:1" placeholder="Qué se dice a partir de ahí">
          </div>
        <?php endforeach; ?>
        <div class="h">
          Si algún día quieres que Peques o Show digan otra cosa, cada nivel
          admite además una frase por tema. No se edita aquí —se pone a mano en
          <code>data/ajustes.json</code>, dentro de <code>textos</code>— y si
          existe, se respeta al guardar desde esta página.
        </div>
      </div>
    </div>

    <h2 id="invitados">// Wifi — para que se conecten</h2>
    <div class="card">
      <div class="f">
        <div class="h" style="margin-bottom:12px">
          Con esto la pantalla del público enseña un <b>QR que conecta el móvil a
          tu wifi</b> con solo apuntar la cámara. Es el paso que más se atasca en
          una fiesta: hasta que alguien no está en tu red, el QR de pedir
          canciones no le sirve de nada, y dictar una contraseña de veinte
          caracteres en voz alta con música puesta no funciona nunca.
          <br><br>
          <b>Estos datos no salen de este ordenador.</b> Se usan solo para
          dibujar el QR, y el QR solo lo ve quien está mirando tu tele.
        </div>
      </div>
      <div class="f">
        <?php $detectado = ssid_actual(); ?>
        <label>Nombre de la red (SSID)</label>
        <input type="text" id="wifiSsid" name="wifi_ssid" value="<?= $v('wifi_ssid') ?>"
               placeholder="<?= $detectado
                 ? htmlspecialchars('Detectada: ' . $detectado, ENT_QUOTES, 'UTF-8')
                 : 'MiWifi_5G' ?>">
        <?php if ($detectado): ?>
          <!-- Volver a detectar es recargar: `netsh` se ejecuta al pintar la
               página, así que no hace falta ningún endpoint nuevo. Y hace
               falta poder hacerlo: en mitad de una fiesta alguien cambia de
               red, o se mueve el equipo de sitio. -->
          <div class="h" style="margin-top:8px">
            Detectada ahora mismo: <b><?= htmlspecialchars($detectado, ENT_QUOTES, 'UTF-8') ?></b>
            &nbsp;·&nbsp;
            <a href="#" onclick="document.getElementById('wifiSsid').value=
               <?= htmlspecialchars(json_encode($detectado), ENT_QUOTES, 'UTF-8') ?>;return false">usar esta</a>
            &nbsp;·&nbsp;
            <a href="?#invitados" onclick="location.reload();return false">volver a detectar</a>
          </div>
        <?php endif; ?>
        <div class="h">
          En Windows se detecta sola si la dejas vacía. Escríbela solo si no
          aparece o si la fiesta va por otra red distinta a la del PC.
        </div>
      </div>
      <div class="f">
        <label>Contraseña de la red</label>
        <input type="text" name="wifi_clave" value="<?= $v('wifi_clave') ?>"
               placeholder="Vacío = no se enseña el QR de wifi">
        <div class="h">
          Esta hay que escribirla a mano <b>siempre</b>. Windows no la entrega
          sin permisos de administrador, y pedirte que arranques el karaoke como
          administrador para dibujar un QR no compensa.
          <br><br>
          Se ve en claro a propósito: la vas a copiar del router y hay que poder
          comprobar que no te has equivocado en un carácter. Si tu red no lleva
          contraseña, déjalo vacío — <b>sin contraseña no se enseña el QR</b>,
          porque un QR que no conecta es peor que ninguno.
        </div>
      </div>
    </div>
    <h2 id="peticiones">// Peticiones — quién puede pedir</h2>
    <div class="card">
      <div class="f">
        <label class="sw">
          <input type="checkbox" name="peticiones" <?= $cfg['peticiones'] ? 'checked' : '' ?>>
          Dejar que los invitados pidan canciones con el QR
        </label>
        <div class="h">Escanean el QR, buscan y su canción entra en la cola sola.</div>
      </div>
      <div class="f">
        <label>Contraseña de la fiesta</label>
        <input type="text" name="clave_fiesta" value="<?= $v('clave_fiesta') ?>" placeholder="Vacío = cualquiera puede pedir">
        <div class="h">Útil si la wifi la comparte más gente de la que has invitado.</div>
      </div>
      <div class="f">
        <label>Canciones seguidas por persona</label>
        <input type="text" name="limite" value="<?= $v('limite_por_invitado', 3) ?>">
        <div class="h">Para que nadie monopolice la noche. Por defecto 3.</div>
      </div>
    </div>

    <div class="seccion" id="g-musica"><h3 class="gtit">Música</h3></div>
    <h2 id="musica">// Buscar en YouTube</h2>
    <div class="card">
      <div class="f">
        <label>Claves de la YouTube Data API v3</label>
        <div class="h" style="margin-bottom:12px">
          Se quedan en tu servidor: el navegador no las ve nunca. Con más de
          una, si la de arriba se queda sin cuota (unas 100 búsquedas al
          día), la búsqueda pasa sola a la siguiente que esté activa — nadie
          tiene que enterarse a media fiesta. El orden de las filas es la
          prioridad: se empieza siempre por la primera.
          <b>Sin ninguna clave la aplicación sigue valiendo</b> — pegas el
          enlace de YouTube en el buscador y la canción entra igual, con su
          título y su carátula. Las claves solo hacen falta para buscar
          escribiendo el nombre.
        </div>
        <?php
          $claves = claves_lista($cfg);
          while (count($claves) < 5) $claves[] = ['nombre' => '', 'key' => '', 'activa' => true, 'propietario' => ''];
          /* ~99 búsquedas al día por clave es el tope real (10.000 unidades,
             101 por búsqueda contando la de duraciones) — ver cache.php.
             La barra se llena contra ESE número, no contra un 100 inventado. */
          $topeDiario = (int)floor(10000 / 101);
        ?>
        <div id="filasClaves">
        <?php foreach ($claves as $i => $c):
          $usos = !empty($c['key']) ? claves_usos_hoy($c['key']) : 0;
          $pct  = max(0, min(100, (int)round($usos / $topeDiario * 100)));
        ?>
          <div class="filaClave" style="display:flex;gap:8px;margin-bottom:8px;align-items:center">
            <span style="width:16px;color:var(--txt3);font-size:12px;text-align:right" title="Prioridad"><?= $i + 1 ?></span>
            <div style="display:flex;flex-direction:column;gap:2px">
              <button type="button" class="mover-arriba" title="Subir prioridad"
                      style="background:none;border:1px solid var(--line);border-radius:5px;color:var(--txt2);
                             width:20px;height:16px;line-height:1;cursor:pointer;font-size:10px" <?= $i === 0 ? 'disabled' : '' ?>>▲</button>
              <button type="button" class="mover-abajo" title="Bajar prioridad"
                      style="background:none;border:1px solid var(--line);border-radius:5px;color:var(--txt2);
                             width:20px;height:16px;line-height:1;cursor:pointer;font-size:10px" <?= $i === count($claves) - 1 ? 'disabled' : '' ?>>▼</button>
            </div>
            <input type="text" name="apik_nombre[<?= $i ?>]" maxlength="30"
                   value="<?= htmlspecialchars((string)($c['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   style="width:100px" placeholder="Nombre" title="Un nombre para reconocerla, p.ej. Personal">
            <input type="text" name="apik_propietario[<?= $i ?>]" maxlength="30"
                   value="<?= htmlspecialchars((string)($c['propietario'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   style="width:100px" placeholder="De quién es" title="Quién es el dueño de esta clave, p.ej. el bar o un amigo">
            <input type="text" name="apik_key[<?= $i ?>]"
                   value="<?= htmlspecialchars((string)($c['key'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   style="flex:1;min-width:120px" placeholder="AIza…" autocomplete="off" spellcheck="false">
            <label class="sw" style="margin:0;white-space:nowrap">
              <input type="checkbox" name="apik_activa[<?= $i ?>]" <?= ($c['activa'] ?? true) ? 'checked' : '' ?>>
              activa
            </label>
            <div style="width:70px" title="<?= $usos ?> de ~<?= $topeDiario ?> búsquedas de hoy">
              <div style="height:6px;border-radius:999px;background:var(--bg4);overflow:hidden;position:relative">
                <div style="height:100%;width:<?= $pct ?>%;border-radius:999px;
                     background:<?= $pct >= 100 ? 'var(--dang, #e2584f)' : ($pct >= 75 ? '#e0b23c' : 'var(--ac, #22d97a)') ?>"></div>
              </div>
              <div style="font-size:10px;color:var(--txt3);text-align:center;margin-top:2px"><?= $pct ?>%</div>
            </div>
            <button type="button" class="borrar-clave" title="Borrar esta clave"
                    style="background:none;border:1px solid var(--line);border-radius:6px;color:var(--txt3);
                           width:26px;height:26px;cursor:pointer;flex-shrink:0">✕</button>
          </div>
        <?php endforeach; ?>
        </div>
        <div class="h">
          Deja el nombre y la clave en blanco para dejar la fila sin usar. La
          barra marca el gasto de hoy contra el tope diario real (~<?= $topeDiario ?>
          búsquedas por clave); en verde va bien, en ámbar se acerca, en rojo se
          ha agotado. <b>▲▼</b> cambia la prioridad — la de más arriba es
          siempre la que se prueba primero. <b>✕</b> vacía la fila.
        </div>
        <script>
        (function(){
          const caja = document.getElementById('filasClaves');
          if (!caja) return;
          const campos = ['apik_nombre', 'apik_propietario', 'apik_key'];
          function valores(fila){
            const v = {};
            campos.forEach(c => { const e = fila.querySelector('[name^="' + c + '"]'); v[c] = e ? e.value : ''; });
            const act = fila.querySelector('[name^="apik_activa"]');
            v.activa = act ? act.checked : false;
            return v;
          }
          function poner(fila, v){
            campos.forEach(c => { const e = fila.querySelector('[name^="' + c + '"]'); if (e) e.value = v[c]; });
            const act = fila.querySelector('[name^="apik_activa"]');
            if (act) act.checked = v.activa;
          }
          function intercambiar(filaA, filaB){
            const a = valores(filaA), b = valores(filaB);
            poner(filaA, b); poner(filaB, a);
          }
          caja.addEventListener('click', ev => {
            const fila = ev.target.closest('.filaClave');
            if (!fila) return;
            if (ev.target.classList.contains('mover-arriba')){
              const ant = fila.previousElementSibling;
              if (ant) intercambiar(fila, ant);
            } else if (ev.target.classList.contains('mover-abajo')){
              const sig = fila.nextElementSibling;
              if (sig) intercambiar(fila, sig);
            } else if (ev.target.classList.contains('borrar-clave')){
              poner(fila, { apik_nombre:'', apik_propietario:'', apik_key:'', activa:true });
            }
          });
        })();
        </script>
      </div>

      <details>
        <summary style="cursor:pointer;color:var(--txt2);font-size:13.5px;font-weight:700">
          Cómo conseguir una, gratis y en cinco minutos
        </summary>
        <ol style="margin-top:14px">
          <li>Entra en <a href="https://console.cloud.google.com" target="_blank" rel="noopener">console.cloud.google.com</a> con tu cuenta de Google.</li>
          <li>Crea un proyecto nuevo. El nombre da igual.</li>
          <li>Ve a <em>API y servicios → Biblioteca</em>, busca <b>YouTube Data API v3</b> y pulsa Habilitar.</li>
          <li>Ve a <em>Credenciales → Crear credenciales → Clave de API</em>.</li>
          <li>Cópiala y pégala aquí arriba.</li>
          <li>Recomendado: en <em>Restringir clave</em>, deja marcada solo la YouTube Data API v3.</li>
        </ol>
        <div class="h">La cuota gratuita da unas 100 búsquedas al día, de sobra para una fiesta.</div>
      </details>
      <div class="h" style="margin-top:16px">
        <b>Sin clave tambien se puede.</b> Pegando en el buscador el enlace de un
        video de YouTube se anade igual: eso no pasa por la API, no gasta cuota y
        funciona desde el primer minuto. La clave solo hace falta para buscar
        escribiendo el nombre de una cancion.
      </div>

      <div class="h" style="margin-top:20px">
        <b>El filtro de busqueda ya no se pone aqui.</b> Esta en la barra de
        arriba del operador, debajo del buscador, que es donde hace falta:
        se cambia en mitad de una fiesta, no una vez al instalar. Alli
        ademas admite <code>AND</code>, <code>OR</code> y <code>-</code>
        para excluir, y trae perfiles hechos.
      </div>
    </div>
    <h2 id="ambiente">// Música ambiente — entre actuaciones</h2>
    <div class="card">
      <div class="f">
        <label>Fuente</label>
        <select name="ambiente_fuente">
          <?php foreach (['no'      => 'Apagada — sin música entre canciones',
                          'youtube' => 'Lista de reproducción de YouTube',
                          'carpeta' => 'Carpeta del ordenador (mp3, m4a…)'] as $k => $t): ?>
            <option value="<?= $k ?>" <?= ($cfg['ambiente_fuente'] ?? 'youtube') === $k ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
        <div class="h">
          <b>Viene apagada.</b> Que el programa empiece a sonar solo la primera vez
          que se abre no es agradable para nadie. Cuando la enciendas, se configura
          <b>una vez</b> y no se vuelve a tocar en toda la noche: la música baja sola
          cuando preparas una actuación y vuelve cuando termina. Y desde el operador
          hay un botón para callarla en cualquier momento sin venir aquí.
          <br><br>
          <b>Esto no es la Cabina DJ.</b> La Cabina DJ es un espacio —otra fiesta,
          una en la que nadie canta y la lista la hacéis entre todos desde el
          móvil—. Lo de aquí es el hilo de fondo del karaoke: suena en los huecos,
          se aparta cuando alguien va a cantar y vuelve al terminar. Durante un
          tiempo las dos cosas se llamaron igual y confundía a todo el mundo,
          empezando por mí.
        </div>
      </div>

      <div class="f">
        <label>Lista de YouTube</label>
        <input type="text" name="ambiente_lista" value="<?= $v('ambiente_lista') ?>"
               placeholder="https://www.youtube.com/playlist?list=…">
        <div class="h">
          Pega lo que tengas: la dirección de una lista, la de un canal, o solo el
          identificador. Se reconoce solo.
          <?php $idAmb = lista_ambiente((string)($cfg['ambiente_lista'] ?? '')); ?>
          <?php if ($idAmb): ?>
            <br><b style="color:var(--ac)">Reconocido:</b> <code><?= htmlspecialchars($idAmb, ENT_QUOTES, 'UTF-8') ?></code>
            — <a href="https://www.youtube.com/playlist?list=<?= rawurlencode($idAmb) ?>" target="_blank" rel="noopener">abrir para comprobarla</a>
          <?php elseif (trim((string)($cfg['ambiente_lista'] ?? '')) !== ''): ?>
            <br><b style="color:var(--dang)">No lo reconozco.</b> Un <code>@usuario</code> no vale:
            abre el canal en YouTube y copia la dirección que empieza por <code>/channel/UC…</code>
          <?php endif; ?>
          <br><br>
          <b>Por defecto viene la de <a href="https://www.youtube.com/channel/UCAj9nn-gOcKuD4ropg44HCw" target="_blank" rel="noopener">DJ&nbsp;Noize</a></b>,
          que publica mezclas de trap y hip hop todas las semanas. Está puesto el
          <b>canal</b> y no una lista concreta a propósito: así se actualiza sola.
          Una lista fija se queda vieja y a los tres meses suena siempre lo mismo.
        </div>
      </div>

      <div class="f">
        <label>Carpeta con música propia</label>
        <input type="text" name="ambiente_carpeta" value="<?= $v('ambiente_carpeta') ?>"
               placeholder="C:\Users\tu\Music\Fiesta">
        <div class="h">
          Para cuando no hay internet, o para poner lo tuyo. Se reproducen en orden
          aleatorio los <code>.mp3</code>, <code>.m4a</code>, <code>.ogg</code> y
          <code>.wav</code> que haya dentro.
        </div>
      </div>

      <div class="f">
        <label class="sw">
          <input type="checkbox" name="ambiente_auto" <?= !empty($cfg['ambiente_auto']) ? 'checked' : '' ?>>
          Que vuelva sola después de cada canción
        </label>
        <div class="h">
          <b>Apagado</b> por defecto, y es el comportamiento que recomiendo: el silencio
          es lo normal y la música la enciendes tú con el botón <b>🎵</b> del operador.
          Si lo activas, la música se calla al empezar cada actuación y vuelve al
          terminar, sin que tengas que tocar nada. Si lo dejas apagado, una vez que se
          calla se queda callada hasta que vuelvas a pulsar el botón.
        </div>
      </div>

      <div class="f">
        <label>Volumen del ambiente</label>
        <select name="ambiente_volumen">
          <?php foreach ([20 => '20 % — apenas se oye, para conversar',
                          35 => '35 % — recomendado',
                          50 => '50 % — se nota',
                          70 => '70 % — alto'] as $k => $t): ?>
            <option value="<?= $k ?>" <?= (int)($cfg['ambiente_volumen'] ?? 35) === $k ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
        <div class="h">Siempre por debajo de la voz: es fondo, no es el espectáculo.</div>
      </div>
    </div>

    <div class="seccion" id="g-video"><h3 class="gtit">Vídeo y descargas</h3></div>
    <h2 id="video">// Descargas — cantar sin internet</h2>
    <div class="card">
      <div class="f">
        <label>Ruta a yt-dlp</label>
        <input type="text" name="yt_dlp" value="<?= $v('yt_dlp', 'yt-dlp') ?>" placeholder="yt-dlp">
        <div class="h">
          Permite guardar los vídeos en el disco y cantar sin internet.
          Baja <code>yt-dlp.exe</code> de
          <a href="https://github.com/yt-dlp/yt-dlp/releases" target="_blank" rel="noopener">sus releases</a>,
          ponlo junto a <code>Karaoke.bat</code> y escribe aquí su nombre o su ruta completa.
          Hace falta también <code>ffmpeg</code>.
        </div>
      </div>
      <div class="f">
        <label>Calidad de descarga</label>
        <select name="altura">
          <?php foreach ([240=>'240p — mínimo', 360=>'360p — ligera',
                          480=>'480p — recomendada', 720=>'720p — pesa bastante'] as $h => $t): ?>
            <option value="<?= $h ?>" <?= (int)$cfg['altura_max'] === $h ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="seccion" id="g-avanzado"><h3 class="gtit">Avanzado</h3></div>
    <h2 id="avanzado">// Esta página</h2>
    <div class="card">
      <div class="f">
        <label>Contraseña para entrar aquí</label>
        <input type="text" name="clave_ajustes" value="<?= $v('clave_ajustes') ?>" placeholder="Vacío = sin contraseña">
        <div class="h">
          Ponla si el PC va a estar accesible desde la wifi de la fiesta: sin
          ella, cualquiera que llegue a esta dirección puede ver tu clave.
        </div>
      </div>
    </div>

    <?php
      /* El bloque de desarrollo solo existe si existe la suite. En una
         copia distribuida no hay `pruebas/`, así que este apartado no
         aparece y nadie tiene que preguntarse qué es. */
      $haySuite = is_file(__DIR__ . '/pruebas/pruebas.php');
      $hayDocs  = is_dir(__DIR__ . '/docs');
    ?>
    <?php if ($haySuite || $hayDocs): ?>
      <h2 id="desarrollo">// Desarrollo</h2>
      <div class="card">
        <div class="f">
          <label>Herramientas de quien toca el código</label>
          <div class="h">
            Esto no aparece en una copia distribuida: solo se ve si la carpeta
            <code>pruebas/</code> está presente. La suite trabaja sobre
            <code>data/pruebas.json</code>, así que <b>no toca tu biblioteca ni tu
            cola</b>, y solo se abre desde este mismo ordenador.
          </div>
          <div class="btns" style="margin-top:16px">
            <a class="b g" href="qa.php" target="_blank" rel="noopener">🩺 Comprobar esta máquina</a>
            <?php if ($haySuite): ?>
              <a class="b p" href="pruebas/pruebas.php" target="_blank" rel="noopener">🧪 Ejecutar las pruebas</a>
            <?php endif; ?>
            <?php if ($hayDocs): ?>
              <a class="b g" href="docs/DECISIONES.md" target="_blank" rel="noopener">📄 Decisiones tomadas</a>
              <a class="b g" href="docs/IDEAS.md" target="_blank" rel="noopener">💡 Ideas aparcadas</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="btns">
      <button class="p" name="guardar" value="1">Guardar</button>
      <button class="g" name="probar" value="1">Probar las claves</button>
      <a class="b g" href="index.html">← Volver al karaoke</a>
    </div>
  </form>

<?php endif; ?>

</div>
</body>
</html>
