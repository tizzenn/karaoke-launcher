<?php
/* ═══════════════════════════════════════════════════════════════════
   proyector.php — la pantalla del público

   Se abre en una segunda ventana y se arrastra a la tele. Es la segunda
   de las dos superficies físicas del sistema; la otra es el monitor del
   PC, que se reparten el operador y el cantante.

   Esta pantalla NO manda: lee `evento.estado` de data/estado.json y
   obedece. Si mandaran las dos, se pelearían por decidir la siguiente
   canción. Como no escribe nada, tampoco abre ninguna puerta nueva a
   los invitados.

   Cinco escenas, y la que toca la decide el estado del evento:

     CALENTAMIENTO   el rato de antes: cómo pedir, la cola llenándose
     ESPERA          nadie cantando
     PREPARADA       el operador está montando la siguiente
     LLAMADA         cuenta atrás: «ahora canta…», 5, 4, 3…
     INTERPRETACION  el vídeo a pantalla completa
     FIN_ACTUACION   aplausos y quién va ahora

   Los nombres salen en UN solo sitio: la cuenta atrás de la llamada, y
   solo si la canción la pidió alguien desde el móvil —o sea, si el
   nombre ya existe y no hay que escribir nada de más—. Es el único
   momento en que saber quién sale cambia algo. En las listas, en la
   franja del vídeo y en los aplausos no aparece ninguno: ahí el título
   basta, y una pantalla proyectada llena de nombres escritos por gente
   bebida no mejora nada.

   El sonido sale de aquí, porque es lo que está enchufado a la tele. El
   portátil se queda mudo, pero sigue reproduciendo: es quien detecta el
   final de la canción y encadena la siguiente.
   ═══════════════════════════════════════════════════════════════════ */

require __DIR__ . '/api/comun.php';
$cfg = cfg();

/* La dirección del QR sale de la red del servidor, NUNCA de la URL con
   la que se abrió esta ventana: el proyector se abre desde el operador,
   que está en `localhost`, y el QR resultante solo servía para el propio
   PC. Lo que hace falta es la IP con la que se ve este ordenador desde
   los móviles. */
$ip     = ip_local();
$puerto = (int)($_SERVER['SERVER_PORT'] ?? 8123);
$urlPedir = $ip ? 'http://' . $ip . ($puerto == 80 ? '' : ':' . $puerto) . '/pedir.php' : '';
$hayPeticiones = (bool)$cfg['peticiones'];

/* Sin dirección de red no hay nada que ofrecer a los móviles, y decirlo
   con un icono de wifi tachada es más honesto que enseñar un QR muerto. */
$esLocal = ($ip === null);

/* ---- La wifi para el QR ---------------------------------------------
   El nombre de la red se puede sacar de Windows sin permisos especiales.
   La contraseña NO: `netsh wlan show profile key=clear` exige
   administrador, y pedirle a alguien que arranque el karaoke como
   administrador para dibujar un QR no compensa. Por eso la contraseña se
   escribe una vez en los ajustes.

   Sin contraseña no se enseña el QR de wifi: un QR que no conecta
   confunde más que no poner ninguno. */
$ssid  = trim((string)($cfg['wifi_ssid'] ?? '')) ?: ssid_actual();
$clave = (string)($cfg['wifi_clave'] ?? '');
$calentamientoMin = (int)($cfg['calentamiento_min'] ?? 20);
$limite = (int)$cfg['limite_por_invitado'];
$conClave = (string)$cfg['clave_fiesta'] !== '';
?><!DOCTYPE html>
<html lang="es" data-modo="karaoke">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#000">
<!-- Su propio manifiesto: instalada en el mini PC de la tele aparece
     como una aplicacion aparte, con su icono y en apaisado. Comparte
     servidor y codigo con las otras dos. -->
