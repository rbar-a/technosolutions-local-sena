@echo off
setlocal EnableExtensions
chcp 65001 >nul
cd /d "%~dp0"
if not defined XAMPP_DIR set "XAMPP_DIR=C:\xampp"
if not defined PORT set "PORT=8080"
if not exist "%XAMPP_DIR%\php\php.exe" (
  echo No se encontro XAMPP en "%XAMPP_DIR%". Ejecute primero instalar_windows.bat
  exit /b 1
)
if not exist "config\local.php" (
  echo Falta config\local.php. Ejecute primero instalar_windows.bat
  exit /b 1
)
set "PHP=%XAMPP_DIR%\php\php.exe"
set "MYSQLBIN=%XAMPP_DIR%\mysql\bin"
call :asegurar_mariadb || exit /b 1
echo.
echo TechnoSolutions en:  http://localhost:%PORT%/     ^(Ctrl + C para detener^)
start "" "http://localhost:%PORT%/"
"%PHP%" -d expose_php=0 -S 127.0.0.1:%PORT% -t .
exit /b 0

:asegurar_mariadb
"%MYSQLBIN%\mysqladmin.exe" -u root ping >nul 2>&1 && exit /b 0
echo Iniciando MariaDB...
start "MariaDB TechnoSolutions" /min "%MYSQLBIN%\mysqld.exe" --defaults-file="%MYSQLBIN%\my.ini" --standalone
for /l %%i in (1,1,20) do (
  "%MYSQLBIN%\mysqladmin.exe" -u root ping >nul 2>&1 && exit /b 0
  timeout /t 1 /nobreak >nul
)
echo No se pudo iniciar MariaDB. Inicielo desde el Panel de XAMPP ^(boton Start en MySQL^).
exit /b 1
