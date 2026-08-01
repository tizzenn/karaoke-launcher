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
