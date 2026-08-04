/* ═══════════════════════════════════════════════════════════════════
   mc.js — el Maestro de ceremonias

   Seis sonidos y cinco frases. Es lo más pequeño que quedaba de la v1.1
   y lo que más cambia la sensación de una sala: entre canción y canción
   hay un hueco en el que nadie sabe si aplaudir, y basta con que algo
   diga «un aplauso para Marta» para que la gente aplauda.

   ── Lo que NO es ─────────────────────────────────────────────────────
   No es un módulo con estado. No añade ni un estado al evento, no
   escribe en el estado compartido y no puede cambiar lo que pasa en la
   fiesta. Pulsas, suena, y ya. Si mañana se borra este archivo, la
   aplicación sigue funcionando entera.

   ── Por qué las frases NO son grabaciones ────────────────────────────
   La idea original eran cinco frases grabadas. Se ha cambiado por la voz
   del sistema (`speechSynthesis`) por un motivo que vale más que la
   calidad del audio: **una grabación no puede decir el nombre de quien
   canta**. «Un aplauso para Marta» hace en una sala lo que «un aplauso»
   no hace, y ese nombre ya está en el estado.

   La voz de Windows en español no es una maravilla. Pero se oye por
   encima de la música una vez cada tres minutos, dice un nombre propio y
   cuesta cero archivos. Si algún día alguien graba las cinco frases con
   su voz, esto se sustituye sin tocar nada más.

   ── Dónde suena ──────────────────────────────────────────────────────
   En el aparato que da el sonido de la sala, igual que la música
   ambiente. Si suena en las dos pantallas se oye doble y desfasado, que
   es exactamente el problema que ya resolvimos una vez.

   ── Y una cosa que sí hace, y es la que importa ──────────────────────
   Baja la música ambiente mientras habla. Un maestro de ceremonias que
   compite con la música de fondo no se entiende, y subir el volumen para
   taparla es peor.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

var KL = window.KL || {};
window.KL = KL;

KL.mc = (function () {

  let plan = null;          // lo que hay en mc.json
  let hay = {};             // qué archivos existen de verdad
  let doySonido = () => true;

  /* Un solo `Audio` por sonido, reutilizado. Crear uno nuevo en cada
     pulsación deja objetos sueltos y, con el botón aporreado, treinta
     aplausos solapados. Reutilizar además permite lo que de verdad hace
     falta: volver a empezar el sonido desde cero si se pulsa dos veces. */
  const pistas = {};

  async function cargar() {
    if (plan) return plan;
    try {
      plan = await fetch('mc.json', { cache: 'no-cache' }).then(r => r.json());
    } catch (e) { plan = { sonidos: [], frases: [] }; return plan; }

    /* Se comprueba qué archivos están ANTES de pintar ningún botón. Un
       botón que no suena es peor que no tener botón: en mitad de una
       fiesta se pulsa tres veces y se pierde la confianza en el panel
       entero. */
    await Promise.all((plan.sonidos || []).map(async s => {
      try {
        const r = await fetch('assets/mc/' + s.archivo + '.mp3', { method: 'HEAD' });
        hay[s.archivo] = r.ok;
      } catch (e) { hay[s.archivo] = false; }
    }));
    return plan;
  }

  /* ---- Sonar ------------------------------------------------------- */
  function sonar(archivo) {
    if (!doySonido() || !hay[archivo]) return;
    let a = pistas[archivo];
    if (!a) {
      a = pistas[archivo] = new Audio('assets/mc/' + archivo + '.mp3');
      a.preload = 'auto';
    }
    /* Volver al principio: pulsar dos veces tiene que sonar dos veces,
       no ignorarse porque ya estaba sonando. */
    try { a.currentTime = 0; a.play().catch(() => {}); } catch (e) {}
    agachar(2200);
  }

  /* ---- Hablar ------------------------------------------------------ */
  /* El nombre sale del mismo sitio que el rótulo de la tele: quien la
     pidió si lo hay, y si no el título. Nunca se inventa. */
  function quienAhora() {
    const S = KL.estado;
    const t = (S.curId && KL.$ && S.queue) ? S.queue.find(x => x.id === S.curId) : null;
    const sig = t || (S.evento && S.evento.pistaId
                      ? (S.queue || []).find(x => x.id === S.evento.pistaId) : null);
    if (sig && KL.Actuacion) return KL.Actuacion.quien(sig) || KL.Actuacion.titulo(sig);
    const r = (S.evento || {}).recien;
    return (r && r.title) || '';
  }

  function decir(clave) {
    if (!doySonido()) return '';
    const f = ((plan || {}).frases || []).find(x => x.clave === clave);
    if (!f) return '';
    const texto = f.texto.replace('{quien}', quienAhora()).replace(/:\s*$/, '');
    if (!('speechSynthesis' in window)) return texto;

    try {
      /* Se corta lo que estuviera diciendo. Dos frases encadenadas por
         un doble clic no se entienden ninguna de las dos. */
      speechSynthesis.cancel();
      const u = new SpeechSynthesisUtterance(texto);
      u.lang = 'es-ES';

      /* ---- Que suene a alguien animando, no a un aviso de megafonía ---
         Una voz sintética con los valores por defecto suena a locutor de
         aeropuerto: plana, lenta y educada. En una sala con música
         puesta eso no anima a nadie — se oye como una instrucción.

         Lo que cambia la percepción son dos números, y en este orden:

         · **El tono.** Subirlo entre un 10 y un 20 % es lo que el oído
           lee como entusiasmo, porque es lo que hace una persona cuando
           se emociona. Es el ajuste que más se nota con diferencia.
         · **La velocidad.** Un poco más rápido que hablar normal.
           Demasiado y no se entiende con ruido de fondo; demasiado
           lento y suena a que está leyendo.

         Por encima de 1.3 deja de sonar a entusiasmo y empieza a sonar
         a dibujos animados, así que se recorta ahí. Y cada frase lleva
         su propia `energia` porque no es lo mismo presentar a alguien
         —que pide empuje— que darle las gracias. */
      const e = Math.max(0.9, Math.min(1.3, +f.energia || 1.1));
      u.pitch  = e;
      u.rate   = 1.0 + (e - 1) * 0.55;   // la mitad de lo que sube el tono
      u.volume = 1.0;

      /* La mejor voz española que haya. Se prefiere una local —no las
         que necesitan internet— porque en una fiesta el router es
         justo lo que falla, y una frase que no sale es peor que
         ninguna. */
      const voces = speechSynthesis.getVoices().filter(v => /^es/i.test(v.lang));
      const voz = voces.find(v => v.localService) || voces[0];
      if (voz) u.voice = voz;
      /* La música vuelve cuando termina de hablar, no a los tres
         segundos: una frase con un nombre largo dura lo que dura. */
      u.onend = () => levantar();
      u.onerror = () => levantar();
      agachar(0);
      speechSynthesis.speak(u);
    } catch (e) { levantar(); }
    return texto;
  }

  /* ---- Bajar la música mientras se habla --------------------------- */
  /* No se para: se agacha. Cortar la música y devolverla se oye como un
     fallo; bajarla a un cuarto durante cuatro segundos se lee como que
     alguien va a decir algo, que es lo que pasa. */
  let vuelta = null;
  function agachar(ms) {
    if (!KL.ambiente || !KL.ambiente.agachar) return;
    clearTimeout(vuelta);
    KL.ambiente.agachar(true);
    if (ms) vuelta = setTimeout(levantar, ms);
  }
  function levantar() {
    clearTimeout(vuelta); vuelta = null;
    if (KL.ambiente && KL.ambiente.agachar) KL.ambiente.agachar(false);
  }

  /* ---- El panel ---------------------------------------------------- */
  function pintar() {
    const caja = document.getElementById('panelMC');
    if (!caja || !plan) return;
    const sonidos = (plan.sonidos || []).filter(s => hay[s.archivo]);

    if (!sonidos.length && !(plan.frases || []).length) {
      caja.innerHTML = '<span class="mcVacio">No hay sonidos en <b>assets/mc/</b>.</span>';
      return;
    }

    caja.innerHTML =
      sonidos.map(s => `<button class="mcBtn" data-son="${esc(s.archivo)}"
          title="${esc(s.nombre)}${s.tecla ? '  ·  tecla ' + s.tecla : ''}">
          ${icono(s.icono || 'musica','sm')}<span>${esc(s.nombre)}</span></button>`).join('') +
      '<span class="mcSep"></span>' +
      (plan.frases || []).map(f => `<button class="mcBtn voz" data-frase="${esc(f.clave)}"
          title="${esc(f.cuando || '')}">${icono('microfono','sm')}<span>${esc(
            f.texto.replace('{quien}','…').slice(0, 22))}</span></button>`).join('');

    caja.querySelectorAll('[data-son]').forEach(b =>
      b.addEventListener('click', () => sonar(b.dataset.son)));
    caja.querySelectorAll('[data-frase]').forEach(b =>
      b.addEventListener('click', () => {
        const dicho = decir(b.dataset.frase);
        if (dicho) toast('🎙 ' + dicho);
      }));
  }

  function iniciar(opciones) {
    doySonido = (opciones && opciones.doySonido) || doySonido;
    cargar().then(pintar);

    /* Las teclas 1..6, y solo cuando no se está escribiendo. Un panel de
       sonidos que se dispara mientras buscas una canción es una broma
       muy mala en mitad de una fiesta. */
    addEventListener('keydown', e => {
      if (e.ctrlKey || e.altKey || e.metaKey) return;
      const en = document.activeElement;
      if (en && /^(INPUT|TEXTAREA|SELECT)$/.test(en.tagName)) return;
      const s = ((plan || {}).sonidos || []).find(x => x.tecla === e.key);
      if (s && hay[s.archivo]) { e.preventDefault(); sonar(s.archivo); }
    });
  }

  return { iniciar, pintar, sonar, decir, cargan: () => plan, existe: a => !!hay[a] };
})();
