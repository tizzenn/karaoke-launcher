# Decisiones tomadas, y por qué

Este archivo existe para no volver a discutir lo mismo dentro de un año.

Cada entrada dice **qué se decidió**, **cuándo** y **por qué**. Una
decisión se puede cambiar —el mundo cambia— pero hay que traer un motivo
nuevo, no el mismo argumento de la vez anterior.

El formato es a propósito aburrido. Un documento bonito no se actualiza.

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
