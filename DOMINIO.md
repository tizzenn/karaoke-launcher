# El dominio de Micro Abierto

Paso 5 del plan. **Aquí no hay código todavía, a propósito.** Este
documento es el modelo; si no convence sobre el papel, la implementación
tampoco va a convencer.

Todo lo que sigue está medido sobre el código que hay hoy, no imaginado.

---

## 1. Qué existe de verdad

Antes de la lista, la tabla que obliga a justificar por qué algo merece
ser una entidad. Tres preguntas y una conclusión.

| Concepto | ¿Identidad propia? | ¿Ciclo de vida propio? | ¿Se guarda? | Qué es |
|---|---|---|---|---|
| Canción | Sí | Sí | Sí | **Entidad** |
| Actuación | Sí | Sí | Sí | **Entidad** |
| Evento | Sí (uno) | Sí | Sí | **Entidad** |
| Ajustes | Sí (uno) | Sí | Sí | **Entidad** |
| Cola | No | No | Sí | **Colección** de actuaciones |
| Historial | No | No | Sí | **Colección** de actuaciones |
| Nombre de quien pide | No | No | Sí | **Valor** (de la actuación) |
| Estado del evento | No | No | Sí | **Valor** (del evento) |
| Posición en la cola | No | No | No | **Valor derivado** — no se guarda |
| Operador | — | — | No | **No pertenece al dominio** |
| Pantalla pública | — | — | No | **No pertenece al dominio** |

Cola e Historial estaban antes en esta lista como entidades y era
impreciso: no tienen identidad —no hay «la cola número 3»—, son dos
colecciones de lo mismo que se diferencian por lo que significan. El
código no cambia por esto; la conversación sobre el código, sí.

Y la lista con el porqué de cada una.

| Concepto | ¿Es una entidad? | Por qué |
|---|---|---|
| **Canción** | Sí | Un vídeo concreto. Tiene identidad propia y vive fuera de la fiesta: está en la biblioteca aunque nadie la cante. |
| **Actuación** | Sí | *Esta* canción, *esta* noche, pedida por *esta* persona. Es lo que hoy no existe y por eso hay tres nombres para lo mismo. |
| **Cola** | Colección | La lista ordenada de actuaciones pendientes. El orden **es** información. |
| **Historial** | Colección | Las actuaciones ya cantadas. Misma forma que la cola, otro significado. |
| **Evento** | Sí | El estado de la noche: qué actuación está en curso y en qué punto. |
| **Ajustes** | Sí | Lo que el dueño configura. Hoy vive en tres sitios distintos. |
| Cantante | **No** | Solo existe un nombre escrito a mano al pedir una canción, y así se quedó por decisión expresa: no se llevan asistentes ni se les identifica. Es un **atributo de la actuación**, no una entidad. Modelarlo como entidad obligaría a tener personas, y no las hay. |
| Operador | **No** | No es un dato, es una **superficie**: el monitor del PC. No tiene estado que guardar. |
| Pantalla pública | **No** | Ídem. Es un espectador del estado, no parte de él. |

Que Cantante y Operador **no** sean entidades es la primera decisión del
modelo, y es una decisión de negocio: en una fiesta en casa nadie se
registra.

---

## 2. El problema que este paso resuelve

Hoy, «la canción que se está cantando ahora» se llama de cuatro maneras:

    S.curId              en el navegador del operador   (23 usos)
    estado.sonando       en el servidor
    evento.pistaId       dentro del evento              (29 usos)
    escena.sonando       en la tabla de escenas  ← ¡y aquí significa OTRA COSA!

Las tres primeras son el mismo dato repetido. La cuarta usa la misma
palabra para un booleano —«¿debería estar saliendo audio?»— que no tiene
nada que ver.

Esto no es feo: es la causa directa de un fallo que ya ocurrió. Al volver
del vídeo al operador había que escribir `sonando` y `evento` por
separado, la primera respuesta llegaba con el evento viejo y pisaba el
nuevo. Se tapó con un contador monótono. El contador hay que mantenerlo
igual —protege de respuestas atrasadas— pero **la escritura doble
desaparece si el dato es uno solo**.

Segundo problema medido: los ajustes viven en tres almacenes —el servidor
(`data/ajustes.json`), el `localStorage` del operador y el de la tele, vía
parámetros en la dirección—. Consecuencia concreta: cambiar el efecto del
micrófono **no llega a una tele que ya está abierta**. Hay que recargarla.

Tercero, menor pero del mismo tipo, y **ya resuelto** (apartado 8): la
paleta de colores estaba repetida en
`css/proyector.css` en vez de venir de `css/base.css`.

