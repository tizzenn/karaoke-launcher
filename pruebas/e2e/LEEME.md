# Las pruebas con un Chrome de verdad

`pruebas/pruebas.php` corre **dentro** de la aplicación: abre un iframe,
manipula el estado y mide el DOM. Cubre el motor y las reglas de producto,
y es lo que hay que mirar primero.

Lo que no puede ver es todo lo que necesita **una ventana de verdad**:

- que la actuación ocupe la pantalla entera —dentro de un iframe «entera»
  son 800 píxeles—;
- que dos ventanas, operador y tele, se pongan de acuerdo;
- que la pantalla del público abierta a mitad de canción salte a donde va;
- cómo se ve algo. Una captura de pantalla no la hace un `assert`.

Para eso están estos guiones. Los conduce **Playwright**, que es Chrome
manejado desde fuera.

---

## Cómo se corren

Hace falta Python y Playwright **una sola vez**:

```
pip install playwright
python -m playwright install chromium
```

Con `Karaoke.bat` arrancado y la aplicación en `http://localhost:8123`,
desde esta carpeta:

```
python 00-ejecutar-la-suite.py
python 09-modo-actuacion.py
python 10-instalacion-limpia.py
```

El **10 es el más importante de todos** y no necesita ni Playwright ni que
la aplicación esté arrancada: se monta la suya. Copia el proyecto a una
carpeta vacía, lo levanta con los errores apagados —como hace
`php.ini-production`, que es lo que instala `Preparar.bat`— y comprueba
que ninguna pantalla sale en blanco.

Es la única prueba que no da por hecho que la aplicación arranca, y por
eso encontró dos fallos que las otras noventa y nueve no podían ver.

Cada guion escribe **capturas de pantalla** en la carpeta desde la que se
lanza. Esa es la mitad del valor: hay fallos —un botón que no se lee sobre
un vídeo oscuro— que no dan error en ninguna parte y se ven a la primera
en una imagen.

Ninguno toca `data/ajustes.json`. Sí usan la cola: si la fiesta está
vacía, algunos avisan y se paran.

---

## Cómo pedírselo a Claude Code

Claude Code corre en tu ordenador y puede hacer exactamente esto: lanzar
los guiones, **abrir las capturas y mirarlas**, y escribir uno nuevo para
lo que quieras comprobar. Un encargo que funciona bien:

> Arranca `Karaoke.bat`, corre todos los guiones de `pruebas/e2e/`, abre
> las capturas que generen y dime qué se ve mal. Si algo falla, busca la
> causa antes de tocar nada y explícamela.

Y para algo concreto:

> Escribe un guion de Playwright en `pruebas/e2e/` que abra el operador y
> la pantalla del público, ponga el tema Peques, empiece una canción y me
> deje una captura de cada pantalla.

Dos avisos que ahorran una tarde:

- **Un fallo en una prueba puede ser de la prueba.** Pasó con la
  sincronía: el guion comparaba contra el segundo 12 fijo y daba rojo en
  un ordenador con la calibración a 0,7. El programa estaba bien; la
  prueba daba por hecho un ajuste que el usuario puede cambiar.
- **Si la suite está en verde y algo falla en la fiesta, falta una
  prueba** — y esa prueba vale más que el arreglo.

---

## Lo que hay

| | |
|---|---|
| `00-ejecutar-la-suite.py` | Lanza `pruebas.php` y devuelve el marcador |
| `01-ciclo-y-llamada.py` | El ciclo entero con las dos pantallas |
| `02-calentamiento-y-tele.py` | Cartelones, micro y escenas de la tele |
| `03-fallo-de-video.py` | Un vídeo que no arranca |
| `04-salida-de-sonido.py` | Por dónde sale el sonido |
| `05-red-y-qr.py` | Dirección en la red local y QR |
| `06-descarga-en-segundo-plano.py` | yt-dlp sin bloquear la fiesta |
| `07-qr-descodificados.py` | Los QR se leen de verdad |
| `08-mando-de-cartelones.py` | El mando desde el operador |
| `09-modo-actuacion.py` | Modo actuación, botón de salida y sincronía |
| `10-instalacion-limpia.py` | **Copia el proyecto a una carpeta vacía y lo arranca de cero** |
