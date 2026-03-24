@echo off
REM Laravel 6 + this codebase must run on PHP 7.4 (not PHP 8.x).
cd /d "%~dp0"
echo Starting Laravel on http://127.0.0.1:8000 with PHP 7.4...
C:\Users\Bacancy\.config\herd\bin\php74.bat artisan serve --host=127.0.0.1 --port=8000
