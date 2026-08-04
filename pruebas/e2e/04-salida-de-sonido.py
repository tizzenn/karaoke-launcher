from playwright.sync_api import sync_playwright
errs=[]
with sync_playwright() as pw:
    b=pw.chromium.launch(); ctx=b.new_context(viewport={'width':1300,'height':800})
    op=ctx.new_page()
    for p in (op,): p.on("pageerror", lambda e: errs.append("PAGEERROR: "+str(e)))
    op.goto("http://localhost:8123/index.html"); op.wait_for_timeout(2000)
    print("por defecto sonidoEn:", op.evaluate("()=>KL.estado.sonidoEn"), "| PC mudo:", op.evaluate("()=>KL.reproductor.estaMudo()"))
    # aplicarSalidaAudio() ya no es global (app.js esta encapsulado, ver su
    # cabecera): en vez de llamarla a mano se cambia el select real de
    # Ajustes y se pulsa Guardar, que es la unica puerta publica que
    # aplica el cambio -y la misma que usaria un operador de verdad-.
    op.click("#bCfg"); op.wait_for_timeout(200)
    op.evaluate("()=>{document.querySelector('#cSonido').value='tele'}")
    op.click("#bSaveCfg"); op.wait_for_timeout(200)
    print("con 'tele'        :", op.evaluate("()=>KL.estado.sonidoEn"), "| PC mudo:", op.evaluate("()=>KL.reproductor.estaMudo()"))
    op.click("#bCfg"); op.wait_for_timeout(200)
    op.evaluate("()=>{document.querySelector('#cSonido').value='pc'}")
    op.click("#bSaveCfg"); op.wait_for_timeout(200)
    print("con 'pc'          :", op.evaluate("()=>KL.estado.sonidoEn"), "| PC mudo:", op.evaluate("()=>KL.reproductor.estaMudo()"))
    tv=ctx.new_page(); tv.goto("http://localhost:8123/proyector.php?sonido=pc"); tv.wait_for_timeout(1000)
    print("tele con ?sonido=pc  -> MUDA:", tv.evaluate("()=>MUDA"), "|", (tv.text_content("#quienSuena") or "").strip()[:60])
    tv2=ctx.new_page(); tv2.goto("http://localhost:8123/proyector.php?sonido=tele"); tv2.wait_for_timeout(1000)
    print("tele con ?sonido=tele-> MUDA:", tv2.evaluate("()=>MUDA"), "|", (tv2.text_content("#quienSuena") or "").strip()[:60])
    b.close()
print("errores:", errs or "ninguno")
