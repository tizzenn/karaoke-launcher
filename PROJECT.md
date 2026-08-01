# Karaoke Launcher — documento del proyecto

Este archivo es la referencia única. Quien lo lea entero debería poder
ponerse a trabajar sin preguntar nada ni leer conversaciones anteriores.

Versión del documento: 1.0 · Corresponde al código de agosto de 2026.

---

## 1. Qué es y para qué sirve

Karaoke Launcher lanza vídeos de karaoke de YouTube en una fiesta. El
problema que resuelve es concreto y doméstico: cuando alguien quiere cantar
algo, la secuencia habitual es abrir YouTube, buscar, equivocarse de vídeo
porque el primer resultado es la canción original y no la pista de karaoke,
volver atrás, encontrar el bueno, y mientras tanto se ha cortado la música y
la gente se ha desinflado. Repetido cuarenta veces en una noche, eso arruina
la fiesta.

La aplicación convierte esa secuencia en cuatro pasos: buscar, elegir el
vídeo correcto en una ventana que muestra las alternativas, añadirlo a la
cola, y cantar. Las canciones se encadenan solas. Lo que se ha buscado una
vez no vuelve a buscarse nunca, porque queda guardado con su identificador.

La filosofía completa cabe en cuatro palabras: **buscar, elegir, añadir,
cantar.** Cualquier propuesta que no encaje en esa secuencia sobra.

### Versiones

**v1.0** es la versión completa, con PHP, y es la que se documenta aquí.

**Lite** es un único archivo HTML sin dependencias, sin servidor, que guarda
en el navegador. Sirve para llevarla en un pendrive. Comparte interfaz y
decisiones con la v1.0, pero no tiene backend, ni peticiones desde el móvil,
ni descargas.

### Lo que NO es

No es un reproductor de música general, ni un gestor de biblioteca musical,
ni un clon de Spotify. No descarga música para escucharla luego. No tiene
cuentas de usuario, ni perfiles, ni estadísticas, ni recomendaciones. No
guarda nada en ningún servidor ajeno.

---

## 2. Arquitectura

Sin frameworks. Sin Bootstrap, sin React, sin Vue, sin jQuery, sin npm, sin
paso de compilación. HTML, CSS y JavaScript a mano en el navegador; PHP a
mano en el servidor. Los datos en archivos JSON.

Esta decisión no es nostalgia. La aplicación tiene que arrancar con doble
clic en el ordenador de alguien que no es programador, cinco minutos antes
de que llegue la gente. Cada dependencia es una forma más de que eso falle.

### Mapa de archivos

    Preparar.bat             Instala PHP, yt-dlp y ffmpeg en modo portable
    Karaoke.bat              Arranca PHP en 0.0.0.0:8123 y abre el navegador
    index.html               La aplicación entera: interfaz, estilos y lógica
    pedir.php                Página ligera para los invitados
    proyector.php            La pantalla del televisor, en otra ventana
    ajustes.php              Configuración desde el navegador
    manifest.webmanifest     Metadatos para instalarla como aplicación
    sw.js                    Service worker: la interfaz carga sin red
    LEEME.md                 Instrucciones para el usuario final
    PROJECT.md               Este documento

    api/config.php           Valores por defecto + lo guardado en ajustes
    api/comun.php            Utilidades compartidas por toda la API
    api/buscar.php           Intermediario con YouTube
    api/estado.php           Biblioteca y cola compartidas
    api/descargar.php        Descarga local con yt-dlp

    data/ajustes.json        Configuración del usuario (NO va a GitHub)
    data/estado.json         Biblioteca y cola (NO va a GitHub)
    data/videos/             Vídeos descargados (NO van a GitHub)

    iconos/                  Icono en SVG y en PNG a dos tamaños

### Flujo de una búsqueda

    navegador                servidor                 Google
       │                        │                        │
       │ GET api/buscar.php?q=  │                        │
       ├───────────────────────>│                        │
       │                        │ search.list + key      │
       │                        ├───────────────────────>│
       │                        │<───────────────────────┤
       │                        │ videos.list (duración) │
       │                        ├───────────────────────>│
       │                        │<───────────────────────┤
       │<───────────────────────┤                        │
       │  JSON normalizado      │                        │
       │  sin la clave dentro   │                        │

La clave nunca sale del servidor. Es lo que permite publicar el proyecto sin
regalarla, y la razón principal de que exista la capa PHP.

