<?php
/**
 * Tareas Pendientes - Asistente de Almacén
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';

if (!tieneRol(ROL_ASISTENTE_ALMACEN)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();

// Obtener solicitudes pendientes
$query_solicitudes = "SELECT COUNT(*) as total FROM solicitudes WHERE estado = 'pendiente' AND sede_id = :sede_id";
$stmt = $db->prepare($query_solicitudes);
$sede_actual = obtenerSedeActual();
$stmt->bindParam(':sede_id', $sede_actual);
$stmt->execute();
$solicitudes_pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Obtener entradas sin autorizar
$query_entradas = "SELECT COUNT(*) as total FROM entradas_materiales WHERE usuario_id = :usuario_id";
$stmt = $db->prepare($query_entradas);
$usuario_id = $_SESSION['usuario_id'];
$stmt->bindParam(':usuario_id', $usuario_id);
$stmt->execute();
$entradas_pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Obtener salidas sin autorizar
$query_salidas = "SELECT COUNT(*) as total FROM salidas_materiales WHERE usuario_id = :usuario_id";
$stmt = $db->prepare($query_salidas);
$usuario_id = $_SESSION['usuario_id'];
$stmt->bindParam(':usuario_id', $usuario_id);
$stmt->execute();
$salidas_pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Obtener materiales con stock bajo
$query_stock_bajo = "SELECT COUNT(*) as total FROM materiales WHERE stock_actual <= stock_minimo AND estado = 'activo' AND sede_id = :sede_id";
$stmt = $db->prepare($query_stock_bajo);
$sede_actual = obtenerSedeActual();
$stmt->bindParam(':sede_id', $sede_actual);
$stmt->execute();
$stock_bajo = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Obtener materiales próximos a vencer
$query_vencimiento = "SELECT COUNT(*) as total FROM entradas_materiales 
                      WHERE fecha_vencimiento IS NOT NULL 
                      AND fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                      AND fecha_vencimiento >= CURDATE()";
$stmt = $db->query($query_vencimiento);
$proximos_vencer = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$page_title = "Tareas Pendientes";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <h5><i class="bi bi-list-check me-2"></i>Tareas Pendientes</h5>
            <p class="text-muted mb-0">Resumen de actividades pendientes en el almacén</p>
        </div>
    </div>
</div>

<!-- Tarjetas de Tareas -->
<div class="row mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card border-left-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Solicitudes Pendientes</h6>
                        <h3 class="text-warning"><?php echo $solicitudes_pendientes; ?></h3>
                    </div>
                    <i class="bi bi-file-earmark-check" style="font-size: 2rem; color: #f59e0b; opacity: 0.5;"></i>
                </div>
                <a href="solicitudes.php" class="btn btn-sm btn-outline-warning mt-2">Ver</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card border-left-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Entradas sin Autorizar</h6>
                        <h3 class="text-info"><?php echo $entradas_pendientes; ?></h3>
                    </div>
                    <i class="bi bi-arrow-down-circle" style="font-size: 2rem; color: #06b6d4; opacity: 0.5;"></i>
                </div>
                <a href="entradas_materiales.php" class="btn btn-sm btn-outline-info mt-2">Ver</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card border-left-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Salidas sin Autorizar</h6>
                        <h3 class="text-danger"><?php echo $salidas_pendientes; ?></h3>
                    </div>
                    <i class="bi bi-arrow-up-circle" style="font-size: 2rem; color: #ef4444; opacity: 0.5;"></i>
                </div>
                <a href="salidas_materiales.php" class="btn btn-sm btn-outline-danger mt-2">Ver</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card border-left-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Stock Bajo</h6>
                        <h3 class="text-success"><?php echo $stock_bajo; ?></h3>
                    </div>
                    <i class="bi bi-exclamation-triangle" style="font-size: 2rem; color: #10b981; opacity: 0.5;"></i>
                </div>
                <a href="materiales.php" class="btn btn-sm btn-outline-success mt-2">Ver</a>
            </div>
        </div>
    </div>
</div>

<!-- Alertas de Vencimiento -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-left-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Materiales Próximos a Vencer (30 días)</h6>
                        <h3 class="text-danger"><?php echo $proximos_vencer; ?></h3>
                        <small class="text-muted">Requieren revisión y posible reemplazo</small>
                    </div>
                    <i class="bi bi-calendar-x" style="font-size: 2.5rem; color: #ef4444; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resumen de Actividades -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-3">Resumen de Actividades</h5>
            <div class="list-group">
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Revisar Solicitudes Pendientes</h6>
                            <p class="mb-0 text-muted">Hay <?php echo $solicitudes_pendientes; ?> solicitud(es) esperando aprobación</p>
                        </div>
                        <span class="badge bg-warning"><?php echo $solicitudes_pendientes; ?></span>
                    </div>
                </div>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Autorizar Entradas de Materiales</h6>
                            <p class="mb-0 text-muted">Hay <?php echo $entradas_pendientes; ?> entrada(s) pendiente(s) de autorización</p>
                        </div>
                        <span class="badge bg-info"><?php echo $entradas_pendientes; ?></span>
                    </div>
                </div>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Autorizar Salidas de Materiales</h6>
                            <p class="mb-0 text-muted">Hay <?php echo $salidas_pendientes; ?> salida(s) pendiente(s) de autorización</p>
                        </div>
                        <span class="badge bg-danger"><?php echo $salidas_pendientes; ?></span>
                    </div>
                </div>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Revisar Stock Bajo</h6>
                            <p class="mb-0 text-muted">Hay <?php echo $stock_bajo; ?> material(es) con stock bajo</p>
                        </div>
                        <span class="badge bg-success"><?php echo $stock_bajo; ?></span>
                    </div>
                </div>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Revisar Vencimientos</h6>
                            <p class="mb-0 text-muted">Hay <?php echo $proximos_vencer; ?> material(es) próximo(s) a vencer en 30 días</p>
                        </div>
                        <span class="badge bg-danger"><?php echo $proximos_vencer; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-warning {
    border-left: 4px solid #f59e0b;
}
.border-left-info {
    border-left: 4px solid #06b6d4;
}
.border-left-danger {
    border-left: 4px solid #ef4444;
}
.border-left-success {
    border-left: 4px solid #10b981;
}
</style>

<?php include '../layouts/footer.php'; ?>
