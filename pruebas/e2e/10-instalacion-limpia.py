"""
10 · Instalación limpia — la prueba que encontró lo que las otras no

Las noventa y nueve pruebas de `pruebas.php` dan por hecho una cosa sin
decirlo: **que la aplicación arranca**. Y por eso ninguna podía ver los
fallos que aparecieron al instalar esto por primera vez en un portátil
ajeno — porque en el ordenador de uno ya está `data/`, ya está
`ajustes.json`, ya están los certificados y ya está todo.

Esto copia el proyecto a una carpeta vacía, lo arranca **con los errores
apagados** —como hace `php.ini-production`, que es lo que instala
`Preparar.bat`— y comprueba lo único que de verdad importa el primer día:

    Que ninguna pantalla salga en blanco.

Ya ha encontrado dos fallos de verdad:

  · `CURLE_PEER_FAILED_VERIFICATION` no existe en todas las versiones de
    curl. Estaba dentro del código que atiende «no he podido conectar»,
    así que el camino de error se rompía justo cuando había un error. Y
    lo disparaba la falta de certificados — es decir, el ordenador que
    más necesitaba leer «te faltan los certificados» era el único que no
    podía verlo.

  · «Ese vídeo no existe o es privado» se decía también cuando lo que
    pasaba era que PHP no llegaba a internet. Un mensaje seguro de sí
    mismo apuntando al sitio equivocado hace perder más tiempo que uno
    que no dice nada.

Uso:  python 10-instalacion-limpia.py [carpeta-del-proyecto]
"""
import http.client, json, os, shutil, subprocess, sys, tempfile, time

ORIGEN = os.path.abspath(sys.argv[1] if len(sys.argv) > 1 else os.path.join(os.path.dirname(__file__), '..', '..'))
PUERTO = 8207
fallos = []

def pedir(ruta, metodo='GET', cuerpo=None):
    c = http.client.HTTPConnection('127.0.0.1', PUERTO, timeout=20)
    cab = {'Content-Type': 'application/x-www-form-urlencoded'} if cuerpo else {}
    c.request(metodo, ruta, cuerpo, cab)
    r = c.getresponse()
    return r.status, r.read().decode('utf-8', 'replace')

def comprobar(nombre, cond, detalle=''):
    print(('  OK   ' if cond else '  FALLA') + '  ' + nombre + ('' if cond else '  ->  ' + detalle))
    if not cond:
        fallos.append(nombre + ' :: ' + detalle)

