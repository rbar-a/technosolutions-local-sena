# TechnoSolutions en Windows — guía rápida

> Estos archivos están escritos para Windows 10/11 con XAMPP. La lógica de la base de datos (`tools/instalar_bd.php`) y la detección de Python
> se probaron en Linux; los archivos `.bat` **no se han ejecutado en un equipo Windows**, ensáyelos antes de la presentación.

## 1. Instalar lo necesario (una sola vez)
1. **XAMPP** (https://www.apachefriends.org/): instalar en `C:\xampp` con los componentes PHP y MySQL (MariaDB). No es necesario iniciar Apache.
2. **Python 3** (https://www.python.org/downloads/): marcar *Add python.exe to PATH*. Sirve para los reportes PDF y Excel.
3. Descomprimir el proyecto, por ejemplo en `C:\technosolutions-local-mint`.

## 2. Configurar la base de datos y los usuarios de demostración
Doble clic en `instalar_windows.bat` (o ejecútelo desde el Símbolo del sistema dentro de la carpeta del proyecto).
Inicia MariaDB si está apagada, crea la base `technosolutions_db`, un usuario `ts_app` con permisos mínimos, carga los datos de demostración, escribe `config\local.php` e instala `reportlab` y `openpyxl` con pip si faltan.

## 3. Iniciar el sistema
Doble clic en `iniciar_windows.bat`: abre **http://localhost:8080/**. Para detenerlo, cierre la ventana o pulse Ctrl + C.

## Si algo falla
| Síntoma | Qué hacer |
|---|---|
| "No se encontró XAMPP" | Instale XAMPP o indique su carpeta: `set XAMPP_DIR=D:\xampp` |
| "No se pudo iniciar MariaDB" | Inicie MySQL desde el Panel de XAMPP (botón Start) y repita |
| El usuario root de MariaDB tiene contraseña | `set DB_ROOT_PASS=su_clave` antes de `instalar_windows.bat` |
| El puerto 8080 está ocupado | `set PORT=9000` antes de `iniciar_windows.bat` |
| Los reportes no se generan | Instale Python 3 con *Add to PATH* y ejecute `py -3 -m pip install reportlab openpyxl`; o defina `PYTHON_BIN` con la ruta de python.exe |
| Desea volver a los datos de demostración | `instalar_windows.bat --reset` |

## Pruebas automatizadas
La suite PHPUnit y los scripts `.sh` están pensados para Linux. En Windows se ejecutan desde WSL (Ubuntu) con `bash ejecutar_pruebas.sh`.
