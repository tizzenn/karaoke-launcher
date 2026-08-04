# Diseño de los temas

**Versión 1.1 · Agosto 2026**

`MODELO.md` define **qué es** un tema. Este documento define **cómo se
decide** dentro de uno. No es una guía de CSS ni una lista de tareas: es
el desempate para cuando alguien —yo dentro de un año— proponga un
cambio y haya que contestar sí o no.

Lo que esté a medias o pendiente vive en `IDEAS.md`. Aquí solo hay
principios, y deberían seguir valiendo cuando el CSS haya cambiado tres
veces.

---

## 0 · La regla de la que salen todas las demás

> **Un tema cambia cómo se siente la aplicación, nunca cómo se aprende.**
>
> Al cambiar de tema, el usuario reaprende **cero** cosas.

Todo lo demás de este documento es consecuencia de esa frase.

---

## 1 · Los invariantes

Seis cosas que un tema **nunca** cambia:

```
- el HTML
- los flujos
- las acciones disponibles
- la lógica
- el significado de los colores
- la accesibilidad mínima
```

> **Si un cambio rompe cualquiera de estas seis, ya no es un tema: es
> otra interfaz.**

Y de ahí sale lo concreto. Un tema no mueve un control de sitio, no
oculta una función, no cambia el nombre de nada, no toca un atajo de
teclado y no crea comportamiento nuevo. Todo eso es la regla 0 dicha en
particular.

Un caso real, para que se vea el filo: en Peques tendría sentido esconder
«Vaciar la cola» cuando hay un niño mirando. **Y no se hace**, porque
esconder funciones es cambiar la aplicación, no vestirla. Si esa idea es
buena, se decide para todos los temas o no se hace.

El invariante del HTML se explica en el apartado 8, y el del significado
de los colores tiene truco: en Peques verde es «seguir» y eso no puede
cambiar **dentro de Peques**, porque se aprende por repetición. Lo que sí
cambia entre temas es qué verde.

---

## 2 · Por qué hay cuatro temas y no veinte

Es la pregunta que va a hacer todo el mundo, y la respuesta decide si
esto sigue siendo un sistema o se convierte en un cajón.

> **Un tema nace de un contexto de uso, nunca de un gusto.**

No existe «tema rojo», ni «tema claro», ni «tema retro». Existe un tema
cuando aparece **una situación de uso que exige decisiones de diseño
distintas**: un bar a media luz, un salón a la una, un cumpleaños, un
escenario.

Y el corolario que hace de podadera:

> **Si dos temas toman las mismas decisiones, sobra uno.**

Un tema oscuro y otro oscuro con el acento en azul no son dos temas: son
un tema y una preferencia de color, y las preferencias de color no van
aquí.

---

## 3 · La pirámide: quién gana cuando dos prioridades chocan

Cada tema tiene su prioridad, pero hace falta un orden general para
cuando se contradicen entre ellas.

```
Siempre manda, de arriba abajo:

  1. Comprensión      entender qué está pasando
  2. Legibilidad      poder leerlo
  3. Seguridad        no romper nada sin querer, ni hacer daño a nadie
  4. Ambiente         que la sala se sienta como toca
  5. Decoración       lo bonito
```

Se lee así: **nada de un nivel puede sacrificar algo de un nivel
superior**. Un neón que impide leer un título pierde contra la
legibilidad aunque el tema se llame Fiesta. Un mensaje divertido que
puede hacer sentir mal a un crío pierde contra la seguridad aunque sea
más gracioso.

Y al revés, dentro de un mismo nivel manda la prioridad del tema: en
Peques un botón enorme empeora la densidad y entra igual, porque la
densidad no está en esta lista.

---

## 4 · Diseño emocional

Hay temas cuyo objetivo no es que se lea mejor, sino que **nadie lo pase
mal**. Estas reglas no son de Peques: son de cualquier tema que algún día
se declare emocional —uno para residencias, uno para terapia, uno para
una boda—.

En un tema emocional:

- **Nunca valorar el resultado.** Ni para mal ni para bien.
- **Nunca comparar personas.** Ni con otras ni consigo mismas.
- **Nunca meter presión temporal.** Ni cuentas atrás, ni «rápido», ni
  turnos que suenen a obligación.
