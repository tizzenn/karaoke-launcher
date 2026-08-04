# Protocolo de sesión QA completa antes de publicar

Pendiente de ejecutar. Encargo tal cual lo dio el usuario el 2026-08-03,
guardado íntegro para no tener que reconstruirlo. No se ha ejecutado
todavía — es un trabajo de horas, con Chrome real, instalación desde cero
y un informe final, no algo que se haga de pasada dentro de otra tarea.

**Antes de lanzarlo**, conviene revisar si sigue encajando con lo que
diga `TRASPASO-v1.1.md` §1 en ese momento: puede que para entonces ya se
haya cubierto parte a mano.

---

Eres el responsable de Calidad (QA Lead), Arquitectura y Desarrollo de este proyecto.

Durante TODA esta sesión actuarás como un miembro más del equipo.

No quiero una revisión puntual.

Quiero una sesión completa de pruebas, corrección de errores, mejora del código y validación final.

Tu objetivo es dejar el proyecto lo más estable posible para una publicación pública.

=========================================================
FORMA DE TRABAJAR
=========================================================

Trabaja completamente de forma autónoma.

NO esperes mi autorización para corregir errores.

Si encuentras un fallo:

1. Reprodúcelo.
2. Localiza la causa.
3. Modifica el código.
4. Comprueba que realmente queda solucionado.
5. Comprueba que no rompe ninguna otra funcionalidad.
6. Continúa con las siguientes pruebas.

Este ciclo debe repetirse continuamente hasta finalizar todas las fases.

=========================================================
QUÉ PUEDES MODIFICAR
=========================================================

Puedes modificar libremente:

- PHP
- HTML
- CSS
- JavaScript
- configuración
- pruebas
- documentación
- scripts BAT
- estructura interna

Siempre que:

- mantengas la arquitectura existente
- no elimines funcionalidades
- no introduzcas regresiones
- no rompas compatibilidad

No hagas refactorizaciones grandes si no aportan estabilidad.

No cambies nombres por preferencias personales.

No reestructures el proyecto únicamente porque te guste más.

=========================================================
QUÉ DEBES HACER SI ENCUENTRAS UN PROBLEMA
=========================================================

Nunca te limites a decir:

"Existe este fallo."

Debes:

✔ reproducirlo

✔ explicar por qué ocurre

✔ arreglarlo

✔ volver a probarlo

✔ documentarlo

=========================================================
MODO DE PRUEBA
=========================================================

Usa Chrome.

Compórtate como un usuario real.

No leas únicamente el código.

Pulsa botones.

Escribe.

Borra.

Arrastra.

Redimensiona.

Cambia pestañas.

Recarga.

Abre varias ventanas.

Haz acciones rápidas.

Haz acciones lentas.

Intenta romper la aplicación.

=========================================================
FASE 1
INSTALACIÓN
=========================================================

Empieza desde un equipo limpio.

Ejecuta:

preparar.bat

Después:

karaoke.bat

Comprueba:

- PHP
- rutas
- permisos
- carpetas
- creación de datos
- consola
- errores

Corrige cualquier problema encontrado.

=========================================================
FASE 2
ARRANQUE
=========================================================

Verifica:

Operador

Público

Cantante

Ajustes

Pantallas independientes

Sin errores JS

Sin errores PHP

Sin errores 404

Sin errores CORS

=========================================================
FASE 3
BUSCADOR
=========================================================

Probar absolutamente todo.

Buscar por:

- artista
- canción
- varias palabras
- espacios
- mayúsculas
- minúsculas
- acentos
- ñ
- emojis
- texto vacío
- texto enorme
- copiar/pegar
- URL youtube completa
- youtu.be
- watch?v=
- playlist
- URL inválida
- texto basura
- búsquedas repetidas

Probar con API válida y sin API.

Si se pega una URL de YouTube debe comportarse de forma coherente.

=========================================================
FASE 4
BIBLIOTECA
=========================================================

Añadir

Eliminar

Favoritos

Descargar

Volver a descargar

Importar

Exportar

Duplicados

