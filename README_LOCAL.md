# TechnoSolutions — ejecución local en Linux Mint

Sistema de gestión de inventario (PHP + MariaDB/MySQL + Python para reportes), ajustado para correr en un equipo con Linux Mint
(también Ubuntu/Debian) sin depender de Internet ni de la base de datos en la nube.

## Puesta en marcha (2 comandos)
```bash
bash instalar_linux_mint.sh      # instala paquetes, crea la base de datos y los usuarios de demostración (pide la clave de sudo)
bash iniciar_demo.sh             # abre http://localhost:8080/   (Ctrl + C para detener)
```
Pruebas automatizadas: `bash ejecutar_pruebas.sh`   ·   Prueba de humo: `bash prueba_humo.sh http://localhost:8080`   ·   Respaldo: `bash respaldo.sh`

## Usuarios de demostración
El instalador crea tres usuarios de ejemplo con los correos `admin@example.com`, `vendedor@example.com` y `bodeguero@example.com`. Las contraseñas se generan aleatoriamente y se muestran una sola vez al finalizar la instalación. También puede definirlas antes de ejecutar el script con `ADMIN_PASS`, `VENDEDOR_PASS` y `BODEGUERO_PASS`.

Base de datos local: `technosolutions_db`

## Reiniciar los datos de demostración
`bash instalar_linux_mint.sh --reset` borra la base y la crea de nuevo con los datos de ejemplo.

## Estructura relevante
- `config/conexion.php` — conexión; lee variables de entorno (`DB_*` o `MYSQL*`) o `config/local.php` (no se sube a Git).
- `database/schema.sql` y `database/seed_demo.sql.tpl` — esquema y datos de demostración.
- `assets/vendor/` — Bootstrap 5.3.0 y Bootstrap Icons 1.10.5 locales (funciona sin Internet).
- `tests/` y `phpunit.xml` — suite PHPUnit (42 casos + exploratorias).
