<?php
/**
 * ARCHIVO: salida.php
 * UBICACIÓN: /technosolutions/modulos/movimientos/salida.php
 * FUNCIÓN: Registra la salida de productos por venta.
 *          Verifica stock disponible antes de permitir la operación.
 *          Muestra el stock por bodega para que el vendedor sepa de dónde descontar.
 * AUTOR: TechnoSolutions Colombia - SENA ADSO
 */

session_start();

if(!isset($_SESSION['usuario'])){
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';

// Traemos bodegas para el selector de origen
$bodegas = $conexion->query(
    "SELECT * FROM bodegas WHERE activa = 1 ORDER BY nombre"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salida / Venta — TechnoSolutions</title>
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
        /* Tarjeta de producto con color rojo/naranja para indicar que es una salida */
        .producto-info {
            background: #fef3f0;
            border: 2px solid #dc3545;
            border-radius: 12px;
            padding: 16px 20px;
            display: none;
        }
        /* Stock por bodega — tabla compacta */
        .stock-bodega-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .codigo-input {
            font-family: 'Courier New', monospace;
            font-size: 16px; font-weight: 600;
            border: 2px solid #dc3545; letter-spacing: 2px;
        }
        .codigo-input:focus {
            border-color: #b02a37;
            box-shadow: 0 0 0 3px rgba(220,53,69,0.2);
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
        <a href="salida.php" class="sidebar-link active"><i class="bi bi-box-arrow-up"></i> Salida / Venta</a>
        <a href="traslado.php" class="sidebar-link"><i class="bi bi-arrow-left-right"></i> Traslado entre Bodegas</a>
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
            <h5 class="mb-0 fw-bold text-dark">Salida / Venta</h5>
            <small class="text-muted">Registrar salida de productos por venta</small>
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
                    Venta registrada exitosamente. Stock descontado.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php
                        if($_GET['error'] == 'stock_insuficiente')
                            echo 'Stock insuficiente en esa bodega para la cantidad solicitada.';
                        if($_GET['error'] == 'campos_vacios')
                            echo 'Completa todos los campos obligatorios.';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="form-card p-4">
                    <h6 class="fw-bold text-danger mb-4 pb-2 border-bottom">
                        <i class="bi bi-box-arrow-up me-2"></i>
                        Registrar Salida por Venta
                    </h6>

                    <form action="procesar_movimiento.php" method="POST">
                        <input type="hidden" name="tipo" value="salida">

                        <!-- Paso 1: Escanear -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <span class="badge bg-danger me-2">1</span>
                                Código del Producto
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-danger text-white">
                                    <i class="bi bi-upc-scan"></i>
                                </span>
                                <input type="text"
                                       id="codigoBuscar"
                                       class="form-control codigo-input"
                                       placeholder="Escanea o escribe el código..."
                                       autocomplete="off"
                                       autofocus>
                                <button type="button"
                                        class="btn btn-danger"
                                        onclick="buscarProducto()">
                                    <i class="bi bi-search"></i> Buscar
                                </button>
                            </div>
                        </div>

                        <!-- Tarjeta del producto encontrado -->
                        <div class="producto-info mb-3" id="productoInfo">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-check-circle-fill text-danger me-2"
                                   style="font-size:20px;"></i>
                                <strong>Producto encontrado:</strong>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <div id="productoNombre" class="fw-bold fs-5"></div>
                                    <div id="productoCodigo" class="text-muted small"></div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="text-muted small">Stock total</div>
                                    <div id="productoStock" class="fw-bold fs-4 text-danger"></div>
                                </div>
                            </div>
                            <!--
                                STOCK POR BODEGA:
                                Se llena dinámicamente con JavaScript al buscar el producto.
                                Muestra cuántas unidades hay en cada bodega para que
                                el vendedor sepa de cuál bodega descontar.
                            -->
                            <div id="stockPorBodega"></div>
                            <input type="hidden" name="id_producto" id="idProducto">
                        </div>

                        <!-- Paso 2: Bodega origen -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <span class="badge bg-danger me-2">2</span>
                                Bodega de Origen (de dónde se descuenta)
                            </label>
                            <select name="id_bodega_origen" class="form-select" required>
                                <option value="">-- Selecciona la bodega --</option>
                                <?php while($b = $bodegas->fetch_assoc()): ?>
                                <option value="<?php echo $b['id']; ?>">
                                    <?php echo htmlspecialchars($b['nombre']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Paso 3: Cantidad -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <span class="badge bg-danger me-2">3</span>
                                Cantidad Vendida
                            </label>
                            <input type="number"
                                   name="cantidad"
                                   class="form-control form-control-lg"
                                   placeholder="0" min="1" required>
                        </div>

                        <!-- Paso 4: Observación -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <span class="badge bg-secondary me-2">4</span>
                                Observación <span class="text-muted fw-normal">(opcional)</span>
                            </label>
                            <input type="text"
                                   name="observacion"
                                   class="form-control"
                                   placeholder="Ej: Venta a cliente Juan Pérez...">
                        </div>

                        <button type="submit"
                                id="btnGuardar"
                                class="btn btn-danger w-100 py-3"
                                disabled>
                            <i class="bi bi-check-lg me-2"></i>
                            Registrar Venta
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

    // Usamos la misma API que entrada.php pero con parámetro adicional
    // stock_por_bodega=1 le indica a la API que incluya el detalle por bodega
    fetch('buscar_producto.php?codigo=' + encodeURIComponent(codigo) + '&stock_por_bodega=1')
        .then(r => r.json())
        .then(data => {
            if(data.encontrado) {
                document.getElementById('productoNombre').textContent = data.nombre;
                document.getElementById('productoCodigo').textContent = 'Código: ' + data.codigo_barras;
                document.getElementById('productoStock').textContent  = data.stock_total + ' uds';
                document.getElementById('idProducto').value = data.id;

                // Construimos la tabla de stock por bodega dinámicamente
                let htmlBodegas = '<div class="mt-2"><small class="text-muted fw-bold">Stock disponible por bodega:</small></div>';
                if(data.stock_bodegas && data.stock_bodegas.length > 0){
                    data.stock_bodegas.forEach(function(b) {
                        // Color verde si hay stock, rojo si no hay
                        const colorClass = b.cantidad > 0 ? 'text-success' : 'text-danger';
                        htmlBodegas += `
                            <div class="stock-bodega-item">
                                <span><i class="bi bi-building me-2 text-muted"></i>${b.nombre}</span>
                                <span class="fw-bold ${colorClass}">${b.cantidad} uds</span>
                            </div>`;
                    });
                }
                document.getElementById('stockPorBodega').innerHTML = htmlBodegas;
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