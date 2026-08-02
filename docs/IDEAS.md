# Ideas que todavía no son código

Aquí se aparcan las ideas para que **no entren a presión**. Estar en esta
lista no significa que se vayan a hacer: significa que están pensadas y
que nadie tiene que volver a explicarlas desde cero.

Orden de arriba abajo = orden previsto. Lo que no está en la lista, no
está previsto.

---

## 1 · PWA de verdad

Instalable, funcionando sin red, a pantalla completa y con su icono.

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

## 2 · Cabina DJ

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

## 4 · Modo Show

Un tema más, no un módulo: estética de concurso, marcador de equipos y un
botón que saca una carta de reto («todos disfrazados», «que cante el grupo
entero», «bailando»).

Las cartas son un archivo de datos. Añadir una no es programar.

**Regla**: los puntos premian el espectáculo, nunca la voz.

## 5 · Maestro de ceremonias

Cinco frases grabadas, no un módulo: «un aplauso para X», «preparándose,
Y». Diez minutos de trabajo y cambia mucho la sensación.

Y seis sonidos para el operador —aplausos, redoble, risas, fanfarria—,
**no dieciséis**: un panel de dieciséis botones es una mesa de mezclas y el
operador ya tiene bastante con la fiesta.

## 6 · Identidad

`OpenKaraoke Center`, y el nombre corto `OKC`.

**El rename no es solo cambiar un texto**: está metido en el identificador
de la PWA, en el nombre de la caché del service worker y en las claves
`karaoke_*` de localStorage. Sin migrar esas claves, todo el mundo pierde
sus preferencias al actualizar.

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
