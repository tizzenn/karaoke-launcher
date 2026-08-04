@echo off
rem ===================================================================
rem  ESTE ARCHIVO TIENE QUE SER ASCII PURO. Sin tildes, sin enes, sin
rem  signos de apertura. Ni siquiera dentro de un rem.
rem
rem  cmd.exe lee los .bat byte a byte. Con `chcp 65001` y un caracter
rem  UTF-8 de dos bytes en cualquier linea, el analizador pierde la
rem  cuenta y se come el primer caracter de las lineas siguientes.
rem ===================================================================
setlocal enabledelayedexpansion
title Crear mi copia de OpenKaraoke Center
cd /d "%~dp0"
chcp 65001 >nul 2>&1

rem ===================================================================
rem  Crea o actualiza una copia PERSONAL a partir de esta carpeta.
rem
rem  Para que existe: la carpeta publica es la que se comparte y la que
rem  va a GitHub. Tu copia personal tiene dentro tu clave de la API, tu
rem  biblioteca y tus videos descargados, y NO puede subirse a ningun
rem  sitio. Tenerlas separadas evita el accidente de un solo comando.
rem
rem  Lo que hace:
rem    - copia el CODIGO (paginas, js, css, api, iconos, .bat)
rem    - NO toca data\  de la copia personal: ahi viven tu clave, tu
rem      biblioteca y tus descargas. Esa es la regla entera.
rem
rem  Asi que se puede ejecutar mil veces: actualiza el programa y deja
rem  tus cosas donde estaban.
rem ===================================================================

set DESTINO=%~1
if "%DESTINO%"=="" set DESTINO=..\PERSONAL

echo.
echo   ================================================================
echo    Crear o actualizar tu copia personal
echo   ================================================================
echo.
echo    Origen:  %CD%
echo    Destino: %DESTINO%
echo.
echo    Se copia el programa. NO se toca la carpeta data del destino:
echo    tu clave, tu biblioteca y tus videos se quedan como estan.
echo.
set /p SEGUIR=   Continuar? (S/N):
if /i not "%SEGUIR%"=="S" goto :fin

rem ---- Las carpetas, todas de golpe -------------------------------
rem  Varias se quedan vacias hoy y da igual: el dia que hagan falta,
rem  estan. Mover archivos de sitio dentro de un ano es lo caro; crear
rem  una carpeta vacia hoy no cuesta nada.
for %%D in (
  data
  data\videos
  data\ambiente
  data\copias
  data\importar
  data\exportar
) do (
  if not exist "%DESTINO%\%%D" mkdir "%DESTINO%\%%D" 2>nul
)

rem ---- El codigo ---------------------------------------------------
rem  /XD data  es la linea que protege tus cosas. Si un dia se borra,
rem  este archivo pasa de actualizar el programa a pisar tu biblioteca.
rem
rem  /XD solo reconoce nombres de carpeta sueltos o rutas absolutas, no
rem  rutas relativas compuestas: "pruebas\e2e" no excluia nada y no
rem  avisaba. Comprobado copiando de verdad. Con solo "e2e" si funciona.
robocopy "%CD%" "%DESTINO%" /E /XD data .git e2e /XF *.zip .gitignore >nul

if errorlevel 8 (
  echo.
  echo    [ERROR] No he podido copiar. Comprueba que la carpeta destino
  echo    no esta abierta en otra ventana y vuelve a intentarlo.
  goto :fin
)

rem ---- Un aviso dentro de la carpeta -------------------------------
rem  Dentro de seis meses habra dos carpetas parecidas y esto dice cual
rem  es cual sin tener que abrir nada.
> "%DESTINO%\LEEME-ESTA-ES-MI-COPIA.txt" (
  echo Esta es TU copia de OpenKaraoke Center.
  echo.
  echo Dentro de data\ estan:
  echo   ajustes.json    tu clave de la API de YouTube
  echo   estado.json     tu biblioteca y tu cola
  echo   videos\         los karaokes que hayas descargado
  echo.
  echo NO subas esta carpeta a GitHub ni la compartas: lleva tu clave
  echo dentro. Para compartir el programa, comparte la carpeta publica.
  echo.
  echo Para actualizar el programa sin perder nada: ejecuta otra vez
  echo Crear-mi-copia.bat desde la carpeta publica.
)

echo.
echo   ================================================================
echo    Listo.
echo   ================================================================
echo.
echo    Tu copia esta en: %DESTINO%
echo    Arrancala con Karaoke.bat DESDE ESA CARPETA.
echo.
echo    Tus ajustes y tu biblioteca no se han tocado.
echo.

:fin
pause
