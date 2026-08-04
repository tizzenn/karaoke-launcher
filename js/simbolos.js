/* ═══════════════════════════════════════════════════════════════════
   simbolos.js — todos los iconos de la aplicación, en un solo sitio

   Se usa así:   <svg class="ic"><use href="#ic-play"></use></svg>
   o desde JS:   KL.icono('play')

   ── Por qué un archivo .js y no un .svg ──────────────────────────────
   La forma elegante sería `<use href="iconos/simbolos.svg#play">`, con
   el sprite en su propio archivo. No funciona: **Chromium no resuelve
   referencias a documentos SVG externos** desde <use> (Firefox sí). No
   da error: simplemente no dibuja nada, y toda la interfaz sale sin
   iconos. Comprobado, no supuesto.

   Así que el sprite se pega dentro del documento. Este script va el
   PRIMERO dentro de <body> y se inserta ahí mismo mientras la página se
   analiza, de modo que cuando el navegador llega al primer icono ya
   están todos disponibles: ni parpadeo, ni esperar a un fetch.

   ── Por qué no la fuente de Google ───────────────────────────────────
   La aplicación tiene que verse con el router caído, que es justo la
   noche en que más falta hace. Un <link> a fonts.googleapis.com deja la
   interfaz sin iconos esa noche.

   Todos los identificadores llevan el prefijo `ic-`. No es cosmético: el
   sprite se inyecta DENTRO del documento, así que sus identificadores
   comparten espacio con los de la página. Había un icono `aviso` y un
   cartel `#aviso` en la pantalla pública, y `querySelector('#aviso')`
   devolvía el <symbol> del sprite: la tele reventaba al intentar poner
   una clase sobre un elemento SVG. Con el prefijo, la colisión no puede
   volver a ocurrir.

   Trazados de 24×24, estilo Material Symbols (relleno, sin trazo), para
   que todos tengan el mismo peso visual. Para cambiar a los glifos
   oficiales de Google basta sustituir los trazados de aquí abajo
   manteniendo los identificadores: el resto de la aplicación no cambia.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

var KL = window.KL || {};
window.KL = KL;

KL.SIMBOLOS = `<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" style="position:absolute" aria-hidden="true">
<!-- ---------- transporte ---------- -->
<symbol id="ic-play" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></symbol>
<symbol id="ic-pausa" viewBox="0 0 24 24"><path d="M6 5h4v14H6zM14 5h4v14h-4z"/></symbol>
<symbol id="ic-stop" viewBox="0 0 24 24"><path d="M6 6h12v12H6z"/></symbol>
<symbol id="ic-anterior" viewBox="0 0 24 24"><path d="M6 6h2v12H6zM18 18V6l-9 6z"/></symbol>
<symbol id="ic-siguiente" viewBox="0 0 24 24"><path d="M16 6h2v12h-2zM6 18l9-6-9-6z"/></symbol>
<symbol id="ic-repetir" viewBox="0 0 24 24"><path d="M7 7h10v3l4-4-4-4v3H5v6h2V7zm10 10H7v-3l-4 4 4 4v-3h12v-6h-2v4z"/></symbol>
<symbol id="ic-barajar" viewBox="0 0 24 24"><path d="M10.59 9.17 5.41 4 4 5.41l5.17 5.17 1.42-1.41zM14.5 4l2.04 2.04L4 18.59 5.41 20 17.96 7.46 20 9.5V4h-5.5zm.33 9.41-1.41 1.41 3.13 3.13L14.5 20H20v-5.5l-2.04 2.04-3.13-3.13z"/></symbol>

<!-- ---------- navegación y acciones ---------- -->
<symbol id="ic-buscar" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></symbol>
<symbol id="ic-embudo" viewBox="0 0 24 24"><path d="M3 4h18l-7 8.2V21l-4-2.4v-6.4z"/></symbol>
<symbol id="ic-cerrar" viewBox="0 0 24 24"><path d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></symbol>
<symbol id="ic-mas" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></symbol>
<symbol id="ic-comprobado" viewBox="0 0 24 24"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></symbol>
<symbol id="ic-plato" viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm0 2a8 8 0 0 1 7.75 6h-4.2A4 4 0 0 0 12 8V4zm0 14a4 4 0 0 1-1.2-7.82l1.2 3.32A1.5 1.5 0 1 0 12 18zm0 2v-2a6 6 0 0 0 5.66-4h2.09A8 8 0 0 1 12 20z"/><circle cx="12" cy="12" r="1.6"/></symbol>
<symbol id="ic-paleta" viewBox="0 0 24 24"><path d="M12 3c-4.97 0-9 3.58-9 8 0 4.42 4.03 8 9 8 .83 0 1.5-.67 1.5-1.5 0-.39-.15-.74-.39-1.01-.23-.26-.38-.61-.38-.99 0-.83.67-1.5 1.5-1.5H16c2.76 0 5-2.24 5-5 0-3.87-4.03-6-9-6zM6.5 12a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm3-4a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm3 4a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/></symbol>
<symbol id="ic-ajustes" viewBox="0 0 24 24"><path d="M3 17v2h6v-2H3zM3 5v2h10V5H3zm10 16v-2h8v-2h-8v-2h-2v6h2zM7 9v2H3v2h4v2h2V9H7zm14 4v-2H11v2h10zm-6-4h2V7h4V5h-4V3h-2v6z"/></symbol>
<symbol id="ic-papelera" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></symbol>
<symbol id="ic-borrar-todo" viewBox="0 0 24 24"><path d="M15 16h4v2h-4zm0-8h7v2h-7zm0 4h6v2h-6zM3 18c0 1.1.9 2 2 2h6c1.1 0 2-.9 2-2V8H3v10zM14 5h-3l-1-1H6L5 5H2v2h12z"/></symbol>
<symbol id="ic-arrastrar" viewBox="0 0 24 24"><path d="M9 4a1.6 1.6 0 1 1 0 3.2A1.6 1.6 0 0 1 9 4zm6 0a1.6 1.6 0 1 1 0 3.2A1.6 1.6 0 0 1 15 4zM9 10.4a1.6 1.6 0 1 1 0 3.2 1.6 1.6 0 0 1 0-3.2zm6 0a1.6 1.6 0 1 1 0 3.2 1.6 1.6 0 0 1 0-3.2zM9 16.8a1.6 1.6 0 1 1 0 3.2 1.6 1.6 0 0 1 0-3.2zm6 0a1.6 1.6 0 1 1 0 3.2 1.6 1.6 0 0 1 0-3.2z"/></symbol>
<symbol id="ic-abrir-fuera" viewBox="0 0 24 24"><path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/></symbol>
<symbol id="ic-izquierda" viewBox="0 0 24 24"><path d="M15.41 7.41 14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></symbol>
<symbol id="ic-derecha" viewBox="0 0 24 24"><path d="M10 6 8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></symbol>

<!-- ---------- estado ---------- -->
<symbol id="ic-estrella" viewBox="0 0 24 24"><path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></symbol>
<symbol id="ic-estrella-borde" viewBox="0 0 24 24"><path d="M22 9.24l-7.19-.62L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.63-7.03L22 9.24zM12 15.4l-3.76 2.27 1-4.28-3.32-2.88 4.38-.38L12 6.1l1.71 4.04 4.38.38-3.32 2.88 1 4.28L12 15.4z"/></symbol>
<symbol id="ic-aviso" viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></symbol>
<symbol id="ic-musica" viewBox="0 0 24 24"><path d="M15 6H3v2h12V6zm0 4H3v2h12v-2zM3 16h8v-2H3v2zM17 6v8.18c-.31-.11-.65-.18-1-.18-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3V8h3V6h-5z"/></symbol>
<symbol id="ic-microfono" viewBox="0 0 24 24"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5-3c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></symbol>

<!-- ---------- descargas: un solo glifo, el color dice el estado ---------- -->
<symbol id="ic-descargar" viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></symbol>
<symbol id="ic-descargado" viewBox="0 0 24 24"><path d="M20 18H4v-2H2v2c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-2h-2v2z"/><path d="M10.6 14.2 7 10.6l-1.4 1.4 5 5 8.8-8.8-1.4-1.4z"/></symbol>
<symbol id="ic-descarga-no" viewBox="0 0 24 24"><path d="M19 9h-4V3H9v3.17l6.83 6.83zM5 18v2h14v-2zM3.4 2 2 3.4 6.6 8H5l7 7 1.3-1.3L20.6 21 22 19.6z"/></symbol>

<!-- ---------- pantallas ---------- -->
<symbol id="ic-tv" viewBox="0 0 24 24"><path d="M21 3H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h5v2h8v-2h5c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 14H3V5h18v12z"/></symbol>
<symbol id="ic-movil" viewBox="0 0 24 24"><path d="M17 1.01 7 1c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-1.99-2-1.99zM17 19H7V5h10v14z"/></symbol>
<!-- ── Iconos de la barra superior ──────────────────────────────────
     Estos cuatro estaban mal elegidos y la auditoría de iconografía lo
     dejó claro: representaban la IMPLEMENTACIÓN, no la INTENCIÓN. El
     calentamiento salía con un QR (porque uno de los cartelones lleva un
     QR), la pantalla del televisor con una tele suelta —que solo dice
     «pantalla», no «segunda pantalla»— y la vista con una flecha de salir
     de pantalla completa, que significa otra cosa.

     La regla que se aplicó: **si el icono necesita explicación, el icono
     está mal.** Cada uno responde ahora a un verbo, no a un objeto. -->

<!-- Calentamiento = calentar la sala. Una llama se entiende sin leer. -->
<symbol id="ic-fuego" viewBox="0 0 24 24"><path d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67zM11.71 19c-1.78 0-3.22-1.4-3.22-3.14 0-1.62 1.05-2.76 2.81-3.12 1.77-.36 3.6-1.21 4.62-2.58.39 1.29.59 2.65.59 4.04 0 2.65-2.15 4.8-4.8 4.8z"/></symbol>

<!-- Peticiones = llegan canciones desde el móvil. El móvil con la flecha
     saliendo dice «esto viene de ahí», que es lo que hay que entender. -->
<symbol id="ic-movil-envia" viewBox="0 0 24 24"><path d="M16 1H8a2 2 0 0 0-2 2v10h2V5h8v14H8v-2H6v4a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2z"/><path d="M4.5 11.25h5.19l-1.72-1.72 1.06-1.06 3.53 3.53-3.53 3.53-1.06-1.06 1.72-1.72H4.5z"/></symbol>

<!-- Pantalla externa = mandar esto a OTRA pantalla. Dos rectángulos y la
     flecha que va de uno al otro. Una tele sola no dice «segunda». -->
<symbol id="ic-segunda-pantalla" viewBox="0 0 24 24"><path d="M2 5a2 2 0 0 1 2-2h6v2H4v8h4v2H4a2 2 0 0 1-2-2z"/><path d="M13 8h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-7a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2zm0 2v8h7v-8z"/><path d="M7.2 8.4h3.4L9.1 6.9 10.2 5.8l3.4 3.4-3.4 3.4-1.1-1.1 1.5-1.5H7.2z"/></symbol>

<!-- Vista = cómo se reparte la pantalla. Un panel grande y dos pequeños:
     se entiende que cambia la disposición, no que «mira» algo. -->
<symbol id="ic-disposicion" viewBox="0 0 24 24"><path d="M3 3h18a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1zm1 2v14h9V5zm11 0v6h5V5zm0 8v6h5v-6z"/></symbol>

<!-- Freestyle: un micro sobre ondas. No es karaoke —no hay letra— y no es
     la cabina —no es música de fondo—: es cantar o tocar encima de una base. -->

<symbol id="ic-qr" viewBox="0 0 24 24"><path d="M3 3h8v8H3V3zm2 2v4h4V5H5zM13 3h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5zM13 13h3v3h-3zM18 13h3v3h-3zM13 18h3v3h-3zM18 18h3v3h-3z"/></symbol>
<symbol id="ic-pantalla-completa" viewBox="0 0 24 24"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></symbol>
<symbol id="ic-salir-pantalla" viewBox="0 0 24 24"><path d="M5 16h3v3h2v-5H5v2zm3-8H5v2h5V5H8v3zm6 11h2v-3h3v-2h-5v5zm2-11V5h-2v5h5V8h-3z"/></symbol>

<!-- ---------- sonido y red ---------- -->
<symbol id="ic-volumen" viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></symbol>
<symbol id="ic-silencio" viewBox="0 0 24 24"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3 3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4 9.91 6.09 12 8.18V4z"/></symbol>
<symbol id="ic-wifi" viewBox="0 0 24 24"><path d="M1 9l2 2c4.97-4.97 13.03-4.97 18 0l2-2C16.93 2.93 7.08 2.93 1 9zm8 8 3 3 3-3c-1.65-1.66-4.34-1.66-6 0zm-4-4 2 2c2.76-2.76 7.24-2.76 10 0l2-2C15.14 9.14 8.87 9.14 5 13z"/></symbol>
<symbol id="ic-wifi-no" viewBox="0 0 24 24"><path d="M22.99 9C19.15 5.16 13.8 3.76 8.84 4.78l2.52 2.52c3.47-.17 6.99 1.05 9.63 3.7l2-2zm-4 4c-1.29-1.29-2.84-2.13-4.49-2.56l3.53 3.53.96-.97zM2 3.05 5.07 6.1C3.6 6.82 2.22 7.78 1 9l1.99 2c1.24-1.24 2.67-2.16 4.2-2.77l2.24 2.24C7.81 10.89 6.27 11.73 5 13v.01L6.99 15c1.36-1.36 3.14-2.04 4.92-2.06L18.98 20l1.27-1.26L3.29 1.79 2 3.05zM9 17l3 3 3-3c-1.65-1.66-4.34-1.66-6 0z"/></symbol>
</svg>`;

/* Se inserta ya, durante el análisis de la página. `document.body` existe
   porque esta etiqueta <script> está dentro de <body>. */
if (document.body) document.body.insertAdjacentHTML('afterbegin', KL.SIMBOLOS);
else document.addEventListener('DOMContentLoaded',
       () => document.body.insertAdjacentHTML('afterbegin', KL.SIMBOLOS));
