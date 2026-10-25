# TechnoSolutions — Sistema de Gestión de Inventario

Aplicación web para administrar productos, bodegas, movimientos de stock, usuarios y reportes de inventario. Está pensada para ejecutarse en local con PHP + MariaDB/MySQL y Python, sin depender de Internet ni de servicios en la nube.

## Funcionalidades

- Autenticación con roles: Administrador, Vendedor y Bodeguero.
- Gestión de productos, bodegas y usuarios.
- Registro de entradas, salidas y traslados con control de stock.
- Dashboard e historial de movimientos.
- Reportes de inventario en PDF y Excel.
- Interfaz con Bootstrap servido localmente (sin CDN).

## Tecnologías

- PHP 8+
- MariaDB / MySQL
- Python 3 (`reportlab`, `openpyxl`)
- PHPUnit para pruebas automatizadas
- HTML, CSS y JavaScript

## Requisitos

- Linux Mint, Ubuntu o Debian (puede adaptarse a otros entornos).
- PHP CLI, extensión `php-mysql`, MariaDB/MySQL y Python 3.
- `sudo` para instalar paquetes y crear la base de datos.

## Puesta en marcha

```bash
bash instalar_linux_mint.sh   # instala dependencias, crea la base y los usuarios demo
bash iniciar_demo.sh          # abre http://localhost:8080/  (Ctrl + C para detener)
```

El instalador crea la base de datos local `technosolutions_db` y escribe las credenciales en `config/local.php`, archivo que está excluido de Git.

## Usuarios de demostración

El instalador crea tres usuarios de ejemplo con los correos `admin@example.com`, `vendedor@example.com` y `bodeguero@example.com`. Las contraseñas se generan aleatoriamente y se muestran una sola vez al finalizar la instalación.

También puedes definir las credenciales antes de ejecutar el script con las variables `ADMIN_PASS`, `VENDEDOR_PASS`, `BODEGUERO_PASS`, `ADMIN_EMAIL`, `VENDEDOR_EMAIL` y `BODEGUERO_EMAIL`.

## Seguridad y credenciales

- `config/local.php` no se versiona: está incluido en `.gitignore`.
- No se incluyen contraseñas reales en el repositorio.
- La contraseña de la base se puede definir con `DB_PASS`; si no se indica, se genera aleatoriamente.
- Las contraseñas se almacenan en la base de datos usando `password_hash()` de PHP.

## Pruebas y utilidades

```bash
bash ejecutar_pruebas.sh                    # suite PHPUnit sobre una base de pruebas aislada
bash prueba_humo.sh http://localhost:8080   # verificación básica de disponibilidad
bash respaldo.sh                            # respaldo de base de datos y archivos
```

## Reiniciar los datos de demostración

```bash
bash instalar_linux_mint.sh --reset
```

Este comando borra la base de datos y la vuelve a crear con los datos de ejemplo.

## Estructura del proyecto

- `modulos/` — autenticación, dashboard, productos, bodegas, movimientos, usuarios y reportes.
- `database/` — esquema SQL y plantilla de datos de demostración.
- `python/` — generación de reportes en PDF y Excel.
- `tests/` — pruebas automatizadas con PHPUnit.
- `config/` — conexión a la base de datos y plantilla de configuración local.
- `assets/vendor/` — Bootstrap 5.3.0 y Bootstrap Icons 1.10.5 locales.
