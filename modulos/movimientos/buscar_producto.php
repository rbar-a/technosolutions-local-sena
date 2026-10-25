<?php
/**
 * ARCHIVO: buscar_producto.php (versión actualizada)
 * Ahora también devuelve stock por bodega cuando se pide con &stock_por_bodega=1
 */

session_start();

if(!isset($_SESSION['usuario'])){
    echo json_encode(['encontrado' => false]);
    exit();
}

require_once '../../config/conexion.php';
header('Content-Type: application/json');

$codigo          = isset($_GET['codigo'])          ? trim($_GET['codigo'])  : '';
$stock_por_bodega = isset($_GET['stock_por_bodega']) ? true : false;

if(empty($codigo)){
    echo json_encode(['encontrado' => false]);
    exit();
}

// Consulta principal del producto con stock total
$sql = "SELECT p.id, p.codigo_barras, p.nombre, p.precio, p.stock_minimo,
               COALESCE(SUM(sb.cantidad), 0) as stock_total
        FROM productos p
        LEFT JOIN stock_bodega sb ON p.id = sb.id_producto
        WHERE p.codigo_barras = ? AND p.activo = 1
        GROUP BY p.id";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $codigo);
$stmt->execute();
$resultado = $stmt->get_result();

if($resultado->num_rows > 0){
    $producto = $resultado->fetch_assoc();
    $respuesta = [
        'encontrado'    => true,
        'id'            => $producto['id'],
        'codigo_barras' => $producto['codigo_barras'],
        'nombre'        => $producto['nombre'],
        'precio'        => $producto['precio'],
        'stock_minimo'  => $producto['stock_minimo'],
        'stock_total'   => $producto['stock_total']
    ];

    // Si se pidió el detalle por bodega, hacemos una consulta adicional
    if($stock_por_bodega){
        $sql_bodegas = "SELECT b.id, b.nombre,
                               COALESCE(sb.cantidad, 0) as cantidad
                        FROM bodegas b
                        LEFT JOIN stock_bodega sb ON b.id = sb.id_bodega
                                                  AND sb.id_producto = ?
                        WHERE b.activa = 1
                        ORDER BY b.nombre";
        $stmt_b = $conexion->prepare($sql_bodegas);
        $stmt_b->bind_param("i", $producto['id']);
        $stmt_b->execute();
        $res_b = $stmt_b->get_result();

        $bodegas = [];
        while($b = $res_b->fetch_assoc()){
            $bodegas[] = [
                'id'       => $b['id'],
                'nombre'   => $b['nombre'],
                'cantidad' => $b['cantidad']
            ];
        }
        // Agregamos el array de bodegas a la respuesta
        $respuesta['stock_bodegas'] = $bodegas;
    }

    echo json_encode($respuesta);
} else {
    echo json_encode(['encontrado' => false]);
}
?>