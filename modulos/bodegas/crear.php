<?php
/**
 * ARCHIVO: crear.php — Bodegas
 * FUNCIÓN: Formulario para registrar una nueva bodega.
 * AUTOR: TechnoSolutions Colombia - SENA ADSO
 */

session_start();
if(!isset($_SESSION['usuario']) || $_SESSION['rol'] != 1){
    header('Location: ../dashboard.php'); exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Bodega — TechnoSolutions</title>
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
            text-transform: uppercase; color: rgba(255,255,255,0.4); letter-spacing: 1.5px; margin-top: 8px;
        }
        .main-content { margin-left: 260px; }
        .topbar {
            background: white; padding: 16px 28px; border-bottom: 1px solid #e2e8f0;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 99; box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .form-card { background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
    </style>
</head>
<body>
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
        <div class="sidebar-section">Administración</div>
        <a href="../usuarios/lista.php" class="sidebar-link"><i class="bi bi-people"></i> Usuarios</a>
        <a href="lista.php" class="sidebar-link active"><i class="bi bi-building"></i> Bodegas</a>
        <div class="sidebar-section">Sesión</div>
        <a href="../auth/cerrar_sesion.php" class="sidebar-link"><i class="bi bi-box-arrow-left"></i> Cerrar Sesión</a>
    </nav>
</div>

<div class="main-content">
    <div class="topbar">
        <div>
            <h5 class="mb-0 fw-bold">Nueva Bodega</h5>
            <small class="text-muted">Registrar ubicación de inventario</small>
        </div>
        <a href="lista.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>
    <div class="p-4">
        <div class="row justify-content-center">
            <div class="col-xl-6">
                <div class="form-card p-4">
                    <h6 class="fw-bold text-primary mb-4 pb-2 border-bottom">
                        <i class="bi bi-building me-2"></i>Datos de la Bodega
                    </h6>
                    <form action="procesar_bodega.php" method="POST">
                        <input type="hidden" name="accion" value="crear">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Nombre de la Bodega <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nombre" class="form-control"
                                   placeholder="Ej: Bodega Centro" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Dirección</label>
                            <input type="text" name="direccion" class="form-control"
                                   placeholder="Ej: Calle 10 # 5-20, Bogotá">
                        </div>
                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="bi bi-check-lg me-2"></i>Guardar Bodega
                            </button>
                            <a href="lista.php" class="btn btn-outline-secondary px-4">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>