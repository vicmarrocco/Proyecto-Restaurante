@echo off
title Mostaza App Mesero - Salon y Mesas
color 0A
cls
echo ============================================================
echo         MOSTAZA - APP DE MESERO (SALON Y MESAS)
echo ============================================================
echo [1] Iniciar con Expo Go (Movil Android / Tablet / Puerto 8082)
echo [2] Abrir Terminal Tactil Web en Navegador
echo [3] Iniciar con Tunel (Si tu celular esta en otra red o datos)
echo ============================================================
set /p opc="Selecciona una opcion (1-3, default 1): "

if "%opc%"=="2" (
    start "" "http://10.0.5.77/PROYECTO_REST/mostaza_web/admin/mesero.php"
    exit
)
if "%opc%"=="3" (
    echo Iniciando Expo con Tunel para App Mesero...
    call npx expo start --go --tunnel --port 8082
    pause
    exit
)

echo Iniciando Expo Server para App Mesero en LAN (10.0.5.77:8082)...
set REACT_NATIVE_PACKAGER_HOSTNAME=10.0.5.77
call npx expo start --go --host lan --port 8082
pause
