/* ═══════════════════════════════════════════════════════════════════
   comandos.js — todo lo que se le puede pedir al estado compartido

   Antes, cada sitio que quería cambiar algo escribía a mano el mensaje
   que viaja al servidor:

       accion({ accion:'anadir_cola', video:v })

   Eso estaba repartido en diecisiete puntos de seis archivos, con trece
   nombres de acción escritos como texto suelto. Tres problemas, todos
   del mismo tipo:

   1. Una errata —'anadir_kola'— no la ve nadie. El servidor recibe una
      acción que no conoce, devuelve el estado sin tocar, y la canción
      simplemente no aparece. Sin error, sin aviso, sin rastro.

   2. Para saber qué puede cambiar el estado hay que leerse todo el JS.
      Este archivo es esa lista, y cabe en una pantalla.

   3. El día que el protocolo cambie —autenticación, lotes, otra ruta—
      hay que tocar diecisiete sitios. Ahora se toca uno.

   La regla, a partir de aquí: **nadie fuera de este archivo escribe
   `accion:` seguido de un nombre**. Si hace falta algo nuevo, se añade
   una función aquí y se llama por su nombre.

   No cambia nada visible. Es la red debajo del trapecio.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

var KL = window.KL || {};
window.KL = KL;

KL.comandos = (function () {

  /* El único punto del proyecto que conoce el formato del mensaje.
     Se resuelve tarde —dentro de la función, no al cargar— porque este
     archivo se carga antes de que el almacén termine de montarse, y
     porque la versión Lite y la de servidor ponen KL.almacen en
     momentos distintos. */
  const enviar = datos => KL.almacen.accion(datos);

  return {

    /* ── La cola ─────────────────────────────────────────────────── */

    anadirALaCola: video =>
      enviar({ accion: 'anadir_cola', video }),

    quitarDeLaCola: id =>
      enviar({ accion: 'quitar_cola', id }),

    /* El orden se manda entero, no como «mover de A a B». Dos aparatos
       reordenando a la vez con instrucciones relativas dan un resultado
       que no quería ninguno; con la lista completa gana el último, que
       es lo que la gente espera al arrastrar. */
    ordenarLaCola: orden =>
      enviar({ accion: 'ordenar_cola', orden }),

    vaciarLaCola: () =>
      enviar({ accion: 'vaciar_cola' }),

    /* ── La biblioteca ───────────────────────────────────────────── */

    guardarEnLaBiblioteca: video =>
      enviar({ accion: 'anadir_biblioteca', video }),

    quitarDeLaBiblioteca: videoId =>
      enviar({ accion: 'quitar_biblioteca', videoId }),

    /* Solo la usa importar una copia de seguridad. Pisa la colección
       entera: no es un comando para andar llamándolo por ahí. */
    reemplazarLaBiblioteca: biblioteca =>
      enviar({ accion: 'reemplazar_biblioteca', biblioteca }),

    /* Cambia campos sueltos de una pista esté donde esté —cola,
       biblioteca o las dos—. `local:null` es «ya no está descargada»,
       y hay que distinguirlo de «no me consta»: por eso se manda el
       objeto de campos tal cual y no se filtran los nulos. */
    actualizarPista: (videoId, campos) =>
      enviar(Object.assign({ accion: 'actualizar_pista', videoId }, campos)),

    /* ── La actuación ────────────────────────────────────────────── */

    /* El estado del evento entero. Lo manda la máquina de estados en
       cada transición; nadie más debería llamarlo. */
    publicarEvento: evento =>
      enviar({ accion: 'evento', evento }),

    /* Aquí estaban `marcarSonando` y `nadaSuena`. Han desaparecido en el
       paso 5 y no vuelven: «qué se está cantando» es `evento.pistaId` y
       nada más. El servidor sigue publicando un campo `sonando`, pero
       ahora lo DEDUCE del evento en vez de guardarlo, así que no puede
       contradecirlo. */

    /* Cerrar una actuación: sale de la cola si `retirar`, y entra en el
       historial pase lo que pase. El servidor pone la hora, no nosotros:
       el reloj del PC del karaoke y el del móvil no coinciden. */
    terminarActuacion: (id, retirar) =>
      enviar({ accion: 'fin_actuacion', id: id || null, retirar: !!retirar }),

    vaciarElHistorial: () =>
      enviar({ accion: 'vaciar_historial' }),

    /* ── La pantalla del público ─────────────────────────────────── */

    calentamiento: activo =>
      enviar({ accion: 'calentamiento', activo: !!activo }),

    /* `fijo` es el cartelón que se queda quieto; null vuelve a rotar. */
    cartelones: (paneles, fijo) =>
      enviar({ accion: 'paneles', paneles, fijo: fijo || null })
  };
})();
