<?php
session_start();

if(!isset($_SESSION['usuario']) || $_SESSION['rol'] != 1){
    header('Location: ../dashboard.php');
    exit();
}

require_once '../../config/conexion.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Location: lista.php');
    exit();
}

$accion    = $_POST['accion'];
$nombre    = trim($_POST['nombre']);
$direccion = trim($_POST['direccion'] ?? '');

if(empty($nombre)){
    header('Location: crear.php?error=campos_vacios');
    exit();
}

if($accion === 'crear'){
    $stmt = $conexion->prepare(
        "INSERT INTO bodegas (nombre, direccion) VALUES (?, ?)"
    );
    $stmt->bind_param("ss", $nombre, $direccion);
    $stmt->execute();
    header('Location: lista.php?exito=creada');
    exit();

} elseif($accion === 'editar'){
    $id     = intval($_POST['id']);
    $activa = intval($_POST['activa']);
    $stmt = $conexion->prepare(
        "UPDATE bodegas SET nombre = ?, direccion = ?, activa = ? WHERE id = ?"
    );
    $stmt->bind_param("ssii", $nombre, $direccion, $activa, $id);
    $stmt->execute();
    header('Location: lista.php?exito=editada');
    exit();
}
?>