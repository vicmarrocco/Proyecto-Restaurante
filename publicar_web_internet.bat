@echo off
chcp 65001 >nul
title Mostaza Web - Tunel Publico HTTPS (Cloudflare)
cd /d "%~dp0"

echo ============================================================================
echo   PUBLICADOR DE MOSTAZA WEB EN INTERNET (HTTPS + MERCADO PAGO + XAMPP)
echo ============================================================================
echo.

if not exist "cloudflared.exe" (
    echo [1/2] Descargando cliente oficial Cloudflare Tunnel ^(cloudflared.exe^)...
    curl.exe -L "https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe" -o "cloudflared.exe"
    if errorlevel 1 (
        echo [ERROR] No se pudo descargar cloudflared.exe. Verifica tu conexion a Internet.
        pause
        exit /b 1
    )
    echo [OK] Descarga completada.
    echo.
)

echo [2/2] Iniciando tunel HTTPS hacia tu servidor XAMPP ^(http://localhost:80^)...
echo.
echo IMPORTANTE:
echo Cuando aparezca el recuadro con tu enlace "https://xxxx.trycloudflare.com",
echo abrilo agregando al final:
echo.
echo   /PROYECTO_REST/mostaza_web/
echo.
echo Ejemplo: https://xxxx.trycloudflare.com/PROYECTO_REST/mostaza_web/
echo ^(Para el Panel Admin: https://xxxx.trycloudflare.com/PROYECTO_REST/admin/^)
echo ============================================================================
echo.

"%~dp0cloudflared.exe" tunnel --url http://localhost:80
pause
