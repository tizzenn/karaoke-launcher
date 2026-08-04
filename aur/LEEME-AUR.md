# Publicar en AUR — lo que falta

Todo lo de esta carpeta se ha escrito y revisado a mano, pero **no se ha
podido compilar ni probar**: este entorno es Windows, sin `makepkg` ni
ningún Arch Linux real donde ejecutarlo. Antes de subirlo, alguien con un
Arch (o una máquina virtual / contenedor con `base-devel`) tiene que:

1. **Comprobarlo de verdad**:
   ```
   makepkg -si
   ```
   Esto compila el paquete, lo instala, y de paso avisa de cualquier error
   de sintaxis del `PKGBUILD` que aquí no se ha podido ver.

2. **Probar el lanzador**: ejecutar `karaoke-launcher` desde una cuenta de
   usuario normal (no root) y comprobar que:
   - crea `~/.local/share/karaoke-launcher` y sincroniza los archivos;
   - `data/` se puede escribir (ajustes, biblioteca, cola);
   - el navegador se abre solo en `http://localhost:8123/`;
   - el QR de los móviles apunta a la IP real de la wifi, no a `localhost`.

3. **Generar el `.SRCINFO`** (obligatorio para subir a AUR, no está en
   esta carpeta porque solo lo genera `makepkg` en un Arch real):
   ```
   makepkg --printsrcinfo > .SRCINFO
   ```

4. **Crear la cuenta en AUR** (https://aur.archlinux.org/register) si no
   existe ya una, y subir la clave SSH pública de esa máquina en
   *My Account*.

5. **Subir el paquete**:
   ```
   git clone ssh://aur@aur.archlinux.org/karaoke-launcher-git.git aur-repo
   cp PKGBUILD .SRCINFO karaoke-launcher karaoke-launcher.desktop aur-repo/
   cd aur-repo
   git add -A
   git commit -m "Paquete inicial: Karaoke Launcher"
   git push
   ```

## Por qué es un paquete `-git`, no una versión con número

El repositorio `tizzenn/karaoke-launcher` trabaja en la rama `desarrollo`,
sin tags de versión todavía. Un paquete `-git` sigue esa rama directamente
y usa `pkgver()` para calcular una versión a partir del propio historial de
git (`r<commits>.<hash>`) — es la convención estándar en AUR para software
que aún no publica releases con número. El día que exista un tag de
verdad (ej. `v1.2`), tiene sentido añadir también un paquete
`karaoke-launcher` normal (sin `-git`) que siga los tags, y dejar el `-git`
para quien quiera la rama de desarrollo al día.

## Por qué el lanzador copia los archivos a `~/.local/share`

`api/comun.php` calcula la carpeta `data/` como una ruta relativa a la
propia carpeta de la app (`__DIR__ . '/../data'`), no al directorio desde
el que se ejecuta PHP. Eso es correcto para el portable de Windows —el
usuario ya controla esa carpeta entera— pero en Arch el paquete se
instala en `/usr/share`, que es de solo lectura para un usuario normal.
Por eso el lanzador mantiene una copia de trabajo por usuario, sincronizada
con el código instalado (que pacman actualiza) sin tocar la carpeta
`data/` de cada uno. Es la misma idea que ya usa el instalador de Windows
para no empaquetar PHP/yt-dlp/ffmpeg directamente: cada plataforma resuelve
"qué es del sistema y qué es del usuario" con sus propias herramientas.
