<?php

/**
 * Alertas de Equipos - Técnico
 * Muestra alertas de equipos/materiales asignados sin usar por tiempo prolongado
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

// Configuración de días para alertas
$dias_alerta_amarilla = 30; // Alerta amarilla: 30 días sin usar
$dias_alerta_roja = 60;     // Alerta roja: 60 días sin usar

// Obtener materiales/equipos con alertas
$query = "SELECT st.*, m.codigo, m.nombre as material_nombre, m.unidad,
                 DATEDIFF(CURRENT_DATE, st.fecha_asignacion) as dias_sin_usar,
                 COALESCE(
                     (SELECT MAX(fecha_liquidacion) 
                      FROM liquidaciones_materiales 
                      WHERE material_id = st.material_id AND tecnico_id = st.tecnico_id),
                     st.fecha_asignacion
                 ) as ultima_actividad,
                 DATEDIFF(CURRENT_DATE, COALESCE(
                     (SELECT MAX(fecha_liquidacion) 
                      FROM liquidaciones_materiales 
                      WHERE material_id = st.material_id AND tecnico_id = st.tecnico_id),
                     st.fecha_asignacion
                 )) as dias_inactivo
          FROM stock_tecnicos st
          INNER JOIN materiales m ON st.material_id = m.id
          WHERE st.tecnico_id = :tecnico_id AND st.cantidad > 0
          HAVING dias_inactivo >= :dias_minimo
          ORDER BY dias_inactivo DESC";

$stmt = $db->prepare($query);
$stmt->execute([
    ':tecnico_id' => $tecnico_id,
    ':dias_minimo' => $dias_alerta_amarilla
]);
$alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Clasificar alertas por nivel
$alertas_rojas = array_filter($alertas, function ($item) use ($dias_alerta_roja) {
    return $item['dias_inactivo'] >= $dias_alerta_roja;
});

$alertas_amarillas = array_filter($alertas, function ($item) use ($dias_alerta_amarilla, $dias_alerta_roja) {
    return $item['dias_inactivo'] >= $dias_alerta_amarilla && $item['dias_inactivo'] < $dias_alerta_roja;
});

$page_title = "Alertas de Equipos";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Alertas de Equipos y Materiales</h5>
                    <p class="text-muted mb-0 mt-1">Equipos y materiales asignados sin actividad reciente</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas de Alertas -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="icon orange">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <h3><?php echo count($alertas); ?></h3>
            <p>Total Alertas</p>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="icon red">
                <i class="bi bi-exclamation-octagon"></i>
            </div>
            <h3><?php echo count($alertas_rojas); ?></h3>
            <p>Alertas Críticas (>60 días)</p>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="icon yellow">
                <i class="bi bi-exclamation-circle"></i>
            </div>
            <h3><?php echo count($alertas_amarillas); ?></h3>
            <p>Alertas Moderadas (30-60 días)</p>
        </div>
    </div>
</div>

<!-- Alertas Críticas -->
<?php if (!empty($alertas_rojas)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="content-card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-exclamation-octagon me-2"></i>
                        Alertas Críticas - Más de <?php echo $dias_alerta_roja; ?> días sin usar
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Material/Equipo</th>
                                <th>Cantidad</th>
                                <th>Fecha Asignación</th>
                                <th>Última Actividad</th>
                                <th>Días Inactivo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alertas_rojas as $alerta): ?>
                                <tr class="table-danger">
                                    <td><code><?php echo $alerta['codigo']; ?></code></td>
                                    <td><strong><?php echo $alerta['material_nombre']; ?></strong></td>
                                    <td>
                                        <span class="badge bg-danger">
                                            <?php echo $alerta['cantidad']; ?> <?php echo $alerta['unidad']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatearFecha($alerta['fecha_asignacion']); ?></td>
                                    <td><?php echo formatearFecha($alerta['ultima_actividad']); ?></td>
                                    <td>
                                        <span class="badge bg-danger">
                                            <i class="bi bi-clock-history me-1"></i>
                                            <?php echo $alerta['dias_inactivo']; ?> días
                                        </span>
                                    </td>
                                    <td>
                                        <a href="liquidar_materiales.php" class="btn btn-sm btn-primary">
                                            <i class="bi bi-clipboard-check me-1"></i>Liquidar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-danger m-3">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Acción Requerida:</strong> Estos materiales/equipos llevan más de <?php echo $dias_alerta_roja; ?> días sin actividad.
                    Por favor, verifica su uso o considera devolverlos al almacén si no los necesitas.
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Alertas Moderadas -->
<?php if (!empty($alertas_amarillas)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="content-card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        Alertas Moderadas - Entre <?php echo $dias_alerta_amarilla; ?> y <?php echo $dias_alerta_roja; ?> días sin usar
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Material/Equipo</th>
                                <th>Cantidad</th>
                                <th>Fecha Asignación</th>
                                <th>Última Actividad</th>
                                <th>Días Inactivo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alertas_amarillas as $alerta): ?>
                                <tr class="table-warning">
                                    <td><code><?php echo $alerta['codigo']; ?></code></td>
                                    <td><strong><?php echo $alerta['material_nombre']; ?></strong></td>
                                    <td>
                                        <span class="badge bg-warning text-dark">
                                            <?php echo $alerta['cantidad']; ?> <?php echo $alerta['unidad']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatearFecha($alerta['fecha_asignacion']); ?></td>
                                    <td><?php echo formatearFecha($alerta['ultima_actividad']); ?></td>
                                    <td>
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-clock-history me-1"></i>
                                            <?php echo $alerta['dias_inactivo']; ?> días
                                        </span>
                                    </td>
                                    <td>
                                        <a href="liquidar_materiales.php" class="btn btn-sm btn-primary">
                                            <i class="bi bi-clipboard-check me-1"></i>Liquidar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-warning m-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Recomendación:</strong> Estos materiales/equipos están próximos a cumplir <?php echo $dias_alerta_roja; ?> días sin uso.
                    Considera utilizarlos pronto o devolverlos al almacén.
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Sin Alertas -->
<?php if (empty($alertas)): ?>
    <div class="row">
        <div class="col-12">
            <div class="content-card">
                <div class="text-center py-5">
                    <i class="bi bi-check-circle text-success" style="font-size: 64px;"></i>
                    <h5 class="mt-3 text-success">¡Excelente!</h5>
                    <p class="text-muted">No tienes alertas de equipos o materiales sin usar</p>
                    <p class="text-muted">Todos tus materiales asignados tienen actividad reciente</p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Información sobre Alertas -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Información sobre Alertas</h6>
            </div>
            <div class="p-3">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-warning">
                            <i class="bi bi-exclamation-circle me-2"></i>Alerta Moderada
                        </h6>
                        <p class="text-muted small">
                            Se genera cuando un material o equipo lleva entre <?php echo $dias_alerta_amarilla; ?> y <?php echo $dias_alerta_roja; ?> días
                            sin ser utilizado en ningún servicio.
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-danger">
                            <i class="bi bi-exclamation-octagon me-2"></i>Alerta Crítica
                        </h6>
                        <p class="text-muted small">
                            Se genera cuando un material o equipo lleva más de <?php echo $dias_alerta_roja; ?> días
                            sin ser utilizado. Requiere acción inmediata.
                        </p>
                    </div>
                </div>
                <hr>
                <p class="text-muted small mb-0">
                    <i class="bi bi-lightbulb me-2"></i>
                    <strong>Consejo:</strong> Si tienes materiales o equipos que no estás utilizando, considera devolverlos al almacén
                    para que puedan ser asignados a otros técnicos que los necesiten.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    .stat-card .icon.yellow {
        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    }

    .stat-card .icon.red {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    }
</style>

<?php include '../layouts/footer.php'; ?>