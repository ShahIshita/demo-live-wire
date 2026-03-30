@echo off
REM Laravel 6 + this codebase must run on PHP 7.4 (not PHP 8.x).
REM For https://learning.test use Laravel Herd (see open-learning-test.bat). This script is the fallback.
cd /d "%~dp0"
echo.
echo Fallback server: http://127.0.0.1:8000  (with hosts: learning.test -^> 127.0.0.1 use http://learning.test:8000)
echo Preferred: start Herd and open https://learning.test
echo.
C:\Users\Bacancy\.config\herd\bin\php74.bat artisan serve --host=127.0.0.1 --port=8000
