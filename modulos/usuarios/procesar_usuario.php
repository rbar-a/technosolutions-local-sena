<?php
/**
 * ARCHIVO: procesar_usuario.php
 * FUNCIÓN: Crea o edita usuarios. Encripta la contraseña con bcrypt.
 * AUTOR: TechnoSolutions Colombia - SENA ADSO
 */

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

$accion = $_POST['accion'];
$nombre = trim($_POST['nombre']);
$email  = trim($_POST['email']);
$id_rol = intval($_POST['id_rol']);

if(empty($nombre) || empty($email) || $id_rol == 0){
    header("Location: crear.php?error=campos_vacios");
    exit();
}

if($accion === 'crear'){

    $password         = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // Validamos que la contraseña tenga mínimo 6 caracteres
    if(strlen($password) < 6){
        header('Location: crear.php?error=password_corta');
        exit();
    }

    // Verificamos que las dos contraseñas sean iguales
    if($password !== $password_confirm){
        header('Location: crear.php?error=campos_vacios');
        exit();
    }

    // Verificamos que el email no esté ya registrado
    $check = $conexion->prepare("SELECT id FROM usuarios WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if($check->get_result()->num_rows > 0){
        header('Location: crear.php?error=email_duplicado');
        exit();
    }

    // Encriptamos la contraseña con bcrypt (PASSWORD_DEFAULT usa bcrypt)
    // NUNCA se guarda la contraseña en texto plano
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conexion->prepare(
        "INSERT INTO usuarios (nombre, email, password, id_rol) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("sssi", $nombre, $email, $hash, $id_rol);
    $stmt->execute();

    header('Location: lista.php?exito=creado');
    exit();

} elseif($accion === 'editar'){

    $id = intval($_POST['id']);

    // Si se envió una nueva contraseña, la actualizamos
    if(!empty($_POST['password'])){
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conexion->prepare(
            "UPDATE usuarios SET nombre=?, email=?, id_rol=?, password=? WHERE id=?"
        );
        $stmt->bind_param("ssisi", $nombre, $email, $id_rol, $hash, $id);
    } else {
        // Si no se envió contraseña, solo actualizamos nombre, email y rol
        $stmt = $conexion->prepare(
            "UPDATE usuarios SET nombre=?, email=?, id_rol=? WHERE id=?"
        );
        $stmt->bind_param("ssii", $nombre, $email, $id_rol, $id);
    }

    $stmt->execute();
    header('Location: lista.php?exito=editado');
    exit();
}
?>