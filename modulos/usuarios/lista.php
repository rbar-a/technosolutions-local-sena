<?php
/**
 * ARCHIVO: lista.php
 * UBICACIÓN: /technosolutions/modulos/usuarios/lista.php
 * FUNCIÓN: Muestra todos los usuarios del sistema.
 *          Solo accesible para Administradores (rol 1).
 * AUTOR: TechnoSolutions Colombia - SENA ADSO
 */

session_start();

if(!isset($_SESSION['usuario'])){
    header('Location: ../auth/login.php');
    exit();
}

// Solo administradores pueden gestionar usuarios
if($_SESSION['rol'] != 1){
    header('Location: ../dashboard.php');
    exit();
}

require_once '../../config/conexion.php';

// Traemos todos los usuarios con el nombre de su rol
$sql = "SELECT u.*, r.nombre as rol_nombre
        FROM usuarios u
        JOIN roles r ON u.id_rol = r.id
        ORDER BY u.activo DESC, u.nombre ASC";
$usuarios = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios — TechnoSolutions</title>
    <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f0f4f8; }
        .sidebar {
            background: linear-gradient(180deg, #1F3864 0%, #2E75B6 100%);
            min-height: 100vh; width: 260px;
            position: fixed; top: 0; left: 0; z-index: 100; overflow-y: auto;
        }
        .sidebar-brand { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-link {
            display: flex; align-items: center; padding: 12px 20px;
            color: rgba(255,255,255,0.8); text-decoration: none;
            border-radius: 8px; margin: 4px 12px; transition: all 0.2s; font-size: 14px;
        }
        .sidebar-link:hover { background: rgba(255,255,255,0.15); color: white; transform: translateX(4px); }
        .sidebar-link.active { background: rgba(255,255,255,0.25); color: white; font-weight: 600; }
        .sidebar-link i { width: 24px; margin-right: 10px; font-size: 16px; }
        .sidebar-section {
            padding: 8px 20px 4px; font-size: 10px; font-weight: 700;
            text-transform: uppercase; color: rgba(255,255,255,0.4);
            letter-spacing: 1.5px; margin-top: 8px;
        }
        .main-content { margin-left: 260px; }
        .topbar {
            background: white; padding: 16px 28px;
            border-bottom: 1px solid #e2e8f0;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 99;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .content-card {
            background: white; border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        .table thead th {
            background-color: #1F3864; color: white;
            font-weight: 600; font-size: 13px;
            padding: 14px 16px; border: none;
        }
        .table tbody td {
            padding: 12px 16px; vertical-align: middle;
            font-size: 14px; border-bottom: 1px solid #f0f4f8;
        }
        .table tbody tr:hover { background-color: #f8faff; }
        /* Avatar circular con inicial del nombre */
        .avatar {
            width: 38px; height: 38px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 15px; color: white;
            background: linear-gradient(135deg, #1F3864, #2E75B6);
        }
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
    <div class="p-3 border-bottom" style="border-color:rgba(255,255,255,0.1)!important;">
        <div class="d-flex align-items-center">
            <div class="rounded-circle bg-white d-flex align-items-center justify-content-center me-2"
                 style="width:36px;height:36px;min-width:36px;">
                <span class="fw-bold text-primary" style="font-size:14px;">
                    <?php echo strtoupper(substr($_SESSION['nombre'], 0, 1)); ?>
                </span>
            </div>
            <div>
                <div class="text-white fw-semibold" style="font-size:13px;">
                    <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                </div>
                <div style="font-size:11px;color:rgba(255,255,255,0.5);">
                    <?php echo htmlspecialchars($_SESSION['rol_nombre']); ?>
                </div>
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
            <h5 class="mb-0 fw-bold text-dark">Gestión de Usuarios</h5>
            <small class="text-muted">Administrar accesos al sistema</small>
        </div>
        <a href="crear.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>Nuevo Usuario
        </a>
    </div>

    <div class="p-4">

        <?php if(isset($_GET['exito'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php
                if($_GET['exito'] == 'creado')    echo 'Usuario creado exitosamente.';
                if($_GET['exito'] == 'editado')   echo 'Usuario actualizado exitosamente.';
                if($_GET['exito'] == 'eliminado') echo 'Usuario desactivado exitosamente.';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="content-card overflow-hidden">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Correo Electrónico</th>
                            <th>Rol</th>
                            <th>Fecha Registro</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($u = $usuarios->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <!-- Avatar con inicial del nombre -->
                                    <div class="avatar">
                                        <?php echo strtoupper(substr($u['nombre'], 0, 1)); ?>
                                    </div>
                                    <span class="fw-semibold">
                                        <?php echo htmlspecialchars($u['nombre']); ?>
                                    </span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td>
                                <?php
                                // Badge de color diferente según el rol
                                $colores_rol = [
                                    'Administrador' => 'bg-primary',
                                    'Vendedor'      => 'bg-success',
                                    'Bodeguero'     => 'bg-warning text-dark'
                                ];
                                $color = $colores_rol[$u['rol_nombre']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?php echo $color; ?> px-2 py-1">
                                    <?php echo htmlspecialchars($u['rol_nombre']); ?>
                                </span>
                            </td>
                            <td style="font-size:13px;" class="text-muted">
                                <?php echo date('d/m/Y', strtotime($u['created_at'])); ?>
                            </td>
                            <td>
                                <?php if($u['activo']): ?>
                                <span class="badge bg-success-subtle text-success px-2 py-1">
                                    <i class="bi bi-check-circle me-1"></i>Activo
                                </span>
                                <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger px-2 py-1">
                                    <i class="bi bi-x-circle me-1"></i>Inactivo
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="editar.php?id=<?php echo $u['id']; ?>"
                                   class="btn btn-sm btn-outline-primary me-1"
                                   title="Editar usuario">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php
                                // No permitimos desactivar el propio usuario logueado
                                if($u['id'] != $_SESSION['usuario']):
                                ?>
                                <button onclick="confirmar(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['nombre']); ?>', <?php echo $u['activo']; ?>)"
                                        class="btn btn-sm <?php echo $u['activo'] ? 'btn-outline-danger' : 'btn-outline-success'; ?>"
                                        title="<?php echo $u['activo'] ? 'Desactivar' : 'Activar'; ?>">
                                    <i class="bi bi-<?php echo $u['activo'] ? 'person-x' : 'person-check'; ?>"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function confirmar(id, nombre, activo) {
    const accion = activo ? 'desactivar' : 'activar';
    if(confirm(`¿Estás seguro de ${accion} al usuario "${nombre}"?`)) {
        window.location.href = `toggle_estado.php?id=${id}`;
    }
}
</script>
</body>
</html>