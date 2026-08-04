/* ═══════════════════════════════════════════════════════════════════
   instalar.js — convertirla en una aplicación del aparato

   Las tres superficies se pueden instalar por separado aunque compartan
   servidor y código: el operador en la tablet, la pantalla en el mini PC
   de la tele, y la de pedir en el móvil de cada invitado. Cada una con su
   icono y su nombre, porque cada una tiene su propio manifiesto.

   ── Lo que hace este archivo ────────────────────────────────────────
   Poco, y a propósito. Ofrecerlo UNA vez, con las palabras exactas de
   cada sistema, y no volver a insistir.

   ── Por qué no vale un solo botón ───────────────────────────────────
   Chrome avisa por su cuenta con `beforeinstallprompt` y da un método
   para abrir el diálogo. **Safari no.** En iPhone hay que hacer
   Compartir → Añadir a pantalla de inicio a mano, y quien no lo sepa no
   lo adivina mirando la pantalla. Por eso hay dos caminos y en uno solo
   se puede explicar.

   ── Por qué solo se ofrece una vez ──────────────────────────────────
   Un aviso que vuelve cada vez que abres la página se aprende a ignorar
   en dos días, y entonces ya no avisa de nada. Si se descarta, se calla
   para siempre en ese aparato.
   ═══════════════════════════════════════════════════════════════════ */
'use strict';

(function () {

  var KL = window.KL || {};
  window.KL = KL;

  const CLAVE = 'karaoke_instalar_no';

  /* Escape propio: este archivo lo carga también `pedir.php`, que es una
     página suelta y no monta `KL`. Depender de él aquí obligaría a cargar
     media aplicación en la página más ligera de las tres. */
  const esc = t => String(t == null ? '' : t).replace(/[&<>"']/g,
    c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));

  /* Ya instalada: no hay nada que ofrecer. `standalone` es lo de Safari. */
  const yaEsAplicacion = () =>
    matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;

  const rechazado = () => { try { return localStorage.getItem(CLAVE) === '1'; } catch (e) { return false; } };
  const rechazar  = () => { try { localStorage.setItem(CLAVE, '1'); } catch (e) {} };

  const esIOS = () =>
    /iPad|iPhone|iPod/.test(navigator.userAgent) ||
    (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

  /* Qué se está instalando: el texto cambia porque la expectativa cambia.
     Quien instala la de pedir espera un botón para pedir canciones, no un
     puesto de mando. */
  function queSoy() {
    const p = location.pathname;
    if (p.includes('proyector')) return { nombre: 'la pantalla del público',
      para: 'Se abre a pantalla completa y sin barras, que es como tiene que estar en la tele.' };
    if (p.includes('pedir')) return { nombre: 'esta página para pedir canciones',
      para: 'Se abre de un toque, sin buscar el enlace ni escanear otra vez el QR.' };
    return { nombre: 'el puesto de mando',
      para: 'Se abre como una aplicación, sin barra del navegador, y funciona aunque la wifi vaya mal.' };
  }

  /* El estilo va aquí dentro y no en `css/base.css` por un motivo que
     costó una captura: **`pedir.php` no carga base.css**. Es una página
     suelta con sus propios estilos, a propósito, para que abra rápido en
     el móvil de un invitado. Al dejar el CSS en la hoja común, el aviso
     salía ahí sin ningún formato: texto suelto pegado al final de la
     página y un botón blanco del navegador.

     Un componente que se usa en tres páginas con tres hojas distintas se
     trae su aspecto puesto. Los colores salen de variables si existen, y
     si no, del valor de reserva. */
  function estilo() {
    if (document.getElementById('estiloInstalar')) return;
    const e = document.createElement('style');
    e.id = 'estiloInstalar';
    e.textContent = `
      #instalar{position:fixed;left:50%;bottom:18px;transform:translate(-50%,180%);
        z-index:900;display:flex;align-items:center;gap:12px;flex-wrap:wrap;
        width:max-content;max-width:min(560px,calc(100vw - 24px));
        background:var(--bg2,#141821);color:var(--txt,#eaedf3);
        border:1px solid var(--line,#2b323f);border-radius:14px;
        padding:13px 16px;box-shadow:0 20px 60px rgba(0,0,0,.6);
        font:400 13px/1.45 'Segoe UI',system-ui,sans-serif;
        transition:transform .35s cubic-bezier(.2,.9,.3,1)}
      #instalar.on{transform:translate(-50%,0)}
      #instalar .txt{display:flex;flex-direction:column;gap:3px;min-width:0;flex:1 1 220px}
      #instalar .txt b{font-size:14px}
      #instalar .txt span{font-size:12.5px;color:var(--txt3,#6a7383)}
      #instalar .ios{font-size:12.5px;color:var(--txt2,#98a1b2);flex:1 1 200px}
      #instalar .ios b{color:var(--ac,#22d97a)}
      #instalar button{border:0;border-radius:999px;padding:10px 18px;
        font:800 13px inherit;cursor:pointer;
        background:var(--ac,#22d97a);color:var(--ac-txt,#052b16)}
      #instalar button.g{background:var(--bg3,#1b2029);color:var(--txt2,#98a1b2);
        border:1px solid var(--line,#2b323f)}
    `;
    document.head.appendChild(e);
  }

  let invitacionDelNavegador = null;

  /* Chrome guarda su propio aviso y lo suelta cuando le parece. Se
     intercepta para enseñarlo en el momento y con las palabras que
     queremos, no en mitad de una canción. */
  addEventListener('beforeinstallprompt', ev => {
    ev.preventDefault();
    invitacionDelNavegador = ev;
    ofrecer();
  });

  addEventListener('appinstalled', () => { rechazar(); quitar(); });

  function quitar() {
    const c = document.getElementById('instalar');
    if (c) c.remove();
  }

  function ofrecer() {
    if (yaEsAplicacion() || rechazado() || document.getElementById('instalar')) return;

    estilo();
    const yo = queSoy();
    const caja = document.createElement('div');
    caja.id = 'instalar';
    caja.innerHTML =
      '<div class="txt"><b>Instala ' + esc(yo.nombre) + '</b>' +
      '<span>' + esc(yo.para) + '</span></div>' +
      (esIOS()
        ? '<div class="ios">Pulsa <b>Compartir</b> y luego <b>Añadir a pantalla de inicio</b>.</div>'
        : '<button id="instSi">Instalar</button>') +
      '<button class="g" id="instNo">Ahora no</button>';
    document.body.appendChild(caja);
    requestAnimationFrame(() => caja.classList.add('on'));

    const si = document.getElementById('instSi');
    if (si) si.addEventListener('click', async () => {
      quitar();
      if (!invitacionDelNavegador) return;
      invitacionDelNavegador.prompt();
      /* La respuesta se espera aunque no se use: sin leerla, algunos
         navegadores dejan la promesa colgada y no vuelven a ofrecerlo. */
      try { await invitacionDelNavegador.userChoice; } catch (e) {}
      invitacionDelNavegador = null;
    });

    document.getElementById('instNo').addEventListener('click', () => { rechazar(); quitar(); });
  }

  /* En iPhone no hay ningún aviso del navegador que interceptar, así que
     lo proponemos nosotros — y no de golpe: a los pocos segundos, cuando
     ya se ha visto que la página funciona. Ofrecer antes de que se vea
     nada es pedir un favor a un desconocido. */
  if (esIOS()) addEventListener('load', () => setTimeout(ofrecer, 6000));

  KL.instalar = { ofrecer };
})();
