/* ═══════════════════════════════════════════════════════════════════
   cronometro.js — cuánto llevamos y cuánto queda

   La barra de progreso de abajo y los dos relojes. Nada más.

   Estaba en `app.js`, y era el último hilo que ataba el motor de estados
   al archivo de cableado: `evento.js` tenía que llamar a una función que
   vivía en el archivo que se carga el último. Aquí es un módulo con su
   nombre, y el motor no tiene que saber quién pinta.

   Se refresca dos veces por segundo. Más rápido no se nota —los relojes
   van en segundos— y más lento se ve saltar la barra.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

var KL = window.KL || {};
window.KL = KL;

KL.cronometro = (function () {

  const REFRESCO = 500;
  let reloj = null;

  function pintar() {
    const dur = KL.reproductor.duracion();
    const pos = KL.reproductor.posicion();
    KL.$('#tt').textContent = KL.fmt(dur);
    KL.$('#tc').textContent = KL.fmt(pos);
    KL.$('#skf').style.width = dur ? (pos / dur * 100) + '%' : '0';
  }

  function arrancar() { parar(); pintar(); reloj = setInterval(pintar, REFRESCO); }
  function parar()    { clearInterval(reloj); reloj = null; }

  /* Dejar la barra a cero al salir de una canción. Si no, se queda
     enseñando el minuto en el que se cortó la anterior, y el operador lee
     eso como si estuviera sonando algo. */
  function limpiar() {
    parar();
    KL.$('#skf').style.width = '0';
    KL.$('#tc').textContent = '0:00';
    KL.$('#tt').textContent = '0:00';
  }

  /* El motor de estados no llama a nadie: avisa, y quien quiera escucha.
     Este módulo se apunta él solo. */
  KL.senales.oir('reproductor:fin', parar);

  return { arrancar, parar, limpiar };
})();
