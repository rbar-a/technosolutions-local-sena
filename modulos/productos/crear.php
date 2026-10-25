<?php
/**
 * ARCHIVO: crear.php
 * UBICACIÓN: /technosolutions/modulos/productos/crear.php
 * FUNCIÓN: Formulario para registrar un nuevo producto.
 *          El campo de código de barras acepta input de la pistola lectora.
 *          Solo pueden crear productos los Administradores (rol 1).
 * AUTOR: TechnoSolutions Colombia - SENA ADSO
 */

session_start();

// Protección de página
if(!isset($_SESSION['usuario'])){
    header('Location: ../auth/login.php');
    exit();
}

// Solo el administrador puede crear productos
// Si el rol no es 1 (Administrador), redirige con mensaje de error
if($_SESSION['rol'] != 1){
    header('Location: lista.php?error=sin_permiso');
    exit();
}

require_once '../../config/conexion.php';

// Traemos las bodegas para el selector de stock inicial
$bodegas = $conexion->query("SELECT * FROM bodegas WHERE activa = 1 ORDER BY nombre");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Producto — TechnoSolutions</title>
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
            text-transform: uppercase; color: rgba(255,255,255,0.4); letter-spacing: 1.5px; margin-top: 8px;
        }
        .main-content { margin-left: 260px; }
        .topbar {
            background: white; padding: 16px 28px; border-bottom: 1px solid #e2e8f0;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 99; box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .form-card {
            background: white; border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06); border: none;
        }
        /* Campo de código con fondo especial para resaltar su importancia */
        .codigo-input {
            font-family: 'Courier New', monospace;
            font-size: 16px; font-weight: 600;
            border: 2px solid #2E75B6;
            letter-spacing: 2px;
        }
        .codigo-input:focus {
            border-color: #1F3864;
            box-shadow: 0 0 0 3px rgba(46,117,182,0.2);
        }
        .form-label { font-weight: 600; color: #333; font-size: 14px; }
        .required-mark { color: #dc3545; }
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
        <a href="lista.php" class="sidebar-link active"><i class="bi bi-box-seam"></i> Productos</a>
        <a href="../movimientos/entrada.php" class="sidebar-link"><i class="bi bi-box-arrow-in-down"></i> Entrada de Mercancía</a>
        <a href="../movimientos/salida.php" class="sidebar-link"><i class="bi bi-box-arrow-up"></i> Salida / Venta</a>
        <a href="../movimientos/traslado.php" class="sidebar-link"><i class="bi bi-arrow-left-right"></i> Traslado entre Bodegas</a>
        <a href="../movimientos/historial.php" class="sidebar-link"><i class="bi bi-clock-history"></i> Historial</a>
        <div class="sidebar-section">Reportes</div>
        <a href="../reportes/inventario_pdf.php" class="sidebar-link"><i class="bi bi-file-earmark-pdf"></i> Reporte PDF</a>
        <a href="../reportes/inventario_excel.php" class="sidebar-link"><i class="bi bi-file-earmark-excel"></i> Exportar Excel</a>
        <?php if($_SESSION['rol'] == 1): ?>
        <div class="sidebar-section">Administración</div>
        <a href="../usuarios/lista.php" class="sidebar-link"><i class="bi bi-people"></i> Usuarios</a>
        <a href="../bodegas/lista.php" class="sidebar-link"><i class="bi bi-building"></i> Bodegas</a>
        <?php endif; ?>
        <div class="sidebar-section">Sesión</div>
        <a href="../auth/cerrar_sesion.php" class="sidebar-link"><i class="bi bi-box-arrow-left"></i> Cerrar Sesión</a>
    </nav>
</div>

<!-- CONTENIDO PRINCIPAL -->
<div class="main-content">
    <div class="topbar">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Nuevo Producto</h5>
            <small class="text-muted">Registrar producto en el inventario</small>
        </div>
        <!-- Botón para volver al listado sin guardar -->
        <a href="lista.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver al listado
        </a>
    </div>

    <div class="p-4">
        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="form-card p-4">

                    <!-- Alerta de error si la validación falla -->
                    <?php if(isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php
                            if($_GET['error'] == 'codigo_duplicado')
                                echo 'El código de barras ya está registrado. Usa uno diferente.';
                            if($_GET['error'] == 'campos_vacios')
                                echo 'Por favor completa todos los campos obligatorios.';
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!--
                        FORMULARIO DE CREACIÓN DE PRODUCTO
                        action="procesar.php": los datos van a procesar.php
                        method="POST": datos viajan de forma segura
                    -->
                    <form action="procesar.php" method="POST">
                        <!-- Campo oculto que indica la acción a realizar -->
                        <!-- procesar.php usa este campo para saber si es crear o editar -->
                        <input type="hidden" name="accion" value="crear">

                        <!-- ── SECCIÓN 1: Identificación ───────────────────── -->
                        <h6 class="fw-bold text-primary mb-3 pb-2 border-bottom">
                            <i class="bi bi-upc-scan me-2"></i>Identificación del Producto
                        </h6>

                        <div class="mb-3">
                            <label class="form-label">
                                Código de Barras <span class="required-mark">*</span>
                            </label>
                            <!--
                                CAMPO PRINCIPAL DE ESCANEO:
                                - autofocus: el cursor llega aquí al cargar la página
                                - La pistola lectora escribe el código y presiona Enter
                                - pattern valida que solo tenga letras y números
                            -->
                            <div class="input-group">
                                <span class="input-group-text bg-primary text-white">
                                    <i class="bi bi-upc-scan"></i>
                                </span>
                                <input type="text"
                                       name="codigo_barras"
                                       id="codigoBarras"
                                       class="form-control codigo-input"
                                       placeholder="Escanea o escribe el código..."
                                       required
                                       autofocus
                                       autocomplete="off"
                                       maxlength="50">
                            </div>
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Coloca el cursor aquí y escanea con la pistola lectora.
                            </small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">
                                Nombre del Producto <span class="required-mark">*</span>
                            </label>
                            <input type="text"
                                   name="nombre"
                                   class="form-control"
                                   placeholder="Ej: Laptop HP ProBook 450 G9"
                                   required
                                   maxlength="150">
                        </div>

                        <!-- ── SECCIÓN 2: Detalles ─────────────────────────── -->
                        <h6 class="fw-bold text-primary mb-3 pb-2 border-bottom mt-4">
                            <i class="bi bi-card-text me-2"></i>Detalles del Producto
                        </h6>

                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion"
                                      class="form-control"
                                      rows="3"
                                      placeholder="Características, especificaciones técnicas, etc."></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Precio de Venta <span class="required-mark">*</span>
                                </label>
                                <div class="input-group">
                                    <!-- Símbolo $ antes del campo -->
                                    <span class="input-group-text">$</span>
                                    <input type="number"
                                           name="precio"
                                           class="form-control"
                                           placeholder="0"
                                           min="0"
                                           step="1"
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Stock Mínimo <span class="required-mark">*</span>
                                </label>
                                <!--
                                    Stock mínimo: cuando el stock caiga por debajo
                                    de este número, el sistema generará una alerta.
                                -->
                                <input type="number"
                                       name="stock_minimo"
                                       class="form-control"
                                       placeholder="5"
                                       min="0"
                                       value="5"
                                       required>
                                <small class="text-muted">
                                    El sistema alertará cuando el stock llegue a este nivel.
                                </small>
                            </div>
                        </div>

                        <!-- ── SECCIÓN 3: Stock inicial ───────────────────── -->
                        <h6 class="fw-bold text-primary mb-3 pb-2 border-bottom mt-4">
                            <i class="bi bi-building me-2"></i>Stock Inicial por Bodega
                        </h6>
                        <p class="text-muted small mb-3">
                            Ingresa cuántas unidades hay actualmente en cada bodega.
                            Puedes dejar en 0 si aún no hay stock.
                        </p>

                        <div class="row">
                        <?php
                        // Generamos un campo de stock para cada bodega registrada
                        $bodegas->data_seek(0); // Reiniciamos el puntero del resultado
                        while($bodega = $bodegas->fetch_assoc()):
                        ?>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-building me-1 text-primary"></i>
                                    <?php echo htmlspecialchars($bodega['nombre']); ?>
                                </label>
                                <!--
                                    name="stock[]" usa array para enviar múltiples valores.
                                    name="bodega_id[]" guarda el ID de cada bodega.
                                    En procesar.php los leemos con $_POST['stock']
                                    y $_POST['bodega_id'] como arrays.
                                -->
                                <input type="hidden"
                                       name="bodega_id[]"
                                       value="<?php echo $bodega['id']; ?>">
                                <input type="number"
                                       name="stock[]"
                                       class="form-control"
                                       placeholder="0"
                                       min="0"
                                       value="0">
                            </div>
                        <?php endwhile; ?>
                        </div>

                        <!-- ── BOTONES DE ACCIÓN ──────────────────────────── -->
                        <div class="d-flex gap-3 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="bi bi-check-lg me-2"></i>Guardar Producto
                            </button>
                            <a href="lista.php" class="btn btn-outline-secondary px-4">
                                <i class="bi bi-x-lg me-1"></i>Cancelar
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
/**
 * LÓGICA DE ESCANEO EN EL FORMULARIO:
 * Cuando la pistola lectora escanea un código, escribe el texto
 * y envía un Enter automáticamente. Aquí capturamos ese Enter
 * para saltar al siguiente campo (nombre) en lugar de enviar el formulario.
 */
document.getElementById('codigoBarras').addEventListener('keydown', function(e) {
    // Verificamos si la tecla presionada es Enter (código 13)
    if(e.key === 'Enter') {
        // preventDefault() evita que el formulario se envíe con solo el código
        e.preventDefault();
        // Movemos el foco al campo "nombre" para que el usuario complete la info
        document.querySelector('[name="nombre"]').focus();
    }
});
</script>
</body>
</html>