<?php
/**
 * ARCHIVO: inventario_excel.php
 * FUNCIÓN: Igual que inventario_pdf.php pero genera Excel (.xlsx)
 * AUTOR: TechnoSolutions Colombia - SENA ADSO
 */

session_start();

if(!isset($_SESSION['usuario'])){
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';

// Consultamos los mismos datos que para el PDF
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

$datos = [
    'empresa'   => 'TechnoSolutions Colombia',
    'fecha'     => date('d/m/Y H:i'),
    'productos' => $productos
];

$json_temp  = sys_get_temp_dir() . '/inventario_' . time() . '.json';
$excel_temp = sys_get_temp_dir() . '/inventario_' . time() . '.xlsx';

file_put_contents($json_temp, json_encode($datos, JSON_UNESCAPED_UNICODE));

$script_python = __DIR__ . '/../../python/reporte_excel.py';
$cmd = "python3 " .
       escapeshellarg($script_python) . " " .
       escapeshellarg($json_temp)     . " " .
       escapeshellarg($excel_temp);

$resultado_python = shell_exec($cmd . " 2>&1");

if(!file_exists($excel_temp)){
    die("Error al generar Excel. Detalle: " . $resultado_python);
}

// Cabeceras para descarga de Excel
$nombre_archivo = 'Inventario_TechnoSolutions_' . date('Y-m-d') . '.xlsx';

// El tipo MIME correcto para archivos .xlsx
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
header('Content-Length: ' . filesize($excel_temp));
header('Cache-Control: no-cache');

readfile($excel_temp);

unlink($json_temp);
unlink($excel_temp);
exit();
?>