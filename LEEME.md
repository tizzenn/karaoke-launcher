# Karaoke Launcher v1.0

Buscas, eliges el vídeo bueno, se añade a la cola, y a cantar. Nada más.

## Arrancar

**La primera vez**, doble clic en **`Preparar.bat`**. Comprueba qué te falta
y se ofrece a descargarlo, preguntando antes. Todo se instala dentro de esta
misma carpeta, en modo portable: no toca el PATH, ni el registro, ni instala
nada en el sistema. Para desinstalarlo todo, borras la carpeta.

Descarga tres cosas, de sus webs oficiales:

- **PHP**, obligatorio, de windows.php.net. Sin esto no arranca.
- **yt-dlp**, opcional, de github.com/yt-dlp. Para cantar sin internet.
- **ffmpeg**, opcional, de github.com/yt-dlp/FFmpeg-Builds. Lo necesita yt-dlp
  para juntar el vídeo con el audio.

Son unos 60 MB en total. Si ya tienes alguno instalado, lo detecta y no lo
descarga.

**Alternativa:** si prefieres un instalador de verdad (acceso directo en el
escritorio, cortafuegos configurado solo), hay uno en
[los Releases del repositorio](https://github.com/tizzenn/karaoke-launcher/releases/latest).
Pide administrador solo para esa regla del cortafuegos; por lo demás hace lo
mismo que `Preparar.bat`.

**A partir de ahí**, doble clic en **`Karaoke.bat`** y se abre el navegador
solo. Si te falta PHP, el propio `Karaoke.bat` te ofrece abrir el preparador.

No abras `index.html` con doble clic: YouTube devuelve el error 153 cuando
la página se sirve desde `file://`, porque exige un origen web de verdad.
Para eso está el `.bat`, que levanta PHP y sirve la página por `http://`.

## La clave de la API

Se pone desde el navegador: engranaje ⚙ → **Ajustes del servidor**. Ahí hay
un desplegable que explica los seis pasos para conseguir una gratis en Google
Cloud, y un botón que la prueba de verdad contra Google y te dice qué falla si
falla. Se guarda en `data/ajustes.json`. El navegador nunca la ve: cuando buscas, la
página pregunta a `api/buscar.php`, y es el servidor quien habla con Google.
Por eso puedes subir el proyecto a GitHub sin regalar la clave: no está en
ningún archivo de código.

Sin clave la aplicación sigue funcionando: pegas el enlace de YouTube en el
buscador y la canción se añade igual, con su título y su carátula.

## Que pidan desde el móvil

Pulsa el botón **📱** de la cabecera. Sale un QR. Quien lo escanee entra en
`pedir.php`, busca su canción y la manda a la cola. Aparece sola en tu
pantalla, sin recargar nada.

Todos tienen que estar en tu misma wifi. La ventana negra del `.bat` te dice
la dirección exacta por si alguien prefiere teclearla.

En `api/config.php` puedes poner una `clave_fiesta` para que no entre
cualquiera, cambiar cuántas canciones seguidas puede pedir cada uno
(`limite_por_invitado`, por defecto 3) o cerrar las peticiones del todo
(`peticiones => false`).

## Instalarla como aplicación

Con la página abierta en Chrome o Edge, en la barra de direcciones aparece un
icono de instalar. Queda con su icono, arranca a pantalla completa y la
interfaz carga aunque la red vaya regular. En Android igual, desde el menú
del navegador, «Añadir a pantalla de inicio».

## Cantar sin internet

El botón **⤓** de cada canción la descarga al disco con yt-dlp. A partir de
ahí suena desde `data/videos/` y le da igual la wifi.

Necesitas `yt-dlp` y `ffmpeg`, que instala `Preparar.bat` por ti. Si
prefieres ponerlos a mano, déjalos junto a `Karaoke.bat` o escribe la ruta
en Ajustes. Las canciones descargadas salen marcadas con ⭳.

## Idiomas

Cada aparato elige el suyo con el selector ES/EN de la cabecera —no afecta a
los demás—. Para traducir a un idioma nuevo sin tocar código, usa
[Weblate](https://hosted.weblate.org/projects/openkaraoke-center/): un
formulario web, sin instalar nada. Los detalles técnicos están en
[idiomas/LEEME.md](idiomas/LEEME.md).

## Detalles útiles para la fiesta

La biblioteca arranca plegada en la estrellita de la izquierda, para que el
foco esté en el buscador. Pulsa la ★ para abrirla o cerrarla.

La tecla `V` cambia entre vista completa, compacta y mini. La mini deja solo
una barrita discreta abajo a la derecha.

Si la conexión baja, la aplicación no se queda colgada: baja la calidad,
reintenta desde donde iba y, si no hay manera, pasa a la siguiente. Arranca a
360p a propósito, porque en un karaoke importa que no se corte.

`espacio` pausa, `/` va al buscador, `Ctrl+→` siguiente canción.

## Estructura

    Preparar.bat             instala PHP, yt-dlp y ffmpeg (portable)
    Karaoke.bat              arranca el servidor
    index.html               la aplicación
    pedir.php                la página de los invitados
    manifest.webmanifest     para instalarla
    sw.js                    para que cargue sin red
    ajustes.php              configuración desde el navegador
    api/config.php           valores por defecto
    api/comun.php            utilidades compartidas
    api/buscar.php           intermediario con YouTube
    api/estado.php           biblioteca y cola compartidas
    api/descargar.php        yt-dlp
    data/estado.json         se crea solo
    data/videos/             lo descargado

## Antes de subirlo a GitHub

Borra `data/ajustes.json` (lleva tu clave), `data/estado.json` (tu
biblioteca) y el contenido de `data/videos/` (tus descargas). Quien lo baje
pondrá su propia clave desde la página de ajustes.

## Licencia

Karaoke Launcher — Copyright (C) 2026 tizzenn

Software libre bajo la Licencia Pública General GNU, versión 3 o posterior.
Puedes usarlo, estudiarlo, modificarlo y redistribuirlo. Se distribuye sin
ninguna garantía. El texto completo está en el archivo `LICENSE`.
