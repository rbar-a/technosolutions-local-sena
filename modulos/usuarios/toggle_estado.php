<?php
/**
 * ARCHIVO: toggle_estado.php
 * FUNCIÓN: Activa o desactiva un usuario según su estado actual.
 *          Protege al administrador logueado de desactivarse a sí mismo.
 * AUTOR: TechnoSolutions Colombia - SENA ADSO
 */

session_start();

if(!isset($_SESSION['usuario']) || $_SESSION['rol'] != 1){
    header('Location: ../dashboard.php');
    exit();
}

require_once '../../config/conexion.php';

$id = intval($_GET['id']);

// No permitimos que el administrador se desactive a sí mismo
if($id === intval($_SESSION['usuario'])){
    header('Location: lista.php');
    exit();
}

// Invertimos el estado: si estaba activo (1) pasa a 0, y viceversa
$stmt = $conexion->prepare(
    "UPDATE usuarios SET activo = IF(activo = 1, 0, 1) WHERE id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();

header('Location: lista.php?exito=editado');
exit();
?>