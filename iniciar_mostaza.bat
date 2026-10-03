@echo off
title Ecosistema Mostaza - Selector de Terminales y Apps
color 0C
:menu
cls
echo =======================================================================
echo          SISTEMA GASTRONOMICO MOSTAZA - SUCURSAL FORMOSA
echo                   IP Servidor Central: 10.0.5.77
echo =======================================================================
echo.
echo  --- TERMINALES DE ESCRITORIO (PYTHON POS - CAJEROS) ---
echo   [1] Iniciar Caja 1 (Mostrador Principal - P.V. 0001) [F11 Fullscreen]
echo   [2] Iniciar Caja 2 (Express / Sal?n - P.V. 0002)     [F11 Fullscreen]
echo.
echo  --- APLICACIONES MOVILES / TABLETS (EXPO GO) ---
echo   [3] Iniciar App Cocina   (KDS Comandas - Expo Go :8081)
echo   [4] Iniciar App Mesero   (Salon y Mesas - Expo Go :8082)
echo   [5] Iniciar App Delivery (Repartidores - Expo Go :8083)
echo.
echo  --- PLATAFORMAS WEB ---
echo   [6] Abrir Panel de Control Admin (Auditor?a y Arqueos)
echo   [7] Abrir Mostaza Web (Carta y Pedidos Online)
echo.
echo   [0] Salir
echo =======================================================================
set /p opc="Seleccione la terminal o app que desea iniciar (0-7): "

if "%opc%"=="1" (
    echo Iniciando Caja 1...
    start "" cmd /k "cd /d "%~dp0App Cajero" && ejecutar_pos.bat"
    goto menu
)
if "%opc%"=="2" (
    echo Iniciando Caja 2...
    start "" cmd /k "cd /d "%~dp0App Cajero 2" && ejecutar_caja2.bat"
    goto menu
)
if "%opc%"=="3" (
    echo Iniciando App Cocina en Expo (Puerto 8081)...
    start "" cmd /k "cd /d "%~dp0App Cocina" && set REACT_NATIVE_PACKAGER_HOSTNAME=10.0.5.77 && npx expo start --go --host lan --port 8081"
    goto menu
)
if "%opc%"=="4" (
    echo Iniciando App Mesero en Expo (Puerto 8082)...
    start "" cmd /k "cd /d "%~dp0App Mesero" && set REACT_NATIVE_PACKAGER_HOSTNAME=10.0.5.77 && npx expo start --go --host lan --port 8082"
    goto menu
)
if "%opc%"=="5" (
    echo Iniciando App Delivery en Expo (Puerto 8083)...
    start "" cmd /k "cd /d "%~dp0delivery" && set REACT_NATIVE_PACKAGER_HOSTNAME=10.0.5.77 && npx expo start --go --host lan --port 8083"
    goto menu
)
if "%opc%"=="6" (
    start "" "http://10.0.5.77/PROYECTO_REST/mostaza_web/admin/caja.php"
    goto menu
)
if "%opc%"=="7" (
    start "" "http://10.0.5.77/PROYECTO_REST/mostaza_web/index.php"
    goto menu
)
if "%opc%"=="0" exit
goto menu
