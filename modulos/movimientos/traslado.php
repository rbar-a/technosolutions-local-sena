<?php
/**
 * ARCHIVO: traslado.php
 * UBICACIÓN: /technosolutions/modulos/movimientos/traslado.php
 * FUNCIÓN: Registra traslados de productos entre las 3 bodegas.
 *          Verifica que origen y destino sean diferentes.
 *          Verifica stock suficiente en la bodega origen.
 * AUTOR: TechnoSolutions Colombia - SENA ADSO
 */

session_start();

if(!isset($_SESSION['usuario'])){
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';

$bodegas = $conexion->query(
    "SELECT * FROM bodegas WHERE activa = 1 ORDER BY nombre"
);
// Guardamos en array para poder usarlo dos veces (origen y destino)
$bodegas_array = [];
while($b = $bodegas->fetch_assoc()){
    $bodegas_array[] = $b;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traslado entre Bodegas — TechnoSolutions</title>
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
        .form-card { background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        .producto-info {
            background: #f0f4ff;
            border: 2px solid #6c6cff;
            border-radius: 12px;
            padding: 16px 20px;
            display: none;
        }
        /* Flecha visual entre bodega origen y destino */
        .flecha-traslado {
            display: flex; align-items: center; justify-content: center;
            font-size: 28px; color: #6c6cff; padding: 8px 0;
        }
        .codigo-input {
            font-family: 'Courier New', monospace;
            font-size: 16px; font-weight: 600;
            border: 2px solid #6c6cff; letter-spacing: 2px;
        }
        .codigo-input:focus {
            border-color: #4a4aff;
            box-shadow: 0 0 0 3px rgba(108,108,255,0.2);
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
        <a href="entrada.php" class="sidebar-link"><i class="bi bi-box-arrow-in-down"></i> Entrada de Mercancía</a>
        <a href="salida.php" class="sidebar-link"><i class="bi bi-box-arrow-up"></i> Salida / Venta</a>
        <a href="traslado.php" class="sidebar-link active"><i class="bi bi-arrow-left-right"></i> Traslado entre Bodegas</a>
        <a href="historial.php" class="sidebar-link"><i class="bi bi-clock-history"></i> Historial</a>
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
            <h5 class="mb-0 fw-bold text-dark">Traslado entre Bodegas</h5>
            <small class="text-muted">Mover productos de una bodega a otra</small>
        </div>
        <a href="historial.php" class="btn btn-outline-secondary">
            <i class="bi bi-clock-history me-1"></i> Ver Historial
        </a>
    </div>

    <div class="p-4">
        <div class="row justify-content-center">
            <div class="col-xl-7">

                <?php if(isset($_GET['exito'])): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Traslado registrado exitosamente. Stock actualizado en ambas bodegas.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php
                        if($_GET['error'] == 'stock_insuficiente')
                            echo 'Stock insuficiente en la bodega origen.';
                        if($_GET['error'] == 'misma_bodega')
                            echo 'La bodega origen y destino no pueden ser la misma.';
                        if($_GET['error'] == 'campos_vacios')
                            echo 'Completa todos los campos obligatorios.';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="form-card p-4">
                    <h6 class="fw-bold mb-4 pb-2 border-bottom" style="color:#6c6cff;">
                        <i class="bi bi-arrow-left-right me-2"></i>
                        Registrar Traslado entre Bodegas
                    </h6>

                    <form action="procesar_movimiento.php" method="POST">
                        <input type="hidden" name="tipo" value="traslado">

                        <!-- Paso 1: Producto -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <span class="badge bg-primary me-2">1</span>
                                Código del Producto
                            </label>
                            <div class="input-group">
                                <span class="input-group-text text-white" style="background:#6c6cff;">
                                    <i class="bi bi-upc-scan"></i>
                                </span>
                                <input type="text"
                                       id="codigoBuscar"
                                       class="form-control codigo-input"
                                       placeholder="Escanea o escribe el código..."
                                       autocomplete="off"
                                       autofocus>
                                <button type="button"
                                        class="btn text-white"
                                        style="background:#6c6cff;"
                                        onclick="buscarProducto()">
                                    <i class="bi bi-search"></i> Buscar
                                </button>
                            </div>
                        </div>

                        <!-- Tarjeta producto encontrado -->
                        <div class="producto-info mb-4" id="productoInfo">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-check-circle-fill me-2" style="font-size:20px;color:#6c6cff;"></i>
                                <strong>Producto encontrado:</strong>
                            </div>
                            <div id="productoNombre" class="fw-bold fs-5"></div>
                            <div id="productoCodigo" class="text-muted small mb-2"></div>
                            <div id="stockPorBodega"></div>
                            <input type="hidden" name="id_producto" id="idProducto">
                        </div>

                        <!-- Paso 2: Bodegas con flecha visual -->
                        <div class="row align-items-center mb-3">
                            <div class="col-5">
                                <label class="form-label fw-semibold">
                                    <span class="badge bg-danger me-1">2</span>
                                    Bodega Origen
                                </label>
                                <select name="id_bodega_origen" class="form-select" required>
                                    <option value="">-- Origen --</option>
                                    <?php foreach($bodegas_array as $b): ?>
                                    <option value="<?php echo $b['id']; ?>">
                                        <?php echo htmlspecialchars($b['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Flecha visual que indica la dirección del traslado -->
                            <div class="col-2 flecha-traslado">
                                <i class="bi bi-arrow-right"></i>
                            </div>
                            <div class="col-5">
                                <label class="form-label fw-semibold">
                                    <span class="badge bg-success me-1">3</span>
                                    Bodega Destino
                                </label>
                                <select name="id_bodega_destino" class="form-select" required>
                                    <option value="">-- Destino --</option>
                                    <?php foreach($bodegas_array as $b): ?>
                                    <option value="<?php echo $b['id']; ?>">
                                        <?php echo htmlspecialchars($b['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Paso 4: Cantidad -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <span class="badge bg-primary me-2">4</span>
                                Cantidad a Trasladar
                            </label>
                            <input type="number"
                                   name="cantidad"
                                   class="form-control form-control-lg"
                                   placeholder="0" min="1" required>
                        </div>

                        <!-- Paso 5: Observación -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <span class="badge bg-secondary me-2">5</span>
                                Observación <span class="text-muted fw-normal">(opcional)</span>
                            </label>
                            <input type="text"
                                   name="observacion"
                                   class="form-control"
                                   placeholder="Motivo del traslado...">
                        </div>

                        <button type="submit"
                                id="btnGuardar"
                                class="btn w-100 py-3 text-white"
                                style="background:#6c6cff;"
                                disabled>
                            <i class="bi bi-check-lg me-2"></i>
                            Confirmar Traslado
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function buscarProducto() {
    const codigo = document.getElementById('codigoBuscar').value.trim();
    if(codigo === '') return;

    fetch('buscar_producto.php?codigo=' + encodeURIComponent(codigo) + '&stock_por_bodega=1')
        .then(r => r.json())
        .then(data => {
            if(data.encontrado) {
                document.getElementById('productoNombre').textContent = data.nombre;
                document.getElementById('productoCodigo').textContent = 'Código: ' + data.codigo_barras;
                document.getElementById('idProducto').value = data.id;

                let html = '<div class="mt-1"><small class="text-muted fw-bold">Stock por bodega:</small></div>';
                if(data.stock_bodegas){
                    data.stock_bodegas.forEach(function(b) {
                        const color = b.cantidad > 0 ? 'text-success' : 'text-danger';
                        html += `<div class="d-flex justify-content-between align-items-center
                                      bg-light rounded px-3 py-2 mt-1">
                                    <span><i class="bi bi-building me-2 text-muted"></i>${b.nombre}</span>
                                    <span class="fw-bold ${color}">${b.cantidad} uds</span>
                                 </div>`;
                    });
                }
                document.getElementById('stockPorBodega').innerHTML = html;
                document.getElementById('productoInfo').style.display = 'block';
                document.getElementById('btnGuardar').disabled = false;
            } else {
                document.getElementById('productoInfo').style.display = 'none';
                document.getElementById('btnGuardar').disabled = true;
                alert('Producto no encontrado: ' + codigo);
            }
        });
}

document.getElementById('codigoBuscar').addEventListener('keydown', function(e) {
    if(e.key === 'Enter') { e.preventDefault(); buscarProducto(); }
});
</script>
</body>
</html>