- **Reforzar la acción, no el rendimiento.** Se celebra haber
  participado, que es lo único que depende de quien participa.

Por qué los elogios cuentan como valoración:

```
✗  Has cantado genial          ✓  Gracias por compartir tu canción
✗  Muy bien                    ✓  Qué bien tenerte aquí
✗  Eres un artista             ✓  Seguimos cantando
✗  Perfecto                    ✓  Gracias por venir
```

Si esta vez te dicen «muy bien», la siguiente sales esperando a ver qué
te dicen — y el que cantó peor lo nota. «Eres un artista» es la peor de
todas: valora a la persona y no la actuación, y de eso también se puede
dejar de ser.

Lo mismo con las obligaciones: «Ahora **le toca a** Marta» suena a turno
impuesto aunque no lo sea; «Ahora canta Marta» dice lo mismo sin empujar
a nadie.

Hay una prueba que busca las palabras de la columna izquierda —incluidas
las buenas— y falla si aparece alguna.

---

## 5 · La fatiga

Esta aplicación se usa **cuatro horas seguidas**. Eso cambia el diseño
más que ninguna otra cosa.

> **Un tema tiene que seguir funcionando después de tres horas.**
>
> Lo que impresiona durante treinta segundos suele cansar durante tres
> horas.

Es la razón real de que Fiesta tenga tan poco movimiento, y de que lo
poco que hay sea lento y esté lejos de donde se lee. Una animación se
juzga imaginándola a la vez ciento veinte, no la primera.

De ahí sale el presupuesto: **máximo dos elementos animados** por
pantalla y por tema. Cada animación se añade con buen criterio y por
separado; el problema aparece cuando hay cuatro, y nadie las ve
acumularse porque nunca se añaden el mismo día. Hay una prueba que lo
cuenta.

---

## 6 · El tiempo se comporta distinto en cada tema

No es solo cuánto se mueve: es **qué puede hacer esperar**.

```
CLÁSICO   Nada espera. Todo responde en el acto.

FIESTA    La luz puede esperar. La interacción, nunca.

PEQUES    Las animaciones acompañan al dedo. Nunca lo hacen esperar.

SHOW      Las transiciones SON el espectáculo. Un segundo entero está
          bien: esta pantalla no la usa nadie, la mira todo el mundo.
```

La línea que separa los cuatro es la misma: **una animación puede
adornar una respuesta, nunca retrasarla** — salvo en Show, donde nadie
está pulsando nada.

---

## 7 · Los temas no mezclan lenguajes

Cada tema tiene su propio vocabulario de formas, y ahí es donde se pierde
la identidad sin que nadie se dé cuenta: un detalle prestado de otro tema
cada dos meses, todos defendibles por separado.

```
CLÁSICO      FIESTA        PEQUES        SHOW
precisión    luz           juguetes      escenario
contraste    neón          cápsulas      focos
densidad     movimiento    volumen       telón
```

> **Si aparece un elemento que pertenece claramente a otro lenguaje —un
> neón en Peques, un botón de plástico en Show, una cápsula en
> Clásico— el tema pierde identidad.**

No es una regla estética. Es la que sostiene la prueba del medio segundo:
lo que hace reconocible un tema no es su color, es que todas sus formas
hablen igual.

---

## 8 · El coste de mantenimiento es parte del diseño

> **El mejor tema es el que consigue su personalidad reutilizando la
> mayor parte posible del sistema común.**
>
> **Cuando un tema necesita HTML distinto, probablemente ya no es un
> tema.**

Un tema precioso con quinientas reglas propias es un fracaso: cada
cambio en la aplicación habrá que hacerlo cinco veces, y a la tercera
alguien se dejará una.

El caso que mejor lo enseña son las fichas de Peques. Las canciones
pasan de fila a tarjeta —carátula grande, título debajo, botones al pie—
y el HTML **no se toca**: son las mismas cuatro piezas colocadas en otro
sitio con `grid`. Si hubiera hecho falta una plantilla distinta, la
respuesta correcta habría sido no hacerlo.

---

