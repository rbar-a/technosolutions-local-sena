<?php
/**
 * ARCHIVO: inventario_pdf.php
 * UBICACIÓN: /technosolutions/modulos/reportes/inventario_pdf.php
 * FUNCIÓN: Consulta la base de datos, genera un JSON temporal,
 *          llama al script Python para generar el PDF y
 *          lo envía al navegador para descarga inmediata.
 * AUTOR: TechnoSolutions Colombia - SENA ADSO
 */

session_start();

if(!isset($_SESSION['usuario'])){
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';

// ── CONSULTAR DATOS DEL INVENTARIO ───────────────────────────────────────────
// Traemos todos los productos activos con su stock total
$sql = "SELECT p.codigo_barras, p.nombre, p.descripcion,
               p.precio, p.stock_minimo,
               COALESCE(SUM(sb.cantidad), 0) as stock_total
        FROM productos p
        LEFT JOIN stock_bodega sb ON p.id = sb.id_producto
        WHERE p.activo = 1
        GROUP BY p.id
        ORDER BY p.nombre ASC";

$resultado = $conexion->query($sql);
$productos = [];

// Convertimos el resultado a un array PHP
while($p = $resultado->fetch_assoc()){
    $productos[] = [
        'codigo_barras' => $p['codigo_barras'],
        'nombre'        => $p['nombre'],
        'descripcion'   => $p['descripcion'] ?? '',
        'precio'        => floatval($p['precio']),
        'stock_minimo'  => intval($p['stock_minimo']),
        'stock_total'   => intval($p['stock_total'])
    ];
}

// ── PREPARAR DATOS PARA PYTHON ────────────────────────────────────────────────
$datos = [
    'empresa'   => 'TechnoSolutions Colombia',
    'fecha'     => date('d/m/Y H:i'),
    'usuario'   => $_SESSION['nombre'],
    'productos' => $productos
];

// Definimos las rutas de los archivos temporales
// sys_get_temp_dir() devuelve la carpeta temporal del sistema (/tmp en Linux)
$id_temp   = uniqid();
$json_temp = sys_get_temp_dir() . '/inventario_' . $id_temp . '.json';
$pdf_temp  = sys_get_temp_dir() . '/inventario_' . $id_temp . '.pdf';

// Guardamos los datos como JSON en el archivo temporal
// JSON_UNESCAPED_UNICODE preserva los caracteres especiales del español (á, é, ñ, etc.)
file_put_contents($json_temp, json_encode($datos, JSON_UNESCAPED_UNICODE));

// ── LLAMAR AL SCRIPT PYTHON ───────────────────────────────────────────────────
// Construimos la ruta absoluta al script Python
// __DIR__ es la carpeta donde está este archivo PHP
$script_python = __DIR__ . '/../../python/reporte_inventario.py';

// Escapamos las rutas para evitar problemas con espacios en nombres de archivo
// Localiza Python 3 en Linux o Windows (ver config/python.php)
require_once __DIR__ . '/../../config/python.php';
$python = ts_python_comando();
if($python === null){
    http_response_code(500);
    die('No se encontró Python 3. Instálelo (y las librerías reportlab y openpyxl) o defina la variable PYTHON_BIN.');
}
$cmd = $python . " " .
       escapeshellarg($script_python) . " " .
       escapeshellarg($json_temp)     . " " .
       escapeshellarg($pdf_temp);

// shell_exec() ejecuta el comando en la terminal y devuelve la salida
$resultado_python = shell_exec($cmd . " 2>&1");
// 2>&1 redirige los errores también a la salida para poder verlos

// ── VERIFICAR SI PYTHON GENERÓ EL PDF ────────────────────────────────────────
if(!file_exists($pdf_temp)){
    // El detalle técnico va al log; al navegador solo un mensaje genérico.
    error_log('TechnoSolutions: error al generar PDF: ' . (string) $resultado_python);
    http_response_code(500);
    die('No se pudo generar el PDF. Verifica que Python y la librería reportlab estén instalados.');
}

// ── ENVIAR EL PDF AL NAVEGADOR ────────────────────────────────────────────────
// Configuramos las cabeceras HTTP para que el navegador descargue el archivo
$nombre_archivo = 'Inventario_TechnoSolutions_' . date('Y-m-d') . '.pdf';

header('Content-Type: application/pdf');
// Content-Disposition: attachment hace que el navegador descargue en lugar de mostrar
header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
header('Content-Length: ' . filesize($pdf_temp));
header('Cache-Control: no-cache');

// readfile() lee el archivo y lo envía directamente al navegador
readfile($pdf_temp);

// Limpiamos los archivos temporales para no acumular basura
unlink($json_temp);  // unlink() elimina un archivo
unlink($pdf_temp);

exit();
?>