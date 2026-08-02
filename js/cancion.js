/* ═══════════════════════════════════════════════════════════════════
   cancion.js — qué es una canción

   Un vídeo concreto. Existe fuera de la fiesta: está en tu biblioteca
   aunque no des una fiesta nunca, y sigue siendo la misma canción la
   cantes hoy, mañana o jamás.

   ── Qué sabe ────────────────────────────────────────────────────────
       videoId       su identidad; no cambia nunca
       title         título real, resuelto contra YouTube
       channel       de quién es el vídeo
       thumb         la carátula
       duration      segundos, o 0 si no se sabe (oEmbed no la da)
       local         nombre del archivo si está descargada
       noEmbed       el dueño no deja incrustarla

   ── Qué NUNCA sabe ──────────────────────────────────────────────────
   Si está en una cola. Si alguien la ha cantado. Quién la pidió. Nada
   de eso es de la canción: es de la actuación.

   ── Por qué existe este archivo ─────────────────────────────────────
   No por ceremonia. Por una razón muy concreta: **es el único sitio del
   proyecto que sabe cómo está guardada una canción**. Hoy los campos
   están sueltos dentro del mismo objeto que la actuación —así se guardó
   en la v1.0 y hay gente con su colección en ese formato—. Mañana serán
   un mapa aparte indexado por videoId, que es lo que toca.

   Cuando ese día llegue, cambia este archivo y nadie más se entera. Ese
   es todo el objetivo. Sin este paso previo habría que tocar ochenta y
   siete sitios a la vez, y ochenta y siete sitios a la vez es como se
   rompen las cosas.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

var KL = window.KL || {};
window.KL = KL;

KL.Cancion = (function () {

  const CAMPOS = ['videoId', 'title', 'channel', 'thumb', 'duration', 'local', 'noEmbed'];

  /* Saca la parte «canción» de cualquier cosa que la lleve dentro: un
     resultado de búsqueda, una fila de la cola, una entrada del
     historial. Hoy es una copia de campos; mañana será una búsqueda en
     el catálogo. Quien llama no nota la diferencia. */
  function de(x) {
    if (!x) return null;
    const c = {};
    for (const k of CAMPOS) if (x[k] !== undefined) c[k] = x[k];
    /* No se exige `videoId` para devolver algo. La primera versión sí lo
       exigía —«sin identidad no es una canción»— y era una trampa: un
       registro antiguo o a medias dejaba de tener título EN TODAS LAS
       PANTALLAS a la vez, en silencio. Un dato incompleto se enseña
       incompleto; no se hace desaparecer. Quien necesite la identidad
       que pregunte por `id()`, que sí devuelve null. */
    return Object.keys(c).length ? c : null;
  }

  const id       = c => (c && c.videoId) || null;
  const titulo   = c => (c && c.title) || '';
  const canal    = c => (c && c.channel) || '';
  const caratula = c => (c && c.thumb) || '';
  const segundos = c => (c && c.duration) || 0;

  /* Descargada = se puede cantar sin internet. Es lo que decide el
     semáforo verde de la lista y lo que salva la fiesta cuando la wifi
     se cae a mitad. */
  const enDisco     = c => !!(c && c.local);
  const seDejaVer   = c => !(c && c.noEmbed);
  const misma       = (a, b) => !!(a && b && a.videoId === b.videoId);

  /* Un título que todavía no se ha resuelto contra YouTube. Se pinta
     distinto porque no es un título de verdad, es un marcador. */
  const sinResolver = c => !titulo(c) || /^Cargando|^Vídeo /.test(titulo(c));

  return { de, id, titulo, canal, caratula, segundos,
           enDisco, seDejaVer, misma, sinResolver, CAMPOS };
})();