## 9 · Los cuatro temas

### La identidad en tres palabras

```
CLÁSICO          FIESTA           PEQUES           SHOW
sobrio           energía          seguridad        escena
preciso          neón             juego            expectación
estable          ritmo            calidez          escala
```

Sirve para contestar deprisa: si un cambio propuesto no cabe en esas tres
palabras, no es de ese tema.

### Objetivo y sala

|  | Objetivo | Prioridad | Sala |
|---|---|---|---|
| **Clásico** | Que se lea | Legibilidad | Un bar a media luz |
| **Fiesta** | Que se note que hay fiesta | Ambiente | Un salón a la una |
| **Peques** | Que nadie sienta presión | Seguridad emocional | Un cumpleaños |
| **Show** | Que parezca un escenario | Espectáculo | Un salón grande |

### No busquéis simetría

Los cuatro **no tienen que cambiar lo mismo**:

```
Clásico   ██        casi nada: es el punto de referencia
Fiesta    ████      luz y movimiento
Peques    ███████   tamaños, formas, colores y palabras
Show      █████     la tele entera
```

Peques cambia mucho más porque responde a un objetivo mucho más concreto.
Igualar el número de reglas «para que queden parejos» sería añadir
cambios sin motivo, que es como se estropea un tema.

---

### Clásico — el punto de referencia

**No compite con los demás. No hay que hacerlo más atractivo.** Cuando
alguien proponga partículas, degradados o transparencias, la respuesta ya
está escrita: no ayudan a leer.

Lo que sí es: **una mesa de mezclas**. El operador trabaja rápido, de
pie, con gente esperando.

- **Cero animaciones.** Ninguna. Lo que se mueve, distrae.
- **Esquinas pequeñas.** El redondeo grande come espacio y suaviza los
  límites entre cosas que hay que distinguir deprisa.
- **Densidad**: más canciones a la vista sin bajar.
- **El color del espacio, muy marcado.** Es el único tema donde el color
  dice en qué espacio estás, y ahí tiene que gritarlo.

### Fiesta — club

La diferencia con Clásico no es «más rosa»: es que aquí las cosas
**brillan y laten**, y en Clásico no se mueve nada.

> Movimiento lento. Luz. Neón. **Nunca confusión.**

La última palabra manda. El operador está trabajando en esta pantalla con
gente delante: una interfaz de fiesta que le distraiga es peor que una
aburrida.

- **Toda la vida ocurre alrededor, nunca donde hay que leer.** La cola se
  queda limpia; el neón vive en la cabecera, el botón principal y el
  espacio activo.
- **Solo dos elementos brillan.** Si brilla todo, no brilla nada — la
  regla del acento aplicada a la luz.

### Peques — para que cantar dé menos vergüenza

El nombre anterior de este apartado era «casi una aplicación educativa» y
estaba mal. Una aplicación educativa intenta enseñar algo. Aquí no se
enseña nada:

> **Peques no intenta entretener al niño: intenta quitarle motivos para
> tener vergüenza.**

Todo el capítulo sale de esa frase. Aplica entero el apartado 3, y se
diseña para alguien concreto: no «para niños», sino para **un niño de
seis a ocho años que mira una televisión desde tres metros mientras hay
ruido, luces y gente alrededor**. Eso decide solo casi todo lo demás.

El error inicial fue el más común: era **claro y con poco contraste**.
Crema sobre crema. Se leía peor que el tema oscuro.

```
Fondo    → pastel
Botones  → MUY saturados
Texto    → casi negro
```

**Y los grises fuera.** Un gris intermedio es jerarquía para un adulto y
una cosa que no se ve para un niño. Aquí todo es negro o color.

- **Un color por acción**, siempre el mismo: verde seguir, azul volver,
  coral quitar, morado información. Se aprende por repetición en dos usos
  y a partir de ahí no hace falta leer el botón.
- **Objetos, no listas.** Cada canción es una ficha que se encuentra por
  la imagen. Un niño no busca leyendo.
- **Todo cápsula o círculo**, iconos del doble, peso 700-800, familia
  redondeada del sistema. Nunca una fuente de internet: esto tiene que
  verse con el router caído.
