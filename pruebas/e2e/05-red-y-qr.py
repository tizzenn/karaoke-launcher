from playwright.sync_api import sync_playwright
import cv2, numpy as np
errs=[]
with sync_playwright() as pw:
    b=pw.chromium.launch(); ctx=b.new_context(viewport={'width':1400,'height':820})
    op=ctx.new_page(); tv=ctx.new_page()
    for p in (op,tv): p.on("pageerror", lambda e: errs.append("PAGEERROR: "+str(e)))
    # abrimos como lo hace Karaoke.bat: en localhost
    op.goto("http://localhost:8123/index.html"); op.wait_for_timeout(2500)
    print("barra de red:", (op.text_content("#redbar") or "").strip())
    print("clase 'mal':", op.evaluate("()=>document.querySelector('#redbar').classList.contains('mal')"))
    op.evaluate("()=>document.querySelector('#bQR').click()"); op.wait_for_timeout(600)
    print("url del QR  :", (op.text_content("#qrurl") or "").strip())
    op.locator("#qrimg").screenshot(path="/tmp/qr.png")
    img=cv2.imread("/tmp/qr.png"); img=cv2.copyMakeBorder(img,30,30,30,30,cv2.BORDER_CONSTANT,value=(255,255,255))
    val,_,_ = cv2.QRCodeDetector().detectAndDecode(cv2.resize(img,None,fx=2,fy=2,interpolation=cv2.INTER_NEAREST))
    print("QR decodifica:", repr(val))
    tv.goto("http://localhost:8123/proyector.php"); tv.wait_for_timeout(1200)
    print("tele, esquina:", (tv.text_content("#esquina") or "").strip()[:60] or "(QR sin texto)")
    print("tele, hay QR :", tv.evaluate("()=>document.querySelectorAll('#esquina svg').length"))
    b.close()
print("errores:", errs or "ninguno")
