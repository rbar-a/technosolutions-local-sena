<?php
/**
 * ARCHIVO: procesar_movimiento.php
 * UBICACIÓN: /technosolutions/modulos/movimientos/procesar_movimiento.php
 * FUNCIÓN: Procesa los 3 tipos de movimiento del inventario:
 *          - ENTRADA: suma stock a una bodega
 *          - SALIDA:  resta stock de una bodega (venta)
 *          - TRASLADO: resta de una bodega y suma a otra
 *
 *          Usa transacciones para garantizar que el stock
 *          siempre quede consistente en la base de datos.
 * AUTOR: TechnoSolutions Colombia - SENA ADSO
 */

session_start();

if(!isset($_SESSION['usuario'])){
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Location: ../dashboard.php');
    exit();
}

// Control de acceso: solo administradores pueden registrar entradas.
if (
    ($_SERVER['REQUEST_METHOD'] === 'POST') &&
    (($_POST['tipo'] ?? '') === 'entrada') &&
    ((int)($_SESSION['rol'] ?? 0) !== 1)
) {
    header('Location: entrada.php?error=sin_permiso');
    exit();
}

// ── RECIBIR DATOS ────────────────────────────────────────────────────────────
$tipo        = $_POST['tipo'] ?? '';     // 'entrada', 'salida' o 'traslado'

// Validación en lista blanca: un tipo desconocido se rechaza (evita pantalla en blanco y redirecciones arbitrarias).
if(!in_array($tipo, ['entrada', 'salida', 'traslado'], true)){
    header('Location: ../dashboard.php');
    exit();
}
$id_producto = intval($_POST['id_producto']);
$cantidad    = intval($_POST['cantidad']);
$observacion = trim($_POST['observacion'] ?? '');
$id_usuario  = $_SESSION['usuario'];

// Bodega origen (para salida y traslado)
$id_bodega_origen  = isset($_POST['id_bodega_origen'])  ? intval($_POST['id_bodega_origen'])  : null;
// Bodega destino (para entrada y traslado)
$id_bodega_destino = isset($_POST['id_bodega_destino']) ? intval($_POST['id_bodega_destino']) : null;

// ── VALIDACIONES ─────────────────────────────────────────────────────────────
if($id_producto == 0 || $cantidad <= 0){
    header("Location: {$tipo}.php?error=campos_vacios");
    exit();
}

// Iniciamos la transacción para garantizar integridad
$conexion->begin_transaction();

