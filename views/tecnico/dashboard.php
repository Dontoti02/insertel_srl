<?php
/**
 * Dashboard - Técnico
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/AsignacionTecnico.php';

if (!tieneRol(ROL_TECNICO)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();

$asignacionModel = new AsignacionTecnico($db);

$tecnico_id = $_SESSION['usuario_id'];
$sede_id = $_SESSION['sede_id'] ?? null;

// Obtener estadísticas del técnico
$mi_stock = $asignacionModel->obtenerPorTecnico($tecnico_id);

$page_title = "Mi Dashboard";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="icon blue">
                <i class="bi bi-box-seam"></i>
            </div>
            <h3><?php echo count($mi_stock); ?></h3>
            <p>Materiales Asignados</p>
        </div>
    </div>
</div>

<!-- Acciones Rápidas -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-lightning me-2"></i>Acciones Rápidas</h5>
            </div>
            <div class="row g-3 p-3">
                <div class="col-md-6">
                    <a href="mi_stock.php" class="text-decoration-none">
                        <div class="d-flex align-items-center p-4 bg-success bg-opacity-10 rounded border border-success">
                            <i class="bi bi-box-seam text-success me-3" style="font-size: 48px;"></i>
                            <div>
                                <h5 class="mb-1 text-success">Mi Stock</h5>
                                <p class="mb-0 text-muted">Ver materiales asignados</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mi Stock Actual -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-box-seam me-2"></i>Mi Stock Actual</h5>
                <a href="mi_stock.php" class="btn btn-sm btn-primary">Ver Detalle</a>
            </div>
            <?php if (empty($mi_stock)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox text-muted" style="font-size: 48px;"></i>
                <p class="text-muted mt-3">No tienes materiales asignados actualmente</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Material</th>
                            <th>Cantidad</th>
                            <th>Última Actualización</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($mi_stock, 0, 5) as $item): ?>
                        <tr>
                            <td><code><?php echo $item['codigo']; ?></code></td>
                            <td><?php echo $item['material_nombre']; ?></td>
                            <td>
                                <span class="badge bg-success">
                                    <?php echo $item['cantidad']; ?> <?php echo $item['unidad']; ?>
                                </span>
                            </td>
                            <td><?php echo formatearFechaHora($item['updated_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
