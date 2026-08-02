/* ═══════════════════════════════════════════════════════════════════
   qr.js — códigos QR sin salir a internet

   Hasta ahora los QR se pedían a api.qrserver.com. Funcionaba, pero
   rompía la única promesa importante de este proyecto: que la noche que
   falle el router la fiesta siga. Y es justo esa noche cuando el QR más
   falta hace, porque lo que codifica es una dirección de la red local
   —que sigue funcionando— y la clave de la wifi.

   Así que se genera aquí. Modo byte, corrección de errores nivel M
   (recupera el 15%: de sobra para una pantalla proyectada), versiones 1
   a 10, que dan hasta 213 caracteres. Una URL de red local y una cadena
   de wifi caben con holgura.

     KL.qr.svg('http://192.168.1.40:8123/pedir.php', {borde:2})  → <svg>

   Está verificado descodificando la salida, no solo mirándola.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

var KL = window.KL || {};
window.KL = KL;

KL.qr = (function () {

  /* ---- Tabla del nivel M, versiones 1 a 10 --------------------------
     [codewords de corrección por bloque, bloques grupo 1, datos por
      bloque grupo 1, bloques grupo 2, datos por bloque grupo 2]      */
  const BLOQUES = {
     1:[10,1,16,0,0],  2:[16,1,28,0,0],  3:[26,1,44,0,0],
     4:[18,2,32,0,0],  5:[24,2,43,0,0],  6:[16,4,27,0,0],
     7:[18,4,31,0,0],  8:[22,2,38,2,39], 9:[22,3,36,2,37],
    10:[26,4,43,1,44]
  };

  /* Centros de los patrones de alineación. La versión 1 no lleva. */
  const ALINEACION = {
    1:[], 2:[6,18], 3:[6,22], 4:[6,26], 5:[6,30],
    6:[6,34], 7:[6,22,38], 8:[6,24,42], 9:[6,26,46], 10:[6,28,50]
  };

  /* Bits sueltos que se añaden al final según la versión. */
  const RESTO = { 1:0, 2:7, 3:7, 4:7, 5:7, 6:7, 7:0, 8:0, 9:0, 10:0 };

  /* ---- Aritmética de Galois GF(256) ---------------------------------- */
  const EXP = new Uint8Array(512), LOG = new Uint8Array(256);
  (function () {
    let x = 1;
    for (let i = 0; i < 255; i++) {
      EXP[i] = x; LOG[x] = i;
      x <<= 1;
      if (x & 0x100) x ^= 0x11d;      // polinomio del estándar
    }
    for (let i = 255; i < 512; i++) EXP[i] = EXP[i - 255];
  })();

  const mul = (a, b) => (a === 0 || b === 0) ? 0 : EXP[LOG[a] + LOG[b]];

  /* Polinomio generador para n codewords de corrección. */
  function generador(n) {
    let g = [1];
    for (let i = 0; i < n; i++) {
      const nuevo = new Array(g.length + 1).fill(0);
      for (let j = 0; j < g.length; j++) {
        /* Los coeficientes van de mayor grado a menor. Multiplicar por x
           deja el coeficiente en su índice; multiplicar por α^i lo
           desplaza uno. Tenerlo al revés da un generador plausible, un
           QR de aspecto impecable y unos codewords de corrección que no
           valen para nada. Pasó. */
        nuevo[j]     ^= g[j];
        nuevo[j + 1] ^= mul(g[j], EXP[i]);
      }
      g = nuevo;
    }
    return g;
  }

  /* Resto de dividir los datos por el generador: eso es Reed-Solomon. */
  function corregir(datos, n) {
    const g = generador(n);
    const r = new Array(datos.length + n).fill(0);
    for (let i = 0; i < datos.length; i++) r[i] = datos[i];
    for (let i = 0; i < datos.length; i++) {
      const c = r[i];
      if (!c) continue;
      for (let j = 0; j < g.length; j++) r[i + j] ^= mul(g[j], c);
    }
    return r.slice(datos.length);
  }

  /* ---- Información de formato y de versión (códigos BCH) -------------- */
  function bitsFormato(mascara) {
    /* Nivel M = 00. Los cinco bits se protegen con un BCH(15,5) y se
       enmascaran con un patrón fijo para que nunca salgan todo ceros. */
    const datos = (0b00 << 3) | mascara;
    let v = datos << 10;
    for (let i = 4; i >= 0; i--) if (v & (1 << (i + 10))) v ^= 0b10100110111 << i;
    return ((datos << 10) | v) ^ 0b101010000010010;
  }

  function bitsVersion(version) {
    let v = version << 12;
    for (let i = 5; i >= 0; i--) if (v & (1 << (i + 12))) v ^= 0b1111100100101 << i;
    return (version << 12) | v;
  }

  /* ---- Bits de datos -------------------------------------------------- */
  function aBytes(texto) {
    return Array.from(new TextEncoder().encode(texto));
  }

  function capacidad(version) {
    const [ec, b1, d1, b2, d2] = BLOQUES[version];
    return b1 * d1 + b2 * d2;
  }

  function versionPara(bytes) {
    for (let v = 1; v <= 10; v++) {
      const cuenta = v < 10 ? 8 : 16;             // bits del contador
      const bits = 4 + cuenta + bytes.length * 8;
      if (bits <= capacidad(v) * 8) return v;
    }
    throw new Error('el texto no cabe en un QR de versión 10');
  }

  function codewords(bytes, version) {
    const bits = [];
    const push = (valor, n) => { for (let i = n - 1; i >= 0; i--) bits.push((valor >> i) & 1); };

    push(0b0100, 4);                              // modo byte
    push(bytes.length, version < 10 ? 8 : 16);
    for (const b of bytes) push(b, 8);

    const total = capacidad(version) * 8;
    for (let i = 0; i < 4 && bits.length < total; i++) bits.push(0);   // terminador
    while (bits.length % 8) bits.push(0);

    const cw = [];
    for (let i = 0; i < bits.length; i += 8) {
      cw.push(bits.slice(i, i + 8).reduce((a, b) => (a << 1) | b, 0));
    }
    /* Relleno: dos bytes que se alternan, así lo manda el estándar. */
    const RELLENO = [0xEC, 0x11];
    let k = 0;
    while (cw.length < capacidad(version)) cw.push(RELLENO[k++ % 2]);
    return cw;
  }

  /* Los bloques se entrelazan: primer byte de cada bloque, segundo de
     cada bloque… Así una mancha en la pantalla no se lleva un bloque
     entero por delante. */
  function entrelazar(cw, version) {
    const [ec, b1, d1, b2, d2] = BLOQUES[version];
    const bloques = [];
    let p = 0;
    for (let i = 0; i < b1; i++) { bloques.push(cw.slice(p, p + d1)); p += d1; }
    for (let i = 0; i < b2; i++) { bloques.push(cw.slice(p, p + d2)); p += d2; }

    const ecs = bloques.map(b => corregir(b, ec));
    const salida = [];
    const maxDatos = Math.max(d1, d2);
    for (let i = 0; i < maxDatos; i++)
      for (const b of bloques) if (i < b.length) salida.push(b[i]);
    for (let i = 0; i < ec; i++)
      for (const e of ecs) salida.push(e[i]);
    return salida;
  }

  /* ---- La matriz ------------------------------------------------------ */
  function nuevaMatriz(n) {
    return { m: Array.from({ length:n }, () => new Array(n).fill(null)), n };
  }

  function ponerFijos(M, version) {
    const n = M.n, m = M.m;

    /* Los tres ojos de las esquinas, con su separador blanco. */
    const ojo = (fila, col) => {
      for (let i = -1; i <= 7; i++) for (let j = -1; j <= 7; j++) {
        const f = fila + i, c = col + j;
        if (f < 0 || c < 0 || f >= n || c >= n) continue;
        const borde  = (i === 0 || i === 6) && j >= 0 && j <= 6;
        const lado   = (j === 0 || j === 6) && i >= 0 && i <= 6;
        const centro = i >= 2 && i <= 4 && j >= 2 && j <= 4;
        m[f][c] = (borde || lado || centro) ? 1 : 0;
      }
    };
    ojo(0, 0); ojo(0, n - 7); ojo(n - 7, 0);

    /* Las dos líneas de puntos que van de un ojo a otro. */
    for (let i = 8; i < n - 8; i++) {
      m[6][i] = m[i][6] = (i % 2 === 0) ? 1 : 0;
    }

    /* Patrones de alineación, salvo donde pisarían un ojo. */
    const centros = ALINEACION[version];
    for (const f of centros) for (const c of centros) {
      if ((f <= 8 && c <= 8) || (f <= 8 && c >= n - 9) || (f >= n - 9 && c <= 8)) continue;
      for (let i = -2; i <= 2; i++) for (let j = -2; j <= 2; j++) {
        m[f + i][c + j] = (Math.abs(i) === 2 || Math.abs(j) === 2 || (i === 0 && j === 0)) ? 1 : 0;
      }
    }

    /* Reservamos el sitio de la información de formato y de versión. */
    for (let i = 0; i < 9; i++) {
      if (m[8][i] === null) m[8][i] = 0;
      if (m[i][8] === null) m[i][8] = 0;
    }
    for (let i = 0; i < 8; i++) {
      if (m[8][n - 1 - i] === null) m[8][n - 1 - i] = 0;
      if (m[n - 1 - i][8] === null) m[n - 1 - i][8] = 0;
    }
    m[n - 8][8] = 1;                       // módulo negro obligatorio

    if (version >= 7) {
      for (let i = 0; i < 6; i++) for (let j = 0; j < 3; j++) {
        m[n - 11 + j][i] = 0; m[i][n - 11 + j] = 0;
      }
    }
  }

  /* Un mapa de qué casillas son de función, para no escribir datos ahí. */
  function mapaFijos(version, n) {
    const M = nuevaMatriz(n);
    ponerFijos(M, version);
    return M.m.map(f => f.map(v => v !== null));
  }

  const MASCARAS = [
    (f, c) => (f + c) % 2 === 0,
    (f)    => f % 2 === 0,
    (f, c) => c % 3 === 0,
    (f, c) => (f + c) % 3 === 0,
    (f, c) => (Math.floor(f / 2) + Math.floor(c / 3)) % 2 === 0,
    (f, c) => (f * c) % 2 + (f * c) % 3 === 0,
    (f, c) => ((f * c) % 2 + (f * c) % 3) % 2 === 0,
    (f, c) => ((f + c) % 2 + (f * c) % 3) % 2 === 0
  ];

  /* Zigzag desde abajo a la derecha, saltándose la columna 6. */
  function colocarDatos(m, fijo, bits, n) {
    let i = 0, arriba = true;
    for (let col = n - 1; col > 0; col -= 2) {
      if (col === 6) col--;
      for (let paso = 0; paso < n; paso++) {
        const fila = arriba ? n - 1 - paso : paso;
        for (const c of [col, col - 1]) {
          if (fijo[fila][c]) continue;
          m[fila][c] = i < bits.length ? bits[i] : 0;
          i++;
        }
      }
      arriba = !arriba;
    }
  }

  /* Las cuatro reglas de penalización del estándar. Se prueba cada
     máscara y gana la que deja el dibujo menos confuso para el lector. */
  function penalizacion(m, n) {
    let p = 0;

    const racha = (get) => {
      for (let a = 0; a < n; a++) {
        let seguidos = 1;
        for (let b = 1; b < n; b++) {
          if (get(a, b) === get(a, b - 1)) seguidos++;
          else { if (seguidos >= 5) p += 3 + (seguidos - 5); seguidos = 1; }
        }
        if (seguidos >= 5) p += 3 + (seguidos - 5);
      }
    };
    racha((f, c) => m[f][c]);
    racha((c, f) => m[f][c]);

    for (let f = 0; f < n - 1; f++) for (let c = 0; c < n - 1; c++) {
      const v = m[f][c];
      if (v === m[f][c+1] && v === m[f+1][c] && v === m[f+1][c+1]) p += 3;
    }

    const PATRON = [1,0,1,1,1,0,1,0,0,0,0];
    const buscar = (get) => {
      for (let a = 0; a < n; a++) for (let b = 0; b <= n - 11; b++) {
        let igual = true;
        for (let k = 0; k < 11; k++) if (get(a, b + k) !== PATRON[k]) { igual = false; break; }
        if (igual) p += 40;
      }
    };
    buscar((f, c) => m[f][c]);
    buscar((c, f) => m[f][c]);

    let negros = 0;
    for (let f = 0; f < n; f++) for (let c = 0; c < n; c++) negros += m[f][c];
    const porcentaje = negros * 100 / (n * n);
    p += Math.floor(Math.abs(porcentaje - 50) / 5) * 10;
    return p;
  }

  function ponerFormato(m, n, mascara) {
    const bits = bitsFormato(mascara);
    const b = i => (bits >> i) & 1;
    /* Ojo con las coordenadas: aquí m[fila][columna]. Casi toda la
       documentación del estándar da los sitios como (x, y) —columna
       primero—, y transponerlos por descuido produce un QR que se ve
       perfecto, que los lectores detectan… y que no descodifica nadie.
       Pasó. */

    /* Copia 1: alrededor del ojo de arriba a la izquierda. */
    for (let i = 0; i <= 5; i++) m[i][8] = b(i);
    m[7][8] = b(6); m[8][8] = b(7); m[8][7] = b(8);
    for (let i = 9; i <= 14; i++) m[8][14 - i] = b(i);

    /* Copia 2: repartida entre los otros dos ojos, por si una se estropea. */
    for (let i = 0; i <= 7; i++) m[8][n - 1 - i] = b(i);
    for (let i = 8; i <= 14; i++) m[n - 15 + i][8] = b(i);

    m[n - 8][8] = 1;                       // módulo negro obligatorio
  }

  function ponerVersion(m, n, version) {
    if (version < 7) return;
    const bits = bitsVersion(version);
    for (let i = 0; i < 18; i++) {
      const bit = (bits >> i) & 1;
      const f = Math.floor(i / 3), c = i % 3;
      m[n - 11 + c][f] = bit;
      m[f][n - 11 + c] = bit;
    }
  }

  /* ---- La función que hace todo el trabajo ---------------------------- */
  function matriz(texto, forzar) {
    const bytes = aBytes(texto);
    const version = versionPara(bytes);
    const n = 17 + version * 4;

    const datos = entrelazar(codewords(bytes, version), version);
    const bits = [];
    for (const cw of datos) for (let i = 7; i >= 0; i--) bits.push((cw >> i) & 1);
    for (let i = 0; i < RESTO[version]; i++) bits.push(0);

    const fijo = mapaFijos(version, n);

    let mejor = null, mejorP = Infinity;
    for (let k = 0; k < 8; k++) {
      if (forzar !== undefined && k !== forzar) continue;
      const M = nuevaMatriz(n);
      ponerFijos(M, version);
      const m = M.m;
      colocarDatos(m, fijo, bits, n);
      for (let f = 0; f < n; f++) for (let c = 0; c < n; c++) {
        if (!fijo[f][c] && MASCARAS[k](f, c)) m[f][c] ^= 1;
      }
      ponerVersion(m, n, version);
      ponerFormato(m, n, k);
      const p = penalizacion(m, n);
      if (p < mejorP) { mejorP = p; mejor = m; }
    }
    return mejor;
  }

  /* Un SVG de un solo trazado: pesa poco y escala a cualquier tamaño sin
     pixelarse, que es justo lo que hace falta en una tele. */
  function svg(texto, opc) {
    opc = opc || {};
    const borde = opc.borde ?? 2;       // el estándar pide 4; 2 basta en pantalla
    const m = matriz(texto);
    const n = m.length, total = n + borde * 2;
    let d = '';
    for (let f = 0; f < n; f++) for (let c = 0; c < n; c++) {
      if (m[f][c]) d += `M${c + borde} ${f + borde}h1v1h-1z`;
    }
    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${total} ${total}" `
         + `shape-rendering="crispEdges" style="width:100%;height:100%;display:block">`
         + `<rect width="${total}" height="${total}" fill="${opc.fondo || '#fff'}"/>`
         + `<path d="${d}" fill="${opc.tinta || '#000'}"/></svg>`;
  }

  /* Cadena estándar para que el móvil se conecte a la wifi al escanear.
     Los caracteres \ ; , : y " hay que escaparlos o la clave llega mal. */
  function wifi(ssid, clave, tipo) {
    const esc = s => String(s || '').replace(/([\\;,:"])/g, '\\$1');
    return `WIFI:T:${tipo || (clave ? 'WPA' : 'nopass')};S:${esc(ssid)};P:${esc(clave)};;`;
  }

  /* Solo para las pruebas: fuerza una máscara concreta. */
  function __debug(texto, k){ return matriz(texto, k); }

  return { matriz, svg, wifi, __debug };
})();
