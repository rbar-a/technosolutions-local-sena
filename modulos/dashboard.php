<?php
/**
 * ARCHIVO: dashboard.php
 * UBICACIÓN: /technosolutions/modulos/dashboard.php
 * FUNCIÓN: Panel principal del sistema. Muestra resumen del inventario,
 *          tarjetas con estadísticas y alertas de stock bajo.
 *          Es la primera pantalla que ve el usuario después de iniciar sesión.
 * AUTOR: TechnoSolutions Colombia - SENA ADSO
 */

// Iniciamos la sesión para poder leer las variables del usuario logueado
session_start();

// ── PROTECCIÓN DE PÁGINA ─────────────────────────────────────────────────────
// Si el usuario NO tiene sesión activa (no está logueado), lo mandamos al login.
// Esto protege la página para que no pueda accederse sin autenticación.
if(!isset($_SESSION['usuario'])){
    header('Location: auth/login.php');
    exit();
}

// Incluimos la conexión a la base de datos
require_once '../config/conexion.php';

// ── CONSULTAS PARA LAS TARJETAS DE ESTADÍSTICAS ──────────────────────────────

// 1. Total de productos activos en el sistema
$sql_productos = "SELECT COUNT(*) as total FROM productos WHERE activo = 1";
$res_productos = $conexion->query($sql_productos);
$total_productos = $res_productos->fetch_assoc()['total'];

// 2. Total de movimientos registrados hoy
$sql_movimientos = "SELECT COUNT(*) as total FROM movimientos 
                    WHERE DATE(fecha) = CURDATE()";
$res_movimientos = $conexion->query($sql_movimientos);
$movimientos_hoy = $res_movimientos->fetch_assoc()['total'];

// 3. Productos con stock bajo (por debajo del mínimo configurado)
// Sumamos el stock de todas las bodegas por producto y comparamos con stock_minimo
$sql_stock_bajo = "SELECT COUNT(*) as total FROM (
                    SELECT p.id, p.stock_minimo, 
                           COALESCE(SUM(sb.cantidad), 0) as stock_total
                    FROM productos p
                    LEFT JOIN stock_bodega sb ON p.id = sb.id_producto
                    WHERE p.activo = 1
                    GROUP BY p.id, p.stock_minimo
                    HAVING stock_total <= p.stock_minimo
                  ) as subconsulta";
$res_stock_bajo = $conexion->query($sql_stock_bajo);
$productos_stock_bajo = $res_stock_bajo->fetch_assoc()['total'];

// 4. Total de bodegas activas
$sql_bodegas = "SELECT COUNT(*) as total FROM bodegas WHERE activa = 1";
$res_bodegas = $conexion->query($sql_bodegas);
$total_bodegas = $res_bodegas->fetch_assoc()['total'];

// ── ÚLTIMOS 5 MOVIMIENTOS REGISTRADOS ────────────────────────────────────────
$sql_ultimos = "SELECT m.*, p.nombre as producto_nombre, 
                       p.codigo_barras,
                       u.nombre as usuario_nombre,
                       bo.nombre as bodega_origen,
                       bd.nombre as bodega_destino
                FROM movimientos m
                JOIN productos p  ON m.id_producto = p.id
                JOIN usuarios u   ON m.id_usuario  = u.id
                LEFT JOIN bodegas bo ON m.id_bodega_origen  = bo.id
                LEFT JOIN bodegas bd ON m.id_bodega_destino = bd.id
                ORDER BY m.fecha DESC
                LIMIT 5";
$res_ultimos = $conexion->query($sql_ultimos);

// ── PRODUCTOS CON STOCK BAJO (para la tabla de alertas) ──────────────────────
$sql_alertas = "SELECT p.nombre, p.codigo_barras, p.stock_minimo,
                       COALESCE(SUM(sb.cantidad), 0) as stock_actual
                FROM productos p
                LEFT JOIN stock_bodega sb ON p.id = sb.id_producto
                WHERE p.activo = 1
                GROUP BY p.id, p.nombre, p.codigo_barras, p.stock_minimo
                HAVING stock_actual <= p.stock_minimo
                ORDER BY stock_actual ASC
                LIMIT 5";
