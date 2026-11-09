<?php
/**
 * ARCHIVO: config/python.php
 * FUNCIÓN: Localiza el intérprete de Python 3 que genera los reportes PDF y Excel.
 *          Funciona en Linux (python3) y en Windows (py -3 o python).
 *
 * Orden de búsqueda:
 *   1. Variable de entorno PYTHON_BIN, o la clave 'python' de config/local.php.
 *   2. Linux/macOS: python3, python.   Windows: py -3, python, python3.
 * Solo se acepta un intérprete que responda "Python 3.x" (así se ignora el acceso directo
 * de la Tienda de Microsoft que en Windows aparece como "python" cuando no está instalado).
 */
function ts_candidatos_python(string $so = PHP_OS_FAMILY): array {
    $lista = [];
    $env = getenv('PYTHON_BIN');
    if ($env !== false && $env !== '') { $lista[] = $env; }
    $local = __DIR__ . '/local.php';
    if (is_file($local)) {
        $cfg = require $local;
        if (is_array($cfg) && !empty($cfg['python'])) { $lista[] = $cfg['python']; }
    }
    if ($so === 'Windows') { array_push($lista, 'py -3', 'python', 'python3'); }
    else                   { array_push($lista, 'python3', 'python'); }
    return $lista;
}

function ts_python_comando(): ?string {
    static $resuelto = false, $cache = null;
    if ($resuelto) { return $cache; }
    $resuelto = true;
    foreach (ts_candidatos_python() as $candidato) {
        $salida = @shell_exec($candidato . ' --version 2>&1');
        if ($salida !== null && preg_match('/Python\s+3\./', $salida)) { return $cache = $candidato; }
    }
    return $cache = null;
}
?>