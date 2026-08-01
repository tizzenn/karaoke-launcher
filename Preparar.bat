@echo off
setlocal enabledelayedexpansion
title Preparar Karaoke Launcher
cd /d "%~dp0"
chcp 65001 >nul 2>&1

rem ============================================================
rem  Comprueba qué falta y ofrece descargarlo.
rem  Todo se instala DENTRO de esta carpeta, en modo portable:
rem  no toca el PATH, no toca el registro, no instala nada en
rem  el sistema. Para desinstalar, borras la carpeta.
rem
rem  Descargas, todas de las webs oficiales:
rem    PHP     windows.php.net
rem    yt-dlp  github.com/yt-dlp/yt-dlp
rem    ffmpeg  github.com/yt-dlp/FFmpeg-Builds
rem    certs   curl.se/ca/cacert.pem
rem
rem  Los certificados no son un extra: el PHP portable viene sin ellos y
rem  sin ellos PHP no puede abrir NINGUNA direccion https. La busqueda en
rem  YouTube falla entera y el error que sale culpa a la wifi.
rem ============================================================

cls
echo.
echo   ================================================================
echo               P R E P A R A R   E L   K A R A O K E
echo   ================================================================
echo.
echo    Voy a comprobar qué tienes y qué falta.
echo    Nada se instala en el sistema: todo va dentro de esta carpeta.
echo.
echo   ----------------------------------------------------------------
echo.

set FALTA_PHP=0
set FALTA_YTDLP=0
set FALTA_FFMPEG=0

rem --------- PHP (obligatorio) ---------
set PHPEXE=
if exist "php\php.exe" set PHPEXE=php\php.exe
if "!PHPEXE!"=="" ( where php >nul 2>&1 && set PHPEXE=php )

if "!PHPEXE!"=="" (
  echo    [  FALTA  ]  PHP          - obligatorio, sin esto no arranca
  set FALTA_PHP=1
) else (
  for /f "tokens=2" %%V in ('"!PHPEXE!" -v 2^>nul ^| findstr /b "PHP"') do set PHPVER=%%V
  echo    [    OK   ]  PHP !PHPVER!
)

rem --------- yt-dlp (opcional) ---------
set YTDLP=
if exist "yt-dlp.exe" set YTDLP=yt-dlp.exe
if "!YTDLP!"=="" ( where yt-dlp >nul 2>&1 && set YTDLP=yt-dlp )

if "!YTDLP!"=="" (
  echo    [  FALTA  ]  yt-dlp       - opcional, para cantar sin internet
  set FALTA_YTDLP=1
) else (
  echo    [    OK   ]  yt-dlp
)

rem --------- ffmpeg (necesario para yt-dlp) ---------
set FFMPEG=
if exist "ffmpeg.exe" set FFMPEG=ffmpeg.exe
if "!FFMPEG!"=="" ( where ffmpeg >nul 2>&1 && set FFMPEG=ffmpeg )

if "!FFMPEG!"=="" (
  echo    [  FALTA  ]  ffmpeg       - opcional, yt-dlp lo necesita
  set FALTA_FFMPEG=1
) else (
  echo    [    OK   ]  ffmpeg
)

echo.
echo   ----------------------------------------------------------------
echo.

set /a TOTAL=%FALTA_PHP%+%FALTA_YTDLP%+%FALTA_FFMPEG%
if %TOTAL%==0 (
  echo    No falta nada. Cierra esto y abre Karaoke.bat
  echo.
  pause
  exit /b 0
)

echo    Lo que falta se descarga de sus webs oficiales:
echo.
if %FALTA_PHP%==1     echo      PHP      https://windows.php.net/downloads/releases/
if %FALTA_YTDLP%==1   echo      yt-dlp   https://github.com/yt-dlp/yt-dlp/releases/
if %FALTA_FFMPEG%==1  echo      ffmpeg   https://github.com/yt-dlp/FFmpeg-Builds/releases/
echo.
echo    Según lo que falte: PHP unos 35 MB, yt-dlp 18 MB y ffmpeg 162 MB.
echo    Los tres juntos pasan de 200 MB, así que hazlo con tiempo.
echo.
set /p SI=   ¿Descargo lo que falta? (S/N):
if /i not "!SI!"=="S" (
  echo.
  echo    De acuerdo, no descargo nada.
  echo    Puedes instalarlo tú a mano; mira LEEME.md
  echo.
  pause
  exit /b 0
)

echo.
echo   ----------------------------------------------------------------
echo.

