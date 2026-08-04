; ═══════════════════════════════════════════════════════════════════
; Karaoke-Launcher.iss — el instalador de verdad, para quien no quiere
; ni descomprimir un zip.
;
; Distinto del portable (Preparar.bat + Karaoke.bat): este SÍ pide
; permisos de administrador, y solo por un motivo — poder abrir el
; puerto 8123 en el cortafuegos de Windows sin dejarlo a que el aviso
; de Windows aparezca (y a veces se cierre sin querer) la primera noche
; de la fiesta. Todo lo demás sigue exactamente igual que el portable:
; no toca el registro, no instala nada fuera de su propia carpeta, y
; para desinstalar de verdad basta con borrarla — el desinstalador solo
; quita el acceso directo y la regla del cortafuegos.
;
; No empaqueta PHP, yt-dlp ni ffmpeg: los descarga Preparar.bat, igual
; que en el portable. Empaquetarlos aquí los dejaría desactualizados el
; día que YouTube cambie algo, que es la razón por la que ya se bajan
; así en el portable. Ver Preparar.bat.
; ═══════════════════════════════════════════════════════════════════

#define MyAppName "Karaoke Launcher"
#define MyAppVersion "1.1"
#define MyAppPublisher "tizzenn"
#define MyAppURL "https://github.com/tizzenn/karaoke-launcher"

