/* ═══════════════════════════════════════════════════════════════════
   textos.js — lo que la aplicación dice, según el tema

   Un tema no es un paquete de colores: es una manera de hablar. En Peques
   la aplicación no dice «Fin de actuación», dice «¡Gracias por compartir
   tu canción!».

   Está aquí y no repartido por las pantallas por dos motivos. El primero
   es obvio: añadir un tema de Navidad tiene que ser añadir una fila. El
   segundo importa más — **cuando los textos están sueltos en el código,
   solo se traduce la mitad**, y una pantalla que mezcla dos idiomas es
   peor que una que use solo el aburrido.

   ── La regla que no se rompe en Peques ──────────────────────────────
   Ningún mensaje valora cómo canta nadie, **ni siquiera para bien**. Un
   «¡muy bien!» parece un elogio y funciona como una nota: la vez
   siguiente el crío sale esperando a ver qué le dicen. Se celebra haber
   salido, que es lo único que depende de él.

   Eso no es estilo: es la diferencia entre que un niño de siete años
   quiera repetir o no vuelva a coger el micro en su vida.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

var KL = window.KL || {};
window.KL = KL;

KL.TEXTOS = (function () {

  const CLASICO = {
    esperaTitulo:  '🎤 Karaoke',
    esperaSub:     'Elige una canción en el ordenador y empieza la fiesta.',
    esperaVacia:   'Escanea el QR y pide la tuya.',
    ahoraCanta:    'Ahora <span class="ac">canta</span>',
    alMicro:       '  ·  al micro',
    finTitulo:     '👏 <span class="ac">¡Bien!</span>',
    aContinuacion: 'A continuación: ',
    seAcabo:       'Se acabó la cola',
    proximas:      'A continuación'
  };

  const FIESTA = Object.assign({}, CLASICO, {
    esperaTitulo:  '🎉 <span class="ac">Fiesta</span>',
    esperaSub:     'La siguiente canción la eliges tú.',
    ahoraCanta:    'Al micro <span class="ac">ahora</span>',
    finTitulo:     '🎉 <span class="ac">¡Ovación!</span>',
    proximas:      'Después van'
  });

  /* ── Peques: repasado palabra por palabra ──────────────────────────
     La regla no es «mensajes bonitos». Es más estrecha y más útil:
     **ningún mensaje puede leerse como una valoración**, ni siquiera una
     buena. «¡Muy bien!» y «¡Perfecto!» parecen elogios y son notas: si
     esta vez te dicen «muy bien», la siguiente estás esperando a ver qué
     te dicen. Y el crío que no cantó tan bien lo nota.

     Lo que sí se puede celebrar es **haber salido**, que es lo único que
     depende de quien canta y lo único que hay que reforzar a esa edad.
     De ahí «¡Gracias por cantar!» en vez de «¡Lo has hecho genial!»: la
     segunda todavía opina sobre el resultado.

     Tres cosas más que se han quitado a propósito:

     · **La comparación implícita.** «¿Quién será el próximo artista?»
       está bien; «¿quién se atreve?» no, porque convierte cantar en un
       reto y deja al que no se atreve retratado delante de todos.
     · **El imperativo seco.** «Escanea el QR» no aparece —en Peques no
       hay QR— y el resto de instrucciones se dicen en positivo y en
       primera persona del plural.
     · **La prisa.** Ni «rápido», ni «ya», ni cuentas atrás. El estado
       PREPARADA existe justo para que no haya prisa.

     Y los emojis van al principio, no repartidos: son un icono grande
     que localiza el mensaje, no decoración dentro de la frase. */
  const KIDS = {
    /* Y menos texto. Un niño de siete años mira la tele desde tres
       metros, con ruido y gente delante: lo que no se entienda sin leer,
       no se entiende. El emoji no es adorno — es la mitad del mensaje. */
    esperaTitulo:  '🎤 <span class="ac">¡A cantar!</span>',
    esperaSub:     'Elige una canción.',
    esperaVacia:   'Busca a un mayor y pídele que la escriba por ti.',
    ahoraCanta:    '🎶 Ahora canta',
    alMicro:       '',
    /* Ni «bien» ni «genial». Y tampoco «gracias por cantar», que era la
       versión anterior: «cantar» todavía señala la actuación, y lo que se
       quiere señalar es haber participado. «Compartir tu canción» pone el
       foco en el gesto —traer algo tuyo a la sala— y no en cómo ha
       sonado. Es un matiz pequeño y es justo donde vive la evaluación. */
    finTitulo:     '🌟 <span class="ac">¡Gracias por compartir tu canción!</span>',
    /* «Le toca a» suena a obligación y a turno impuesto. «Ahora canta»
       dice lo mismo sin empujar a nadie. */
    aContinuacion: 'Ahora canta: ',
    seAcabo:       '¡Ya hemos cantado todas!',
    proximas:      'Después cantan'
  };

  /* Show: estética de concurso. Ni una palabra sobre cómo canta nadie
     tampoco aquí — se habla de «números» y de escenario, que es como se
     llama a una actuación en un espectáculo y no una nota. */
  const SHOW = Object.assign({}, CLASICO, {
    esperaTitulo:  '🏆 <span class="ac">El Show</span>',
    esperaSub:     'Que salga el siguiente número.',
    esperaVacia:   'Escanea el QR y apúntate al concurso.',
    ahoraCanta:    'En el <span class="ac">escenario</span>',
    alMicro:       '  ·  su turno',
    finTitulo:     '🏆 <span class="ac">¡Qué número!</span>',
    aContinuacion: 'Siguiente número: ',
    seAcabo:       'Fin del concurso',
    proximas:      'Siguientes números'
  });

  const TODOS = { clasico: CLASICO, fiesta: FIESTA, kids: KIDS, show: SHOW };

  /* ── Las preguntas de «¿seguro?» ────────────────────────────────────
     Estas NO cambian con el tema, y durante un tiempo ese fue el motivo
     que me di para dejarlas sueltas en el código. Era un mal motivo:
     `confirm()` es un mecanismo de interacción del navegador, no un
     texto de presentación, y que el mecanismo no se pueda tematizar no
     dice nada sobre dónde debe vivir lo que se lee dentro.

     Están aquí por lo mismo que todo lo demás: un texto escrito en la
     línea donde se usa es un texto que nadie vuelve a leer. Y estos tres
     son los únicos de la aplicación que preceden a algo que no se puede
     deshacer, así que son precisamente los que hay que poder revisar de
     una sentada.

     La regla que cumplen los tres: **decir qué se pierde y qué no**.
     «¿Seguro?» a secas no es una pregunta, es un peaje. */
  const PREGUNTAS = {
    vaciarCola: d =>
      `¿Vaciar la cola de ${d.espacio}?\n\n` +
      `Son ${d.cuantas} canciones. Las de los otros espacios no se tocan.`,

    borrarDescarga: d =>
      `¿Borrar el archivo descargado de «${d.titulo}»?\n\n` +
      `La canción sigue en la lista; volverá a sonar desde YouTube.`,

    /* No es un error: es que en la cola ya hay alguien con esa canción.
       Dos personas cantando lo mismo son dos actuaciones distintas, así
       que se pregunta en vez de impedirlo. */
    yaEstaEnLaCola: d =>
      `«${d.titulo}» ya está en la cola.\n\n` +
      `Si es para otra persona, adelante: dos actuaciones de la misma canción ` +
      `son dos actuaciones. ¿La añado otra vez?`,

    borrarTodasLasDescargas: () =>
      `¿Borrar TODOS los vídeos descargados?\n\n` +
      `Las canciones no se pierden: volverán a sonar desde YouTube.`,

    vaciarHistorial: () =>
      `¿Vaciar el historial de lo cantado esta fiesta?\n\n` +
      `La cola y la biblioteca no se tocan, solo se olvida quién ha cantado ya.`
  };

  /* Si alguien pide una pregunta que no existe, se ve. Devolver «¿Seguro?»
     en silencio dejaría un diálogo genérico delante de un borrado, que es
     el peor sitio posible para un texto de relleno. */
  function pregunta(clave, datos) {
    const f = PREGUNTAS[clave];
    if (!f) return '¿Seguro? (falta el texto «' + clave + '» en textos.js)';
    return f(datos || {});
  }

  /* Si falta una fila se cae al Clásico en vez de quedarse en blanco: un
     tema a medias tiene que verse raro, no vacío. */
  function de(tema, clave) {
    const t = TODOS[tema] || CLASICO;
    return t[clave] !== undefined ? t[clave] : CLASICO[clave];
  }

  return { de, pregunta, TODOS, PREGUNTAS };
})();
