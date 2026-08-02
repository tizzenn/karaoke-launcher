/* ═══════════════════════════════════════════════════════════════════
   estados.js — qué enseña cada pantalla en cada estado

   Una sola tabla, leída por las dos superficies: el monitor del PC
   (index.html) y la tele (proyector.php).

   ── Por qué existe este archivo ──────────────────────────────────────
   Antes cada pantalla decidía por su cuenta, con una cadena de `if`.
   Cinco estados por cuatro sitios que se ramifican son veinte casos
   repartidos por el código, y basta olvidarse de uno. Pasó: al añadir
   LLAMADA, la tele se quedó enseñando el calentamiento mientras el
   operador ya había dado a Empezar, porque una condición de proyector.php
   no se actualizó.

   Con la tabla, añadir un estado es añadir una fila, y olvidarse de una
   pantalla deja de ser posible: si falta la columna, se ve al momento.

   La alternativa que se descartó fue reducir el número de estados. No
   habría ayudado: esa información no desaparece, se convierte en
   banderas sueltas —hayPistaPreparada, enCuentaAtras, acabaDeTerminar—
   y tres banderas dan ocho combinaciones de las que seis no significan
   nada. Con cinco estados solo puede haber uno a la vez, que es
   justamente lo que se quiere.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

var KL = window.KL || {};
window.KL = KL;

/* Columnas:
     pc         modo del monitor del PC: 'operador' u 'interpretacion'
     tele       escena de la pantalla pública
     barra      qué pinta la barra inferior del operador (null = nada)
     sonando    ¿debería estar saliendo audio en este estado?
     calienta   ¿puede el calentamiento tapar esta escena en la tele?
                Esta es la columna cuyo olvido costó el fallo. */
KL.ESCENAS = {
  ESPERA:         { pc:'operador',       tele:'espera',   barra:null,        sonando:false, calienta:true  },
  PREPARADA:      { pc:'operador',       tele:'espera',   barra:'preparada', sonando:false, calienta:true  },
  LLAMADA:        { pc:'interpretacion', tele:'llamada',  barra:null,        sonando:false, calienta:false },
  INTERPRETACION: { pc:'interpretacion', tele:'video',    barra:null,        sonando:true,  calienta:false },
  FIN_ACTUACION:  { pc:'operador',       tele:'aplausos', barra:'fin',       sonando:false, calienta:false }
};

/* Un estado desconocido —un archivo de una versión futura, un dato
   corrupto— no puede dejar la pantalla en blanco en mitad de una fiesta.
   Se cae a ESPERA, que es el estado seguro, y se deja constancia. */
KL.escena = function (estado) {
  const e = KL.ESCENAS[estado];
  if (e) return e;
  console.warn('[estados] estado desconocido:', estado, '— se usa ESPERA');
  return KL.ESCENAS.ESPERA;
};
