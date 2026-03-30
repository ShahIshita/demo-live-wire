@echo off
REM Laravel Herd serves this project at https://learning.test (HTTP redirects to HTTPS).
REM Herd.json lastSite should point to this folder; PHP 7.4 is set for learning.test in Herd.
cd /d "%~dp0"

if exist "%ProgramFiles%\Herd\Herd.exe" (
    start "" "%ProgramFiles%\Herd\Herd.exe"
    echo Started Laravel Herd. Wait a few seconds for nginx, then open https://learning.test
    timeout /t 5 /nobreak >nul
    start "" "https://learning.test"
) else (
    echo Herd not found at "%ProgramFiles%\Herd\Herd.exe"
    echo Install Laravel Herd for Windows, or run serve-php74.bat and open http://127.0.0.1:8000
    pause
)
