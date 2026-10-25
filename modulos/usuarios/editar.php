<?php
/**
 * ARCHIVO: editar.php — Usuarios
 * FUNCIÓN: Formulario para editar un usuario existente.
 *          El campo contraseña es opcional — si se deja vacío
 *          no se cambia la contraseña actual.
 * AUTOR: TechnoSolutions Colombia - SENA ADSO
 */

session_start();

if(!isset($_SESSION['usuario']) || $_SESSION['rol'] != 1){
    header('Location: ../dashboard.php');
    exit();
}

require_once '../../config/conexion.php';

$id = intval($_GET['id']);

if($id == 0){
    header('Location: lista.php');
    exit();
}

// Cargamos los datos actuales del usuario
$stmt = $conexion->prepare(
    "SELECT u.*, r.nombre as rol_nombre
     FROM usuarios u
     JOIN roles r ON u.id_rol = r.id
     WHERE u.id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if(!$usuario){
    header('Location: lista.php');
    exit();
}

// Traemos los roles disponibles para el selector
$roles = $conexion->query("SELECT * FROM roles ORDER BY id");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario — TechnoSolutions</title>
    <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f0f4f8; }
        .sidebar {
            background: linear-gradient(180deg, #1F3864 0%, #2E75B6 100%);
            min-height: 100vh; width: 260px; position: fixed;
            top: 0; left: 0; z-index: 100; overflow-y: auto;
        }
        .sidebar-brand { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-link {
            display: flex; align-items: center; padding: 12px 20px;
            color: rgba(255,255,255,0.8); text-decoration: none;
            border-radius: 8px; margin: 4px 12px; transition: all 0.2s; font-size: 14px;
        }
        .sidebar-link:hover { background: rgba(255,255,255,0.15); color: white; }
        .sidebar-link.active { background: rgba(255,255,255,0.25); color: white; font-weight: 600; }
        .sidebar-link i { width: 24px; margin-right: 10px; }
        .sidebar-section {
            padding: 8px 20px 4px; font-size: 10px; font-weight: 700;
            text-transform: uppercase; color: rgba(255,255,255,0.4);
            letter-spacing: 1.5px; margin-top: 8px;
        }
        .main-content { margin-left: 260px; }
        .topbar {
            background: white; padding: 16px 28px; border-bottom: 1px solid #e2e8f0;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 99;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .form-card { background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        .form-label { font-weight: 600; color: #333; font-size: 14px; }
    </style>
</head>
<body>

<!-- BARRA LATERAL -->
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="d-flex align-items-center">
            <i class="bi bi-boxes text-white me-2" style="font-size:28px;"></i>
            <div>
                <div class="text-white fw-bold" style="font-size:15px;">TechnoSolutions</div>
                <div style="font-size:11px;color:rgba(255,255,255,0.5);">Gestión de Inventario</div>
            </div>
        </div>
    </div>
    <nav class="mt-2 pb-4">
        <div class="sidebar-section">Principal</div>
        <a href="../dashboard.php" class="sidebar-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <div class="sidebar-section">Inventario</div>
        <a href="../productos/lista.php" class="sidebar-link"><i class="bi bi-box-seam"></i> Productos</a>
        <a href="../movimientos/entrada.php" class="sidebar-link"><i class="bi bi-box-arrow-in-down"></i> Entrada de Mercancía</a>
        <a href="../movimientos/salida.php" class="sidebar-link"><i class="bi bi-box-arrow-up"></i> Salida / Venta</a>
        <a href="../movimientos/traslado.php" class="sidebar-link"><i class="bi bi-arrow-left-right"></i> Traslado entre Bodegas</a>
        <a href="../movimientos/historial.php" class="sidebar-link"><i class="bi bi-clock-history"></i> Historial</a>
        <div class="sidebar-section">Reportes</div>
        <a href="../reportes/inventario_pdf.php" class="sidebar-link"><i class="bi bi-file-earmark-pdf"></i> Reporte PDF</a>
        <a href="../reportes/inventario_excel.php" class="sidebar-link"><i class="bi bi-file-earmark-excel"></i> Exportar Excel</a>
        <div class="sidebar-section">Administración</div>
        <a href="lista.php" class="sidebar-link active"><i class="bi bi-people"></i> Usuarios</a>
        <a href="../bodegas/lista.php" class="sidebar-link"><i class="bi bi-building"></i> Bodegas</a>
        <div class="sidebar-section">Sesión</div>
        <a href="../auth/cerrar_sesion.php" class="sidebar-link"><i class="bi bi-box-arrow-left"></i> Cerrar Sesión</a>
    </nav>
</div>

<!-- CONTENIDO PRINCIPAL -->
<div class="main-content">
    <div class="topbar">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Editar Usuario</h5>
            <small class="text-muted">
                Modificando: <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong>
            </small>
        </div>
        <a href="lista.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="p-4">
        <div class="row justify-content-center">
            <div class="col-xl-7">
                <div class="form-card p-4">

                    <h6 class="fw-bold text-primary mb-4 pb-2 border-bottom">
                        <i class="bi bi-person-gear me-2"></i>
                        Datos del Usuario
                    </h6>

                    <form action="procesar_usuario.php" method="POST">
                        <input type="hidden" name="accion" value="editar">
                        <!-- ID oculto para identificar qué usuario actualizar -->
                        <input type="hidden" name="id"
                               value="<?php echo $usuario['id']; ?>">

                        <div class="mb-3">
                            <label class="form-label">
                                Nombre Completo <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="nombre"
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                                   required autofocus>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Correo Electrónico <span class="text-danger">*</span>
                            </label>
                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($usuario['email']); ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Rol <span class="text-danger">*</span>
                            </label>
                            <select name="id_rol" class="form-select" required>
                                <?php while($r = $roles->fetch_assoc()): ?>
                                <option value="<?php echo $r['id']; ?>"
                                    <?php echo $usuario['id_rol'] == $r['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($r['nombre']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Sección de cambio de contraseña OPCIONAL -->
                        <div class="p-3 bg-light rounded mb-4 border">
                            <p class="fw-semibold mb-2 small text-muted">
                                <i class="bi bi-lock me-1"></i>
                                Cambiar contraseña — <em>déjalo vacío si no quieres cambiarla</em>
                            </p>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <input type="password"
                                           name="password"
                                           id="password"
                                           class="form-control"
                                           placeholder="Nueva contraseña">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <input type="password"
                                           name="password_confirm"
                                           id="password_confirm"
                                           class="form-control"
                                           placeholder="Confirmar nueva contraseña">
                                </div>
                            </div>
                            <div id="msg_password" class="small mt-1" style="display:none;"></div>
                        </div>

                        <div class="d-flex gap-3 pt-2 border-top">
                            <button type="submit" id="btnGuardar" class="btn btn-primary px-5">
                                <i class="bi bi-check-lg me-2"></i>Guardar Cambios
                            </button>
                            <a href="lista.php" class="btn btn-outline-secondary px-4">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
// Igual que en crear.php — verifica que las contraseñas coincidan
function verificarPassword() {
    const pwd1 = document.getElementById('password').value;
    const pwd2 = document.getElementById('password_confirm').value;
    const msg  = document.getElementById('msg_password');
    const btn  = document.getElementById('btnGuardar');

    if(pwd2.length === 0 && pwd1.length === 0) {
        msg.style.display = 'none';
        btn.disabled = false; // Sin contraseña nueva es válido
        return;
    }

    msg.style.display = 'block';

    if(pwd1 === pwd2) {
        msg.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i>' +
                        '<span class="text-success">Las contraseñas coinciden</span>';
        btn.disabled = false;
    } else {
        msg.innerHTML = '<i class="bi bi-x-circle-fill text-danger me-1"></i>' +
                        '<span class="text-danger">Las contraseñas no coinciden</span>';
        btn.disabled = true;
    }
}

document.getElementById('password').addEventListener('input', verificarPassword);
document.getElementById('password_confirm').addEventListener('input', verificarPassword);
</script>
</body>
</html>