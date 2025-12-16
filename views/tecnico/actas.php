<?php

/**
 * Mis Actas Técnicas - Técnico
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';

if (!tieneRol(ROL_TECNICO)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();
$tecnico_id = $_SESSION['usuario_id'];

// Procesar peticiones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CASO 1: ELIMINAR ACTA
    if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
        $acta_id = (int)$_POST['acta_id'];

        // Verificar que el acta pertenezca al técnico
        $query_check = "SELECT id, foto_acta FROM actas_tecnicas WHERE id = :id AND tecnico_id = :tecnico_id";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->execute([':id' => $acta_id, ':tecnico_id' => $tecnico_id]);
        $acta_existente = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($acta_existente) {
            // Eliminar foto si existe
            if (!empty($acta_existente['foto_acta']) && file_exists(ACTAS_PATH . $acta_existente['foto_acta'])) {
                unlink(ACTAS_PATH . $acta_existente['foto_acta']);
            }

            $query = "DELETE FROM actas_tecnicas WHERE id = :id";
            $stmt = $db->prepare($query);
            if ($stmt->execute([':id' => $acta_id])) {
                registrarActividad($tecnico_id, 'eliminar', 'actas', "Acta eliminada: ID {$acta_id}");
                setMensaje('success', 'Acta técnica eliminada exitosamente');
            } else {
                setMensaje('danger', 'Error al eliminar el acta');
            }
        } else {
            setMensaje('danger', 'No tienes permiso para eliminar esta acta o no existe');
        }
        redirigir('views/tecnico/actas.php');
    }

    // CASO 2: CREAR NUEVA ACTA (Si no es eliminar)
    else {
        $codigo_acta = generarCodigo('ACT-');
        $fecha_servicio = $_POST['fecha_servicio'] ?? date('Y-m-d');
        $cliente = sanitizar($_POST['cliente'] ?? '');
        $direccion_servicio = sanitizar($_POST['direccion_servicio'] ?? '');
        $tipo_servicio = sanitizar($_POST['tipo_servicio'] ?? '');
        $descripcion_trabajo = sanitizar($_POST['descripcion_trabajo'] ?? '');
        $materiales_utilizados = sanitizar($_POST['materiales_utilizados'] ?? '');
        $observaciones = sanitizar($_POST['observaciones'] ?? '');
        $estado = sanitizar($_POST['estado'] ?? 'finalizada');

        // Validar campos requeridos
        if (empty($cliente) || empty($direccion_servicio) || empty($tipo_servicio)) {
            setMensaje('danger', 'Por favor complete todos los campos obligatorios');
        } else {
            // Procesar foto del acta
            $foto_acta = null;
            if (isset($_FILES['foto_acta']) && $_FILES['foto_acta']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
                $file_type = $_FILES['foto_acta']['type'];

                if (in_array($file_type, $allowed_types)) {
                    $extension = pathinfo($_FILES['foto_acta']['name'], PATHINFO_EXTENSION);
                    $foto_nombre = $codigo_acta . '_' . time() . '.' . $extension;
                    $upload_path = ACTAS_PATH . $foto_nombre;

                    if (!file_exists(ACTAS_PATH)) {
                        mkdir(ACTAS_PATH, 0755, true);
                    }

                    if (move_uploaded_file($_FILES['foto_acta']['tmp_name'], $upload_path)) {
                        $foto_acta = $foto_nombre;
                    }
                }
            }

            $query = "INSERT INTO actas_tecnicas 
                      (codigo_acta, tecnico_id, fecha_servicio, cliente, direccion_servicio, 
                       tipo_servicio, descripcion_trabajo, materiales_utilizados, observaciones, estado, foto_acta) 
                      VALUES (:codigo, :tecnico_id, :fecha, :cliente, :direccion, :tipo, :descripcion, 
                              :materiales, :observaciones, :estado, :foto)";

            $stmt = $db->prepare($query);
            if ($stmt->execute([
                ':codigo' => $codigo_acta,
                ':tecnico_id' => $tecnico_id,
                ':fecha' => $fecha_servicio,
                ':cliente' => $cliente,
                ':direccion' => $direccion_servicio,
                ':tipo' => $tipo_servicio,
                ':descripcion' => $descripcion_trabajo,
                ':materiales' => $materiales_utilizados,
                ':observaciones' => $observaciones,
                ':estado' => $estado,
                ':foto' => $foto_acta
            ])) {
                registrarActividad($tecnico_id, 'crear', 'actas', "Acta creada: {$codigo_acta} - Estado: {$estado}");
                setMensaje('success', 'Acta técnica registrada exitosamente');
                redirigir('views/tecnico/actas.php');
            } else {
                setMensaje('danger', 'Error al registrar el acta');
            }
        }
    }
}

// Obtener actas del técnico
$query = "SELECT * FROM actas_tecnicas 
          WHERE tecnico_id = :tecnico_id 
          ORDER BY fecha_servicio DESC";
$stmt = $db->prepare($query);
$stmt->execute([':tecnico_id' => $tecnico_id]);
$mis_actas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Mis Actas Técnicas";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-file-text me-2"></i>Mis Actas Técnicas</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaActa">
                    <i class="bi bi-plus-circle me-2"></i>Nueva Acta
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="content-card">
            <?php if (empty($mis_actas)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-file-text text-muted" style="font-size: 48px;"></i>
                    <p class="text-muted mt-3">No has registrado actas técnicas aún</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaActa">
                        <i class="bi bi-plus-circle me-2"></i>Registrar Primera Acta
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Fecha Servicio</th>
                                <th>Cliente</th>
                                <th>Tipo Servicio</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mis_actas as $acta): ?>
                                <tr>
                                    <td><code><?php echo $acta['codigo_acta']; ?></code></td>
                                    <td><?php echo formatearFecha($acta['fecha_servicio']); ?></td>
                                    <td><?php echo $acta['cliente']; ?></td>
                                    <td><?php echo $acta['tipo_servicio']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $acta['estado'] == 'finalizada' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($acta['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalDetalleActa<?php echo $acta['id']; ?>">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEliminarActa"
                                            data-acta-id="<?php echo $acta['id']; ?>"
                                            data-acta-codigo="<?php echo $acta['codigo_acta']; ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- Modal Detalle Acta -->
                                <div class="modal fade" id="modalDetalleActa<?php echo $acta['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Detalle de Acta: <?php echo $acta['codigo_acta']; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="fw-bold">Fecha de Servicio:</label>
                                                        <p><?php echo formatearFecha($acta['fecha_servicio']); ?></p>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="fw-bold">Cliente:</label>
                                                        <p><?php echo $acta['cliente']; ?></p>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="fw-bold">Tipo de Servicio:</label>
                                                        <p><?php echo $acta['tipo_servicio']; ?></p>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="fw-bold">Estado:</label>
                                                        <p>
                                                            <span class="badge bg-<?php echo $acta['estado'] == 'finalizada' ? 'success' : 'warning'; ?>">
                                                                <?php echo ucfirst($acta['estado']); ?>
                                                            </span>
                                                        </p>
                                                    </div>
                                                    <div class="col-12 mb-3">
                                                        <label class="fw-bold">Dirección:</label>
                                                        <p><?php echo $acta['direccion_servicio']; ?></p>
                                                    </div>
                                                    <div class="col-12 mb-3">
                                                        <label class="fw-bold">Descripción del Trabajo:</label>
                                                        <p class="bg-light p-2 rounded"><?php echo nl2br($acta['descripcion_trabajo']); ?></p>
                                                    </div>
                                                    <div class="col-12 mb-3">
                                                        <label class="fw-bold">Materiales Utilizados:</label>
                                                        <p class="bg-light p-2 rounded"><?php echo nl2br($acta['materiales_utilizados'] ?? 'Ninguno'); ?></p>
                                                    </div>
                                                    <div class="col-12 mb-3">
                                                        <label class="fw-bold">Observaciones:</label>
                                                        <p class="bg-light p-2 rounded"><?php echo nl2br($acta['observaciones'] ?? 'Ninguna'); ?></p>
                                                    </div>

                                                    <?php if (!empty($acta['foto_acta'])): ?>
                                                        <div class="col-12 mb-3">
                                                            <label class="fw-bold mb-2">Foto del Acta:</label>
                                                            <div class="text-center">
                                                                <img src="<?php echo BASE_URL . 'uploads/actas/' . $acta['foto_acta']; ?>"
                                                                    class="img-fluid rounded border shadow-sm"
                                                                    style="max-height: 500px;"
                                                                    alt="Foto del Acta">
                                                                <div class="mt-2">
                                                                    <a href="<?php echo BASE_URL . 'uploads/actas/' . $acta['foto_acta']; ?>"
                                                                        target="_blank"
                                                                        class="btn btn-sm btn-outline-primary">
                                                                        <i class="bi bi-arrows-fullscreen me-1"></i>Ver Imagen Completa
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Eliminar Acta -->
<div class="modal fade" id="modalEliminarActa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="acta_id" id="eliminar_acta_id">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar el acta <strong id="eliminar_acta_codigo"></strong>?</p>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Esta acción no se puede deshacer. Se eliminará el registro y la foto asociada.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar Definitivamente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modalEliminar = document.getElementById('modalEliminarActa');
        modalEliminar.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const actaId = button.getAttribute('data-acta-id');
            const actaCodigo = button.getAttribute('data-acta-codigo');

            document.getElementById('eliminar_acta_id').value = actaId;
            document.getElementById('eliminar_acta_codigo').textContent = actaCodigo;
        });
    });
</script>

<!-- Modal Nueva Acta -->
<div class="modal fade" id="modalNuevaActa" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Acta Técnica</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha de Servicio *</label>
                            <input type="date" name="fecha_servicio" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cliente *</label>
                            <input type="text" name="cliente" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dirección del Servicio *</label>
                        <input type="text" name="direccion_servicio" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de Servicio *</label>
                        <select name="tipo_servicio" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="Instalación">Instalación</option>
                            <option value="Mantenimiento">Mantenimiento</option>
                            <option value="Postventa">Postventa</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción del Trabajo Realizado *</label>
                        <textarea name="descripcion_trabajo" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Materiales Utilizados</label>
                        <textarea name="materiales_utilizados" class="form-control" rows="3"
                            placeholder="Liste los materiales utilizados en el servicio"></textarea>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Puede liquidar materiales posteriormente desde el módulo de liquidación
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Foto del Acta</label>
                        <input type="file" name="foto_acta" class="form-control" accept="image/jpeg,image/jpg,image/png">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Formatos permitidos: JPG, JPEG, PNG (Máx. 5MB)
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado del Acta *</label>
                        <select name="estado" class="form-select" required>
                            <option value="finalizada">Finalizada</option>
                            <option value="pendiente">Pendiente</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Selecciona "Finalizada" si completaste el servicio, o "Pendiente" si aún hay tareas pendientes.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Registrar Acta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>