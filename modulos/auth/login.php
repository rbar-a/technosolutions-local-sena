<?php
/**
 * ARCHIVO: login.php
 * UBICACIÓN: /technosolutions/modulos/auth/login.php
 * FUNCIÓN: Muestra el formulario de inicio de sesión.
 *          También muestra mensajes de error o éxito según corresponda.
 * AUTOR: TechnoSolutions Colombia - SENA ADSO
 */

// Iniciamos la sesión para poder leer y escribir variables de sesión.
session_start();

// Si el usuario ya tiene sesión activa, no tiene sentido mostrarle el login.
// Lo redirigimos directo al dashboard.
if(isset($_SESSION['usuario'])){
    header('Location: ../dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- Hace que el diseño se adapte bien a celulares y tablets -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechnoSolutions — Iniciar Sesión</title>

    <!-- Bootstrap 5: librería CSS que nos da el diseño profesional y responsive -->
    <!-- La cargamos desde un CDN (internet) para no tener que descargarla -->
    <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons: librería de íconos que usamos en el formulario -->
    <link href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* ── ESTILOS PERSONALIZADOS ─────────────────────────────────────────── */

        /* Fondo degradado azul que cubre toda la pantalla */
        body {
            background: linear-gradient(135deg, #1F3864 0%, #2E75B6 100%);
            min-height: 100vh;        /* Ocupa el 100% de la altura de la pantalla */
            display: flex;            /* Usamos flexbox para centrar el contenido */
            align-items: center;      /* Centrado vertical */
            justify-content: center;  /* Centrado horizontal */
        }

        /* Tarjeta blanca del formulario */
        .card-login {
            border: none;                    /* Sin borde */
            border-radius: 16px;             /* Bordes redondeados */
            box-shadow: 0 20px 60px rgba(0,0,0,0.3); /* Sombra suave */
            width: 100%;
            max-width: 420px;                /* Ancho máximo de la tarjeta */
        }

        /* Encabezado azul de la tarjeta con el logo */
        .card-header-login {
            background: linear-gradient(135deg, #1F3864, #2E75B6);
            border-radius: 16px 16px 0 0 !important; /* Solo redondea arriba */
            padding: 30px;
            text-align: center;
        }

        /* Botón de ingresar */
        .btn-login {
            background: linear-gradient(135deg, #1F3864, #2E75B6);
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
        }

        /* Efecto hover: el botón sube levemente al pasar el mouse */
        .btn-login:hover {
            background: linear-gradient(135deg, #2E75B6, #1F3864);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(46,117,182,0.4);
        }

        /* Color del borde cuando un campo está activo (con foco) */
        .form-control:focus {
            border-color: #2E75B6;
            box-shadow: 0 0 0 0.2rem rgba(46,117,182,0.25);
        }

        /* Fondo del ícono dentro del campo de texto */
        .input-group-text {
            background-color: #f8f9fa;
            border-right: none; /* Quita el borde derecho para unirlo al input */
        }

        /* Quitamos el borde izquierdo del input para que se vea como una sola pieza */
        .form-control {
            border-left: none;
        }
    </style>
</head>
<body>

    <!-- TARJETA PRINCIPAL DEL LOGIN -->
    <div class="card card-login">

        <!-- ENCABEZADO: logo e ícono de la empresa -->
        <div class="card-header-login">
            <!-- Ícono de cajas (inventario) de Bootstrap Icons -->
            <i class="bi bi-boxes text-white" style="font-size: 48px;"></i>
            <h4 class="text-white mt-2 mb-0 fw-bold">TechnoSolutions</h4>
            <small class="text-white-50">Sistema de Gestión de Inventario</small>
        </div>

        <!-- CUERPO DE LA TARJETA: formulario -->
        <div class="card-body p-4">

            <!-- ── MENSAJE DE ERROR ──────────────────────────────────────────── -->
            <!-- 
                Cuando el login falla, procesar_login.php redirige con ?error=credenciales
                en la URL. Aquí verificamos si ese parámetro existe y mostramos el mensaje.
                isset() verifica si una variable o parámetro existe.
            -->
            <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php
                    // Mostramos mensajes diferentes según el tipo de error
                    if($_GET['error'] == 'credenciales') 
                        echo 'Usuario o contraseña incorrectos.';
                    if($_GET['error'] == 'inactivo')     
                        echo 'Tu cuenta está desactivada. Contacta al administrador.';
                ?>
                <!-- Botón X para cerrar la alerta -->
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- ── MENSAJE DE CIERRE DE SESIÓN EXITOSO ───────────────────────── -->
            <!-- 
                Cuando el usuario cierra sesión, redirigimos con ?logout=1 en la URL.
                Aquí mostramos el mensaje de confirmación.
            -->
            <?php if(isset($_GET['logout'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                Sesión cerrada correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <h5 class="mb-4 text-center text-muted">Iniciar Sesión</h5>

            <!-- ── FORMULARIO DE LOGIN ────────────────────────────────────────── -->
            <!-- 
                action="procesar_login.php": cuando se envíe el formulario, los datos
                van al archivo procesar_login.php para ser verificados.
                method="POST": los datos viajan de forma segura (no aparecen en la URL).
            -->
            <form action="procesar_login.php" method="POST">

                <!-- Campo de correo electrónico -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Correo electrónico</label>
                    <!-- input-group agrupa el ícono y el campo de texto visualmente -->
                    <div class="input-group">
                        <!-- Ícono decorativo a la izquierda del campo -->
                        <span class="input-group-text">
                            <i class="bi bi-envelope text-muted"></i>
                        </span>
                        <!-- 
                            name="email": así se llama el dato cuando llegue a PHP.
                            required: el navegador no permite enviar si está vacío.
                            autofocus: el cursor aparece aquí al cargar la página.
                        -->
                        <input type="email" 
                               name="email" 
                               class="form-control"
                               placeholder="correo@technosolutions.com"
                               required 
                               autofocus>
                    </div>
                </div>

                <!-- Campo de contraseña -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock text-muted"></i>
                        </span>
                        <!-- 
                            type="password": oculta los caracteres con puntos.
                            id="password": lo usamos en JavaScript para mostrar/ocultar.
                        -->
                        <input type="password" 
                               name="password" 
                               id="password"
                               class="form-control" 
                               placeholder="••••••••" 
                               required>
                        <!-- Botón del ojo para mostrar/ocultar la contraseña -->
                        <button class="btn btn-outline-secondary" 
                                type="button"
                                onclick="togglePassword()">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Botón de envío del formulario -->
                <button type="submit" class="btn btn-login btn-primary w-100 text-white">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar al Sistema
                </button>

            </form>
        </div>

        <!-- PIE DE LA TARJETA -->
        <div class="card-footer text-center text-muted py-3" 
             style="border-radius: 0 0 16px 16px;">
            <small>© 2025 TechnoSolutions Colombia — SENA ADSO</small>
        </div>
    </div>

    <!-- Bootstrap JS: necesario para que funcionen alertas, modales, etc. -->
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <script>
        /**
         * FUNCIÓN: togglePassword()
         * QUÉ HACE: Alterna entre mostrar y ocultar la contraseña
         *           cuando el usuario hace clic en el ícono del ojo.
         */
        function togglePassword() {
            // Obtenemos el campo de contraseña por su ID
            const pwd  = document.getElementById('password');
            // Obtenemos el ícono del ojo por su ID
            const icon = document.getElementById('eyeIcon');

            // Si actualmente está oculta (type="password"), la mostramos
            if (pwd.type === 'password') {
                pwd.type = 'text';  // Cambiamos a texto visible
                // Cambiamos el ícono de ojo abierto a ojo tachado
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                // Si está visible, la volvemos a ocultar
                pwd.type = 'password';
                // Cambiamos el ícono de ojo tachado a ojo abierto
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }
    </script>
</body>
</html>