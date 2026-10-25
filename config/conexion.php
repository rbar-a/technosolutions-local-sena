<?php
/**
 * ARCHIVO: config/conexion.php
 * FUNCIÓN: Crea la conexión $conexion a MySQL/MariaDB.
 *
 * Las credenciales YA NO están escritas en el código. Se leen, en este orden de prioridad:
 *   1. Variables de entorno DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME
 *      (o las variables MYSQLHOST, MYSQLPORT, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE de Railway).
 *   2. El archivo config/local.php (ignorado por Git; lo crea instalar_linux_mint.sh).
 *   3. Valores por defecto para desarrollo local (sin contraseña).
 */

// Zona horaria de Colombia para fechas y horas mostradas en pantalla.
date_default_timezone_set('America/Bogota');

$cfg = ['host' => '127.0.0.1', 'port' => 3306, 'user' => 'ts_app', 'pass' => '', 'name' => 'technosolutions_db'];

$archivo_local = __DIR__ . '/local.php';
if (is_file($archivo_local)) {
    $cfg = array_merge($cfg, require $archivo_local);
}

// Lee la primera variable de entorno definida de la lista; si no hay ninguna, devuelve el valor actual.
$entorno = function (array $nombres, $actual) {
    foreach ($nombres as $n) {
        $v = getenv($n);
        if ($v !== false && $v !== '') { return $v; }
    }
    return $actual;
};
$cfg['host'] = $entorno(['DB_HOST', 'MYSQLHOST'], $cfg['host']);
$cfg['port'] = (int) $entorno(['DB_PORT', 'MYSQLPORT'], $cfg['port']);
$cfg['user'] = $entorno(['DB_USER', 'MYSQLUSER'], $cfg['user']);
$cfg['pass'] = $entorno(['DB_PASS', 'MYSQLPASSWORD'], $cfg['pass']);
$cfg['name'] = $entorno(['DB_NAME', 'MYSQLDATABASE'], $cfg['name']);

mysqli_report(MYSQLI_REPORT_OFF);   // los errores se manejan abajo, sin exponer credenciales
$conexion = @new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name'], $cfg['port']);

if ($conexion->connect_error) {
    error_log('TechnoSolutions: error de conexión a la base de datos: ' . $conexion->connect_error);
    http_response_code(500);
    die('No se pudo conectar a la base de datos. Verifica que MariaDB/MySQL esté activo y revisa config/local.php o las variables DB_*.');
}

$conexion->set_charset('utf8mb4');
?>