Si lo que se escribe en el buscador es un enlace de YouTube o un
identificador de once caracteres, `buscar.php` ni llama a la API: resuelve el
vídeo por oEmbed, que es público y no consume cuota. Por eso la aplicación
sigue siendo útil sin clave configurada.

### Flujo de una petición desde el móvil

    móvil                    servidor                    PC del karaoke
      │                         │                              │
      │ POST estado.php         │                              │
      │ {anadir_cola,invitado}  │                              │
      ├────────────────────────>│                              │
      │                         │ flock + escribe + version++  │
      │<────────────────────────┤                              │
      │                         │<─────────────────────────────┤
      │                         │  GET estado.php (cada 1,5 s) │
      │                         ├─────────────────────────────>│
      │                         │  responde al momento         │
      │                         │  el cliente compara version  │

Cada dispositivo pregunta por el estado cada segundo y medio y suelta la
conexión. Si la versión ha subido, repinta; si no, espera y vuelve.

**Esto era un sondeo largo y hubo que cambiarlo.** La idea original —retener
la respuesta hasta veinte segundos y contestar en cuanto algo cambiara— es
mejor sobre un servidor normal, pero es incompatible con el que trae PHP.
`php -S` atiende **una petición cada vez**, y en Windows no sabe crear
procesos hijo, así que `PHP_CLI_SERVER_WORKERS` no le hace nada. Medido: con
un solo sondeo abierto, pedir otra página tardaba 27 segundos; con el arreglo,
0,03. Con cuatro dispositivos sondeando, el estado se lee en 10–32 ms.

Si algún día esto se sirve con Apache o nginx, el sondeo largo vuelve a ser
la mejor opción y merece la pena recuperarlo.

### Modelo de datos

Una pista es siempre este objeto, en la cola y en la biblioteca:

```json
{
  "id":       "identificador único dentro de la lista",
  "videoId":  "los 11 caracteres de YouTube",
  "title":    "título",
  "channel":  "canal",
  "thumb":    "url de la carátula",
  "duration": 252,
  "local":    "data/videos/xxx.mp4 o null",
  "pedida":   "nombre del invitado o null"
}
```

`data/estado.json` contiene `biblioteca`, `cola`, `sonando` y `version`. El
contador de versión es lo que hace posible el sondeo.

**Toda escritura pasa por `modificar_estado()`**, que abre el archivo, hace
`flock(LOCK_EX)`, aplica la función y lo cierra. Sin ese bloqueo, dos móviles
pidiendo a la vez se pisan y una petición se pierde. No escribas nunca en
`estado.json` por fuera de esa función.

---

## 3. Decisiones de diseño y por qué

**El vídeo se guarda, no la búsqueda.** Es el origen de todo el rediseño. Al
elegir un resultado se almacena su identificador, y reproducir es abrirlo
directamente. Nada se busca dos veces.

**Tres resultados visibles y luego scroll.** Con uno no se compara; con diez
la ventana ocupa la pantalla entera y hay que leer. Tres es lo que se abarca
de un vistazo, y suele bastar para distinguir la versión karaoke de la
original y de la instrumental. Está fijado en CSS con
`max-height: calc(3 * 90px)`, no es casualidad ni aproximación.

**Carátulas siempre.** Identificar el vídeo correcto por el texto del título
es lento y falla; por la miniatura es inmediato.

**Biblioteca y cola son cosas distintas.** La biblioteca es la colección
permanente, lo que se canta todas las noches. La cola es lo que va a sonar
ahora. Confundirlas fue un error de una versión intermedia y costó rehacer la
interfaz.

**La biblioteca arranca plegada.** En una fiesta el foco está en el buscador:
alguien dice un título y hay que teclearlo ya. La biblioteca se reduce a una
estrella de 54 píxeles y se abre al pulsarla.

**360p por defecto.** En un karaoke importa que no se corte, no la
resolución. La imagen se ve de lejos y con la luz apagada.

**El antiparones actúa en escalera, no de golpe.** A los 3 segundos
atascado baja un escalón de calidad; a los 7 reintenta desde la posición
guardada; a los 14 recarga el vídeo en ese segundo a calidad mínima; a los 25
se rinde y pasa a la siguiente. Cada escalón es menos destructivo que el
siguiente. La posición se guarda cada segundo mientras se reproduce, así que
nunca se vuelve al principio.

