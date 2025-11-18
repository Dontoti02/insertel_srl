<?php
/**
 * Alertas de Inventario - Jefe de Almacén
 * Muestra alertas de stock mínimo
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/Material.php';

if (!tieneAlgunRol([ROL_JEFE_ALMACEN, ROL_ADMINISTRADOR, ROL_ASISTENTE_ALMACEN])) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();
$materialModel = new Material($db);

// Obtener configuración de alertas
$query_config = "SELECT * FROM configuraciones_sede WHERE sede_id = :sede_id";
$stmt_config = $db->prepare($query_config);
$sede_actual = obtenerSedeActual();
$stmt_config->bindParam(':sede_id', $sede_actual);
$stmt_config->execute();
$config = [];
foreach ($stmt_config->fetchAll(PDO::FETCH_ASSOC) as $c) {
    $config[$c['clave']] = $c['valor'];
}

$stock_minimo_alerta = $config['stock_minimo_alerta'] ?? 10;

// Obtener materiales con stock bajo
$query_stock_bajo = "SELECT m.*, c.nombre as categoria_nombre,
                            (m.stock_minimo - m.stock_actual) as deficit
                     FROM materiales m
                     LEFT JOIN categorias_materiales c ON m.categoria_id = c.id
                     WHERE m.stock_actual <= m.stock_minimo 
                     AND m.estado = 'activo'";

if (!esSuperAdmin()) {
    $sede_actual = obtenerSedeActual();
    if ($sede_actual) {
        $query_stock_bajo .= " AND m.sede_id = :sede_id";
    }
}

$query_stock_bajo .= " ORDER BY m.stock_actual ASC";

$stmt_stock_bajo = $db->prepare($query_stock_bajo);
if (!esSuperAdmin()) {
    $sede_actual = obtenerSedeActual();
    if ($sede_actual) {
        $stmt_stock_bajo->bindParam(':sede_id', $sede_actual);
    }
}
$stmt_stock_bajo->execute();
$materiales_stock_bajo = $stmt_stock_bajo->fetchAll(PDO::FETCH_ASSOC);

$materiales_vencimiento = [];
$materiales_vencidos = [];

$page_title = "Alertas de Inventario";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5><i class="bi bi-exclamation-triangle me-2"></i>Alertas de Inventario</h5>
                    <p class="text-muted mb-0">Monitoreo de stock mínimo de materiales</p>
                </div>
                <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas de Alertas -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card bg-gradient-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Stock Bajo</h6>
                        <h3><?php echo count($materiales_stock_bajo); ?></h3>
                    </div>
                    <i class="bi bi-exclamation-circle" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alertas de Stock Bajo -->
<?php if (!empty($materiales_stock_bajo)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-3">
                <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                Materiales con Stock Bajo
            </h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Material</th>
                            <th>Categoría</th>
                            <th>Stock Actual</th>
                            <th>Stock Mínimo</th>
                            <th>Déficit</th>
                            <th>Ubicación</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materiales_stock_bajo as $material): ?>
                        <tr class="table-danger">
                            <td><code><?php echo $material['codigo']; ?></code></td>
                            <td><strong><?php echo $material['nombre']; ?></strong></td>
                            <td><?php echo $material['categoria_nombre'] ?? '-'; ?></td>
                            <td>
                                <span class="badge bg-danger">
                                    <?php echo $material['stock_actual']; ?> <?php echo $material['unidad']; ?>
                                </span>
                            </td>
                            <td><?php echo $material['stock_minimo']; ?> <?php echo $material['unidad']; ?></td>
                            <td>
                                <strong class="text-danger">
                                    -<?php echo ($material['deficit'] ?? ($material['stock_minimo'] - $material['stock_actual'])); ?> <?php echo $material['unidad']; ?>
                                </strong>
                            </td>
                            <td><?php echo $material['ubicacion'] ?? '-'; ?></td>
                            <td>
                                <a href="entradas_materiales.php" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-plus-circle me-1"></i>Entrada
                                </a>
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


<!-- Sin Alertas -->
<?php if (empty($materiales_stock_bajo) && empty($materiales_vencimiento) && empty($materiales_vencidos)): ?>
<div class="row">
    <div class="col-12">
        <div class="alert alert-success" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <strong>¡Excelente!</strong> No hay alertas de inventario en este momento. Todo está bajo control.
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.bg-gradient-danger {
    background: linear-gradient(135deg, #ef4444 0%, #ec4899 100%);
}
.bg-gradient-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
}
.bg-gradient-dark {
    background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
}
</style>

<script>
</script>

<?php include '../layouts/footer.php'; ?>