---

## 3. Qué sabe cada uno, qué puede hacer, qué no debe conocer nunca

### Canción

    Sabe        videoId, título, canal, carátula, duración,
                si está descargada (local), si no se deja incrustar
    Puede       nada. Es un dato.
    NUNCA sabe  si está en una cola, si se ha cantado, quién la pidió.

Una canción no sabe que existe una fiesta. Por eso puede estar en la
biblioteca de alguien que no ha dado una fiesta nunca.

### Actuación

    Sabe        su identificador, qué Canción, quién la pidió (o nadie,
                si la puso el operador), cuándo entró en la cola,
                cuándo se cantó (si se cantó)
    Puede       decir si ya se cantó; decir si fue pedida o puesta a mano
    NUNCA sabe  en qué posición está, ni si es la siguiente, ni si está
                sonando

Lo último es la clave. **La actuación no guarda su estado.** Si lo
guardara, tendríamos dos sitios donde mirar —el evento y la actuación— y
volveríamos al problema de hoy en versión nueva. El estado es del Evento;
la actuación solo se deja señalar.

«Quién la pidió» es un texto y nada más. No hay tabla de personas.

### Cola

    Sabe        la lista ordenada de Actuaciones pendientes
    Puede       añadir al final, quitar, reordenar entera,
                decir cuál va después de una dada
    NUNCA sabe  qué se está cantando, ni la máquina de estados

Se reordena mandando **la lista entera**, nunca «mueve de A a B». Con
instrucciones relativas, dos móviles reordenando a la vez dan un
resultado que no quería ninguno; con la lista completa gana el último,
que es lo que espera quien arrastra una fila.

### Historial

    Sabe        las Actuaciones cantadas, la más reciente primero,
                con la hora en que terminaron
    Puede       recordar, y vaciarse
    NUNCA sabe  nada de la cola ni del evento

La hora la pone **el servidor**, no el navegador: el reloj del PC del
karaoke y el del móvil de un invitado no coinciden.

### Evento

    Sabe        en qué estado está la noche, qué Actuación señala,
                desde cuándo, y su contador monótono
    Puede       las transiciones permitidas, y solo esas
    NUNCA sabe  de YouTube, de iframes, de píxeles, ni de qué pantalla
                enseña qué

Aquí vive **la regla que no se rompe**: una canción no arranca sola.

Lo que se ve en cada estado no lo decide el evento: está en la tabla de
escenas, y las pantallas la consultan. El evento no sabe que existen
pantallas.

### Ajustes

    Sabe        lo del servidor (clave de API, wifi, edición, límites)
                y lo de cada aparato (desfase, efecto, quién da el sonido)
    Puede       decir qué vale cada cosa, y avisar cuando cambia
    NUNCA sabe  qué se está cantando

La separación importante no es «servidor / navegador», es **«de la fiesta»
o «de este aparato»**. El desfase de la tele es de la tele; la clave de la
wifi es de la fiesta. Confundirlo es lo que hace hoy que un cambio no
llegue a una pantalla ya abierta.

---

## 4. Quién puede hablar con quién

    Ajustes                          (no depende de nadie)
       ↑
    Canción                          (no depende de nadie)
       ↑
    Actuación  ──referencia──▶ Canción
       ↑              ↑
    Cola          Historial
       ↑              ↑
       └─── Evento ───┘              señala una Actuación
              ↑
        Tabla de escenas             traduce estado → qué se ve
              ↑
        ┌─────┴─────┐
     Operador     Pantalla pública   (superficies: solo miran)

Y quién es dueño de cada relación, que es lo que el esquema no dice:

| Relación | Quién la posee | Qué significa |
|---|---|---|
| Evento → Actuación | el **Evento** | la *señala*. No la contiene ni la modifica. |
| Cola → Actuaciones | la **Cola** | las *contiene*. Si sale de la cola, deja de estar. |
| Historial → Actuaciones | el **Historial** | las *contiene*, ya cerradas. |
| Actuación → Canción | la **Actuación** | la *referencia*. La canción sigue existiendo sin ella. |
| Canción → nadie | — | no conoce a nadie, y ahí está su valor. |

La diferencia entre **contener** y **señalar** es la que evita el error
clásico: si el Evento *contuviera* la actuación en curso, esa actuación
estaría en dos sitios —la cola y el evento— y habría que mantenerlos a la
par. Señalar es guardar un identificador y nada más.

**Las flechas nunca bajan.** Una Canción no puede preguntar por el
Evento; una Actuación no puede preguntar por la Cola. Si algún día hace
falta que lo hagan, es que el modelo estaba mal.