**El proyector sigue, no manda.** `proyector.php` solo lee el estado: pone lo
que diga `sonando` y no toca la cola. Si mandaran las dos pantallas, se
pelearían por decidir la siguiente canción. El PC conserva los controles.
Como no escribe nada, tampoco abre ninguna puerta nueva a los invitados.

**El PC se calla, pero no se para.** Al abrir el proyector, el reproductor
del PC se silencia solo: con dos pantallas el sonido sale de la tele y si no
se oiría doble. Sigue reproduciendo en silencio a propósito, porque es quien
detecta el final de la canción y encadena la siguiente. Silenciarlo no es
pararlo, y esa diferencia es justo la que hace que funcione.

**Los invitados solo pueden añadir.** `estado.php` comprueba la bandera
`invitado` y rechaza cualquier acción que no sea `anadir_cola`. Un invitado
no puede vaciar la cola, ni reordenarla, ni tocar la biblioteca. Con gente
bebida y un QR en la pared, esto no es paranoia.

**Sin base de datos.** Un archivo JSON con bloqueo aguanta de sobra a diez
personas pidiendo canciones. MySQL sería una dependencia más que instalar y
otra cosa que puede no arrancar el día de la fiesta.

---

## 4. Normas de código

**El idioma del código es el español.** Variables, funciones, comentarios y
mensajes. El proyecto es de un hispanohablante para hispanohablantes y
mezclar idiomas dentro de una misma función es peor que elegir cualquiera de
los dos. Las excepciones son las claves del modelo de datos, que se quedaron
en inglés desde el principio (`videoId`, `title`, `thumb`) y cambiarlas
rompería los archivos ya guardados de la gente.

**Los comentarios explican por qué, no qué.** `// suma uno al contador` sobra.
`// sin este bloqueo dos peticiones simultáneas se pisan` es lo que hace
falta.

**Los mensajes de error dicen qué hacer.** «Error 403» no vale. «Cuota diaria
agotada, vuelve mañana o pega enlaces» sí. `buscar.php` traduce los códigos
de Google a frases accionables; sigue ese patrón.

**Nada de dependencias nuevas** sin discutirlo. La única excepción actual es
el generador de QR, que se pide a un servicio externo y degrada a mostrar la
dirección en texto si no hay internet.

**Escapar siempre lo que venga de fuera.** En JavaScript se usa `esc()`; en
PHP, `htmlspecialchars`. Los títulos de YouTube llevan comillas y símbolos.

**CSS: variables para todo color.** Están en `:root`. No metas colores
literales en las reglas.

**Cada cambio deja la aplicación ejecutable.** No se sube nada a medias.

---

## 5. Trampas conocidas

**El error 153 de YouTube.** Si la página se abre con doble clic —protocolo
`file://`— YouTube se niega a reproducir vídeo incrustado, porque exige un
origen web válido. Falla con cualquier vídeo, y el mensaje no ayuda nada.
La solución es servirla por HTTP; para eso existe `Karaoke.bat`. Es el fallo
que más tiempo ha costado diagnosticar del proyecto entero.

**Los vídeos no incrustables.** Algunos dueños desactivan la reproducción
fuera de YouTube: dan error 101 o 150. La aplicación los marca con ⚠ y pasa
a la siguiente automáticamente. En la búsqueda se pide
`videoEmbeddable=true`, que filtra la mayoría pero no todos.

**La cuota de la API.** El plan gratuito da 10.000 unidades al día y cada
búsqueda cuesta 100, o sea unas 100 búsquedas diarias. Suficiente para una
fiesta, insuficiente para un uso intensivo. Al agotarse, la aplicación sigue
funcionando con enlaces pegados.

**oEmbed no da la duración.** Por eso las pistas resueltas por enlace llegan
con `duration: 0`. No es un fallo.

**`shell_exec` deshabilitado.** Muchas configuraciones de PHP lo desactivan,
y sin él no hay yt-dlp. `descargar.php` lo comprueba y lo dice claramente en
lugar de fallar en silencio.

**El PHP portable viene sin certificados.** Recién descargado de
windows.php.net no trae `cacert.pem` ni `curl.cainfo` configurado, así que
**no puede abrir ninguna dirección https**: ni la API de YouTube, ni oEmbed.
cURL devuelve el error 60 y `traer()` daba un escueto «no he podido contactar
con YouTube», que manda a mirar la wifi. `Preparar.bat` lo instala y lo deja
escrito en `php.ini`; si alguien instala PHP a mano, tiene que hacerlo él.

