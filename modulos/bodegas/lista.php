<?php
/**
 * ARCHIVO: lista.php
 * UBICACIÓN: /technosolutions/modulos/bodegas/lista.php
 * FUNCIÓN: Muestra y gestiona las bodegas de la empresa.
 * AUTOR: TechnoSolutions Colombia - SENA ADSO
 */

session_start();

if(!isset($_SESSION['usuario']) || $_SESSION['rol'] != 1){
    header('Location: ../dashboard.php');
    exit();
}

require_once '../../config/conexion.php';

// Traemos las bodegas con la cantidad de productos que tienen
$sql = "SELECT b.*,
               COUNT(DISTINCT sb.id_producto) as total_productos,
               COALESCE(SUM(sb.cantidad), 0) as total_unidades
        FROM bodegas b
        LEFT JOIN stock_bodega sb ON b.id = sb.id_bodega
        GROUP BY b.id
        ORDER BY b.activa DESC, b.nombre ASC";
$bodegas = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bodegas — TechnoSolutions</title>
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
        /* Tarjetas de bodega en lugar de tabla — más visual */
        .bodega-card {
            background: white; border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            padding: 24px; border: 2px solid transparent;
            transition: all 0.2s;
        }
        .bodega-card:hover {
            border-color: #2E75B6;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(46,117,182,0.15);
        }
        .bodega-card.inactiva {
            opacity: 0.6;
            border-color: #dee2e6;
        }
        .bodega-icon {
            width: 56px; height: 56px; border-radius: 14px;
            background: linear-gradient(135deg, #1F3864, #2E75B6);
            display: flex; align-items: center; justify-content: center;
            font-size: 26px; color: white; margin-bottom: 16px;
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
        <a href="../usuarios/lista.php" class="sidebar-link"><i class="bi bi-people"></i> Usuarios</a>
        <a href="lista.php" class="sidebar-link active"><i class="bi bi-building"></i> Bodegas</a>
        <div class="sidebar-section">Sesión</div>
        <a href="../auth/cerrar_sesion.php" class="sidebar-link"><i class="bi bi-box-arrow-left"></i> Cerrar Sesión</a>
    </nav>
</div>

<!-- CONTENIDO PRINCIPAL -->
<div class="main-content">
    <div class="topbar">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Gestión de Bodegas</h5>
            <small class="text-muted">Administrar ubicaciones de inventario</small>
        </div>
        <a href="crear.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>Nueva Bodega
        </a>
    </div>

    <div class="p-4">

        <?php if(isset($_GET['exito'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php
                if($_GET['exito'] == 'creada')  echo 'Bodega creada exitosamente.';
                if($_GET['exito'] == 'editada') echo 'Bodega actualizada exitosamente.';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Mostramos las bodegas como tarjetas -->
        <div class="row g-4">
        <?php while($b = $bodegas->fetch_assoc()): ?>
            <div class="col-md-4">
                <div class="bodega-card <?php echo !$b['activa'] ? 'inactiva' : ''; ?>">
                    <div class="bodega-icon">
                        <i class="bi bi-building"></i>
                    </div>
                    <h5 class="fw-bold mb-1">
                        <?php echo htmlspecialchars($b['nombre']); ?>
                    </h5>
                    <p class="text-muted small mb-3">
                        <i class="bi bi-geo-alt me-1"></i>
                        <?php echo htmlspecialchars($b['direccion'] ?? 'Sin dirección'); ?>
                    </p>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="bg-light rounded p-2 text-center">
                                <div class="fw-bold fs-5 text-primary">
                                    <?php echo $b['total_productos']; ?>
                                </div>
                                <small class="text-muted">Productos</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded p-2 text-center">
                                <div class="fw-bold fs-5 text-success">
                                    <?php echo $b['total_unidades']; ?>
                                </div>
                                <small class="text-muted">Unidades</small>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="badge <?php echo $b['activa'] ? 'bg-success' : 'bg-secondary'; ?>">
                            <?php echo $b['activa'] ? 'Activa' : 'Inactiva'; ?>
                        </span>
                        <a href="editar.php?id=<?php echo $b['id']; ?>"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil me-1"></i>Editar
                        </a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
        </div>
    </div>
</div>

<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>