---

## 5. La prueba de la frase única

La condición que puso el revisor, y me parece la buena: cada módulo en una
frase, sin «y además».

| Módulo | Frase |
|---|---|
| `js/cancion.js` | qué es una canción |
| `js/actuacion.js` | qué es cantar una canción esta noche |
| `js/cola.js` | ordenar actuaciones pendientes |
| `js/historial.js` | recordar actuaciones cantadas |
| `js/evento.js` | decidir en qué punto está la noche |
| `js/estados.js` | traducir el estado a lo que se ve |
| `js/comandos.js` | lo que se le puede pedir al estado |
| `js/senales.js` | repartir avisos |
| `js/reproductor.js` | reproducir vídeo |
| `js/proyector/escenas.js` | qué se ve |
| `js/proyector/servidor.js` | enterarse de los cambios |

Dos que hoy **no pasan la prueba**, y hay que decirlo:

- `js/interfaz.js` → «el pegamento». Eso no es una frase, es una excusa.
- `js/app.js` → «cablear los botones **y** arrancar **y** el cronómetro
  **y** el aviso de red **y** los cartelones». Cinco «y además». Es el
  paso 6.

---

## 6. Las cinco señales de alarma, revisadas contra el código de hoy

El revisor pidió vigilar cinco cosas. Están las cinco, y estas son:

**1. Una entidad que sabe demasiado.** `KL.estado` es un objeto único con
38 propiedades donde conviven la biblioteca, la cola, el historial, el
evento, las preferencias del aparato y el estado de las descargas. No es
una entidad: es un cajón.

**2. Datos duplicados.** `curId` / `sonando` / `pistaId`. Ya está medido
arriba.

**3. Estado derivado guardado sin necesidad.** `escena.sonando` dice si
debería haber audio; se puede deducir del estado y de hecho *se deduce*,
pero también se guarda. Y `S.curId` es derivable de `evento.pistaId`.

**4. Lógica de negocio en la interfaz.** Sí, y localizada: `cola.js`
decide que la primera canción añadida pasa a estar preparada
automáticamente. Es una regla del negocio viviendo en el archivo que
pinta filas.

**5. Conceptos reales como objetos anónimos.** El caso más claro del
proyecto. Una actuación es hoy `{id, videoId, title, channel, thumb,
duration, local, pedida, quien}` — un objeto suelto que mezcla **la
canción** (videoId, título, carátula) con **la actuación** (quién la
pidió, cuándo). Por eso el mismo objeto sirve de fila en la cola y de
entrada en el historial, y por eso nadie sabe cuál de los dos es.

---

## 7. Qué NO voy a hacer en este paso

- **No voy a introducir clases.** El proyecto no usa ninguna y no hace
  falta: funciones que crean y leen objetos con forma conocida bastan, y
  no obligan a nadie a aprender nada nuevo para tocar el código.
- **No voy a cambiar las claves de los datos guardados.** `videoId`,
  `title`, `thumb`, `duration`, `local` se quedan en inglés y con el mismo
  nombre. Hay gente con su colección dentro de `estado.json` y ningún
  refactor vale eso.
- **No voy a tocar el contador monótono.** Protege de respuestas
  atrasadas y sigue haciendo falta aunque desaparezca la escritura doble.
- **No voy a unificar la paleta y el resto a la vez.** El color se toca en
  su propio cambio, para poder mirarlo en una tele y revertirlo solo.

---

## 7 bis. De quién es cada dato

El paso 5.5 que propuso el revisor, y tenía razón en que faltaba. La
pregunta que contesta esta tabla no es «dónde está guardado» sino **quién
tiene derecho a cambiarlo**.

| Dato | Dueño | Quién puede cambiarlo |
|---|---|---|
| `videoId` | Canción | nadie: es su identidad |
| `title`, `channel`, `thumb`, `duration` | Canción | quien resuelve títulos contra YouTube |
| `local` (está descargada) | Canción | la descarga |
| `noEmbed` (no se deja incrustar) | Canción | el reproductor, al fallar |
| `id` de la actuación | Actuación | nadie: es su identidad |
| `quien` (nombre escrito) | Actuación | quien la pide, una vez |
| `pedida` (vino de un móvil) | Actuación | el servidor, al crearla |
| `cantada_en` | Actuación | el servidor, al cerrarla |
| posición en la cola | **nadie** | es el orden del array, no un campo |
| `estado` del evento | Evento | solo la máquina de estados |
| qué actuación está en curso | Evento | solo la máquina de estados |
| `n` (contador monótono) | Evento | solo la máquina de estados, y solo subiendo |
| `desde` (hora del cambio) | Evento | **el servidor**, nunca el navegador |
| clave de API, wifi, edición, límites | Ajustes de la fiesta | el dueño, en ajustes.php |
| desfase, efecto, quién da el sonido | Ajustes de este aparato | quien abre esa ventana |

