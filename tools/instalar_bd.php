<?php
/**
 * ARCHIVO: tools/instalar_bd.php
 * FUNCIÓN: Instala la base de datos de TechnoSolutions en cualquier sistema (Linux o Windows).
 *          Crea la base y el usuario de la aplicación, carga el esquema y los datos de demostración,
 *          y escribe config/local.php.
 *
 * Uso:   php tools/instalar_bd.php [--reset]
 *   --reset   borra la base de datos y la crea de nuevo con los datos de demostración
 *
 * Variables de entorno opcionales (valores por defecto entre paréntesis):
 *   Conexión de administración:  DB_ROOT_USER (root), DB_ROOT_PASS (vacía), DB_HOST (127.0.0.1), DB_PORT (3306)
 *   Base y usuario de la app:    DB_NAME (technosolutions_db), DB_USER (ts_app), DB_PASS (TechnoLocal_2026)
 *   Usuarios de demostración:    ADMIN_EMAIL/ADMIN_PASS, VENDEDOR_EMAIL/VENDEDOR_PASS, BODEGUERO_EMAIL/BODEGUERO_PASS
 *   LOCAL_CONFIG_PATH (config/local.php)
 * Las contraseñas por defecto son solo para demostración local: cámbielas después de la presentación.
 */
if (PHP_SAPI !== 'cli') { exit("Este script se ejecuta solo desde la línea de comandos.\n"); }
mysqli_report(MYSQLI_REPORT_OFF);

$dir = dirname(__DIR__);
$env = function (string $k, string $def): string { $v = getenv($k); return ($v !== false && $v !== '') ? $v : $def; };
$reset = in_array('--reset', $argv, true);

$cfg = [
    'host' => $env('DB_HOST', '127.0.0.1'), 'port' => (int) $env('DB_PORT', '3306'),
    'name' => $env('DB_NAME', 'technosolutions_db'), 'user' => $env('DB_USER', 'ts_app'), 'pass' => $env('DB_PASS', 'TechnoLocal_2026'),
];
$usuarios = [
    ['__ADMIN_EMAIL__', $env('ADMIN_EMAIL', 'admin@technosolutions.com'), $env('ADMIN_PASS', 'Admin2026!'), '__HASH_ADMIN__', 'Administrador'],
    ['__VENDEDOR_EMAIL__', $env('VENDEDOR_EMAIL', 'laura.martinez@technosolutions.com'), $env('VENDEDOR_PASS', 'Vende2026!'), '__HASH_VENDEDOR__', 'Vendedor'],
    ['__BODEGUERO_EMAIL__', $env('BODEGUERO_EMAIL', 'carlos.rojas@technosolutions.com'), $env('BODEGUERO_PASS', 'Bodega2026!'), '__HASH_BODEGUERO__', 'Bodeguero'],
];

function fallar(string $m): void { fwrite(STDERR, "ERROR: $m\n"); exit(1); }
function paso(string $m): void { echo "\n==> $m\n"; }
function ejecutar_sql(mysqli $db, string $sql): void {
    if (!$db->multi_query($sql)) { fallar('SQL: ' . $db->error); }
    do { if ($r = $db->store_result()) { $r->free(); } } while ($db->more_results() && $db->next_result());
    if ($db->errno) { fallar('SQL: ' . $db->error); }
}

paso('1/5 Conectando como administrador de la base de datos');
$root_user = $env('DB_ROOT_USER', 'root'); $root_pass = $env('DB_ROOT_PASS', '');
$db = null;
foreach ([$cfg['host'], 'localhost'] as $h) {       // por TCP y, si no, por socket (Linux con autenticación del sistema)
    $c = @new mysqli($h, $root_user, $root_pass, '', $cfg['port']);
    if (!$c->connect_error) { $db = $c; break; }
    $ultimo_error = $c->connect_error;
}
if (!$db) {
    fallar("no se pudo conectar a MariaDB/MySQL ($ultimo_error). Verifique que el servicio esté iniciado; "
         . "si el usuario root tiene contraseña, defina DB_ROOT_PASS. En Linux puede ejecutar este script con sudo.");
}
$db->set_charset('utf8mb4');
echo "Conectado a " . $db->server_info . "\n";

