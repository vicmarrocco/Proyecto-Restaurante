@echo off
title Mostaza App Cocina - KDS (Expo Go)
color 0C
cd /d "%~dp0"
echo ============================================================
echo   MOSTAZA - APP DE COCINA KDS (EXPO GO)
echo   IP Servidor Central: 10.0.5.77
echo   Puerto Expo Metro: 8081
echo ============================================================
echo.
set REACT_NATIVE_PACKAGER_HOSTNAME=10.0.5.77
call npx expo start --go --host lan --port 8081
pause
