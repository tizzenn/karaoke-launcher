/* ═══════════════════════════════════════════════════════════════════
   show.js — las cartas de reto del Modo Show

   Un botón. Saca una carta en la tele para quien va a salir: «canta con
   una mano en el corazón», «que toda la sala haga los coros».

   ── Por qué esto y no un marcador ───────────────────────────────────
   El Modo Show empezó con un marcador de equipos y se quitó antes de
   terminarlo. En cuanto hay un número en pantalla, la sala mira el
   número y la noche se convierte en otra cosa. Las cartas hacen lo que
   de verdad se quería —que la gente se atreva— y no dejan a nadie
   perdiendo.

   ── La regla de las cartas ──────────────────────────────────────────
   Un reto se cumple sin saber cantar. Nada que dependa de afinar, de la
   letra o de la voz. Están en `retos.json`, se editan con el Bloc de
   notas y añadir una no es programar.

   ── Dos montones ────────────────────────────────────────────────────
   «Suave» vale para el primer grupo de la noche; «fuerte» necesita
   confianza o unas horas de fiesta encima. Sacar «canta de rodillas» a
   las nueve y media es la forma más rápida de que nadie coja el micro.

   ── Se enseña antes de publicarla ───────────────────────────────────
   El botón NO manda la carta a la tele: la elige y la enseña aquí. El
   operador la lee, y si no le pega a quien va a salir, saca otra. Esa
   pausa de dos segundos es la diferencia entre un juego y un mal rato.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

var KL = window.KL || {};
window.KL = KL;

KL.show = (function () {

  let cartas = { suave: [], fuerte: [] };
  let cargadas = false;
  let propuesta = '';
  /* Las últimas que han salido, para no repetir. Se guardan los textos y
     no los índices: si alguien edita `retos.json` en mitad de la fiesta,
     los índices dejan de significar lo mismo y los textos no. */
  const recientes = [];
  const NO_REPETIR = 8;

  async function cargar() {
    if (cargadas) return;
    cargadas = true;
    try {
      const r = await fetch('retos.json', { cache: 'no-cache' });
      const j = await r.json();
      for (const m of ['suave', 'fuerte'])
        cartas[m] = (Array.isArray(j[m]) ? j[m] : [])
          .map(x => String(x).trim()).filter(Boolean);
    } catch (e) {
      /* Sin cartas el botón lo dice y no se rompe nada: el resto de la
         aplicación no depende de esto para funcionar. */
      cartas = { suave: [], fuerte: [] };
    }
  }

  /* Una al azar del montón, evitando las últimas. Si el montón es más
     corto que la memoria, se acepta repetir antes que no dar ninguna:
     quedarse sin carta por ser exquisito es peor que repetir. */
  function sacar(monton) {
    const todas = cartas[monton] || [];
    if (!todas.length) return '';
    const frescas = todas.filter(t => !recientes.includes(t));
    const de = frescas.length ? frescas : todas;
    return de[Math.random() * de.length | 0];
  }

  function proponer(monton) {
    propuesta = sacar(monton);
    pintar();
    return propuesta;
  }

  /* Publicarla es mandarla al estado: de ahí la coge la tele por el
     mismo sondeo que todo lo demás. Cero canales nuevos. */
  function publicar() {
    if (!propuesta) return;
    recientes.push(propuesta);
    while (recientes.length > NO_REPETIR) recientes.shift();
    KL.comandos.sacarCarta(propuesta);
    propuesta = '';
    pintar();
  }

  function retirar() {
    propuesta = '';
    KL.comandos.retirarCarta();
    pintar();
  }

  function enPantalla() {
    return ((KL.estado.show || {}).reto || {}).texto || '';
  }

  /* ---- El panel ---------------------------------------------------- */
  function pintar() {
    const caja = document.getElementById('panelShow');
    if (!caja) return;
    /* Fuera del tema Show el panel no se pinta. El CSS lo esconde, pero
       eso solo tapa: si además se pintara, `draw()` estaría rehaciendo
       cada segundo y medio un HTML que nadie ve. */
    if (KL.estado.tema !== 'show') { caja.innerHTML = ''; return; }
    const hay = cartas.suave.length + cartas.fuerte.length;
    const enTele = enPantalla();

    const texto = !hay
      ? 'No hay cartas: mira que <b>retos.json</b> esté junto a Karaoke.bat.'
      : propuesta
        ? 'Propuesta: <b>' + esc(propuesta) + '</b>'
        : enTele
          ? 'En la tele: <b>' + esc(enTele) + '</b>'
          : 'Saca una carta de reto para quien va a salir.';

    caja.innerHTML =
      '<button class="btn g" id="bRetoSuave"' + (hay ? '' : ' disabled') + '>Carta suave</button>' +
      '<button class="btn g" id="bRetoFuerte"' + (hay ? '' : ' disabled') + '>Carta fuerte</button>' +
      '<span class="carta">' + texto + '</span>' +
      (propuesta ? '<button class="btn" id="bRetoVa">A la tele</button>' : '') +
      (propuesta || enTele ? '<button class="btn g" id="bRetoNo">Quitar</button>' : '');

    const en = (id, f) => { const b = document.getElementById(id); if (b) b.addEventListener('click', f); };
    en('bRetoSuave',  () => proponer('suave'));
    en('bRetoFuerte', () => proponer('fuerte'));
    en('bRetoVa',     publicar);
    en('bRetoNo',     retirar);
  }

  function iniciar() {
    /* Solo se carga el archivo si el tema es Show. En Clásico esto no
       existe y pedir un archivo que no se va a usar es ruido en el
       servidor, que atiende una petición cada vez. */
    if (KL.estado.tema !== 'show') return;
    cargar().then(pintar);
  }

  return { iniciar, pintar, proponer, publicar, retirar, enPantalla,
           /* para las pruebas */ cuantas: () => cartas.suave.length + cartas.fuerte.length };
})();
