# OpenKaraoke Center

Lanza vídeos de karaoke de YouTube en una fiesta, sin cortar la música.

Cuando alguien quiere cantar algo, lo normal es abrir YouTube, buscar,
equivocarse de vídeo porque el primero es la canción original y no la pista de
karaoke, volver atrás, encontrar el bueno — y mientras tanto la fiesta se ha
desinflado. Repetido cuarenta veces en una noche, eso arruina la noche.

Esto lo convierte en cuatro pasos: **buscar, elegir, añadir, cantar.**

## Qué hace

- **Ventana de resultados con carátulas**, para distinguir de un vistazo la
  versión karaoke de la original.
- **Biblioteca propia**: lo que se busca una vez no se busca nunca más.
- **Cola** que encadena las canciones solas, y se reordena arrastrando.
- **Peticiones desde el móvil**: enseñas un QR y los invitados añaden
  canciones desde su teléfono. Solo pueden añadir, nada más.
- **Descarga con yt-dlp**, para cantar sin depender de la wifi.
- Se instala como aplicación (PWA) y funciona en el móvil.

## Cómo se usa

1. Ejecuta `Preparar.bat`. Descarga PHP, yt-dlp y ffmpeg **dentro de la
   carpeta**: no toca el sistema ni el registro. Para desinstalar, borras la
   carpeta.
2. Ejecuta `Karaoke.bat`.
3. Abre Ajustes y pon tu clave de la YouTube Data API v3.

Sin clave también funciona: puedes pegar enlaces de YouTube.

Instrucciones completas en [LEEME.md](LEEME.md). La documentación técnica, en
[PROJECT.md](PROJECT.md).

## Por qué no usa frameworks

Ni React, ni Vue, ni Bootstrap, ni npm, ni paso de compilación. HTML, CSS,
JavaScript y PHP a mano.

No es nostalgia: esto tiene que arrancar con doble clic en el ordenador de
alguien que no programa, cinco minutos antes de que llegue la gente. Cada
dependencia es una forma más de que eso falle esa noche.

## Tus datos son tuyos

La clave de la API no sale nunca del servidor: el navegador se la pide a tu
propio PHP, que es quien habla con Google. La biblioteca, los ajustes y los
vídeos descargados se quedan en `data/`, que está en el `.gitignore`. No hay
cuentas, ni estadísticas, ni servidores ajenos.

## Licencia

Copyright (C) 2026 tizzenn

Software libre bajo la [GPLv3](LICENSE) o posterior. Sin ninguna garantía.
