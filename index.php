<?php
/**
 * ARCHIVO: index.php
 * UBICACIÓN: /technosolutions/index.php
 * FUNCIÓN: Punto de entrada principal del sistema.
 *          Redirige al usuario según su estado de sesión.
 * AUTOR: TechnoSolutions Colombia - SENA ADSO
 */

// Iniciamos la sesión de PHP para poder leer las variables de sesión.
// Esto SIEMPRE debe ser lo primero en archivos que usan sesiones.
session_start();

// Verificamos si ya existe una sesión activa.
// Cuando el usuario inicia sesión, guardamos su ID en $_SESSION['usuario'].
// Si esa variable existe, significa que ya está logueado.
if(isset($_SESSION['usuario'])){
    
    // Si ya tiene sesión, lo enviamos directo al dashboard (panel principal).
    // header('Location:') es la forma de redirigir en PHP.
    header('Location: modulos/dashboard.php');
    
    // exit() detiene la ejecución del código para que no continúe después del redirect.
    exit();
}

// Si NO tiene sesión activa, lo enviamos a la pantalla de login.
header('Location: modulos/auth/login.php');
exit();
?>