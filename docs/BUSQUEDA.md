# Cómo buscar canciones

**El campo pequeño, debajo del buscador.**

Hay dos campos y son dos preguntas distintas:

| | |
|---|---|
| **Arriba** | qué busco — «bohemian rhapsody» |
| **Abajo** | cómo lo acoto — «karaoke OR playback» |

Lo de abajo se queda puesto entre búsqueda y búsqueda, y **cada espacio
recuerda el suyo**. Ir a la Cabina DJ a poner una canción y volver al
Karaoke no te hace reescribirlo.

Mientras escribes, debajo aparece **lo que de verdad se va a pedir**. Esa
línea es el manual: escribe `OR`, mira cómo se convierte en `|`, y ya lo
sabes.

---

## Lo que se puede escribir

### Varias palabras — todas tienen que estar

```
karaoke español
```

Un espacio ya significa «y». Puedes escribir `AND` si te sale solo
—también `Y`— pero no hace falta: no cambia nada.

### `OR` — cualquiera de ellas

```
karaoke OR playback
```

Esto es lo más útil de todo. Muchos karaokes no llevan la palabra
«karaoke» en el título: llevan «playback», «pista», «con letra» o
«instrumental». Con `OR` los pillas todos de una vez.

Vale `OR`, vale `O` y vale `|`.

### `-` — que NO salga

```
karaoke -live -cover
```

El clásico: quitar de en medio los conciertos y las versiones de otra
gente cuando lo que buscas es una pista limpia.

También se puede decir con palabras: `sin live sin cover`.

### `"comillas"` — esas palabras, en ese orden

```
"karaoke version"
```

Sin comillas, «karaoke version» encuentra cosas que llevan las dos
palabras sueltas por cualquier parte. Con comillas, la frase entera.

Si te dejas una comilla sin cerrar, se cierra sola. No es un error que
haya que enseñar.

### Todo junto

```
karaoke OR "karaoke version" OR playback -live -cover
```

Se lee así: que sea de karaoke, de las tres maneras en que puede estar
escrito, y que no sea un directo ni una versión de nadie.

---

## Lo que NO se puede escribir

Y aquí está la regla que gobierna este campo:

> **No hay ningún operador inventado.** Todo lo que se acepta hace algo
> de verdad al llegar a YouTube.

Es muy fácil escribir un buscador que finja entender. Aceptas `IN`,
aceptas `NEAR`, aceptas lo que sea, lo mandas tal cual, y YouTube lo trata
como una palabra más. Devuelve resultados, parecen razonables, y **nadie
descubre nunca que no ha hecho nada**. Un buscador que miente es peor que
uno tonto: con el tonto sabes a qué atenerte.

Así que no hay `site:`, no hay `filetype:`, no hay `AROUND()` y no hay
`IN`. La API de YouTube entiende esto y solo esto:

| Lo que escribes | Lo que se manda | Qué hace |
|---|---|---|
| un espacio, `AND`, `Y` | un espacio | todas las palabras |
| `OR`, `O`, `\|` | `\|` | cualquiera de ellas |
| `-palabra`, `sin palabra`, `no palabra` | `-palabra` | que no salga |
| `"frase"` | `"frase"` | tal cual, en ese orden |

---

## Los perfiles

El botón de la estrella guarda lo que hay escrito con un nombre. Un perfil
**es el filtro con nombre**, nada más — no es otra función.

En la lista desplegable están además los de fábrica, y no son un adorno:
son la documentación que sí se lee. Quien despliega y ve
«Sin versiones en directo → `karaoke -live -cover -concierto`» aprende
para qué sirve el guion sin abrir esta página.

**Karaoke**: Karaoke normal · Karaoke o playback · Con letra en pantalla ·
Sin versiones en directo · Instrumental.

**Cabina DJ**: Tal cual · Videoclip oficial · Para bailar · Bases y beats.

Los perfiles son de este ordenador, no de la fiesta: es una manera de
trabajar, no un dato que tengan que compartir las pantallas.

---

## Y una advertencia sobre la cuota

Cada búsqueda cuesta **100 unidades** de las 10.000 que da YouTube al día.
Eso son **unas 99 búsquedas**, y a medianoche vuelta a empezar.

Por eso conviene saber tres cosas:

- **Repetir una búsqueda es gratis.** Se guardan durante treinta días. Si
  tres personas piden la misma canción, se paga una vez.
- **Pegar un enlace de YouTube es gratis.** No pasa por la API.
- **Escribir mejor el filtro ahorra búsquedas**, que es el motivo real de
  que este campo esté a la vista: una búsqueda bien acotada evita las tres
  siguientes.

En `qa.php` se ve cuánta cuota llevas gastada hoy y cuántas búsquedas te
has ahorrado.
