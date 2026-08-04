/* ═══════════════════════════════════════════════════════════════════
   copiloto.js — sugiere, nunca decide

   Ningún aviso de aquí cambia el estado por su cuenta, solo propone un
   botón que el operador tiene que pulsar él mismo (misma regla que «una
   canción no arranca sola», MODELO §6).

   El primer caso, y de momento el único: hace rato que no entra ninguna
   canción y quedan pocas en la cola. Puede ser que nadie sepa que puede
   pedir desde el móvil — el QR está a un clic, pero solo si se sabe que
   existe.

   Cerrado en su propio archivo (2026-08-04): vivía dentro de app.js y lo
   empujó por encima del límite de 800 líneas que la propia suite vigila
   (`pruebas.php`, «ningún archivo ha vuelto a crecer sin que nadie
   mire»). Mismo patrón que ambiente.js o atajos.js: un aviso más no es
   asunto del cableado general. */
'use strict';

(function () {

const COPILOTO_UMBRAL_MS = 8 * 60 * 1000;
const COPILOTO_COLA_BAJA = 2;

function proponerCopiloto(){
  const caja = $('#avisoCopiloto');
  if(!caja) return;
  if(Date.now() < S.avisoCopilotoHasta) return;

  const quieta = Date.now() - S.ultimaAnadida > COPILOTO_UMBRAL_MS;
  const colaBaja = cola().length <= COPILOTO_COLA_BAJA;

  if(!S.peticiones || !quieta || !colaBaja){
    caja.classList.remove('on');
    return;
  }

  caja.innerHTML = icono('aviso') +
    '<span>Hace rato que no entra ninguna canción y queda poca cola. ' +
    '¿Enseñas el QR para que la gente pida desde el móvil?</span>' +
    '<button class="btn" id="bCopilotoQR">Enseñar QR</button>' +
    '<button class="btn g" id="bCopilotoNo">Ahora no</button>';
  caja.classList.add('on');

  $('#bCopilotoNo').addEventListener('click', () => {
    caja.classList.remove('on');
    S.avisoCopilotoHasta = Date.now() + 15 * 60 * 1000;
  });
  $('#bCopilotoQR').addEventListener('click', () => {
    caja.classList.remove('on');
    S.avisoCopilotoHasta = Date.now() + 15 * 60 * 1000;
    $('#bQR').click();
  });
}
setInterval(proponerCopiloto, 60 * 1000);
KL.proponerCopiloto = proponerCopiloto;

})();
