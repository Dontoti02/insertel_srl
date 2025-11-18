<?php
/**
 * Entradas de Materiales - Jefe de Almacén
 * Registra entradas de proveedores y devoluciones
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/Movimiento.php';
require_once '../../models/Material.php';

if (!tieneAlgunRol([ROL_JEFE_ALMACEN, ROL_ADMINISTRADOR, ROL_ASISTENTE_ALMACEN])) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();
$movimientoModel = new Movimiento($db);
$materialModel = new Material($db);

// Procesar nueva entrada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_entrada'])) {
    $material_id = (int)$_POST['material_id'];
    $cantidad = (int)$_POST['cantidad'];
    $tipo_entrada = sanitizar($_POST['tipo_entrada']);
    $proveedor_id = !empty($_POST['proveedor_id']) ? (int)$_POST['proveedor_id'] : null;
    $documento_referencia = sanitizar($_POST['documento_referencia'] ?? '');
    $observaciones = sanitizar($_POST['observaciones'] ?? '');
    $fecha_entrada = sanitizar($_POST['fecha_entrada']);
    $numero_lote = sanitizar($_POST['numero_lote'] ?? '');
    $fecha_vencimiento = null;

    // Validaciones
    if (empty($material_id) || empty($cantidad) || $cantidad <= 0) {
        setMensaje('danger', 'Debe seleccionar un material y una cantidad válida');
    } else {
        try {
            $db->beginTransaction();

            // Obtener información del material
            $material = $materialModel->obtenerPorId($material_id);
            if (!$material) {
                throw new Exception('Material no encontrado');
            }

            // Crear movimiento de entrada
            $movimientoModel->material_id = $material_id;
            $movimientoModel->tipo_movimiento = 'entrada';
            $movimientoModel->cantidad = $cantidad;
            $movimientoModel->motivo = "Entrada de $tipo_entrada";
            $movimientoModel->usuario_id = $_SESSION['usuario_id'];
            $movimientoModel->sede_id = obtenerSedeActual();
            $movimientoModel->documento_referencia = $documento_referencia;
            $movimientoModel->observaciones = $observaciones;

            if (!$movimientoModel->crear()) {
                throw new Exception('Error al registrar el movimiento');
            }

            // Guardar información adicional de entrada
            $query_entrada = "INSERT INTO entradas_materiales 
                            (movimiento_id, tipo_entrada, proveedor_id, numero_lote, fecha_vencimiento, fecha_entrada, usuario_id)
                            VALUES (:movimiento_id, :tipo_entrada, :proveedor_id, :numero_lote, :fecha_vencimiento, :fecha_entrada, :usuario_id)";
            $stmt_entrada = $db->prepare($query_entrada);
            $stmt_entrada->bindParam(':movimiento_id', $movimientoModel->id);
            $stmt_entrada->bindParam(':tipo_entrada', $tipo_entrada);
            $stmt_entrada->bindParam(':proveedor_id', $proveedor_id);
            $stmt_entrada->bindParam(':numero_lote', $numero_lote);
            $stmt_entrada->bindParam(':fecha_vencimiento', $fecha_vencimiento);
            $stmt_entrada->bindParam(':fecha_entrada', $fecha_entrada);
            $stmt_entrada->bindParam(':usuario_id', $_SESSION['usuario_id']);

            if (!$stmt_entrada->execute()) {
                throw new Exception('Error al registrar detalles de entrada');
            }

            $db->commit();
            registrarActividad($_SESSION['usuario_id'], 'crear', 'entradas_materiales', 
                "Entrada registrada: {$material['nombre']} - Cantidad: $cantidad");
            setMensaje('success', 'Entrada de material registrada correctamente');
            redirigir('views/almacen/entradas_materiales.php');

        } catch (Exception $e) {
            $db->rollBack();
            setMensaje('danger', 'Error: ' . $e->getMessage());
        }
    }
}

// Obtener entradas recientes
$query_entradas = "SELECT em.*, m.nombre as material_nombre, m.codigo, m.unidad, 
                          p.nombre as proveedor_nombre, u.nombre_completo as usuario_nombre,
                          mi.cantidad
                   FROM entradas_materiales em
                   LEFT JOIN movimientos_inventario mi ON em.movimiento_id = mi.id
                   LEFT JOIN materiales m ON mi.material_id = m.id
                   LEFT JOIN proveedores p ON em.proveedor_id = p.id
                   LEFT JOIN usuarios u ON em.usuario_id = u.id";

if (!esSuperAdmin()) {
    $sede_actual = obtenerSedeActual();
    if ($sede_actual) {
        $query_entradas .= " WHERE mi.sede_id = :sede_id";
    }
}

$query_entradas .= " ORDER BY em.fecha_entrada DESC LIMIT 50";

$stmt_entradas = $db->prepare($query_entradas);
if (!esSuperAdmin()) {
    $sede_actual = obtenerSedeActual();
    if ($sede_actual) {
        $stmt_entradas->bindParam(':sede_id', $sede_actual);
    }
}
$stmt_entradas->execute();
$entradas = $stmt_entradas->fetchAll(PDO::FETCH_ASSOC);

// Obtener materiales y proveedores
$materiales = $materialModel->obtenerTodos(['estado' => 'activo']);
$query_prov = "SELECT * FROM proveedores WHERE estado = 'activo' ORDER BY nombre";
$proveedores = $db->query($query_prov)->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Entradas de Materiales";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5><i class="bi bi-box-seam me-2"></i>Entradas de Materiales</h5>
                    <p class="text-muted mb-0">Registre entradas de proveedores y devoluciones</p>
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
    <div class="col-md-3">
        <div class="card bg-gradient-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Entradas Hoy</h6>
                        <h3><?php 
                            $hoy = date('Y-m-d');
                            $count_hoy = count(array_filter($entradas, function($e) use ($hoy) {
                                return date('Y-m-d', strtotime($e['fecha_entrada'])) === $hoy;
                            }));
                            echo $count_hoy;
                        ?></h3>
                    </div>
                    <i class="bi bi-arrow-down-circle" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-gradient-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Entradas</h6>
                        <h3><?php echo count($entradas); ?></h3>
                    </div>
                    <i class="bi bi-box2" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-gradient-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">De Proveedores</h6>
                        <h3><?php 
                            $count_prov = count(array_filter($entradas, function($e) {
                                return $e['tipo_entrada'] === 'proveedor';
                            }));
                            echo $count_prov;
                        ?></h3>
                    </div>
                    <i class="bi bi-truck" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-gradient-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Devoluciones</h6>
                        <h3><?php 
                            $count_dev = count(array_filter($entradas, function($e) {
                                return $e['tipo_entrada'] === 'devolucion';
                            }));
                            echo $count_dev;
                        ?></h3>
                    </div>
                    <i class="bi bi-arrow-counterclockwise" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Entradas -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-3">Historial de Entradas</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Material</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Proveedor/Lote</th>
                            <th>Registrado por</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($entradas)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No hay entradas registradas</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($entradas as $entrada): ?>
                            <tr>
                                <td><?php echo formatearFechaHora($entrada['fecha_entrada']); ?></td>
                                <td>
                                    <code><?php echo $entrada['codigo']; ?></code>
                                    <strong class="d-block"><?php echo $entrada['material_nombre']; ?></strong>
                                </td>
                                <td>
                                    <?php
                                    $badge_class = $entrada['tipo_entrada'] === 'proveedor' ? 'bg-info' : 'bg-warning';
                                    $icon = $entrada['tipo_entrada'] === 'proveedor' ? 'truck' : 'arrow-counterclockwise';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <i class="bi bi-<?php echo $icon; ?> me-1"></i>
                                        <?php echo ucfirst($entrada['tipo_entrada']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-success"><?php echo $entrada['cantidad']; ?> <?php echo $entrada['unidad']; ?></span>
                                </td>
                                <td>
                                    <?php if ($entrada['tipo_entrada'] === 'proveedor'): ?>
                                        <?php echo $entrada['proveedor_nombre'] ?? '-'; ?>
                                    <?php else: ?>
                                        <small><?php echo $entrada['numero_lote']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $entrada['usuario_nombre']; ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick="verDetalleEntrada(<?php echo htmlspecialchars(json_encode($entrada)); ?>)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmarEliminacion(<?php echo $entrada['id']; ?>, 'entrada')">
                                        <i class="bi bi-trash"></i>
                                    </button>
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
                    <i class="bi bi-plus-circle me-2"></i>Registrar Nueva Entrada
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Entrada <span class="text-danger">*</span></label>
                            <select name="tipo_entrada" class="form-select" required>
                                <option value="">Seleccione tipo...</option>
                                <option value="proveedor">Compra a Proveedor</option>
                                <option value="devolucion">Devolución de Técnico</option>
                                <option value="ajuste">Ajuste de Inventario</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Material <span class="text-danger">*</span></label>
                            <select name="material_id" class="form-select" required>
                                <option value="">Seleccione material...</option>
                                <?php foreach ($materiales as $mat): ?>
                                <option value="<?php echo $mat['id']; ?>">
                                    <?php echo $mat['codigo']; ?> - <?php echo $mat['nombre']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cantidad <span class="text-danger">*</span></label>
                            <input type="number" name="cantidad" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Entrada <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_entrada" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6" id="div_proveedor" style="display: none;">
                            <label class="form-label">Proveedor</label>
                            <select name="proveedor_id" class="form-select">
                                <option value="">Seleccione proveedor...</option>
                                <?php foreach ($proveedores as $prov): ?>
                                <option value="<?php echo $prov['id']; ?>"><?php echo $prov['nombre']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Número de Lote</label>
                            <input type="text" name="numero_lote" class="form-control" placeholder="Ej: LOTE-2025-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Documento de Referencia</label>
                            <input type="text" name="documento_referencia" class="form-control" placeholder="Ej: Factura, Remisión">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="3" placeholder="Notas adicionales..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="registrar_entrada" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Registrar Entrada
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.bg-gradient-success {
    background: linear-gradient(135deg, #10b981 0%, #14b8a6 100%);
}
.bg-gradient-info {
    background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
}
.bg-gradient-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
}
.bg-gradient-danger {
    background: linear-gradient(135deg, #ef4444 0%, #ec4899 100%);
}
</style>

<script>
// Mostrar/ocultar proveedor según tipo de entrada
document.querySelector('select[name="tipo_entrada"]').addEventListener('change', function() {
    const div_proveedor = document.getElementById('div_proveedor');
    if (this.value === 'proveedor') {
        div_proveedor.style.display = 'block';
    } else {
        div_proveedor.style.display = 'none';
    }
});

function verDetalleEntrada(entrada) {
    console.log('Detalle de entrada:', entrada);
    // Implementar modal de detalles si es necesario
}

function confirmarEliminacion(id, tipo) {
    if (confirm(`¿Está seguro de que desea eliminar este registro de ${tipo}? Esta acción no se puede deshacer.`)) {
        window.location.href = `eliminar_movimiento.php?id=${id}&tipo=${tipo}`;
    }
}
</script>

<?php include '../layouts/footer.php'; ?>
