from playwright.sync_api import sync_playwright
import cv2, numpy as np, sys
casos = [
 "http://192.168.1.40:8123/pedir.php",
 "WIFI:T:WPA;S:MiWifi_2.4G;P:contraseña-larga-123;;",
 "http://10.0.0.7:8123/pedir.php?clave=fiesta2026",
 "A"*60, "B"*120, "C"*200,
 "WIFI:T:WPA;S:Casa;P:a;;",
]
ok=0
with sync_playwright() as pw:
    b = pw.chromium.launch(); p = b.new_page(viewport={'width':500,'height':500})
    p.goto("http://127.0.0.1:8123/index.html"); p.wait_for_timeout(1200)
    det = cv2.QRCodeDetector()
    for t in casos:
        p.evaluate("""t=>{document.body.innerHTML='<div id=z style="width:420px;height:420px;background:#fff;padding:20px">'+KL.qr.svg(t)+'</div>';}""", t)
        p.wait_for_timeout(150)
        p.locator("#z").screenshot(path="/tmp/q.png")
        img = cv2.imread("/tmp/q.png")
        val, pts, _ = det.detectAndDecode(img)
        good = (val == t)
        ok += good
        print(("OK  " if good else "FALLO ") , repr(t[:45]), "->", repr(val[:45]) if val else "(no decodifica)")
    b.close()
print(f"\n{ok}/{len(casos)} correctos")
