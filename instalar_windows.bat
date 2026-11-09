@echo off
setlocal EnableExtensions
chcp 65001 >nul
cd /d "%~dp0"
echo ============================================================
echo  TechnoSolutions - instalacion local en Windows
echo ============================================================
if not defined XAMPP_DIR set "XAMPP_DIR=C:\xampp"
if not exist "%XAMPP_DIR%\php\php.exe" (
  echo No se encontro XAMPP en "%XAMPP_DIR%".
  echo Instale XAMPP desde https://www.apachefriends.org/ ^(incluye PHP y MariaDB^)
  echo o indique su carpeta:  set XAMPP_DIR=D:\xampp ^& instalar_windows.bat
  exit /b 1
)
set "PHP=%XAMPP_DIR%\php\php.exe"
set "MYSQLBIN=%XAMPP_DIR%\mysql\bin"
call :asegurar_mariadb || exit /b 1
echo.
echo [1/2] Configurando la base de datos...
"%PHP%" "%~dp0tools\instalar_bd.php" %*
if errorlevel 1 (
  echo ERROR: no se pudo configurar la base de datos.
  exit /b 1
)
echo.
echo [2/2] Verificando Python y las librerias de reportes...
set "PY="
py -3 --version >nul 2>&1 && set "PY=py -3"
if not defined PY (
  python --version 2>&1 | findstr /b /c:"Python 3" >nul && set "PY=python"
)
if not defined PY (
  echo AVISO: no se encontro Python 3. Los reportes PDF y Excel no funcionaran hasta instalarlo.
  echo Descarguelo de https://www.python.org/downloads/ marcando "Add python.exe to PATH" y vuelva a ejecutar este archivo.
  goto :fin
)
%PY% -c "import reportlab, openpyxl" >nul 2>&1 || (
  echo Instalando reportlab y openpyxl...
  %PY% -m pip install reportlab openpyxl
)
:fin
echo.
echo Listo. Para iniciar el sistema ejecute:  iniciar_windows.bat
exit /b 0

:asegurar_mariadb
"%MYSQLBIN%\mysqladmin.exe" -u root ping >nul 2>&1 && exit /b 0
echo Iniciando MariaDB...
start "MariaDB TechnoSolutions" /min "%MYSQLBIN%\mysqld.exe" --defaults-file="%MYSQLBIN%\my.ini" --standalone
for /l %%i in (1,1,20) do (
  "%MYSQLBIN%\mysqladmin.exe" -u root ping >nul 2>&1 && exit /b 0
  timeout /t 1 /nobreak >nul
)
echo No se pudo iniciar MariaDB. Inicielo desde el Panel de XAMPP ^(boton Start en MySQL^) y vuelva a intentar.
exit /b 1
