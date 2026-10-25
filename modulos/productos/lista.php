<?php
/**
 * ARCHIVO: lista.php
 * UBICACIÓN: /technosolutions/modulos/productos/lista.php
 * FUNCIÓN: Muestra la tabla con todos los productos registrados.
 *          Permite buscar, ver detalle, editar y desactivar productos.
 *          También es desde aquí donde se usa la pistola lectora para buscar.
 * AUTOR: TechnoSolutions Colombia - SENA ADSO
 */

session_start();

// Protección: si no hay sesión activa, redirige al login
if(!isset($_SESSION['usuario'])){
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';

// ── BÚSQUEDA DE PRODUCTOS ────────────────────────────────────────────────────
// Verificamos si el usuario envió un término de búsqueda (por teclado o pistola)
// $_GET['buscar'] viene de la URL: lista.php?buscar=CODIGO123
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// Construimos la consulta SQL según si hay búsqueda o no
if($buscar !== ''){
    // Si hay búsqueda, filtramos por código de barras O por nombre del producto
    // El % en LIKE significa "cualquier texto antes/después"
    // Ejemplo: LIKE '%laptop%' encuentra "Laptop HP", "Laptop Dell", etc.
    $termino = "%$buscar%";
    $sql = "SELECT p.*, 
                   COALESCE(SUM(sb.cantidad), 0) as stock_total
            FROM productos p
            LEFT JOIN stock_bodega sb ON p.id = sb.id_producto
            WHERE p.activo = 1 
            AND (p.codigo_barras LIKE ? OR p.nombre LIKE ?)
            GROUP BY p.id
            ORDER BY p.nombre ASC";
    $stmt = $conexion->prepare($sql);
    // "ss" = dos parámetros de tipo string
    $stmt->bind_param("ss", $termino, $termino);
} else {
    // Sin búsqueda: traemos TODOS los productos activos
    $sql = "SELECT p.*, 
                   COALESCE(SUM(sb.cantidad), 0) as stock_total
            FROM productos p
            LEFT JOIN stock_bodega sb ON p.id = sb.id_producto
            WHERE p.activo = 1
            GROUP BY p.id
            ORDER BY p.nombre ASC";
    $stmt = $conexion->prepare($sql);
}

$stmt->execute();
$productos = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos — TechnoSolutions</title>
    <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f0f4f8; }

        /* Barra lateral — mismos estilos que el dashboard */
        .sidebar {
            background: linear-gradient(180deg, #1F3864 0%, #2E75B6 100%);
            min-height: 100vh; width: 260px;
            position: fixed; top: 0; left: 0;
            z-index: 100; overflow-y: auto;
        }
        .sidebar-brand { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-link {
            display: flex; align-items: center;
            padding: 12px 20px; color: rgba(255,255,255,0.8);
            text-decoration: none; border-radius: 8px;
            margin: 4px 12px; transition: all 0.2s; font-size: 14px;
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
            display: flex; align-items: center;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 99;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        /* Tarjeta principal de contenido */
        .content-card {
            background: white; border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border: none;
        }

        /* Tabla de productos */
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

        /* Badge de estado del stock */
        .stock-ok      { color: #155724; background: #d4edda; }
        .stock-bajo    { color: #856404; background: #fff3cd; }
        .stock-critico { color: #721c24; background: #f8d7da; }

        /* Campo de búsqueda especial para pistola lectora */
        .search-scanner {
            border: 2px solid #2E75B6;
            border-radius: 10px;
            padding: 10px 16px;
            font-size: 15px;
            transition: all 0.2s;
        }
        .search-scanner:focus {
            border-color: #1F3864;
            box-shadow: 0 0 0 3px rgba(46,117,182,0.2);
            outline: none;
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
        <a href="../dashboard.php" class="sidebar-link">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <div class="sidebar-section">Inventario</div>
        <!-- 'active' marca la página actual en el menú -->
        <a href="lista.php" class="sidebar-link active">
            <i class="bi bi-box-seam"></i> Productos
        </a>
        <a href="../movimientos/entrada.php" class="sidebar-link">
            <i class="bi bi-box-arrow-in-down"></i> Entrada de Mercancía
        </a>
        <a href="../movimientos/salida.php" class="sidebar-link">
            <i class="bi bi-box-arrow-up"></i> Salida / Venta
        </a>
        <a href="../movimientos/traslado.php" class="sidebar-link">
            <i class="bi bi-arrow-left-right"></i> Traslado entre Bodegas
        </a>
        <a href="../movimientos/historial.php" class="sidebar-link">
            <i class="bi bi-clock-history"></i> Historial
        </a>
        <div class="sidebar-section">Reportes</div>
        <a href="../reportes/inventario_pdf.php" class="sidebar-link">
            <i class="bi bi-file-earmark-pdf"></i> Reporte PDF
        </a>
        <a href="../reportes/inventario_excel.php" class="sidebar-link">
            <i class="bi bi-file-earmark-excel"></i> Exportar Excel
        </a>
        <?php if($_SESSION['rol'] == 1): ?>
        <div class="sidebar-section">Administración</div>
        <a href="../usuarios/lista.php" class="sidebar-link">
            <i class="bi bi-people"></i> Usuarios
        </a>
        <a href="../bodegas/lista.php" class="sidebar-link">
            <i class="bi bi-building"></i> Bodegas
        </a>
        <?php endif; ?>
        <div class="sidebar-section">Sesión</div>
        <a href="../auth/cerrar_sesion.php" class="sidebar-link">
            <i class="bi bi-box-arrow-left"></i> Cerrar Sesión
        </a>
    </nav>
</div>

<!-- CONTENIDO PRINCIPAL -->
<div class="main-content">

    <!-- Barra superior -->
    <div class="topbar">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Productos</h5>
            <small class="text-muted">Gestión del catálogo de productos</small>
        </div>
        <!-- Botón para ir a crear un producto nuevo -->
        <a href="crear.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>Nuevo Producto
        </a>
    </div>

    <div class="p-4">

        <!-- ── BARRA DE BÚSQUEDA ──────────────────────────────────────────── -->
        <!--
            INTEGRACIÓN CON PISTOLA LECTORA:
            La pistola lectora funciona como un teclado: escribe el código
            y presiona Enter automáticamente. El formulario con method="GET"
            se envía solo al detectar el Enter, haciendo la búsqueda instantánea.
            El usuario solo necesita poner el cursor en este campo y escanear.
        -->
        <div class="content-card p-3 mb-4">
            <form method="GET" action="lista.php" class="d-flex gap-3 align-items-center">
                <div class="flex-grow-1 position-relative">
                    <i class="bi bi-upc-scan position-absolute text-muted"
                       style="left:14px; top:50%; transform:translateY(-50%); font-size:18px;"></i>
                    <input type="text"
                           name="buscar"
                           id="campoBusqueda"
                           class="form-control search-scanner ps-5"
                           placeholder="Escanea un código de barras o escribe el nombre del producto..."
                           value="<?php echo htmlspecialchars($buscar); ?>"
                           autocomplete="off">
                </div>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-search me-1"></i> Buscar
                </button>
                <?php if($buscar): ?>
                <!-- Botón para limpiar la búsqueda y ver todos los productos -->
                <a href="lista.php" class="btn btn-outline-secondary px-4">
                    <i class="bi bi-x-lg me-1"></i> Limpiar
                </a>
                <?php endif; ?>
            </form>
            <!-- Indicador de modo escaneo -->
            <small class="text-muted mt-2 d-block">
                <i class="bi bi-info-circle me-1"></i>
                Haz clic en el campo y escanea con la pistola lectora para búsqueda instantánea.
            </small>
        </div>

        <!-- Mensaje si se hizo una búsqueda -->
        <?php if($buscar): ?>
        <div class="alert alert-info mb-3">
            <i class="bi bi-search me-2"></i>
            Resultados para: <strong>"<?php echo htmlspecialchars($buscar); ?>"</strong>
            — <?php echo $productos->num_rows; ?> producto(s) encontrado(s)
        </div>
        <?php endif; ?>

        <!-- Mensaje de éxito al crear/editar producto -->
        <?php if(isset($_GET['exito'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-3">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php
                if($_GET['exito'] == 'creado')  echo 'Producto registrado exitosamente.';
                if($_GET['exito'] == 'editado') echo 'Producto actualizado exitosamente.';
                if($_GET['exito'] == 'eliminado') echo 'Producto desactivado exitosamente.';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- ── TABLA DE PRODUCTOS ─────────────────────────────────────────── -->
        <div class="content-card overflow-hidden">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Código de Barras</th>
                            <th>Nombre del Producto</th>
                            <th>Precio</th>
                            <th>Stock Total</th>
                            <th>Stock Mínimo</th>
                            <th>Estado Stock</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    // Verificamos si hay productos para mostrar
                    if($productos->num_rows > 0):
                        // fetch_assoc() trae una fila a la vez como array
                        while($p = $productos->fetch_assoc()):

                            // Determinamos el estado del stock para mostrar el color correcto
                            if($p['stock_total'] == 0){
                                $badge_class = 'stock-critico';
                                $badge_texto = 'Sin Stock';
                            } elseif($p['stock_total'] <= $p['stock_minimo']){
                                $badge_class = 'stock-bajo';
                                $badge_texto = 'Stock Bajo';
                            } else {
                                $badge_class = 'stock-ok';
                                $badge_texto = 'Normal';
                            }
                    ?>
                        <tr>
                            <!-- Código de barras en fuente monoespaciada para mejor lectura -->
                            <td>
                                <span class="badge bg-light text-dark border"
                                      style="font-family:monospace; font-size:13px; letter-spacing:1px;">
                                    <?php echo htmlspecialchars($p['codigo_barras']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold">
                                    <?php echo htmlspecialchars($p['nombre']); ?>
                                </div>
                                <?php if($p['descripcion']): ?>
                                <small class="text-muted">
                                    <!-- substr corta el texto a 60 caracteres para no ocupar mucho espacio -->
                                    <?php echo htmlspecialchars(substr($p['descripcion'], 0, 60));
                                    echo strlen($p['descripcion']) > 60 ? '...' : ''; ?>
                                </small>
                                <?php endif; ?>
                            </td>
                            <td class="fw-semibold">
                                <!-- number_format da formato de miles al precio -->
                                $<?php echo number_format($p['precio'], 0, ',', '.'); ?>
                            </td>
                            <td class="fw-bold text-center" style="font-size:16px;">
                                <?php echo $p['stock_total']; ?>
                            </td>
                            <td class="text-center text-muted">
                                <?php echo $p['stock_minimo']; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $badge_class; ?> px-2 py-1 rounded-pill">
                                    <?php echo $badge_texto; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <!-- Botón editar -->
                                <a href="editar.php?id=<?php echo $p['id']; ?>"
                                   class="btn btn-sm btn-outline-primary me-1"
                                   title="Editar producto">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <!-- Botón desactivar (solo para administradores) -->
                                <?php if($_SESSION['rol'] == 1): ?>
                                <button onclick="confirmarEliminar(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['nombre']); ?>')"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Desactivar producto">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <!-- Sin productos -->
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-box-seam" style="font-size:40px;"></i>
                                <p class="mt-3 mb-1 fw-semibold">
                                    <?php echo $buscar ? 'No se encontraron productos con ese criterio' : 'No hay productos registrados aún'; ?>
                                </p>
                                <a href="crear.php" class="btn btn-primary mt-2">
                                    <i class="bi bi-plus-lg me-1"></i> Registrar primer producto
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
/**
 * FUNCIÓN: confirmarEliminar()
 * QUÉ HACE: Muestra una confirmación antes de desactivar un producto.
 *           Evita que se eliminen productos por error.
 * PARÁMETROS:
 *   - id: el ID del producto en la base de datos
 *   - nombre: el nombre del producto para mostrarlo en el mensaje
 */
function confirmarEliminar(id, nombre) {
    // confirm() muestra un cuadro de diálogo con Aceptar/Cancelar
    if(confirm('¿Estás seguro de desactivar el producto "' + nombre + '"?\nNo se eliminará, solo quedará inactivo.')) {
        // Si el usuario acepta, redirigimos al archivo que hace la eliminación
        window.location.href = 'eliminar.php?id=' + id;
    }
}

/**
 * FUNCIÓN: autoFocusBusqueda()
 * QUÉ HACE: Pone el cursor automáticamente en el campo de búsqueda
 *           para que la pistola lectora pueda escanear de inmediato.
 */
window.addEventListener('load', function() {
    // Solo ponemos foco si no hay texto ya en el campo (para no interrumpir)
    const campo = document.getElementById('campoBusqueda');
    if(campo.value === '') {
        campo.focus();
    }
});
</script>
</body>
</html>