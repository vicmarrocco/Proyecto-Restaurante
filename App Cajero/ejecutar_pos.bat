@echo off
title Mostaza POS - Terminal de Cajero
color 0C
echo ============================================================
echo   MOSTAZA POS - TERMINAL DE PUNTO DE VENTA
echo ============================================================
echo.
echo Iniciando aplicacion de cajero...
echo.
cd /d "%~dp0"
python main.py
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo Ocurrio un error al ejecutar la aplicacion.
    pause
)
