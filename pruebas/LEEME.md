# Pruebas de OpenKaraoke Center

## Lo único que necesitas saber

Arranca `Karaoke.bat` y abre esto en el navegador:

    http://localhost:8123/pruebas/pruebas.php

Pulsa **Ejecutar todas**. Tarda medio minuto. Tienen que salir todas en
verde antes de publicar nada.

**No toca tu biblioteca ni tu cola.** Todo se ejecuta sobre
`data/pruebas.json`, un archivo aparte que puedes borrar cuando quieras.

---

## Por qué las pruebas son una página web

Por la misma razón que el resto del proyecto no usa frameworks: **tienen
que poder ejecutarse sin instalar nada**.

Unas pruebas que exigen Node, o npm, o Python, son unas pruebas que el
dueño del proyecto no puede ejecutar en su propio ordenador. Y unas
pruebas que no se ejecutan no existen: se pudren en tres meses y a partir
de ahí solo dan ruido.

Así que la suite abre la aplicación **de verdad** dentro de un marco y la
maneja desde fuera. No es una imitación: es el mismo motor de estados, el
mismo almacén y el mismo servidor PHP que usa la fiesta.

---

## Qué cubren

87 pruebas repartidas en veintidós grupos, más los invariantes que se
comprueban después de cada una.

Y cada prueba lleva una **clase**, que es lo que hay que mirar primero
cuando algo se pone en rojo — porque para la mitad de ellas «arreglar el
código hasta que pase» es la reacción equivocada:

| Clase | Qué protege | Si falla |
|---|---|---|
| **arquitectura** | quién sabe de quién, qué se deduce | Casi nunca se baja la exigencia: mira qué se ha metido donde no era |
| **comportamiento** | lo que la aplicación hace | El caso normal: hay un fallo y se arregla |
| **compatibilidad** | que lo guardado antes siga valiendo | Alguien acaba de romperle los datos a quien ya lo usaba |
| **producto** | decisiones sobre qué aplicación queremos | El código puede estar bien. Es una conversación, no un fallo |
| **rendimiento** | números, no impresiones | La respuesta casi nunca es subir el número |
| **regresión** | un fallo concreto que ya pasó | Ha vuelto. Lee el comentario: dice cómo se manifestaba |

La leyenda completa está desplegable en la propia página.

**El ciclo de una actuación** — que preparar no arranca, que la cuenta
atrás lleva a la reproducción, que Esc la cancela, que la canción cantada
sale de la cola y entra en el historial, que el pánico para todo, y que
las transiciones prohibidas se rechazan.

Una de ellas merece nombre propio: **«AL TERMINAR NO ARRANCA SOLA»**. Es
la regla que no se rompe nunca, y la prueba espera tres segundos después
de que la siguiente quede preparada para comprobar que sigue ahí quieta.

**Condiciones de carrera** — que una respuesta atrasada del servidor no
resucita un estado ya superado, y que pulsar Empezar tres veces seguidas
no lanza tres cuentas atrás. Los dos son fallos que ya ocurrieron.

**Compatibilidad hacia atrás** — que un `estado.json` de la v1.0, sin
`evento` ni `historial`, carga sin perder la biblioteca. Hay gente con su
colección dentro de ese archivo.

**Contrato entre almacenes** — que `api/estado.php` acepta todas las
acciones que implementa `almacen-local.js`. Es la única defensa contra que
la versión Lite y la de servidor se separen sin que nadie se entere.

**La capa de comandos** — que todos los nombres de acción que usa
`KL.comandos` los conoce el servidor, y que ningún otro archivo se salta
la capa montando el mensaje a mano. Una errata en un nombre de acción no
daba error antes: la canción simplemente no aparecía.

**El mono** — ciento veinte pulsaciones al azar sobre los mandos del
evento, comprobando los invariantes después de cada una. Y una cola de
doscientas canciones que entra, se reordena y sale sin perder ni duplicar
ninguna. El azar lleva semilla: si falla, el mensaje trae el número y el
camino exacto para repetirlo. Un fallo que no se puede repetir no se
arregla.

**Módulos encapsulados** — que ninguna función interna se queda suelta en
el ámbito global, y que cada módulo publica su puerta. La primera falla si
alguien vuelve a dejar una función global: no porque ensucie, sino porque
una función global la acaba llamando alguien, y entonces ya no se puede
cambiar sin romper a un desconocido.

**Canción y actuación** — que una actuación nunca guarde su posición ni su
estado, y que los atajos devuelvan lo mismo que el acceso directo.

**Códigos QR** — estructura de la matriz, escapado de la cadena de wifi, y
que un texto demasiado largo da error en vez de un QR silenciosamente
roto.

**Tabla de escenas** — que todos los estados tienen fila, que ninguna fila
se queda sin columnas, y que el calentamiento no puede taparle la pantalla
a la cuenta atrás ni al vídeo. El olvido de esa última columna costó un
fallo entero.

**La API** — que el servidor informa de su dirección de red, que un
invitado no puede ejecutar acciones de operador, que borrar descargas
exige POST, y que la misma canción no entra dos veces.

---

## Los invariantes

