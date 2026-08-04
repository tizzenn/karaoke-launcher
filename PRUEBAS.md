# Lo que hay que probar a mano

**Antes de una fiesta de verdad. Veinte minutos.**

Las 90 pruebas automáticas de `pruebas/pruebas.php` cubren el motor, el
estado, las carreras y las reglas de producto. Lo que **no** pueden
cubrir es todo lo que ocurre fuera del navegador: el sonido, la segunda
pantalla, una cámara de móvil apuntando a un QR y una persona delante del
micro.

Eso es esta lista. No es larga porque no puede serlo: una lista de
cuarenta pasos no la hace nadie, y una lista que no se hace no protege de
nada.

---

## 0 · Lo primero, siempre

- [ ] `Karaoke.bat` arranca y **la ventana negra se queda abierta**.
- [ ] Ajustes → **¿Está todo bien?** sale en verde, o lo que salga en
      ámbar es algo que ya sabías.

Si el diagnóstico está en rojo, arréglalo antes de seguir. Está escrito
para eso: cada aviso dice qué hacer.

---

## 1 · Las dos pantallas

- [ ] El botón de la segunda pantalla abre la ventana del público.
- [ ] La arrastras a la tele y con **F** se pone a pantalla completa.
- [ ] En el operador aparece **Pantalla del público: conectada**.

**Y lo que de verdad importa:**

- [ ] Empiezas una canción y las dos pantallas van **a la par**. No hace
      falta que arranquen a la vez —es imposible— pero a los cinco
      segundos tienen que estar en el mismo punto.
- [ ] Si abres la ventana del público **a mitad** de una canción, salta
      sola a donde va. No empieza desde el principio.

> Si la tele va con retraso constante —modo cine, un receptor de audio
> por medio— eso no lo puede arreglar el programa: está en Ajustes →
> Avanzado → Calibración. En el caso normal es 0.

---

## 2 · El sonido

- [ ] Sale por donde dice Ajustes → Este aparato → «El sonido sale por…».
- [ ] La otra pantalla está **muda pero reproduciendo**: si la callas no
      la estás parando.
- [ ] La música ambiente suena en los huecos y **se aparta sola** al
      preparar una actuación.
- [ ] Al terminar, vuelve.

---

## 3 · La regla que no se rompe

- [ ] Dejas terminar una canción entera.
- [ ] La siguiente queda **preparada**.
- [ ] **NO arranca sola.** Esperas diez segundos y sigue ahí quieta.

Esto lo comprueba también la suite, y aun así está aquí: es la única
regla del proyecto que, si se rompe en una fiesta, se nota delante de
treinta personas.

- [ ] En la **Cabina DJ**, lo contrario: al terminar una, la siguiente
      **sí** arranca sola. Allí no hay nadie esperando al micro.

**Y el modo actuación, que es lo que ve quien canta:**

- [ ] Al darle a **Empezar**, la consola desaparece entera: solo el vídeo,
      de borde a borde. También si tenías la vista **compacta** o **mini**.
- [ ] Arriba a la izquierda se ve **Volver al operador** sin tener que
      buscarlo.
- [ ] Al terminar la canción, la consola **vuelve sola**. No hay que
      cerrar nada.
- [ ] Con la siguiente ya preparada, el botón **Empezar** está encendido
      y se puede pulsar.

---

## 4 · Unirse a la fiesta

Con un móvil de verdad, no con el navegador del PC.

- [ ] QR **1 · Conectarse al wifi**: la cámara lo lee y el móvil se
      conecta a la red.
- [ ] QR **2 · Pedir canciones**: abre la página de pedir.
- [ ] Buscas, pides, y la canción **aparece en la cola del operador**
      con tu nombre.
- [ ] Pides **la misma canción otra vez**: te dice que ya está
      esperándote. No parece un error.
- [ ] La pide **otra persona** con otro nombre: entra sin problema.

---

## 5 · El maestro de ceremonias

- [ ] Los seis botones suenan.
- [ ] Las teclas **1** a **6** también, y **no** se disparan mientras
      escribes en el buscador.
- [ ] Una frase se oye por encima de la música: **la música baja mientras
      habla y vuelve al terminar**.
- [ ] La voz dice el **nombre** de quien canta, no «indefinido».

> Si no hay voz española instalada en Windows, la frase sale con la que
> haya o no sale. No es un fallo del programa.

---

## 6 · Los cuatro temas

Para cada uno —Clásico, Fiesta, Peques, Show— con el botón de la paleta:

- [ ] La tele **se pone el mismo tema sola**.
- [ ] Se lee bien desde el otro lado de la habitación.
- [ ] El termómetro dice algo distinto y coherente con el tema.

Y dos concretas:

- [ ] En **Peques** no hay ningún QR por ninguna parte.
- [ ] En **Show** la carta de reto sale en la tele; al cambiar a otro
      tema, **desaparece**.

---

## 7 · Cuando algo va mal

Merece la pena provocarlo una vez para saber qué se ve:

- [ ] **Cierras la ventana del público** en mitad de una canción. El
      operador sigue funcionando; el diagnóstico lo detecta en un minuto.
- [ ] **Desenchufas el wifi** unos segundos. Sale el aviso de conexión y
      la aplicación no se queda colgada.
- [ ] Vuelves a enchufar: se recupera sola, sin recargar nada.
- [ ] **Recargas el operador** con una canción sonando. Vuelve al mismo
      sitio: la fiesta está en el servidor, no en la pestaña.

---

## 8 · Antes de irte a dormir

- [ ] Ajustes → **Exportar copia**. Es un archivo pequeño y te ahorra un
      disgusto.

---

## Si algo de esto falla

No lo apuntes para luego: **abre `pruebas/pruebas.php` y mira si la suite
lo ve**. Si la suite está en verde y esto en rojo, es que falta una
prueba — y esa prueba vale más que el arreglo, porque el arreglo se hace
una vez y la prueba protege para siempre.
