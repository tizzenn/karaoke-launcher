@echo off
rem ===================================================================
rem  ASCII PURO, sin tildes ni enes. Con `chcp 65001`, un solo caracter
rem  UTF-8 descoloca el analizador de cmd y a partir de ahi se come el
rem  primer caracter de cada linea. Ver la explicacion larga en
rem  Preparar.bat.
rem ===================================================================
setlocal enabledelayedexpansion
title OpenKaraoke Center v1.1
cd /d "%~dp0"
chcp 65001 >nul 2>&1

set PORT=8123

rem ============================================================
rem  Arranca el servidor PHP escuchando en toda la red local,
rem  para que los moviles de la fiesta puedan entrar al QR.
rem ============================================================

set PHPEXE=
if exist "php\php.exe" set PHPEXE=php\php.exe
if "!PHPEXE!"=="" ( where php >nul 2>&1 && set PHPEXE=php )

if "!PHPEXE!"=="" (
  cls
  echo.
  echo   ================================================================
  echo    Falta PHP, que es lo unico imprescindible.
  echo   ================================================================
  echo.
  echo    Puedo descargarlo e instalarlo aqui mismo, dentro de esta
  echo    carpeta, sin tocar nada de tu sistema.
  echo.
  set /p P=   Abro el preparador ahora? ^(S/N^):
  if /i "!P!"=="S" (
    call "%~dp0Preparar.bat"
  ) else (
    echo.
    echo    De acuerdo. Ejecuta Preparar.bat cuando quieras.
  )
  echo.
  pause
  exit /b 1
)

rem --- averiguar la IP de la wifi ---
set IP=
for /f "tokens=2 delims=:" %%A in ('ipconfig ^| findstr /c:"IPv4"') do (
  for /f "tokens=* delims= " %%B in ("%%A") do (
    echo %%B | findstr /r "^169\." >nul || if "!IP!"=="" set IP=%%B
  )
)
if "!IP!"=="" set IP=localhost

cls
echo.
echo   ================================================================
echo                    K A R A O K E   L A U N C H E R
echo   ================================================================
echo.
echo     En este PC        http://localhost:%PORT%/
echo     Desde los moviles http://!IP!:%PORT%/pedir.php
echo.
echo     Pulsa el boton del movil en la aplicacion para ver el QR.
echo     Todos tienen que estar en la misma wifi.
echo.
echo   ----------------------------------------------------------------
echo     NO CIERRES ESTA VENTANA mientras dure la fiesta.
echo   ----------------------------------------------------------------
echo.

if not exist "data" mkdir data
if not exist "data\videos" mkdir data\videos

if not exist "data\ajustes.json" (
  echo     [!] Sin configurar. Abre Ajustes en la aplicacion y pon tu
  echo         clave de YouTube. Mientras, funcionan los enlaces pegados.
  echo.
)

if not exist "yt-dlp.exe" (
  where yt-dlp >nul 2>&1 || (
    echo     [i] Sin yt-dlp: no podras descargar canciones para cantar
    echo         sin internet. Ejecuta Preparar.bat si lo quieres.
    echo.
  )
)

start "" "http://localhost:%PORT%/"

"!PHPEXE!" -S 0.0.0.0:%PORT% -t "%~dp0"

echo.
echo   Servidor detenido.
pause
