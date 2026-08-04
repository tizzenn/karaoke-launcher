# Decisiones tomadas, y por qué

Este archivo existe para no volver a discutir lo mismo dentro de un año.

Cada entrada dice **qué se decidió**, **cuándo** y **por qué**. Una
decisión se puede cambiar —el mundo cambia— pero hay que traer un motivo
nuevo, no el mismo argumento de la vez anterior.

El formato es a propósito aburrido. Un documento bonito no se actualiza.

---

## Lo que nunca va a haber

La lista corta, para leer de un vistazo. Cada una está razonada más abajo.

    · chat entre invitados
    · perfiles ni cuentas
    · votaciones ni «me gusta»
    · rankings ni clasificaciones
    · puntuación de cómo canta nadie
    · nube obligatoria
    · anuncios
    · IA metida donde no hace falta
    · gamificación permanente

Todo eso tiene algo en común: **pone el móvil en el centro de la fiesta**.
Y el centro de la fiesta es la gente.

El filtro para cualquier idea nueva, incluidas las que parezcan
inofensivas:

> ¿Esto hace que organizar una fiesta sea más sencillo, o solo añade otra
> opción?

Si es lo segundo, no entra.

---

## No hay votaciones, ni «me gusta», ni rankings

**Agosto 2026.** Se propuso que los invitados pudieran votar las canciones
de la cola para subirlas o bajarlas.

**No.** Tres motivos, por orden de peso:

1. Obliga a la gente a mirar el móvil durante la fiesta, que es justo lo
   contrario de lo que busca el proyecto. El móvil sirve para **aportar
   contenido**, no para dirigir la fiesta.
2. Convierte una reunión en una red social pequeña, con sus ganadores y
   sus perdedores. En una casa eso no acaba bien.
3. El operador ya puede reordenar arrastrando, que es más rápido y no
   necesita que nadie se entere.

## No hay puntuaciones de cómo canta nadie

**Agosto 2026.** Ni notas, ni estrellas, ni «has afinado un 72 %».

Si gana el que mejor canta, el resto deja de participar — y el resto es
casi todo el mundo. En un karaoke de casa el objetivo es que se anime la
gente que canta mal, que son los que hacen la fiesta.

Vale también para el futuro modo Show: los puntos, si algún día los hay,
premian el espectáculo (salir disfrazado, que cante el grupo entero), no
la voz.

## No hay entidad Cantante

**Agosto 2026.** Lo único que existe es un nombre escrito a mano al pedir
una canción. Es un **valor de la actuación**, no una persona.

Modelarlo como entidad obligaría a tener personas, y en una fiesta en casa
nadie se registra. Está razonado entero en `DOMINIO.md`.

## No se guarda la posición en la cola

**Agosto 2026.** La posición es el orden del array y de nadie más.

Un campo `posicion` crea dos verdades y una se desincroniza el primer día
que dos móviles reordenan a la vez. Por eso reordenar manda **la lista
entera** y no «mueve de A a B».

## La hora la pone siempre el servidor

**Agosto 2026.** El reloj del PC del karaoke y el del móvil de un invitado
no coinciden, y un historial ordenado por la hora del móvil sale mal.

## Una canción no arranca sola

**Julio 2026.** La regla que no se rompe. Al terminar una, la siguiente
queda PREPARADA y ahí se queda hasta que el operador lo diga.

La gente no está mirando la pantalla. Una canción que empieza sola pilla a
alguien desprevenido delante de todos.

Hay una prueba con nombre propio —«AL TERMINAR NO ARRANCA SOLA»— y espera
tres segundos a propósito para comprobar que sigue quieta.

## No hay cuenta atrás por defecto

**Agosto 2026.** Un 3-2-1 en la pantalla le dice al cantante «empieza justo
cuando esto llegue a cero», y casi nadie está listo: sigue cogiendo el
micro o esperando a que la gente se calle. Ese agobio no lo pone la
canción, lo pone el contador.

Sigue configurable para quien la quiera.

## No se puede pausar mientras se canta

**Agosto 2026.** Pausaba la copia de **este** ordenador, no la de la tele.
Las dos pantallas llevan su propio vídeo —no se envía señal, se reproduce
lo mismo dos veces— así que pausar aquí las descuadraba y no volvían a
juntarse solas.

Para salir están Terminar y el botón de pánico, que pasan por el estado y
paran las dos a la vez.

## No hay juegos, quiz ni bingo dentro del karaoke

**Agosto 2026.** Acaban siendo mediocres comparados con las aplicaciones
que solo hacen eso, y engordan el programa para algo que se usa cinco
minutos.

El **modo Show** sí se contempla, porque no toca el núcleo: son cartas en
un archivo de datos, dos equipos y un marcador que suma el operador a
mano.

## Spotify no

**Agosto 2026.** Su API no permite reproducir libremente desde una página
web: exige autenticación y cuenta Premium, y el flujo es bastante más
complicado. Mucha complejidad para lo que aporta.

La Cabina DJ empieza con **lista de YouTube** y **carpeta local**. Otras
fuentes —Jellyfin, NAS— caben en la arquitectura sin rediseñar nada.