Tres cosas que esta tabla deja claras y que antes no lo estaban:

- **La posición en la cola no es un dato.** Es el orden del array. Si
  alguien añade un campo `posicion`, hay dos verdades y una se
  desincroniza. Por eso reordenar manda la lista entera.
- **La hora la pone siempre el servidor.** El reloj del PC del karaoke y
  el del móvil de un invitado no coinciden, y el historial ordenado por
  la hora del móvil sale mal.
- **El estado tiene un solo dueño.** Es la regla que hoy se incumple: hay
  tres sitios donde vive «qué se canta ahora» y los tres se escriben.

---

## 8. El orden, y por qué este

1. **Separar Canción de Actuación.** Es la raíz: sin esto, los otros tres
   no se pueden hacer limpios.
2. ~~**Un solo nombre para «lo que se canta ahora».**~~ **HECHO.** `curId`
   ya no es un campo: es una pregunta que devuelve `evento.pistaId` y que
   **no se puede asignar**. El servidor sigue publicando `sonando` porque
   lo leen la tele y los móviles, pero lo *deduce* del evento en vez de
   guardarlo, así que no puede contradecirlo. La acción `sonando` se
   sigue aceptando y no hace nada: una pestaña abierta desde antes del
   cambio la mandaría, y responderle «acción desconocida» sería peor.
3. **Partir `KL.estado`** en «la fiesta» y «este aparato». De ahí sale que
   un cambio de ajustes llegue a una tele ya abierta.
4. ~~**Unificar la paleta**, en su propio cambio.~~ **HECHO.** Se adelantó
   porque no toca el modelo y se podía revertir sola. `proyector.css` ya
   solo cambia lo que de verdad es distinto en una tele —negro de verdad—
   y el resto sale de `base.css`. Efecto secundario que no esperaba: la
   tele ahora **sigue el modo del operador**, así que pasar a DJ o a MC le
   cambia el color a las dos pantallas. Antes la paleta repetida la dejaba
   en verde pasara lo que pasara.

Cada uno tiene que dejar las 32 pruebas en verde antes de empezar el
siguiente. Si uno se tuerce, se revierte solo ese.

---

## 9. Lo que le pedí al revisor, y qué contestó

1. **¿Falta Sesión/Noche?** Hoy no. El criterio acordado: existirá cuando
   aparezcan dos o tres datos que sean claramente de *una* ejecución del
   karaoke —duración, número de actuaciones, un QR temporal—. Hoy no hay
   ninguno. **Pero hay un síntoma anotado**: el historial no se vacía
   solo, así que en la fiesta siguiente todavía se ven las canciones de
   la anterior. Eso se arregla con un campo, no con una entidad.
2. **¿Cantante es entidad?** No, y por decisión de producto: el programa
   nunca conoce personas, solo un texto. `quien` es un valor de la
   actuación.
3. **¿5 antes que 6?** Sí, por dependencia y no por gusto: encapsular
   módulos es decidir qué exporta cada uno, y `KL.estado` va a partirse
   en dos. Encapsular primero obligaría a encapsular dos veces.
4. **¿Hay un paso 5.5?** Sí: la tabla de propiedad de los datos. Está
   arriba, en el apartado 7 bis.
5. **¿Las flechas bajan en algún sitio?** Sin hallazgos. La regla queda
   como norma del proyecto: si un módulo necesita preguntar hacia abajo,
   es una alarma, no un apaño.

Único punto en el que el modelo cambió tras la revisión: Cola e Historial
han dejado de llamarse entidades. Son colecciones.

No «¿está bien?». Estas cinco:

1. ¿Falta alguna entidad? Sospecho de **Sesión/Noche** —lo que empieza al
   encender y termina al apagar— pero hoy no tiene ni un dato propio, así
   que no la he metido. ¿Es un error?
2. ¿Es correcto que **Cantante no sea una entidad**? Es una decisión de
   producto tanto como técnica.
3. ¿El paso 5 va antes que el 6, o al revés? Encapsular primero haría el
   dominio más fácil de mover; modelar primero evita encapsular algo que
   va a cambiar de forma. He elegido lo segundo. Se puede discutir.
4. ¿Hay un **paso 5.5** que no veo?
5. ¿La regla de que **las flechas nunca bajan** se rompe en algún sitio
   donde no me he dado cuenta?
