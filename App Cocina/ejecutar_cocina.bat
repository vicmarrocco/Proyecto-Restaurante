@echo off
title Mostaza App Cocina - KDS
color 0C
cls
echo ============================================================
echo         MOSTAZA - APP DE COCINA KDS (EN VIVO)
echo ============================================================
echo [1] Iniciar con Expo Go (Movil Android / Tablet / Puerto 8081)
echo [2] Abrir Pantalla KDS en Navegador Web (Pantalla Completa)
echo [3] Iniciar con Tunel (Si tu celular esta en otra red o datos)
echo ============================================================
set /p opc="Selecciona una opcion (1-3, default 1): "

if "%opc%"=="2" (
    start "" "http://10.0.5.77/PROYECTO_REST/mostaza_web/admin/cocina.php"
    exit
)
if "%opc%"=="3" (
    echo Iniciando Expo con Tunel para App Cocina...
    call npx expo start --go --tunnel --port 8081
    pause
    exit
)

echo Iniciando Expo Server para App Cocina en LAN (10.0.5.77:8081)...
set REACT_NATIVE_PACKAGER_HOSTNAME=10.0.5.77
call npx expo start --go --host lan --port 8081
pause
