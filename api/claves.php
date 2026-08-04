<?php
/* ═══════════════════════════════════════════════════════════════════
   claves.php — varias claves de YouTube, con reparto y reserva

   Con una sola clave, agotar la cuota diaria (10.000 unidades, unas 99
   búsquedas) para la aplicación entera hasta el día siguiente. Con
   varias, se prueba la de más prioridad y si devuelve quotaExceeded se
   pasa a la siguiente sin que el operador tenga que hacer nada — ni
   enterarse.

   No es aleatorio a propósito: elegir al azar entre una agotada y dos
   buenas sigue fallando la mitad de las veces. Se empieza siempre por
   la primera de la lista (la prioridad la decide el orden) y solo se
   baja a la siguiente cuando la de arriba falla de verdad.

   El estado de qué clave está agotada vive aparte de `ajustes.json`:
   no es algo que el dueño configure, es algo que pasa solo y se
   deshace solo al día siguiente — mismo patrón que `cuota.json` en
   cache.php. */

declare(strict_types=1);

function claves_estado_archivo(): string { return __DIR__ . '/../data/claves_estado.json'; }

function claves_estado_leer(): array {
  $j = json_decode((string)@file_get_contents(claves_estado_archivo()), true);
  return is_array($j) ? $j : [];
}

function claves_estado_guardar(array $estado): void {
  $f = claves_estado_archivo();
  $tmp = $f . '.' . getmypid() . '.tmp';
  if (@file_put_contents($tmp, json_encode($estado, JSON_UNESCAPED_UNICODE)) !== false)
    @rename($tmp, $f);
}

/* Lo guardado en ajustes.json ya trae `api_keys`, pero una instalación
   con la `api_key` suelta de antes de esto no debe quedarse sin clave
   al actualizar: se completa con una entrada "Personal" al vuelo, sin
   tocar el archivo. */
function claves_lista(array $cfg): array {
  $lista = is_array($cfg['api_keys'] ?? null) ? $cfg['api_keys'] : [];
  if (!$lista && trim((string)($cfg['api_key'] ?? '')) !== '') {
    $lista = [['nombre' => 'Personal', 'key' => trim((string)$cfg['api_key']), 'activa' => true]];
  }
  $out = [];
  foreach ($lista as $c) {
    if (!is_array($c)) continue;
    $key = trim((string)($c['key'] ?? ''));
    if ($key === '') continue;
    $out[] = [
      'nombre'      => trim((string)($c['nombre'] ?? '')) ?: 'Sin nombre',
      'key'         => $key,
      'activa'      => ($c['activa'] ?? true) !== false,
      'propietario' => trim((string)($c['propietario'] ?? '')),
    ];
  }
  return $out;
}

/* Un identificador estable para el estado por clave, sin guardar la
   clave entera en claves_estado.json: si algún día ese archivo se
   comparte por error (una captura de pantalla, un zip de soporte), que
   no lleve las claves dentro. */
function claves_id(string $key): string { return substr(sha1($key), 0, 16); }

function claves_disponibles(array $cfg): array {
  $hoy = date('Y-m-d');
  $estado = claves_estado_leer();
  $out = [];
  foreach (claves_lista($cfg) as $c) {
    if (!$c['activa']) continue;
    $id = claves_id($c['key']);
    if (($estado[$id]['agotada'] ?? '') === $hoy) continue;
    $out[] = $c;
  }
  return $out;
}

function claves_marcar_agotada(string $key): void {
  $id = claves_id($key);
  $estado = claves_estado_leer();
  $estado[$id]['agotada'] = date('Y-m-d');
  claves_estado_guardar($estado);
}

/* Un uso por búsqueda real a la API (no por cada intento fallido: fallar
   no gasta cuota, y contar lo que no se ha gastado confundiría más de lo
   que ayuda). Solo para el «Consultas hoy» de Ajustes — no pretende ser
   el contador exacto de Google, que no lo da en tiempo real. */
function claves_sumar_uso(string $key): void {
  $id = claves_id($key);
  $hoy = date('Y-m-d');
  $estado = claves_estado_leer();
  if (($estado[$id]['usos_fecha'] ?? '') !== $hoy) {
    $estado[$id]['usos_fecha'] = $hoy;
    $estado[$id]['usos'] = 0;
  }
  $estado[$id]['usos'] = (int)($estado[$id]['usos'] ?? 0) + 1;
  claves_estado_guardar($estado);
}

function claves_usos_hoy(string $key): int {
  $id = claves_id($key);
  $estado = claves_estado_leer();
  $hoy = date('Y-m-d');
  if (($estado[$id]['usos_fecha'] ?? '') !== $hoy) return 0;
  return (int)($estado[$id]['usos'] ?? 0);
}

/* ¿Por qué ha fallado esta clave en concreto? Se usa para decidir si
   merece la pena probar la siguiente (quotaExceeded, keyInvalid,
   accessNotConfigured: sí — es la clave, no la búsqueda) o si el fallo
   es de la propia consulta y da igual qué clave se use (el resto de
   motivos: no tiene sentido gastar las demás claves en algo que va a
   fallar igual con cualquiera). */
function claves_motivo_de_clave(?string $motivo): bool {
  /* 'badRequest' entra aquí también: comprobado a mano, es lo que
     devuelve Google para una clave con formato invalido o revocada —no
     solo 'keyInvalid', que es el que documenta la API pero no el único
     que se ha visto en la practica. */
  return in_array($motivo, ['quotaExceeded', 'keyInvalid', 'accessNotConfigured',
                             'ipRefererBlocked', 'badRequest'], true);
}
