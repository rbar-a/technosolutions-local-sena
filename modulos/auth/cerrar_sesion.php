<?php
/**
 * ARCHIVO: cerrar_sesion.php
 * FUNCIÓN: Destruye la sesión del usuario y lo regresa al login.
 */

// Iniciamos la sesión para poder destruirla
session_start();

// session_destroy() elimina TODAS las variables de sesión
// El usuario queda como si nunca hubiera iniciado sesión
session_destroy();

// Redirigimos al login con mensaje de confirmación
header('Location: login.php?logout=1');
exit();
?>