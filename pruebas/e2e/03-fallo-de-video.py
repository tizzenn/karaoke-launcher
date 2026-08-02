from playwright.sync_api import sync_playwright
errs=[]
with sync_playwright() as pw:
    b=pw.chromium.launch(); p=b.new_page(viewport={'width':1400,'height':800})
    p.on("pageerror", lambda e: errs.append("PAGEERROR: "+str(e)))
    p.goto("http://localhost:8123/index.html"); p.wait_for_timeout(2000)
    p.evaluate("""()=>fetch('api/estado.php',{method:'POST',headers:{'Content-Type':'application/json'},
      body:JSON.stringify({accion:'anadir_cola',video:{videoId:'dQw4w9WgXcQ',title:'Cicatrices · Natos y Waor',channel:'Kantar',thumb:'',duration:212}})})""")
    p.wait_for_timeout(2000)
    p.evaluate("()=>{KL.estado.segLlamada=0; KL.evento.arrancar(KL.estado.queue[0].id);}")
    p.wait_for_timeout(1200)
    print("estado:", p.evaluate("()=>document.body.dataset.evento"))
    vis = p.evaluate("()=>{const e=document.querySelector('#salirInterp');const c=getComputedStyle(e);return [c.display,c.opacity];}")
    print("boton salir -> display:", vis[0], "| opacidad:", vis[1], "(antes era 0)")
    p.screenshot(path="/home/claude/salir.png")
    # simulamos el fallo
    p.evaluate("()=>mostrarFallo('sin arrancar')"); p.wait_for_timeout(400)
    print("pantalla de fallo visible:", p.evaluate("()=>getComputedStyle(document.querySelector('#falloVideo')).display"))
    p.screenshot(path="/home/claude/fallo.png")
    p.evaluate("()=>document.querySelector('#bFalloSalir').click()"); p.wait_for_timeout(600)
    print("tras 'Volver al operador':", p.evaluate("()=>document.body.dataset.evento"),
          "| pantalla:", p.evaluate("()=>document.body.dataset.pantalla"),
          "| cartel:", p.evaluate("()=>getComputedStyle(document.querySelector('#falloVideo')).display"))
    b.close()
print("errores:", errs or "ninguno")