## El streaming es otro proyecto

**Agosto 2026.** Un módulo de Twitch/YouTube Live no reutiliza casi nada
del karaoke y cambia la identidad del programa: se pasa de música
participativa a consumo de contenido.

Vive aparte, con la misma estética y la misma filosofía, pero aparte.

## Sin frameworks, sin npm, sin compilar

**Julio 2026.** El objetivo es que cualquiera arranque la aplicación con
doble clic en el PC de su casa, antes de una fiesta, sin instalar nada.

Todo lo que meta un paso previo entre «descargar» y «funciona» está en
contra de eso.

## Las pruebas son una página web

**Agosto 2026.** Unas pruebas que exigen Node, npm o Python son unas
pruebas que el dueño del proyecto no puede ejecutar en su ordenador. Y
unas pruebas que no se ejecutan no existen: se pudren en tres meses.

Los guiones de Playwright en `pruebas/e2e/` son opcionales y cubren lo que
un marco no puede: pantalla completa, dos ventanas, leer un QR de verdad.

## `S` y `toast` siguen siendo globales, a propósito

**Agosto 2026.** Se usan 186 y 38 veces. Son el vocabulario del proyecto,
como `$` en las páginas con jQuery. Escribir `KL.interfaz.toast(...)`
doscientas cuarenta veces no desacopla nada: solo hace el texto más largo.

Lo que sí importa es que **la lista esté cerrada y enumerada**, y que una
prueba falle si aparece uno nuevo sin que nadie lo haya decidido.

## El nombre cambia; las claves de localStorage, no

**Agosto 2026.** La aplicación pasa a llamarse **OpenKaraoke Center**. Las
ocho claves `karaoke_*` de localStorage siguen llamándose igual.

La regla que sale de aquí y vale para lo próximo: **se renombra lo que lee
una persona; lo que solo lee la máquina se queda quieto.** Un identificador
interno bonito no le sirve a nadie, y el precio de equivocarse en la
migración lo paga el usuario perdiendo ajustes que costó una tarde afinar.

Hay una prueba que escribe unas preferencias con la clave vieja, recarga la
aplicación y comprueba que las ha leído. Está para que el día que alguien
vea `karaoke_launcher_v1` dentro de un proyecto llamado OKC y le parezca un
descuido, lo cambie y se entere en el acto.

## El Modo Show no lleva marcador

**Agosto 2026.** El Modo Show iba a traer puntuación por equipos, con la
regla «los puntos premian el espectáculo, nunca la voz». Se escribió a
medias y se quitó antes de terminarlo.

La regla era correcta —nadie recibía una nota por su forma de cantar— pero
no era el punto. El punto es que **en cuanto hay un número en pantalla, la
sala mira el número**. Lo que se quería del Show era que la gente se
lanzara, y eso lo hacen las cartas de reto sin que nadie pierda.

Y había una razón más dura: `ajustes.php` le promete al usuario, con esas
palabras, que nunca hay puntuaciones ni clasificaciones en ningún tema. Una
promesa escrita en la pantalla vale más que una función que a lo mejor se
usa una noche. Si algún día se reabre, lo primero es cambiar ese texto —y
esa es exactamente la conversación que hay que tener antes de escribir
código.

Hay una prueba que falla si el estado del Show crece con algo que no sea la
carta, o si aparecen las palabras «equipos», «puntos», «marcador» o
«ranking».

## «Cabina DJ» significaba dos cosas

**Agosto 2026.** La misma palabra nombraba dos conceptos incompatibles:

1. La música de fondo que suena entre actuaciones de karaoke y se aparta
   cuando alguien va a cantar.
2. Un espacio: otra fiesta, una en la que **nadie canta** y la lista la
   hace la gente desde el móvil.

Quien leía «Cabina DJ» esperaba un VirtualDJ y encontraba un control de
volumen automático. Y al revés: quien quería montar una fiesta sin karaoke
no sabía que eso ya existía.

**Lo primero pasa a llamarse Música ambiente**, y es una herramienta del
karaoke, no un espacio. **La Cabina DJ es el espacio**, que es lo que de
verdad merece ese nombre.

Y la separación no es solo de vocabulario, porque las dos cosas se
comportan al revés:

- La música ambiente **no suena en la Cabina DJ**. Allí la cola ES la
  música, y un hilo por debajo serían dos cosas sonando a la vez.
- En la Cabina DJ **la siguiente canción arranca sola**. «Una canción no
  arranca sola» es la regla del proyecto, pero no es una regla sobre
  reproductores: es una regla sobre personas, y existe para que nadie se
  vea empujado a un micro antes de estar listo. Donde no hay micro, esa
  misma regla produce el fallo que quería evitar — treinta segundos de
  silencio que sientan a toda la sala.

Hay dos pruebas que dicen lo contrario la una de la otra a propósito. Si
alguien «unifica» esto algún día, no pueden estar las dos en verde.

Los nombres internos (`dj`, `ambiente`, `ambienteOn`) no se han tocado: se
renombra lo que lee una persona.
