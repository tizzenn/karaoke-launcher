# Ideas que todavía no son código

Aquí se aparcan las ideas para que **no entren a presión**. Estar en esta
lista no significa que se vayan a hacer: significa que están pensadas y
que nadie tiene que volver a explicarlas desde cero.

Orden de arriba abajo = orden previsto. Lo que no está en la lista, no
está previsto.

---

## 1 · PWA de verdad — HECHO, comprobado el 2026-08-03

Instalable, funcionando sin red, a pantalla completa y con su icono.
**Ya estaba hecho por completo**, no quedaba nada por escribir: los tres
`.webmanifest`, sus iconos (192 y 512, con versión `maskable`) y el
enlace `<link rel="manifest">` en cada página, todo verificado a mano hoy
y ya cubierto por la suite automática desde antes de esta sesión ("Cada
superficie tiene su propio manifiesto, y es válido"). Lo único que hizo
falta tocar: los colores `background_color`/`theme_color` de los tres
manifiestos, para que coincidieran con la paleta de Clásico repensada en
la idea 16.

Lo interesante no es el envoltorio: es que **cada superficie puede
instalarse por separado** aunque compartan servidor y código.

    /            el operador       🎤
    /proyector   la tele           📺
    /pedir       los invitados     📱

Tres manifiestos, tres iconos, tres aplicaciones en el cajón del móvil, un
solo proyecto que mantener.

En Android es un botón de «Instalar». En iPhone hay que hacer
Compartir → Añadir a pantalla de inicio, y conviene decirlo con esas
palabras exactas la primera vez.

**Riesgo conocido**: el service worker que ya existe cachea la interfaz. Al
cambiar el nombre del proyecto habrá que cambiar el nombre de la caché o
la gente se quedará con la versión vieja sin enterarse.

## 2 · Música ambiente (antes «Cabina DJ»)

Música mientras no canta nadie. La filosofía es **configurar una vez y
olvidarse**:

    nadie cantando        → suena la lista configurada
    el operador prepara   → la música baja y se apaga
    termina la actuación  → vuelve la música

Fuentes de la primera versión: **lista de YouTube** y **carpeta local**.
Spotify descartado (ver `DECISIONES.md`).

Lo que NO lleva: listas editables, controles avanzados, biblioteca
musical. Es una fuente configurada que mantiene vivo el ambiente.

**Depende de**: el reproductor. Por eso va después de cerrar la
arquitectura y no antes.

## 3 · Temas

No son colores: son personalidades.

    Clásico   lo de ahora, para adultos
    Fiesta    confeti al empezar, destellos, transiciones vivas
    Kids      botones enormes, mensajes siempre positivos, sin QR

**Kids no es Fiesta con más colores.** Cambia el lenguaje entero: en vez de
«Fin de actuación», «¡Lo has hecho genial!». Nunca puntuaciones. Y el
móvil no pinta nada — los niños no organizan la cola, así que fuera
cualquier QR.

Un cumpleaños por la tarde y una cena de amigos por la noche, con el mismo
programa y sin cambiar el flujo de uso.

## 4 · Modo Show — HECHO (agosto 2026)

Un tema más, no un módulo: estética de concurso —azul de plató, dorado de
premio— y un botón que saca en la tele una **carta de reto** para quien va
a salir: «canta con una mano en el corazón», «que toda la sala haga los
coros», «ponte algo encima que no sea tuyo».

Las cartas están en `retos.json`, en dos montones —suave y fuerte— porque
sacar «canta de rodillas» al primer grupo de la noche es la forma más
rápida de que nadie vuelva a coger el micro. Añadir una carta es abrir el
archivo con el Bloc de notas.

El botón **no manda la carta directamente**: la elige y la enseña en el
operador. Se lee, y si no le pega a quien va a salir, se saca otra. Esa
pausa de dos segundos es la diferencia entre un juego y un mal rato.

**Lo que se cayó por el camino: el marcador.** La idea original traía
puntuación de equipos con la regla «los puntos premian el espectáculo,
nunca la voz». La regla era buena y el marcador seguía siendo mala idea: en
cuanto hay un número en pantalla, la sala mira el número. Además
contradecía de frente una promesa escrita en `ajustes.php` —nunca hay
puntuaciones ni clasificaciones— y una promesa que el usuario lee vale más
que una función que a lo mejor se usa una noche. Hay una prueba que impide
que vuelva sin que nadie lo decida.

## 5 · Maestro de ceremonias — HECHO (agosto 2026)

Seis sonidos y cinco frases, en un desplegable de la cabecera. Pulsas,
suena, y ya: **no añade ni un estado**, no escribe en el estado
compartido y no puede cambiar lo que pasa en la fiesta. Si mañana se
borra `js/mc.js`, la aplicación sigue funcionando entera. Hay una prueba
que lee el archivo y falla si empieza a llamar a `KL.comandos`.

**Los sonidos** están en `assets/mc/` y se anuncian en `mc.json`. Para
cambiar uno, se sustituye el .mp3 con el mismo nombre. Si un archivo no
está, su botón no aparece — no hay botones muertos, que en mitad de una
fiesta destruyen la confianza en el panel entero. Seis y no dieciséis:
un panel de dieciséis botones es una mesa de mezclas.

**Las frases no son grabaciones**, y ese fue el cambio de criterio. La
idea original eran cinco frases grabadas; se dicen con la voz del
sistema porque **una grabación no puede decir el nombre de quien canta**,
y «un aplauso para Marta» hace en una sala lo que «un aplauso» no hace.
Ese nombre ya está en el estado. La voz de Windows en español no es
ninguna maravilla, pero se oye una vez cada tres minutos, dice un nombre
propio y cuesta cero archivos. Si algún día alguien graba las cinco con
su voz, se sustituyen sin tocar nada más.

**Lo único que hace fuera de su casa**: agachar la música ambiente
mientras habla, a un cuarto de volumen y en trescientos milisegundos. Un
maestro de ceremonias que compite con la música de fondo no se entiende,
y subir el volumen para taparla es peor.

**Aplausos, redoble, fanfarria, remate, atención y tensión.** Faltan risas
y no las hay: no se pueden sintetizar de forma que no den vergüenza. Si
alguien graba unas, se copian a `assets/mc/` y se añade una línea.

## 6 · Identidad — HECHO (agosto 2026)

`OpenKaraoke Center`, nombre corto `OKC`. Cambiado en las tres pantallas,
los tres manifiestos, los dos `.bat`, los títulos de pestaña y la
documentación.

**Lo que NO se ha cambiado, y es una decisión:** las claves `karaoke_*` de
localStorage. Renombrarlas obliga a una migración que hay que mantener
para siempre, y quien se equivoque le hace perder al usuario el desfase de
la tele, el efecto y la salida de audio — lo más caro de volver a ajustar.
A cambio no gana nadie: eso no lo lee una persona, lo lee el navegador.

Se renombra lo que la gente ve. Lo que solo ve la máquina se queda quieto.
Hay una prueba que carga unas preferencias escritas con las claves viejas
y comprueba que siguen valiendo, para que dentro de un año esto no parezca
un descuido.

El identificador de la PWA no era un problema: es `./index.html`, una
ruta. La caché del service worker sí cambió (`karaoke-v6` → `okc-v7`), y
ahí tampoco hay riesgo porque `activate` borra todas las que no se llamen
como la actual, filtrando por diferencia y no por prefijo.

## 7 · Traducir la aplicación — a partir del 6 de agosto

Que se pueda usar en otro idioma. **No se empieza antes del 6**, pero sí
conviene no cavar más hondo el agujero mientras tanto.

La buena noticia es que la mitad del trabajo está hecha sin querer:
`js/textos.js` ya existe y ya centraliza los textos que cambian con el
tema, con `KL.TEXTOS.de(tema, clave)`. Traducir es la misma idea con una
dimensión más — `de(idioma, tema, clave)` — y no un mecanismo nuevo.

La mala es que ahí dentro solo están los textos de la pantalla del
público. El resto —la interfaz del operador, `ajustes.php`, los mensajes
de error, los `title` de los botones— sigue escrito en el sitio donde se
usa. Sacarlos es el 90 % del trabajo y es puramente mecánico.

**Dos decisiones que conviene tomar antes de escribir nada:**

- **Qué NO se traduce.** Los nombres de los espacios probablemente se
  quedan: «Karaoke» y «Freestyle» ya son palabras internacionales, y
  «Cabina DJ» se entiende. Traducir un nombre propio de la aplicación es
  cómo se pierde la identidad.
- **De dónde sale el idioma.** Si es un ajuste de la fiesta —como el
  tema— o una preferencia del aparato. Mi apuesta: **de la fiesta**, por
  el mismo motivo que el tema. Una tele en inglés y un operador en
  español son dos aplicaciones distintas en la misma habitación.

Mientras tanto, la regla es no empeorarlo: **cualquier texto nuevo que se
escriba a partir de ahora en la pantalla del público va a `textos.js`**,
no a la línea donde se usa. Cada uno que se escriba fuera es uno más que
habrá que ir a buscar.

## 8 · Pendientes de los temas

Salieron de `DISENO.md`, que es un manual de principios y no una lista de
tareas. Aquí es donde tocaba dejarlas.

- **Accesos rápidos de búsqueda en Peques** (Disney, animales, princesas,
  vehículos, Navidad, cumpleaños). Buena idea y **no es un tema**: es un
  perfil de búsqueda, y encaja con los perfiles editables que ya están
  anotados. Meterlo en el CSS de un tema sería el primer sitio donde
  buscarlo dentro de un año y el peor sitio donde ponerlo.
- **Sorpresas cada tantas canciones** (un globo distinto, dos segundos).
  Tiene una trampa: necesita un contador, y un contador de canciones
  cantadas es la primera pieza de una gamificación. Si se hace, sin
  guardar nada.
- **Un animal cruzando la pantalla de espera de Peques cada quince
  segundos.** Mejor que las estrellas flotando y hoy no cabe: el
  contenido de un `::after` no se puede ir cambiando en CSS puro, y meter
  JavaScript para esto convertiría un tema en un módulo.
- **Esconder las acciones peligrosas cuando hay un niño delante.**
  Descartado como decisión de tema —un tema no oculta funciones— y **no
  descartado como idea**: si «Vaciar» y «Borrar descargas» necesitan una
  confirmación más fuerte, se decide para toda la aplicación.
- **Sonidos de interfaz, solo en Peques.** Un pop de juguete al pulsar.
  Ni decidido ni descartado.

## 9 · A qué cola van las peticiones del móvil — REVISADO (2026-08-04)

**Decisión original (2026-08-03):** el invitado no elige cola — sigue
siempre al espacio que el operador tenga activo. Implementado en
`api/estado.php` y `pedir.php` (`pintarEspacio()`), probado sin
regresiones.

**Revisado (2026-08-04):** una fiesta no son dos fiestas selladas — pasa
de música a karaoke y vuelve, es un continuo. El motivo del cambio: el
usuario quería poder ir alimentando ambas colas a la vez ("es posible
que una fiesta tenga música... pase a hacer karaoke... y vuelvan con
música. Es un todo en la práctica"). Se valoró primero un botón
secundario ("añadir también a la otra lista") y se descartó a favor de
un **selector libre**: dos píldoras táctiles (🎤 Karaoke / 🎧 Música
ambiente) arriba del buscador en `pedir.php`, que el invitado puede
tocar para elegir a qué lista pide, independiente del espacio activo del
operador.

Comportamiento por defecto sin tocar nada: igual que antes, sigue al
espacio activo (`espacioElegido` empieza en `null`, `espacioVista()` cae
a `espacioActivo`). En cuanto el invitado toca una píldora, esa elección
manda y se recuerda en `sessionStorage` para esa pestaña — no vuelve a
seguir al activo aunque el operador cambie de espacio, porque ha sido
una elección a propósito. La lista "Lo que viene" y el termómetro
muestran la lista que se está viendo (elegida o activa), no siempre la
activa. La confirmación avisa explícitamente si se ha pedido "para
luego" (espacio distinto del activo ahora mismo), para que no parezca
que la canción va a sonar ya.

Servidor: `api/estado.php` ya no ignora `espacio` en la petición del
invitado — `espacio_valido($in['espacio'] ?? $e['espacio'])`, validado
igual que antes (`karaoke`/`dj` o cae a `karaoke`), nunca texto libre.

**Repercusión en cuota de API: ninguna por diseño.** Una búsqueda cuesta
lo mismo la mande a Karaoke o a Cabina DJ — el espacio es solo la
etiqueta de destino de un vídeo ya buscado. Lo único que puede subir es
el volumen total si la libertad anima a pedir más en general, y eso ya
lo absorben la caché de búsquedas (`api/cache.php`) y el pool de claves
con failover ([[10]]).

Probado en vivo: canción pedida con Cabina DJ activa en el servidor pero
Karaoke elegido a mano en el móvil → quedó guardada en `espacio:"karaoke"`,
confirmado consultando `api/estado.php` directamente.

**Del lado del operador** sigue sin haber selector de «añadir a otra
cola»: `KL.comandos.anadirALaCola(v)` manda al espacio activo de su
consola, y cambiar de cola sigue siendo cambiar de espacio con el botón
de la cabecera — eso no ha cambiado, y no hacía falta para esta decisión.

**Arreglado (2026-08-03), independiente de cuál se elija:** `pintarCola()`
en `pedir.php` pintaba «Lo que viene» con la cola **entera sin filtrar por
espacio** (mezclaba Karaoke y Cabina DJ) mientras el termómetro sí
filtraba por espacio activo. Ahora los dos usan el mismo filtro. Riesgo
cero: no cambia a qué cola va la petición, solo qué se enseña.

**Del lado del operador** no hay hoy ningún selector de «añadir a esta cola
u otra»: `KL.comandos.anadirALaCola(v)` siempre manda al espacio activo de
su consola (`KL.estado.modo`); cambiar de cola es cambiar de espacio con
el botón de la cabecera, que es una acción compartida y visible en las
tres pantallas (MODELO.md §4). Añadir un selector «cola actual / karaoke /
cabina» al añadir manualmente sería una función nueva, no algo que ya
exista a medias.

## 10 · Pool de claves de API de YouTube (aparcada, no empezar sin decidirlo antes)

Propuesta: sustituir la única `youtubeApiKey` de `data/ajustes.json` por
una lista de claves con nombre, activa/inactiva y prioridad, con un
`youtubeKeyManager.js` que sirva siempre la de mayor prioridad que
funcione, marque como agotada la que devuelva `quotaExceeded`, reintente
con la siguiente y las reactive todas al cambiar el día. Pensado también
para que futuras llamadas (Maestro de Ceremonias con IA, etc.) usen el
mismo gestor. Incluye un botón «Probar claves» que valide cada una contra
la API y diga cuál está mal escrita, revocada o sin cuota, para no
descubrirlo a media fiesta.

**Por qué se aparca en vez de decidirse ya:** el modelo de datos actual es
una clave suelta en `ajustes.php`, y esto cambia esa forma de guardarla —
justo el tipo de cambio que `PROJECT.md` §4 pide discutir antes de tocar
código, no después. Encaja con la filosofía del proyecto (nadie tiene que
tocar nada durante la fiesta, un fallo de cuota no para la noche), pero
falta decidir si de verdad hace falta: la mayoría de instalaciones son de
una sola casa con una sola clave, y el caso de uso real («compartir la
aplicación entre varios sitios», «la clave de un bar») no está confirmado
como algo que esté pasando hoy.

**Implementado (2026-08-03), en su versión más simple.** `api/claves.php`
+ `ajustes.php` ya tienen el pool con failover real: 5 filas fijas de
nombre + clave + activa/desactivada, botón «Probar claves», y contador de
usos del día por clave. Probado con una clave real: falla → se marca
agotada → pasa a la siguiente → se recupera al día siguiente. Lo que
sigue pendiente es la interfaz más rica que se pidió después:

**Fila enriquecida por clave — HECHO, 2026-08-03.** Cada fila tiene ya:
número de prioridad, botones ▲▼ para reordenar (intercambian valores
entre filas, sin arrastrar), barra de gasto de hoy contra el tope diario
real (~99 búsquedas, calculado desde las mismas constantes que
`cache.php`, no un 100 inventado) con corte de color en 75%/100%, botón
✕ para vaciar la fila, y un campo «De quién es» distinto del nombre de
la clave. Probado de verdad: guardar, recargar y comprobar que
prioridad, propietario y borrado sobreviven — todo correcto.

## 11 · Wifi blanda / wifi dura — endurecer el permiso de invitado

**El problema (QA-002, auditoría externa del 2026-08-03):** `api/estado.php`
solo restringe una petición a "añadir a la cola" si el propio cliente
manda `invitado:true`. Quien esté en la wifi de la fiesta y sepa mandar
una petición HTTP a mano (no usando `pedir.php`) puede saltarse esa
restricción y ejecutar cualquier acción de operador — vaciar la cola,
cambiar de espacio, lo que sea. Es una decisión de diseño reconocida en
el propio código (`estado.php:26`: "quien esté en la red local ya tiene
el puesto de mando entero"), no un descuido, y decidimos (2026-08-03) no
tocarlo antes del 6 de agosto: requiere saber craftear peticiones HTTP,
encaja con la filosofía "sin cuentas, sin logins", y ya existe
`clave_fiesta` como mitigación parcial.

**Propuesta para más adelante:** un ajuste **wifi blanda / wifi dura**
en Ajustes, **blanda por defecto** (el comportamiento de hoy, sin
fricción para una fiesta entre amigos). *Dura* pediría algo que solo
sepa el operador —reutilizar `clave_ajustes` o una nueva— para cualquier
acción que no sea `anadir_cola`, cerrando exactamente el hueco de
QA-002 sin montar un sistema de cuentas. Encaja con `MODELO.md`: sigue
sin haber cuentas ni perfiles, solo una casilla más para quien monte la
fiesta en una casa compartida o un local en vez de en su propio salón.

---

## Aparcadas sin fecha

- **Sesión / Noche como entidad.** Hoy no tiene ni un dato propio. Existirá
  cuando aparezcan dos o tres que sean claramente de *una* ejecución del
  karaoke: duración, número de actuaciones, un QR temporal.
- **Vaciar el historial al empezar una fiesta.** Hay un síntoma real —hoy
  se ven las canciones de la fiesta anterior— pero «qué significa empezar
  una fiesta» es una decisión de producto sin tomar.
- **Otras fuentes de vídeo**: Vimeo, NAS, Jellyfin. La abstracción de
  fuentes del reproductor ya las admite; no hay prisa.

## 12 · «Cada pantalla es un miembro del equipo» (v1.2, sin tocar código aún)

Surgida de la auditoría UX del 2026-08-03 y la conversación posterior. La
idea que la resume: el operador es el **copiloto** (sugiere, recuerda,
simplifica), la pantalla pública es el **animador** (invita a participar,
mantiene el ambiente), la pantalla del cantante es el **escenario**
(limpia, sin distracciones — esto último ya está hecho, ver `MODELO.md`
§7 bis). No se ha tocado nada de código para esto: son ideas de producto
que necesitan decidirse antes de escribir una línea, y esta semana no es
el momento.

**Botón Empezar, más allá de agrandarlo.** No es solo tamaño: la
propuesta es que domine la barra entera cuando hay algo preparado, del
mismo modo que en modo actuación la consola entera desaparece. Ya se
agrandó el tamaño (2026-08-03); si se quiere llevar más lejos, es una
decisión de layout, no solo de CSS.

**La cola como escaleta, no como lista.** "Ahora / Después / Luego" en
vez de una lista vertical numerada. Cambia cómo se lee la cola, no qué
guarda — encajaría con el modelo de datos actual sin tocarlo, pero es un
rediseño visual real, no un ajuste.

**Tensión narrativa en los textos.** Los mensajes de la pantalla pública
informan («Hay mucha cola», «No hay canciones») y podrían animar en su
lugar («🎉 Tenemos música para un buen rato», «🌱 Todavía queda sitio para
muchas voces»). Parte de esto ya lo hace el termómetro por temas (ver
`config.php` → `termometro_niveles`); la propuesta es llevar ese mismo
criterio a más sitios de la interfaz.

**Un operador que sugiere, no que decide.** Avisos discretos tipo "hace
rato que no entra ninguna canción, ¿enseñas el QR?" o "cuatro baladas
seguidas, puedes alternarlas". Ninguno cambia el estado por su cuenta —
si algún día se hace, tiene que seguir siendo el operador quien pulsa,
nunca la sugerencia sola (misma regla que "una canción no arranca sola").

**Transiciones más teatrales entre canciones — HECHO, 2026-08-04.**
Revisado con más cuidado: la regla "no miente" (`evento.js:151-160`) es
sobre qué pasa si se recarga la página del operador a media canción, no
sobre alargar la pausa en sí — FIN_ACTUACION ya está cubierto por esa
misma comprobación al recargar, así que no había conflicto real que
resolver primero.

Elegido entre las opciones (usuario, 2026-08-04): música de fondo +
efecto visual, las dos.

- **Música ambiente durante la pausa.** `ambiente.js:deberiaSonar()`
  solo sonaba en ESPERA; ahora también suena en FIN_ACTUACION (los
  aplausos), que es el mismo hueco en espíritu — nada preparado
  todavía, el operador mirando a la sala. Se apaga sola en cuanto pasa a
  PREPARADA, con el mismo `revisar()` que ya escuchaba `evento:cambio`,
  sin tocar nada más. Sigue sin sonar en Cabina DJ (ahí la cola ya es la
  música).
- **Confeti de verdad en la pantalla pública.** `#aplausos` ya entraba
  con un fundido (`.escena{animation:entra}`); se añadió un rebote al
  emoji 👏 y 34 piezas de confeti (`js/proyector/escenas.js:lanzarConfeti()`)
  con color, ancho, giro, deriva y tiempo aleatorios cada vez —no un
  patrón que se repite igual las treinta veces que se ve en una fiesta
  larga—, tomando los colores de los tokens del tema activo así que
  cambian solos con Clásico/Fiesta/Peques/Show. Se lanza solo al
  **entrar** en la escena, no en cada sondeo mientras ya está ahí
  (comprobado en vivo). Respeta `prefers-reduced-motion`: ni genera las
  piezas ni anima el rebote.

**Puntuación revisada de la auditoría original (tras esta conversación):**
arquitectura del flujo 9,5 · ergonomía del operador 8,8 · pantalla
pública 8,2 · pantalla de actuación 9,3 · lenguaje y personalidad 9,5.

## 13 · Iconos de importar/exportar (Karaoke y DJ) — HECHO, 2026-08-03

Investigado a fondo antes de tocar nada: `S.library` y `S.queue`
(`interfaz.js:112-113`) ya guardan **la biblioteca y la cola enteras, sin
filtrar por espacio** — cada pista lleva su propio campo `espacio`, y lo
que se ve filtrado por Karaoke/DJ es solo la pantalla, no el dato. Así
que la sospecha original («¿se pierde la cola del otro espacio al
exportar?») no era un problema real: `#bExp` ya exporta las dos colas
juntas desde siempre.

Lo que sí faltaba, y era el pedido de verdad: **iconos**, que no
tenían. Añadidos con el vocabulario ya cerrado del proyecto —
`ic-descargar` para exportar, el mismo icono girado 180° para importar,
sin inventar ningún símbolo nuevo (no hay ninguno de "subir" en
`simbolos.js`)— y un `title` en cada botón aclarando qué incluye
exactamente («las dos colas, las dos») y qué NO toca importar (la cola
se queda como está).

## 14 · Rediseño UX de `pedir.php` — HECHO, 2026-08-03

Decisión tomada: **no** añadir accesos rápidos de búsqueda (Disney, Rap,
Cumpleaños...) — mantienen categorías y condicionan lo que canta la
gente, contra la filosofía de "abrir → buscar → cantar". Implementado
completo, sin tocar ninguna funcionalidad (mismo `api/buscar.php`, mismo
`api/estado.php`, misma cola, mismo termómetro, misma contraseña de
fiesta si la hay): pantalla de bienvenida una sola vez por sesión
(`sessionStorage`), buscador dominante a 56px con botón de 52px,
resultados como tarjetas con aire y botón "🎤 Cantar", confirmación
propia en la página (nada de `alert()` ni modal) que sustituye al
buscador un momento y vuelve con "Buscar otra", mensajes reformulados
("🎵 Buscando canciones…", "😅 No encontramos esa canción. Prueba con
otro artista o una palabra diferente."). Probado de punta a punta en el
navegador: bienvenida → buscar → tarjeta → Cantar → confirmación →
Buscar otra → cola actualizada en vivo. Sin errores de consola.

## 15 · Micro-evento en la pantalla pública al entrar una canción

Idea, no implementada: que el termómetro **lata** (crece 5-10% y vuelve)
con un destello suave recorriendo su barra cuando entra una petición, más
un texto de 2 segundos que varía según el nivel del termómetro («🎤
¡Primera canción! Gracias por romper el hielo» en Empezando, «🔥 ¡Esto no
para!» en Muy animada). Nunca un banner tipo «Nueva canción añadida» —el
objetivo es contagiar, no informar. Sin sonidos, sin confeti en cada
entrada: con 15 canciones en dos minutos cansaría. Encaja con el mismo
principio de "la pantalla pública anima, no informa" de la idea 10 más
arriba, y con el termómetro ya existente (`js/termometro.js`) como única
pieza que cambia, sin banners nuevos.

## 16 · Paletas de color — HECHO, 2026-08-03

**Decisión tomada y aplicada, en dos pasadas.** La primera bajó el ruido
de Clásico (grafito cálido). La segunda cambió algo más de fondo, a
petición explícita: Clásico pasa a tener **un color de personalidad fijo
por tema** (azul acero `#5A8DEE`) en vez de que lo decida el espacio,
igual que ya hacían Fiesta, Peques y Show. Se conserva la distinción
Karaoke/Cabina DJ con un segundo acento (violeta `#9B6BEA` para DJ,
`html[data-tema="clasico"][data-modo="dj"]`), para no perder la señal de
en qué espacio se está sin depender solo de la pestaña de la cabecera.
`temas.css` dice, y sigue valiendo, que **Clásico no lleva partículas,
degradados ni transparencias** — eso no ha cambiado, solo el color.

**Corrección (2026-08-04): "igual que ya hacían Fiesta, Peques y Show"
de arriba no era del todo cierto.** Los tres tenían el bloque
`[data-modo="dj"]` en `temas.css`, pero fijaba exactamente el mismo
valor que el tema ya tenía por defecto — copiado de la estructura de
Clásico sin cambiar los números, así que no diferenciaba nada de verdad.
Arreglado con el mismo criterio que Clásico —un giro de tono manteniendo
el carácter del tema—: Fiesta pasa de rosa (`#ff2fa8`) a violeta
eléctrico (`#7c3fff`) en Cabina DJ; Peques de naranja (`#ff7a00`) a
turquesa (`#1ec9b7`); Show de dorado (`#ffc21a`) a azul de foco
(`#29c4ff`). Probado en vivo comprobando `--ac` calculado por tema y
modo: los cuatro temas cambian de verdad ahora.

**Clásico — hoy es correcto, pero no es "bonito" a propósito.** El verde
de acento (`--ac:#22d97a`) es un verde de "botón de confirmar" de
cualquier aplicación web, no un color elegido por él mismo, y el fondo
(`--bg:#0d0f14`) es un azul-carbón sin matiz, el gris "por defecto" que
sale de no decidir un tinte. Propuesta, manteniendo el mismo contraste y
la misma estructura de variables:

    --bg      #121316   grafito casi neutro, un pelín cálido — no azulado
    --bg2     #1a1b1f
    --bg3     #232428
    --line    #2e2f34
    --txt     #f0efec   blanco cálido, no blanco frío de pantalla
    --txt2    #a8a6a0
    --txt3    #726f68
    --ac (karaoke)   #2ba86f   verde esmeralda profundo, no verde de aviso
    --ac2            #1f7d53
    --ac (dj)        #7c5cd6   violeta apagado, un tono más grave que hoy
    --ac2            #5c3fb0

  La idea: que el karaoke se sienta como un salón bien iluminado por la
  noche, no como el panel de estado de un servidor. El acento sigue
  siendo el único color que se mueve entre espacios — esa regla no
  cambia, solo el tono elegido.

**Fiesta — ya acentúa lo suyo, casi no toca nada.** El neón fucsia/cian
sobre fondo violeta-negro (`temas.css:57-66`) ya está bien razonado ("que
brille y lata, nunca que confunda"). Único ajuste: si Clásico deja de ser
azulado, conviene que el negro de Fiesta (`--bg:#0a0713`) se quede con su
matiz violeta tal cual — es lo que hace que se note el salto de un tema a
otro nada más cambiar.

**Peques — ya acentúa lo suyo, casi no toca nada.** Los colores tipo
juguete (`--k-seguir` verde, `--k-volver` azul, `--k-quitar` rojo,
`--k-info` violeta) y el bisel 3D de los botones ya son el lenguaje
correcto para un niño de siete años. No hace falta un diseñador para
mejorar esto — ya lo tiene.

**Show — ya acentúa lo suyo, casi no toca nada.** Dorado sobre azul
noche con focos radiales (`temas.css:370-386`) ya lee como plató de
concurso. Único matiz: si se toca el dorado alguna vez, que sea sin
tocar `--star`, que se comparte con los otros tres temas para marcar
favoritos — cambiarlo aquí cambiaría también las estrellas de Clásico.

## 17 · Barra de búsqueda: icono pisando una línea, barra demasiado larga

**Campo del filtro — HECHO, 2026-08-04, con captura real.** Era otro
fallo, distinto del de la lupa: con texto escrito en el filtro
(`#filtro`), la estrella de "guardar como perfil" (`#bFiltroGuardar`,
siempre visible) y la X de borrar (`#bFiltroX`, aparece solo con texto)
estaban las dos en `right:4px` — el mismo sitio exacto, una encima de la
otra, y el input no reservaba hueco a la derecha así que el texto largo
llegaba hasta debajo de los botones. Confirmado con la captura del
usuario: solo se veía un icono raro en vez de dos. Arreglado en
`operador.css`: `#bFiltroX{right:4px}` / `#bFiltroGuardar{right:34px}`,
cada uno en su sitio, y `#buscador .fbox input{padding-right:64px}` para
que el texto no llegue hasta ahí. Mismo campo en Karaoke y Cabina DJ —un
solo arreglo cubre los dos. Probado en vivo: cero solape confirmado por
coordenadas reales.

Reportado en vivo (2026-08-03), no verificado aquí con captura porque este
entorno no compone frames de navegador. El icono de la lupa (`.sbox .mg`,
`operador.css:46`) se posiciona con `position:absolute; left:15px`, fijo
sin relación con el ancho real del campo — sospecha principal. El ancho
de `#buscador` tiene `min-width:340px` con `flex:3 1 380px`
(`operador.css:510`) mientras el contenedor `header .sbox` declara
`flex:1 1 320px` (`operador.css:544`) — dos reglas de ancho para la misma
franja que pueden estar entrando en conflicto según el ancho real de la
ventana. Falta: abrirlo en un navegador real, ver a qué anchura pasa y
medir cuánto sobra.

## 18 · Hoja de ruta y "actividad de la sala" — conversación externa (ChatGPT, 2026-08-04)

Notas de una conversación del usuario con ChatGPT
(https://chatgpt.com/share/6a711c26-66a8-83ed-8e2e-ab60e7c0210d), leída y
resumida aquí. Son propuestas externas, no decisiones — se registran para
decidir sobre ellas más adelante, nada de esto se ha tocado en código.

**Diagnóstico de fase.** El proyecto ya no está falto de funciones — el
riesgo ahora es romper una identidad que ya está bastante definida.
Propone **congelar el producto** para v1.2: nada de funcionalidad grande,
solo bugs, pulido UX, estabilización, E2E, mensajes y detalles visuales.

**Lo que considera imprescindible antes de publicar (v1.2):**
1. 🔴 Cerrar los bugs ya conocidos (PHP portable, URL de YouTube,
   `pedir.php` en otro PC, guardar la clave API…) antes de tocar nada más.
2. 🔴 Instalación "a prueba de tontos": Descargar → `Preparar.bat` →
   `Karaoke.bat` → funciona, sin consola, sin editar nada, sin leer
   documentación. Lo marca como el objetivo real de esta fase.
3. 🟡 El flujo (Calentamiento → llegan canciones → operador ordena →
   actuación → pantalla del cantante → fin → operador → siguiente) ya lo
   da por cerrado, no lo tocaría más.
4. 🟡 Ergonomía: tamaños, márgenes, colores, iconos, alineaciones,
   contraste, textos más simples.
5. 🟡 Documentación **visual**, no técnica: primer uso, cómo organizar
   una fiesta, cómo usar Cabina DJ, cómo usar el móvil, cómo conectar la
   pantalla pública.

**Lo que NO metería en v1.2** (todo pasa a v1.3+): director musical,
"energía"/impacto, IA, plugins, AutoDJ inteligente, estadísticas,
comunidad, aprendizaje, mezclas, crossfade inteligente.

**Única excepción que sí introduciría ahora:** dejar la arquitectura
*preparada* sin implementar nada — campos vacíos tipo `"metadata": {}`,
`"plugins": []`, `"profiles": {}` en el modelo de datos, para no necesitar
una migración grande dentro de un año. (Nota de quien registra esto: si
se hace, hay que pasarlo primero por `DECISIONES.md`/`MODELO.md` — el
proyecto ya tiene una regla explícita contra guardar campos "por si
acaso".)

**Hoja de ruta que propone:**
- **v1.2** — "La mejor aplicación de karaoke casero." Nada más.
- **v1.3** — "La mejor aplicación para organizar una fiesta." Perfiles,
  Director Musical, AutoDJ, plugins, metadatos.
- **v2.0** — "El karaoke aprende cómo evoluciona una fiesta." IA,
  impacto, aprendizaje, comunidad, sincronización, estadísticas.

**"Modo Fiesta" — HECHO, 2026-08-04, con matiz.** Esta app no tiene una
pantalla de inicio separada como se imaginaba en la conversación (es un
panel siempre activo, no un menú previo), así que se ha aplicado la idea
al sitio real donde vive: los `title` de `#bCal`, de las dos pestañas de
`#espacios` y de `#bCfg`, con el mismo lenguaje ("🎉 Preparar la fiesta",
"🎤 Empezar karaoke", "🎧 Música ambiente", "⚙ Ajustes"). Solo tooltips,
cero cambio de arquitectura ni de los nombres de espacio que se usan en
el resto de la app y la documentación. Sobre "preparar la arquitectura"
(`metadata`/`plugins`/`profiles` vacíos): descartado — en PHP+JSON un
campo nuevo no exige migración (`?? []` ya es el patrón en todo
`estado.php`), y el proyecto tiene una regla explícita contra guardar
campos "por si acaso" (`DECISIONES.md`).

**"Modo Fiesta" — la única mejora que metería ya en v1.2.** No es un modo
nuevo, es la pantalla de inicio actual con lenguaje más humano: en vez de
"Karaoke / Cabina DJ / Calentar la sala / Ajustes", algo como
"🎉 Preparar la fiesta → 🎤 Empezar karaoke → 🎧 Música ambiente →
⚙ Ajustes". Cambio puramente de palabras, no de arquitectura — candidato
razonable a hacer pronto si se decide.

**"Actividad de la sala" (para v1.3/v2.0, brainstorm, nada que hacer
ahora).** Punto de partida del usuario: en una fiesta nadie aplaude,
canta si acaso, pero hay más ruido cuando la canción tiene energía. La
respuesta de ChatGPT matiza y desarrolla bastante la idea:

- Separar **intensidad musical** (fija, la podría poner una IA o la
  comunidad) de **respuesta de la sala / actividad** (dinámica, se mide
  sola con el tiempo). Una balada puede tener intensidad 1 e impacto 5 si
  todo el mundo la conoce y la canta; una sesión techno puede ser
  intensidad 5 e impacto 1 si nadie la sigue.
- Medir con una **media móvil**, nunca una lectura instantánea (una tos,
  un golpe al micro, no deberían contar). Comparar 30s antes / durante /
  30s después de cada canción, no un número absoluto.
- Señal alternativa sin hardware: cuánto tarda en llegar la siguiente
  petición después de que termina una canción — si entran varias en
  quince segundos, esa canción probablemente animó la sala.
- **Ojo con el micrófono**: si el micro del cantante entra por el mismo
  PC que "escucha" el ambiente, se está midiendo al cantante, no al
  público — solo es fiable si el micro va por una mesa de mezclas aparte.
- **Normalizar por sesión, no en dB absolutos** — una boda y un pub no
  suenan igual, así que compara contra la media de esa misma noche
  (`+9% sobre la media de hoy`), no contra un umbral fijo. Propone una
  calibración de 5 segundos al empezar la fiesta (música parada) para
  fijar el nivel base de esa sesión.
- Renombrar el concepto de "ruido" a **"actividad de la sala"**: hoy se
  mediría con el micrófono, pero mañana podría sumar otras señales
  (peticiones tras la canción, si la gente sigue mandando música…) sin
  cambiar el resto del sistema — el algoritmo trabaja siempre con una
  magnitud abstracta, no atada a una sola fuente.
- Aprendizaje incremental por canción: cada reproducción es un voto
  (`+8, +5, +11, +9…`) que va afinando una media y una confianza
  (`impacto medio +8,3, confianza 96%`) — encajaría con perfiles que ya
  se manejan como una escaleta de energía (`2-3-4-5-5-4`), sin necesitar
  BPM ni Camelot.
- Posible capa comunitaria (v2.5+): cada instalación comparte solo
  `{ song, delta, samples }` agregado y anónimo — nada de audio, nombres
  ni usuarios.
- Aplicado a Cabina DJ específicamente (sin cantante, todo el mundo es
  oyente): el mismo razonamiento vale casi igual, ahí el ruido sí es un
  indicador limpio de si la sala reacciona a la pista, sin el problema
  del micrófono del cantante de por medio.
- **Escalonado que propone:** v1.2 sin ninguna medición · v1.3 registrar
  en silencio la actividad de la sala en Cabina DJ, sin usar el dato para
  nada todavía · v2.0 empezar a usar ese histórico para elegir canciones ·
  v2.5 compartir estadísticas agregadas si existe un servidor comunitario.

Relacionado con [[12]] («cada pantalla un miembro del equipo», que ya
habla de un operador "copiloto") y con la sección 10 de la agrupación de
claves API sobre estadísticas — mismo instinto de "no soy yo quien
decide, es un dato que se acumula solo".

**Segunda vuelta (2026-08-04), tras enseñarle la crítica de arriba:**
ChatGPT retira del todo lo de los campos vacíos ("YAGNI en una
arquitectura JSON sin migraciones tiene mucho sentido") y confirma que
Modo Fiesta es vocabulario, no pantalla — coincide con lo ya hecho.
Propone llevar ese mismo lenguaje "de anfitrión, no de software" más
lejos: "Calentamiento" → "Calentar la sala", "Modo Karaoke" → "Empezar
karaoke", "Cabina DJ" → "Pon música ambiente", etc., en más sitios de la
interfaz (no solo tooltips). Y una idea más grande: dice que v1.2 ya no
necesita arquitectura, necesita una **pasada de dirección artística**
completa — repasar uno por uno nombres, iconos, colores, mensajes,
ayudas, animaciones, tiempos, feedback. Apuntado como concepto para
decidir aparte, no ejecutado — es un lote grande y toca casi todas las
pantallas, no encaja en un rato suelto.

## 19 · Lenguaje visual escénico completo (2026-08-04, especificación sin ejecutar)

Tercera vuelta de la misma conversación externa, después de ver hecho el
punto 12 (confeti + música en los aplausos). Propone no quedarse en una
animación suelta sino resolver el lenguaje de transición de **toda** la
aplicación con una regla rectora: **animar acontecimientos, no
pantallas** — "cambia el modo" no merece animación, "empieza una
actuación" / "entra una canción nueva" / "termina una actuación" sí.
Tres límites que pone el propio origen: cortas (<700ms salvo la
presentación del cantante), suaves, y solo cuando pasa algo importante.
**No implementado — es un rediseño real de seis pantallas, no un ajuste,
y con la fiesta pasado mañana no es el momento** (ver más abajo).

**Para decidir de un vistazo:**

| # | Pieza | Esfuerzo | Utilidad | Riesgo |
|---|-------|:--:|:--:|:--:|
| 1 | Fin de actuación (secuencia en vez de corte) | 🟢 bajo — la mitad ya está (confeti + música ambiente, [[12]]); falta solo el fundido de vídeo y encadenar los tiempos | 🟢 alta — es el momento que más se repite en toda la noche | 🟢 bajo — toca un único punto de transición ya identificado (`evento.js:terminar()`) |
| Extra | "Blackout" teatral (300-500ms) | 🟢 bajo — una capa oscura con fundido, un solo sitio | 🟡 media — se nota, pero es un matiz sobre el punto 1, no algo que se eche en falta si no está | 🟢 bajo — puramente visual, no toca estados |
| 2 | Inicio de actuación ("Preparando escenario…") | 🟡 medio — nueva pantalla intermedia, hay que encajarla en la máquina de estados (LLAMADA o un paso nuevo) sin romper la cuenta atrás que ya existe | 🟡 media — bonito, pero el hueco que resuelve (arranque brusco) es menos molesto que el del punto 1 | 🟡 medio — toca el flujo de estados, no solo el pintado |
| 3 | Pantalla pública: celebración por cada canción nueva pedida | 🟡 medio — varias piezas (destello, fila que entra deslizándose, recolocar la cola) | 🟡 media — refuerza la sensación de fiesta viva, pero es frecuente y hay que vigilar que no canse | 🟡 medio — se ejecuta muy seguido (cada petición), más superficie para que algo desentone |
| 4 | Calentamiento: animación lenta de fondo | 🟡 medio — hay que diseñar un movimiento que no se note como movimiento, eso cuesta más de lo que parece | 🔴 baja — el calentamiento ya funciona bien, esto es pulido puro | 🟢 bajo — pantalla estática hoy, poco que romper |
| 5 | Cabina DJ: transiciones tipo Spotify (sin teatralidad) | 🟡 medio — es una familia de transiciones nueva, distinta de Karaoke, con su propio criterio | 🟡 media — DJ es el espacio menos usado hasta ahora (según lo hablado en la sesión) | 🟢 bajo — más sobrio que lo de Karaoke, menos que pueda ir mal |
| 6 | Karaoke: más teatralidad en general (marco que junta 1+2+3) | 🔴 alto — es la suma de las piezas de arriba, coordinadas | 🟢 alta — es el efecto conjunto que da la sensación de "espectáculo cuidado" | 🟡 medio — cuantas más piezas se combinan, más fácil que alguna quede floja |

Lectura rápida: **1 y el blackout son los candidatos claros** para una
próxima sesión corta — esfuerzo bajo, riesgo bajo, y 1 ya tiene medio
camino andado. El resto (2, 3, 4, 5) son mejoras reales pero de peor
relación esfuerzo/riesgo por separado; 6 no es una pieza suelta, es la
suma de las demás.

**1. Fin de actuación (1-2s).** Secuencia en vez de corte: vídeo se
desvanece (150-250ms) → celebración (el confeti que ya existe, [[12]]) →
suena la música ambiente (ya hecho) → vuelve a espera. "Ha terminado una
actuación", no "se ha cerrado un vídeo".

**2. Inicio de actuación (700-1000ms).** Una pantalla de presentación
tipo "Preparando escenario… 🎤 Ana García · 🎵 Mediterráneo" antes de que
aparezca el vídeo — hoy pasa de lista a vídeo al instante. Le da tiempo
al cantante a mirar la pantalla antes de que empiece a sonar.

**3. Pantalla pública, cada canción nueva.** Destello suave, rebote del
emoji, confeti radial, la fila nueva entra deslizándose, la cola se
recoloca con transición — "un pequeño acontecimiento" cada vez que entra
una petición, no solo al terminar una actuación.

**4. Calentamiento.** Animación muy lenta de fondo para que la pantalla
de espera no se sienta "congelada": respiración del logotipo, degradado
lentísimo, partículas discretas. Nada que se note como movimiento, solo
que evite la sensación de pantalla parada.

**5. Cabina DJ — deliberadamente sin teatralidad.** Aquí no hay
"celebración", hay continuidad — transiciones tipo Spotify: fundido,
cambio de portada, desplazamiento de información. Sin confeti, sin
rebotes. Coherente con por qué la música ambiente no suena en DJ
([[12]]): la cola ES la música, no hay actuaciones que celebrar.

**6. Karaoke — sí más teatralidad.** Cada canción es una actuación:
entrada, celebración, salida. Contraste deliberado con el punto 5.

**Extra: "blackout" teatral entre actuaciones.** Fondo oscuro 300-500ms
entre el fin de una actuación y el inicio de la siguiente, como cuando
se apagan un poco las luces de un escenario. Encaja con el punto 1, sería
el primer paso de esa secuencia.

**Puntos 1 y el blackout — HECHO, 2026-08-04.** Se pidió explícitamente
implementar solo estos dos, los de menor riesgo de la tabla. El resto
(2, 3, 4, 5, 6) sigue sin tocar por el mismo motivo de siempre: la
primera conversación con la misma fuente ([[18]]) decía "congelar el
producto", y con la fiesta encima, repintar las seis pantallas de golpe
no es la apuesta correcta.

Lo construido: en `js/proyector/escenas.js`, al ENTRAR en la escena de
aplausos (no en cada sondeo mientras ya está ahí), se dispara
`destelloBlackout()` — una capa `#blackout` que se pone opaca al
instante (sin transición, para tapar el corte en seco de `parar()`),
aguanta 350ms con la escena de aplausos y el confeti ya montados detrás,
y se desvanece en 300ms más revelándolos. Antes: vídeo→corte→(hasta
1,5s de sondeo)→confeti de golpe. Ahora: vídeo→apagón con intención→
celebración ya lista.

Nuevo ajuste **"Efectos escénicos"** (`ajustes.php`, grupo La fiesta):
un checkbox que apaga el blackout Y el confeti a la vez
(`cfg['efectos_escenicos']`, viaja en `e.efectos` del estado —la tele no
puede leer `ajustes.json`, igual que el termómetro). Por defecto
activado: sin él la pantalla se comporta exactamente como llevaba
haciendo desde el principio, así que "activado" no es un riesgo nuevo,
es añadir un adorno encima de lo de siempre. Respeta
`prefers-reduced-motion` igual que el confeti. Probado en vivo: la
secuencia de tiempos exacta (opaco→350ms→desvanece→700ms→limpio) y que
`efectosOn:false` desactiva las dos cosas sin romper la escena.

## 20 · Guardar y Volver flotantes en Ajustes — HECHO, 2026-08-04

Reportado en vivo: `ajustes.php` pasa de 800 líneas y "Guardar" estaba
al final del todo — encontrarlo significaba bajar toda la página
primero. `.btns` (el bloque con Guardar / Probar las claves / Volver al
karaoke) ahora es `position:sticky; bottom:0`, con fondo propio y sombra
hacia arriba para que se note que flota sobre el contenido, no que es
parte de él. Ancho completo (roto del `max-width:680px` de `.w` con el
truco `calc(50% - 50vw)`) para que no quede una barra flotando a la
mitad de la pantalla en un monitor ancho — el contenido de la barra se
realinea a la misma columna de lectura con padding. `sticky` y no
`fixed` a propósito: solo se pega cuando el resto ya ha pasado, no tapa
nada mientras se sube. Confirmado el CSS computado (`position:sticky`,
`bottom:0px`); la geometría exacta en scroll no se pudo verificar en
este entorno (no compone un viewport real), pero el mecanismo es
sticky nativo del navegador, no JS a mano.

## 21 · Auditoría de arquitectura (ChatGPT, 2026-08-04) — para después de publicar

Auditoría a nivel de estructura (no línea a línea) sobre 14 PHP, 35 JS,
6 CSS — **los tres números comprobados aquí, exactos**. Nota global
8,9/10. Explícitamente dice "esto no ahora, cuando la v1.2 esté
publicada" — nada de lo de aquí cambia el trabajo de hoy o de mañana.

**Lo que destaca a favor:** hay arquitectura de verdad (`api/js/css/
docs/assets/data`, no todo en un `index.php`); el estado centralizado
como decisión ("todo gira alrededor del estado, no de los botones");
mucha documentación (`DECISIONES.md`, `MODELO.md`, `IDEAS.md`);
operador y público como pantallas separadas, no una reutilizada; JSON
en vez de base de datos. Y la frase que mejor resume la sesión entera:
"un proyecto suele crecer añadiendo funciones, un producto madura
quitando complejidad" — encaja con lo que ya dijo la primera auditoría
([[18]]) y con la de dirección artística ([[19]]).

**Riesgos señalados, verificados aquí:**
- `operador.css`: **789 líneas hoy**, propone no pasar de ~1500. No es
  un problema todavía, es una tendencia a vigilar — ha ido creciendo
  cada vez que se ha tocado la interfaz del operador esta sesión.
- `evento.js`: **498 líneas hoy**, "archivo delicado, mantenerlo
  pequeño". Moderado, no descontrolado.
- 35 archivos JS: sugiere organizar por dominios
  (`js/estado/operador/publico/reproductor/biblioteca/util/`) el día
  que cueste recordar dónde vive cada cosa — no ahora.
- Lógica de animación (termómetro, aplausos, confeti, transiciones,
  ambiente) mezclada con lógica de negocio: separar presentación de
  lógica antes de que crezca más — relacionado directo con [[19]].
  Docs: distinguir vigente de histórico, algunos ya quedarán obsoletos.

**La única recomendación concreta que hace, para cuando se publique:**
un `ARQUITECTURA.md` corto (máx. 5 páginas) — módulos, flujo,
responsabilidades, qué nunca debe hacer un módulo, convenciones — no
para el usuario, para que el proyecto no pierda coherencia dentro de un
año. Distinto de `MODELO.md` (que es sobre el producto, no sobre el
código) y de `PROJECT.md` (que ya tiene normas de código pero mezcladas
con el backlog).

**Lo que NO haría, explícito:** no migrar a React/Vue/TypeScript/base
de datos/Electron — "el proyecto funciona precisamente porque es
sencillo". Coincide con la filosofía ya escrita en `CLAUDE.md` ("sin
frameworks, HTML/CSS/JS/PHP a mano").

## 22 · Rotación de turnos por participante (2026-08-04, aplazada)

Propuesta (ChatGPT, relayada por el usuario mid-QA): la cola hoy es
FIFO — si alguien pide tres canciones seguidas, canta tres veces
seguidas y el resto espera mucho. Propone pensar en **turnos, no
canciones**: cada persona tiene su propia sub-cola, y un planificador
construye la cola visible por rondas (todos cantan una vez antes de que
nadie repita). Tres modos configurables en Ajustes: FIFO (el de hoy),
Rotación por participante (recomendado como nuevo por defecto), Solo el
operador ordena. Si alguien ya tiene actuación pendiente y pide otra,
el mensaje de confirmación explicaría por qué no va justo detrás
("la hemos colocado después de que canten los demás, para que todos
tengan su turno") en vez de "añadida al final", para que la espera se
sienta justa y no como un fallo.

**Aplazada, no ejecutada.** Es una idea sólida — la rotación por
cantante es práctica habitual en karaokes de verdad — pero es un
cambio en el orden real de la cola, y prácticamente todo el motor
depende de que ese orden ya sea el de reproducción: `evento.js`
(`pistaTrasId()`, `cuentaAtras()` decidiendo "la siguiente"), el
arrastre manual de la cola, y la escaleta "Ahora/Después/Luego" de
hoy mismo ([[10]]) que pinta la cola asumiendo que el orden guardado
es el orden real. Cambiarlo en mitad de una QA para estabilizar antes
de una fiesta pasado mañana es justo el tipo de cambio con más
probabilidad de esconder un bug sutil hasta que ya esté sonando
delante de la gente. Retomar con calma después de la fiesta.
