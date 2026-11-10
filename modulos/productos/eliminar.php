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
require_once '../../config/csrf.php';

// Solo POST: la baja lógica ya no se ejecuta por GET (evita CSRF).
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Location: lista.php');
    exit();
}

ts_csrf_validar();

$id = intval($_POST['id'] ?? 0);

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