Aparte de las 27 pruebas hay una función, `estadoValido()`, que se
ejecuta **después de cada prueba** y comprueba cosas que tienen que ser
ciertas siempre: que el estado existe y tiene fila en la tabla de
escenas, que si se está cantando se sabe el qué, que en ESPERA no suena
nada, que no hay identificadores repetidos en la cola, que el evento no
apunta a una pista que ya no está.

Las pruebas comprueban lo que se le ocurrió a quien las escribió. Esto
comprueba lo que no se le ocurrió a nadie. Añadir uno nuevo es añadir una
línea.

Es lo que hace que refactorizar no dé miedo, y es lo que convierte al
mono en algo útil: un mono pulsando botones sin nadie que mire no detecta
nada.

---

## Qué NO cubren, y hay que probar a mano

Dentro de un marco no se puede probar la pantalla completa, ni el foco
entre ventanas, ni el sonido, ni una cámara escaneando un QR, ni una
persona delante de un micro. Eso está en **`PRUEBAS.md`**, en la raíz del
proyecto: veinte minutos, y sigue siendo obligatorio antes de una fiesta.

Lo más importante de esa lista, por orden:

1. Que las dos pantallas vayan **a la par**, y que la del público abierta
   a mitad de canción salte a donde va.
2. Dejar terminar una canción real y comprobar que la siguiente **no**
   arranca sola — y que en la Cabina DJ **sí**.
3. Escanear los dos QR con un móvil de verdad.
4. Que el sonido salga por donde dice el ajuste, y que la música ambiente
   se aparte sola.

Y una regla para cuando algo de esa lista falle: **antes de arreglarlo,
mira si la suite lo ve**. Si la suite está en verde y la fiesta en rojo,
falta una prueba — y esa prueba vale más que el arreglo, porque el
arreglo se hace una vez y la prueba protege para siempre.

---

## Añadir una prueba

Se abre `pruebas.php` y se escribe. No hay que aprender nada:

```js
prueba('Lo que tiene que pasar', async () => {
  await limpiar();
  const v = await anadir('Una canción');
  await sincronizar(v.videoId);
  KL().evento.preparar(S().queue[0].id);
  await hasta(() => KL().evento.estado() === EV().PREPARADA, 'pase a PREPARADA');
  afirmar(!KL().reproductor.sonando(), 'no debe sonar todavía');
});
```

Cinco funciones y ya está: `afirmar`, `igual`, `hasta`, `esperar` y
`prueba`.

### La regla que más importa

**Nunca esperes un tiempo fijo. Espera a que algo se cumpla.**

`await esperar(2000)` funciona en tu portátil y falla en un ordenador
lento, y una prueba que falla a veces deja de creerse en una semana. Usa
`hasta(...)`, que sondea hasta que la condición se cumple o se agota el
plazo.

### La trampa en la que ya caí

`sincronizar()` espera a que estén **las pistas concretas**, no a que la
cola tenga tantas. La primera versión comprobaba la longitud y era una
carrera: la cola de la prueba anterior seguía en memoria del navegador y
ya tenía esa longitud, así que la espera terminaba al momento y la prueba
seguía trabajando con identificadores que el servidor ya había borrado.

Falló una prueba de las 23 que había entonces y tardé un rato en ver que el error estaba en
la prueba, no en la aplicación.

---

## Tres trampas que ya me han costado un rato

**`sincronizar()` espera pistas concretas, no una longitud.** La primera
versión comprobaba `length >= n` y era una carrera: la cola de la prueba
anterior seguía en memoria del navegador y ya tenía esa longitud, así que
la espera terminaba al momento.

**`limpiar()` espera a que la aplicación se entere.** Limpiar el servidor
no limpia el navegador: se entera al sondear, hasta segundo y medio
después. La prueba siguiente arrancaba a veces con el evento de la
anterior todavía en memoria. Fallaba solo a veces, según cuándo cayera el
sondeo. **La encontró el mono, el mismo día que se escribió.**

**`limpiar()` para la aplicación antes de tocar el servidor.** Si la
prueba anterior dejó una cuenta atrás en marcha, ese temporizador sigue
publicando estados con un contador cada vez más alto y pisa la limpieza.
Y el contador tiene que subir: la aplicación descarta por diseño
cualquier evento con un `n` menor que el suyo, así que mandar `n:0` era
pedirle que se limpiara con un mensaje que estaba programada para tirar.

---

## La carpeta `e2e/`

Guiones de Playwright con Python. **Son opcionales**: cubren lo que la
suite del navegador no puede —pantalla completa, dos ventanas a la vez,
capturas, descodificar QR con un lector real— pero exigen instalar cosas.

Se guardan aquí porque escribirlos costó trabajo y tirar ese conocimiento
sería absurdo. Si algún día haces cambios grandes en el proyector o en el
generador de QR, merece la pena montarlos.

    pip install playwright opencv-python
    playwright install chromium
    python pruebas/e2e/01-ciclo-y-llamada.py

Con `Karaoke.bat` en marcha.

El más valioso es `07-qr-descodificados.py`: genera códigos y los
**descodifica con un lector de verdad**. Fue el que destapó que el
polinomio generador de Reed-Solomon estaba invertido — el QR se veía
perfecto, los lectores lo detectaban, y ninguno conseguía leerlo. Mirarlo
no servía de nada.