<link rel="manifest" href="manifest-tele.webmanifest">
<link rel="apple-touch-icon" href="iconos/icono-tele-192.png">
<title>📺 Pantalla del público · OpenKaraoke Center</title>
<!-- La paleta y los tres modos salen de base.css, igual que en el resto
     del proyecto. proyector.css solo cambia lo que de verdad es distinto
     en una tele: el fondo negro de verdad. -->
<link rel="stylesheet" href="css/base.css">
<link rel="stylesheet" href="css/temas.css">
<link rel="stylesheet" href="css/proyector.css">
</head>
<body data-lado="der" data-escena="ESPERA">

<script src="js/idioma.js"></script>
<script src="js/simbolos.js"></script>

<div id="escena">
  <div id="yt"></div>
  <video id="vlocal" class="oculto" playsinline></video>
</div>

<div id="halo" class="no"></div>
<div id="barras"></div>

<!-- Lenguaje visual escénico (2026-08-04): un instante antes de que
     aparezca el vídeo, no de la lista al vídeo de golpe. Solo en
     Karaoke —en Cabina DJ no hay a quién presentar, ver escenas.js—, y
     no retrasa nada del control del operador: es la pantalla PÚBLICA
     la que se toma un momento, no la máquina de estados. -->
<div id="presentando" class="oculto">
  <div class="ppTxt" data-i18n="presentando_txt">Preparando escenario…</div>
  <div class="ppQuien" id="ppQuien"></div>
  <div class="ppTitulo" id="ppTitulo"></div>
</div>

<!-- Un destello muy corto cada vez que entra una petición nueva desde
     el móvil — "un pequeño acontecimiento", no una fiesta de confeti
     cada vez, eso queda para los aplausos. Ver escenas.js. -->
<div id="destello" aria-hidden="true"></div>

<!-- El "blackout" teatral: tapa el instante en que el vídeo se corta en
     seco al terminar una actuación, como se apagan un poco las luces de
     un escenario antes del siguiente número. Detrás ya está montada la
     escena de aplausos con su confeti; el blackout solo la revela con
     un fundido en vez de un chasquido. Ver js/proyector/escenas.js. -->
<div id="blackout" aria-hidden="true"></div>

