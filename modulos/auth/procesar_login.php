<?php
// Iniciamos la sesión ANTES de cualquier otra cosa.
session_start();

// Incluimos el archivo de conexión a la base de datos.
require_once '../../config/conexion.php';

// ── VALIDACIÓN DE MÉTODO HTTP ────────────────────────────────────────────────
// Este archivo SOLO debe ejecutarse cuando se envía el formulario (método POST).
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Location: login.php');
    exit();
}

// ── RECIBIR Y LIMPIAR DATOS DEL FORMULARIO ───────────────────────────────────
$email    = trim($_POST['email']);
$password = $_POST['password'];

// ── CONSULTA A LA BASE DE DATOS ──────────────────────────────────────────────
$sql = "SELECT u.id, u.nombre, u.email, u.password, u.id_rol, u.activo, r.nombre as rol_nombre 
        FROM usuarios u 
        JOIN roles r ON u.id_rol = r.id 
        WHERE u.email = ? 
        AND u.activo = 1";

// ── PREPARED STATEMENT ───────────────────────────────────────────────────────
$stmt = $conexion->prepare($sql);

if(!$stmt){
    header('Location: login.php?error=servidor');
    exit();
}

$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

// ── VERIFICAR SI EL USUARIO EXISTE ───────────────────────────────────────────
if($stmt->num_rows === 0){
    header('Location: login.php?error=credenciales');
    exit();
}

// ── OBTENER DATOS DEL USUARIO ────────────────────────────────────────────────
// Usamos bind_result en lugar de get_result() por compatibilidad con InfinityFree
$id         = null;
$nombre     = null;
$email_db   = null;
$password_db= null;
$id_rol     = null;
$activo     = null;
$rol_nombre = null;

$stmt->bind_result($id, $nombre, $email_db, $password_db, $id_rol, $activo, $rol_nombre);
$stmt->fetch();
$stmt->close();

// ── VERIFICAR LA CONTRASEÑA ──────────────────────────────────────────────────
// password_verify compara la contraseña ingresada con el hash guardado en BD
if(!password_verify($password, $password_db)){
    header('Location: login.php?error=credenciales');
    exit();
}

// ── CREAR LA SESIÓN ──────────────────────────────────────────────────────────
// Si llegamos aquí, el usuario y contraseña son correctos.
$_SESSION['usuario']    = $id;
$_SESSION['nombre']     = $nombre;
$_SESSION['email']      = $email_db;
$_SESSION['rol']        = $id_rol;
$_SESSION['rol_nombre'] = $rol_nombre;

// ── REDIRIGIR AL DASHBOARD ───────────────────────────────────────────────────
header('Location: ../dashboard.php');
exit();
?>