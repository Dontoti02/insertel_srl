<?php
/**
 * Mi Stock - Técnico
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
$mi_stock = $asignacionModel->obtenerPorTecnico($tecnico_id);

// Calcular valor total
$valor_total = 0;
foreach ($mi_stock as $item) {
    $query = "SELECT costo_unitario FROM materiales WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $item['material_id']]);
    $material = $stmt->fetch(PDO::FETCH_ASSOC);
    $valor_total += ($material['costo_unitario'] ?? 0) * $item['cantidad'];
}

$page_title = "Mi Stock";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="stat-card">
            <div class="icon blue">
                <i class="bi bi-box-seam"></i>
            </div>
            <h3><?php echo count($mi_stock); ?></h3>
            <p>Tipos de Materiales</p>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="stat-card">
            <div class="icon green">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <h3><?php echo formatearMoneda($valor_total); ?></h3>
            <p>Valor Total Asignado</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-box-seam me-2"></i>
                    Materiales en Mi Posesión
                </h5>
            </div>

            <?php if (empty($mi_stock)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox text-muted" style="font-size: 64px;"></i>
                <h5 class="mt-3 text-muted">No tienes materiales asignados</h5>
                <p class="text-muted">Contacta al almacén para que te asignen materiales</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Material</th>
                            <th>Cantidad</th>
                            <th>Unidad</th>
                            <th>Fecha Asignación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mi_stock as $item): ?>
                        <tr>
                            <td><code><?php echo $item['codigo']; ?></code></td>
                            <td><strong><?php echo $item['material_nombre']; ?></strong></td>
                            <td>
                                <span class="badge bg-success" style="font-size: 14px;">
                                    <?php echo $item['cantidad']; ?>
                                </span>
                            </td>
                            <td><?php echo $item['unidad']; ?></td>
                            <td><?php echo formatearFechaHora($item['fecha_asignacion']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="alert alert-info mt-3">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Importante:</strong> Estos materiales están bajo tu responsabilidad. 
                Asegúrate de hacer un uso adecuado y reportar cualquier devolución o consumo.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<?php include '../layouts/footer.php'; ?>