rem ================= PHP =================
if %FALTA_PHP%==1 (
  echo    Descargando PHP...
  powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "$ErrorActionPreference='Stop';" ^
    "[Net.ServicePointManager]::SecurityProtocol=[Net.SecurityProtocolType]::Tls12;" ^
    "$p='https://windows.php.net/downloads/releases/';" ^
    "$h=(Invoke-WebRequest -UseBasicParsing $p).Links.href;" ^
    "$z=$h | Where-Object { $_ -match 'php-8\.\d+\.\d+-Win32-vs\d+-x64\.zip$' -and $_ -notmatch 'nts' } | Sort-Object { [version][regex]::Match($_,'\d+\.\d+\.\d+').Value } | Select-Object -Last 1;" ^
    "if(-not $z){ throw 'no encuentro el zip de PHP en la pagina oficial' };" ^
    "$u=$p+($z -replace '^.*/','');" ^
    "Write-Host ('      ' + $u);" ^
    "Invoke-WebRequest -UseBasicParsing $u -OutFile 'php.zip';" ^
    "Expand-Archive -Path 'php.zip' -DestinationPath 'php' -Force;" ^
    "Remove-Item 'php.zip';" ^
    "if(Test-Path 'php\php.ini-production'){ Copy-Item 'php\php.ini-production' 'php\php.ini' -Force }" ^
    "elseif(Test-Path 'php\php.ini-development'){ Copy-Item 'php\php.ini-development' 'php\php.ini' -Force };" ^
    "Invoke-WebRequest -UseBasicParsing 'https://curl.se/ca/cacert.pem' -OutFile 'php\cacert.pem';" ^
    "$ini='php\php.ini';" ^
    "if(Test-Path $ini){" ^
    "  $t=Get-Content $ini -Raw;" ^
    "  $ext=(Resolve-Path 'php\ext').Path;" ^
    "  $ca=(Resolve-Path 'php\cacert.pem').Path;" ^
    "  $t=$t -replace '(?m)^\s*;?\s*extension_dir\s*=.*$', ('extension_dir = '+[char]34+$ext+[char]34);" ^
    "  foreach($e in 'curl','openssl','mbstring'){ $t=$t -replace ('(?m)^\s*;\s*(extension\s*=\s*'+$e+')\s*$'), '$1' };" ^
    "  $t=$t -replace '(?m)^\s*;?\s*curl\.cainfo\s*=.*$', ('curl.cainfo = '+[char]34+$ca+[char]34);" ^
    "  $t=$t -replace '(?m)^\s*;?\s*openssl\.cafile\s*=.*$', ('openssl.cafile = '+[char]34+$ca+[char]34);" ^
    "  if($t -notmatch '(?m)^curl\.cainfo'){ $t=$t+[Environment]::NewLine+'curl.cainfo = '+[char]34+$ca+[char]34 };" ^
    "  if($t -notmatch '(?m)^openssl\.cafile'){ $t=$t+[Environment]::NewLine+'openssl.cafile = '+[char]34+$ca+[char]34 };" ^
    "  [IO.File]::WriteAllText((Resolve-Path $ini).Path, $t, (New-Object Text.UTF8Encoding $false)) }"
  if errorlevel 1 (
    echo    [X] No he podido instalar PHP automáticamente.
    echo        Bájalo a mano de https://windows.php.net/download/
    echo        version "Thread Safe" x64, y descomprímelo en una carpeta
    echo        llamada  php  dentro de esta misma carpeta.
    echo.
    pause
    exit /b 1
  )
  echo    [OK] PHP instalado en .\php\
  echo.
)

rem ================= yt-dlp =================
if %FALTA_YTDLP%==1 (
  echo    Descargando yt-dlp...
  powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "$ErrorActionPreference='Stop';" ^
    "[Net.ServicePointManager]::SecurityProtocol=[Net.SecurityProtocolType]::Tls12;" ^
    "Invoke-WebRequest -UseBasicParsing 'https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp.exe' -OutFile 'yt-dlp.exe'"
  if errorlevel 1 (
    echo    [!] No he podido descargar yt-dlp. Seguimos sin él:
    echo        solo significa que no podrás cantar sin internet.
  ) else (
    echo    [OK] yt-dlp.exe descargado
  )
  echo.
)

rem ================= ffmpeg =================
if %FALTA_FFMPEG%==1 (
  echo    Descargando ffmpeg... (es el más pesado, paciencia)
  powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "$ErrorActionPreference='Stop';" ^
    "[Net.ServicePointManager]::SecurityProtocol=[Net.SecurityProtocolType]::Tls12;" ^
    "$u='https://github.com/yt-dlp/FFmpeg-Builds/releases/latest/download/ffmpeg-master-latest-win64-gpl.zip';" ^
    "Invoke-WebRequest -UseBasicParsing $u -OutFile 'ff.zip';" ^
    "Expand-Archive -Path 'ff.zip' -DestinationPath 'ff_tmp' -Force;" ^
    "Get-ChildItem 'ff_tmp' -Recurse -Include 'ffmpeg.exe','ffprobe.exe' | ForEach-Object { Copy-Item $_.FullName '.' -Force };" ^
    "Remove-Item 'ff.zip','ff_tmp' -Recurse -Force"
  if errorlevel 1 (
    echo    [!] No he podido descargar ffmpeg. Sin él yt-dlp descarga
    echo        el vídeo pero no puede juntarlo con el audio.
  ) else (
    echo    [OK] ffmpeg.exe descargado
  )
  echo.
)

rem --- dejar la ruta de yt-dlp escrita en los ajustes ---
if exist "yt-dlp.exe" (
  if not exist "data" mkdir data
  powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "$f='data\ajustes.json';" ^
    "$o = if(Test-Path $f){ Get-Content $f -Raw | ConvertFrom-Json } else { [pscustomobject]@{} };" ^
    "$o | Add-Member -NotePropertyName yt_dlp -NotePropertyValue 'yt-dlp.exe' -Force;" ^
    "$j = $o | ConvertTo-Json -Depth 5;" ^
    "[IO.File]::WriteAllText((Join-Path (Get-Location) $f), $j, (New-Object Text.UTF8Encoding $false))" 2>nul
)

echo   ================================================================
echo.
echo      Listo. Ya puedes cerrar esto y abrir  Karaoke.bat
echo.
echo   ================================================================
echo.
pause
