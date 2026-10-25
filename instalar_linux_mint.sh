#!/usr/bin/env bash
# Instala y configura TechnoSolutions para ejecutarse en local en Linux Mint (también Ubuntu/Debian).
# Uso:  bash instalar_linux_mint.sh [--reset] [--sin-pruebas]
#   --reset         borra la base de datos y la vuelve a crear con los datos de demostración
#   --sin-pruebas   no instala PHPUnit ni php-cgi (solo necesarios para ejecutar la suite de pruebas)
# Las contraseñas de la base y de los usuarios de demostración se generan aleatoriamente.
# Se pueden definir antes de ejecutar el script con estas variables de entorno:
#   DB_PASS, ADMIN_PASS, VENDEDOR_PASS, BODEGUERO_PASS, ADMIN_EMAIL, VENDEDOR_EMAIL, BODEGUERO_EMAIL
set -euo pipefail
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
gen_pass(){ openssl rand -hex 16 2>/dev/null || od -An -N16 -tx1 /dev/urandom | tr -d ' \n'; }
DB_NAME="${DB_NAME:-technosolutions_db}"; DB_USER="${DB_USER:-ts_app}"; DB_PASS="${DB_PASS:-$(gen_pass)}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@example.com}";       ADMIN_PASS="${ADMIN_PASS:-$(gen_pass)}"
VENDEDOR_EMAIL="${VENDEDOR_EMAIL:-vendedor@example.com}"; VENDEDOR_PASS="${VENDEDOR_PASS:-$(gen_pass)}"
BODEGUERO_EMAIL="${BODEGUERO_EMAIL:-bodeguero@example.com}"; BODEGUERO_PASS="${BODEGUERO_PASS:-$(gen_pass)}"
RESET=0; SIN_PRUEBAS=0; SEEDED=0
for a in "$@"; do case "$a" in --reset) RESET=1;; --sin-pruebas) SIN_PRUEBAS=1;; -h|--help) sed -n '2,9p' "$0"; exit 0;; esac; done
SUDO=""; [ "$(id -u)" -ne 0 ] && SUDO="sudo"
paso(){ echo; echo "==> $*"; }

paso "1/6 Instalando paquetes (PHP, MariaDB, Python y librerías de reportes)"
PAQ="php-cli php-mysql mariadb-server python3 python3-reportlab python3-openpyxl curl"
[ "$SIN_PRUEBAS" -eq 0 ] && PAQ="$PAQ php-cgi phpunit"
$SUDO apt-get update -qq || true
$SUDO env DEBIAN_FRONTEND=noninteractive apt-get install -y $PAQ

paso "2/6 Iniciando el servicio MariaDB"
if ! $SUDO mysqladmin ping --silent 2>/dev/null; then
  $SUDO systemctl enable --now mariadb 2>/dev/null || $SUDO service mariadb start 2>/dev/null || true
  for _ in $(seq 1 30); do $SUDO mysqladmin ping --silent 2>/dev/null && break; sleep 1; done
fi
$SUDO mysqladmin ping --silent || { echo "ERROR: no se pudo iniciar MariaDB."; exit 1; }

paso "3/6 Creando la base de datos y el usuario de la aplicación (permisos mínimos)"
[ "$RESET" -eq 1 ] && $SUDO mysql -e "DROP DATABASE IF EXISTS \`$DB_NAME\`;"
$SUDO mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
CREATE USER IF NOT EXISTS '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS';
ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
ALTER USER '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS';
GRANT SELECT,INSERT,UPDATE,DELETE ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
GRANT SELECT,INSERT,UPDATE,DELETE ON \`$DB_NAME\`.* TO '$DB_USER'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

paso "4/6 Cargando el esquema y los datos de demostración"
$SUDO mysql "$DB_NAME" < "$DIR/database/schema.sql"
N=$($SUDO mysql -N "$DB_NAME" -e "SELECT COUNT(*) FROM usuarios")
if [ "$N" = "0" ]; then
  hash(){ php -r 'echo password_hash($argv[1], PASSWORD_DEFAULT);' "$1"; }
  H1=$(hash "$ADMIN_PASS"); H2=$(hash "$VENDEDOR_PASS"); H3=$(hash "$BODEGUERO_PASS")
  sed -e "s#__ADMIN_EMAIL__#$ADMIN_EMAIL#" -e "s#__VENDEDOR_EMAIL__#$VENDEDOR_EMAIL#" -e "s#__BODEGUERO_EMAIL__#$BODEGUERO_EMAIL#" \
      -e "s#__HASH_ADMIN__#$H1#" -e "s#__HASH_VENDEDOR__#$H2#" -e "s#__HASH_BODEGUERO__#$H3#" "$DIR/database/seed_demo.sql.tpl" \
    | $SUDO mysql --default-character-set=utf8mb4 "$DB_NAME"
  echo "Datos de demostración cargados."
  SEEDED=1
else
  echo "La base ya tiene usuarios: no se cargaron datos de demostración (use --reset para empezar de cero)."
fi

paso "5/6 Escribiendo config/local.php (credenciales fuera del código, ignorado por Git)"
cat > "$DIR/config/local.php" <<PHP
<?php
return ['host' => '127.0.0.1', 'port' => 3306, 'user' => '$DB_USER', 'pass' => '$DB_PASS', 'name' => '$DB_NAME'];
PHP
chmod 640 "$DIR/config/local.php"
php -r "require '$DIR/config/conexion.php'; echo 'Conexión a la base de datos: OK', PHP_EOL;"

paso "6/6 Listo"
if [ "$SEEDED" -eq 1 ]; then
cat <<TXT
Para iniciar el sistema:   bash iniciar_demo.sh      (abre http://localhost:8080/)
Para ejecutar las pruebas: bash ejecutar_pruebas.sh

Usuarios de demostración (guarde estas credenciales; cámbielas después de la presentación):
  Administrador : $ADMIN_EMAIL  /  $ADMIN_PASS
  Vendedor      : $VENDEDOR_EMAIL  /  $VENDEDOR_PASS
  Bodeguero     : $BODEGUERO_EMAIL  /  $BODEGUERO_PASS

La contraseña de la base quedó en config/local.php (archivo ignorado por Git).
TXT
else
cat <<TXT
Para iniciar el sistema:   bash iniciar_demo.sh      (abre http://localhost:8080/)
Para ejecutar las pruebas: bash ejecutar_pruebas.sh

La base ya tenía usuarios: no se crearon usuarios de demostración ni se cambiaron sus contraseñas.
La contraseña de la base quedó en config/local.php (archivo ignorado por Git).
TXT
fi
