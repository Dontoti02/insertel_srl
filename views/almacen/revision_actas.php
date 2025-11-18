<?php
/**
 * Revisión de Actas Técnicas - Jefe de Almacén
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/Acta.php';

if (!tieneRol(ROL_JEFE_ALMACEN)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();
$actaModel = new Acta($db);

// Procesar aprobación/rechazo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_acta'])) {
    $acta_id = (int)$_POST['acta_id'];
    $estado = $_POST['estado'];
    $observaciones = sanitizar($_POST['observaciones_admin'] ?? '');

    if ($actaModel->actualizarEstado($acta_id, $estado, $observaciones)) {
        registrarActividad($_SESSION['usuario_id'], 'actualizar', 'acta_tecnica', "Acta ID {$acta_id} {$estado} por Jefe de Almacén");
        setMensaje('success', "Acta {$estado} correctamente.");
    } else {
        setMensaje('danger', "Error al {$estado} el acta.");
    }
    redirigir('views/almacen/revision_actas.php');
}

$actas_pendientes = $actaModel->obtenerTodas('pendiente');
$actas_revisadas = $actaModel->obtenerTodas(['aprobada', 'rechazada']); // Obtener aprobadas y rechazadas

$page_title = "Revisión de Actas";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <h5><i class="bi bi-file-earmark-check me-2"></i>Actas Pendientes de Revisión</h5>
            <p class="text-muted mb-0">Actas reportadas por asistentes que requieren tu aprobación</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="content-card">
            <?php if (empty($actas_pendientes)): ?>
            <div class="text-center py-5">
                <i class="bi bi-check-circle text-success" style="font-size: 48px;"></i>
                <p class="text-muted mt-3">No hay actas pendientes de revisión</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Técnico</th>
                            <th>Reportado por</th>
                            <th>Cliente</th>
                            <th>Fecha Servicio</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($actas_pendientes as $acta): ?>
                        <tr>
                            <td><code><?php echo $acta['codigo_acta']; ?></code></td>
                            <td><?php echo $acta['tecnico_nombre']; ?></td>
                            <td><?php echo $acta['reporta_nombre']; ?></td>
                            <td><?php echo $acta['cliente'] ?? '-'; ?></td>
                            <td><?php echo formatearFecha($acta['fecha_servicio']); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalRevisarActa" data-acta-id="<?php echo $acta['id']; ?>" data-acta-codigo="<?php echo $acta['codigo_acta']; ?>" data-accion="aprobada">
                                    <i class="bi bi-check-circle me-1"></i> Aprobar
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalRevisarActa" data-acta-id="<?php echo $acta['id']; ?>" data-acta-codigo="<?php echo $acta['codigo_acta']; ?>" data-accion="rechazada">
                                    <i class="bi bi-x-circle me-1"></i> Rechazar
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalVerActa" data-acta-id="<?php echo $acta['id']; ?>">
                                    <i class="bi bi-eye me-1"></i> Ver
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row mt-5">
    <div class="col-12">
        <div class="content-card">
            <h5><i class="bi bi-file-earmark-ruled me-2"></i>Historial de Actas Revisadas</h5>
            <p class="text-muted mb-0">Actas que ya han sido aprobadas o rechazadas</p>
            <?php if (empty($actas_revisadas)): ?>
            <div class="text-center py-5">
                <i class="bi bi-journal-check text-muted" style="font-size: 48px;"></i>
                <p class="text-muted mt-3">No hay actas revisadas en el historial</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Técnico</th>
                            <th>Reportado por</th>
                            <th>Cliente</th>
                            <th>Estado</th>
                            <th>Obs. Admin</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($actas_revisadas as $acta): ?>
                        <tr>
                            <td><code><?php echo $acta['codigo_acta']; ?></code></td>
                            <td><?php echo $acta['tecnico_nombre']; ?></td>
                            <td><?php echo $acta['reporta_nombre']; ?></td>
                            <td><?php echo $acta['cliente'] ?? '-'; ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    if ($acta['estado'] == 'aprobada') echo 'success';
                                    else if ($acta['estado'] == 'rechazada') echo 'danger';
                                    else echo 'warning';
                                ?>">
                                    <?php echo ucfirst($acta['estado']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($acta['observaciones_admin'] ?? '-'); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalVerActa" data-acta-id="<?php echo $acta['id']; ?>">
                                    <i class="bi bi-eye me-1"></i> Ver
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Revisar Acta (Aprobar/Rechazar) -->
<div class="modal fade" id="modalRevisarActa" tabindex="-1" aria-labelledby="modalRevisarActaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRevisarActaLabel">Revisar Acta Técnica</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formRevisarActa" method="POST" action="revision_actas.php">
                <div class="modal-body">
                    <input type="hidden" name="acta_id" id="acta_id_revision">
                    <input type="hidden" name="estado" id="estado_revision">
                    <p>Estás a punto de <strong id="accion_texto"></strong> el acta con código <code id="acta_codigo_revision"></code>.</p>
                    <div class="mb-3">
                        <label for="observaciones_admin" class="form-label">Observaciones (Opcional)</label>
                        <textarea name="observaciones_admin" id="observaciones_admin" class="form-control" rows="3" placeholder="Añade comentarios sobre la aprobación o rechazo..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="accion_acta" class="btn btn-primary" id="btnConfirmarRevision">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Acta (reutiliza el de asistente, pero se carga aquí) -->
<div class="modal fade" id="modalVerActa" tabindex="-1" aria-labelledby="modalVerActaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalVerActaLabel">
                    <i class="bi bi-file-earmark-text me-2"></i>Detalles del Acta
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="contenidoActaDetalleJefe">
                <!-- Contenido cargado dinámicamente por AJAX -->
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modalRevisarActa = document.getElementById('modalRevisarActa');
    modalRevisarActa.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var actaId = button.getAttribute('data-acta-id');
        var actaCodigo = button.getAttribute('data-acta-codigo');
        var accion = button.getAttribute('data-accion'); // 'aprobada' o 'rechazada'

        var actaIdInput = modalRevisarActa.querySelector('#acta_id_revision');
        var estadoInput = modalRevisarActa.querySelector('#estado_revision');
        var accionTexto = modalRevisarActa.querySelector('#accion_texto');
        var actaCodigoSpan = modalRevisarActa.querySelector('#acta_codigo_revision');
        var btnConfirmar = modalRevisarActa.querySelector('#btnConfirmarRevision');

        actaIdInput.value = actaId;
        estadoInput.value = accion;
        actaCodigoSpan.textContent = actaCodigo;
        accionTexto.textContent = (accion === 'aprobada' ? 'aprobar' : 'rechazar');
        btnConfirmar.className = 'btn ' + (accion === 'aprobada' ? 'btn-success' : 'btn-danger');
    });

    var modalVerActaJefe = document.getElementById('modalVerActa');
    modalVerActaJefe.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var actaId = button.getAttribute('data-acta-id');
        var contenidoActaDetalle = document.getElementById('contenidoActaDetalleJefe');
        
        contenidoActaDetalle.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>
        `;

        // Cargar detalles del acta vía AJAX
        fetch('<?php echo $base_url; ?>ajax/obtener_detalle_acta.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'acta_id=' + actaId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.acta) {
                const acta = data.acta;
                contenidoActaDetalle.innerHTML = `
                    <p><strong>Código:</strong> <code>${acta.codigo_acta}</code></p>
                    <p><strong>Técnico:</strong> ${acta.tecnico_nombre}</p>
                    <p><strong>Reportado por:</strong> ${acta.reporta_nombre}</p>
                    <p><strong>Cliente:</strong> ${acta.cliente}</p>
                    <p><strong>Fecha Servicio:</strong> ${acta.fecha_servicio}</p>
                    <p><strong>Tipo Servicio:</strong> ${acta.tipo_servicio}</p>
                    <p><strong>Descripción:</strong> ${acta.descripcion}</p>
                    <p><strong>Estado:</strong> <span class="badge bg-${
                        acta.estado == 'aprobada' ? 'success' : 
                        acta.estado == 'rechazada' ? 'danger' : 'warning'
                    }">${acta.estado.charAt(0).toUpperCase() + acta.estado.slice(1)}</span></p>
                    ${acta.observaciones_admin ? `<p><strong>Obs. Administrador:</strong> ${acta.observaciones_admin}</p>` : ''}
                    <p><strong>Creado:</strong> ${acta.created_at}</p>
                `;
            } else {
                contenidoActaDetalle.innerHTML = `
                    <div class="alert alert-danger" role="alert">
                        Error al cargar los detalles del acta.
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            contenidoActaDetalle.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    Error de conexión al cargar los detalles del acta.
                </div>
            `;
        });
    });
});
</script>

<?php include '../layouts/footer.php'; ?>
