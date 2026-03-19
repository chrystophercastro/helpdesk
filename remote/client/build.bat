@echo off
title HelpDesk Remote - Build
echo ============================================
echo   HelpDesk Remote - Build Installer
echo ============================================
echo.

REM Verificar Python
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERRO] Python nao encontrado no PATH.
    echo Instale o Python 3.8+ em https://python.org
    pause
    exit /b 1
)

echo [1/5] Instalando dependencias...
pip install -r requirements.txt
pip install pyinstaller

REM TurboJPEG precisa do libjpeg-turbo DLL
echo Verificando libjpeg-turbo...
pip install PyTurboJPEG numpy dxcam comtypes

echo.
echo [2/5] Limpando build anterior...
if exist dist rmdir /s /q dist
if exist build rmdir /s /q build
if exist output rmdir /s /q output

echo.
echo [3/5] Compilando executavel com PyInstaller...
REM Incluir turbojpeg.dll bundled no executavel
set "TJDLL="
if exist turbojpeg.dll set "TJDLL=--add-binary turbojpeg.dll;."
if not defined TJDLL if exist "C:\libjpeg-turbo64\bin\turbojpeg.dll" (
    copy "C:\libjpeg-turbo64\bin\turbojpeg.dll" turbojpeg.dll >nul
    set "TJDLL=--add-binary turbojpeg.dll;."
)
pyinstaller --onefile --windowed --name "HelpDesk_Remote" ^
    --hidden-import=pystray._win32 ^
    --hidden-import=PIL._tkinter_finder ^
    --hidden-import=turbojpeg ^
    --hidden-import=numpy ^
    --hidden-import=dxcam ^
    --hidden-import=comtypes ^
    --hidden-import=dxcam.core ^
    %TJDLL% ^
    helpdesk_remote.py

if %errorlevel% neq 0 (
    echo.
    echo [ERRO] Falha no PyInstaller. Verifique os erros acima.
    pause
    exit /b 1
)

echo.
echo [4/5] Verificando config.json...
if not exist config.json (
    echo {> config.json
    echo   "server_url": "ws://localhost:8089",>> config.json
    echo   "quality": 40,>> config.json
    echo   "fps": 30,>> config.json
    echo   "scale": 0.5,>> config.json
    echo   "auto_start": true,>> config.json
    echo   "require_approval": false,>> config.json
    echo   "adaptive_quality": true>> config.json
    echo }>> config.json
    echo [INFO] config.json padrao criado.
    echo [INFO] Edite config.json com o IP do servidor antes de compilar o instalador.
    echo [INFO] Ou baixe o instalador pelo sistema HelpDesk que ja configura automaticamente.
)

echo.
echo [5/5] Compilando instalador com Inno Setup...

REM Procurar ISCC.exe
set "ISCC="
if exist "%ProgramFiles(x86)%\Inno Setup 6\ISCC.exe" (
    set "ISCC=%ProgramFiles(x86)%\Inno Setup 6\ISCC.exe"
) else if exist "%ProgramFiles%\Inno Setup 6\ISCC.exe" (
    set "ISCC=%ProgramFiles%\Inno Setup 6\ISCC.exe"
) else if exist "%ProgramFiles(x86)%\Inno Setup 5\ISCC.exe" (
    set "ISCC=%ProgramFiles(x86)%\Inno Setup 5\ISCC.exe"
) else if exist "%ProgramFiles%\Inno Setup 5\ISCC.exe" (
    set "ISCC=%ProgramFiles%\Inno Setup 5\ISCC.exe"
)

if not defined ISCC (
    where ISCC.exe >nul 2>&1
    if %errorlevel% equ 0 (
        for /f "tokens=*" %%i in ('where ISCC.exe') do set "ISCC=%%i"
    )
)

if defined ISCC (
    echo Inno Setup encontrado: %ISCC%
    echo.
    "%ISCC%" installer.iss
    if %errorlevel% neq 0 (
        echo.
        echo [AVISO] Falha ao compilar instalador Inno Setup.
        echo         Verifique se o installer.iss esta correto.
    ) else (
        echo.
        echo ============================================
        echo   BUILD CONCLUIDO COM SUCESSO!
        echo ============================================
        echo.
        echo   Instalador: output\HelpDesk_Remote_Setup.exe
        echo   Client EXE: dist\HelpDesk_Remote.exe
        echo.
        echo   O instalador ja inclui:
        echo     - HelpDesk_Remote.exe
        echo     - config.json com endereco do servidor
        echo     - Autostart com Windows
        echo     - Atalhos no menu iniciar
        echo     - Desinstalador
        echo.
        echo   Distribua o arquivo:
        echo     output\HelpDesk_Remote_Setup.exe
        echo.
        echo   Ou baixe pelo sistema HelpDesk que gera o
        echo   instalador com o IP configurado automaticamente.
        echo.
        pause
        exit /b 0
    )
) else (
    echo [AVISO] Inno Setup nao encontrado.
    echo.
    echo Para gerar o instalador, instale o Inno Setup 6:
    echo   https://jrsoftware.org/isdl.php
    echo.
    echo Apos instalar, execute este script novamente.
    echo.
    echo Enquanto isso, o executavel esta disponivel em:
    echo   dist\HelpDesk_Remote.exe
)

echo.
pause
