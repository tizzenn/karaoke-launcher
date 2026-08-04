/* ═══════════════════════════════════════════════════════════════════
   filtro.js — traducir lo que escribe una persona a lo que entiende YouTube

   ── Por qué existe ──────────────────────────────────────────────────
   Esto ya estaba, y estaba mal de dos maneras a la vez.

   Existía un «sufijo» por espacio —«karaoke» en Karaoke, vacío en la
   Cabina DJ— que se pegaba al final de cada búsqueda. Funcionaba, y era
   **invisible**: vivía en un campo dentro de Ajustes que no abre nadie.
   Es el mismo fallo que tenían los temas: una función que no se sabe que
   existe no existe.

   Y existía el espacio Freestyle, que se diferenciaba de Karaoke en
   exactamente una cosa: buscaba «instrumental» en vez de «karaoke». Un
   filtro disfrazado de espacio, con sus dos botones —Instrumentales,
   Bases— que eran este archivo escrito a mano y solo para él.

   Las dos cosas se arreglan igual: **el filtro se saca a la vista y se
   generaliza**. Quedan dos espacios de verdad, que sí se comportan
   distinto (uno encadena las canciones solas y el otro no), y una caja de
   texto donde se dice qué se busca.

   ── La regla de honestidad ──────────────────────────────────────────
   > **Aquí no se inventa ningún operador.** Todo lo que se acepta hace
   > algo de verdad al llegar a YouTube.

   Es fácil escribir un buscador que finja entender. Se acepta `IN`, se
   acepta `NEAR`, se acepta lo que sea, se manda tal cual y YouTube lo
   trata como una palabra más — así que buscar «karaoke IN español»
   devuelve resultados, parecen razonables, y nadie descubre nunca que
   `IN` no ha hecho nada. Un buscador que miente es peor que uno tonto:
   con el tonto sabes a qué atenerte.

   Lo que la API de YouTube entiende de verdad en `q`, y nada más:

     · el espacio           = Y (todas las palabras)
     · `|`                  = O (cualquiera de ellas)
     · `-palabra`           = sin esa palabra
     · `"frase exacta"`     = tal cual, en ese orden

   Y lo que escribe una persona es `AND`, `OR` y `NO`. Este archivo es el
   puente, y solo eso.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

var KL = window.KL || {};
window.KL = KL;

KL.filtro = (function () {

  /* Los tres en varios idiomas y con el símbolo de siempre. Alguien va a
     escribir «o» en minúscula estando la aplicación en español, y tener
     razón. Que funcione cuesta una fila en esta tabla. */
  const O  = ['or', 'o', '||', '|'];
  const Y  = ['and', 'y', '&&', '&', '+'];
  const NO = ['not', 'no', 'sin'];

  /* Trocear respetando las comillas: dentro de «"..."» un espacio no
     separa nada. Sin esto, `"karaoke version"` se parte en dos y la frase
     exacta deja de serlo justo cuando más falta hace. */
  function trocear(texto) {
    const out = [];
    let actual = '', dentro = false;
    for (const c of String(texto || '')) {
      if (c === '"') { dentro = !dentro; actual += c; continue; }
      if (!dentro && /\s/.test(c)) { if (actual) out.push(actual); actual = ''; continue; }
      actual += c;
    }
    if (actual) out.push(actual);
    return out;
  }

  /* Una comilla sin cerrar es lo más normal del mundo escribiendo
     deprisa. No es un error que haya que enseñar: se cierra sola. */
  function cerrarComillas(t) {
    const n = (t.match(/"/g) || []).length;
    return n % 2 ? t + '"' : t;
  }

  /* ── El puente ────────────────────────────────────────────────────
     Devuelve la cadena que se le pega a la búsqueda. No se toca `q`: lo
     que escribe la persona en el buscador se respeta tal cual, y el
     filtro va detrás. Son dos campos porque son dos cosas: QUÉ busco y
     CÓMO acoto. */
  function aConsulta(expresion) {
    const piezas = trocear(cerrarComillas(expresion));
    const out = [];
    let negar = false, unirConO = false;

    for (const p of piezas) {
      const bajo = p.toLowerCase();

      if (O.includes(bajo))  { unirConO = true; continue; }
      /* Y es lo que pasa por defecto entre dos palabras, así que la
         palabra AND no traduce a nada: se reconoce para que quien la
         escriba no la vea aparecer como término de búsqueda. */
      if (Y.includes(bajo))  { unirConO = false; continue; }
      if (NO.includes(bajo)) { negar = true; continue; }

      let t = p;
      if (t[0] === '-') { negar = true; t = t.slice(1); }
      if (!t) continue;

      if (negar) {
        /* Excluir y unir con O a la vez no significa nada —«sin esto o
           sin lo otro» no acota— y el que gana es el excluir, que es lo
           que la persona ha dicho más claro. */
        out.push('-' + t);
        negar = false; unirConO = false;
        continue;
      }

      if (unirConO && out.length) {
        out[out.length - 1] += '|' + t;
        unirConO = false;
      } else {
        out.push(t);
      }
    }
    return out.join(' ');
  }

  /* Lo que se enseña debajo del campo mientras se escribe. No es
     decoración: es la única manera de que alguien aprenda la sintaxis sin
     leer nada. Ve `karaoke|playback` y entiende qué ha hecho `OR`. */
  function explicar(expresion) {
    const q = aConsulta(expresion);
    if (!q) return '';
    return 'Se busca: ' + q;
  }

  /* ¿Tiene sentido lo que hay escrito? Solo se avisa de lo que de verdad
     no hace nada, no de lo que a mí me parezca raro. */
  function aviso(expresion) {
    const piezas = trocear(String(expresion || ''));
    const ultima = (piezas[piezas.length - 1] || '').toLowerCase();
    if (O.includes(ultima) || Y.includes(ultima) || NO.includes(ultima))
      return 'Falta lo que va después de «' + piezas[piezas.length - 1] + '».';
    if (!aConsulta(expresion) && String(expresion || '').trim())
      return 'Eso no acota nada: se buscará tal cual escribas arriba.';
    return '';
  }

  /* Los filtros de fábrica de cada espacio. En la Cabina DJ vacío a
     propósito: quien pincha música quiere la canción, no una versión de
     nada. La pista de lo que se puede escribir va en el `placeholder`,
     que sugiere sin imponer. */
  const POR_ESPACIO = {
    karaoke: { valor: 'karaoke',
               pista: 'karaoke OR playback OR "karaoke version"' },
    dj:      { valor: '',
               pista: 'backtrack OR beat OR instrumental' }
  };

  /* ── Perfiles de búsqueda ─────────────────────────────────────────
     Un perfil es **este mismo campo con nombre**. Nada más. Se planteó
     como una función aparte —una lista de perfiles, con su panel y su
     pantalla— y hubiera sido construir dos veces lo mismo: al final un
     perfil es un filtro que alguien quiere volver a usar.

     Los de fábrica no son un adorno: son la documentación que sí se lee.
     Quien despliega la lista y ve «Sin coros en directo → -live -cover»
     aprende para qué sirve el «-» sin abrir ningún manual, y de paso
     descubre que el campo existe.

     Y son por espacio, porque un perfil de karaoke en la Cabina DJ no
     significa nada. */
  const PERFILES = {
    karaoke: [
      { nombre: 'Karaoke normal',        filtro: 'karaoke' },
      { nombre: 'Karaoke o playback',    filtro: 'karaoke OR playback OR "karaoke version"' },
      { nombre: 'Con letra en pantalla', filtro: 'karaoke OR "con letra" OR lyrics' },
      { nombre: 'Sin versiones en directo', filtro: 'karaoke -live -cover -concierto' },
      { nombre: 'Instrumental (lo que era Freestyle)', filtro: 'instrumental OR "backing track"' }
    ],
    dj: [
      { nombre: 'Tal cual (sin filtro)', filtro: '' },
      { nombre: 'Videoclip oficial',     filtro: '"video oficial" OR "official video"' },
      { nombre: 'Para bailar',           filtro: 'remix OR extended OR "dj set"' },
      { nombre: 'Bases y beats',         filtro: 'backtrack OR beat OR instrumental' }
    ]
  };

  /* Los que guarda el operador se pegan detrás de los de fábrica. No se
     mezclan ni se ordenan juntos: quien guardó «lo mío» quiere
     encontrarlo donde lo dejó, no alfabetizado entre los nuestros. */
  function perfilesDe(espacio, propios) {
    const base = PERFILES[espacio] || [];
    const mios = (propios && propios[espacio]) || [];
    return base.concat(mios.map(p => Object.assign({ propio: true }, p)));
  }

  return { aConsulta, explicar, aviso, trocear,
           POR_ESPACIO, PERFILES, perfilesDe, O, Y, NO };
})();
