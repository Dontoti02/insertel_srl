<?php
/**
 * Dashboard - Jefe de Almacén
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/Material.php';

if (!tieneRol(ROL_JEFE_ALMACEN)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();

$materialModel = new Material($db);

$stats_materiales = $materialModel->obtenerEstadisticas();
$materiales_stock_bajo = $materialModel->obtenerStockBajo();

$page_title = "Dashboard Jefe de Almacén";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="icon blue">
                <i class="bi bi-box"></i>
            </div>
            <h3><?php echo $stats_materiales['total_materiales'] ?? 0; ?></h3>
            <p>Total Materiales</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="icon green">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <h3><?php echo formatearMoneda($stats_materiales['valor_inventario'] ?? 0); ?></h3>
            <p>Valor Inventario</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="icon orange">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <h3><?php echo $stats_materiales['stock_bajo'] ?? 0; ?></h3>
            <p>Stock Bajo</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="icon teal">
                <i class="bi bi-graph-up"></i>
            </div>
            <h3><?php echo $stats_materiales['total_materiales'] ?? 0; ?></h3>
            <p>Total Materiales</p>
        </div>
    </div>
</div>

<!-- Materiales con Stock Bajo -->
<?php if (!empty($materiales_stock_bajo)): ?>
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-exclamation-triangle text-warning me-2"></i>Alertas de Stock Bajo</h5>
                <a href="materiales.php?stock_bajo=1" class="btn btn-sm btn-danger">
                    <i class="bi bi-exclamation-circle me-1"></i>Acción Requerida
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Material</th>
                            <th>Stock Actual</th>
                            <th>Stock Mínimo</th>
                            <th>Diferencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($materiales_stock_bajo, 0, 5) as $material): ?>
                        <tr>
                            <td><code><?php echo $material['codigo']; ?></code></td>
                            <td><?php echo $material['nombre']; ?></td>
                            <td>
                                <span class="badge bg-danger">
                                    <?php echo $material['stock_actual']; ?> <?php echo $material['unidad']; ?>
                                </span>
                            </td>
                            <td><?php echo $material['stock_minimo']; ?> <?php echo $material['unidad']; ?></td>
                            <td>
                                <span class="text-danger fw-bold">
                                    <i class="bi bi-arrow-down me-1"></i>
                                    <?php echo ($material['stock_minimo'] - $material['stock_actual']); ?> <?php echo $material['unidad']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../layouts/footer.php'; ?>
