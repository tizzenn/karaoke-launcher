"""
09 · Modo actuación y sincronía — lo que no se puede ver desde dentro

La suite de pruebas.php corre DENTRO de un iframe y mide el DOM. Estas
tres cosas no se pueden medir así:

  1. Que la actuación ocupe la ventana entera, venga de la vista que
     venga. Dentro de un iframe «la ventana entera» son 800 px.
  2. Que el operador publique `t0` cuando el vídeo empieza a sonar de
     verdad, y que el servidor lo guarde.
  3. Que la pantalla del público abierta A MITAD de canción salte al
     segundo que toca en vez de empezar de cero.

Se hace con dos ventanas de verdad, que es como pasa en una fiesta.
"""
from playwright.sync_api import sync_playwright
import json, time, urllib.request

BASE = "http://127.0.0.1:8123"
api = lambda q="": json.load(urllib.request.urlopen(BASE + "/api/estado.php?quien=" + (q or "prueba")))
errs = []

with sync_playwright() as pw:
    b = pw.chromium.launch()
    ctx = b.new_context(viewport={'width': 1400, 'height': 860})
    op = ctx.new_page()
    op.on("pageerror", lambda e: errs.append("PAGEERROR operador: " + str(e)))

    op.goto(BASE + "/index.html")
    op.wait_for_timeout(2500)

    cola = [t for t in api()['cola'] if (t.get('espacio') or 'karaoke') == 'karaoke']
    if not cola:
        print("SIN COLA: añade alguna canción antes de correr esto"); b.close(); raise SystemExit

    pid = cola[0]['id']

    # ── 1 · La actuación ocupa la ventana entera desde cualquier vista ──
    for vista in ('', 'compact', 'mini'):
        op.evaluate("v=>{document.body.classList.remove('compact','mini');"
                    "if(v)document.body.classList.add(v)}", vista)
        op.evaluate("id=>KL.evento.arrancarYa(id)", pid)
        op.wait_for_timeout(900)
        caja = op.evaluate("()=>{const r=document.getElementById('vb').getBoundingClientRect();"
                           "return {w:Math.round(r.width),h:Math.round(r.height)}}")
        ancho = op.evaluate("()=>innerWidth")
        ok = caja['w'] >= ancho * 0.98
        print(f"1.{vista or 'completa':9s} vídeo {caja['w']}x{caja['h']} de {ancho} "
              f"-> {'OK' if ok else 'NO OCUPA LA CONSOLA'}")
        op.screenshot(path=f"actuacion-{vista or 'completa'}.png")
        op.evaluate("()=>KL.evento.panico()")
        op.wait_for_timeout(500)
    op.evaluate("()=>document.body.classList.remove('compact','mini')")

    # ── 2 · El botón de salir se ve ────────────────────────────────────
    op.evaluate("id=>KL.evento.arrancarYa(id)", pid)
    op.wait_for_timeout(900)
    s = op.evaluate("""()=>{const b=document.getElementById('salirInterp');
        const e=getComputedStyle(b), r=b.getBoundingClientRect();
        return {display:e.display, opacidad:e.opacity, w:Math.round(r.width), h:Math.round(r.height)}}""")
    print(f"2. salir: {s}  -> {'OK' if s['display']!='none' and float(s['opacidad'])>=.85 else 'NO SE VE'}")

    # ── 3 · El operador publica t0 al sonar de verdad ──────────────────
    op.evaluate("()=>KL.senales.avisar('reproductor:sonando', true)")
    op.wait_for_timeout(1200)
    ev = api()['evento']
    print(f"3. t0 en el servidor: {ev.get('t0')} -> {'OK' if ev.get('t0') else 'NO SE PUBLICA'}")

    # ── 4 · El público que llega tarde salta a donde va ────────────────
    time.sleep(6)                       # se deja correr la canción
    tv = ctx.new_page()
    tv.on("pageerror", lambda e: errs.append("PAGEERROR tele: " + str(e)))
    tv.goto(BASE + "/proyector.php")
    tv.wait_for_timeout(2500)
    tv.evaluate("""()=>{const o=window.poner;window.__saltos=[];
        window.poner=function(p,ev){window.__saltos.push(window.porDondeVa(ev));
        return o.apply(this,arguments)}}""")
    tv.click("#empezar")
    tv.wait_for_timeout(2500)
    saltos = tv.evaluate("()=>window.__saltos")
    # `let CALIBRACION` no es una propiedad de window: se lee de donde vive.
    cal = tv.evaluate("()=>localStorage.getItem('karaoke_desfase') || '0 (por defecto)'")
    print(f"4. calibración de esta pantalla: {cal}")
    print(f"   salto al abrir tarde: {saltos} -> "
          f"{'OK' if saltos and saltos[0] and saltos[0] > 4 else 'ARRANCA DE CERO'}")
    tv.screenshot(path="tele-tarde.png")

    op.evaluate("()=>KL.evento.panico()")
    b.close()

print("errores:", errs or "ninguno")
