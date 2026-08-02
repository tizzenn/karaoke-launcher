from playwright.sync_api import sync_playwright
with sync_playwright() as pw:
    b=pw.chromium.launch(); p=b.new_page(viewport={'width':1500,'height':1000})
    errs=[]; p.on("pageerror", lambda e: errs.append(str(e)))
    p.goto("http://localhost:8123/pruebas/pruebas.php"); p.wait_for_timeout(2500)
    p.click("#bIr")
    for _ in range(80):
        p.wait_for_timeout(1000)
        if p.evaluate("()=>document.querySelector('#resumen').classList.contains('on')"): break
    print(p.text_content("#marcador").strip())
    print("---")
    for el in p.query_selector_all(".p.mal"):
        print("FALLA:", el.text_content().strip()[:250].replace("\n"," | "))
    p.screenshot(path="/home/claude/suite.png", full_page=True)
    b.close()
    print("errores de página:", errs or "ninguno")
