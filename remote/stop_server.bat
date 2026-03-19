@echo off
echo Parando HelpDesk Remote Server...
taskkill /F /FI "WINDOWTITLE eq HelpDesk Remote*" >nul 2>&1
for /f "tokens=2" %%i in ('tasklist /FI "IMAGENAME eq php.exe" /FO LIST ^| findstr PID') do (
    wmic process where "ProcessId=%%i" get CommandLine 2>nul | findstr /i "server.php" >nul && taskkill /F /PID %%i >nul 2>&1
)
echo Servidor parado.
timeout /t 2
