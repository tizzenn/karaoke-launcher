/* ═══════════════════════════════════════════════════════════════════
   senales.js — quien avisa no sabe quién escucha

   Hasta ahora, cada módulo que avisaba de algo tenía exactamente UN
   sitio donde avisar:

       KL.reproductor.iniciar({ onFin: ..., onError: ... })

   Un objeto, una vez, y punto. Si dos partes de la aplicación querían
   enterarse de que una canción ha terminado, no había manera: la
   segunda llamada a `iniciar()` pisaba a la primera, en silencio.

   La consecuencia no fue un fallo, fue una forma. Como solo cabía un
   suscriptor, ese suscriptor tenía que ser alguien que lo supiera todo,
   y ese alguien acabó siendo `app.js`: nueve avisos, todos aterrizando
   en el mismo archivo, que por eso mismo pasó de los quinientos
   renglones y se convirtió en el sitio donde vive lo que no vive en
   ningún sitio.

   Esto son treinta líneas y lo desbloquea: cada módulo escucha lo suyo
   donde le corresponde.

   ── Lo único que tiene de listo ─────────────────────────────────────
   Si un oyente revienta, los demás se enteran igual. Sin eso, un fallo
   tonto pintando un botón dejaría sin avisar al motor de estados, y la
   canción se quedaría sonando para siempre. El error se saca por la
   consola en vez de tragárselo: un aviso que desaparece es peor que uno
   que molesta.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

var KL = window.KL || {};
window.KL = KL;

KL.senales = (function () {

  const oyentes = Object.create(null);

  /* Devuelve la función de darse de baja. No hace falta hoy —nada se
     desconecta— pero un oyente que no se puede quitar es una fuga
     esperando a que exista el primer módulo con ciclo de vida. */
  function oir(nombre, fn) {
    if (typeof fn !== 'function') return () => {};
    (oyentes[nombre] || (oyentes[nombre] = [])).push(fn);
    return () => sordo(nombre, fn);
  }

  function sordo(nombre, fn) {
    const lista = oyentes[nombre];
    if (!lista) return;
    const i = lista.indexOf(fn);
    if (i >= 0) lista.splice(i, 1);
  }

  function avisar(nombre, ...datos) {
    const lista = oyentes[nombre];
    if (!lista || !lista.length) return 0;
    /* Sobre una copia: un oyente que se da de baja a sí mismo mientras
       se reparte el aviso descolocaría el recorrido y el siguiente se
       quedaría sin enterarse. */
    for (const fn of lista.slice()) {
      try { fn(...datos); }
      catch (err) { console.error('[senales] «' + nombre + '» ha reventado en un oyente:', err); }
    }
    return lista.length;
  }

  /* Registra de golpe un objeto {onFin, onError, ...} bajo un prefijo:
     `onFin` pasa a escuchar «reproductor:fin». Existe para que los
     módulos que ya llamaban a `iniciar({...})` sigan funcionando tal
     cual mientras se van moviendo a `oir()` uno a uno. */
  function oirObjeto(prefijo, obj) {
    if (!obj) return;
    for (const clave of Object.keys(obj)) {
      const nombre = clave.replace(/^on/, '');
      oir(prefijo + ':' + nombre.charAt(0).toLowerCase() + nombre.slice(1), obj[clave]);
    }
  }

  /* Solo para las pruebas: cuántos escuchan cada cosa. */
  function cuantos(nombre) { return (oyentes[nombre] || []).length; }

  return { oir, sordo, avisar, oirObjeto, cuantos };
})();