try {

    // ════════════════════════════════════════════════════════════════════════
    //  CASO 1: ENTRADA DE MERCANCÍA
    //  Suma la cantidad al stock de la bodega destino.
    //  Si el producto no tiene registro en esa bodega, lo crea con INSERT.
    //  Si ya existe, suma con UPDATE.
    // ════════════════════════════════════════════════════════════════════════
    if($tipo === 'entrada'){

        // INSERT ... ON DUPLICATE KEY UPDATE:
        // Si ya existe el par (id_producto, id_bodega) en stock_bodega, hace UPDATE.
        // Si no existe, hace INSERT. Todo en una sola consulta.
        $sql = "INSERT INTO stock_bodega (id_producto, id_bodega, cantidad)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE cantidad = cantidad + ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iiii",
            $id_producto,
            $id_bodega_destino,
            $cantidad,
            $cantidad
        );
        $stmt->execute();

        // Registramos el movimiento en el historial
        $sql_mov = "INSERT INTO movimientos
                    (tipo, id_producto, id_bodega_destino, cantidad, id_usuario, observacion)
                    VALUES ('entrada', ?, ?, ?, ?, ?)";
        $stmt_mov = $conexion->prepare($sql_mov);
        $stmt_mov->bind_param("iiiis",
            $id_producto,
            $id_bodega_destino,
            $cantidad,
            $id_usuario,
            $observacion
        );
        $stmt_mov->execute();

        $conexion->commit();
        header('Location: entrada.php?exito=1');
        exit();
    }

    // ════════════════════════════════════════════════════════════════════════
    //  CASO 2: SALIDA POR VENTA
    //  Verifica que haya suficiente stock antes de restar.
    //  Nunca permite que el stock quede negativo.
    // ════════════════════════════════════════════════════════════════════════
    elseif($tipo === 'salida'){

        // Verificamos el stock disponible en la bodega origen
        $sql_check = "SELECT cantidad FROM stock_bodega
                      WHERE id_producto = ? AND id_bodega = ?";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->bind_param("ii", $id_producto, $id_bodega_origen);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result()->fetch_assoc();

        // Si no hay registro o el stock es insuficiente, cancelamos
        if(!$res_check || $res_check['cantidad'] < $cantidad){
            $conexion->rollback();
            header('Location: salida.php?error=stock_insuficiente');
            exit();
        }

        // Restamos el stock de la bodega
        $sql = "UPDATE stock_bodega
                SET cantidad = cantidad - ?
                WHERE id_producto = ? AND id_bodega = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iii", $cantidad, $id_producto, $id_bodega_origen);
        $stmt->execute();

        // Registramos en el historial
        $sql_mov = "INSERT INTO movimientos
                    (tipo, id_producto, id_bodega_origen, cantidad, id_usuario, observacion)
                    VALUES ('salida', ?, ?, ?, ?, ?)";
        $stmt_mov = $conexion->prepare($sql_mov);
        $stmt_mov->bind_param("iiiis",
            $id_producto,
            $id_bodega_origen,
            $cantidad,
            $id_usuario,
            $observacion
        );
        $stmt_mov->execute();

        $conexion->commit();
        header('Location: salida.php?exito=1');
        exit();
    }

    // ════════════════════════════════════════════════════════════════════════
    //  CASO 3: TRASLADO ENTRE BODEGAS
    //  Verifica stock en origen, resta de origen y suma en destino.
    //  Si origen y destino son iguales, rechaza la operación.
    // ════════════════════════════════════════════════════════════════════════
    elseif($tipo === 'traslado'){

        // No tiene sentido trasladar a la misma bodega
        if($id_bodega_origen === $id_bodega_destino){
            $conexion->rollback();
            header('Location: traslado.php?error=misma_bodega');
            exit();
        }

        // Verificamos stock en la bodega origen
        $sql_check = "SELECT cantidad FROM stock_bodega
                      WHERE id_producto = ? AND id_bodega = ?";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->bind_param("ii", $id_producto, $id_bodega_origen);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result()->fetch_assoc();

        if(!$res_check || $res_check['cantidad'] < $cantidad){
            $conexion->rollback();
            header('Location: traslado.php?error=stock_insuficiente');
            exit();
        }

        // Restamos de la bodega origen
        $sql_resta = "UPDATE stock_bodega
                      SET cantidad = cantidad - ?
                      WHERE id_producto = ? AND id_bodega = ?";
        $stmt_resta = $conexion->prepare($sql_resta);
        $stmt_resta->bind_param("iii", $cantidad, $id_producto, $id_bodega_origen);
        $stmt_resta->execute();

        // Sumamos en la bodega destino (creamos registro si no existe)
        $sql_suma = "INSERT INTO stock_bodega (id_producto, id_bodega, cantidad)
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE cantidad = cantidad + ?";
        $stmt_suma = $conexion->prepare($sql_suma);
        $stmt_suma->bind_param("iiii",
            $id_producto,
            $id_bodega_destino,
            $cantidad,
            $cantidad
        );
        $stmt_suma->execute();

        // Registramos en el historial con origen Y destino
        $sql_mov = "INSERT INTO movimientos
                    (tipo, id_producto, id_bodega_origen, id_bodega_destino,
                     cantidad, id_usuario, observacion)
                    VALUES ('traslado', ?, ?, ?, ?, ?, ?)";
        $stmt_mov = $conexion->prepare($sql_mov);
        $stmt_mov->bind_param("iiiiis",
            $id_producto,
            $id_bodega_origen,
            $id_bodega_destino,
            $cantidad,
            $id_usuario,
            $observacion
        );
        $stmt_mov->execute();

        $conexion->commit();
        header('Location: traslado.php?exito=1');
        exit();
    }

} catch(Exception $e) {
    // Cualquier error inesperado: deshacemos todo
    $conexion->rollback();
    die("Error al procesar movimiento: " . $e->getMessage());
}
?>