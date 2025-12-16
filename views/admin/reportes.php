<?php

/**
 * Reportes - Administrador
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';

if (!tieneRol(ROL_ADMINISTRADOR)) {
    redirigirSegunRol();
}

$database = new Database();
$database = new Database();
$db = $database->getConnection();
$sede_id = obtenerSedeActual();

// Procesar exportación
if (isset($_GET['exportar'])) {
    $tipo = $_GET['exportar'];

    if ($tipo === 'materiales') {
        $query = "SELECT m.codigo, m.nombre, c.nombre as categoria, m.stock_actual, m.unidad, 
                         m.stock_minimo, m.stock_maximo, m.ubicacion, m.costo_unitario
                  FROM materiales m
                  LEFT JOIN categorias_materiales c ON m.categoria_id = c.id
                  WHERE m.estado = 'activo' AND m.sede_id = :sede_id
                  ORDER BY m.nombre";
        $stmt = $db->prepare($query);
        $stmt->execute([':sede_id' => $sede_id]);
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        $headers = ['Código', 'Material', 'Categoría', 'Stock', 'Unidad', 'Mín', 'Máx', 'Ubicación', 'Costo'];
        exportarExcel('reporte_materiales_' . date('Ymd') . '.xls', $data, $headers);
    }

    if ($tipo === 'movimientos') {
        $fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
        $fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

        $query = "SELECT DATE(mi.fecha_movimiento) as fecha, m.codigo, m.nombre, 
                         mi.tipo_movimiento, mi.cantidad, u.nombre_completo, mi.motivo
                  FROM movimientos_inventario mi
                  INNER JOIN materiales m ON mi.material_id = m.id
                  INNER JOIN usuarios u ON mi.usuario_id = u.id
                  WHERE DATE(mi.fecha_movimiento) BETWEEN :desde AND :hasta AND m.sede_id = :sede_id
                  ORDER BY mi.fecha_movimiento DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([':desde' => $fecha_desde, ':hasta' => $fecha_hasta, ':sede_id' => $sede_id]);
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        $headers = ['Fecha', 'Código', 'Material', 'Tipo', 'Cantidad', 'Usuario', 'Motivo'];
        exportarExcel('reporte_movimientos_' . date('Ymd') . '.xls', $data, $headers);
    }

    if ($tipo === 'solicitudes') {
        $query = "SELECT s.codigo_solicitud, u.nombre_completo, DATE(s.fecha_solicitud) as fecha,
                         s.estado, s.motivo
                  FROM solicitudes s
                  INNER JOIN usuarios u ON s.tecnico_id = u.id
                  WHERE u.sede_id = :sede_id
                  ORDER BY s.fecha_solicitud DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([':sede_id' => $sede_id]);
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        $headers = ['Código', 'Técnico', 'Fecha', 'Estado', 'Motivo'];
        exportarExcel('reporte_solicitudes_' . date('Ymd') . '.xls', $data, $headers);
    }
}

$page_title = "Reportes del Sistema";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <h5><i class="bi bi-file-earmark-spreadsheet me-2"></i>Generador de Reportes</h5>
            <p class="text-muted mb-0">Exporte datos del sistema en formato Excel</p>
        </div>
    </div>
</div>

<!-- Reportes Disponibles -->
<div class="row">
    <!-- Reporte de Materiales -->
    <div class="col-md-4 mb-4">
        <div class="content-card">
            <div class="text-center mb-3">
                <i class="bi bi-box text-primary" style="font-size: 48px;"></i>
            </div>
            <h5 class="text-center mb-3">Inventario de Materiales</h5>
            <p class="text-muted text-center">
                Exporta el listado completo de materiales con stock actual, ubicación y costos.
            </p>
            <div class="d-grid">
                <a href="?exportar=materiales" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel me-2"></i>Descargar Excel
                </a>
            </div>
        </div>
    </div>

    <!-- Reporte de Movimientos -->
    <div class="col-md-4 mb-4">
        <div class="content-card">
            <div class="text-center mb-3">
                <i class="bi bi-arrow-left-right text-success" style="font-size: 48px;"></i>
            </div>
            <h5 class="text-center mb-3">Movimientos de Inventario</h5>
            <form method="GET" class="mb-3">
                <input type="hidden" name="exportar" value="movimientos">
                <div class="mb-2">
                    <label class="form-label small">Desde:</label>
                    <input type="date" name="fecha_desde" class="form-control form-control-sm"
                        value="<?php echo date('Y-m-01'); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label small">Hasta:</label>
                    <input type="date" name="fecha_hasta" class="form-control form-control-sm"
                        value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-file-earmark-excel me-2"></i>Descargar Excel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reporte de Solicitudes -->
    <div class="col-md-4 mb-4">
        <div class="content-card">
            <div class="text-center mb-3">
                <i class="bi bi-clipboard-check text-warning" style="font-size: 48px;"></i>
            </div>
            <h5 class="text-center mb-3">Solicitudes de Técnicos</h5>
            <p class="text-muted text-center">
                Exporta el historial completo de solicitudes con su estado y respuestas.
            </p>
            <div class="d-grid">
                <a href="?exportar=solicitudes" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel me-2"></i>Descargar Excel
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas Generales -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-4"><i class="bi bi-graph-up me-2"></i>Estadísticas del Sistema</h5>

            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted">Resumen de Inventario</h6>
                    <?php
                    $query = "SELECT 
                                COUNT(*) as total_materiales,
                                SUM(stock_actual) as total_items,
                                SUM(stock_actual * costo_unitario) as valor_total,
                                SUM(stock_actual * costo_unitario) as valor_total,
                                COUNT(CASE WHEN stock_actual <= stock_minimo THEN 1 END) as criticos
                              FROM materiales WHERE estado = 'activo' AND sede_id = :sede_id";
                    $stmt = $db->prepare($query);
                    $stmt->execute([':sede_id' => $sede_id]);
                    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Total de Materiales:</span>
                            <strong><?php echo $stats['total_materiales']; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Total de Items:</span>
                            <strong><?php echo number_format($stats['total_items']); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Valor Total:</span>
                            <strong><?php echo formatearMoneda($stats['valor_total']); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Materiales en Estado Crítico:</span>
                            <strong class="text-danger"><?php echo $stats['criticos']; ?></strong>
                        </li>
                    </ul>
                </div>

                <div class="col-md-6">
                    <h6 class="text-muted">Actividad del Mes</h6>
                    <?php
                    $query = "SELECT 
                                tipo_movimiento,
                                COUNT(*) as total,
                                SUM(cantidad) as suma_cantidad
                                FROM movimientos_inventario mi
                                INNER JOIN materiales m ON mi.material_id = m.id
                              WHERE MONTH(mi.fecha_movimiento) = MONTH(CURRENT_DATE())
                              AND YEAR(mi.fecha_movimiento) = YEAR(CURRENT_DATE())
                              AND m.sede_id = :sede_id
                              GROUP BY mi.tipo_movimiento";
                    $stmt = $db->prepare($query);
                    $stmt->execute([':sede_id' => $sede_id]);
                    $mov_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <ul class="list-group">
                        <?php foreach ($mov_stats as $stat): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><?php echo ucfirst($stat['tipo_movimiento']); ?>s:</span>
                                <strong><?php echo $stat['total']; ?> movimientos</strong>
                            </li>
                        <?php endforeach; ?>

                        <?php
                        $query_sol = "SELECT COUNT(*) as total FROM solicitudes s
                                      INNER JOIN usuarios u ON s.tecnico_id = u.id
                                      WHERE MONTH(s.fecha_solicitud) = MONTH(CURRENT_DATE())
                                      AND u.sede_id = :sede_id";
                        $stmt = $db->prepare($query_sol);
                        $stmt->execute([':sede_id' => $sede_id]);
                        $sol_stat = $stmt->fetch(PDO::FETCH_ASSOC);
                        ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Solicitudes Recibidas:</span>
                            <strong><?php echo $sol_stat['total']; ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>