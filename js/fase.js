/* ═══════════════════════════════════════════════════════════════════
   fase.js — en qué momento de la noche estamos

   ── El problema que resuelve ────────────────────────────────────────
   Hasta ahora el operador sabía en qué punto de la fiesta estaba porque
   **se acordaba**. La aplicación conocía su estado interno —ESPERA,
   PREPARADA, INTERPRETACION— y no lo decía en ninguna parte, o lo decía
   en jerga que no significa nada a las dos de la mañana con ruido.

   Una fiesta de karaoke tiene cinco momentos, y los tiene siempre,
   la organice quien la organice:

     🔥 Calentamiento          suena música, la gente va llegando y pide
     🎤 Actuación en curso     alguien está cantando
     🎵 Preparando la siguiente
     🌙 Últimas actuaciones    se acerca la hora
     👋 Fin de fiesta          ya no queda nadie en la cola

   No son modos que haya que elegir: **son consecuencia de lo que está
   pasando**. Y por eso este archivo no guarda nada.

   ── Por qué se DEDUCE (MODELO §6) ──────────────────────────────────
   La tentación era obvia: un campo `fase` en `estado.json` y a correr.
   Sería el mismo error que costó dos fallos seguidos con el icono de
   «descargada» — dos datos diciendo lo mismo acaban discrepando, y no
   es cuestión de cuidado, es cuestión de tiempo.

   La fase sale de cosas que YA existen: si el calentamiento está puesto,
   en qué estado va el evento, cuánta cola queda y si ya ha cantado
   alguien. Nadie puede ponerla «mal» porque nadie la pone.

   Y tiene una consecuencia práctica buena: cualquier ventana —el
   operador, la tele, el móvil— calcula la misma fase leyendo el mismo
   estado, sin que nadie se la tenga que mandar.

   ── Lo que todavía no está ──────────────────────────────────────────
   «Últimas actuaciones» necesita saber a qué hora se acaba la fiesta, y
   eso hoy no lo sabe nadie. Está escrito y probado, pero solo aparece si
   el operador pone una hora de cierre. Prefiero una fase que no salga
   nunca a una que se invente cuándo está acabando la noche.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

var KL = window.KL || {};
window.KL = KL;

KL.fase = (function () {

  /* El orden importa: es el de la noche. */
  const FASES = {
    calentamiento: {
      id: 'calentamiento', icono: '🔥', nombre: 'Calentamiento',
      /* Lo que dice cada fase NO es un resumen de sí misma —«estás en
         calentamiento» no informa de nada— sino **qué toca hacer ahora**.
         Es la diferencia entre una etiqueta y una ayuda. */
      pista: 'Suena música y la gente va pidiendo. Coloca pantallas y micros.'
    },
    actuacion: {
      id: 'actuacion', icono: '🎤', nombre: 'Actuación en curso',
      pista: 'Deja el portátil. Mira a la sala.'
    },
    preparando: {
      id: 'preparando', icono: '🎵', nombre: 'Preparando la siguiente',
      pista: 'Cuando esté delante del micro, dale a Empezar.'
    },
    ultimas: {
      id: 'ultimas', icono: '🌙', nombre: 'Últimas actuaciones',
      pista: 'Se acerca la hora. La fiesta no se corta: entra en su último tramo.'
    },
    fin: {
      id: 'fin', icono: '👋', nombre: 'Fin de fiesta',
      pista: 'No queda nadie en la cola. Puedes poner música y dejarlo correr.'
    }
  };

  /* En la Cabina DJ no hay actuaciones ni hay nadie esperando al micro,
     así que las cinco fases del karaoke no significan nada allí. Tiene
     una sola, y decirlo es más honesto que forzar las otras. */
  const CABINA = {
    id: 'cabina', icono: '🎧', nombre: 'Cabina DJ',
    pista: 'Las canciones se encadenan solas. Nadie tiene que dar a nada.'
  };

  /* `hechos` es todo lo que hace falta, y sale entero del estado:

       espacio        'karaoke' | 'dj'
       evento         el estado del evento
       enCola         cuántas actuaciones esperan
       yaHaCantado    ¿hay historial?
       calentamiento  ¿está puesto el calentamiento?
       minutosParaCerrar  null si no hay hora de cierre

     Ninguno es un dato nuevo salvo el último, y ese es opcional. */
  function de(hechos) {
    const h = hechos || {};
    if ((h.espacio || 'karaoke') === 'dj') return CABINA;

    const ev = h.evento || 'ESPERA';

    /* Cantando manda sobre todo lo demás, incluido el calentamiento: si
       alguien está al micro, la fiesta no se está calentando. */
    if (ev === 'LLAMADA' || ev === 'INTERPRETACION') return FASES.actuacion;

    /* El calentamiento es el estado inicial de la noche, y deja de serlo
       en cuanto ha cantado alguien — aunque el operador se olvide de
       quitarlo, que es lo normal. */
    if (h.calentamiento && !h.yaHaCantado) return FASES.calentamiento;

    if (!h.enCola) return h.yaHaCantado ? FASES.fin : FASES.calentamiento;

    /* Y lo último: si se sabe a qué hora acaba esto y ya no cabe mucho
       más, se dice. Sin hora de cierre, esta fase no existe. */
    const m = h.minutosParaCerrar;
    if (m !== null && m !== undefined && isFinite(m) && m <= 30) return FASES.ultimas;

    return FASES.preparando;
  }

  /* Los mismos hechos, sacados del estado de la aplicación. Está aquí
     para que `de()` siga siendo una función pura que se puede probar
     sin navegador ni servidor. */
  function delEstado(S) {
    return {
      espacio: S.modo,
      evento: (S.evento && S.evento.estado) || 'ESPERA',
      enCola: (S.queue || []).filter(t => (t.espacio || 'karaoke') === S.modo).length,
      yaHaCantado: !!(S.historial || []).length,
      calentamiento: !!S.calentamiento,
      minutosParaCerrar: null
    };
  }

  return { de, delEstado, FASES, CABINA };
})();
