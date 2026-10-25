<?php
/**
 * ARCHIVO: procesar.php
 * UBICACIÓN: /technosolutions/modulos/productos/procesar.php
 * FUNCIÓN: Recibe los datos del formulario de producto y los guarda
 *          en la base de datos. Maneja tanto CREAR como EDITAR productos.
 *          Usa transacciones MySQL para garantizar integridad de datos.
 * AUTOR: TechnoSolutions Colombia - SENA ADSO
 */

session_start();

// Protección: solo administradores
if(!isset($_SESSION['usuario']) || $_SESSION['rol'] != 1){
    header('Location: lista.php');
    exit();
}

require_once '../../config/conexion.php';

// Solo aceptamos método POST (datos enviados desde el formulario)
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Location: lista.php');
    exit();
}

// ── RECIBIR Y LIMPIAR DATOS ──────────────────────────────────────────────────
$accion       = $_POST['accion'];           // 'crear' o 'editar'
$codigo       = trim($_POST['codigo_barras']);
$nombre       = trim($_POST['nombre']);
$descripcion  = trim($_POST['descripcion']);
$precio       = floatval($_POST['precio']); // floatval convierte a número decimal
$stock_minimo = intval($_POST['stock_minimo']); // intval convierte a número entero

// Arrays del stock inicial por bodega
$bodega_ids = $_POST['bodega_id']; // Array con IDs de bodegas
$stocks     = $_POST['stock'];      // Array con cantidades

// ── VALIDACIONES BÁSICAS ─────────────────────────────────────────────────────
// Verificamos que los campos obligatorios no estén vacíos
if(empty($codigo) || empty($nombre)){
    header('Location: crear.php?error=campos_vacios');
    exit();
}

// ── TRANSACCIÓN MySQL ────────────────────────────────────────────────────────
/*
    Una TRANSACCIÓN agrupa varias operaciones SQL en una sola unidad.
    Si cualquier operación falla, se hace ROLLBACK (deshace todo).
    Si todo sale bien, se hace COMMIT (confirma todos los cambios).
    
    Esto garantiza que nunca quede el producto guardado sin su stock,
    ni el stock guardado sin el producto. O todo o nada.
*/
$conexion->begin_transaction();

try {
    if($accion === 'crear'){

        // ── VERIFICAR CÓDIGO DUPLICADO ─────────────────────────────────
        // Buscamos si ya existe un producto con ese código de barras
        $check = $conexion->prepare(
            "SELECT id FROM productos WHERE codigo_barras = ?"
        );
        $check->bind_param("s", $codigo);
        $check->execute();

        if($check->get_result()->num_rows > 0){
            // Ya existe: cancelamos la transacción y mostramos error
            $conexion->rollback();
            header('Location: crear.php?error=codigo_duplicado');
            exit();
        }

        // ── INSERTAR PRODUCTO ──────────────────────────────────────────
        $sql_insert = "INSERT INTO productos 
                       (codigo_barras, nombre, descripcion, precio, stock_minimo) 
                       VALUES (?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql_insert);
        // "sssdi" = string, string, string, double(decimal), integer
        $stmt->bind_param("sssdi", $codigo, $nombre, $descripcion, $precio, $stock_minimo);
        $stmt->execute();

        // Obtenemos el ID del producto recién insertado
        // insert_id devuelve el AUTO_INCREMENT generado
        $producto_id = $conexion->insert_id;

        // ── INSERTAR STOCK POR BODEGA ──────────────────────────────────
        // Guardamos el stock inicial para cada bodega
        $sql_stock = "INSERT INTO stock_bodega (id_producto, id_bodega, cantidad) 
                      VALUES (?, ?, ?)";
        $stmt_stock = $conexion->prepare($sql_stock);

        // Recorremos el array de bodegas y stocks
        // count() devuelve el número de elementos del array
        for($i = 0; $i < count($bodega_ids); $i++){
            $bid      = intval($bodega_ids[$i]);
            $cantidad = intval($stocks[$i]);
            $stmt_stock->bind_param("iii", $producto_id, $bid, $cantidad);
            $stmt_stock->execute();
        }

        // Todo salió bien: confirmamos la transacción
        $conexion->commit();
        header('Location: lista.php?exito=creado');
        exit();

    } elseif($accion === 'editar'){

        // ── EDITAR PRODUCTO EXISTENTE ──────────────────────────────────
        $id = intval($_POST['id']); // ID del producto a editar

        $sql_update = "UPDATE productos 
                       SET codigo_barras = ?, 
                           nombre = ?, 
                           descripcion = ?, 
                           precio = ?, 
                           stock_minimo = ?
                       WHERE id = ?";
        $stmt = $conexion->prepare($sql_update);
        // "sssdii" = string, string, string, double, integer, integer
        $stmt->bind_param("sssdii", $codigo, $nombre, $descripcion, $precio, $stock_minimo, $id);
        $stmt->execute();

        $conexion->commit();
        header('Location: lista.php?exito=editado');
        exit();
    }

} catch(Exception $e) {
    // Si ocurre cualquier error inesperado, deshacemos todos los cambios
    $conexion->rollback();
    // En producción no deberías mostrar el error real, pero para desarrollo es útil
    die("Error al procesar: " . $e->getMessage());
}
?>