- **Física de juguete**: el botón se hunde, rebota y proyecta sombra del
  mismo color. Sombras coloreadas, no grises — una sombra gris sobre
  crema es suciedad.

### Show — no un escenario, un espectáculo

Los otros tres tenían su frase y este no. Es esta:

> **Si parece una aplicación, todavía no parece un escenario.**

Se usa como cuchilla: cualquier cosa que recuerde a quien maneja el
aparato —un borde de caja, un contador, una barra de herramientas— sobra
en esta pantalla. Solo queda el espectáculo.

La diferencia entre «tema elegante» y «evento» no está en el color: está
en **cómo entra cada cosa**.

- En televisión un rótulo no aparece: **sube**. Y no se va en cuanto
  termina: se queda un segundo de más.
- **Las luces bajan antes de un nombre y vuelven después.**
- **Menos interfaz.** En un escenario no hay barras de herramientas.

---

## 10 · Lo que se ha descartado, y por qué

- **Sonidos de interfaz.** Pasados por la pregunta de arriba —si los
  quito, ¿la fiesta empeora?— la respuesta es **no** en Clásico, **no**
  en Fiesta y **no** en Show. En Peques, quizá un poco, y solo como
  parte de la sensación de juguete. Así que si algún día entran, sería
  **solo ahí** —un pop al pulsar— y ni eso está decidido.

  Cuidado con no confundirlos con los del **Maestro de ceremonias**, que
  no son sonidos de interfaz: un aplauso lo oye toda la sala y responde
  a una decisión del anfitrión, no a un clic. Esos sí pasan la pregunta,
  y por eso existen.

  El riesgo real de abrir esta puerta no es un sonido: son quince. Cada
  uno parece inocente por separado y juntos convierten la aplicación en
  algo que hace ruido cada vez que la tocas.
- **Partículas en Fiesta.** Rompe «nunca confusión» y el presupuesto de
  dos animaciones.
- **Esconder botones en Peques.** Ver el apartado 1: rompe un invariante.
- **Igualar el peso de los cuatro temas.** Ver arriba.

---

## 11 · Lo que sujeta esto

Dos pruebas, porque «le falta contraste» es una impresión y las
impresiones se discuten sin final:

- **Contraste medido** con la fórmula de WCAG. Mínimo 4,5:1 en todos y
  **7:1 en Peques** — el nivel que se exige cuando quien mira puede tener
  la vista cansada, o siete años.
- **Máximo dos elementos animados** por pantalla y por tema.

Y una tercera que no es automática sino una forma de mirar una captura:

> Si tapas el nombre y la enseñas durante medio segundo, cualquiera
> debería poder decir «esto es Peques» o «esto es Show».

Mientras haga falta leer el nombre para saber qué tema es, sigue siendo
una piel.

---

## 12 · Antes de añadir una regla

Primero, la pregunta que mejor funciona y que no es la obvia:

> **No preguntes «¿esto es bonito?». Pregunta: si lo quito, ¿la fiesta
> empeora?**

La primera siempre se contesta que sí — todo lo que uno acaba de diseñar
parece bonito. La segunda obliga a mirar la sala en vez de la pantalla, y
casi siempre se contesta que no.

Sirve de podadera para lo que ya está, no solo para lo que entra: es la
única forma de quitar algo, porque quitar nunca tiene defensor.

Y después, tres preguntas más, y las tres tienen que salir que sí:

```
1. ¿Hace el tema más reconocible?
2. ¿Refuerza su prioridad principal?
3. ¿Podría quitar una regla en vez de añadir esta?
```

La tercera es la que nadie se hace y la que más falta hace. Un tema no
mejora acumulando detalles: mejora cuando cada detalle que queda es
necesario. Si la respuesta a la tercera es «sí, podría», el cambio
correcto es el otro.

---

## 13 · Cómo se sabe si un tema ha salido bien

> **Si una captura permite reconocer el tema al instante pero el usuario
> tiene que volver a aprender a usarlo, el tema ha fracasado.**
>
> **Si no tiene que pensar y además reconoce el contexto de un vistazo,
> el tema ha cumplido.**

Las dos mitades importan, y en ese orden.
