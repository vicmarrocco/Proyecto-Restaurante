@echo off
title Mostaza App Cocina - KDS (Expo Go)
color 0C
cd /d "%~dp0"
echo ============================================================
echo   MOSTAZA - APP DE COCINA KDS (EXPO GO)
echo   IP Servidor Central: 192.168.0.19
echo   Puerto Expo Metro: 8081
echo ============================================================
echo.
set REACT_NATIVE_PACKAGER_HOSTNAME=192.168.0.19
call npx expo start --go --host lan --port 8081
pause
