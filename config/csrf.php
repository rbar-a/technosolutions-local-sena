<?php
/**
 * ARCHIVO: config/csrf.php
 * FUNCIÓN: Genera y valida el token anti-CSRF de los formularios que
 *          cambian el estado del sistema.
 */

require_once __DIR__ . '/sesion.php';

if (!function_exists('ts_csrf_token')) {

    function ts_csrf_token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            ts_iniciar_sesion();
        }
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    function ts_csrf_campo(): string
    {
        return '<input type="hidden" name="csrf_token" value="'
             . htmlspecialchars(ts_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    function ts_csrf_validar(): void
    {
        $enviado  = $_POST['csrf_token'] ?? '';
        $guardado = $_SESSION['csrf_token'] ?? '';
        if (!is_string($enviado) || !is_string($guardado) || $guardado === ''
            || !hash_equals($guardado, $enviado)) {
            http_response_code(403);
            die('Solicitud no válida (posible CSRF). Recarga la página e inténtalo de nuevo.');
        }
    }
}
