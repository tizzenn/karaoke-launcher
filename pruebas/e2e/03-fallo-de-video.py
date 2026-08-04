from playwright.sync_api import sync_playwright
errs=[]
with sync_playwright() as pw:
    b=pw.chromium.launch(); p=b.new_page(viewport={'width':1400,'height':800})
    p.on("pageerror", lambda e: errs.append("PAGEERROR: "+str(e)))
    p.goto("http://localhost:8123/index.html"); p.wait_for_timeout(2000)
    # videoId con formato valido (11 caracteres) pero que no existe: YouTube
    # responde con un error de verdad (100, video borrado o inexistente),
    # asi que el fallo que se prueba aqui es el mismo que le pasaria a un
    # usuario, no uno simulado a mano.
    p.evaluate("""()=>fetch('api/estado.php',{method:'POST',headers:{'Content-Type':'application/json'},
      body:JSON.stringify({accion:'anadir_cola',video:{videoId:'000000000AA',title:'Video que no existe (a proposito)',channel:'Prueba',thumb:'',duration:212}})})""")
    p.wait_for_timeout(2000)
    p.evaluate("()=>{KL.estado.segLlamada=0; KL.evento.arrancar(KL.estado.queue[0].id);}")
    p.wait_for_timeout(1200)
    print("estado:", p.evaluate("()=>document.body.dataset.evento"))
    vis = p.evaluate("()=>{const e=document.querySelector('#salirInterp');const c=getComputedStyle(e);return [c.display,c.opacity];}")
    print("boton salir -> display:", vis[0], "| opacidad:", vis[1], "(antes era 0)")
    p.screenshot(path="/home/claude/salir.png")
    # El fallo llega solo: desde que app.js se encapsulo en una funcion
    # (ver la cabecera de ese archivo), mostrarFallo() ya no es una
    # funcion global -llamarla a mano da "mostrarFallo is not defined"-,
    # asi que en vez de simularla se deja que YouTube devuelva un error
    # de verdad para el videoId inventado de arriba y que el reproductor
    # dispare el aviso por su cuenta, como le pasaria a un usuario real.
    # 4s se quedaba corto en maquinas lentas: YouTube tarda en devolver el
    # error y el guion comprobaba la pantalla antes de que llegara. Un
    # Chromium recien lanzado por Playwright, sin cache ni instancia de
    # youtube.com previa, tarda mas en cargar el iframe_api que una
    # pestaña que ya lo habia cargado antes -confirmado: con la pestaña
    # ya "caliente" 6s bastaban, en frio no. 14s da margen de sobra.
    p.wait_for_timeout(14000)
    print("pantalla de fallo visible:", p.evaluate("()=>getComputedStyle(document.querySelector('#falloVideo')).display"))
    p.screenshot(path="/home/claude/fallo.png")
    p.evaluate("()=>document.querySelector('#bFalloSalir').click()"); p.wait_for_timeout(600)
    print("tras 'Volver al operador':", p.evaluate("()=>document.body.dataset.evento"),
          "| pantalla:", p.evaluate("()=>document.body.dataset.pantalla"),
          "| cartel:", p.evaluate("()=>getComputedStyle(document.querySelector('#falloVideo')).display"))
    b.close()
print("errores:", errs or "ninguno")
