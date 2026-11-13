@echo off
title Mostaza App Cliente - Pedidos y Catalogo
color 0C
cd /d "%~dp0"
cls

:: Sincronizar IP automaticamente con la red actual
if exist "..\auto_update_ip.py" (
    python "..\auto_update_ip.py"
)

:: Verificar e instalar dependencias si faltan
if not exist "node_modules\" (
    echo ============================================================
    echo [AVISO] Instalando paquetes y Expo SDK por primera vez...
    echo Esto puede demorar unos minutos, por favor aguarda.
    echo ============================================================
    call npm install
)

for /f "tokens=*" %%i in ('python -c "import socket; s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM); s.connect(('8.8.8.8', 80)); print(s.getsockname()[0]); s.close()" 2^>nul') do set "ACTUAL_IP=%%i"
if "%ACTUAL_IP%"=="" set "ACTUAL_IP=192.168.1.237"

echo ============================================================
echo         MOSTAZA - APP CLIENTE (PEDIDOS Y PROMOS)
echo         IP Servidor Detectada: %ACTUAL_IP%
echo ============================================================
echo [1] Iniciar con Expo Go (Movil Android / Tablet / Puerto 8084)
echo [2] Abrir Tienda Web en Navegador
echo [3] Iniciar con Tunel (Si tu celular esta en otra red o datos)
echo ============================================================
set /p opc="Selecciona una opcion (1-3, default 1): "

if "%opc%"=="2" (
    start "" "http://%ACTUAL_IP%/PROYECTO_REST/mostaza_web/index.php"
    exit
)
if "%opc%"=="3" (
    echo Iniciando Expo con Tunel para App Cliente...
    call npx expo start --go --tunnel --port 8084
    pause
    exit
)

echo Iniciando Expo Server para App Cliente en LAN (%ACTUAL_IP%:8084)...
set REACT_NATIVE_PACKAGER_HOSTNAME=%ACTUAL_IP%
call npx expo start --go --host lan --port 8084
pause
