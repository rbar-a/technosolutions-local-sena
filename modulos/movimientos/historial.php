<?php
/**
 * ARCHIVO: historial.php
 * UBICACIÓN: /technosolutions/modulos/movimientos/historial.php
 * FUNCIÓN: Muestra el historial completo de todos los movimientos
 *          del inventario. Permite filtrar por tipo, fecha y bodega.
 * AUTOR: TechnoSolutions Colombia - SENA ADSO
 */

session_start();

if(!isset($_SESSION['usuario'])){
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';

// ── FILTROS DESDE LA URL ─────────────────────────────────────────────────────
// Recogemos los filtros que el usuario seleccionó en el formulario
$filtro_tipo   = isset($_GET['tipo'])   ? $_GET['tipo']   : '';
$filtro_fecha  = isset($_GET['fecha'])  ? $_GET['fecha']  : '';
$filtro_bodega = isset($_GET['bodega']) ? intval($_GET['bodega']) : 0;

// ── CONSTRUCCIÓN DINÁMICA DEL SQL ────────────────────────────────────────────
// Empezamos con la consulta base y vamos agregando condiciones según los filtros
$sql = "SELECT m.*,
               p.nombre  as producto_nombre,
               p.codigo_barras,
               u.nombre  as usuario_nombre,
               bo.nombre as bodega_origen_nombre,
               bd.nombre as bodega_destino_nombre
        FROM movimientos m
        JOIN productos p  ON m.id_producto = p.id
        JOIN usuarios u   ON m.id_usuario  = u.id
        LEFT JOIN bodegas bo ON m.id_bodega_origen  = bo.id
        LEFT JOIN bodegas bd ON m.id_bodega_destino = bd.id
        WHERE 1=1";  // WHERE 1=1 permite agregar AND dinámicamente

// Array para guardar los parámetros del prepared statement
$params = [];
// String con los tipos de cada parámetro para bind_param
$types  = '';

// Aplicamos filtro de tipo si se seleccionó uno
if($filtro_tipo !== ''){
    $sql     .= " AND m.tipo = ?";
    $params[] = $filtro_tipo;
    $types   .= 's';
}

// Aplicamos filtro de fecha si se ingresó una
if($filtro_fecha !== ''){
    // DATE(m.fecha) extrae solo la fecha sin la hora para comparar
    $sql     .= " AND DATE(m.fecha) = ?";
    $params[] = $filtro_fecha;
    $types   .= 's';
}

// Aplicamos filtro de bodega (busca en origen O destino)
if($filtro_bodega > 0){
    $sql     .= " AND (m.id_bodega_origen = ? OR m.id_bodega_destino = ?)";
    $params[] = $filtro_bodega;
    $params[] = $filtro_bodega;
    $types   .= 'ii';
}

// Ordenamos por fecha descendente (más reciente primero)
$sql .= " ORDER BY m.fecha DESC LIMIT 200";

// Ejecutamos la consulta con los parámetros dinámicos
$stmt = $conexion->prepare($sql);

// Solo hacemos bind_param si hay parámetros que enlazar
if(!empty($params)){
    // call_user_func_array llama bind_param con un array de argumentos dinámicos
    // Esto es necesario porque bind_param no acepta arrays directamente
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$movimientos = $stmt->get_result();

// Traemos bodegas para el filtro
$bodegas = $conexion->query("SELECT * FROM bodegas WHERE activa = 1 ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial — TechnoSolutions</title>
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
        /* Badges de color por tipo de movimiento */
        .badge-entrada  { background:#d4edda; color:#155724; font-size:12px; }
        .badge-salida   { background:#f8d7da; color:#721c24; font-size:12px; }
        .badge-traslado { background:#d1ecf1; color:#0c5460; font-size:12px; }
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
        <a href="traslado.php" class="sidebar-link"><i class="bi bi-arrow-left-right"></i> Traslado entre Bodegas</a>
        <a href="historial.php" class="sidebar-link active"><i class="bi bi-clock-history"></i> Historial</a>
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
            <h5 class="mb-0 fw-bold text-dark">Historial de Movimientos</h5>
            <small class="text-muted">Registro completo de entradas, salidas y traslados</small>
        </div>
    </div>

    <div class="p-4">

        <!-- ── FILTROS ──────────────────────────────────────────────────────── -->
        <div class="content-card p-3 mb-4">
            <form method="GET" action="historial.php" class="row g-3 align-items-end">
                <!-- Filtro por tipo -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Tipo de Movimiento</label>
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <option value="entrada"  <?php echo $filtro_tipo=='entrada'  ? 'selected':''; ?>>Entrada</option>
                        <option value="salida"   <?php echo $filtro_tipo=='salida'   ? 'selected':''; ?>>Salida</option>
                        <option value="traslado" <?php echo $filtro_tipo=='traslado' ? 'selected':''; ?>>Traslado</option>
                    </select>
                </div>
                <!-- Filtro por fecha -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Fecha</label>
                    <input type="date"
                           name="fecha"
                           class="form-control form-control-sm"
                           value="<?php echo htmlspecialchars($filtro_fecha); ?>">
                </div>
                <!-- Filtro por bodega -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Bodega</label>
                    <select name="bodega" class="form-select form-select-sm">
                        <option value="0">-- Todas las bodegas --</option>
                        <?php while($b = $bodegas->fetch_assoc()): ?>
                        <option value="<?php echo $b['id']; ?>"
                                <?php echo $filtro_bodega == $b['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b['nombre']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <!-- Botones -->
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <a href="historial.php" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="bi bi-x-lg me-1"></i>Limpiar
                    </a>
                </div>
            </form>
        </div>

        <!-- Contador de resultados -->
        <p class="text-muted small mb-3">
            <i class="bi bi-list-ul me-1"></i>
            Mostrando <strong><?php echo $movimientos->num_rows; ?></strong> movimiento(s)
            <?php if($filtro_tipo || $filtro_fecha || $filtro_bodega): ?>
            con filtros aplicados
            <?php endif; ?>
        </p>

        <!-- ── TABLA DE HISTORIAL ─────────────────────────────────────────── -->
        <div class="content-card overflow-hidden">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Fecha y Hora</th>
                            <th>Tipo</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Bodega Origen</th>
                            <th>Bodega Destino</th>
                            <th>Usuario</th>
                            <th>Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if($movimientos->num_rows > 0):
                        while($m = $movimientos->fetch_assoc()):
                    ?>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">
                                #<?php echo $m['id']; ?>
                            </td>
                            <td style="font-size:12px;">
                                <div><?php echo date('d/m/Y', strtotime($m['fecha'])); ?></div>
                                <small class="text-muted">
                                    <?php echo date('H:i:s', strtotime($m['fecha'])); ?>
                                </small>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $m['tipo']; ?> px-2 py-1 rounded-pill">
                                    <?php
                                    // Mostramos ícono según el tipo de movimiento
                                    $iconos = [
                                        'entrada'  => 'bi-arrow-down-circle-fill',
                                        'salida'   => 'bi-arrow-up-circle-fill',
                                        'traslado' => 'bi-arrow-left-right'
                                    ];
                                    echo '<i class="bi ' . $iconos[$m['tipo']] . ' me-1"></i>';
                                    echo ucfirst($m['tipo']);
                                    ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold" style="font-size:13px;">
                                    <?php echo htmlspecialchars($m['producto_nombre']); ?>
                                </div>
                                <small class="text-muted" style="font-family:monospace;">
                                    <?php echo $m['codigo_barras']; ?>
                                </small>
                            </td>
                            <td class="fw-bold text-center" style="font-size:16px;">
                                <?php echo $m['cantidad']; ?>
                            </td>
                            <td style="font-size:13px;">
                                <?php echo $m['bodega_origen_nombre']
                                    ? htmlspecialchars($m['bodega_origen_nombre'])
                                    : '<span class="text-muted">—</span>'; ?>
                            </td>
                            <td style="font-size:13px;">
                                <?php echo $m['bodega_destino_nombre']
                                    ? htmlspecialchars($m['bodega_destino_nombre'])
                                    : '<span class="text-muted">—</span>'; ?>
                            </td>
                            <td style="font-size:13px;">
                                <?php echo htmlspecialchars($m['usuario_nombre']); ?>
                            </td>
                            <td style="font-size:12px;" class="text-muted">
                                <?php echo $m['observacion']
                                    ? htmlspecialchars($m['observacion'])
                                    : '—'; ?>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox" style="font-size:40px;"></i>
                                <p class="mt-3 mb-0">No hay movimientos registrados aún</p>
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
</body>
</html>