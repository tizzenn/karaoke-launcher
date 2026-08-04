<?php
/* ═══════════════════════════════════════════════════════════════════
   OpenKaraoke Center v1.1 — configuración

   NO hace falta tocar este archivo: todo se configura desde el
   navegador, en la página «ajustes.php». Lo que guardes allí va a
   data/ajustes.json y manda sobre los valores de aquí abajo.

   Este archivo nunca se sirve al navegador: la clave se queda dentro.
   ═══════════════════════════════════════════════════════════════════ */

$defecto = [

  /* Clave de la YouTube Data API v3. Vacía = solo enlaces pegados.
     Se mantiene por compatibilidad con instalaciones de antes del pool
     de claves (ver claves.php): si 'api_keys' está vacío, esta se usa
     como si fuera la única entrada, con el nombre "Personal". */
  'api_key' => '',

  /* Varias claves con nombre, prioridad (el orden de la lista) y
     activa/desactivada. Cuando una devuelve quotaExceeded se salta a la
     siguiente sola, sin que el operador se entere a media fiesta.
     [{ 'nombre' => 'Personal', 'key' => 'AIza...', 'activa' => true }, ...] */
  'api_keys' => [],

  /* Se añade a cada búsqueda. Vacío = buscar tal cual. */
  'sufijo' => 'karaoke',

  /* ¿Pueden los invitados pedir desde el móvil con el QR? */
  'peticiones' => true,

  /* Contraseña para la página del móvil. Vacío = sin contraseña. */
  'clave_fiesta' => '',

  /* Canciones seguidas que puede tener cada invitado en la cola. */
  'limite_por_invitado' => 3,

  /* Ruta a yt-dlp. 'yt-dlp' basta si está en el PATH. */
  'yt_dlp' => 'yt-dlp',

  /* Altura máxima al descargar. 480 va sobrado para karaoke. */
  'altura_max' => 480,

  /* Marca de la edición. Vacío en la versión pública.
     Si tiene texto, sustituye el título de arriba a la izquierda por un
     rótulo de espray con ese texto. Es la ÚNICA diferencia entre la
     versión pública y la de casa: la misma aplicación, otro ajuste. */
  'edicion' => '',

  /* ---- Wifi para el QR de la pantalla pública ----------------------
     El nombre de la red se detecta solo en Windows (netsh); si sale
     vacío o es otro sistema, se escribe aquí. La contraseña hay que
     ponerla a mano SIEMPRE: Windows no la entrega sin permisos de
     administrador, y pedirle a la gente que ejecute el karaoke como
     administrador para dibujar un QR no compensa.
     Vacío = no se muestra el QR de wifi (que es lo correcto: un QR que
     no conecta es peor que ninguno). */
  'wifi_ssid'  => '',
  'wifi_clave' => '',

  /* ---- Canciones repetidas -----------------------------------------
     La unidad de esto NO es la cancion: es la ACTUACION. Si Ana canta
     «Vivir mi vida» y una hora despues Maria tambien quiere cantarla,
     eso no es una repeticion: son dos actuaciones distintas, y es de las
     cosas mas normales que pasan en un karaoke.

     Por eso lo normal es permitirlo. Lo unico que se para siempre —y no
     depende de este ajuste— es que la MISMA persona pida dos veces lo
     mismo, que casi siempre es un doble clic.

       siempre   dos personas pueden cantar la misma cancion
       avisar    se permite, pero se dice que ya la habia elegido alguien
       no        en esta fiesta cada cancion suena una vez

     Por defecto «siempre»: un karaoke no es Spotify. */
  'repetidas' => 'siempre',

  /* ---- Termometro de fiesta ----------------------------------------
     Lo que la pantalla del publico dice del ambiente. NUNCA habla de la
     aplicacion —ni cola, ni lista, ni «canciones pendientes»— y NUNCA
     dice un numero: en cuanto pone «3 canciones», la gente se pone a
     hacer calculos y la pantalla se convierte en un panel de control.

     ── Y aqui hay una regla mas, que es la que de verdad importa ──
     Ningun mensaje puede sonar a que sobras, a que llegas tarde o a que
     molestas. Ni «cola llena», ni «espera larga», ni «quedan pocas».
     Alguien que duda si pedir una cancion es exactamente quien mas
     necesita que la pantalla le diga que hay sitio.

     Por eso el nivel mas alto NO dice «deja de pedir». Dice «tenemos
     canciones para disfrutar un buen rato», y quien lo lee entiende lo
     mismo sin que nadie le haya cerrado la puerta. La diferencia entre
     las dos frases es la diferencia entre un aviso y un anfitrion.

     `estado` es el nombre corto del momento —Empezando, Animandose, En
     marcha…— y no un nivel de cola. `textos` permite que cada tema hable
     a su manera con el mismo estado por debajo. */
  /* El apagón entre canciones y el confeti de los aplausos (pantalla
     pública). Apagado por defecto en `false` sería lo prudente para una
     función nueva, pero aquí se elige `true`: sin esto la escena de
     aplausos simplemente se corta en seco como llevaba haciendo desde
     el principio, así que "activado" es volver al comportamiento de
     siempre con un adorno encima, no un riesgo nuevo. Quien no lo quiera,
     lo apaga en un clic. */
  'efectos_escenicos' => true,
  'termometro' => true,
  'termometro_niveles' => [
    ['desde' =>  0, 'estado' => 'Empezando', 'icono' => '🌱',
     'texto' => 'Todavía queda sitio para muchas voces.',
     'textos' => [
          'fiesta' => '¡Vamos calentando motores!',
          'kids' => '⭐ Hay sitio para más canciones.',
          'show' => '🎭 El escenario espera nuevos artistas.'
     ]],
    ['desde' =>  3, 'estado' => 'Animándose', 'icono' => '🎤',
     'texto' => '¿Quién se anima con la siguiente?',
     'textos' => [
          'fiesta' => '¿Te animas con una canción?',
          'kids' => '🎵 Si te apetece cantar, puedes pedir una canción.',
          'show' => '🌟 El espectáculo empieza a tomar forma.'
     ]],
    ['desde' =>  7, 'estado' => 'En marcha', 'icono' => '🎉',
     'texto' => '¡La fiesta está en marcha! Gracias por formar parte.',
     'textos' => [
          'fiesta' => '🔥 Esto ya está arrancando.',
          'kids' => '🌈 ¡Qué bien lo estamos pasando!',
          'show' => '✨ El espectáculo está en marcha.'
     ]],
    ['desde' => 16, 'estado' => 'Muy animada', 'icono' => '✨',
     'texto' => 'Hay muchas ganas de cantar. ¡Qué buen ambiente!',
     'textos' => [
          'fiesta' => '🥳 ¡La fiesta está a tope de energía!',
          'kids' => '🎈 Tenemos muchas canciones preparadas.',
          'show' => '🎬 Una gran noche nos espera.'
     ]],
    ['desde' => 26, 'estado' => 'A tope', 'icono' => '❤️',
     'texto' => 'Tenemos canciones para disfrutar un buen rato.',
     'textos' => [
          'fiesta' => '💃 Tenemos música para seguir disfrutando.',
          'kids' => '🎊 ¡La fiesta sigue llena de música!',
          'show' => '👏 Tenemos actuaciones para un buen rato.'
     ]],
  ],


  /* Contraseña para entrar en ajustes.php. Vacío = sin contraseña.
     Ponla si el PC va a estar accesible desde la wifi de la fiesta. */
  'clave_ajustes' => '',

  /* ---- Cabina DJ: qué suena cuando no canta nadie -------------------
     El silencio entre canciones es lo que apaga una fiesta. Esto se
     configura una vez y no se vuelve a tocar en toda la noche.

     `ambiente_fuente`  'youtube' | 'carpeta' | 'no'
     `ambiente_lista`   una lista de reproducción de YouTube, o un canal.
                        Se acepta la dirección entera, el identificador
                        suelto o la del canal: el programa lo normaliza.
     `ambiente_carpeta` ruta a una carpeta con mp3/m4a, para cuando no
                        hay internet o se prefiere música propia.
     `ambiente_volumen` 0-100. Por debajo de la voz, siempre: es fondo,
                        no es el espectáculo.

     Por defecto va la lista de **DJ Noize**, que publica mezclas de trap
     y hip hop todas las semanas. Se eligió una lista que se actualiza
     sola a propósito: una lista fija se queda vieja y a los tres meses
     suena siempre lo mismo. */
  /* APAGADA de fábrica, y a conciencia. Que el programa empiece a sonar
     solo, la primera vez que se abre, en el ordenador de alguien que
     todavía está montando la fiesta, es una sorpresa desagradable. Se
     enciende cuando el dueño decide que quiere música. */
  'ambiente_fuente'  => 'no',
  'ambiente_lista'   => 'https://www.youtube.com/channel/UCAj9nn-gOcKuD4ropg44HCw',
  'ambiente_carpeta' => '',

  /* ¿Vuelve sola después de cada canción? **No.** El silencio es el
     comportamiento normal de la aplicación; la música ambiente es una
     herramienta que el operador enciende cuando quiere y que se calla al
     empezar una actuación. Que volviera sola significaba que apagarla no
     servía de nada: al terminar la siguiente canción estaba otra vez ahí.
     Quien la quiera continua, lo enciende aquí. */
  'ambiente_auto'    => false,

  /* ---- Tema: la personalidad de la aplicación -----------------------
     'clasico' | 'fiesta' | 'kids'

     No es un paquete de colores: cambia también con qué palabras habla la
     aplicación. En Kids no dice «Fin de actuación», dice «¡Lo has hecho
     genial!», y no enseña ningún QR.

     Es un ajuste de la FIESTA y no de cada aparato: la tele y el operador
     tienen que ir vestidos igual. */
  'tema' => 'clasico',
  'ambiente_volumen' => 35,
];

/* Lo guardado desde ajustes.php manda sobre lo de arriba. */
$guardado = [];
$f = __DIR__ . '/../data/ajustes.json';
if (is_file($f)) {
  $j = json_decode((string)file_get_contents($f), true);
  if (is_array($j)) $guardado = $j;
}

return array_merge($defecto, $guardado);