**`escapeshellarg()` se come los `%` en Windows.** Los sustituye por espacios
para que `cmd` no expanda variables de entorno. La plantilla de salida de
yt-dlp, `%(ext)s`, llegaba como ` (ext)s` y el vídeo se guardaba con un nombre
que luego nadie encontraba: se descargaba bien y la aplicación decía que
había fallado. Por eso el nombre de salida va fijo y se fuerza mp4 con
`--remux-video`. Cuidado al añadir cualquier opción de yt-dlp con `%`.

**`cmd` no encuentra un ejecutable relativo entre comillas.** `"yt-dlp.exe"`
le llega con las comillas dobladas y responde que no lo reconoce, aunque esté
al lado. Por eso `descargar.php` resuelve la ruta completa antes de llamarlo.

**`php.ini-development` mete los avisos dentro del JSON.** Con
`display_errors = On`, cualquier aviso de PHP se imprime antes de la
respuesta y el navegador ya no puede leerla: la aplicación dice que el
servidor no responde bien. `Preparar.bat` usa `php.ini-production`.

---

## 6. Backlog

Ordenado por relación entre lo que aporta y lo que cuesta. Lo de arriba es lo
siguiente que hay que hacer.

**Sincronizar la posición entre dispositivos.** Ahora mismo `sonando` dice
qué canción va, pero no por qué segundo. Guardar la posición permitiría que
la pantalla de proyección siguiera al reproductor con exactitud.

**Descarga en segundo plano.** `descargar.php` bloquea hasta que yt-dlp
termina, lo que con un vídeo largo puede ser un minuto de espera. Debería
lanzar el proceso, devolver el control y que la interfaz consulte el progreso.

**Descargar la cola entera de una vez**, para preparar la fiesta con
antelación y no depender de la wifi.

**Historial de lo cantado**, para no repetir y para saber a la mañana
siguiente qué pasó.

**Votar canciones desde el móvil**, subiendo en la cola las más votadas.

**Traducción al inglés**, en línea con la web de tizzenn, que ya es bilingüe.

**Modo dúo**, marcando canciones que se cantan a dos voces.

---

## 7. Roadmap

**v1.0 — hecho.** Backend PHP con la clave oculta, biblioteca y cola
compartidas, peticiones desde el móvil por QR, PWA instalable, descarga con
yt-dlp, menú de ajustes en el navegador, lanzador para Windows.

**v1.1 — en marcha.** Pantalla de proyección **hecha** (`proyector.php`,
probada con vídeo real por HTTP). Faltan la sincronización de posición y la
descarga en segundo plano. Es la versión que convierte esto en un karaoke de
salón de verdad.

**v1.2.** Historial, votaciones y descarga por lotes.

**v2.0.** Traducción, y decidir si merece la pena empaquetarlo con un PHP
embebido para que no haya que instalar nada. Esa decisión no está tomada.

---

## 8. Cómo incorporarse al proyecto

Arranca `Karaoke.bat`, usa la aplicación diez minutos y lee este documento.
Con eso ya sabes lo que hay.

Para tocar código, `index.html` está dividido en ocho secciones numeradas con
comentarios de cabecera: estado y persistencia, utilidades, búsqueda,
biblioteca y cola, reproducción, modales y atajos, eventos, arranque. Busca
la sección antes de buscar la función.

Si el cambio afecta a los datos compartidos, va en `api/estado.php` y pasa
por `modificar_estado()`. Si afecta a YouTube, va en `api/buscar.php`. Si es
solo interfaz, se queda en `index.html`.

Prueba con dos navegadores abiertos a la vez, uno de ellos en la vista de
móvil, antes de dar nada por bueno. La mitad de los fallos de este proyecto
solo aparecen cuando hay dos dispositivos.

---

## 9. Licencia y créditos

GPLv3, igual que el resto de los proyectos de tizzenn.

Escrito por tizzenn con la ayuda de Claude. La parte del humano fue saber qué
hacía falta y detectar cuándo algo no funcionaba en la práctica; la de la IA,
escribirlo y probarlo. El error 153 lo encontró el humano cantando, que es
como se encuentran los errores de verdad.
