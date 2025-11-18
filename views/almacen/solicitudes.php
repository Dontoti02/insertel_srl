<?php
/**
 * Gestion de Solicitudes - Jefe de Almacen
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/Solicitud.php';
require_once '../../models/Material.php';

if (!tieneAlgunRol([ROL_JEFE_ALMACEN, ROL_ADMINISTRADOR])) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();
$solicitudModel = new Solicitud($db);

// Ver detalle de solicitud
$ver_solicitud = null;
if (isset($_GET['ver'])) {
    $ver_solicitud = $solicitudModel->obtenerDetalleCompleto((int)$_GET['ver']);
}

// Procesar aprobación/rechazo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $solicitud_id = (int)$_POST['solicitud_id'];
    
    if ($accion === 'aprobar') {
        $comentario = sanitizar($_POST['comentario'] ?? '');
        $detalles_aprobados = $_POST['cantidad_aprobada'] ?? [];
        
        if ($solicitudModel->aprobar($solicitud_id, $_SESSION['usuario_id'], $detalles_aprobados, $comentario)) {
            registrarActividad($_SESSION['usuario_id'], 'aprobar', 'solicitudes', "Solicitud aprobada ID: {$solicitud_id}");
            setMensaje('success', 'Solicitud aprobada exitosamente');
        } else {
            setMensaje('danger', 'Error al aprobar la solicitud');
        }
        redirigir('views/almacen/solicitudes.php');
    }
    
    if ($accion === 'rechazar') {
        $comentario = sanitizar($_POST['comentario']);
        
        if (empty($comentario)) {
            setMensaje('danger', 'Debe proporcionar un motivo de rechazo');
        } else {
            if ($solicitudModel->rechazar($solicitud_id, $_SESSION['usuario_id'], $comentario)) {
                registrarActividad($_SESSION['usuario_id'], 'rechazar', 'solicitudes', "Solicitud rechazada ID: {$solicitud_id}");
                setMensaje('success', 'Solicitud rechazada');
            } else {
                setMensaje('danger', 'Error al rechazar la solicitud');
            }
        }
        redirigir('views/almacen/solicitudes.php');
    }
    
    if ($accion === 'eliminar') {
        // Verificar que el usuario tenga permisos para eliminar
        if (!tieneAlgunRol([ROL_ADMINISTRADOR, ROL_JEFE_ALMACEN])) {
            setMensaje('danger', 'No tiene permisos para eliminar solicitudes');
            redirigir('views/almacen/solicitudes.php');
            exit;
        }
        
        $resultado = $solicitudModel->eliminarSeguro($solicitud_id);
        
        if ($resultado['success']) {
            registrarActividad($_SESSION['usuario_id'], 'eliminar', 'solicitudes', "Solicitud eliminada ID: {$solicitud_id}");
            setMensaje('success', $resultado['message']);
        } else {
            setMensaje('danger', $resultado['message']);
        }
        redirigir('views/almacen/solicitudes.php');
    }
}

// Obtener filtros
$filtros = [];
if (!empty($_GET['estado'])) {
    $filtros['estado'] = $_GET['estado'];
}

$solicitudes = $solicitudModel->obtenerTodas($filtros);

$page_title = "Gestión de Solicitudes";
include '../layouts/header.php';
?>

<?php if ($ver_solicitud): ?>
<!-- Modal Ver Solicitud -->
<div class="modal fade show" id="modalVerSolicitud" tabindex="-1" style="display: block;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-clipboard-check me-2"></i>
                    Solicitud <?php echo $ver_solicitud['codigo_solicitud']; ?>
                </h5>
                <a href="solicitudes.php" class="btn-close btn-close-white"></a>
            </div>
            <div class="modal-body">
                <!-- Información General -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <strong>Tecnico:</strong><br>
                        <?php echo $ver_solicitud['tecnico_nombre']; ?><br>
                        <?php if (!empty($ver_solicitud['tecnico_email'])): ?>
                        <small class="text-muted"><?php echo $ver_solicitud['tecnico_email']; ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Fecha:</strong><br>
                        <?php echo formatearFechaHora($ver_solicitud['fecha_solicitud']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Estado:</strong><br>
                        <span class="badge bg-<?php echo getBadgeEstado($ver_solicitud['estado']); ?>">
                            <?php echo ucfirst($ver_solicitud['estado']); ?>
                        </span>
                    </div>
                </div>

                <div class="mb-4">
                    <strong>Motivo:</strong>
                    <p class="mb-0"><?php echo $ver_solicitud['motivo']; ?></p>
                </div>

                <?php if ($ver_solicitud['justificacion']): ?>
                <div class="mb-4">
                    <strong>Justificación:</strong>
                    <p class="mb-0"><?php echo $ver_solicitud['justificacion']; ?></p>
                </div>
                <?php endif; ?>

                <!-- Detalles de Materiales -->
                <strong>Materiales Solicitados:</strong>
                <form method="POST" id="formProcesar">
                    <input type="hidden" name="solicitud_id" value="<?php echo $ver_solicitud['id']; ?>">
                    
                    <div class="table-responsive mt-2">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Material</th>
                                    <th>Unidad</th>
                                    <th>Solicitado</th>
                                    <th>Stock Disponible</th>
                                    <?php if ($ver_solicitud['estado'] == 'pendiente'): ?>
                                    <th>Cantidad a Aprobar</th>
                                    <?php else: ?>
                                    <th>Cantidad Aprobada</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ver_solicitud['detalles'] as $detalle): ?>
                                <tr>
                                    <td><?php echo $detalle['material_nombre']; ?></td>
                                    <td><?php echo $detalle['unidad']; ?></td>
                                    <td><strong><?php echo $detalle['cantidad_solicitada']; ?></strong></td>
                                    <td>
                                        <?php if ($detalle['stock_actual'] < $detalle['cantidad_solicitada']): ?>
                                        <span class="badge bg-warning">
                                            <?php echo $detalle['stock_actual']; ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="badge bg-success">
                                            <?php echo $detalle['stock_actual']; ?>
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($ver_solicitud['estado'] == 'pendiente'): ?>
                                        <input type="number" 
                                               name="cantidad_aprobada[<?php echo $detalle['id']; ?>]" 
                                               class="form-control form-control-sm" 
                                               min="0" 
                                               max="<?php echo min($detalle['cantidad_solicitada'], $detalle['stock_actual']); ?>"
                                               value="<?php echo min($detalle['cantidad_solicitada'], $detalle['stock_actual']); ?>"
                                               style="width: 100px;">
                                        <?php else: ?>
                                        <span class="badge bg-info"><?php echo $detalle['cantidad_aprobada']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($ver_solicitud['estado'] == 'pendiente'): ?>
                    <div class="mb-3">
                        <label class="form-label">Comentarios / Observaciones:</label>
                        <textarea name="comentario" class="form-control" rows="2" 
                                  placeholder="Opcional: agregue comentarios sobre la aprobación..."></textarea>
                    </div>
                    <?php endif; ?>

                    <?php if ($ver_solicitud['estado'] != 'pendiente' && $ver_solicitud['comentario_respuesta']): ?>
                    <div class="alert alert-info">
                        <strong>Comentarios:</strong><br>
                        <?php echo $ver_solicitud['comentario_respuesta']; ?><br>
                        <small class="text-muted">
                            Por <?php echo $ver_solicitud['respondido_por']; ?> 
                            el <?php echo formatearFechaHora($ver_solicitud['fecha_respuesta']); ?>
                        </small>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
            <div class="modal-footer">
                <a href="solicitudes.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Volver
                </a>
                <?php if ($ver_solicitud['estado'] == 'pendiente'): ?>
                    <?php if (tieneAlgunRol([ROL_ADMINISTRADOR, ROL_JEFE_ALMACEN])): ?>
                    <button type="button" class="btn btn-outline-danger" onclick="confirmarEliminacion(<?php echo $ver_solicitud['id']; ?>, '<?php echo htmlspecialchars($ver_solicitud['codigo_solicitud']); ?>')">
                        <i class="bi bi-trash me-1"></i>Eliminar
                    </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-danger" onclick="rechazarSolicitud()">
                        <i class="bi bi-x-circle me-1"></i>Rechazar
                    </button>
                    <button type="button" class="btn btn-success" onclick="aprobarSolicitud()">
                        <i class="bi bi-check-circle me-1"></i>Aprobar
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<div class="modal-backdrop fade show"></div>

<script>
function aprobarSolicitud() {
    if (confirm('¿Está seguro de aprobar esta solicitud?')) {
        const form = document.getElementById('formProcesar');
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'accion';
        input.value = 'aprobar';
        form.appendChild(input);
        form.submit();
    }
}

function rechazarSolicitud() {
    const comentario = prompt('Ingrese el motivo del rechazo:');
    if (comentario && comentario.trim()) {
        const form = document.getElementById('formProcesar');
        const inputAccion = document.createElement('input');
        inputAccion.type = 'hidden';
        inputAccion.name = 'accion';
        inputAccion.value = 'rechazar';
        
        const inputComentario = document.createElement('input');
        inputComentario.type = 'hidden';
        inputComentario.name = 'comentario';
        inputComentario.value = comentario;
        
        form.appendChild(inputAccion);
        form.appendChild(inputComentario);
        form.submit();
    } else {
        alert('Debe ingresar un motivo de rechazo');
    }
}
</script>
<?php endif; ?>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">
                    <i class="bi bi-clipboard-check me-2"></i>
                    Solicitudes de Materiales
                </h5>
                <form method="GET" class="d-flex gap-2">
                    <select name="estado" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        <option value="pendiente" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'pendiente') ? 'selected' : ''; ?>>
                            Pendientes
                        </option>
                        <option value="aprobada" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'aprobada') ? 'selected' : ''; ?>>
                            Aprobadas
                        </option>
                        <option value="rechazada" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'rechazada') ? 'selected' : ''; ?>>
                            Rechazadas
                        </option>
                    </select>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de solicitudes -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Tecnico</th>
                            <th>Sede</th>
                            <th>Fecha</th>
                            <th>Motivo</th>
                            <th>Materiales</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($solicitudes)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No hay solicitudes registradas
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($solicitudes as $sol): ?>
                            <tr>
                                <td><code><?php echo $sol['codigo_solicitud']; ?></code></td>
                                <td><?php echo $sol['tecnico_nombre']; ?></td>
                                <td><?php echo formatearFecha($sol['fecha_solicitud']); ?></td>
                                <td><?php echo substr($sol['motivo'], 0, 30) . '...'; ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $sol['total_materiales']; ?> items</span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo getBadgeEstado($sol['estado']); ?>">
                                        <?php echo ucfirst($sol['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="solicitudes.php?ver=<?php echo $sol['id']; ?>" class="btn btn-outline-primary" title="Ver detalle">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($sol['estado'] === 'pendiente' && tieneAlgunRol([ROL_ADMINISTRADOR, ROL_JEFE_ALMACEN])): ?>
                                        <button type="button" class="btn btn-outline-danger" onclick="confirmarEliminacion(<?php echo $sol['id']; ?>, '<?php echo htmlspecialchars($sol['codigo_solicitud']); ?>')" title="Eliminar solicitud">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
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

<!-- Modal de Confirmación de Eliminación -->
<div class="modal fade" id="modalEliminarSolicitud" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>¡Atención!</strong> Esta acción no se puede deshacer.
                </div>
                <p>¿Está seguro que desea eliminar la solicitud <strong id="codigoSolicitudEliminar"></strong>?</p>
                <p class="text-muted small">Solo se pueden eliminar solicitudes en estado pendiente.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>
                    Cancelar
                </button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="solicitud_id" id="solicitudIdEliminar">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>
                        Eliminar Solicitud
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarEliminacion(solicitudId, codigoSolicitud) {
    document.getElementById('solicitudIdEliminar').value = solicitudId;
    document.getElementById('codigoSolicitudEliminar').textContent = codigoSolicitud;
    
    const modal = new bootstrap.Modal(document.getElementById('modalEliminarSolicitud'));
    modal.show();
}
</script>

<?php include '../layouts/footer.php'; ?>
