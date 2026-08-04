# Encargo para Claude Code

Lo que se hace **en la máquina de r**, y por eso no lo hago yo: o necesita
borrar archivos, o necesita un Chrome de verdad con la aplicación
instalada como la instalaría cualquiera.

Se lo puedes dar entero, o por tareas. Están en orden: la 1 es de un
minuto y las otras dependen de que la aplicación arranque.

---

## 1 · Borrar dos carpetas muertas

En `C:\Users\rober\Desktop\Karaoke-Proyecto`:

```
aplicacion\
PERSONAL-CONGELADA-20260801\
```

`aplicacion\` es de antes de que existieran los dos espejos.
`PERSONAL-CONGELADA-20260801\` fue una copia de seguridad de un día
concreto y ya está superada por el zip.

**Antes de borrar**, dos comprobaciones que cuestan nada:

- Que `PERSONAL\data\ajustes.json` y `PERSONAL\data\estado.json` existen y
  se leen. Son la clave de la API y la biblioteca personal.
- Que ninguna de las dos carpetas tiene un `data\videos\` con descargas
  que no estén también en `PERSONAL\`.

Si algo de eso falla, **no borres nada** y dilo.

> `PERSONAL\` no se sube nunca a GitHub. Lleva la clave de la API y la
> biblioteca. Si en algún momento tocas `.gitignore`, compruébalo.

---

## 2 · La instalación limpia

Esto es lo que descubrió los dos fallos del portátil del amigo, y es la
prueba que más vale del proyecto: **en el ordenador de uno ya está todo
puesto y por eso todo funciona**.

Copia `publico\` a una carpeta nueva, vacía, fuera del proyecto —o mejor,
a otro ordenador— y recorre esto sin saltarte nada:

```
□ Preparar.bat termina sin errores
□ Karaoke.bat arranca y la ventana negra se queda abierta
□ Abre  http://localhost:8123/qa.php  ANTES que nada
     · si algo sale en rojo, para y dilo: lo demás serán síntomas
□ Operador abre, cero errores en consola, cero 404
□ Pantalla del público abre
□ pedir.php abre EN UN MÓVIL de verdad, por el QR
□ Ajustes: guardar una clave de API y que diga «Guardado»
□ Ajustes: guardar otra vez sin tocar nada
□ Pegar un enlace de YouTube en el buscador SIN clave de API
     · tiene que funcionar: va por oEmbed, no por la API
□ Cambiar de tema y que la tele se ponga igual sola
□ Empezar una actuación y dejarla terminar entera
□ Cerrar y volver a abrir Karaoke.bat: sigue todo
```

**Ninguna casilla puede acabar en una página en blanco.** Si alguna lo
hace, es un fallo nuevo: `api/comun.php` tiene una red que convierte
cualquier error fatal en una pantalla que explica qué ha pasado, y
`qa.php` tiene un botón —«Probar la red de seguridad»— para comprobar que
sigue puesta.

---

## 3 · La sesión de QA con Chrome

El protocolo completo está **dentro de `qa.php`**, listo para copiar. Ahí
está y no aquí a propósito: un protocolo en un documento aparte se queda
viejo, y ese se lee al lado de los botones que lo ejecutan.

Resumido:

1. Abre `qa.php` y comprueba que todo está en verde.
2. Pulsa «Cargar fiesta de ejemplo» para partir siempre del mismo sitio.
3. Copia el protocolo de esa misma página y recórrelo entero.
4. **No te pares en el primer fallo.** Informe completo al final.

Lo que más interesa, por orden:

- **El ciclo de karaoke completo**, dejando terminar la canción. Al
  acabar: la consola vuelve sola, la siguiente queda PREPARADA, **no
  arranca**, y el botón Empezar está encendido y pulsable.
- **El ciclo de Cabina DJ**, que hace lo contrario a propósito: al
  terminar una, la siguiente arranca sola.
- **El campo de filtro**: `AND`, `OR`, comillas, `-`, los perfiles,
  guardar uno propio. Y la caché — repetir la misma búsqueda tiene que
  decir que viene de la caché y no gastar cuota. Se ve en `qa.php`.
- **La actuación a pantalla completa** desde las tres vistas: completa,
  compacta y mini.

También hay guiones de Playwright ya hechos en `pruebas\e2e\`, con su
`LEEME.md`. Correrlos es un minuto y cubren la parte mecánica; lo de
arriba es para lo que una máquina no ve.

---

## 4 · Si encuentras un fallo

El orden que ha funcionado en este proyecto, y que pido mantener:

1. **Reprodúcelo** y apunta los pasos exactos.
2. **Busca la causa antes de tocar nada.** Dos síntomas distintos suelen
   ser un solo fallo — las dos páginas en blanco del portátil eran
   `display_errors=Off` las dos veces.
3. **Escribe la prueba primero.** Si la suite está en verde y la fiesta en
   rojo, lo que falta es una prueba, y esa prueba vale más que el arreglo:
   el arreglo se hace una vez, la prueba protege para siempre.
4. Arregla, y deja `pruebas/pruebas.php` en verde.

Y una cosa que también ha pasado aquí: **un fallo en una prueba puede ser
de la prueba**. La de sincronía daba rojo en un ordenador con la
calibración a 0,7 porque comparaba contra un número fijo. El programa
estaba bien.

---

## Lo que NO hay que hacer

- No subir `PERSONAL\` a ningún sitio.
- No añadir funciones nuevas mientras quede un fallo sin resolver.
- No tocar `docs\MODELO.md` sin decirlo: ahí están las decisiones de
  producto, y cambiarlas es una decisión, no un cambio.
- No meter frameworks, npm ni pasos de compilación. Esto tiene que seguir
  arrancando con doble clic en un ordenador de alguien que no programa.
