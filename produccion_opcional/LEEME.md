# Archivos para publicar en Railway (opcional)

`Procfile` y `.railway.json` se movieron aquí porque el proyecto se entrega para ejecutarse en local (Linux y Windows).
Solo se usan si, más adelante, se decide publicar el sistema: copiarlos a la raíz del proyecto y definir en Railway las variables `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS` y `DB_NAME` (o usar las `MYSQL*` que crea el servicio MySQL).

Antes de publicar: el `Procfile` arranca con el servidor integrado de PHP (`php -S`), que no está pensado para producción; conviene usar Apache o Nginx con PHP-FPM. Además deben declararse las dependencias de Python (`reportlab`, `openpyxl`).
