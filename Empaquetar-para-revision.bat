@echo off
REM ===================================================================
REM  Empaquetar-para-revision.bat
REM
REM  Genera micro-abierto-revision.zip con el proyecto PUBLICO, listo
REM  para darselo a alguien de fuera que vaya a revisarlo.
REM
REM  ATENCION: este archivo tiene que quedarse en ASCII puro. Sin
REM  acentos, sin enyes, sin signos de apertura. Con chcp 65001 un solo
REM  caracter multibyte desincroniza el interprete de cmd y se come la
REM  primera letra de las lineas siguientes. Ya paso una vez.
REM
REM  Lo que NUNCA entra en el zip:
REM    data\ajustes.json   la clave de la API
REM    data\estado.json    la biblioteca personal
REM    data\videos         las descargas
REM    PERSONAL            la copia de las fiestas, entera
REM ===================================================================
setlocal
cd /d "%~dp0"

set DESTINO=%~dp0..\micro-abierto-revision.zip
set TEMPORAL=%TEMP%\micro-abierto-revision

echo Preparando copia limpia...
if exist "%TEMPORAL%" rmdir /s /q "%TEMPORAL%"
mkdir "%TEMPORAL%"

robocopy "%~dp0." "%TEMPORAL%" /e /nfl /ndl /njh /njs /nc /ns /np ^
  /xd data\videos data\descargas .git ^
  /xf ajustes.json estado.json pruebas.json >nul

echo Comprobando que no se cuela ninguna clave...
REM  La marca va partida en dos a proposito. Si estuviera entera, este
REM  mismo archivo la contendria, robocopy lo copia a la carpeta
REM  temporal, y el findstr se encontraria a si mismo: el guion se
REM  negaria SIEMPRE a generar el zip. Paso en la primera version.
set MARCA=AIza
findstr /s /m /c:"%MARCA%Sy" "%TEMPORAL%\*" >nul 2>&1
if not errorlevel 1 (
  echo.
  echo   PARADO. Hay una clave de API dentro de la copia.
  echo   No se genera el zip. Revisa que archivo la lleva.
  echo.
  rmdir /s /q "%TEMPORAL%"
  pause
  exit /b 1
)

echo Comprimiendo...
if exist "%DESTINO%" del "%DESTINO%"
powershell -NoProfile -Command "Compress-Archive -Path %TEMPORAL%\* -DestinationPath %DESTINO% -Force"
rmdir /s /q "%TEMPORAL%"

echo.
echo Listo: %DESTINO%
echo.
pause
