<?php

/**
 * Liquidar Materiales y Equipos - Técnico
 * Permite al técnico registrar materiales/equipos usados en servicios
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

// Procesar liquidación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'liquidar') {
    $acta_id = (int)$_POST['acta_id'];
    $materiales_usados = $_POST['materiales'] ?? [];

    if (!empty($materiales_usados)) {
        $db->beginTransaction();

        try {
            foreach ($materiales_usados as $material_id => $cantidad) {
                $cantidad = (int)$cantidad;

                if ($cantidad <= 0) continue;

                // Verificar que el técnico tenga el material
                $query_check = "SELECT cantidad FROM stock_tecnicos 
                               WHERE tecnico_id = :tecnico_id AND material_id = :material_id";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->execute([
                    ':tecnico_id' => $tecnico_id,
                    ':material_id' => $material_id
                ]);
                $stock = $stmt_check->fetch(PDO::FETCH_ASSOC);

                if (!$stock || $stock['cantidad'] < $cantidad) {
                    throw new Exception("Stock insuficiente para el material ID: $material_id");
                }

                // Reducir del stock del técnico
                $nueva_cantidad = $stock['cantidad'] - $cantidad;

                if ($nueva_cantidad > 0) {
                    $query_update = "UPDATE stock_tecnicos 
                                    SET cantidad = :cantidad, updated_at = CURRENT_TIMESTAMP 
                                    WHERE tecnico_id = :tecnico_id AND material_id = :material_id";
                    $stmt_update = $db->prepare($query_update);
                    $stmt_update->execute([
                        ':cantidad' => $nueva_cantidad,
                        ':tecnico_id' => $tecnico_id,
                        ':material_id' => $material_id
                    ]);
                } else {
                    $query_delete = "DELETE FROM stock_tecnicos 
                                    WHERE tecnico_id = :tecnico_id AND material_id = :material_id";
                    $stmt_delete = $db->prepare($query_delete);
                    $stmt_delete->execute([
                        ':tecnico_id' => $tecnico_id,
                        ':material_id' => $material_id
                    ]);
                }

                // Registrar en liquidaciones
                $query_liquidacion = "INSERT INTO liquidaciones_materiales 
                                     (acta_id, tecnico_id, material_id, cantidad, fecha_liquidacion, sede_id)
                                     VALUES (:acta_id, :tecnico_id, :material_id, :cantidad, CURRENT_TIMESTAMP, :sede_id)";
                $stmt_liquidacion = $db->prepare($query_liquidacion);
                $stmt_liquidacion->execute([
                    ':acta_id' => $acta_id,
                    ':tecnico_id' => $tecnico_id,
                    ':material_id' => $material_id,
                    ':cantidad' => $cantidad,
                    ':sede_id' => $sede_id
                ]);

                // Registrar movimiento
                $query_mov = "INSERT INTO movimientos_inventario 
                             (material_id, tipo_movimiento, cantidad, motivo, usuario_id, tecnico_asignado_id, fecha_movimiento, sede_id)
                             VALUES (:material_id, 'consumo', :cantidad, :motivo, :usuario_id, :tecnico_id, CURRENT_TIMESTAMP, :sede_id)";
                $stmt_mov = $db->prepare($query_mov);
                $stmt_mov->execute([
                    ':material_id' => $material_id,
                    ':cantidad' => $cantidad,
                    ':motivo' => "Liquidación de acta ID: $acta_id",
                    ':usuario_id' => $tecnico_id,
                    ':tecnico_id' => $tecnico_id,
                    ':sede_id' => $sede_id
                ]);
            }

            // Actualizar estado del acta
            $query_acta = "UPDATE actas_tecnicas SET estado_liquidacion = 'liquidada' WHERE id = :acta_id";
            $stmt_acta = $db->prepare($query_acta);
            $stmt_acta->execute([':acta_id' => $acta_id]);

            $db->commit();
            registrarActividad($tecnico_id, 'liquidar', 'materiales', "Liquidación de materiales para acta ID: $acta_id");
            setMensaje('success', 'Materiales liquidados exitosamente');
            redirigir('views/tecnico/liquidar_materiales.php');
        } catch (Exception $e) {
            $db->rollBack();
            setMensaje('danger', 'Error al liquidar materiales: ' . $e->getMessage());
        }
    }
}

// Obtener actas pendientes de liquidación
$query_actas = "SELECT * FROM actas_tecnicas 
                WHERE tecnico_id = :tecnico_id 
                AND (estado_liquidacion IS NULL OR estado_liquidacion = 'pendiente')
                ORDER BY fecha_servicio DESC";
$stmt_actas = $db->prepare($query_actas);
$stmt_actas->execute([':tecnico_id' => $tecnico_id]);
$actas_pendientes = $stmt_actas->fetchAll(PDO::FETCH_ASSOC);

// Obtener mi stock actual
$mi_stock = $asignacionModel->obtenerPorTecnico($tecnico_id);

// Obtener historial de liquidaciones
$query_historial = "SELECT lm.*, m.nombre as material_nombre, m.codigo, m.unidad,
                          at.codigo_acta, at.cliente, at.tipo_servicio, at.fecha_servicio
                   FROM liquidaciones_materiales lm
                   INNER JOIN materiales m ON lm.material_id = m.id
                   INNER JOIN actas_tecnicas at ON lm.acta_id = at.id
                   WHERE lm.tecnico_id = :tecnico_id
                   ORDER BY lm.fecha_liquidacion DESC
                   LIMIT 20";
$stmt_historial = $db->prepare($query_historial);
$stmt_historial->execute([':tecnico_id' => $tecnico_id]);
$historial = $stmt_historial->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Liquidar Materiales";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Liquidación de Materiales y Equipos</h5>
                    <p class="text-muted mb-0 mt-1">Registra los materiales y equipos utilizados en tus servicios</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="icon blue">
                <i class="bi bi-file-text"></i>
            </div>
            <h3><?php echo count($actas_pendientes); ?></h3>
            <p>Actas Pendientes</p>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="icon green">
                <i class="bi bi-box-seam"></i>
            </div>
            <h3><?php echo count($mi_stock); ?></h3>
            <p>Materiales Disponibles</p>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="icon orange">
                <i class="bi bi-check-circle"></i>
            </div>
            <h3><?php echo count($historial); ?></h3>
            <p>Liquidaciones Realizadas</p>
        </div>
    </div>
</div>

<!-- Actas Pendientes de Liquidación -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-hourglass-split me-2"></i>Actas Pendientes de Liquidación</h5>
            </div>

            <?php if (empty($actas_pendientes)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-check-circle text-success" style="font-size: 48px;"></i>
                    <p class="text-muted mt-3">No tienes actas pendientes de liquidación</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Código Acta</th>
                                <th>Fecha Servicio</th>
                                <th>Cliente</th>
                                <th>Tipo Servicio</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($actas_pendientes as $acta): ?>
                                <tr>
                                    <td><code><?php echo $acta['codigo_acta']; ?></code></td>
                                    <td><?php echo formatearFecha($acta['fecha_servicio']); ?></td>
                                    <td><?php echo $acta['cliente']; ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $acta['tipo_servicio']; ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalLiquidar"
                                            data-acta-id="<?php echo $acta['id']; ?>"
                                            data-acta-codigo="<?php echo $acta['codigo_acta']; ?>"
                                            data-acta-cliente="<?php echo $acta['cliente']; ?>">
                                            <i class="bi bi-clipboard-check me-1"></i>Liquidar
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

<!-- Historial de Liquidaciones -->
<?php if (!empty($historial)): ?>
    <div class="row">
        <div class="col-12">
            <div class="content-card">
                <div class="card-header">
                    <h5><i class="bi bi-clock-history me-2"></i>Historial de Liquidaciones</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Acta</th>
                                <th>Cliente</th>
                                <th>Material</th>
                                <th>Cantidad</th>
                                <th>Servicio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historial as $item): ?>
                                <tr>
                                    <td><?php echo formatearFechaHora($item['fecha_liquidacion']); ?></td>
                                    <td><code><?php echo $item['codigo_acta']; ?></code></td>
                                    <td><?php echo $item['cliente']; ?></td>
                                    <td><?php echo $item['material_nombre']; ?></td>
                                    <td>
                                        <span class="badge bg-success">
                                            <?php echo $item['cantidad']; ?> <?php echo $item['unidad']; ?>
                                        </span>
                                    </td>
                                    <td><span class="badge bg-info"><?php echo $item['tipo_servicio']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Modal Liquidar Materiales -->
<div class="modal fade" id="modalLiquidar" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="formLiquidar">
                <input type="hidden" name="accion" value="liquidar">
                <input type="hidden" name="acta_id" id="acta_id">

                <div class="modal-header">
                    <h5 class="modal-title">Liquidar Materiales</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Acta:</strong> <span id="modal_acta_codigo"></span><br>
                        <strong>Cliente:</strong> <span id="modal_acta_cliente"></span>
                    </div>

                    <h6 class="mb-3">Selecciona los materiales utilizados:</h6>

                    <div id="materiales_list">
                        <?php if (empty($mi_stock)): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                No tienes materiales asignados actualmente
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Material</th>
                                            <th>Disponible</th>
                                            <th>Cantidad Usada</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($mi_stock as $material): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $material['material_nombre']; ?></strong><br>
                                                    <small class="text-muted"><?php echo $material['codigo']; ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success">
                                                        <?php echo $material['cantidad']; ?> <?php echo $material['unidad']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <input type="number"
                                                        name="materiales[<?php echo $material['material_id']; ?>]"
                                                        class="form-control form-control-sm"
                                                        min="0"
                                                        max="<?php echo $material['cantidad']; ?>"
                                                        value="0"
                                                        style="width: 100px;">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" <?php echo empty($mi_stock) ? 'disabled' : ''; ?>>
                        <i class="bi bi-check-circle me-1"></i>Confirmar Liquidación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modalLiquidar = document.getElementById('modalLiquidar');

        modalLiquidar.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const actaId = button.getAttribute('data-acta-id');
            const actaCodigo = button.getAttribute('data-acta-codigo');
            const actaCliente = button.getAttribute('data-acta-cliente');

            document.getElementById('acta_id').value = actaId;
            document.getElementById('modal_acta_codigo').textContent = actaCodigo;
            document.getElementById('modal_acta_cliente').textContent = actaCliente;
        });
    });
</script>

<?php include '../layouts/footer.php'; ?>