Miniaturas

Ordenaciones

Filtros

=========================================================
FASE 5
COLA
=========================================================

Añadir canciones

Eliminar

Mover

Arrastrar

Vaciar

Duplicados

Mismo cantante

Distintos cantantes

Reordenar

=========================================================
FASE 6
MODO KARAOKE
=========================================================

Simular una fiesta completa.

Calentamiento.

Llegada de canciones.

Lanzar actuación.

Pantalla cantante.

Pantalla público.

Pausa.

Reanudar.

Reiniciar.

Cancelar.

Final.

Volver automáticamente al operador.

Repetir al menos 20 actuaciones.

=========================================================
FASE 7
MODO CABINA DJ
=========================================================

Compararlo con Karaoke.

Comprobar que:

- reproduce automáticamente
- enlaza canciones
- mantiene cola
- no requiere operador

=========================================================
FASE 8
PANTALLA PÚBLICO
=========================================================

Comprobar:

Termómetro

Cola

Próximas canciones

Notificaciones

QR

Mensajes

Actualización automática

Pantalla completa

=========================================================
FASE 9
MÓVIL
=========================================================

Entrar mediante QR.

Solicitar canciones.

Actualizar.

Duplicados.

API.

Sin API.

Recargar.

Cerrar navegador.

Volver.

=========================================================
FASE 10
AJUSTES
=========================================================

Modificar TODOS.

Guardar TODOS.

Cerrar.

Abrir.

Verificar persistencia.

=========================================================
FASE 11
TEMAS
=========================================================

Classic

Fiesta

Kids

Show

Revisar:

alineaciones

iconos

contraste

animaciones

responsive

listas

tarjetas

pantalla cantante

pantalla público

=========================================================
FASE 12
RESPONSIVE
=========================================================

320

480

768

1024

1366

1920

Ultrawide

Zoom 125%

150%

175%

=========================================================
FASE 13
ROBUSTEZ
=========================================================

Cerrar ventanas.

Cerrar público.

Cerrar cantante.

Recargar durante reproducción.

Desconectar Internet.

API errónea.

Vídeo privado.

Vídeo eliminado.

Vídeo bloqueado.

Archivos inexistentes.

=========================================================
FASE 14
CONSOLA
=========================================================

La consola debe quedar limpia.

No deben existir:

Errores JS

Errores PHP

404

500

Unhandled Promise

Warnings importantes

=========================================================
FASE 15
ERGONOMÍA
=========================================================

Actúa como operador profesional de karaoke.

Evalúa:

Número de clics.

Economía de movimientos.

Tamaño de botones.

Colores.

Legibilidad.

Información.

Estados.

Pantallas.

Flujo.

Facilidad de aprendizaje.

Haz pequeñas mejoras cuando aporten valor y no rompan la arquitectura.

=========================================================
FASE 16
REGRESIONES
=========================================================

Cada vez que modifiques código:

Repite automáticamente las pruebas relacionadas.

No des un error por solucionado hasta comprobarlo.

=========================================================
FASE 17
CRITERIO DE FINALIZACIÓN
=========================================================

No des la sesión por terminada mientras existan errores críticos o altos.

Si encuentras nuevos fallos derivados de una corrección, vuelve atrás y continúa.

=========================================================
INFORME FINAL
=========================================================

Genera un informe dividido en:

1. Errores críticos encontrados.
2. Errores altos.
3. Errores medios.
4. Errores bajos.
5. Mejoras implementadas automáticamente.
6. Mejoras recomendadas para la v1.2.

Para cada incidencia incluye:

- descripción
- pasos para reproducirla
- causa
- solución aplicada
- archivos modificados
- comprobaciones realizadas
- riesgo de regresión

Termina con una valoración objetiva:

- Estabilidad (0–10)
- Usabilidad (0–10)
- Robustez (0–10)
- Preparación para publicar la v1.1 (0–10)

Finalmente, indica si considerarías esta versión apta para utilizar durante una fiesta real de 4–5 horas con público, justificando la respuesta con los resultados de las pruebas.
