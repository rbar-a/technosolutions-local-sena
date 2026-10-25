<?php
use PHPUnit\Framework\TestCase;

/**
 * Base de pruebas funcionales/integración.
 * Ejecuta los scripts REALES del sistema con php-cgi (como lo haría Apache),
 * contra una base de datos de pruebas aislada. Nunca toca la BD de producción:
 * se copia el código a un sandbox y se reemplaza solo config/conexion.php.
 */
abstract class BaseTest extends TestCase
{
    protected static mysqli $db;

    public static function setUpBeforeClass(): void
    {
        $testPass = getenv('TS_TEST_DB_PASS');
        if ($testPass === false || $testPass === '') {
            throw new RuntimeException('Defina TS_TEST_DB_PASS o ejecute las pruebas con: bash ejecutar_pruebas.sh');
        }
        self::rmdirRec(SANDBOX);
        self::copiarRec(SRC_DIR, SANDBOX);
        file_put_contents(SANDBOX . '/config/conexion.php', '<?php
$conexion = new mysqli("127.0.0.1","ts_test",' . var_export($testPass, true) . ',"technosolutions_test",3306);
$conexion->set_charset("utf8");');
        @mkdir(SESS_DIR, 0777, true);
        mysqli_report(MYSQLI_REPORT_OFF);
        self::$db = new mysqli('127.0.0.1', 'ts_test', $testPass, 'technosolutions_test', 3306);
    }

    private static function rmdirRec(string $d): void
    {
        if (!is_dir($d)) { return; }
        foreach (scandir($d) as $f) {
            if ($f === '.' || $f === '..') { continue; }
            is_dir("$d/$f") ? self::rmdirRec("$d/$f") : unlink("$d/$f");
        }
        rmdir($d);
    }

    private static function copiarRec(string $o, string $d): void
    {
        mkdir($d, 0777, true);
        foreach (scandir($o) as $f) {
            if (in_array($f, ['.', '..', 'venv', '.git', 'local.php', '.phpunit.result.cache'], true)) { continue; }
            is_dir("$o/$f") ? self::copiarRec("$o/$f", "$d/$f") : copy("$o/$f", "$d/$f");
        }
    }

    protected function setUp(): void
    {
        // Estado inicial conocido antes de CADA caso (pruebas independientes).
        self::$db->multi_query(file_get_contents(__DIR__ . '/schema_test.sql'));
        while (self::$db->more_results() && self::$db->next_result()) {}
        $hash = password_hash('Clave123', PASSWORD_DEFAULT);
        self::$db->query("INSERT INTO roles (id,nombre) VALUES (1,'Administrador'),(2,'Vendedor'),(3,'Bodeguero')");
        self::$db->query("INSERT INTO usuarios (id,nombre,email,password,id_rol,activo) VALUES
            (1,'Admin','admin@t.co','$hash',1,1),(2,'Vende','vende@t.co','$hash',2,1),
            (3,'Bodega','bodega@t.co','$hash',3,1),(4,'Inactivo','off@t.co','$hash',2,0)");
        self::$db->query("INSERT INTO bodegas (id,nombre) VALUES (1,'Principal'),(2,'Norte')");
        self::$db->query("INSERT INTO productos (id,codigo_barras,nombre,precio,stock_minimo) VALUES (1,'7700001','Mouse',50000,5)");
    }

    /** Ejecuta un script del sistema y devuelve [status, location, body]. */
    protected function request(string $script, string $method = 'POST', array $post = [], ?array $session = null): array
    {
        [$script, $query] = array_pad(explode('?', $script, 2), 2, '');
        $body = http_build_query($post);
        $env = [
            'REDIRECT_STATUS' => '1', 'REQUEST_METHOD' => $method,
            'SCRIPT_FILENAME' => SANDBOX . '/' . $script,
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'CONTENT_LENGTH' => (string)strlen($body), 'QUERY_STRING' => $query,
        ];
        if ($session !== null) {
            $sid = bin2hex(random_bytes(8));
            $raw = '';
            foreach ($session as $k => $v) { $raw .= $k . '|' . serialize($v); }
            file_put_contents(SESS_DIR . "/sess_$sid", $raw);
            $env['HTTP_COOKIE'] = "PHPSESSID=$sid";
        }
        $cmd = (getenv('TS_PHPCGI') ?: 'php-cgi') . ' -d session.save_path=' . SESS_DIR . ' -d display_errors=0';
        $proc = proc_open($cmd, [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']], $pipes, SANDBOX . '/' . dirname($script), $env);
        fwrite($pipes[0], $body); fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]); fclose($pipes[1]); fclose($pipes[2]); proc_close($proc);
        [$head, $content] = array_pad(preg_split("/\r?\n\r?\n/", $out, 2), 2, '');
        $location = null;
        foreach (preg_split("/\r?\n/", $head) as $line) {
            if (stripos($line, 'Location:') === 0) { $location = trim(substr($line, 9)); }
        }
        return ['location' => $location, 'body' => $content];
    }

    protected function sesion(int $rol): array
    {
        $nombres = [1 => 'Administrador', 2 => 'Vendedor', 3 => 'Bodeguero'];
        return ['usuario' => $rol, 'nombre' => 'T', 'email' => 't@t.co', 'rol' => $rol, 'rol_nombre' => $nombres[$rol]];
    }

    protected function stock(int $prod, int $bod): ?int
    {
        $r = self::$db->query("SELECT cantidad FROM stock_bodega WHERE id_producto=$prod AND id_bodega=$bod")->fetch_assoc();
        return $r ? (int)$r['cantidad'] : null;
    }

    protected function contar(string $tabla): int
    {
        return (int)self::$db->query("SELECT COUNT(*) c FROM $tabla")->fetch_assoc()['c'];
    }

    protected function setStock(int $prod, int $bod, int $cant): void
    {
        self::$db->query("INSERT INTO stock_bodega VALUES ($prod,$bod,$cant) ON DUPLICATE KEY UPDATE cantidad=$cant");
    }
}
