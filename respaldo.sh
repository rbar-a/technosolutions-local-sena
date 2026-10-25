#!/usr/bin/env bash
# Respaldo diario de TechnoSolutions: base de datos + archivos. Conserva los últimos 7 días.
# Uso: bash respaldo_technosolutions.sh   (credenciales en ~/.my.cnf, sección [client], permisos 600)
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"; DB="${DB_NAME:-technosolutions_db}"; DEST="$HOME/respaldos_technosolutions"; APP="$DIR"
set -o pipefail   # si mysqldump falla, el respaldo se marca como fallido
FECHA=$(date +%Y%m%d_%H%M); mkdir -p "$DEST"
mysqldump --single-transaction --routines "$DB" | gzip > "$DEST/db_${DB}_${FECHA}.sql.gz" || { echo "[FALLA] respaldo de la base"; exit 1; }
tar -czf "$DEST/archivos_${FECHA}.tar.gz" --exclude="$(basename "$APP")/config/local.php" -C "$(dirname "$APP")" "$(basename "$APP")" || { echo "[FALLA] respaldo de archivos"; exit 1; }
find "$DEST" -type f -mtime +7 -delete
echo "[OK] Respaldo creado en $DEST ($FECHA)"; ls -lh "$DEST" | tail -n 3
