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

// Procesar nueva acta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo_acta = generarCodigo('ACT-');
    $fecha_servicio = $_POST['fecha_servicio'];
    $cliente = sanitizar($_POST['cliente']);
    $direccion_servicio = sanitizar($_POST['direccion_servicio']);
    $tipo_servicio = sanitizar($_POST['tipo_servicio']);
    $descripcion_trabajo = sanitizar($_POST['descripcion_trabajo']);
    $materiales_utilizados = sanitizar($_POST['materiales_utilizados'] ?? '');
    $observaciones = sanitizar($_POST['observaciones'] ?? '');
    $estado = sanitizar($_POST['estado'] ?? 'finalizada');
    
    $query = "INSERT INTO actas_tecnicas 
              (codigo_acta, tecnico_id, fecha_servicio, cliente, direccion_servicio, 
               tipo_servicio, descripcion_trabajo, materiales_utilizados, observaciones, estado) 
              VALUES (:codigo, :tecnico_id, :fecha, :cliente, :direccion, :tipo, :descripcion, 
                      :materiales, :observaciones, :estado)";
    
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
        ':estado' => $estado
    ])) {
        registrarActividad($tecnico_id, 'crear', 'actas', "Acta creada: {$codigo_acta} - Estado: {$estado}");
        setMensaje('success', 'Acta técnica registrada exitosamente');
        redirigir('views/tecnico/actas.php');
    } else {
        setMensaje('danger', 'Error al registrar el acta');
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
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Nueva Acta -->
<div class="modal fade" id="modalNuevaActa" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
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
                            <option value="Reparación">Reparación</option>
                            <option value="Configuración">Configuración</option>
                            <option value="Soporte Tecnico">Soporte Tecnico</option>
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
