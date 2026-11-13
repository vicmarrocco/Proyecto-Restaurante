@echo off
title Mostaza Delivery - App de Repartidores (Expo)
color 0E
cd /d "%~dp0"
cls

if exist "..\auto_update_ip.py" (
    python "..\auto_update_ip.py"
)

for /f "tokens=*" %%i in ('python -c "import socket; s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM); s.connect(('8.8.8.8', 80)); print(s.getsockname()[0]); s.close()" 2^>nul') do set "ACTUAL_IP=%%i"
if "%ACTUAL_IP%"=="" set "ACTUAL_IP=192.168.1.237"

echo ============================================================
echo   MOSTAZA DELIVERY - APP DE REPARTIDORES (EXPO)
echo   IP Servidor Central Detectada: %ACTUAL_IP%
echo   EPET Nº 7 - 7º IV Programacion
echo ============================================================
echo [1] Iniciar con Expo Go (Movil Android / Tablet / Puerto 8083)
echo [2] Abrir App en Navegador Web
echo [3] Iniciar con Tunel (Si el celular esta en datos o red diferente)
echo ============================================================
set /p opc="Selecciona una opcion (1-3, default 1): "

if "%opc%"=="2" (
    call npx expo start --web --port 8083
    exit
)
if "%opc%"=="3" (
    echo Iniciando Expo con Tunel para App Delivery...
    call npx expo start --go --tunnel --port 8083 -c
    pause
    exit
)

echo Iniciando Expo Server para App Delivery en LAN (%ACTUAL_IP%:8083)...
set REACT_NATIVE_PACKAGER_HOSTNAME=%ACTUAL_IP%
call npx expo start --go --host lan --port 8083 -c
pause
