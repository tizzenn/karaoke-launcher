# Modelo conceptual de OpenKaraoke Center

**Versión 1.1 · Agosto 2026**

Este documento define **el significado de los conceptos fundamentales** del
proyecto. Solo cambia mediante una decisión explícita de producto.

No explica cómo funciona el código: explica qué quieren decir las palabras
que usa. Existe porque casi todos los fallos caros de esta aplicación no
fueron errores de programación — fueron dos cosas distintas llamadas igual,
o el mismo dato guardado en dos sitios.

Si un cambio futuro contradice algo de aquí, no es un cambio: es una
decisión de producto, y hay que tomarla a la vista antes de escribir una
línea.

---

## 0 · Para qué existe esto

OpenKaraoke Center existe para **facilitar una fiesta presencial**. Gente en
una habitación, un aparato conectado a la tele y una noche que sale bien.

Cada función nueva tiene que mejorar la experiencia de alguien concreto: el
anfitrión, quien canta o quien mira. Una idea que añade complejidad sin
mejorar claramente una fiesta no pertenece al proyecto, por buena que sea
como idea.

Y ojo con la pregunta que se hace: no es «¿simplifica?». Es **«¿hace mejor
una fiesta?»**. El Maestro de ceremonias no simplifica nada — añade cinco
frases grabadas que antes no estaban— y aun así entra, porque una sala en la
que alguien anuncia «un aplauso para Marta» es mejor sala. La complejidad no
es el enemigo: lo es la complejidad que no se nota en la fiesta.

---

## 1 · Espacio

Un espacio es **una manera distinta de usar la música en una fiesta**. No es
un filtro ni una pestaña: es otro sitio de trabajo.

| | Karaoke | Cabina DJ |
|---|---|---|
| ¿Alguien canta? | Sí, uno cada vez | No |
| ¿Hay actuaciones? | Sí | **No** |
| La cola es… | de actuaciones | la sesión musical |
| ¿Arranca sola? | **Nunca** | **Siempre** |
| Se busca… | lo que diga el filtro | lo que diga el filtro |

**Cada espacio tiene su propia cola.** No es la misma lista filtrada: son
dos listas.

### Por qué se quitó Freestyle

Fueron tres. El tercero, **Freestyle**, se quitó en la v1.2 — y el motivo
está en esta misma tabla, que es lo interesante.

Freestyle se diferenciaba de Karaoke en **una sola casilla**: la última.
Alguien cantaba, había actuaciones, no arrancaba sola. Lo único suyo era
que le pegaba «instrumental» a la búsqueda en vez de «karaoke».

> **Si dos espacios solo se distinguen por lo que buscan, uno de los dos
> no es un espacio: es un filtro.**

Y así se resolvió: el filtro salió a la barra, a la vista, editable y con
perfiles. Lo único que Freestyle sabía hacer se hace ahora desde Karaoke
escribiendo una palabra — y además se puede hacer mucho más, porque el
campo entiende `OR` y excluir.

La prueba de fuego para un espacio nuevo, a partir de aquí: **¿se
comporta distinto, o solo busca distinto?** Si es lo segundo, es un
perfil de búsqueda y se resuelve en una línea.

**Y cada espacio conserva su propio contexto de trabajo. Cambiar de espacio
nunca hace perder lo que estabas haciendo.** Hoy eso significa la cola y el
sufijo de búsqueda. Mañana significará también el filtro, el orden, lo que
habías escrito en el buscador y lo que tenías seleccionado. Ir a la Cabina
DJ a poner una canción y volver al Karaoke tiene que devolverte la mesa como
la dejaste.

### La cola no contiene canciones

> **La unidad de la cola es una ACTUACIÓN, no una canción. Dos personas
> cantando lo mismo no son un duplicado: son dos momentos distintos de la
> fiesta.**

La pregunta que hay que hacerse nunca es «¿esta canción ya está en la
cola?». Es **«¿esta persona ya va a cantar esta canción?»**. Con esa
pregunta, los casos salen solos:

| | |
|---|---|
| Ana añade «Color Esperanza» | ✓ |
| Ana vuelve a añadirla | Se para, y sin regañar: es un doble clic |
| Pedro añade la misma | ✓ Sin más |
| Cinco personas la eligen | ✓ Cinco actuaciones |

Y sirve de brújula para lo que venga: **si una función nueva está
pensando en canciones y no en personas, probablemente va en la dirección
equivocada.** Esto organiza gente que quiere participar; la música es el
medio.

Por eso tampoco se dice «repetida». Una canción elegida por tres
personas no está repetida: está **compartida**.

### La diferencia que NO se debe unificar

> **En el karaoke una canción no arranca sola. En la Cabina DJ sí.**

Parece una incoherencia y es lo contrario. «Una canción no arranca sola» no
es una regla sobre reproductores: es una regla **sobre personas**. Existe
para que nadie se vea empujado a un micro antes de estar listo, y el
operador es quien mira si lo está.

En la Cabina DJ no hay micro ni hay nadie esperando: hay una lista que la
gente ha hecho desde el móvil. Ahí esa misma regla produce exactamente el
fallo que quiere evitar — treinta segundos de silencio que sientan a toda la
sala.

