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

  /* ── Versión inglesa de cada tema ────────────────────────────────────
     Mismo reparto, mismas reglas —nada de valorar cómo canta nadie en
     Peques/Kids—, solo el idioma. Vive aquí y no en idiomas/en.json
     porque esto no es contenido de UI por dispositivo: es el TONO de la
     fiesta, compartido por todas las pantallas, igual que su versión en
     español. Si falta una fila, de() cae al español antes que dejar un
     hueco en blanco — ver de() más abajo. */
  const CLASICO_EN = {
    esperaTitulo:  '🎤 Karaoke',
    esperaSub:     'Choose a song on the computer and start the party.',
    esperaVacia:   'Scan the QR and request yours.',
    ahoraCanta:    'Now <span class="ac">singing</span>',
    alMicro:       '  ·  at the mic',
    finTitulo:     '👏 <span class="ac">Nice!</span>',
    aContinuacion: 'Up next: ',
    seAcabo:       'That\'s the whole queue',
    proximas:      'Up next'
  };

  const FIESTA_EN = Object.assign({}, CLASICO_EN, {
    esperaTitulo:  '🎉 <span class="ac">Party</span>',
    esperaSub:     'You choose the next song.',
    ahoraCanta:    'At the mic <span class="ac">now</span>',
    finTitulo:     '🎉 <span class="ac">Ovation!</span>',
    proximas:      'Coming up'
  });

  const KIDS_EN = {
    esperaTitulo:  '🎤 <span class="ac">Sing time!</span>',
    esperaSub:     'Choose a song.',
    esperaVacia:   'Find a grown-up and ask them to type it for you.',
    ahoraCanta:    '🎶 Now singing',
    alMicro:       '',
    finTitulo:     '🌟 <span class="ac">Thanks for sharing your song!</span>',
    aContinuacion: 'Now singing: ',
    seAcabo:       'We\'ve sung them all!',
    proximas:      'Singing next'
  };

  const SHOW_EN = Object.assign({}, CLASICO_EN, {
    esperaTitulo:  '🏆 <span class="ac">The Show</span>',
    esperaSub:     'Bring on the next act.',
    esperaVacia:   'Scan the QR and enter the contest.',
    ahoraCanta:    'On <span class="ac">stage</span>',
    alMicro:       '  ·  their turn',
    finTitulo:     '🏆 <span class="ac">What an act!</span>',
    aContinuacion: 'Next act: ',
    seAcabo:       'End of the show',
    proximas:      'Coming up next'
  });

  const TODOS_EN = { clasico: CLASICO_EN, fiesta: FIESTA_EN, kids: KIDS_EN, show: SHOW_EN };

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

  const PREGUNTAS_EN = {
    vaciarCola: d =>
      `Clear the ${d.espacio} queue?\n\n` +
      `That's ${d.cuantas} songs. The other space isn't touched.`,

    borrarDescarga: d =>
      `Delete the downloaded file for «${d.titulo}»?\n\n` +
      `The song stays in the list; it'll play from YouTube again.`,

    yaEstaEnLaCola: d =>
      `«${d.titulo}» is already in the queue.\n\n` +
      `If it's for someone else, go ahead: two performances of the same song ` +
      `are two performances. Add it again?`,

    borrarTodasLasDescargas: () =>
      `Delete ALL downloaded videos?\n\n` +
      `The songs aren't lost: they'll play from YouTube again.`,

    vaciarHistorial: () =>
      `Clear the history of what's been sung tonight?\n\n` +
      `The queue and the library aren't touched, only who's already sung is forgotten.`
  };

  /* Por dispositivo, igual que idioma.js — cada aparato lee su propio
     idioma para este mismo tono compartido de fiesta. */
  function idiomaActual() {
    return (window.KL && KL.idioma && KL.idioma.actual()) || 'es';
  }

  /* Si alguien pide una pregunta que no existe, se ve. Devolver «¿Seguro?»
     en silencio dejaría un diálogo genérico delante de un borrado, que es
     el peor sitio posible para un texto de relleno. */
  function pregunta(clave, datos) {
    const dicc = idiomaActual() === 'en' ? PREGUNTAS_EN : PREGUNTAS;
    const f = dicc[clave] || PREGUNTAS[clave];
    if (!f) return '¿Seguro? (falta el texto «' + clave + '» en textos.js)';
    return f(datos || {});
  }

  /* Si falta una fila se cae al Clásico en vez de quedarse en blanco: un
     tema a medias tiene que verse raro, no vacío. Y si falta la versión
     inglesa de una fila concreta, cae al español antes que al Clásico:
     mejor una frase en el idioma que no toca que un hueco en blanco. */
  function de(tema, clave) {
    const t = TODOS[tema] || CLASICO;
    const base = t[clave] !== undefined ? t[clave] : CLASICO[clave];
    if (idiomaActual() !== 'en') return base;
    const tEn = TODOS_EN[tema] || CLASICO_EN;
    return tEn[clave] !== undefined ? tEn[clave]
         : (CLASICO_EN[clave] !== undefined ? CLASICO_EN[clave] : base);
  }

  return { de, pregunta, TODOS, PREGUNTAS };
})();