[Setup]
AppId={{8B2B6E1A-6C2F-4B7B-9C4E-2F6C3F0B7A11}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
AppSupportURL={#MyAppURL}
DefaultDirName={autopf}\KaraokeLauncher
DefaultGroupName=Karaoke Launcher
DisableProgramGroupPage=yes
; Admin SOLO para la regla de cortafuegos — ver la nota de arriba. El
; propio instalador no escribe nada fuera de su carpeta.
PrivilegesRequired=admin
OutputDir=..\..\dist
OutputBaseFilename=Karaoke-Launcher-Setup
SetupIconFile=..\iconos\icono.ico
UninstallDisplayIcon={app}\iconos\icono.ico
Compression=lzma2
SolidCompression=yes
WizardStyle=modern
ArchitecturesInstallIn64BitMode=x64compatible
; Nada de licencia GPL en un cuadro de "aceptar o cancelar": una GPL no
; se "acepta" para poder usar el programa, eso es justo lo que el
; software privativo hace mal. El texto vive en LICENSE, dentro.

[Languages]
Name: "spanish"; MessagesFile: "compiler:Languages\Spanish.isl"

[Files]
; Todo publico/, con las mismas exclusiones que ya usa
; Empaquetar-para-revision.bat y por los mismos motivos: nada de datos
; de esta máquina, nada de pruebas, nada de binarios que se bajan solos.
Source: "..\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs; \
  Excludes: "\data\ajustes.json,\data\estado.json,\data\pruebas.json,\data\videos\*,\data\descargas\*,\data\claves_estado.json,\data\cuota.json,\data\presencia.json,\data\errores.log,\data\cache\*,\data\pruebas.roto-*.json,\.git\*,e2e\*,\php\*,\instalador\*,\dist\*,yt-dlp.exe,ffmpeg.exe,ffprobe.exe,*.zip"

[Dirs]
; Igual que Preparar.bat: las carpetas se crean solas la primera vez
; que hace falta, pero dejarlas ya listas evita el primer aviso de PHP
; sobre permisos si el antivirus es especialmente suspicaz con carpetas
; nuevas creadas por PHP en caliente.
Name: "{app}\data\videos"
Name: "{app}\data\cache"
Name: "{app}\data\descargas"

[Icons]
Name: "{group}\Karaoke Launcher"; Filename: "{app}\Karaoke.bat"; WorkingDir: "{app}"; IconFilename: "{app}\iconos\icono.ico"
Name: "{group}\Preparar (primera vez o reparar)"; Filename: "{app}\Preparar.bat"; WorkingDir: "{app}"
Name: "{group}\Desinstalar Karaoke Launcher"; Filename: "{uninstallexe}"
Name: "{autodesktop}\Karaoke Launcher"; Filename: "{app}\Karaoke.bat"; WorkingDir: "{app}"; IconFilename: "{app}\iconos\icono.ico"; Tasks: escritorio

[Tasks]
Name: "escritorio"; Description: "Crear un acceso directo en el escritorio"; GroupDescription: "Accesos directos:"

[Code]
var
  PageComplementos: TInputOptionWizardPage;

procedure InitializeWizard;
begin
  { La página que pide TRASPASO: elegir qué complementos instalar, con
    el tamaño de cada uno y qué desbloquea cada uno — mismo lenguaje y
    mismos números que ya usa Preparar.bat (que es quien de verdad los
    descarga, un momento después de esto). }
  PageComplementos := CreateInputOptionPage(wpSelectTasks,
    'Descargar sin depender de la wifi de la fiesta',
    'yt-dlp y FFmpeg son opcionales',
    'Con estos dos, las canciones se pueden descargar de antemano y sonar ' +
    'aunque la wifi falle a mitad de fiesta. Sin ellos, todo sigue ' +
    'funcionando igual: las canciones se reproducen desde YouTube en directo.' +
    Chr(13) + Chr(10) + Chr(13) + Chr(10) +
    'Se descargan de sus webs oficiales al terminar esta instalación ' +
    '(hace falta internet en ese momento, no durante la fiesta).',
    False, False);
  PageComplementos.Add('yt-dlp — descargar canciones (unos 18 MB)');
  PageComplementos.Add('FFmpeg — lo que yt-dlp necesita para funcionar (unos 162 MB)');
  PageComplementos.Values[0] := True;
  PageComplementos.Values[1] := True;
end;

function ObtenerFlagExtras(Param: String): String;
begin
  { Si se han pedido las dos, Preparar.bat sigue su camino normal —
    pregunta él mismo, con sus propios números, tal cual hace en el
    portable. Si se ha pedido "sin extras", se lo decimos por variable
    de entorno para que no vuelva a preguntar algo que ya se ha
    contestado en este mismo asistente. }
  if PageComplementos.Values[0] or PageComplementos.Values[1] then
    Result := '0'
  else
    Result := '1';
end;

procedure AplicarReglaCortafuegos;
var
  ResultCode: Integer;
begin
  { El arreglo con más impacto de todo el instalador: sin esto, la
    primera vez que un móvil intenta pedir una canción, Windows puede
    haber preguntado "¿permitir el acceso?" y a nadie se le ocurre
    mirar esa ventana en mitad de una fiesta — y si se cierra sin
    responder, el cortafuegos se queda bloqueando en silencio el resto
    de la noche. Con la regla ya puesta, esa pregunta no llega a salir. }
  Exec(ExpandConstant('{sys}\netsh.exe'), ExpandConstant(
    'advfirewall firewall add rule name="Karaoke Launcher" dir=in action=allow protocol=TCP localport=8123 program="{app}\php\php.exe" enable=yes profile=private,domain'),
    '', SW_HIDE, ewWaitUntilTerminated, ResultCode);
end;

procedure CurStepChanged(CurStep: TSetupStep);
begin
  if CurStep = ssPostInstall then
  begin
    AplicarReglaCortafuegos;
  end;
end;

[Run]
; Preparar.bat en su propia consola, visible: es la misma pantalla que
; ya se ha probado a fondo esta sesión (PHP, yt-dlp, ffmpeg, el arreglo
; de php.ini). No se oculta con /B: el usuario tiene que poder leer
; "OK PHP" antes de que la ventana se cierre sola.
Filename: "{cmd}"; Parameters: "/C set KARAOKE_SIN_EXTRAS={code:ObtenerFlagExtras}&& ""{app}\Preparar.bat"""; \
  WorkingDir: "{app}"; Description: "Preparar (descarga PHP y, si se ha pedido, yt-dlp/FFmpeg)"; \
  Flags: postinstall runascurrentuser skipifsilent

[UninstallDelete]
; Los datos de la fiesta (data\) NO se borran al desinstalar — es
; justo lo que se pierde si se hace, y nada obliga a desinstalar antes
; de una fiesta. Solo se limpia lo que el propio instalador puso.
Type: filesandordirs; Name: "{app}\php"
