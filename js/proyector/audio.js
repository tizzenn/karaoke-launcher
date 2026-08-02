/* ===================================================================
   audio.js — la pantalla reacciona al ruido de la sala

   Por el micrófono, no por el vídeo: el audio de un iframe de YouTube es
   de otro origen y la Web Audio API no lo alcanza. Funciona porque la
   página está en localhost, que cuenta como contexto seguro.

   La intensidad se separa en dos —cantando y en el calentamiento—
   porque durante el calentamiento interesa que se note MÁS: es cuando la
   gente descubre que la pantalla responde a lo que hacen.
   =================================================================== */
'use strict';

/* ---- Halo que respira -------------------------------------------------
   Dos fuentes posibles y una degradación honesta:
     · si la pista está descargada, se analiza el propio <video>;
     · si no, el micrófono de la sala (y recoge también al público);
     · si no hay ninguno, no se enseña halo. Nada de fingirlo.        */
let ctxAudio = null, analizador = null, datos = null, fuenteVideo = null;

function arrancarAudio(){
  if(ctxAudio) return ctxAudio;
  ctxAudio = new (window.AudioContext || window.webkitAudioContext)();
  analizador = ctxAudio.createAnalyser();
  analizador.fftSize = 512;
  analizador.smoothingTimeConstant = .75;
  datos = new Uint8Array(analizador.frequencyBinCount);
  $('#halo').classList.remove('no');
  bucleHalo();
  return ctxAudio;
}

async function conectarMicro(){
  try{
    const flujo = await navigator.mediaDevices.getUserMedia({
      audio:{ echoCancellation:false, noiseSuppression:false, autoGainControl:false }
    });
    arrancarAudio().createMediaStreamSource(flujo).connect(analizador);
    return true;
  }catch(e){
    /* Sin permiso no pasa nada malo: la pantalla se ve igual, solo que
       quieta. No merece un aviso rojo en mitad de la fiesta. */
    return false;
  }
}

function conectarAnalizadorVideo(v){
  if(fuenteVideo || !ctxAudio) return;
  /* Al enrutar un <video> por la Web Audio API, su sonido pasa a salir
     por el grafo. Si esta pantalla NO es la que suena, conectarlo al
     destino le devolvería el audio por la puerta de atrás y sonaría
     doble. Se analiza igual —el analizador no reproduce nada— pero no se
     conecta a la salida. */
  try{
    fuenteVideo = ctxAudio.createMediaElementSource(v);
    fuenteVideo.connect(analizador);
    if(!MUDA) fuenteVideo.connect(ctxAudio.destination);
  }catch(e){}
}

/* ---- Ensayo: ruido de mentira ----------------------------------------
   Ajustar un efecto que reacciona al sonido de la sala es imposible sin
   sonido en la sala. Y probarlo dando palmadas delante del portátil a las
   tres de la mañana es exactamente lo que nadie va a hacer.

   Con `?ensayo=1` en la dirección, el analizador se sustituye por una
   señal inventada: una base a tiempo constante con golpes encima y algo
   de ruido, para que se vea cómo respira el halo, cómo saltan las barras
   y si la intensidad está bien puesta. **No graba nada ni pide permiso
   al micrófono.**

   Es una herramienta de ensayo, no una función: se enciende poniéndola en
   la dirección y desaparece al quitarla. Sale un cartelito para que nadie
   la deje puesta en una fiesta sin darse cuenta. */
const ENSAYO = new URLSearchParams(location.search).get('ensayo') === '1';
let fase = 0;

/* En ensayo el bucle arranca solo: no hay micrófono que pedir, así que
   tampoco hay que esperar a que nadie dé permiso. */
if(ENSAYO) addEventListener('DOMContentLoaded', () => {
  document.getElementById('halo').classList.remove('no');
  bucleHalo();
});

function nivelDeEnsayo(){
  /* Un pulso cada medio segundo, con un golpe más fuerte cada cuatro, y
     un poco de ruido para que no parezca un metrónomo. */
  fase += 1 / 60;
  const compas = fase % 2;
  const golpe  = Math.max(0, 1 - (compas % 0.5) * 6);
  const fuerte = compas < 0.12 ? 0.4 : 0;
  const fondo  = 0.10 + 0.05 * Math.sin(fase * 1.7);
  return Math.min(1, fondo + golpe * 0.45 + fuerte);
}

function avisarEnsayo(){
  if(!ENSAYO || document.getElementById('avisoEnsayo')) return;
  const d = document.createElement('div');
  d.id = 'avisoEnsayo';
  d.textContent = 'ENSAYO · sonido simulado, el micrófono está apagado';
  d.style.cssText = 'position:fixed;top:1vh;left:50%;transform:translateX(-50%);'
    + 'z-index:99;background:var(--avi,#ffb648);color:#2b1500;font:800 1.1vw system-ui;'
    + 'padding:.6vh 1.4vw;border-radius:999px;letter-spacing:.05vw';
  document.body.appendChild(d);
}

let nivel = 0;
function bucleHalo(){
  requestAnimationFrame(bucleHalo);

  if(ENSAYO){
    avisarEnsayo();
    const rms = nivelDeEnsayo();
    nivel = rms > nivel ? rms : nivel * .90 + rms * .10;
    document.documentElement.style.setProperty('--pulso', Math.min(1, nivel * 2.6).toFixed(3));
    if(EFECTO === 'barras') pintarBarrasDeEnsayo();
    return;
  }

  if(!analizador) return;
  analizador.getByteFrequencyData(datos);
  let suma = 0;
  for(let i = 0; i < datos.length; i++) suma += datos[i] * datos[i];
  const rms = Math.sqrt(suma / datos.length) / 255;
  /* Subida rápida y bajada lenta: así el halo late con los golpes en vez
     de temblar con el ruido de fondo. */
  nivel = rms > nivel ? rms : nivel * .90 + rms * .10;
  document.documentElement.style.setProperty('--pulso', Math.min(1, nivel * 2.6).toFixed(3));

  if(EFECTO !== 'barras') return;
  /* Una barra por banda. Se toma solo la mitad baja del espectro: la
     mitad alta de un micro en una fiesta es casi todo siseo y las barras
     de la derecha se quedaban planas toda la noche. */
  const barras = $('#barras').children;
  const gan = parseFloat(getComputedStyle(document.documentElement)
                .getPropertyValue('--ganancia')) || 1;
  const paso = Math.floor(datos.length / 2 / barras.length) || 1;
  for(let i = 0; i < barras.length; i++){
    let m = 0;
    for(let j = 0; j < paso; j++) m = Math.max(m, datos[i * paso + j] || 0);
    const alto = Math.min(100, 2 + (m / 255) * 90 * gan);
    barras[i].style.height = alto.toFixed(1) + '%';
  }
}

/* Las barras, en ensayo: cada una con su propia frecuencia para que se
   muevan como un espectro y no todas a la vez. */
function pintarBarrasDeEnsayo(){
  const barras = $('#barras').children;
  const gan = parseFloat(getComputedStyle(document.documentElement)
                .getPropertyValue('--ganancia')) || 1;
  for(let i = 0; i < barras.length; i++){
    const caida = 1 - i / barras.length * 0.55;
    const v = (0.35 + 0.65 * Math.abs(Math.sin(fase * (1.3 + i * 0.21)))) * caida * nivel;
    barras[i].style.height = Math.min(100, v * 130 * gan) + '%';
  }
}