Hay dos pruebas que afirman lo contrario la una de la otra a propósito. Si
algún día alguien «simplifica» esto, no pueden estar las dos en verde.

---

## 2 · Tema

Un tema es **cómo se ve y cómo habla** la aplicación.

**Un tema adapta la aplicación al contexto de la fiesta, no al gusto
personal del operador.** Esa frase resuelve sola la pregunta de por qué
Peques cambia los textos: no es una decoración para quien maneja el aparato,
es que en un cumpleaños de siete años «Fin de actuación» está mal escrito.

Lo que un tema **nunca** cambia es el flujo: buscar, elegir, añadir, cantar.
Un tema que además cambiara cómo se usa serían dos aplicaciones disfrazadas
de una.

**El tema es de la fiesta, no del aparato.** Vive en `data/ajustes.json` y
la tele se pone igual sola. Un tema por pantalla sería una fiesta con dos
personalidades.

Regla de color: en **Clásico** manda el espacio (verde, morado, naranja). En
**Fiesta**, **Peques** y **Show** manda el tema, y el espacio solo cambia
qué se busca. Un tema que se descoloca al cambiar de espacio no es un tema:
es un accidente.

---

## 3 · Herramienta

Una herramienta es **algo que se usa dentro de un espacio**. No es un
espacio, aunque tenga botón propio.

> **Una herramienta nunca cambia el comportamiento fundamental del espacio
> en el que vive. Solo añade capacidades.**

Esa regla es la que impide que dentro del Karaoke aparezca algún día una
herramienta que altere la lógica de turnos —un «modo automático», un
«encadenar sin parar»— y deje la regla del micro rota por una puerta
lateral. Si una supuesta herramienta cambia cómo funciona el espacio, no es
una herramienta: es otro espacio, y hay que decidirlo como tal.

Las que hay:

- **Música ambiente** — el hilo de fondo del karaoke. Suena en los huecos,
  se aparta cuando alguien va a cantar y vuelve al terminar. **No suena en
  la Cabina DJ**, porque allí la cola *es* la música.
- **Cartas de reto** — solo con el tema Show.
- **Calentamiento** — los cartelones de antes de empezar.
- **QR de peticiones** — para que pidan desde el móvil.

---

## 4 · Una fiesta es una sola

Este es el capítulo del que dependen todos los demás.

**La fiesta es una entidad única.** Tiene un tema, un espacio activo, una
cola por espacio, una pantalla pública y unos participantes.

**Los dispositivos no tienen fiestas distintas: todos participan en la
misma.** El PC del salón, la tele, el móvil del anfitrión y los seis móviles
de los invitados son ventanas a lo mismo, no copias que haya que mantener de
acuerdo.

De ahí sale todo lo que parece complicado y no lo es:

- Por qué el espacio se comparte en vez de ser una preferencia. Si el
  operador está en la Cabina DJ y la tele enseña la cola del Karaoke, están
  contando cosas distintas delante de la gente.
- Por qué el tema viaja con el estado y la tele se viste sola.
- Por qué una ventana nueva se reconstruye entera con solo leer el estado,
  sin necesitar ningún mensaje anterior. No se está poniendo al día: se está
  asomando.
- Por qué no hay mensajes entre ventanas. No hacen falta: hay un sitio donde
  está lo que pasa, y todo el mundo lo lee.

Lo que **sí** es de cada aparato es cómo ese aparato enseña la fiesta —el
tamaño de la ventana, por dónde sale el sonido— y nada más. Ver el apartado
siguiente.

---

## 5 · Qué es estado compartido y qué es preferencia

> **Todo lo que describe el estado actual de la fiesta pertenece al estado
> compartido.**

Esa es la regla, y sirve también para lo que todavía no existe: el día que
haya BPM, tonalidad, escena o nivel de energía, ya se sabe dónde van. Si
describe cómo está la fiesta ahora mismo, es compartido. Sin discusión.

La pregunta práctica que lo decide: **¿si esto cambia, tiene que cambiar en
las tres pantallas?**

**Estado compartido** — `data/estado.json`, lo ven todos:
la cola de cada espacio · la biblioteca · el historial · el evento (qué se
está cantando y en qué punto del ciclo) · en qué espacio está la fiesta · el
calentamiento · los cartelones · la carta de reto del Show.

**Ajuste de la fiesta** — `data/ajustes.json`, lo pone el dueño una vez:
la clave de la API · el tema · la música ambiente · las peticiones · la
clave de la fiesta · yt-dlp.

**Preferencia del aparato** — `localStorage`, no se comparte:
la calidad de vídeo · la vista (completa/compacta/mini) · por dónde sale el
sonido · el efecto de la tele · la calibración de pantalla · si la música
ambiente está encendida en *este* aparato.

Las claves de `localStorage` se llaman `karaoke_*` y **no se renombran**
aunque la aplicación se llame OKC: se renombra lo que lee una persona; lo
que solo lee la máquina se queda quieto.

---

## 6 · Lo que se puede deducir NO se guarda

> **Si dos datos pueden discrepar, solo uno de ellos puede existir.**

