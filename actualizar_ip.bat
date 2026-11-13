@echo off
chcp 65001 >nul
title Sincronizador Automatico de IP - Mostaza REST
cd /d "%~dp0"

python auto_update_ip.py
if errorlevel 1 (
    echo [!] No se pudo ejecutar Python. Verifique que Python este instalado y en el PATH.
)
echo.
pause
