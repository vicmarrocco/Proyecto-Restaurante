@echo off
title Mostaza App Mesero - Salon y Mesas
color 0A
cd /d "%~dp0"
cls

:: Sincronizar IP automaticamente con la red actual
if exist "..\auto_update_ip.py" (
    python "..\auto_update_ip.py"
)

for /f "tokens=*" %%i in ('python -c "import socket; s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM); s.connect(('8.8.8.8', 80)); print(s.getsockname()[0]); s.close()" 2^>nul') do set "ACTUAL_IP=%%i"
if "%ACTUAL_IP%"=="" set "ACTUAL_IP=192.168.1.237"

echo ============================================================
echo         MOSTAZA - APP DE MESERO (SALON Y MESAS)
echo         IP Servidor Detectada: %ACTUAL_IP%
echo ============================================================
echo [1] Iniciar con Expo Go (Movil Android / Tablet / Puerto 8082)
echo [2] Abrir Terminal Tactil Web en Navegador
echo [3] Iniciar con Tunel (Si tu celular esta en otra red o datos)
echo ============================================================
set /p opc="Selecciona una opcion (1-3, default 1): "

if "%opc%"=="2" (
    start "" "http://%ACTUAL_IP%/PROYECTO_REST/mostaza_web/admin/mesero.php"
    exit
)
if "%opc%"=="3" (
    echo Iniciando Expo con Tunel para App Mesero...
    call npx expo start --go --tunnel --port 8082
    pause
    exit
)

echo Iniciando Expo Server para App Mesero en LAN (%ACTUAL_IP%:8082)...
set REACT_NATIVE_PACKAGER_HOSTNAME=%ACTUAL_IP%
call npx expo start --go --host lan --port 8082
pause
