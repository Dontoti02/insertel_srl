<?php
/**
 * Registrar Salidas de Materiales - Asistente de Almacén
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

// Procesar nueva salida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    $material_id = (int)$_POST['material_id'];
    $cantidad = (int)$_POST['cantidad'];
    $tipo_salida = sanitizar($_POST['tipo_salida']);
    $tecnico_id = !empty($_POST['tecnico_id']) ? (int)$_POST['tecnico_id'] : null;
    $numero_orden = sanitizar($_POST['numero_orden'] ?? '');
    $observaciones = sanitizar($_POST['observaciones'] ?? '');
    $fecha_salida = $_POST['fecha_salida'] ?? date('Y-m-d');

    if (empty($material_id) || empty($cantidad) || $cantidad <= 0) {
        setMensaje('danger', 'Material y cantidad son requeridos');
    } else {
        // Verificar stock disponible
        $query_stock = "SELECT stock_actual FROM materiales WHERE id = :material_id";
        $stmt_stock = $db->prepare($query_stock);
        $stmt_stock->bindParam(':material_id', $material_id);
        $stmt_stock->execute();
        $material = $stmt_stock->fetch(PDO::FETCH_ASSOC);

        if (!$material || $material['stock_actual'] < $cantidad) {
            setMensaje('danger', 'Stock insuficiente. Stock disponible: ' . ($material['stock_actual'] ?? 0));
        } else {
            try {
                $db->beginTransaction();

                // Crear movimiento de salida
                $movimientoModel = new Movimiento($db);
                    $movimientoModel->material_id = $material_id;
                    $movimientoModel->tipo_movimiento = 'salida';
                    $movimientoModel->cantidad = $cantidad;
                    $movimientoModel->motivo = $tipo_salida;
                    $movimientoModel->usuario_id = $_SESSION['usuario_id'];
                    $movimientoModel->sede_id = obtenerSedeActual();

                if ($movimientoModel->crear()) {
                    // Registrar salida en tabla salidas_materiales
                    $query = "INSERT INTO salidas_materiales 
                             (movimiento_id, tipo_salida, tecnico_id, numero_orden, fecha_salida, usuario_id)
                             VALUES (:movimiento_id, :tipo_salida, :tecnico_id, :numero_orden, :fecha_salida, :usuario_id)";
                    $stmt = $db->prepare($query);
                    
                    // Convertir a tipos específicos para evitar problemas de referencia
                    $movimiento_id = (int)$movimientoModel->id;
                    $tipo_salida_str = (string)$tipo_salida;
                    $tecnico_id_int = $tecnico_id !== null ? (int)$tecnico_id : null;
                    $numero_orden_str = (string)$numero_orden;
                    $fecha_salida_str = (string)$fecha_salida;
                    $usuario_id_int = (int)$_SESSION['usuario_id'];
                    
                    $stmt->bindValue(':movimiento_id', $movimiento_id, PDO::PARAM_INT);
                    $stmt->bindValue(':tipo_salida', $tipo_salida_str, PDO::PARAM_STR);
                    $stmt->bindValue(':tecnico_id', $tecnico_id_int, $tecnico_id_int !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
                    $stmt->bindValue(':numero_orden', $numero_orden_str, PDO::PARAM_STR);
                    $stmt->bindValue(':fecha_salida', $fecha_salida_str, PDO::PARAM_STR);
                    $stmt->bindValue(':usuario_id', $usuario_id_int, PDO::PARAM_INT);

                    if ($stmt->execute()) {
                        $db->commit();
                        registrarActividad($_SESSION['usuario_id'], 'crear', 'salidas_materiales', "Salida registrada: Material ID $material_id, Cantidad: $cantidad");
                        setMensaje('success', 'Salida registrada exitosamente. Pendiente de aprobación del Jefe de Almacén');
                        redirigir('views/asistente/salidas_materiales.php');
                    } else {
                        $db->rollBack();
                        setMensaje('danger', 'Error al registrar salida');
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
}

// Obtener salidas recientes
$query = "SELECT sm.*, m.nombre as material_nombre, m.codigo, m.unidad, 
                 u.nombre_completo as tecnico_nombre, usr.nombre_completo,
                 mi.material_id as material_id, mi.cantidad
          FROM salidas_materiales sm
          INNER JOIN movimientos_inventario mi ON sm.movimiento_id = mi.id
          INNER JOIN materiales m ON mi.material_id = m.id
          LEFT JOIN usuarios u ON sm.tecnico_id = u.id
          LEFT JOIN usuarios usr ON sm.usuario_id = usr.id
          WHERE sm.usuario_id = :usuario_id
          ORDER BY sm.fecha_salida DESC
          LIMIT 20";
$stmt = $db->prepare($query);
$usuario_id = $_SESSION['usuario_id'];
$stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
$stmt->execute();
$salidas_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener materiales disponibles
$materiales = $materialModel->obtenerTodos();

// Obtener técnicos
$query_tecnicos = "SELECT id, nombre_completo FROM usuarios WHERE rol_id = :rol_id AND estado = 'activo' ORDER BY nombre_completo";
$stmt_tecnicos = $db->prepare($query_tecnicos);
$rol_tecnico = ROL_TECNICO;
$stmt_tecnicos->bindParam(':rol_id', $rol_tecnico);
$stmt_tecnicos->execute();
$tecnicos = $stmt_tecnicos->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Registrar Salidas de Materiales";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5><i class="bi bi-arrow-up-circle me-2"></i>Registrar Salidas de Materiales</h5>
                    <p class="text-muted mb-0">Registre salidas de materiales del almacén</p>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaSalida">
                    <i class="bi bi-plus-circle me-1"></i>Nueva Salida
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
                        <h6 class="card-title">Salidas Registradas</h6>
                        <h3><?php echo count($salidas_recientes); ?></h3>
                    </div>
                    <i class="bi bi-arrow-up-circle" style="font-size: 2.5rem; opacity: 0.7;"></i>
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
                        <h3><?php echo count(array_filter($salidas_recientes, function($s) { return ($s['estado'] ?? 'pendiente') === 'pendiente'; })); ?></h3>
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
                        <h3><?php echo count(array_filter($salidas_recientes, function($s) { return ($s['estado'] ?? 'pendiente') === 'aprobada'; })); ?></h3>
                    </div>
                    <i class="bi bi-check-circle" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Salidas Recientes -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-3">Últimas Salidas Registradas</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Material</th>
                            <th>Cantidad</th>
                            <th>Tipo</th>
                            <th>Destino</th>
                            <th>Orden</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($salidas_recientes)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No hay salidas registradas</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($salidas_recientes as $salida): ?>
                            <tr>
                                <td><?php echo formatearFecha($salida['fecha_salida']); ?></td>
                                <td><strong><?php echo $salida['material_nombre'] ?? 'N/A'; ?></strong></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $salida['cantidad'] ?? 0; ?> <?php echo $salida['unidad'] ?? ''; ?></span>
                                </td>
                                <td><?php echo ucfirst($salida['tipo_salida'] ?? 'N/A'); ?></td>
                                <td><?php echo $salida['tecnico_nombre'] ?? $salida['tipo_salida']; ?></td>
                                <td><?php echo $salida['numero_orden'] ?? '-'; ?></td>
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

<!-- Modal Nueva Salida -->
<div class="modal fade" id="modalNuevaSalida" tabindex="-1" aria-labelledby="modalNuevaSalidaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalNuevaSalidaLabel">
                    <i class="bi bi-plus-circle me-2"></i>Nueva Salida de Material
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Material <span class="text-danger">*</span></label>
                            <select name="material_id" class="form-select" required>
                                <option value="">Seleccionar material...</option>
                                <?php foreach ($materiales as $mat): ?>
                                <option value="<?php echo $mat['id']; ?>"><?php echo $mat['nombre']; ?> (Stock: <?php echo $mat['stock_actual']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cantidad <span class="text-danger">*</span></label>
                            <input type="number" name="cantidad" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Salida <span class="text-danger">*</span></label>
                            <select name="tipo_salida" class="form-select" required>
                                <option value="tecnico">Asignación a Técnico</option>
                                <option value="proyecto">Salida a Proyecto</option>
                                <option value="devolucion_proveedor">Devolución a Proveedor</option>
                                <option value="ajuste">Ajuste de Inventario</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Técnico (si aplica)</label>
                            <select name="tecnico_id" class="form-select">
                                <option value="">Seleccionar técnico...</option>
                                <?php foreach ($tecnicos as $tec): ?>
                                <option value="<?php echo $tec['id']; ?>"><?php echo $tec['nombre_completo']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Número de Orden</label>
                            <input type="text" name="numero_orden" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Salida</label>
                            <input type="date" name="fecha_salida" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="accion" value="crear" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Registrar Salida
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

<?php include '../layouts/footer.php'; ?>
