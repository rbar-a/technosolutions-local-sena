<?php
/**
 * ARCHIVO: entrada.php
 * UBICACIÓN: /technosolutions/modulos/movimientos/entrada.php
 * FUNCIÓN: Registra el ingreso de mercancía a una bodega.
 *          El campo de código acepta la pistola lectora.
 *          Al escanear, busca el producto automáticamente vía JavaScript.
 * AUTOR: TechnoSolutions Colombia - SENA ADSO
 */

session_start();

if(!isset($_SESSION['usuario'])){
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';

// Traemos las bodegas activas para el selector
$bodegas = $conexion->query(
    "SELECT * FROM bodegas WHERE activa = 1 ORDER BY nombre"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrada de Mercancía — TechnoSolutions</title>
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
        .form-card {
            background: white; border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        /* Tarjeta de información del producto encontrado */
        .producto-info {
            background: #e8f4fd;
            border: 2px solid #2E75B6;
            border-radius: 12px;
            padding: 16px 20px;
            display: none; /* Oculta por defecto, se muestra con JavaScript */
        }
        .codigo-input {
            font-family: 'Courier New', monospace;
            font-size: 16px; font-weight: 600;
            border: 2px solid #2E75B6; letter-spacing: 2px;
        }
        .codigo-input:focus {
            border-color: #1F3864;
            box-shadow: 0 0 0 3px rgba(46,117,182,0.2);
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
        <a href="entrada.php" class="sidebar-link active"><i class="bi bi-box-arrow-in-down"></i> Entrada de Mercancía</a>
        <a href="salida.php" class="sidebar-link"><i class="bi bi-box-arrow-up"></i> Salida / Venta</a>
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
            <h5 class="mb-0 fw-bold text-dark">Entrada de Mercancía</h5>
            <small class="text-muted">Registrar ingreso de productos a bodega</small>
        </div>
        <a href="historial.php" class="btn btn-outline-secondary">
            <i class="bi bi-clock-history me-1"></i> Ver Historial
        </a>
    </div>

    <div class="p-4">
        <div class="row justify-content-center">
            <div class="col-xl-7">

                <!-- Mensaje de éxito -->
                <?php if(isset($_GET['exito'])): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Entrada registrada exitosamente. Stock actualizado.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Mensaje de error -->
                <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php
                        if($_GET['error'] == 'stock_insuficiente')
                            echo 'Stock insuficiente para realizar la operación.';
                        if($_GET['error'] == 'producto_no_encontrado')
                            echo 'Producto no encontrado. Verifica el código.';
                        if($_GET['error'] == 'campos_vacios')
                            echo 'Completa todos los campos obligatorios.';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="form-card p-4">
                    <h6 class="fw-bold text-success mb-4 pb-2 border-bottom">
                        <i class="bi bi-box-arrow-in-down me-2"></i>
                        Registrar Entrada de Mercancía
                    </h6>

                    <form action="procesar_movimiento.php" method="POST">
                        <!-- Indica el tipo de movimiento -->
                        <input type="hidden" name="tipo" value="entrada">

                        <!-- ── PASO 1: Escanear producto ──────────────────── -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <span class="badge bg-primary me-2">1</span>
                                Código del Producto
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-success text-white">
                                    <i class="bi bi-upc-scan"></i>
                                </span>
                                <!--
                                    Campo de escaneo:
                                    - onchange: cuando cambia el valor (al escanear),
                                      llama buscarProducto() automáticamente
                                    - El Enter de la pistola activa el onchange
                                -->
                                <input type="text"
                                       id="codigoBuscar"
                                       class="form-control codigo-input"
                                       placeholder="Escanea o escribe el código..."
                                       autocomplete="off"
                                       autofocus>
                                <button type="button"
                                        class="btn btn-success"
                                        onclick="buscarProducto()">
                                    <i class="bi bi-search"></i> Buscar
                                </button>
                            </div>
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Escanea con la pistola o escribe el código y presiona Buscar.
                            </small>
                        </div>

                        <!--
                            TARJETA DE PRODUCTO ENCONTRADO:
                            Se muestra con JavaScript cuando se encuentra el producto.
                            Contiene campos ocultos con el ID del producto.
                        -->
                        <div class="producto-info mb-4" id="productoInfo">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-check-circle-fill text-success me-2" 
                                   style="font-size:20px;"></i>
                                <strong>Producto encontrado:</strong>
                            </div>
                            <div class="row">
                                <div class="col-md-8">
                                    <!-- Aquí JavaScript escribe el nombre del producto -->
                                    <div id="productoNombre" class="fw-bold fs-5"></div>
                                    <div id="productoCodigo" class="text-muted small"></div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="text-muted small">Stock actual</div>
                                    <!-- Aquí JavaScript escribe el stock actual -->
                                    <div id="productoStock" class="fw-bold fs-4 text-primary"></div>
                                </div>
                            </div>
                            <!-- Campo oculto que guarda el ID del producto encontrado -->
                            <!-- Este ID es el que se envía al formulario PHP -->
                            <input type="hidden" name="id_producto" id="idProducto">
                        </div>

                        <!-- ── PASO 2: Seleccionar bodega ─────────────────── -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <span class="badge bg-primary me-2">2</span>
                                Bodega de Destino
                            </label>
                            <select name="id_bodega_destino" class="form-select" required>
                                <option value="">-- Selecciona la bodega --</option>
                                <?php while($b = $bodegas->fetch_assoc()): ?>
                                <option value="<?php echo $b['id']; ?>">
                                    <?php echo htmlspecialchars($b['nombre']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- ── PASO 3: Cantidad ───────────────────────────── -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <span class="badge bg-primary me-2">3</span>
                                Cantidad a Ingresar
                            </label>
                            <input type="number"
                                   name="cantidad"
                                   class="form-control form-control-lg"
                                   placeholder="0"
                                   min="1"
                                   required>
                        </div>

                        <!-- ── PASO 4: Observación opcional ──────────────── -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <span class="badge bg-secondary me-2">4</span>
                                Observación <span class="text-muted fw-normal">(opcional)</span>
                            </label>
                            <input type="text"
                                   name="observacion"
                                   class="form-control"
                                   placeholder="Ej: Compra a proveedor X, factura 001...">
                        </div>

                        <button type="submit"
                                id="btnGuardar"
                                class="btn btn-success w-100 py-3"
                                disabled>
                            <!-- Deshabilitado hasta que se encuentre un producto -->
                            <i class="bi bi-check-lg me-2"></i>
                            Registrar Entrada
                        </button>
                        <small class="text-muted d-block text-center mt-2">
                            El stock se actualizará inmediatamente al confirmar.
                        </small>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
/**
 * FUNCIÓN: buscarProducto()
 * QUÉ HACE: Llama a la API PHP (buscar_producto.php) con el código escaneado.
 *           Si lo encuentra, muestra la tarjeta de información y habilita el botón.
 *           Usa fetch() — la forma moderna de hacer peticiones HTTP desde JavaScript.
 */
function buscarProducto() {
    // Obtenemos el valor del campo de código
    const codigo = document.getElementById('codigoBuscar').value.trim();

    // Si el campo está vacío, no hacemos nada
    if(codigo === '') return;

    // fetch() hace una petición HTTP al servidor SIN recargar la página
    // Esto se llama AJAX (Asynchronous JavaScript And XML)
    fetch('buscar_producto.php?codigo=' + encodeURIComponent(codigo))
        .then(function(response) {
            // response.json() convierte la respuesta del servidor a objeto JavaScript
            return response.json();
        })
        .then(function(data) {
            if(data.encontrado) {
                // Producto encontrado: llenamos la tarjeta con los datos
                document.getElementById('productoNombre').textContent = data.nombre;
                document.getElementById('productoCodigo').textContent = 'Código: ' + data.codigo_barras;
                document.getElementById('productoStock').textContent  = data.stock_total + ' uds';

                // Guardamos el ID en el campo oculto para enviarlo al formulario
                document.getElementById('idProducto').value = data.id;

                // Mostramos la tarjeta de información (era display:none)
                document.getElementById('productoInfo').style.display = 'block';

                // Habilitamos el botón de guardar
                document.getElementById('btnGuardar').disabled = false;

            } else {
                // Producto NO encontrado: ocultamos la tarjeta y deshabilitamos botón
                document.getElementById('productoInfo').style.display = 'none';
                document.getElementById('btnGuardar').disabled = true;
                alert('Producto no encontrado. Verifica el código: ' + codigo);
            }
        })
        .catch(function(error) {
            // Si hay error de red o del servidor
            console.error('Error al buscar producto:', error);
            alert('Error al conectar con el servidor.');
        });
}

/**
 * EVENTO: keydown en el campo de código
 * QUÉ HACE: Cuando la pistola lectora envía Enter después del código,
 *           ejecutamos la búsqueda automáticamente sin que el usuario
 *           tenga que hacer clic en "Buscar".
 */
document.getElementById('codigoBuscar').addEventListener('keydown', function(e) {
    if(e.key === 'Enter') {
        e.preventDefault(); // Evitamos que el formulario se envíe
        buscarProducto();   // Ejecutamos la búsqueda
    }
});
</script>
</body>
</html>