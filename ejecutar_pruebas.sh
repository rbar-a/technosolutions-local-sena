#!/usr/bin/env bash
# Ejecuta la suite de pruebas automatizadas (PHPUnit) sobre una base de datos aislada (technosolutions_test).
# No toca la base de datos de la aplicación. Requiere: bash instalar_linux_mint.sh (sin --sin-pruebas).
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"; SUDO=""; [ "$(id -u)" -ne 0 ] && SUDO="sudo"
command -v phpunit >/dev/null || { echo "Falta phpunit. Ejecute: bash instalar_linux_mint.sh"; exit 1; }
TS_TEST_DB_PASS="${TS_TEST_DB_PASS:-$(php -r 'echo bin2hex(random_bytes(16));')}"; export TS_TEST_DB_PASS
$SUDO mysql <<SQL
CREATE DATABASE IF NOT EXISTS technosolutions_test CHARACTER SET utf8mb4;
CREATE USER IF NOT EXISTS 'ts_test'@'127.0.0.1' IDENTIFIED BY '$TS_TEST_DB_PASS';
CREATE USER IF NOT EXISTS 'ts_test'@'localhost' IDENTIFIED BY '$TS_TEST_DB_PASS';
ALTER USER 'ts_test'@'127.0.0.1' IDENTIFIED BY '$TS_TEST_DB_PASS';
ALTER USER 'ts_test'@'localhost' IDENTIFIED BY '$TS_TEST_DB_PASS';
GRANT ALL ON technosolutions_test.* TO 'ts_test'@'127.0.0.1';
GRANT ALL ON technosolutions_test.* TO 'ts_test'@'localhost';
FLUSH PRIVILEGES;
SQL
cd "$DIR"; export TS_SRC="$DIR"
echo "=== SUITE PRINCIPAL (todas deben aprobar) ==="; phpunit --exclude-group hallazgos --testdox; R1=$?
echo; echo "=== PRUEBAS EXPLORATORIAS (hallazgos pendientes de decisión) ==="; phpunit --group hallazgos --testdox; R2=$?
echo; echo "Suite principal: $([ $R1 -eq 0 ] && echo APROBADA || echo CON FALLOS) | Exploratorias: $([ $R2 -eq 0 ] && echo 'sin hallazgos' || echo 'con hallazgos documentados')"
exit $R1