paso('2/5 Creando la base de datos y el usuario de la aplicación (permisos mínimos)');
$n = '`' . str_replace('`', '', $cfg['name']) . '`';
if ($reset) { ejecutar_sql($db, "DROP DATABASE IF EXISTS $n"); }
ejecutar_sql($db, "CREATE DATABASE IF NOT EXISTS $n CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$u = $db->real_escape_string($cfg['user']); $p = $db->real_escape_string($cfg['pass']);
foreach (['localhost', '127.0.0.1'] as $h) {
    if (!$db->query("CREATE USER IF NOT EXISTS '$u'@'$h' IDENTIFIED BY '$p'") ) { $db->query("GRANT USAGE ON *.* TO '$u'@'$h' IDENTIFIED BY '$p'"); }
    $db->query("ALTER USER '$u'@'$h' IDENTIFIED BY '$p'");
    if (!$db->query("GRANT SELECT, INSERT, UPDATE, DELETE ON $n.* TO '$u'@'$h'")) { fallar('no se pudo otorgar permisos: ' . $db->error); }
}
$db->query('FLUSH PRIVILEGES');
$db->select_db($cfg['name']);

paso('3/5 Cargando el esquema');
ejecutar_sql($db, file_get_contents("$dir/database/schema.sql"));
$existe = $db->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roles' AND COLUMN_NAME = 'descripcion'")->fetch_assoc()['c'];
if ((int) $existe === 0) {   // bases creadas con una versión anterior del esquema
    ejecutar_sql($db, "ALTER TABLE roles ADD COLUMN descripcion VARCHAR(150) NULL");
    ejecutar_sql($db, "UPDATE roles SET descripcion = CASE id WHEN 1 THEN 'Acceso total: productos, bodegas, usuarios y reportes' WHEN 2 THEN 'Registra ventas y consulta el inventario' WHEN 3 THEN 'Gestiona entradas, traslados y existencias' END WHERE descripcion IS NULL");
    echo "Se agregó la columna roles.descripcion.\n";
}

paso('4/5 Cargando los datos de demostración');
$total = (int) $db->query('SELECT COUNT(*) c FROM usuarios')->fetch_assoc()['c'];
if ($total === 0) {
    $sql = file_get_contents("$dir/database/seed_demo.sql.tpl");
    foreach ($usuarios as [$marca_correo, $correo, $clave, $marca_hash]) {
        $sql = str_replace([$marca_correo, $marca_hash], [$db->real_escape_string($correo), password_hash($clave, PASSWORD_DEFAULT)], $sql);
    }
    ejecutar_sql($db, $sql);
    echo "Datos de demostración cargados.\n";
} else {
    echo "La base ya tiene usuarios: no se cargaron datos de demostración (use --reset para empezar de cero).\n";
}

paso('5/5 Escribiendo config/local.php (credenciales fuera del código, ignorado por Git)');
$ruta = $env('LOCAL_CONFIG_PATH', "$dir/config/local.php");
file_put_contents($ruta, "<?php\nreturn " . var_export(['host' => $cfg['host'], 'port' => $cfg['port'], 'user' => $cfg['user'], 'pass' => $cfg['pass'], 'name' => $cfg['name']], true) . ";\n");
@chmod($ruta, 0640);
$prueba = @new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name'], $cfg['port']);
if ($prueba->connect_error) { fallar('el usuario de la aplicación no puede conectarse: ' . $prueba->connect_error); }
echo "Conexión con el usuario de la aplicación: OK\n";

echo "\nBase de datos lista. Usuarios de demostración (cámbielos después de la presentación):\n";
foreach ($usuarios as [, $correo, $clave, , $rol]) { printf("  %-14s %s  /  %s\n", $rol, $correo, $clave); }
