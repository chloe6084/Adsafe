@echo off
chcp 65001 >nul
echo AdSafe 로컬 서버 시작
echo.

set PHP_EXE=
if exist "c:\xampp2\php\php.exe" set PHP_EXE=c:\xampp2\php\php.exe
if exist "c:\xampp\php\php.exe" set PHP_EXE=c:\xampp\php\php.exe
if exist "%~dp0..\..\php\php.exe" set PHP_EXE=%~dp0..\..\php\php.exe

if "%PHP_EXE%"=="" (
    echo [방법 1] XAMPP Apache 사용
    echo   - XAMPP Control Panel에서 Apache를 시작하세요.
    echo   - 브라우저에서 http://localhost/AdSafe/ 를 여세요.
    echo.
    echo [방법 2] PHP 내장 서버 사용
    echo   - PATH에 PHP가 없습니다. XAMPP 폴더의 php\php.exe 경로를
    echo     확인한 뒤 아래처럼 실행하세요:
    echo     "c:\xampp2\php\php.exe" -S localhost:8080 -t "%~dp0" "%~dp0router.php"
    pause
    exit /b 1
)

cd /d "%~dp0"
echo PHP 내장 서버: http://localhost:8080/
echo 종료하려면 Ctrl+C 를 누르세요.
echo.
"%PHP_EXE%" -S localhost:8080 -t . router.php
pause
