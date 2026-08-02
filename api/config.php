<?php
/* ═══════════════════════════════════════════════════════════════════
   Karaoke Launcher v1.0 — configuración

   NO hace falta tocar este archivo: todo se configura desde el
   navegador, en la página «ajustes.php». Lo que guardes allí va a
   data/ajustes.json y manda sobre los valores de aquí abajo.

   Este archivo nunca se sirve al navegador: la clave se queda dentro.
   ═══════════════════════════════════════════════════════════════════ */

$defecto = [

  /* Clave de la YouTube Data API v3. Vacía = solo enlaces pegados. */
  'api_key' => '',

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

  /* Contraseña para entrar en ajustes.php. Vacío = sin contraseña.
     Ponla si el PC va a estar accesible desde la wifi de la fiesta. */
  'clave_ajustes' => '',
];

/* Lo guardado desde ajustes.php manda sobre lo de arriba. */
$guardado = [];
$f = __DIR__ . '/../data/ajustes.json';
if (is_file($f)) {
  $j = json_decode((string)file_get_contents($f), true);
  if (is_array($j)) $guardado = $j;
}

return array_merge($defecto, $guardado);
