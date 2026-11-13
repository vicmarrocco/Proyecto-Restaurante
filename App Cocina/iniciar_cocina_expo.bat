@echo off
title Mostaza App Cocina - KDS (Expo Go)
color 0C
cd /d "%~dp0"

if exist "..\auto_update_ip.py" (
    python "..\auto_update_ip.py"
)

for /f "tokens=*" %%i in ('python -c "import socket; s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM); s.connect(('8.8.8.8', 80)); print(s.getsockname()[0]); s.close()" 2^>nul') do set "ACTUAL_IP=%%i"
if "%ACTUAL_IP%"=="" set "ACTUAL_IP=192.168.1.237"

echo ============================================================
echo   MOSTAZA - APP DE COCINA KDS (EXPO GO)
echo   IP Servidor Central Detectada: %ACTUAL_IP%
echo   Puerto Expo Metro: 8081
echo ============================================================
echo.
set REACT_NATIVE_PACKAGER_HOSTNAME=%ACTUAL_IP%
call npx expo start --go --host lan --port 8081 -c
pause
