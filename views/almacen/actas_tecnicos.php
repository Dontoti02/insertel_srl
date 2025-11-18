<?php
/**
 * Actas Técnicas de Tecnicos - Jefe de Almacén
 * Visualizar todas las actas registradas por los técnicos
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';

if (!tieneAlgunRol([ROL_JEFE_ALMACEN, ROL_ADMINISTRADOR])) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();
// Procesar eliminación de acta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
    $acta_id = (int)$_POST['acta_id'];

    $query = "DELETE FROM actas_tecnicas WHERE id = :id";
    $stmt = $db->prepare($query);
    if ($stmt->execute([':id' => $acta_id])) {
        registrarActividad($_SESSION['usuario_id'], 'eliminar', 'actas_tecnicas', "Acta eliminada: ID {$acta_id}");
        setMensaje('success', 'Acta técnica eliminada exitosamente.');
    } else {
        setMensaje('danger', 'Error al eliminar el acta técnica.');
    }
    redirigir('views/almacen/actas_tecnicos.php');
}

// Filtros
$filtro_tecnico = $_GET['tecnico'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';
$filtro_fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$filtro_fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
$pagina = (int)($_GET['pagina'] ?? 1);
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

// Construir query base
$query_base = "SELECT at.*, u.nombre_completo as tecnico_nombre
              FROM actas_tecnicas at
              INNER JOIN usuarios u ON at.tecnico_id = u.id
              WHERE u.rol_id = :rol_tecnico
              AND DATE(at.fecha_servicio) BETWEEN :fecha_desde AND :fecha_hasta";

$params = [
    ':rol_tecnico' => ROL_TECNICO,
    ':fecha_desde' => $filtro_fecha_desde,
    ':fecha_hasta' => $filtro_fecha_hasta
];

// Aplicar filtros
if (!empty($filtro_tecnico)) {
    $query_base .= " AND at.tecnico_id = :tecnico_id";
    $params[':tecnico_id'] = (int)$filtro_tecnico;
}

if (!empty($filtro_estado)) {
    $query_base .= " AND at.estado = :estado";
    $params[':estado'] = $filtro_estado;
}

// Contar total
$query_count = "SELECT COUNT(*) as total FROM (" . $query_base . ") as count_query";
$stmt_count = $db->prepare($query_count);
$stmt_count->execute($params);
$total_actas = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_actas / $por_pagina);

// Obtener actas con paginación
$query = $query_base . " ORDER BY at.fecha_servicio DESC LIMIT :offset, :limit";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    if (strpos($key, ':fecha') === 0 || strpos($key, ':rol') === 0 || strpos($key, ':estado') === 0) {
        $stmt->bindParam($key, $params[$key]);
    } elseif ($key === ':tecnico_id') {
        $stmt->bindParam($key, $params[$key], PDO::PARAM_INT);
    }
}
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limit', $por_pagina, PDO::PARAM_INT);
$stmt->execute();
$actas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener técnicos para filtro
$query_tecnicos = "SELECT id, nombre_completo FROM usuarios WHERE rol_id = :rol_id ORDER BY nombre_completo";
$stmt_tecnicos = $db->prepare($query_tecnicos);
$rol_tecnico = ROL_TECNICO;
$stmt_tecnicos->bindParam(':rol_id', $rol_tecnico);
$stmt_tecnicos->execute();
$tecnicos = $stmt_tecnicos->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$query_stats = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'finalizada' THEN 1 ELSE 0 END) as finalizadas,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas
                FROM actas_tecnicas
                WHERE tecnico_id IN (SELECT id FROM usuarios WHERE rol_id = :rol_tecnico)";
$stmt_stats = $db->prepare($query_stats);
$rol_tecnico = ROL_TECNICO;
$stmt_stats->bindParam(':rol_tecnico', $rol_tecnico);
$stmt_stats->execute();
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

$page_title = "Actas Técnicas de Tecnicos";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5><i class="bi bi-file-text me-2"></i>Actas Técnicas de Tecnicos</h5>
                    <p class="text-muted mb-0">Visualice todas las actas registradas por los técnicos</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-gradient-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Actas</h6>
                        <h3><?php echo $stats['total'] ?? 0; ?></h3>
                    </div>
                    <i class="bi bi-file-text" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-gradient-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Finalizadas</h6>
                        <h3><?php echo $stats['finalizadas'] ?? 0; ?></h3>
                    </div>
                    <i class="bi bi-check-circle" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-gradient-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Pendientes</h6>
                        <h3><?php echo $stats['pendientes'] ?? 0; ?></h3>
                    </div>
                    <i class="bi bi-hourglass-split" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-gradient-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Canceladas</h6>
                        <h3><?php echo $stats['canceladas'] ?? 0; ?></h3>
                    </div>
                    <i class="bi bi-x-circle" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Técnico</label>
                    <select name="tecnico" class="form-select">
                        <option value="">Todos los técnicos</option>
                        <?php foreach ($tecnicos as $tec): ?>
                        <option value="<?php echo $tec['id']; ?>" <?php echo $filtro_tecnico == $tec['id'] ? 'selected' : ''; ?>>
                            <?php echo $tec['nombre_completo']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="finalizada" <?php echo $filtro_estado === 'finalizada' ? 'selected' : ''; ?>>Finalizada</option>
                        <option value="pendiente" <?php echo $filtro_estado === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="cancelada" <?php echo $filtro_estado === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control" value="<?php echo $filtro_fecha_desde; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $filtro_fecha_hasta; ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-search me-1"></i>Filtrar
                    </button>
                    <a href="actas_tecnicos.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise me-1"></i>Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tabla de Actas -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-3">Actas Registradas (<?php echo $total_actas; ?> total)</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Fecha</th>
                            <th>Técnico</th>
                            <th>Cliente</th>
                            <th>Tipo de Servicio</th>
                            <th>Ubicación</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($actas)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No hay actas registradas</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($actas as $acta): ?>
                            <tr>
                                <td><strong><?php echo $acta['codigo_acta']; ?></strong></td>
                                <td><?php echo formatearFecha($acta['fecha_servicio']); ?></td>
                                <td><?php echo $acta['tecnico_nombre']; ?></td>
                                <td><?php echo $acta['cliente']; ?></td>
                                <td><?php echo ucfirst($acta['tipo_servicio']); ?></td>
                                <td><?php echo substr($acta['direccion_servicio'], 0, 30) . '...'; ?></td>
                                <td>
                                    <?php
                                    $badge_class = match($acta['estado']) {
                                        'finalizada' => 'bg-success',
                                        'pendiente' => 'bg-warning',
                                        'cancelada' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($acta['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#modalDetalles<?php echo $acta['id']; ?>">
                                        <i class="bi bi-eye me-1"></i>Ver
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarActa" data-acta-id="<?php echo $acta['id']; ?>" data-codigo-acta="<?php echo $acta['codigo_acta']; ?>">
                                        <i class="bi bi-trash me-1"></i>Eliminar
                                    </button>
                                </td>
                            </tr>

                            <!-- Modal Detalles -->
                            <div class="modal fade" id="modalDetalles<?php echo $acta['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title">
                                                <i class="bi bi-file-text me-2"></i><?php echo $acta['codigo_acta']; ?>
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label"><strong>Técnico:</strong></label>
                                                    <p><?php echo $acta['tecnico_nombre']; ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label"><strong>Fecha de Servicio:</strong></label>
                                                    <p><?php echo formatearFecha($acta['fecha_servicio']); ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label"><strong>Cliente:</strong></label>
                                                    <p><?php echo $acta['cliente']; ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label"><strong>Tipo de Servicio:</strong></label>
                                                    <p><?php echo ucfirst($acta['tipo_servicio']); ?></p>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label"><strong>Dirección:</strong></label>
                                                    <p><?php echo $acta['direccion_servicio']; ?></p>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label"><strong>Descripción del Trabajo:</strong></label>
                                                    <p><?php echo nl2br($acta['descripcion_trabajo']); ?></p>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label"><strong>Materiales Utilizados:</strong></label>
                                                    <p><?php echo nl2br($acta['materiales_utilizados'] ?? 'N/A'); ?></p>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label"><strong>Observaciones:</strong></label>
                                                    <p><?php echo nl2br($acta['observaciones'] ?? 'N/A'); ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label"><strong>Estado:</strong></label>
                                                    <p>
                                                        <span class="badge <?php echo $badge_class; ?>">
                                                            <?php echo ucfirst($acta['estado']); ?>
                                                        </span>
                                                    </p>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label"><strong>Fecha de Registro:</strong></label>
                                                    <p><?php echo formatearFecha($acta['fecha_creacion'] ?? $acta['created_at']); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($pagina > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=1&tecnico=<?php echo $filtro_tecnico; ?>&estado=<?php echo $filtro_estado; ?>&fecha_desde=<?php echo $filtro_fecha_desde; ?>&fecha_hasta=<?php echo $filtro_fecha_hasta; ?>">Primera</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?>&tecnico=<?php echo $filtro_tecnico; ?>&estado=<?php echo $filtro_estado; ?>&fecha_desde=<?php echo $filtro_fecha_desde; ?>&fecha_hasta=<?php echo $filtro_fecha_hasta; ?>">Anterior</a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $pagina - 2); $i <= min($total_paginas, $pagina + 2); $i++): ?>
                    <li class="page-item <?php echo $i === $pagina ? 'active' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $i; ?>&tecnico=<?php echo $filtro_tecnico; ?>&estado=<?php echo $filtro_estado; ?>&fecha_desde=<?php echo $filtro_fecha_desde; ?>&fecha_hasta=<?php echo $filtro_fecha_hasta; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($pagina < $total_paginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?>&tecnico=<?php echo $filtro_tecnico; ?>&estado=<?php echo $filtro_estado; ?>&fecha_desde=<?php echo $filtro_fecha_desde; ?>&fecha_hasta=<?php echo $filtro_fecha_hasta; ?>">Siguiente</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=<?php echo $total_paginas; ?>&tecnico=<?php echo $filtro_tecnico; ?>&estado=<?php echo $filtro_estado; ?>&fecha_desde=<?php echo $filtro_fecha_desde; ?>&fecha_hasta=<?php echo $filtro_fecha_hasta; ?>">Última</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.bg-gradient-info {
    background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
}
.bg-gradient-success {
    background: linear-gradient(135deg, #10b981 0%, #14b8a6 100%);
}
.bg-gradient-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
}
.bg-gradient-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}
</style>

<!-- Modal Eliminar Acta -->
<div class="modal fade" id="modalEliminarActa" tabindex="-1" aria-labelledby="modalEliminarActaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalEliminarActaLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="acta_id" id="eliminar_acta_id">
                    <p>¿Está seguro de que desea eliminar el acta técnica con código <strong><span id="codigo_acta_eliminar"></span></strong>?</p>
                    <p class="text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar Acta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modalEliminar = document.getElementById('modalEliminarActa');
    modalEliminar.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var actaId = button.getAttribute('data-acta-id');
        var codigoActa = button.getAttribute('data-codigo-acta');

        var modalTitle = modalEliminar.querySelector('.modal-title');
        var actaIdInput = modalEliminar.querySelector('#eliminar_acta_id');
        var codigoActaSpan = modalEliminar.querySelector('#codigo_acta_eliminar');

        actaIdInput.value = actaId;
        codigoActaSpan.textContent = codigoActa;
    });
});
</script>

<?php include '../layouts/footer.php'; ?>
