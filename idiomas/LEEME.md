# Traducir OpenKaraoke Center

No hace falta saber programar. Cada idioma es un archivo `.json` en esta
carpeta: una clave a la izquierda (no se toca), el texto traducido a la
derecha (eso sí se edita).

El español no tiene archivo propio — es el idioma en el que está escrito
el HTML de partida, así que siempre está completo por definición.

## Añadir un idioma nuevo

1. Copia `en.json`, ponle el nombre de tu idioma con el código de dos
   letras (`fr.json` para francés, `de.json` para alemán…).
2. Traduce cada valor. No borres ninguna clave ni cambies lo que hay a
   la izquierda de los dos puntos — si falta una clave, la app enseña el
   español en su lugar en vez de dejar un hueco en blanco, así que es
   mejor dejar sin traducir algo (se ve en español) que borrarlo.
3. Añade el código de tu idioma a `IDIOMAS_DISPONIBLES` en
   `js/idioma.js` y a `BASE` en `sw.js` (una línea cada uno). Si no
   sabes tocar esos archivos, avísalo al abrir el Pull Request y alguien
   lo hace.

## Traducir vía Weblate (recomendado, sin usar GitHub)

**[hosted.weblate.org/projects/openkaraoke-center](https://hosted.weblate.org/projects/openkaraoke-center/)**
— entra, elige un idioma (o pide uno nuevo) y traduce desde un
formulario web, sin descargar nada ni tocar archivos a mano. Los
cambios llegan aquí solos, como Pull Requests.

`idiomas/en.json` es la plantilla: cualquier idioma nuevo que se
empiece en Weblate parte de sus claves.
