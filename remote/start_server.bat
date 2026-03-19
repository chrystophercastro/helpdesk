@echo off
title HelpDesk Remote - WebSocket Server
echo ============================================
echo   HelpDesk Remote - WebSocket Relay Server
echo ============================================
echo.

set PHP_PATH=C:\xampp\php\php.exe
set SERVER_SCRIPT=%~dp0server.php
set PORT=8089

if not exist "%PHP_PATH%" (
    echo [ERRO] PHP nao encontrado em %PHP_PATH%
    echo Verifique o caminho do PHP.
    pause
    exit /b 1
)

echo Iniciando servidor na porta %PORT%...
echo Pressione Ctrl+C para parar.
echo.

"%PHP_PATH%" "%SERVER_SCRIPT%" %PORT%

pause
