#!/usr/bin/env bash
# Prueba de humo (solo lectura) de un sitio desplegado. Uso: bash prueba_humo_produccion.sh https://tu-dominio
# No inicia sesión ni modifica datos: solo verifica disponibilidad, redirecciones de seguridad y tiempos.
BASE="${1:?Indica la URL base, ej: https://midominio.com}"; BASE="${BASE%/}"; OK=0; FAIL=0
chk(){ # nombre, condicion(0=ok), detalle
  if [ "$2" -eq 0 ]; then echo "[OK]    $1  $3"; OK=$((OK+1)); else echo "[FALLA] $1  $3"; FAIL=$((FAIL+1)); fi; }
code(){ curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$1"; }
loc(){ curl -s -o /dev/null -D - --max-time 20 "$1" | tr -d '\r' | awk 'tolower($1)=="location:"{print $2}' | head -1; }
echo "== Prueba de humo: $BASE  ($(date '+%d/%m/%Y %H:%M')) =="
c=$(code "$BASE/"); [ "$c" = "200" ] || [ "$c" = "302" ]; chk "Disponibilidad de la raíz" $? "HTTP $c"
c=$(code "$BASE/modulos/auth/login.php"); [ "$c" = "200" ]; chk "Página de login carga" $? "HTTP $c"
curl -s --max-time 20 "$BASE/modulos/auth/login.php" | grep -qi "<form"; chk "Login contiene formulario" $? ""
for p in modulos/dashboard.php modulos/productos/lista.php modulos/movimientos/historial.php modulos/reportes/inventario_pdf.php modulos/reportes/inventario_excel.php modulos/usuarios/lista.php; do
  c=$(code "$BASE/$p"); l=$(loc "$BASE/$p"); echo "$l" | grep -qi "login"; chk "Ruta protegida sin sesión: $p" $? "HTTP $c -> ${l:-sin Location}"
done
curl -s -D - -o /dev/null --max-time 20 "$BASE/modulos/auth/login.php" | tr -d '\r' | grep -qi "^x-powered-by"; r=$?; chk "No expone X-Powered-By (informativo)" $((1-r)) ""
t=$(curl -s -o /dev/null -w '%{time_total}' --max-time 20 "$BASE/modulos/auth/login.php"); awk -v t="$t" 'BEGIN{exit !(t<3)}'; chk "Tiempo de respuesta del login < 3 s" $? "${t}s"
case "$BASE" in https://*) chk "Sitio servido por HTTPS" 0 "";; *) chk "Sitio servido por HTTPS" 1 "usa http";; esac
echo "== Resultado: $OK OK, $FAIL FALLA =="; [ "$FAIL" -eq 0 ]
