/* ═══════════════════════════════════════════════════════════════════
   termometro.js — el pulso de la fiesta, no el estado de la aplicación

   Sustituye a las notificaciones. Y el cambio no es de formato: es de
   **quién habla**.

   Antes la pantalla del público decía cosas como «hay 4 canciones en la
   cola». Eso es la aplicación hablando de sí misma delante de treinta
   personas que no saben ni que hay una aplicación. Lo que se lee ahí no
   es información: es que el aparato está pidiendo que le miren.

   Ahora dice «¡Anímate a pedir la tuya!». Misma cifra por debajo, otra
   cosa completamente distinta en la sala.

   ── Dos reglas, y la segunda importa más ─────────────────────────────

   **1. No nombra nunca una pieza de la aplicación.** Ni cola, ni lista,
   ni biblioteca, ni «canciones pendientes». Y **ningún número**: en
   cuanto pone «3 canciones», la gente se pone a calcular cuánto falta
   para la suya y la pantalla vuelve a ser un panel de control.

   **2. Ningún mensaje puede sonar a que sobras.** Ni «cola llena», ni
   «espera larga», ni «quedan pocas». Alguien que está dudando si pedir
   una canción —que suele ser quien más ganas tiene y menos se atreve— es
   exactamente a quien va dirigido esto.

   Por eso el nivel más alto NO dice «deja de pedir». Dice «tenemos
   canciones para disfrutar un buen rato», y quien lo lee entiende lo
   mismo sin que nadie le haya cerrado la puerta. Esa es la diferencia
   entre un aviso y un anfitrión.

   Hay pruebas que buscan las palabras de las dos reglas y fallan si
   aparece alguna, en los textos de fábrica y en los configurados.

   La diferencia entre las dos frases:

       «4 canciones en la cola»   →  te informo de mi estado interno
       «¡Anímate a pedir la tuya!» →  te digo qué puedes hacer

   La segunda es la que hace que alguien saque el móvil.

   ── Por qué es un termómetro y no un contador ────────────────────────
   Un número exacto invita a compararlo con el de antes. Una barra que
   sube dice «esto va bien» sin que nadie tenga que contar nada, que es
   justo lo que se quiere transmitir desde el fondo de un salón.

   ── Genérico a propósito ─────────────────────────────────────────────
   Los niveles —desde cuántas, qué icono, qué frase— están en los ajustes
   y se editan sin tocar código. Y cada nivel puede llevar una frase
   distinta por tema en `textos`, que hoy nadie rellena y funciona igual:
   el día que Peques quiera decir otra cosa, es un campo, no una rama.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

var KL = window.KL || {};
window.KL = KL;

KL.termometro = (function () {

  /* Lo que se usa si los ajustes no traen nada. Está aquí y no en el
     servidor para que la pantalla del público siga sabiendo qué decir
     aunque el archivo de ajustes se quede corto. */
  const POR_DEFECTO = [
    { desde:  0, estado:'Empezando',   icono:'🌱',
      texto:'La fiesta tiene sitio para más canciones.' },
    { desde:  3, estado:'Animándose',  icono:'🎤',
      texto:'Si te apetece cantar, este es un buen momento.' },
    { desde:  7, estado:'En marcha',   icono:'🎉',
      texto:'¡La fiesta está en marcha! Gracias por formar parte.' },
    { desde: 16, estado:'Muy animada', icono:'✨',
      texto:'Hay muchas ganas de cantar. ¡Qué buen ambiente!' },
    { desde: 26, estado:'A tope',      icono:'❤️',
      texto:'Tenemos canciones para disfrutar un buen rato.' }
  ];

  /* Con cuántas se considera el termómetro «lleno». No es el último
     umbral: es un poco más, para que llegar al último nivel no deje la
     barra clavada al 100 % el resto de la noche. */
  const TOPE = 30;

  /* Los ajustes se pueden pasar a mano, y no es un capricho: la pantalla
     del público no monta `KL.estado`, así que leyendo solo de ahí se
     quedaba siempre con los de fábrica —y los de fábrica no traen los
     textos por tema—. El resultado era que Peques y Show decían lo
     mismo que Clásico y no había forma de ver por qué.

     Quien tiene el dato lo pasa. Quien no, se lee el estado. */
  function niveles(ajustes) {
    const t = ajustes || (KL.estado && KL.estado.termometro) || {};
    const n = Array.isArray(t.niveles) && t.niveles.length ? t.niveles : POR_DEFECTO;
    /* Ordenados siempre: si alguien edita los umbrales a mano y los deja
       desordenados, el termómetro seguiría funcionando pero diría lo que
       no toca. Se ordena aquí y no se le pide a nadie que tenga cuidado. */
    const lista = n.slice().sort((a, b) => (a.desde || 0) - (b.desde || 0));

    /* Y con nombre de momento pase lo que pase. Un ajuste guardado por la
       versión anterior del formulario viene sin `estado` —el campo existía
       en los niveles de fábrica pero la página de Ajustes no lo escribía—
       y sin nombre el termómetro se queda mudo: enseña la frase larga y
       ninguna etiqueta.

       Se rellena por posición desde los de fábrica. No es adivinar: es lo
       que había ahí antes de que el formulario lo borrase. */
    return lista.map((n, i) =>
      n.estado ? n : Object.assign({}, n, {
        estado: (POR_DEFECTO[i] || POR_DEFECTO[POR_DEFECTO.length - 1]).estado
      }));
  }

  function encendido(ajustes) {
    const t = ajustes || (KL.estado && KL.estado.termometro) || {};
    return t.on !== false;
  }

  /* El nivel que toca para un número de canciones. Sin tocar el DOM y
     sin leer nada de fuera: así se puede probar de verdad. */
  function nivel(cuantas, tema, ajustes) {
    const lista = niveles(ajustes);
    let elegido = lista[0];
    for (const n of lista) if (cuantas >= (n.desde || 0)) elegido = n;
    const i = lista.indexOf(elegido);

    /* La frase del tema si la hay; si no, la de siempre. El mecanismo
       existe aunque hoy nadie lo use: el día que Peques quiera decir
       otra cosa es rellenar un campo, no escribir un `if`. */
    const texto = (elegido.textos && tema && elegido.textos[tema]) || elegido.texto || '';

    return {
      indice: i,
      total: lista.length,
      /* El nombre del momento de la fiesta —Empezando, En marcha— y no
         un nivel de cola. Es lo que hace que la pantalla hable como un
         anfitrión y no como un panel. */
      estado: elegido.estado || '',
      icono: elegido.icono || '',
      texto: texto,
      /* El porcentaje sale del número real, no del nivel: así la barra
         se mueve al añadir una canción aunque no cambie la frase, y la
         gente ve que su petición ha llegado. */
      pct: Math.max(4, Math.min(100, Math.round(cuantas / TOPE * 100)))
    };
  }

  /* ---- Pintar --------------------------------------------------------
     Recibe el elemento y el número. No busca nada por su cuenta: lo usan
     la pantalla del público y la de pedir, y cada una sabe cuántas hay
     mejor que este archivo. */
  /* Propio y no el global: este archivo lo cargan la tele y la página de
     pedir, y `pedir.php` no monta el vocabulario de la interfaz. Un
     módulo que se puede llevar a otra página sin arrastrar medio
     proyecto vale lo que cuesta escribir tres líneas. */
  const esc = s => String(s ?? '').replace(/[&<>"']/g,
    c => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c]));

  /* ---- El latido cuando entra una petición ----------------------------
     No es un aviso -«nueva canción añadida» hablaría de la aplicación,
     que es justo la regla que este archivo existe para romper-. Es que
     la barra reaccione, como reacciona una sala cuando alguien se anima:
     contagia, no informa. Por eso no hay texto nuevo, solo un pulso de
     la propia barra que ya está ahí. Auditoría UX, 2026-08-03.

     Un WeakMap por elemento: la tele y pedir.php llaman a pintar() con
     su propia caja, y cada una tiene que notar SU último número, no el
     de la otra pantalla. */
  const ultimoVisto = new WeakMap();

  function pintar(caja, cuantas, tema, ajustes) {
    if (!caja) return;
    if (!encendido(ajustes)) { caja.classList.add('oculto'); return; }
    caja.classList.remove('oculto');

    const antes = ultimoVisto.get(caja);
    const sube = antes !== undefined && (cuantas || 0) > antes;
    ultimoVisto.set(caja, cuantas || 0);
    if (sube) {
      /* Se relanza aunque ya estuviera animando: dos peticiones seguidas
         tienen que notarse dos veces, no una vez más larga. */
      caja.classList.remove('tmLatido');
      void caja.offsetWidth;   // fuerza el reflow: si no, el navegador fusiona las dos clases y no repite la animación
      caja.classList.add('tmLatido');
    }

    const n = nivel(cuantas || 0, tema, ajustes);
    caja.dataset.nivel = n.indice;
    /* Ni un número, y esto no es un descuido: en cuanto pone «3
       canciones» la gente se pone a calcular cuánto falta para la suya y
       la pantalla se convierte en un panel de control. La barra dice lo
       mismo sin invitar a contar. */
    caja.innerHTML =
      '<div class="tmTexto"><span class="tmIcono">' + esc(n.icono) + '</span>' +
      '<span class="tmFrase">' + esc(n.texto) + '</span></div>' +
      '<div class="tmBarra"><i style="width:' + n.pct + '%"></i></div>' +
      (n.estado ? '<div class="tmEstado">' + esc(n.estado) + '</div>' : '');
  }

  return { pintar, nivel, niveles, encendido, POR_DEFECTO, TOPE };
})();