$res_alertas = $conexion->query($sql_alertas);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — TechnoSolutions</title>

    <!-- Bootstrap 5 CSS -->
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* Color de fondo suave para el área de contenido */
        body { background-color: #f0f4f8; }

        /* ── BARRA LATERAL (SIDEBAR) ─────────────────────────────────────── */
        .sidebar {
            background: linear-gradient(180deg, #1F3864 0%, #2E75B6 100%);
            min-height: 100vh;   /* Ocupa toda la altura de la pantalla */
            width: 260px;        /* Ancho fijo de la barra lateral */
            position: fixed;     /* Se queda fija aunque se haga scroll */
            top: 0;
            left: 0;
            z-index: 100;        /* Se muestra encima del contenido */
            overflow-y: auto;    /* Scroll si el menú es muy largo */
        }

        /* Logo/título en la barra lateral */
        .sidebar-brand {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.15);
        }

        /* Cada enlace del menú lateral */
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);  /* Blanco semitransparente */
            text-decoration: none;
            border-radius: 8px;
            margin: 4px 12px;
            transition: all 0.2s;          /* Animación suave al hacer hover */
            font-size: 14px;
        }

        /* Efecto hover en los enlaces del menú */
        .sidebar-link:hover {
            background: rgba(255,255,255,0.15);
            color: white;
            transform: translateX(4px);    /* Se desplaza levemente a la derecha */
        }

        /* Estilo del enlace activo (página actual) */
        .sidebar-link.active {
            background: rgba(255,255,255,0.25);
            color: white;
            font-weight: 600;
        }

        /* Ícono dentro de cada enlace del menú */
        .sidebar-link i {
            width: 24px;           /* Ancho fijo para alinear los textos */
            margin-right: 10px;
            font-size: 16px;
        }

        /* ── CONTENIDO PRINCIPAL ─────────────────────────────────────────── */
        /* Empujamos el contenido a la derecha para que no quede debajo del sidebar */
        .main-content {
            margin-left: 260px;    /* Mismo ancho que el sidebar */
            padding: 0;
        }

        /* Barra superior (navbar) */
        .topbar {
            background: white;
            padding: 16px 28px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;      /* Se queda pegada arriba al hacer scroll */
            top: 0;
            z-index: 99;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        /* ── TARJETAS DE ESTADÍSTICAS ────────────────────────────────────── */
        .stat-card {
            border: none;
            border-radius: 16px;
            padding: 24px;
            color: white;
            position: relative;
            overflow: hidden;      /* Para que el ícono decorativo no se salga */
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-4px);  /* Sube levemente al pasar el mouse */
        }

        /* Ícono decorativo grande en el fondo de la tarjeta */
        .stat-card .bg-icon {
            position: absolute;
            right: -10px;
            top: -10px;
            font-size: 80px;
            opacity: 0.15;         /* Muy transparente, es solo decorativo */
        }

        /* Número grande de la estadística */
        .stat-card .stat-number {
            font-size: 42px;
            font-weight: 700;
            line-height: 1;
        }

        /* Colores de cada tarjeta */
        .card-blue   { background: linear-gradient(135deg, #1F3864, #2E75B6); }
        .card-green  { background: linear-gradient(135deg, #1a6b3c, #28a745); }
        .card-orange { background: linear-gradient(135deg, #b85c00, #fd7e14); }
        .card-teal   { background: linear-gradient(135deg, #0d6b6b, #20c997); }

        /* ── TABLAS ──────────────────────────────────────────────────────── */
        .table-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            overflow: hidden;
        }

        .table thead th {
            background-color: #1F3864;
            color: white;
            font-weight: 600;
            font-size: 13px;
            padding: 14px 16px;
            border: none;
        }

        .table tbody td {
            padding: 12px 16px;
            vertical-align: middle;
            font-size: 14px;
            border-bottom: 1px solid #f0f4f8;
        }

        .table tbody tr:hover {
            background-color: #f8faff;
        }

        /* Etiqueta de tipo de movimiento */
        .badge-entrada  { background-color: #d4edda; color: #155724; }
        .badge-salida   { background-color: #f8d7da; color: #721c24; }
        .badge-traslado { background-color: #d1ecf1; color: #0c5460; }

        /* Separador de sección en el sidebar */
        .sidebar-section {
            padding: 8px 20px 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: rgba(255,255,255,0.4);
            letter-spacing: 1.5px;
            margin-top: 8px;
        }
    </style>
</head>
<body>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!--  BARRA LATERAL (SIDEBAR) — Menú de navegación principal                  -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div class="sidebar">

    <!-- Logo y nombre del sistema -->
    <div class="sidebar-brand">
        <div class="d-flex align-items-center">
            <i class="bi bi-boxes text-white me-2" style="font-size:28px;"></i>
            <div>
                <div class="text-white fw-bold" style="font-size:15px;">TechnoSolutions</div>
                <div style="font-size:11px; color:rgba(255,255,255,0.5);">
                    Gestión de Inventario
                </div>
            </div>
        </div>
    </div>

    <!-- Información del usuario logueado -->
    <!-- $_SESSION['nombre'] y $_SESSION['rol_nombre'] los guardamos al hacer login -->
    <div class="p-3 border-bottom" style="border-color:rgba(255,255,255,0.1)!important;">
        <div class="d-flex align-items-center">
            <div class="rounded-circle bg-white d-flex align-items-center justify-content-center me-2"
                 style="width:36px;height:36px;min-width:36px;">
                <!-- Mostramos la primera letra del nombre del usuario -->
                <span class="fw-bold text-primary" style="font-size:14px;">
                    <?php echo strtoupper(substr($_SESSION['nombre'], 0, 1)); ?>
                </span>
            </div>
            <div>
                <div class="text-white fw-semibold" style="font-size:13px;">
                    <!-- htmlspecialchars() previene ataques XSS al mostrar datos del usuario -->
                    <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                </div>
                <div style="font-size:11px; color:rgba(255,255,255,0.5);">
                    <?php echo htmlspecialchars($_SESSION['rol_nombre']); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ── MENÚ DE NAVEGACIÓN ──────────────────────────────────────────────── -->
    <nav class="mt-2 pb-4">

        <!-- SECCIÓN PRINCIPAL -->
        <div class="sidebar-section">Principal</div>
        <a href="dashboard.php" class="sidebar-link active">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <!-- SECCIÓN INVENTARIO -->
        <div class="sidebar-section">Inventario</div>
        <a href="productos/lista.php" class="sidebar-link">
            <i class="bi bi-box-seam"></i> Productos
        </a>
        <a href="movimientos/entrada.php" class="sidebar-link">
            <i class="bi bi-box-arrow-in-down"></i> Entrada de Mercancía
        </a>
        <a href="movimientos/salida.php" class="sidebar-link">
            <i class="bi bi-box-arrow-up"></i> Salida / Venta
        </a>
        <a href="movimientos/traslado.php" class="sidebar-link">
            <i class="bi bi-arrow-left-right"></i> Traslado entre Bodegas
        </a>
        <a href="movimientos/historial.php" class="sidebar-link">
            <i class="bi bi-clock-history"></i> Historial
        </a>

        <!-- SECCIÓN REPORTES -->
        <div class="sidebar-section">Reportes</div>
        <a href="reportes/inventario_pdf.php" class="sidebar-link">
            <i class="bi bi-file-earmark-pdf"></i> Reporte PDF
        </a>
        <a href="reportes/inventario_excel.php" class="sidebar-link">
            <i class="bi bi-file-earmark-excel"></i> Exportar Excel
        </a>

        <!-- SECCIÓN ADMINISTRACIÓN — Solo visible para Administradores -->
        <!-- $_SESSION['rol'] == 1 significa que es Administrador -->
        <?php if($_SESSION['rol'] == 1): ?>
        <div class="sidebar-section">Administración</div>
        <a href="usuarios/lista.php" class="sidebar-link">
            <i class="bi bi-people"></i> Usuarios
        </a>
        <a href="bodegas/lista.php" class="sidebar-link">
            <i class="bi bi-building"></i> Bodegas
        </a>
        <?php endif; ?>

        <!-- CERRAR SESIÓN -->
        <div class="sidebar-section">Sesión</div>
        <a href="auth/cerrar_sesion.php" class="sidebar-link">
            <i class="bi bi-box-arrow-left"></i> Cerrar Sesión
        </a>

    </nav>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!--  CONTENIDO PRINCIPAL                                                       -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div class="main-content">

    <!-- ── BARRA SUPERIOR ──────────────────────────────────────────────────── -->
    <div class="topbar">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Dashboard</h5>
            <small class="text-muted">
                <!-- date() formatea la fecha actual en español -->
                <?php
                $dias_es  = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
                $meses_es = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
                echo ucfirst($dias_es[(int) date('w')]) . ', ' . date('d') . ' de ' . $meses_es[(int) date('n')] . ' de ' . date('Y');
                ?>
            </small>
        </div>
        <!-- Saludo personalizado con el nombre del usuario -->
        <div class="text-muted" style="font-size:14px;">
            <i class="bi bi-person-circle me-1"></i>
            Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['nombre']); ?></strong>
        </div>
    </div>

    <!-- ── ÁREA DE CONTENIDO ───────────────────────────────────────────────── -->
    <div class="p-4">

        <!-- ── TARJETAS DE ESTADÍSTICAS ──────────────────────────────────── -->
        <div class="row g-3 mb-4">

            <!-- Tarjeta 1: Total de productos -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card card-blue">
                    <i class="bi bi-box-seam bg-icon"></i>
                    <div class="stat-number"><?php echo $total_productos; ?></div>
                    <div class="mt-1" style="font-size:14px; opacity:0.85;">
                        Productos Activos
                    </div>
                    <small style="opacity:0.6;">En el sistema</small>
                </div>
            </div>

            <!-- Tarjeta 2: Movimientos de hoy -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card card-green">
                    <i class="bi bi-arrow-left-right bg-icon"></i>
                    <div class="stat-number"><?php echo $movimientos_hoy; ?></div>
                    <div class="mt-1" style="font-size:14px; opacity:0.85;">
                        Movimientos Hoy
                    </div>
                    <small style="opacity:0.6;">Entradas y salidas</small>
                </div>
            </div>

            <!-- Tarjeta 3: Alertas de stock bajo -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card card-orange">
                    <i class="bi bi-exclamation-triangle bg-icon"></i>
                    <div class="stat-number"><?php echo $productos_stock_bajo; ?></div>
                    <div class="mt-1" style="font-size:14px; opacity:0.85;">
                        Stock Bajo
                    </div>
                    <small style="opacity:0.6;">Requieren reposición</small>
                </div>
            </div>

            <!-- Tarjeta 4: Bodegas activas -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card card-teal">
                    <i class="bi bi-building bg-icon"></i>
                    <div class="stat-number"><?php echo $total_bodegas; ?></div>
                    <div class="mt-1" style="font-size:14px; opacity:0.85;">
                        Bodegas Activas
                    </div>
                    <small style="opacity:0.6;">En operación</small>
                </div>
            </div>

        </div>
        <!-- FIN tarjetas -->

        <!-- ── TABLAS INFERIORES ──────────────────────────────────────────── -->
        <div class="row g-3">

            <!-- TABLA: Últimos movimientos -->
            <div class="col-xl-7">
                <div class="card table-card">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-clock-history text-primary me-2"></i>
                            Últimos Movimientos
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Tipo</th>
                                        <th>Cantidad</th>
                                        <th>Usuario</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                // Verificamos si hay movimientos para mostrar
                                if($res_ultimos->num_rows > 0):
                                    // Recorremos cada fila del resultado con while
                                    while($mov = $res_ultimos->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold" style="font-size:13px;">
                                                <?php echo htmlspecialchars($mov['producto_nombre']); ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($mov['codigo_barras']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <!-- Mostramos badge de color según el tipo de movimiento -->
                                            <span class="badge badge-<?php echo $mov['tipo']; ?> px-2 py-1 rounded-pill">
                                                <?php echo ucfirst($mov['tipo']); /* ucfirst pone mayúscula inicial */ ?>
                                            </span>
                                        </td>
                                        <td class="fw-semibold"><?php echo $mov['cantidad']; ?></td>
                                        <td style="font-size:13px;">
                                            <?php echo htmlspecialchars($mov['usuario_nombre']); ?>
                                        </td>
                                        <td style="font-size:12px;" class="text-muted">
                                            <!-- date() formatea la fecha del movimiento -->
                                            <?php echo date('d/m/Y H:i', strtotime($mov['fecha'])); ?>
                                        </td>
                                    </tr>
                                <?php
                                    endwhile;
                                else:
                                ?>
                                    <!-- Si no hay movimientos, mostramos este mensaje -->
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox" style="font-size:24px;"></i>
                                            <p class="mt-2 mb-0">No hay movimientos registrados aún</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLA: Alertas de stock bajo -->
            <div class="col-xl-5">
                <div class="card table-card">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                            Alertas de Stock Bajo
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Stock Actual</th>
                                        <th>Mínimo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                if($res_alertas->num_rows > 0):
                                    while($alerta = $res_alertas->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold" style="font-size:13px;">
                                                <?php echo htmlspecialchars($alerta['nombre']); ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo $alerta['codigo_barras']; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <!-- Si el stock es 0 mostramos en rojo, si no en naranja -->
                                            <span class="fw-bold <?php echo $alerta['stock_actual'] == 0 ? 'text-danger' : 'text-warning'; ?>">
                                                <?php echo $alerta['stock_actual']; ?>
                                            </span>
                                        </td>
                                        <td class="text-muted"><?php echo $alerta['stock_minimo']; ?></td>
                                    </tr>
                                <?php
                                    endwhile;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">
                                            <i class="bi bi-check-circle text-success" 
                                               style="font-size:24px;"></i>
                                            <p class="mt-2 mb-0">¡Todo el stock está en orden!</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- FIN tablas -->

    </div>
    <!-- FIN área de contenido -->

</div>
<!-- FIN contenido principal -->

<!-- Bootstrap JS -->
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

</body>
</html>