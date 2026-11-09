# TechnoSolutions — Sistema de Gestión de Inventario

Aplicación web para administrar productos, bodegas, movimientos de stock, usuarios y reportes de inventario. Está pensada para ejecutarse **en local** con PHP + MariaDB/MySQL y Python, **sin depender de Internet ni de servicios en la nube**.

> Esta versión prioriza el funcionamiento local en **Linux (Mint/Ubuntu/Debian)** y **Windows 10/11**. Los archivos para publicar en un servidor están aparte, en `produccion_opcional/`.

## Funcionalidades

- Autenticación con roles: **Administrador**, **Vendedor** y **Bodeguero**.
- Gestión de productos, bodegas y usuarios.
- Registro de **entradas, salidas y traslados** con control de stock y transacciones.
- Dashboard con estadísticas, alertas de stock bajo e historial de movimientos.
- Búsqueda de productos por código de barras (compatible con pistola lectora).
- Reportes de inventario en **PDF** y **Excel** generados con Python.
- Interfaz responsive con **Bootstrap 5.3 servido localmente** (sin CDN).
- Instaladores, pruebas automatizadas, prueba de humo y respaldo incluidos.

## Tecnologías

- PHP 8+
- MariaDB / MySQL (InnoDB)
- Python 3 (`reportlab`, `openpyxl`)
- PHPUnit para pruebas automatizadas
- HTML, CSS y JavaScript (Bootstrap 5.3 e iconos locales)

## Requisitos

**Linux (Mint, Ubuntu, Debian)**
- PHP CLI, extensión `php-mysql`, MariaDB/MySQL y Python 3.
- `sudo` para instalar paquetes y crear la base de datos.

**Windows 10/11**
- [XAMPP](https://www.apachefriends.org/) (incluye PHP y MariaDB).
- [Python 3](https://www.python.org/downloads/) (marcar *Add python.exe to PATH*).
- Detalle adicional en [`LEEME_WINDOWS.md`](LEEME_WINDOWS.md).

## Puesta en marcha

### Linux

```bash
bash instalar_linux_mint.sh      # instala paquetes, crea la base y los usuarios demo (pide sudo)
bash iniciar_demo.sh             # abre http://localhost:8080/  (Ctrl + C para detener)
```

### Windows

```bat
instalar_windows.bat     :: configura la base de datos y verifica Python y sus librerías
iniciar_windows.bat      :: abre http://localhost:8080/  (Ctrl + C para detener)
```

Si XAMPP no está en `C:\xampp`: `set XAMPP_DIR=D:\xampp` antes de ejecutar los archivos.

El instalador crea la base de datos local `technosolutions_db`, el usuario `ts_app` y escribe las credenciales en `config/local.php`, archivo excluido de Git. La base solo escucha en `127.0.0.1`.

## Usuarios de demostración

Cámbielos después de la presentación.

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | `admin@technosolutions.com` | `Admin2026!` |
| Vendedor | `laura.martinez@technosolutions.com` | `Vende2026!` |
| Bodeguero | `carlos.rojas@technosolutions.com` | `Bodega2026!` |

También puedes definir las credenciales antes de instalar con las variables `ADMIN_PASS`, `VENDEDOR_PASS`, `BODEGUERO_PASS`, `ADMIN_EMAIL`, `VENDEDOR_EMAIL` y `BODEGUERO_EMAIL`.

## Seguridad y credenciales

- `config/local.php` **no se versiona**: está incluido en `.gitignore`.
- Las credenciales se leen de variables de entorno `DB_*` o de `config/local.php`; no están escritas en el código.
- El usuario de la base `ts_app` tiene permisos mínimos (`SELECT, INSERT, UPDATE, DELETE`).
- Las contraseñas se almacenan con `password_hash()` (bcrypt) y se verifican con `password_verify()`.
- Las consultas usan *prepared statements* para evitar inyección SQL.
- Las salidas se escapan con `htmlspecialchars()` para mitigar XSS.

## Pruebas y utilidades

```bash
bash ejecutar_pruebas.sh                     # suite PHPUnit (42 casos) sobre una base aislada
bash prueba_humo.sh http://localhost:8080    # verificación de solo lectura del sitio
bash respaldo.sh                             # respaldo de base de datos y archivos
```

## Reiniciar los datos de demostración

```bash
bash instalar_linux_mint.sh --reset   # Linux
instalar_windows.bat --reset          # Windows
```

Este comando borra la base de datos y la vuelve a crear con los datos de ejemplo.

## Estructura del proyecto

- `index.php` — punto de entrada; redirige según la sesión.
- `modulos/` — autenticación, dashboard, productos, bodegas, movimientos, usuarios y reportes.
- `config/` — conexión a la base de datos (`conexion.php`), detección de Python (`python.php`) y plantilla `local.php.example`.
- `database/` — esquema SQL, datos de demostración y consultas de ejemplo.
- `python/` — generación de reportes en PDF y Excel.
- `tests/` y `phpunit.xml` — pruebas automatizadas con PHPUnit.
- `assets/vendor/` — Bootstrap 5.3.0 y Bootstrap Icons 1.10.5 locales (funciona sin Internet).
- `tools/instalar_bd.php` — instalador de la base de datos multiplataforma.
- `produccion_opcional/` — `Procfile` y `.railway.json` para despliegue opcional; no se usan en local.
- `instalar_linux_mint.sh`, `iniciar_demo.sh`, `instalar_windows.bat`, `iniciar_windows.bat` — instalación y arranque.
- `prueba_humo.sh`, `respaldo.sh`, `ejecutar_pruebas.sh` — verificación y mantenimiento.

## Notas y pendientes

- **H02:** un Vendedor puede registrar entradas; falta definir y validar la regla de negocio por rol.
- Algunas acciones que cambian estado usan `GET` sin token anti-CSRF (desactivar usuario, eliminar producto).
- El servidor integrado de PHP (`php -S`) es apto para demostración local, **no para producción**.
- Los lanzadores `.bat` de Windows no se ejecutaron en un equipo Windows (la lógica de base de datos y la detección de Python sí se probaron en Linux).

El detalle de los cambios respecto al proyecto original está en [`CAMBIOS.md`](CAMBIOS.md) y la guía local extendida en [`README_LOCAL.md`](README_LOCAL.md).
