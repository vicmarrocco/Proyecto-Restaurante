@echo off
title Mostaza POS - Terminal de Cajero 2 (Express)
color 0E
echo ============================================================
echo   MOSTAZA POS - TERMINAL DE PUNTO DE VENTA #2 (EXPRESS)
echo ============================================================
echo.
echo Iniciando terminal de Caja 2 (Salon / Express)...
echo.
cd /d "%~dp0"
python main.py
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo Ocurrio un error al ejecutar la terminal de Caja 2.
    pause
)
