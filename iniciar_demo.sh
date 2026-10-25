#!/usr/bin/env bash
# Inicia TechnoSolutions en local con el servidor integrado de PHP (solo para demostración y pruebas locales).
# Uso:  bash iniciar_demo.sh        (puerto por defecto 8080; cambiar con PORT=9000 bash iniciar_demo.sh)
# Detener: Ctrl + C
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"; PORT="${PORT:-8080}"
SUDO=""; [ "$(id -u)" -ne 0 ] && SUDO="sudo"
if ! $SUDO mysqladmin ping --silent 2>/dev/null; then
  echo "Iniciando MariaDB..."; $SUDO systemctl start mariadb 2>/dev/null || $SUDO service mariadb start 2>/dev/null || true
  for _ in $(seq 1 20); do $SUDO mysqladmin ping --silent 2>/dev/null && break; sleep 1; done
fi
[ -f "$DIR/config/local.php" ] || { echo "Falta config/local.php. Ejecute primero: bash instalar_linux_mint.sh"; exit 1; }
if command -v ss >/dev/null && ss -tln | grep -q ":$PORT "; then echo "El puerto $PORT está ocupado. Use otro: PORT=9000 bash iniciar_demo.sh"; exit 1; fi
echo "TechnoSolutions en:  http://localhost:$PORT/     (Ctrl + C para detener)"
command -v xdg-open >/dev/null && (sleep 1; xdg-open "http://localhost:$PORT/" >/dev/null 2>&1 &)
exec php -S 127.0.0.1:"$PORT" -t "$DIR"
