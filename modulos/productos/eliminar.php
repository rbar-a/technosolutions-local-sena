<?php
/**
 * ARCHIVO: eliminar.php — Productos
 * FUNCIÓN: Desactiva un producto (no lo borra físicamente).
 *          Solo Administradores pueden hacer esto.
 * AUTOR: TechnoSolutions Colombia - SENA ADSO
 */

session_start();

if(!isset($_SESSION['usuario']) || $_SESSION['rol'] != 1){
    header('Location: lista.php');
    exit();
}

require_once '../../config/conexion.php';

$id = intval($_GET['id']);

if($id == 0){
    header('Location: lista.php');
    exit();
}

// Desactivamos el producto en lugar de borrarlo
// Así conservamos el historial de movimientos intacto
$stmt = $conexion->prepare(
    "UPDATE productos SET activo = 0 WHERE id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();

header('Location: lista.php?exito=eliminado');
exit();
?>