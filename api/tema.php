<?php
/* ═══════════════════════════════════════════════════════════════════
   tema.php — cambiar el tema sin ir a los ajustes

   El tema estaba solo en `ajustes.php`, detrás de una contraseña
   opcional y en una página que hay que saber que existe. Resultado
   práctico: casi nadie sabía que había temas. Una función que no se
   encuentra no existe.

   Ahora se cambia desde un botón de la barra del operador, y este
   archivo es lo único que hacía falta para eso.

   ── Por qué un endpoint aparte y no una acción de estado.php ────────
   Porque el tema NO es estado de la fiesta: es un ajuste, vive en
   `data/ajustes.json` y sobrevive a vaciar la cola. Meterlo en
   `estado.json` habría sido más rápido de escribir y habría dejado dos
   sitios donde vive el mismo dato — que es exactamente la clase de
   atajo que este proyecto lleva medio año deshaciendo.

   Escribe UN campo y solo uno. No acepta nada más, así que no puede
   convertirse por accidente en una puerta trasera a los ajustes: la
   clave de la API está en ese mismo archivo.
   ═══════════════════════════════════════════════════════════════════ */

declare(strict_types=1);
require __DIR__ . '/comun.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  error_json('esto solo se cambia con POST', 405);
}

const TEMAS = ['clasico', 'fiesta', 'kids', 'show'];

$in   = cuerpo_json();
$tema = (string)($in['tema'] ?? '');
if (!in_array($tema, TEMAS, true)) error_json('tema desconocido: ' . $tema);

/* Un invitado no cambia el tema. No es un secreto —quien esté en la red
   ya ve la pantalla— pero cambiarle el aspecto a la tele de la fiesta
   desde el móvil es justo la clase de broma que arruina un cumpleaños. */
if (!empty($in['invitado'])) error_json('los invitados no cambian el tema', 403);

$archivo = DIR_DATOS . '/ajustes.json';
$actual  = [];
if (is_file($archivo)) {
  $j = json_decode((string)file_get_contents($archivo), true);
  if (is_array($j)) $actual = $j;
}
$actual['tema'] = $tema;

/* Temporal + rename, como en ajustes.php: en este archivo está la clave
   de la API, y un corte a mitad de escritura la borraría entera. */
if (guardar_json_atomico($archivo, $actual) === false) {
  error_json('no he podido escribir en la carpeta data');
}

salir_json(['ok' => true, 'tema' => $tema]);
