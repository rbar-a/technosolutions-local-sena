<?php
/**
 * ARCHIVO: config/sesion.php
 * FUNCIÓN: Endurecimiento de la sesión PHP: cookies HttpOnly/SameSite,
 *          expiración por inactividad y utilidades de cierre de sesión.
 */

if (!function_exists('ts_configurar_cookies_sesion')) {

    function ts_configurar_cookies_sesion(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }
        $https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
              || (($_SERVER['SERVER_PORT'] ?? '') == 443);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    function ts_iniciar_sesion(): void
    {
        ts_configurar_cookies_sesion();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    function ts_sesion_expirada(int $minutos = 30): bool
    {
        if (!isset($_SESSION['usuario']) || empty($_SESSION['ultima_actividad'])) {
            return false;
        }
        return (time() - (int) $_SESSION['ultima_actividad']) > $minutos * 60;
    }

    function ts_marcar_actividad(): void
    {
        $_SESSION['ultima_actividad'] = time();
    }

    function ts_cerrar_sesion_actual(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /** Ruta absoluta al login, válida tanto con php -S como bajo un subdirectorio de Apache. */
    function ts_url_login(): string
    {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $pos = strpos($script, '/modulos/');
        $base = $pos !== false ? substr($script, 0, $pos) : '';
        return $base . '/modulos/auth/login.php';
    }
}
