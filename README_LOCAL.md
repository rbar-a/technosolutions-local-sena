# TechnoSolutions — ejecución local (Linux y Windows)

Sistema de gestión de inventario (PHP + MariaDB/MySQL + Python para reportes). Corre en el propio equipo, sin Internet y sin base de datos en la nube.
Prioridad del proyecto: **funcionar en local en Linux y Windows**. Los archivos para publicar en un servidor están aparte, en `produccion_opcional/`.

## Linux (Linux Mint, Ubuntu, Debian)
```bash
bash instalar_linux_mint.sh      # instala paquetes, crea la base de datos y los usuarios de demostración (pide sudo)
bash iniciar_demo.sh             # abre http://localhost:8080/   (Ctrl + C para detener)
```
Pruebas: `bash ejecutar_pruebas.sh` · Prueba de humo: `bash prueba_humo.sh http://localhost:8080` · Respaldo: `bash respaldo.sh`

## Windows 10/11
Requisitos: [XAMPP](https://www.apachefriends.org/) (incluye PHP y MariaDB) y [Python 3](https://www.python.org/downloads/) (para los reportes; marcar "Add python.exe to PATH").
```bat
instalar_windows.bat     :: configura la base de datos y verifica Python y sus librerías
iniciar_windows.bat      :: abre http://localhost:8080/   (Ctrl + C para detener)
```
Si XAMPP no está en `C:\xampp`: `set XAMPP_DIR=D:\xampp` antes de ejecutar los archivos. Más detalle en `LEEME_WINDOWS.md`.

## Usuarios de demostración (cámbielos después de la presentación)
| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@technosolutions.com | Admin2026! |
| Vendedor | laura.martinez@technosolutions.com | Vende2026! |
| Bodeguero | carlos.rojas@technosolutions.com | Bodega2026! |

Base de datos local: `technosolutions_db` · usuario `ts_app` · contraseña `TechnoLocal_2026` (solo escucha en 127.0.0.1).
Reiniciar los datos de demostración: `bash instalar_linux_mint.sh --reset` (Linux) o `instalar_windows.bat --reset` (Windows).

## Estructura relevante
- `config/conexion.php` — conexión; lee `config/local.php` (no se sube a Git) o variables de entorno `DB_*`.
- `config/python.php` — localiza Python 3 en Linux (`python3`) y Windows (`py -3` o `python`); se puede forzar con `PYTHON_BIN`.
- `tools/instalar_bd.php` — instalador de la base de datos para cualquier sistema operativo.
- `database/` — esquema, datos de demostración y consultas de demostración.
- `assets/vendor/` — Bootstrap 5.3.0 y Bootstrap Icons 1.10.5 locales (funciona sin Internet).
- `tests/` y `phpunit.xml` — suite PHPUnit (42 casos + exploratorias; se ejecuta en Linux).
- `produccion_opcional/` — Procfile y `.railway.json` para Railway; no se usan en local.
