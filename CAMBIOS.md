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
| 10 | `config/python.php`, `modulos/reportes/inventario_pdf.php`, `inventario_excel.php` | Python se localiza según el sistema (`python3` en Linux; `py -3` o `python` en Windows) en lugar de escribir `python3` fijo. Los archivos temporales de los reportes usan nombre único. | Que los reportes funcionen en Windows y que dos descargas simultáneas no se pisen. |
| 11 | `tools/instalar_bd.php`, `instalar_windows.bat`, `iniciar_windows.bat`, `LEEME_WINDOWS.md`, `.gitattributes` | Instalador de base de datos independiente del sistema operativo y lanzadores para Windows (XAMPP). `.gitattributes` fija los saltos de línea (LF en `.sh`, CRLF en `.bat`). | Ejecutar el proyecto en local también en Windows. |
| 12 | `modulos/productos/procesar.php` | Al editar un producto ya no se generan avisos de PHP por campos de stock inexistentes. | Depuración: avisos "Undefined array key" en cada edición. |
| 13 | `modulos/productos/lista.php` | Los botones Nuevo Producto y Editar solo se muestran al Administrador; los demás roles ven "Solo consulta". | Los botones aparecían a roles sin permiso y el servidor los rechazaba. |
| 14 | `database/schema.sql`, `database/seed_demo.sql.tpl`, instaladores | Se agregó `roles.descripcion` (la usa `usuarios/crear.php`); los instaladores la agregan a bases antiguas. | Depuración: aviso "Undefined array key descripcion" en el formulario de usuarios. |
| 15 | `produccion_opcional/` | `Procfile` y `.railway.json` salen de la raíz. | El proyecto se entrega para ejecución local. |
| 16 | `iniciar_demo.sh`, `iniciar_windows.bat` | Se inicia PHP con `expose_php=0` (no revela la tecnología en las cabeceras). | Endurecimiento básico. |
| 17 | `config/sesion.php`, `config/conexion.php`, `modulos/auth/*` | Cookies de sesión `HttpOnly` y `SameSite=Lax`, `session_regenerate_id(true)` al iniciar sesión, expiración por 30 minutos de inactividad y límite de 5 intentos fallidos (bloqueo de 15 minutos). | Endurecer la autenticación y reducir fijación de sesión y fuerza bruta. |
| 18 | `config/csrf.php` y formularios/endpoints | Token anti-CSRF en todas las acciones que cambian estado. Desactivar usuario y eliminar producto pasan de `GET` a `POST` con token. | Evitar peticiones forzadas desde otros sitios. |
| 19 | `modulos/movimientos/procesar_movimiento.php` | Validación de rol por tipo de movimiento (entrada/traslado: Administrador o Bodeguero; salida: todos) y actualización de stock atómica con `WHERE cantidad >= ?`. | Corrige H02 y evita stock negativo por operaciones simultáneas. |
| 20 | `modulos/movimientos/procesar_movimiento.php`, `modulos/productos/procesar.php`, reportes PDF/Excel | Los detalles técnicos de los errores se escriben en el log; al navegador se muestra un mensaje genérico. | No exponer rutas, consultas ni trazas internas. |

## Pendiente (decisión del equipo, no se modificó)
- El servidor integrado de PHP (`php -S`) es apto para demostración local, no para producción.
- Los archivos `.bat` de Windows no se ejecutaron en un equipo Windows (la lógica de base de datos y la detección de Python sí se probaron en Linux).
- El límite de intentos de login es por sesión; para producción conviene complementarlo con límite por IP/usuario a nivel de servidor.
