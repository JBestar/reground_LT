@echo off
chcp 65001 >nul
cd /d "%~dp0"

set "PHP_EXE=D:\xampp\php\php.exe"
if not exist "%PHP_EXE%" set "PHP_EXE=php"

echo reground_LT Startup.php 실행 ^(중지: Ctrl+C^)
echo 폴더: %CD%
echo.

"%PHP_EXE%" -f "%~dp0Startup.php"

echo.
echo 종료 코드: %ERRORLEVEL%
pause
