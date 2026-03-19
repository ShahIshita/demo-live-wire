@echo off
REM Run Composer with PHP 7.4 (for Laravel 6 compatibility)
cd /d "%~dp0"
"C:\Users\Bacancy\.config\herd\bin\php74\php.exe" "C:\ProgramData\ComposerSetup\bin\composer.phar" %*
