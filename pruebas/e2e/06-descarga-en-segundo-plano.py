from playwright.sync_api import sync_playwright
import time
errs=[]
with sync_playwright() as pw:
    b=pw.chromium.launch(); p=b.new_page(viewport={'width':1400,'height':820})
    p.on("pageerror", lambda e: errs.append(str(e)))
    p.goto("http://localhost:8123/index.html"); p.wait_for_timeout(2200)
    p.evaluate("""()=>fetch('api/estado.php',{method:'POST',headers:{'Content-Type':'application/json'},
      body:JSON.stringify({accion:'anadir_cola',video:{videoId:'dQw4w9WgXcQ',title:'Cicatrices · Natos y Waor',channel:'Kantar',thumb:'',duration:212}})})""")
    p.wait_for_timeout(2000)
    print("puedeDescargar:", p.evaluate("()=>KL.estado.puedeDescargar"))
    # descargar() dejo de ser global cuando cola.js se encapsulo ("paso 6",
    # ver su cabecera): la puerta publica es KL.cola.descargar.
    p.evaluate("()=>KL.cola.descargar(KL.estado.queue[0])")
    # ¿sigue respondiendo la aplicación mientras baja?
    t0=time.time()
    r = p.evaluate("()=>fetch('api/estado.php').then(r=>r.json()).then(j=>j.ok)")
    print(f"la app responde durante la descarga: {r} en {time.time()-t0:.2f}s")
    vistos=set()
    for i in range(9):
        p.wait_for_timeout(500)
        txt = p.evaluate("()=>{const e=document.querySelector('#que .it .ib.pct');return e?e.textContent:null;}")
        if txt: vistos.add(txt)
    print("porcentajes vistos en la fila:", sorted(vistos) or "(ninguno)")
    p.wait_for_timeout(2500)
    print("local tras terminar:", p.evaluate("()=>KL.estado.queue[0].local"))
    print("icono final verde:", p.evaluate("()=>!!document.querySelector('#que .it [data-dl-ok]')"))
    print("sondeo detenido:", p.evaluate("()=>Object.keys(KL.estado.descargas).length===0"))
    p.screenshot(path="/home/claude/desc.png")
    b.close()
print("errores:", errs or "ninguno")
