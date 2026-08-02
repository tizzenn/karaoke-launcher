<?php
declare(strict_types=1);
require __DIR__ . '/api/comun.php';

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
    'api_key'             => trim((string)($_POST['api_key'] ?? '')),
    'sufijo'              => trim((string)($_POST['sufijo'] ?? '')),
    'peticiones'          => isset($_POST['peticiones']),
    'clave_fiesta'        => trim((string)($_POST['clave_fiesta'] ?? '')),
    'limite_por_invitado' => max(1, min(20, (int)($_POST['limite'] ?? 3))),
    'yt_dlp'              => trim((string)($_POST['yt_dlp'] ?? 'yt-dlp')) ?: 'yt-dlp',
    'altura_max'          => in_array((int)($_POST['altura'] ?? 480), [240,360,480,720], true)
                             ? (int)$_POST['altura'] : 480,
    'clave_ajustes'       => trim((string)($_POST['clave_ajustes'] ?? '')),
  ];

  if ($nuevo['api_key'] !== '' && !preg_match('/^AIza[A-Za-z0-9_\-]{30,}$/', $nuevo['api_key'])) {
    $aviso = 'Esa clave no tiene pinta de ser válida: las de Google empiezan por «AIza».';
    $tipo  = 'e';
  } else {
    if (!is_dir(DIR_DATOS)) @mkdir(DIR_DATOS, 0775, true);
    $ok = @file_put_contents($archivo,
            json_encode($nuevo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    if ($ok === false) {
      $aviso = 'No he podido escribir en la carpeta data. Comprueba los permisos.';
      $tipo  = 'e';
    } else {
      $aviso = 'Guardado. Ya puedes volver al karaoke.';
      $cfg   = require __DIR__ . '/api/config.php';
    }
  }
}

/* ---- comprobar la clave contra Google ------------------------------- */
if ($entrado && ($_POST['probar'] ?? '') !== '') {
  $k = trim((string)($_POST['api_key'] ?? ''));
  if ($k === '') {
    $aviso = 'Escribe una clave antes de probarla.'; $tipo = 'e';
  } else {
    $r = traer('https://www.googleapis.com/youtube/v3/search?part=snippet&type=video'
               . '&maxResults=1&q=karaoke&key=' . rawurlencode($k));
    $j = $r ? json_decode($r, true) : null;
    if ($j === null) {
      $aviso = 'No he podido conectar con Google. ¿Tienes internet?'; $tipo = 'e';
    } elseif (isset($j['error'])) {
      $motivo = $j['error']['errors'][0]['reason'] ?? '';
      $aviso = match ($motivo) {
        'quotaExceeded'       => 'La clave es válida, pero la cuota de hoy está agotada.',
        'keyInvalid'          => 'Esa clave no vale. Revísala en Google Cloud.',
        'accessNotConfigured' => 'Falta habilitar «YouTube Data API v3» en ese proyecto de Google.',
        'ipRefererBlocked'    => 'La clave tiene restricciones que bloquean a este servidor.',
        default               => 'Google dice: ' . ($j['error']['message'] ?? 'error'),
      };
      $tipo = $motivo === 'quotaExceeded' ? 'o' : 'e';
    } else {
      $aviso = '✓ La clave funciona.'; $tipo = 'o';
    }
  }
}

$v = fn(string $k, $d = '') => htmlspecialchars((string)($cfg[$k] ?? $d), ENT_QUOTES, 'UTF-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>⚙️ Ajustes · Karaoke Launcher</title>
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
.btns{display:flex;gap:10px;flex-wrap:wrap;margin-top:26px}
button,.b{padding:13px 24px;border-radius:999px;font-weight:800;font-size:14px;
 border:none;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-block}
.p{background:var(--ac);color:#052b16}
.g{background:var(--bg3);color:var(--txt2);border:1px solid var(--line)}
.msg{padding:14px 17px;border-radius:11px;font-size:14px;margin:18px 0;line-height:1.55}
.msg.e{background:#2c1418;border:1px solid #5c2630;color:#ffc4ca}
.msg.o{background:#0f2e1e;border:1px solid #1c6b41;color:#a9f0c8}
ol{color:var(--txt2);font-size:13.5px;line-height:2;padding-left:22px;margin:0}
ol a{color:var(--ac)}
code{background:var(--bg3);border:1px solid var(--line);border-radius:6px;
 padding:2px 7px;font-size:12.5px}
</style>
</head>
<body>

<header><div class="w"><h1>⚙ Ajustes <small>Karaoke Launcher v1.0</small></h1></div></header>

<div class="w">

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

    <h2>// Clave de YouTube</h2>
    <div class="card">
      <div class="f">
        <label>Clave de la YouTube Data API v3</label>
        <input type="text" name="api_key" value="<?= $v('api_key') ?>" placeholder="AIza…" autocomplete="off" spellcheck="false">
        <div class="h">
          Se queda en tu servidor: el navegador no la ve nunca, así que
          nadie que abra la página puede robártela.
          <b>Sin clave la aplicación sigue valiendo</b> — pegas el enlace de
          YouTube en el buscador y la canción entra igual, con su título y su
          carátula. La clave solo hace falta para buscar por nombre.
        </div>
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

      <div class="f" style="margin-top:20px">
        <label>Texto que se añade a cada búsqueda</label>
        <input type="text" name="sufijo" value="<?= $v('sufijo') ?>" placeholder="karaoke">
        <div class="h">Si buscas «cicatrices», pedirá «cicatrices karaoke». Déjalo vacío para buscar tal cual.</div>
      </div>
    </div>

    <h2>// Peticiones desde el móvil</h2>
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

    <h2>// Descargas</h2>
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

    <h2>// Esta página</h2>
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

    <div class="btns">
      <button class="p" name="guardar" value="1">Guardar</button>
      <button class="g" name="probar" value="1">Probar la clave</button>
      <a class="b g" href="index.html">← Volver al karaoke</a>
    </div>
  </form>

<?php endif; ?>

</div>
</body>
</html>
