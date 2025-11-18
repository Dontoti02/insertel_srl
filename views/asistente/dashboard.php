<?php
/**
 * Dashboard - Asistente de Almacén
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/Material.php';

if (!tieneRol(ROL_ASISTENTE_ALMACEN)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();

$materialModel = new Material($db);

$materiales_disponibles = $materialModel->obtenerTodos();
$stats_materiales = $materialModel->obtenerEstadisticas();

$page_title = "Dashboard Asistente de Almacen";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="icon blue">
                <i class="bi bi-box"></i>
            </div>
            <h3><?php echo count($materiales_disponibles); ?></h3>
            <p>Materiales Disponibles</p>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="icon orange">
                <i class="bi bi-graph-up"></i>
            </div>
            <h3><?php echo $stats_materiales['total_materiales'] ?? 0; ?></h3>
            <p>Total Materiales</p>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="icon green">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <h3><?php echo $stats_materiales['stock_bajo'] ?? 0; ?></h3>
            <p>Stock Bajo</p>
        </div>
    </div>
</div>

<!-- Tareas del Día -->
<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-list-check me-2"></i>Tareas Pendientes del Día</h5>
            </div>
            <div class="list-group list-group-flush">
                <div class="list-group-item">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-box text-info me-3" style="font-size: 24px;"></i>
                        <div class="flex-grow-1">
                            <h6 class="mb-0">Inventario de Materiales</h6>
                            <small class="text-muted">Consultar disponibilidad</small>
                        </div>
                        <a href="materiales.php" class="btn btn-sm btn-outline-primary">Ir</a>
                    </div>
                </div>
                <div class="list-group-item">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-file-text text-success me-3" style="font-size: 24px;"></i>
                        <div class="flex-grow-1">
                            <h6 class="mb-0">Revisar Actas Técnicas</h6>
                            <small class="text-muted">Documentos subidos por técnicos</small>
                        </div>
                        <a href="actas.php" class="btn btn-sm btn-outline-primary">Ir</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-box-seam me-2"></i>Resumen de Materiales</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Categoría</th>
                            <th>Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Total Materiales</td>
                            <td><span class="badge bg-primary"><?php echo $stats_materiales['total_materiales'] ?? 0; ?></span></td>
                        </tr>
                        <tr>
                            <td>Stock Bajo</td>
                            <td><span class="badge bg-warning"><?php echo $stats_materiales['stock_bajo'] ?? 0; ?></span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Acceso Rápido -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-lightning me-2"></i>Acceso Rápido</h5>
            </div>
            <div class="row g-3 p-3">
                <div class="col-md-4">
                    <a href="materiales.php" class="text-decoration-none">
                        <div class="d-flex align-items-center p-3 bg-light rounded">
                            <i class="bi bi-box text-primary me-3" style="font-size: 32px;"></i>
                            <div>
                                <h6 class="mb-0">Ver Materiales</h6>
                                <small class="text-muted">Consultar inventario</small>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="solicitudes.php" class="text-decoration-none">
                        <div class="d-flex align-items-center p-3 bg-light rounded">
                            <i class="bi bi-clipboard-check text-warning me-3" style="font-size: 32px;"></i>
                            <div>
                                <h6 class="mb-0">Solicitudes</h6>
                                <small class="text-muted">Gestionar solicitudes</small>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="actas.php" class="text-decoration-none">
                        <div class="d-flex align-items-center p-3 bg-light rounded">
                            <i class="bi bi-file-text text-success me-3" style="font-size: 32px;"></i>
                            <div>
                                <h6 class="mb-0">Actas Técnicas</h6>
                                <small class="text-muted">Ver documentos</small>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