destino = tempfile.mkdtemp(prefix='okc-limpio-')
try:
    print('Copiando el proyecto a una carpeta vacia:', destino)
    shutil.copytree(ORIGEN, destino, dirs_exist_ok=True,
                    ignore=shutil.ignore_patterns('data', '.git', '*.png', 'php', 'PERSONAL'))
    print('Sin data/. Sin ajustes. Como un ordenador nuevo.\n')

    # display_errors apagado: es lo que deja php.ini-production, que es lo
    # que copia Preparar.bat. Con esto, un fatal es una pagina en blanco.
    srv = subprocess.Popen(['php', '-d', 'display_errors=0', '-d', 'error_reporting=0',
                            '-S', f'127.0.0.1:{PUERTO}', '-t', destino],
                           stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    time.sleep(2.5)

    print('1 · Ninguna pantalla en blanco')
    for ruta in ['/qa.php', '/index.html', '/pedir.php', '/proyector.php',
                 '/ajustes.php', '/api/estado.php?quien=x']:
        cod, txt = pedir(ruta)
        comprobar(f'{ruta:34s} {cod} {len(txt):6d} bytes',
                  cod == 200 and len(txt) > 400, 'respuesta vacia o error')

    print('\n2 · Las carpetas se crean solas')
    for d in ['data', 'data/videos', 'data/cache']:
        comprobar(d, os.path.isdir(os.path.join(destino, d)), 'no se ha creado')

    print('\n3 · Buscar y pegar enlaces SIN clave  (antes de guardar ninguna)')
    # Este bloque va ANTES de guardar la clave, y no es un detalle de
    # orden: en cuanto hay clave, «sin clave» deja de poder probarse.
    # La primera version guardaba primero y este paso daba rojo por un
    # motivo que no tenia nada que ver con el programa.
    cod, txt = pedir('/api/buscar.php?q=cicatrices')
    j = json.loads(txt)
    comprobar('sin clave, el error dice que se pueden pegar enlaces',
              'pega' in (j.get('error') or '').lower(), j.get('error', '')[:90])
    comprobar('sin clave, el error dice donde se pone',
              'ajustes' in (j.get('error') or '').lower(), j.get('error', '')[:90])

    cod, txt = pedir('/api/buscar.php?q=' + 'https%3A%2F%2Fyoutu.be%2FdQw4w9WgXcQ')
    j = json.loads(txt)
    comprobar('pegar un enlace no revienta', 'Uncaught' not in str(j), str(j)[:120])

    cod, txt = pedir('/api/buscar.php?q=' + 'https%3A%2F%2Fwww.youtube.com%2Fplaylist%3Flist%3DPL1')
    j = json.loads(txt)
    comprobar('una lista se distingue de un video',
              'lista' in (j.get('error') or '').lower(), j.get('error', '')[:90])

    print('\n4 · Guardar la clave de la API')
    # Desde el pool de claves (2026-08) ya no hay un campo "api_key" en
    # el formulario: se guarda por filas, apik_nombre[]/apik_key[]/
    # apik_activa[], y queda en api_keys[0]. api_key se conserva tal
    # cual para quien tuviera una clave suelta de antes, pero ya no se
    # escribe desde aqui (ver ajustes.php:28-33).
    cod, txt = pedir('/ajustes.php', 'POST',
                     'guardar=1&apik_nombre[0]=Principal&'
                     'apik_key[0]=AIzaSyDUMMYKEY1234567890abcdefghijklmnop&'
                     'apik_activa[0]=1&tema=clasico')
    comprobar('responde una pagina, no un blanco', cod == 200 and len(txt) > 400, f'{cod} {len(txt)}b')
    comprobar('dice que ha guardado', 'Guardado' in txt, 'no aparece la confirmacion')
    aj = os.path.join(destino, 'data', 'ajustes.json')
    comprobar('ajustes.json escrito', os.path.isfile(aj), 'no existe')
    if os.path.isfile(aj):
        with open(aj, encoding='utf-8') as f:
            d = json.load(f)
        claves = d.get('api_keys') or []
        comprobar('la clave esta dentro',
                   bool(claves) and claves[0].get('key', '').startswith('AIza'), str(d)[:120])

    print('\n5 · Un JSON corrupto se aparta, no se pisa')
    with open(os.path.join(destino, 'data', 'estado.json'), 'w', encoding='utf-8') as f:
        f.write('{esto no es json')
    cod, txt = pedir('/api/estado.php?quien=x')
    comprobar('la aplicacion sigue arrancando', cod == 200 and '"ok":true' in txt, txt[:90])
    apartados = [x for x in os.listdir(os.path.join(destino, 'data')) if '.roto-' in x]
    comprobar('el archivo roto se ha apartado con su fecha', bool(apartados),
              'se ha perdido: no hay ningun .roto-')

    print('\n6 · qa.php ve el estado real de esta maquina')
    cod, txt = pedir('/qa.php')
    comprobar('avisa del archivo apartado', 'apartad' in txt.lower(), 'no lo menciona')
    comprobar('comprueba los formatos de enlace',
              'Formatos de enlace' in txt and 'NO se reconocen' not in txt,
              'hay formatos que no se reconocen')

    print('\n7 · La red de seguridad, con los errores apagados')
    cod, txt = pedir('/qa.php', 'POST', 'accion=romper')
    comprobar('un fatal NO deja la pantalla en blanco', len(txt) > 400, f'{len(txt)} bytes')
    comprobar('el fatal explica que ha pasado', 'se ha roto' in txt.lower(), txt[:120])

    srv.terminate()
finally:
    shutil.rmtree(destino, ignore_errors=True)

print('\n' + '=' * 62)
if fallos:
    print(f'{len(fallos)} FALLOS en una instalacion limpia:')
    for f in fallos:
        print('  ·', f)
    sys.exit(1)
print('Instalacion limpia: todo bien. Ninguna pantalla en blanco.')
