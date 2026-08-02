from playwright.sync_api import sync_playwright
errs=[]
with sync_playwright() as pw:
    b=pw.chromium.launch(); ctx=b.new_context(viewport={'width':1400,'height':800})
    op=ctx.new_page(); tv=ctx.new_page()
    for p in (op,tv): p.on("pageerror", lambda e: errs.append("PAGEERROR: "+str(e)))
    op.goto("http://127.0.0.1:8123/index.html"); op.wait_for_timeout(1500)
    for v,t,q in [("dQw4w9WgXcQ","Cicatrices · Natos y Waor","Marta"),("oHg5SJYRHA0","Quemando Carretera · Natos y Waor",None)]:
        op.evaluate("""([v,t,q])=>fetch('api/estado.php',{method:'POST',headers:{'Content-Type':'application/json'},
          body:JSON.stringify({accion:'anadir_cola',quien:q||'',video:{videoId:v,title:t,channel:'Kantar',thumb:'',duration:212}})})""",[v,t,q])
    op.wait_for_timeout(2000)
    tv.goto("http://127.0.0.1:8123/proyector.php"); tv.wait_for_timeout(1200)
    tv.evaluate("()=>document.querySelector('#empezar').click()"); tv.wait_for_timeout(800)
    op.evaluate("()=>{KL.estado.segLlamada=5; KL.evento.arrancar(KL.estado.queue[0].id);}")
    op.wait_for_timeout(1200)
    print("1. operador:", op.evaluate("()=>document.body.dataset.evento"), "| pantalla:", op.evaluate("()=>document.body.dataset.pantalla"))
    print("   num PC  :", (op.text_content("#llamada .num") or "").strip())
    op.screenshot(path="/home/claude/llam_pc.png")
    tv.wait_for_timeout(700); print("2. tele    :", tv.evaluate("()=>document.body.dataset.escena"), "| num:", (tv.text_content("#llamNum") or "").strip(), "| quien:", (tv.text_content("#llamQuien") or "").strip())
    tv.screenshot(path="/home/claude/llam_tv.png")
    op.wait_for_timeout(5000)
    print("3. tras cuenta:", op.evaluate("()=>document.body.dataset.evento"), "| tele:", tv.evaluate("()=>document.body.dataset.escena"))
    # cancelar
    op.evaluate("()=>KL.evento.terminar()"); op.wait_for_timeout(300)
    op.evaluate("()=>{KL.estado.segFin=3;}"); op.wait_for_timeout(3500)
    op.evaluate("()=>KL.evento.arrancar()"); op.wait_for_timeout(1200)
    print("4. llamada 2:", op.evaluate("()=>document.body.dataset.evento"))
    op.keyboard.press("Escape"); op.wait_for_timeout(600)
    print("5. tras Esc :", op.evaluate("()=>document.body.dataset.evento"), "<- debe ser PREPARADA")
    b.close()
print("errores:", errs or "ninguno")
