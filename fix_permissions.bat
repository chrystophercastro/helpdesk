@echo off
:: =====================================================
:: HelpDesk - Corrigir Permissoes do XAMPP
:: Execute como Administrador (clique direito > Executar como administrador)
:: =====================================================

echo.
echo ============================================
echo   HelpDesk - Corrigir Permissoes do XAMPP
echo ============================================
echo.

:: Verificar se esta rodando como admin
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERRO] Este script precisa ser executado como Administrador!
    echo.
    echo Clique com o botao direito neste arquivo e selecione:
    echo "Executar como administrador"
    echo.
    pause
    exit /b 1
)

echo [INFO] Executando como Administrador - OK
echo.

:: Conceder permissao total para Everyone (SID universal S-1-1-0)
echo [1/4] Aplicando permissoes em C:\xampp\php ...
icacls "C:\xampp\php" /grant *S-1-1-0:(OI)(CI)F /T /Q
if %errorlevel% equ 0 (echo       [OK] Permissoes aplicadas) else (echo       [FALHA] Erro ao aplicar permissoes)

echo.
echo [2/4] Aplicando permissoes em C:\xampp\php\ext ...
icacls "C:\xampp\php\ext" /grant *S-1-1-0:(OI)(CI)F /T /Q
if %errorlevel% equ 0 (echo       [OK] Permissoes aplicadas) else (echo       [FALHA] Erro ao aplicar permissoes)

echo.
echo [3/4] Aplicando permissoes em C:\xampp\apache ...
icacls "C:\xampp\apache" /grant *S-1-1-0:(OI)(CI)F /T /Q
if %errorlevel% equ 0 (echo       [OK] Permissoes aplicadas) else (echo       [FALHA] Erro ao aplicar permissoes)

echo.
echo [4/4] Aplicando permissoes em C:\xampp\htdocs ...
icacls "C:\xampp\htdocs" /grant *S-1-1-0:(OI)(CI)F /T /Q
if %errorlevel% equ 0 (echo       [OK] Permissoes aplicadas) else (echo       [FALHA] Erro ao aplicar permissoes)

echo.
echo ============================================
echo   Permissoes aplicadas com sucesso!
echo   Agora voce pode usar a instalacao
echo   automatica do SSH2 no HelpDesk.
echo ============================================
echo.
pause