<!-- ═══ CALENTAMIENTO ═══ -->
<div class="escena oculto" id="calent">
  <div class="marca"><svg class="ic"><use href="#ic-microfono"></use></svg> <span id="marcaTxt" data-i18n="marca_karaoke">Karaoke</span></div>
  <div class="reloj" id="reloj"></div>

  <!-- 1 · Cómo pedir, en tres pasos -->
  <div class="panel on" data-k="pedir">
    <h1 data-i18n-html="pedir_h1">Pide tu canción <span class="ac">desde el móvil</span></h1>
    <div class="pasos">
      <div class="paso">
        <span class="num">1</span>
        <div class="pic" id="picWifi"><svg class="ic"><use href="#ic-wifi"></use></svg></div>
        <div class="tit" id="tWifi" data-i18n="paso1_tit">Conéctate al wifi</div>
        <div class="txt" id="dWifi" data-i18n="paso1_txt">Apunta con la cámara y acepta la red</div>
      </div>
      <div class="flecha">›</div>
      <div class="paso">
        <span class="num">2</span>
        <div class="pic" id="picPedir"><svg class="ic"><use href="#ic-qr"></use></svg></div>
        <div class="tit" data-i18n="paso2_tit">Escanea esto</div>
        <div class="txt" id="dPedir" data-i18n="paso2_txt">Se abre solo. No hay que instalar nada</div>
      </div>
      <div class="flecha">›</div>
      <div class="paso">
        <span class="num">3</span>
        <div class="pic"><svg class="ic"><use href="#ic-buscar"></use></svg></div>
        <div class="tit" data-i18n="paso3_tit">Busca y envía</div>
        <div class="txt" id="dEnviar" data-i18n="paso3_txt">Tu canción entra en la cola</div>
      </div>
    </div>
  </div>

  <!-- 2 · La cola, llenándose en directo -->
  <div class="panel" data-k="cola">
    <h1 data-i18n-html="cola_h1">La cola <span class="ac">ahora mismo</span></h1>
    <div class="sub" id="colaSub"></div>
    <div class="cola" id="colaLista"></div>
  </div>

  <!-- 3 · Cómo funciona esto por dentro -->
  <div class="panel" data-k="funciona">
    <h1 data-i18n-html="funciona_h1">Cómo <span class="ac">funciona</span></h1>
    <div class="circuito">
      <div class="nodo"><svg class="ic"><use href="#ic-movil"></use></svg>
        <span class="q" data-i18n="nodo1_q">Tú pides</span><span class="d" data-i18n="nodo1_d">desde tu móvil</span></div>
      <div class="flecha">›</div>
      <div class="nodo vivo"><svg class="ic"><use href="#ic-musica"></use></svg>
        <span class="q" data-i18n="nodo2_q">A la cola</span><span class="d" id="nodoCola" data-i18n="nodo2_d">esperando turno</span></div>
      <div class="flecha">›</div>
      <div class="nodo"><svg class="ic"><use href="#ic-tv"></use></svg>
        <span class="q" data-i18n="nodo3_q">A la pantalla</span><span class="d" data-i18n="nodo3_d">con la letra</span></div>
      <div class="flecha">›</div>
      <div class="nodo"><svg class="ic"><use href="#ic-microfono"></use></svg>
        <span class="q" data-i18n="nodo4_q">Cantas</span><span class="d" data-i18n="nodo4_d">y aplaudimos</span></div>
    </div>
    <div class="consejo" data-i18n="funciona_consejo">Nadie tiene que buscar nada en YouTube ni pasarle el móvil a nadie.
    Se encadenan solas, una detrás de otra.</div>
  </div>

  <!-- 4 · Datos y consejos -->
  <div class="panel" data-k="datos">
    <h1 data-i18n-html="datos_h1">Antes de <span class="ac">empezar</span></h1>
    <div class="datos">
      <div class="dato"><div class="g" id="dCola">0</div><div class="p" data-i18n="dato_cola">en la cola</div></div>
      <div class="dato"><div class="g" id="dBib">0</div><div class="p" data-i18n="dato_bib">en la biblioteca</div></div>
      <div class="dato"><div class="g" id="dMin">—</div><div class="p" data-i18n="dato_min">de música ya pedida</div></div>
    </div>
    <div class="consejo" id="consejo"></div>
  </div>

  <div class="puntos" id="puntos"></div>
</div>

<!-- ═══ ESPERA / PREPARADA ═══ -->
<div class="escena oculto" id="espera">
  <h1 id="esperaTit">🎤 <span data-i18n="marca_karaoke">Karaoke</span></h1>
  <div class="sub" id="esperaSub" data-i18n="espera_sub">Elige una canción en el ordenador y empieza la fiesta.</div>
  <div class="cola" id="esperaLista" style="width:64vw;max-height:38vh"></div>
  <!-- El termómetro. No dice cuántas canciones hay: dice cómo va la
       fiesta. Quien lo lee no sabe que hay una aplicación detrás y no
       tiene por qué enterarse. -->
  <div id="termometro" class="oculto"></div>
</div>

<!-- ═══ CARTA DE RETO (Modo Show) ═══
     No es una escena: es una tira encima de lo que haya. La saca el
     operador para quien va a salir y se queda hasta que la quita, porque
     el reto tiene que poder leerse mientras se canta. -->
<div id="carta" class="oculto">
  <div class="et" data-i18n="carta_reto_et">Reto</div>
  <div class="txt" id="cartaTxt"></div>
</div>

<!-- ═══ LLAMADA ═══ -->
<div class="escena oculto" id="llamada">
  <div class="et" data-i18n="llamada_et">Ahora canta</div>
  <div class="quien" id="llamQuien"></div>
  <div class="tit" id="llamTit"></div>
  <div class="num" id="llamNum">5</div>
