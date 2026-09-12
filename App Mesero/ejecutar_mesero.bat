@echo off
title Mostaza App Mesero - Salon y Mesas
color 0A
cls
echo ============================================================
echo         MOSTAZA - APP DE MESERO (SALON Y MESAS)
echo ============================================================
echo [1] Iniciar con Expo (Movil Android / Expo Go / Metro)
echo [2] Abrir Terminal Tactil Web en Navegador
echo ============================================================
set /p opc="Selecciona una opcion (1 o 2, default 1): "

if "%opc%"=="2" (
    start "" "http://localhost/PROYECTO_REST/mostaza_web/admin/mesero.php"
    exit
)

echo Iniciando Expo Server para App Mesero...
call npx expo start
pause
