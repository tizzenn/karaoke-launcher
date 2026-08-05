/* ═══════════════════════════════════════════════════════════════════
   idioma.js — español o inglés, por dispositivo

   Distinto de textos.js a propósito: textos.js varía el TONO según el
   tema de la fiesta (todos ven el mismo idioma). Esto varía el IDIOMA
   según quien mira la pantalla — un invitado inglés en su móvil, el
   operador español en su portátil, cada uno con el suyo. Por eso vive
   en localStorage (por aparato), no en el estado compartido.

   Cómo se marca el HTML: `data-i18n="clave"` sustituye el texto del
   elemento, `data-i18n-ph="clave"` el placeholder, `data-i18n-title`
   el title, `data-i18n-html` el innerHTML (solo para los pocos textos
   que llevan una etiqueta dentro, como un <br>). Una sola pasada por
   el DOM al cargar y al cambiar de idioma.

   ── Las traducciones NO viven aquí ──────────────────────────────────
   Viven en `idiomas/en.json` (y el que se añada después): un archivo
   de texto plano, sin código, para que cualquiera pueda corregir o
   añadir una traducción con un Pull Request sin tocar JavaScript ni
   entender cómo funciona la aplicación. Este archivo solo sabe LEER
   ese diccionario y aplicarlo — nunca lo escribe.

   Si en español no hace falta pedir nada por red: con el HTML de
   partida ya escrito en español, `idiomas/en.json` solo se descarga
   cuando de verdad hace falta inglés (primera vez que se detecta o se
   elige). Ver `LEEME.md` en `idiomas/` sobre cómo añadir un idioma
   nuevo — sumar un `.json` y una línea aquí basta, sin tocar HTML. */
'use strict';

(function () {

const IDIOMAS_DISPONIBLES = ['es', 'en'];

const DICCIONARIO = {
  es: {},  // el HTML ya está en español: no hace falta ningún archivo
  en: {}
};
const cargados = { es: true, en: false };
let cargando = null;

let idioma = null;
try { idioma = localStorage.getItem('karaoke_idioma'); } catch (e) {}
if (!IDIOMAS_DISPONIBLES.includes(idioma)) {
  /* Sin elección previa: se adivina por el navegador, nunca se impone.
     Cualquier variante de inglés cuenta; todo lo demás cae a español,
     que es el idioma en el que está escrito el HTML de partida. */
  idioma = (navigator.language || '').toLowerCase().startsWith('en') ? 'en' : 'es';
}

/* Para lo poco que una página concreta quiera añadir sin pasar por el
   archivo compartido (casos sueltos, no la traducción general). Se
   fusiona ENCIMA del JSON, así que puede sobreescribir una clave si
   hace falta, pero lo normal es no necesitarlo nunca. */
function cargar(diccionarioEn) {
  Object.assign(DICCIONARIO.en, diccionarioEn);
}

async function asegurarCargado(cual) {
  if (cargados[cual]) return;
  if (cual === 'en') {
    if (!cargando) {
      cargando = fetch('idiomas/en.json')
        .then(r => r.ok ? r.json() : {})
        .then(d => { Object.assign(DICCIONARIO.en, d); cargados.en = true; })
        .catch(() => { cargados.en = true; }); // sin red: se queda en español, no se rompe nada
    }
    await cargando;
  }
}

function t(clave) {
  if (idioma === 'es') return null; // el HTML de partida ya es la versión española
  return DICCIONARIO.en[clave] ?? null;
}

function aplicar() {
  document.documentElement.lang = idioma;
  document.querySelectorAll('[data-i18n]').forEach(el => {
    const v = t(el.dataset.i18n);
    if (v !== null) el.textContent = v;
  });
  document.querySelectorAll('[data-i18n-ph]').forEach(el => {
    const v = t(el.dataset.i18nPh);
    if (v !== null) el.placeholder = v;
  });
  document.querySelectorAll('[data-i18n-title]').forEach(el => {
    const v = t(el.dataset.i18nTitle);
    if (v !== null) el.title = v;
  });
  document.querySelectorAll('[data-i18n-html]').forEach(el => {
    const v = t(el.dataset.i18nHtml);
    if (v !== null) el.innerHTML = v;
  });
  document.querySelectorAll('.idiomaBtn').forEach(b =>
    b.classList.toggle('on', b.dataset.idioma === idioma));
}

async function iniciar() {
  await asegurarCargado(idioma);
  aplicar();
}

async function cambiar(nuevo) {
  if (!IDIOMAS_DISPONIBLES.includes(nuevo)) return;
  idioma = nuevo;
  try { localStorage.setItem('karaoke_idioma', idioma); } catch (e) {}
  await asegurarCargado(idioma);
  aplicar();
}

function actual() { return idioma; }

/* Para los sitios donde el texto se genera en JS, no vive en el HTML
   (mensajes de confirm(), toasts…): `KL.idioma.t('clave')` devuelve la
   traducción o null si toca español o si el diccionario de inglés
   todavía no ha terminado de llegar, para poder escribir
   `KL.idioma.t('x') || 'texto en español de siempre'` sin duplicar
   nada ni dejar un hueco en blanco mientras carga. */
window.KL = window.KL || {};
KL.idioma = { cargar, aplicar, cambiar, actual, t };

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', iniciar);
} else {
  iniciar();
}

})();
