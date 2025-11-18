<?php
/**
 * Reportes de Asignaciones - Jefe de Almacén
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/AsignacionTecnico.php';
require_once '../../models/User.php';

if (!tieneAlgunRol([ROL_JEFE_ALMACEN, ROL_ADMINISTRADOR])) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();
$asignacionModel = new AsignacionTecnico($db);
$userModel = new User($db);

// Obtener datos para reportes
$resumen_general = $asignacionModel->obtenerResumen();
$tecnicos_stock_bajo = $asignacionModel->obtenerTecnicosStockBajo();
$tecnicos = $userModel->obtenerTecnicos();

// Reporte por técnico seleccionado
$reporte_tecnico = null;
$historial_tecnico = [];
if (!empty($_GET['tecnico_id'])) {
    $tecnico_id = (int)$_GET['tecnico_id'];
    $reporte_tecnico = $userModel->obtenerPorId($tecnico_id);
    if ($reporte_tecnico) {
        $asignaciones_tecnico = $asignacionModel->obtenerPorTecnico($tecnico_id);
        $historial_tecnico = $asignacionModel->obtenerHistorialTecnico($tecnico_id, 20);
        
        // Calcular estadísticas del técnico
        $total_materiales = count($asignaciones_tecnico);
        $total_items = array_sum(array_column($asignaciones_tecnico, 'cantidad'));
        $valor_total = 0;
        foreach ($asignaciones_tecnico as $asignacion) {
            $valor_total += ($asignacion['costo_unitario'] ?? 0) * $asignacion['cantidad'];
        }
    }
}

// Obtener materiales más asignados
$query_materiales_top = "SELECT m.codigo, m.nombre, 
                                SUM(st.cantidad) as total_asignado,
                                COUNT(DISTINCT st.tecnico_id) as tecnicos_asignados,
                                AVG(st.cantidad) as promedio_por_tecnico
                         FROM stock_tecnicos st
                         INNER JOIN materiales m ON st.material_id = m.id
                         WHERE st.cantidad > 0
                         GROUP BY st.material_id, m.codigo, m.nombre
                         ORDER BY total_asignado DESC
                         LIMIT 10";
$stmt_materiales = $db->prepare($query_materiales_top);
$stmt_materiales->execute();
$materiales_top = $stmt_materiales->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Reportes de Asignaciones";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5><i class="bi bi-graph-up me-2"></i>Reportes de Asignaciones</h5>
                    <p class="text-muted mb-0">Análisis detallado de asignaciones de materiales a técnicos</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-success" onclick="exportarReporte('excel')">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </button>
                    <button type="button" class="btn btn-danger" onclick="exportarReporte('pdf')">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resumen General -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-gradient-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Técnicos Activos</h6>
                        <h2><?php echo $resumen_general['total_tecnicos_con_asignaciones'] ?? 0; ?></h2>
                        <small>con materiales asignados</small>
                    </div>
                    <i class="bi bi-people" style="font-size: 3rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-gradient-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Materiales Asignados</h6>
                        <h2><?php echo $resumen_general['total_materiales_asignados'] ?? 0; ?></h2>
                        <small>tipos diferentes</small>
                    </div>
                    <i class="bi bi-box-seam" style="font-size: 3rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-gradient-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Items</h6>
                        <h2><?php echo number_format($resumen_general['total_items_asignados'] ?? 0); ?></h2>
                        <small>unidades asignadas</small>
                    </div>
                    <i class="bi bi-boxes" style="font-size: 3rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-gradient-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Valor Total</h6>
                        <h2>S/ <?php echo number_format($resumen_general['valor_total_asignado'] ?? 0, 0); ?></h2>
                        <small>en materiales asignados</small>
                    </div>
                    <i class="bi bi-currency-dollar" style="font-size: 3rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alertas de Stock Bajo -->
<?php if (!empty($tecnicos_stock_bajo)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-warning">
            <div class="d-flex align-items-center mb-3">
                <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 1.5rem;"></i>
                <h6 class="mb-0">Técnicos con Stock Bajo (≤ 5 unidades)</h6>
            </div>
            <div class="row">
                <?php foreach ($tecnicos_stock_bajo as $tecnico_bajo): ?>
                <div class="col-md-6 mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo $tecnico_bajo['nombre_completo']; ?></strong>
                            <br><small class="text-muted"><?php echo $tecnico_bajo['materiales_detalle']; ?></small>
                        </div>
                        <span class="badge bg-warning"><?php echo $tecnico_bajo['materiales_bajo_stock']; ?> materiales</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Materiales Más Asignados -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-3"><i class="bi bi-trophy me-2"></i>Top 10 - Materiales Más Asignados</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Material</th>
                            <th>Total Asignado</th>
                            <th>Técnicos</th>
                            <th>Promedio por Técnico</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materiales_top as $index => $material): ?>
                        <tr>
                            <td><span class="badge bg-primary"><?php echo $index + 1; ?></span></td>
                            <td>
                                <div>
                                    <code><?php echo $material['codigo']; ?></code>
                                    <strong class="d-block"><?php echo $material['nombre']; ?></strong>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-success" style="font-size: 14px;">
                                    <?php echo number_format($material['total_asignado']); ?> unidades
                                </span>
                            </td>
                            <td><?php echo $material['tecnicos_asignados']; ?> técnicos</td>
                            <td><?php echo number_format($material['promedio_por_tecnico'], 1); ?> unidades</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Selector de Técnico para Reporte Detallado -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-3"><i class="bi bi-person-lines-fill me-2"></i>Reporte Detallado por Técnico</h5>
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label">Seleccione un Técnico:</label>
                    <select name="tecnico_id" class="form-select">
                        <option value="">Seleccione un técnico...</option>
                        <?php foreach ($tecnicos as $tec): ?>
                        <option value="<?php echo $tec['id']; ?>" 
                                <?php echo (isset($_GET['tecnico_id']) && $_GET['tecnico_id'] == $tec['id']) ? 'selected' : ''; ?>>
                            <?php echo $tec['nombre_completo']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Generar Reporte
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reporte Detallado del Técnico -->
<?php if ($reporte_tecnico): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="mb-1">Reporte Detallado: <?php echo $reporte_tecnico['nombre_completo']; ?></h5>
                    <small class="text-muted"><?php echo $reporte_tecnico['email']; ?></small>
                </div>
                <button type="button" class="btn btn-outline-primary" onclick="imprimirReporte()">
                    <i class="bi bi-printer"></i> Imprimir
                </button>
            </div>

            <!-- Estadísticas del Técnico -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <i class="bi bi-box text-primary" style="font-size: 2rem;"></i>
                            <h4 class="mt-2"><?php echo $total_materiales ?? 0; ?></h4>
                            <small class="text-muted">Tipos de Materiales</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <i class="bi bi-boxes text-success" style="font-size: 2rem;"></i>
                            <h4 class="mt-2"><?php echo number_format($total_items ?? 0); ?></h4>
                            <small class="text-muted">Total de Items</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <i class="bi bi-currency-dollar text-warning" style="font-size: 2rem;"></i>
                            <h4 class="mt-2">S/ <?php echo number_format($valor_total ?? 0, 2); ?></h4>
                            <small class="text-muted">Valor Total Asignado</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Materiales Asignados Actualmente -->
            <h6 class="mb-3">Materiales Asignados Actualmente</h6>
            <?php if (!empty($asignaciones_tecnico)): ?>
            <div class="table-responsive mb-4">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th>Cantidad</th>
                            <th>Valor Unitario</th>
                            <th>Valor Total</th>
                            <th>Fecha Asignación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($asignaciones_tecnico as $asignacion): ?>
                        <tr>
                            <td>
                                <code><?php echo $asignacion['codigo']; ?></code>
                                <?php echo $asignacion['material_nombre']; ?>
                            </td>
                            <td><?php echo $asignacion['cantidad']; ?> <?php echo $asignacion['unidad']; ?></td>
                            <td>S/ <?php echo number_format($asignacion['costo_unitario'] ?? 0, 2); ?></td>
                            <td>S/ <?php echo number_format(($asignacion['costo_unitario'] ?? 0) * $asignacion['cantidad'], 2); ?></td>
                            <td><?php echo formatearFecha($asignacion['fecha_asignacion']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Este técnico no tiene materiales asignados actualmente.
            </div>
            <?php endif; ?>

            <!-- Historial de Movimientos -->
            <h6 class="mb-3">Historial Reciente de Movimientos</h6>
            <?php if (!empty($historial_tecnico)): ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Material</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Motivo</th>
                            <th>Responsable</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial_tecnico as $movimiento): ?>
                        <tr>
                            <td><?php echo formatearFechaHora($movimiento['fecha_movimiento']); ?></td>
                            <td>
                                <code><?php echo $movimiento['codigo']; ?></code>
                                <?php echo $movimiento['material_nombre']; ?>
                            </td>
                            <td>
                                <?php
                                $badge_class = match($movimiento['tipo_movimiento']) {
                                    'entrada' => 'bg-success',
                                    'salida' => 'bg-danger',
                                    'reasignacion' => 'bg-warning',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo ucfirst($movimiento['tipo_movimiento']); ?>
                                </span>
                            </td>
                            <td><?php echo $movimiento['cantidad']; ?> <?php echo $movimiento['unidad']; ?></td>
                            <td><?php echo $movimiento['motivo']; ?></td>
                            <td><?php echo $movimiento['usuario_nombre']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                No hay historial de movimientos para este técnico.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function exportarReporte(formato) {
    // Implementar exportación según el formato
    alert('Funcionalidad de exportación a ' + formato.toUpperCase() + ' en desarrollo');
}

function imprimirReporte() {
    window.print();
}

// Estilos para impresión
const estilosImpresion = `
    <style>
        @media print {
            .btn, .alert, .content-card { 
                box-shadow: none !important; 
                border: 1px solid #ddd !important; 
            }
            .btn { display: none !important; }
            .card { break-inside: avoid; }
        }
    </style>
`;
document.head.insertAdjacentHTML('beforeend', estilosImpresion);
</script>

<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
}
.bg-gradient-success {
    background: linear-gradient(135deg, #10b981 0%, #14b8a6 100%);
}
.bg-gradient-info {
    background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
}
.bg-gradient-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
}
</style>

<?php include '../layouts/footer.php'; ?>
