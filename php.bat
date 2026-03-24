@echo off
REM Project-local PHP 7.4 — use: php.bat artisan serve
REM Plain `php` still uses system PATH; use this file or run-php74.bat.
cd /d "%~dp0"
C:\Users\Bacancy\.config\herd\bin\php74.bat %*
