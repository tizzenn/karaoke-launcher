from playwright.sync_api import sync_playwright
import json,time
errs=[]
with sync_playwright() as pw:
    b=pw.chromium.launch(args=["--use-fake-ui-for-media-stream","--use-fake-device-for-media-stream"])
    ctx=b.new_context(viewport={'width':1600,'height':900},permissions=["microphone"])
    op=ctx.new_page(); tv=ctx.new_page()
    for pg in (op,tv):
        pg.on("pageerror", lambda e: errs.append("PAGEERROR: "+str(e)))
        pg.on("console", lambda m: errs.append("CONSOLE: "+m.text) if m.type=="error" and "youtube" not in m.text.lower() and "ERR_" not in m.text else None)
    op.goto("http://127.0.0.1:8123/index.html"); op.wait_for_timeout(1500)
    for vid,t,d in [("dQw4w9WgXcQ","Cicatrices · Natos y Waor",212),("oHg5SJYRHA0","Quemando Carretera · Natos y Waor",198),("aaaaaaaaaaa","Hasta el Amanecer · Natos y Waor",245),("bbbbbbbbbbb","Problemas · Natos y Waor",190)]:
        op.evaluate("""([v,t,d])=>fetch('api/estado.php',{method:'POST',headers:{'Content-Type':'application/json'},
          body:JSON.stringify({accion:'anadir_cola',video:{videoId:v,title:t,channel:'Kantar Hip-Hop Kanala',thumb:'',duration:d}})})""",[vid,t,d])
    op.wait_for_timeout(2200)
    op.evaluate("()=>document.querySelector('#bCal').click()")
    tv.goto("http://127.0.0.1:8123/proyector.php"); tv.wait_for_timeout(1500)
    tv.evaluate("()=>document.querySelector('#empezar').click()"); tv.wait_for_timeout(1500)
    for i in range(4):
        tv.evaluate("i=>{panel=i;pintarPanel();}", i)
        tv.wait_for_timeout(500)
        tv.screenshot(path=f"/home/claude/tv{i}.png")
    print("escena:", tv.evaluate("()=>document.body.dataset.escena"))
    print("halo activo:", tv.evaluate("()=>!document.querySelector('#halo').classList.contains('no')"))
    # el operador arranca -> se acaba el calentamiento
    op.evaluate("()=>KL.evento.arrancar(KL.estado.queue[0].id)")
    op.wait_for_timeout(2500)
    print("calentamiento tras play:", op.evaluate("()=>KL.estado.calentamiento"))
    print("escena tele:", tv.evaluate("()=>document.body.dataset.escena"))
    tv.screenshot(path="/home/claude/tv_video.png")
    op.evaluate("()=>{KL.estado.segFin=20; KL.evento.terminar();}")
    op.wait_for_timeout(2500)
    print("escena tele tras fin:", tv.evaluate("()=>document.body.dataset.escena"))
    tv.screenshot(path="/home/claude/tv_fin.png")
    b.close()
print("--- errores ---")
for e in dict.fromkeys(errs): print(e)
