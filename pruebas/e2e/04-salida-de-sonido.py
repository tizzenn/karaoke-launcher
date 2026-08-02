from playwright.sync_api import sync_playwright
errs=[]
with sync_playwright() as pw:
    b=pw.chromium.launch(); ctx=b.new_context(viewport={'width':1300,'height':800})
    op=ctx.new_page()
    for p in (op,): p.on("pageerror", lambda e: errs.append("PAGEERROR: "+str(e)))
    op.goto("http://localhost:8123/index.html"); op.wait_for_timeout(2000)
    print("por defecto sonidoEn:", op.evaluate("()=>KL.estado.sonidoEn"), "| PC mudo:", op.evaluate("()=>KL.reproductor.estaMudo()"))
    op.evaluate("()=>{S.sonidoEn='tele'; aplicarSalidaAudio();}")
    print("con 'tele'        :", op.evaluate("()=>KL.estado.sonidoEn"), "| PC mudo:", op.evaluate("()=>KL.reproductor.estaMudo()"))
    op.evaluate("()=>{S.sonidoEn='pc'; aplicarSalidaAudio();}")
    print("con 'pc'          :", op.evaluate("()=>KL.estado.sonidoEn"), "| PC mudo:", op.evaluate("()=>KL.reproductor.estaMudo()"))
    tv=ctx.new_page(); tv.goto("http://localhost:8123/proyector.php?sonido=pc"); tv.wait_for_timeout(1000)
    print("tele con ?sonido=pc  -> MUDA:", tv.evaluate("()=>MUDA"), "|", (tv.text_content("#quienSuena") or "").strip()[:60])
    tv2=ctx.new_page(); tv2.goto("http://localhost:8123/proyector.php?sonido=tele"); tv2.wait_for_timeout(1000)
    print("tele con ?sonido=tele-> MUDA:", tv2.evaluate("()=>MUDA"), "|", (tv2.text_content("#quienSuena") or "").strip()[:60])
    b.close()
print("errores:", errs or "ninguno")
