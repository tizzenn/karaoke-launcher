/* ═══════════════════════════════════════════════════════════════════
   actuacion.js — qué es cantar una canción esta noche

   *Esta* canción, *esta* vez, pedida por *esta* persona. Es lo que no
   existía y por eso «lo que se está cantando» tenía tres nombres.

   ── Qué sabe ────────────────────────────────────────────────────────
       id            su identidad dentro de la noche
       (una Canción) a cuál se refiere
       quien         el nombre que escribió quien la pidió, o nada si
                     la puso el operador
       pedida        vino de un móvil
       cantada_en    cuándo terminó, si terminó

   ── Qué NUNCA sabe ──────────────────────────────────────────────────
   **En qué posición está.** Eso es del orden de la cola, no suyo. Si
   hubiera un campo `posicion`, habría dos verdades y una se
   desincronizaría el primer día que dos móviles reordenaran a la vez.

   **Si está sonando.** El estado es del Evento y de nadie más. Si la
   actuación también lo guardara, tendríamos otra vez dos sitios donde
   mirar, que es exactamente el problema del que venimos.

   ── La regla de este archivo ────────────────────────────────────────
   Una actuación **referencia** una canción, no la copia. Hoy los campos
   viven físicamente en el mismo objeto —así se guardó en la v1.0— pero
   nadie fuera de aquí y de `cancion.js` puede depender de eso. El día
   que las canciones pasen a un catálogo aparte, `cancion()` deja de
   copiar campos y pasa a buscar en el catálogo. Nada más cambia.

   Por eso `titulo(a)` existe pudiendo escribirse `a.title`: el atajo
   funciona hoy y ata el proyecto a la forma de hoy.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

var KL = window.KL || {};
window.KL = KL;

KL.Actuacion = (function () {

  const id      = a => (a && a.id) || null;
  const cancion = a => KL.Cancion.de(a);

  /* Atajos a la canción. Existen para que el resto del proyecto no tenga
     que escribir `KL.Cancion.titulo(KL.Actuacion.cancion(a))` cien
     veces: eso sería cambiar una dependencia mala por una incómoda. */
  const titulo   = a => KL.Cancion.titulo(cancion(a));
  const canal    = a => KL.Cancion.canal(cancion(a));
  const caratula = a => KL.Cancion.caratula(cancion(a));
  const segundos = a => KL.Cancion.segundos(cancion(a));
  const videoId  = a => KL.Cancion.id(cancion(a));

  /* Quién la pidió. Vacío cuando la puso el operador, y eso significa
     algo: en la pantalla no se pone nada, porque se da por supuesto. */
  const quien    = a => (a && a.pedida) || '';
  const laPidio  = a => !!(a && a.pedida);

  const yaSeCanto = a => !!(a && a.cantada_en);

  /* Lo que se enseña como «quién canta»: el nombre si alguien la pidió,
     y si no el título. Estaba escrito a mano en tres pantallas con tres
     criterios ligeramente distintos. */
  const rotulo = a => quien(a) || titulo(a);

  return { id, cancion, titulo, canal, caratula, segundos, videoId,
           quien, laPidio, yaSeCanto, rotulo };
})();
