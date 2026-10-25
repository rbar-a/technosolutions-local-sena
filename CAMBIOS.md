# Cambios respecto al proyecto original

| # | Archivo(s) | Cambio | Motivo |
|---|---|---|---|
| 1 | `config/conexion.php` | Ya no tiene host, usuario ni contraseña escritos en el código. Lee variables de entorno (`DB_*` o `MYSQL*` de Railway) o `config/local.php`. Maneja el error de conexión sin mostrar credenciales y fija la zona horaria America/Bogota. | Ejecutar en local y eliminar la contraseña expuesta en el repositorio. Sigue funcionando en Railway si se definen las variables. |
| 2 | `config/local.php.example`, `.gitignore` | Plantilla de configuración local; `config/local.php` queda ignorado por Git. | Que las credenciales no se suban al repositorio. |
| 3 | 15 archivos `.php` con interfaz, `assets/vendor/` | Bootstrap 5.3.0 y Bootstrap Icons 1.10.5 se sirven desde el propio proyecto, con rutas relativas por nivel de carpeta. | La presentación no depende de Internet ni del CDN. |
| 4 | `database/schema.sql`, `database/seed_demo.sql.tpl` | Esquema de la base de datos y datos de demostración (3 roles, 3 usuarios, 3 bodegas, 6 productos, stock y 9 movimientos). | El repositorio no incluía el script de la base de datos. El esquema se reconstruyó a partir de las consultas del código. |
| 5 | `instalar_linux_mint.sh`, `iniciar_demo.sh` | Instalación (PHP, MariaDB, Python y librerías de reportes) y arranque en `http://localhost:8080/`. | Puesta en marcha en Linux Mint en pocos comandos. |
| 6 | `tests/`, `phpunit.xml`, `ejecutar_pruebas.sh` | Suite de pruebas automatizadas integrada al proyecto, con base de datos aislada `technosolutions_test`. | Poder ejecutar pruebas en vivo. |
| 7 | `prueba_humo.sh`, `respaldo.sh` | Prueba de humo de solo lectura y respaldo de base y archivos (excluye `config/local.php`). | Verificación rápida y copia de seguridad. |
| 8 | `modulos/movimientos/procesar_movimiento.php` | Se rechaza un tipo de movimiento desconocido (lista blanca: entrada, salida, traslado). | Corrige el hallazgo H01 (página en blanco y redirección arbitraria). |
| 9 | `modulos/dashboard.php` | La fecha se muestra en español. | La fecha aparecía en inglés. |

## Pendiente (decisión del equipo, no se modificó)
- **H02:** un Vendedor puede registrar entradas; el servidor no valida el rol en ese script. Requiere definir la regla de negocio.
- Acciones por GET sin token anti-CSRF (desactivar usuario, eliminar producto).
- Los botones Nuevo Producto y Editar se muestran a roles sin permiso (el servidor los bloquea).
- El servidor integrado de PHP (`php -S`) es apto para demostración local, no para producción.