Esa frase resume media aplicación.

| Dato | De dónde sale |
|---|---|
| qué se está cantando (`curId`) | `evento.pistaId` |
| `sonando` | el evento está en INTERPRETACION |
| la cola de un espacio | filtrar la cola por `espacio` |
| si una canción está **descargada** | **existe el archivo en `data/videos/`** |

El último costó dos fallos seguidos, y el segundo con más daño: había un
campo `local` en el JSON y un archivo en el disco diciendo cosas distintas.
El operador veía «descargada», pulsaba para borrar y el servidor le
contestaba que no existe.

**Dos fuentes para el mismo dato acaban discrepando siempre.** No es
cuestión de cuidado: es cuestión de tiempo.

---

## 7 · El reloj lo marca el reproductor maestro

El **reproductor maestro** es el que da el sonido de la sala. Hoy es el de
la pantalla del cantante; en una versión futura con la tele como núcleo será
otro, y la regla seguirá valiendo igual porque no habla de pantallas: habla
de quién manda el tiempo.

El operador pulsa **una vez**, en un solo botón.

1. El reproductor maestro empieza a sonar de verdad.
2. En ese momento publica **el instante en que estaba en el segundo cero**.
3. Las demás pantallas calculan por dónde va la canción y **saltan ahí**.
4. Cada pocos segundos se vuelve a comprobar y se corrige si el desvío se
   nota (más de 0,35 s).

Lo que **no** se hace, y no se va a volver a hacer: intentar que dos
pantallas arranquen a la vez. Es imposible entre dos reproductores de
YouTube, y compensarlo con un número fijo es apuntar a un blanco que se
mueve.

La «calibración de pantalla» escondida en Ajustes sirve solo para lo que
ocurre **fuera** del programa: que una tele tarde en pintar lo que el
navegador ya ha dibujado, o que el audio pase por un receptor. En el caso
normal vale cero.

---

## 7 bis · Modo actuación

El caso real no es «tres pantallas». Es este, y hay que decirlo con
números porque cambia el diseño:

> **En ocho de cada diez fiestas hay un portátil conectado a la tele. El
> operador está delante del portátil. El cantante se acerca al mismo
> portátil a leer. El público mira la tele.**

Operador y cantante **comparten el hardware, pero nunca al mismo tiempo**.
Y ahí está la decisión: dos funciones que nunca coinciden **no necesitan
compartir interfaz**. Una consola híbrida —con la letra grande *y* la cola
*y* el buscador— hace las dos cosas mal.

**Modo actuación es un estado de la aplicación, no una pantalla.** No es
una vista más junto a completa, compacta y mini: es que el puesto de mando
deja de estar. Mientras dura:

- el vídeo ocupa la ventana entera, con el texto lo más grande posible;
- no queda ningún control salvo la salida;
- la vista que hubiera antes no se guarda ni se restaura, porque **no
  cambia**: sencillamente no se mira. Al terminar reaparece porque nunca
  se fue.

Guardar «qué vista tenía antes» sería un segundo estado que puede
discrepar del primero. Ya sabemos cómo acaba eso (§6).

**Se entra y se sale solo.** El operador pulsa Empezar y el portátil se
convierte en monitor de karaoke; la actuación termina y vuelve la consola.
No hay que acordarse de cerrar nada. La aplicación sabe en qué momento de
la fiesta estamos, y eso vale más que un botón de más.

**«Volver al operador» es un botón de emergencia**: hay que buscar otra
canción, el cantante se ha equivocado, alguien pide otra cosa. En una
noche normal no se toca — y aun así tiene que verse sin buscarlo, porque
el iframe de YouTube se traga el ratón y no puede depender de ningún
gesto. Estuvo al 42 % de opacidad, gris sobre negro, y cumplía la letra de
esta regla sin cumplir su intención.

---

## 8 · Una acción, un camino

Si hay dos maneras de hacer lo mismo, sobra una. Y su corolario, que costó
más caro:

> **Un concepto tiene un único nombre.**

«Cabina DJ» significó a la vez la música de fondo del karaoke y una fiesta
sin karaoke. Quien lo leía esperaba un VirtualDJ y encontraba un control de
volumen automático. Una palabra con dos significados no confunde solo al
usuario: confunde al que escribe el código, y acaba en dos comportamientos
peleándose dentro de la misma función.

Ya aplicado en:

- Un solo botón de **Empezar**.
- Una sola fuente de estado.
- Un solo camino para cargar un vídeo (siempre `cue`, luego `play`).
- Un solo archivo que sabe el formato del mensaje (`comandos.js`).
- Un solo sitio donde se decide qué se ve en cada estado (`estados.js`).
- **Música ambiente** y **Cabina DJ**, cada una con su nombre.

---

## 9 · Lo que nunca va a haber

Está en `DECISIONES.md` con sus motivos y sus fechas: chat, perfiles,
votaciones, rankings, **puntuaciones**, nube obligatoria, anuncios, IA donde
no hace falta, gamificación.

El filtro para cualquier idea nueva es el del principio:

> **¿Hace mejor una fiesta?**
>
> Sí → entra. No → no entra, por buena que sea la idea.