</div>

<!-- ═══ FIN DE ACTUACIÓN ═══ -->
<div class="escena oculto" id="aplausos">
  <!-- Piezas de confeti reales, generadas en JS cada vez que se ENTRA en
       esta escena (js/proyector/escenas.js, lanzarConfeti()). Vacío en
       reposo: nada que limpiar si la fiesta lleva horas encendida. -->
  <div id="confeti" aria-hidden="true"></div>
  <h1>👏 <span class="ac" data-i18n="aplausos_bien">¡Bien!</span></h1>
  <div class="sub" id="finQue"></div>
  <div class="sub" style="font-size:2.6vw;color:var(--txt)" id="finSig"></div>
</div>

<!-- ═══ Encima del vídeo ═══ -->
<div id="franja" class="fuera oculto">
  <div class="et" data-i18n="franja_et">Suena ahora</div>
  <div class="canta" id="quien">—</div>
  <div class="tema" id="tema"></div>
</div>

<div id="proximas" class="oculto"></div>
<div id="esquina"></div>

<div id="arranque">
  <!-- Lo primero que se lee, en grande y antes de tocar nada. Con tres
       ventanas abiertas —el operador, esta y los ajustes— la única
       pregunta de quien lo monta por primera vez es cuál va a la tele.
       Contestarla aquí ahorra la llamada de teléfono. -->
  <div class="quien">
    <div class="et" data-i18n="arranque_et">Esta ventana es</div>
    <div class="nom"><svg class="ic"><use href="#ic-tv"></use></svg> <span data-i18n="arranque_nom">LA PANTALLA DEL PÚBLICO</span></div>
    <div class="don" data-i18n-html="arranque_don">Arrástrala a la televisión o al proyector y ponla a pantalla completa con <b>F</b>.<br>
      La otra ventana —la de la cola y los botones— se queda en el ordenador.</div>
  </div>
  <button class="b" id="empezar" data-i18n="arranque_boton">Encender la pantalla</button>
  <div class="p">
    <span data-i18n="arranque_p">Pulsa una vez y ya se queda. El navegador no deja que suene el vídeo
    hasta que alguien toca la pantalla.</span><br><br>
    <span id="quienSuena"></span>
  </div>
  <label><input type="checkbox" id="conMicro" checked>
    <span data-i18n="arranque_micro">Que la pantalla reaccione al ruido de la sala</span></label>
</div>

<script src="js/cancion.js"></script>
<script src="js/actuacion.js"></script>
<script src="js/estados.js"></script>
<script src="js/termometro.js"></script>
<script src="js/textos.js"></script>
<script src="js/qr.js"></script>
<script>
'use strict';
/* Lo único que sigue aquí dentro: los valores que pone PHP. El resto
   está en js/proyector/, que se puede leer sin ejecutar nada. */
const CFG = {
  urlPedir:  <?= json_encode($urlPedir) ?>,
  peticiones:<?= $hayPeticiones ? 'true' : 'false' ?>,
  esLocal:   <?= $esLocal ? 'true' : 'false' ?>,
  ssid:      <?= json_encode($ssid) ?>,
  clave:     <?= json_encode($clave) ?>,
  limite:    <?= (int)$limite ?>,
  conClave:  <?= $conClave ? 'true' : 'false' ?>
};
</script>

<!-- El orden importa: ajustes crea las constantes, escenas necesita la
     tabla de js/estados.js, y servidor arranca cuando ya existe todo. -->
<script src="js/proyector/ajustes.js"></script>
<script src="js/proyector/reproductor.js"></script>
<script src="js/proyector/audio.js"></script>
<script src="js/proyector/carteles.js"></script>
<script src="js/proyector/escenas.js"></script>
<script src="js/proyector/servidor.js"></script>
<script src="js/instalar.js"></script>
</body>
</html>
