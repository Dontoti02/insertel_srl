<?php
/**
 * Gestión de Solicitudes - Asistente de Almacén
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';

if (!tieneRol(ROL_ASISTENTE_ALMACEN)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();

// Obtener solicitudes
$query = "SELECT s.*, u.nombre_completo as tecnico_nombre, COUNT(sd.id) as total_items
          FROM solicitudes s
          LEFT JOIN usuarios u ON s.tecnico_id = u.id
          LEFT JOIN solicitudes_detalle sd ON s.id = sd.solicitud_id
          WHERE s.sede_id = :sede_id
          GROUP BY s.id
          ORDER BY s.fecha_solicitud DESC";
$stmt = $db->prepare($query);
$sede_actual = obtenerSedeActual();
$stmt->bindParam(':sede_id', $sede_actual);
$stmt->execute();
$solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar por estado
$pendientes = count(array_filter($solicitudes, function($s) { return $s['estado'] === 'pendiente'; }));
$aprobadas = count(array_filter($solicitudes, function($s) { return $s['estado'] === 'aprobada'; }));
$rechazadas = count(array_filter($solicitudes, function($s) { return $s['estado'] === 'rechazada'; }));

$page_title = "Gestión de Solicitudes";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5><i class="bi bi-file-earmark-check me-2"></i>Gestión de Solicitudes</h5>
                    <p class="text-muted mb-0">Visualice y gestione solicitudes de materiales</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-gradient-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Solicitudes</h6>
                        <h3><?php echo count($solicitudes); ?></h3>
                    </div>
                    <i class="bi bi-file-earmark-check" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-gradient-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Pendientes</h6>
                        <h3><?php echo $pendientes; ?></h3>
                    </div>
                    <i class="bi bi-hourglass-split" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-gradient-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Aprobadas</h6>
                        <h3><?php echo $aprobadas; ?></h3>
                    </div>
                    <i class="bi bi-check-circle" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Solicitudes -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-3">Solicitudes Registradas</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Fecha</th>
                            <th>Técnico</th>
                            <th>Items</th>
                            <th>Motivo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($solicitudes)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No hay solicitudes registradas</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($solicitudes as $solicitud): ?>
                            <tr>
                                <td><strong><?php echo $solicitud['codigo_solicitud'] ?? 'N/A'; ?></strong></td>
                                <td><?php echo formatearFecha($solicitud['fecha_solicitud']); ?></td>
                                <td><?php echo $solicitud['tecnico_nombre'] ?? 'N/A'; ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $solicitud['total_items']; ?></span>
                                </td>
                                <td><?php echo substr($solicitud['motivo'] ?? '', 0, 50) . '...'; ?></td>
                                <td>
                                    <?php
                                    $badge_class = match($solicitud['estado']) {
                                        'pendiente' => 'bg-warning',
                                        'aprobada' => 'bg-success',
                                        'rechazada' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($solicitud['estado']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gradient-info {
    background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
}
.bg-gradient-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
}
.bg-gradient-success {
    background: linear-gradient(135deg, #10b981 0%, #14b8a6 100%);
}
</style>

<?php include '../layouts/footer.php'; ?>
