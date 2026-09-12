@echo off
title Mostaza App Cocina - KDS
color 0C
cls
echo ============================================================
echo         MOSTAZA - APP DE COCINA KDS (EN VIVO)
echo ============================================================
echo [1] Iniciar con Expo (Movil Android / Tablet / Metro)
echo [2] Abrir Pantalla KDS en Navegador (Pantalla Completa)
echo ============================================================
set /p opc="Selecciona una opcion (1 o 2, default 1): "

if "%opc%"=="2" (
    start "" "http://localhost/PROYECTO_REST/mostaza_web/admin/cocina.php"
    exit
)

echo Iniciando Expo Server para App Cocina...
call npx expo start
pause
