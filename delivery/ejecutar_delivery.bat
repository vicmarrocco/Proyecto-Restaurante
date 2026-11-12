@echo off
title Mostaza Delivery - App de Repartidores (Expo)
color 0E
echo ============================================================
echo   MOSTAZA DELIVERY - APP DE REPARTIDORES (EXPO)
echo   EPET Nº 7 - 7º IV Programacion
echo ============================================================
echo.
echo Iniciando servidor de desarrollo Expo...
echo.
echo - Para probar en NAVEGADOR WEB: Presiona 'w'
echo - Para probar en CELULAR con EXPO GO: Escanea el codigo QR
echo - Para probar en EMULADOR ANDROID: Presiona 'a'
echo.
cd /d "%~dp0"
call npx expo start
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo Ocurrio un error al iniciar Expo.
    pause
)
