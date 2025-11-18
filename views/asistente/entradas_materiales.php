<?php
/**
 * Registrar Entradas de Materiales - Asistente de Almacén
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/Material.php';
require_once '../../models/Movimiento.php';

if (!tieneRol(ROL_ASISTENTE_ALMACEN)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();
$materialModel = new Material($db);
$movimientoModel = new Movimiento($db);
$form_token = crearTokenFormulario('entradas_materiales');

// Procesar nueva entrada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    if (!isset($_POST['form_token']) || !validarTokenFormulario('entradas_materiales', $_POST['form_token'])) {
        setMensaje('danger', 'Envío inválido. Recargue la página e intente nuevamente.');
        redirigir('views/asistente/entradas_materiales.php');
    }
    $material_id = (int)$_POST['material_id'];
    $cantidad = (int)$_POST['cantidad'];
    $tipo_entrada = sanitizar($_POST['tipo_entrada']);
    $proveedor_id = !empty($_POST['proveedor_id']) ? (int)$_POST['proveedor_id'] : null;
    $numero_lote = sanitizar($_POST['numero_lote'] ?? '');
    $fecha_vencimiento = !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null;
    $documento_referencia = sanitizar($_POST['documento_referencia'] ?? '');
    $observaciones = sanitizar($_POST['observaciones'] ?? '');
    $fecha_entrada = $_POST['fecha_entrada'] ?? date('Y-m-d');

    if (empty($material_id) || empty($cantidad) || $cantidad <= 0) {
        setMensaje('danger', 'Material y cantidad son requeridos');
    } else {
        try {
            $db->beginTransaction();

            $dup = $db->prepare("SELECT id FROM movimientos_inventario 
                                  WHERE material_id = :material_id AND tipo_movimiento = 'entrada' 
                                    AND cantidad = :cantidad AND usuario_id = :usuario_id 
                                    AND sede_id = :sede_id AND documento_referencia = :doc 
                                    AND TIMESTAMPDIFF(SECOND, fecha_movimiento, NOW()) < 10 
                                  ORDER BY id DESC LIMIT 1");
            $dup->bindValue(':material_id', $material_id);
            $dup->bindValue(':cantidad', $cantidad);
            $dup->bindValue(':usuario_id', $_SESSION['usuario_id']);
            $dup->bindValue(':sede_id', obtenerSedeActual());
            $dup->bindValue(':doc', $documento_referencia);
            $dup->execute();
            if ($dup->fetch(PDO::FETCH_ASSOC)) {
                $db->rollBack();
                consumirTokenFormulario('entradas_materiales');
                setMensaje('warning', 'Entrada duplicada detectada. No se registró nuevamente.');
                redirigir('views/asistente/entradas_materiales.php');
            }

            // Crear movimiento de entrada
            $movimientoModel->material_id = $material_id;
            $movimientoModel->tipo_movimiento = 'entrada';
            $movimientoModel->cantidad = $cantidad;
            $movimientoModel->motivo = $tipo_entrada;
            $movimientoModel->usuario_id = $_SESSION['usuario_id'];
            $movimientoModel->sede_id = obtenerSedeActual();
            $movimientoModel->documento_referencia = $documento_referencia;
            $movimientoModel->observaciones = $observaciones;

            if ($movimientoModel->crear()) {
                // Registrar entrada en tabla entradas_materiales
                $query = "INSERT INTO entradas_materiales 
                         (movimiento_id, tipo_entrada, proveedor_id, numero_lote, fecha_vencimiento, fecha_entrada, usuario_id)
                         VALUES (:movimiento_id, :tipo_entrada, :proveedor_id, :numero_lote, :fecha_vencimiento, :fecha_entrada, :usuario_id)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':movimiento_id', $movimientoModel->id);
                $stmt->bindParam(':tipo_entrada', $tipo_entrada);
                $stmt->bindParam(':proveedor_id', $proveedor_id);
                $stmt->bindParam(':numero_lote', $numero_lote);
                $stmt->bindParam(':fecha_vencimiento', $fecha_vencimiento);
                $stmt->bindParam(':fecha_entrada', $fecha_entrada);
                $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);

                if ($stmt->execute()) {
                    $db->commit();
                    consumirTokenFormulario('entradas_materiales');
                    registrarActividad($_SESSION['usuario_id'], 'crear', 'entradas_materiales', "Entrada registrada: Material ID $material_id, Cantidad: $cantidad");
                    setMensaje('success', 'Entrada registrada exitosamente. Pendiente de aprobación del Jefe de Almacén');
                    redirigir('views/asistente/entradas_materiales.php');
                } else {
                    $db->rollBack();
                    setMensaje('danger', 'Error al registrar entrada');
                }
            } else {
                $db->rollBack();
                setMensaje('danger', 'Error al crear movimiento');
            }
        } catch (Exception $e) {
            $db->rollBack();
            setMensaje('danger', 'Error: ' . $e->getMessage());
        }
    }
}

// Obtener entradas recientes
$query = "SELECT em.*,
                 mi.cantidad,
                 m.nombre AS material_nombre,
                 m.codigo,
                 m.unidad,
                 p.nombre AS proveedor_nombre,
                 u.nombre_completo
          FROM entradas_materiales em
          JOIN movimientos_inventario mi ON em.movimiento_id = mi.id
          JOIN materiales m ON mi.material_id = m.id
          LEFT JOIN proveedores p ON em.proveedor_id = p.id
          JOIN usuarios u ON em.usuario_id = u.id
          WHERE em.usuario_id = :usuario_id
          ORDER BY em.fecha_entrada DESC
          LIMIT 20";
$stmt = $db->prepare($query);
$stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
$stmt->execute();
$entradas_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener materiales disponibles
$materiales = $materialModel->obtenerTodos();

// Obtener proveedores
$query_proveedores = "SELECT id, nombre FROM proveedores WHERE estado = 'activo' ORDER BY nombre";
$stmt_proveedores = $db->query($query_proveedores);
$proveedores = $stmt_proveedores->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Registrar Entradas de Materiales";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5><i class="bi bi-arrow-down-circle me-2"></i>Registrar Entradas de Materiales</h5>
                    <p class="text-muted mb-0">Registre nuevas entradas de materiales al almacén</p>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaEntrada">
                    <i class="bi bi-plus-circle me-1"></i>Nueva Entrada
                </button>
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
                        <h6 class="card-title">Entradas Registradas</h6>
                        <h3><?php echo count($entradas_recientes); ?></h3>
                    </div>
                    <i class="bi bi-arrow-down-circle" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-gradient-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Pendientes de Aprobación</h6>
                        <h3><?php echo count(array_filter($entradas_recientes, function($e) { return ($e['estado'] ?? 'pendiente') === 'pendiente'; })); ?></h3>
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
                        <h3><?php echo count(array_filter($entradas_recientes, function($e) { return ($e['estado'] ?? 'pendiente') === 'aprobada'; })); ?></h3>
                    </div>
                    <i class="bi bi-check-circle" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Entradas Recientes -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-3">Últimas Entradas Registradas</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Material</th>
                            <th>Cantidad</th>
                            <th>Tipo</th>
                            <th>Proveedor</th>
                            <th>Lote</th>
                            <th>Vencimiento</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($entradas_recientes)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No hay entradas registradas</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($entradas_recientes as $entrada): ?>
                            <tr>
                                <td><?php echo formatearFecha($entrada['fecha_entrada']); ?></td>
                                <td><strong><?php echo $entrada['material_nombre'] ?? 'N/A'; ?></strong></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $entrada['cantidad'] ?? 0; ?> <?php echo $entrada['unidad'] ?? ''; ?></span>
                                </td>
                                <td><?php echo ucfirst($entrada['tipo_entrada'] ?? 'N/A'); ?></td>
                                <td><?php echo $entrada['proveedor_nombre'] ?? '-'; ?></td>
                                <td><?php echo $entrada['numero_lote'] ?? '-'; ?></td>
                                <td><?php echo !empty($entrada['fecha_vencimiento']) ? formatearFecha($entrada['fecha_vencimiento']) : '-'; ?></td>
                                <td>
                                    <span class="badge bg-warning">Pendiente</span>
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

<!-- Modal Nueva Entrada -->
<div class="modal fade" id="modalNuevaEntrada" tabindex="-1" aria-labelledby="modalNuevaEntradaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalNuevaEntradaLabel">
                    <i class="bi bi-plus-circle me-2"></i>Nueva Entrada de Material
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="formNuevaEntrada">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Material <span class="text-danger">*</span></label>
                            <select name="material_id" class="form-select" required>
                                <option value="">Seleccionar material...</option>
                                <?php foreach ($materiales as $mat): ?>
                                <option value="<?php echo $mat['id']; ?>"><?php echo $mat['nombre']; ?> (<?php echo $mat['codigo']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cantidad <span class="text-danger">*</span></label>
                            <input type="number" name="cantidad" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Entrada <span class="text-danger">*</span></label>
                            <select name="tipo_entrada" class="form-select" required>
                                <option value="proveedor">Compra a Proveedor</option>
                                <option value="devolucion">Devolución de Técnico</option>
                                <option value="ajuste">Ajuste de Inventario</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Proveedor</label>
                            <select name="proveedor_id" class="form-select">
                                <option value="">Seleccionar proveedor...</option>
                                <?php foreach ($proveedores as $prov): ?>
                                <option value="<?php echo $prov['id']; ?>"><?php echo $prov['nombre']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Número de Lote</label>
                            <input type="text" name="numero_lote" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Vencimiento</label>
                            <input type="date" name="fecha_vencimiento" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Documento de Referencia</label>
                            <input type="text" name="documento_referencia" class="form-control" placeholder="Factura, Remisión, etc.">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Entrada</label>
                            <input type="date" name="fecha_entrada" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2"></textarea>
                        </div>
                        <input type="hidden" name="form_token" value="<?php echo $form_token; ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="accion" value="crear" class="btn btn-primary" id="btnSubmitEntrada">
                        <i class="bi bi-check-circle me-1"></i>Registrar Entrada
                    </button>
                </div>
            </form>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('formNuevaEntrada');
    var btn = document.getElementById('btnSubmitEntrada');
    if (form && btn) {
        form.addEventListener('submit', function() {
            btn.disabled = true;
            btn.classList.add('disabled');
        });
    }
});
</script>

<?php include '../layouts/footer.php'; ?>
