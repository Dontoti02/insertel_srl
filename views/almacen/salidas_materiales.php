<?php
/**
 * Salidas de Materiales - Jefe de Almacén
 * Registra salidas a proyectos, técnicos y devoluciones
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/Movimiento.php';
require_once '../../models/Material.php';
require_once '../../models/User.php';

if (!tieneAlgunRol([ROL_JEFE_ALMACEN, ROL_ADMINISTRADOR, ROL_ASISTENTE_ALMACEN])) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();
$movimientoModel = new Movimiento($db);
$materialModel = new Material($db);
$userModel = new User($db);

// Procesar nueva salida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_salida'])) {
    $material_id = (int)$_POST['material_id'];
    $cantidad = (int)$_POST['cantidad'];
    $tipo_salida = sanitizar($_POST['tipo_salida']);
    $tecnico_id = !empty($_POST['tecnico_id']) ? (int)$_POST['tecnico_id'] : null;
    $numero_orden = sanitizar($_POST['numero_orden'] ?? '');
    $observaciones = sanitizar($_POST['observaciones'] ?? '');
    $fecha_salida = sanitizar($_POST['fecha_salida']);

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

            // Verificar stock disponible
            if ($material['stock_actual'] < $cantidad) {
                throw new Exception("Stock insuficiente. Disponible: {$material['stock_actual']}, Solicitado: $cantidad");
            }

            // Crear movimiento de salida
            $movimientoModel->material_id = $material_id;
            $movimientoModel->tipo_movimiento = 'salida';
            $movimientoModel->cantidad = $cantidad;
            $movimientoModel->motivo = "Salida para $tipo_salida";
            $movimientoModel->usuario_id = $_SESSION['usuario_id'];
            $movimientoModel->sede_id = obtenerSedeActual();
            $movimientoModel->tecnico_asignado_id = $tecnico_id;
            $movimientoModel->documento_referencia = $numero_orden;
            $movimientoModel->observaciones = $observaciones;

            if (!$movimientoModel->crear()) {
                throw new Exception('Error al registrar el movimiento');
            }

            // Guardar información adicional de salida
            $query_salida = "INSERT INTO salidas_materiales 
                            (movimiento_id, tipo_salida, tecnico_id, numero_orden, fecha_salida, usuario_id)
                            VALUES (:movimiento_id, :tipo_salida, :tecnico_id, :numero_orden, :fecha_salida, :usuario_id)";
            $stmt_salida = $db->prepare($query_salida);
            $stmt_salida->bindParam(':movimiento_id', $movimientoModel->id);
            $stmt_salida->bindParam(':tipo_salida', $tipo_salida);
            $stmt_salida->bindParam(':tecnico_id', $tecnico_id);
            $stmt_salida->bindParam(':numero_orden', $numero_orden);
            $stmt_salida->bindParam(':fecha_salida', $fecha_salida);
            $stmt_salida->bindParam(':usuario_id', $_SESSION['usuario_id']);

            if (!$stmt_salida->execute()) {
                throw new Exception('Error al registrar detalles de salida');
            }

            $db->commit();
            registrarActividad($_SESSION['usuario_id'], 'crear', 'salidas_materiales', 
                "Salida registrada: {$material['nombre']} - Cantidad: $cantidad");
            setMensaje('success', 'Salida de material registrada correctamente');
            redirigir('views/almacen/salidas_materiales.php');

        } catch (Exception $e) {
            $db->rollBack();
            setMensaje('danger', 'Error: ' . $e->getMessage());
        }
    }
}

// Obtener salidas recientes
$query_salidas = "SELECT sm.*, m.nombre as material_nombre, m.codigo, m.unidad, 
                          u.nombre_completo as usuario_nombre, ut.nombre_completo as tecnico_nombre,
                          mi.cantidad
                   FROM salidas_materiales sm
                   LEFT JOIN movimientos_inventario mi ON sm.movimiento_id = mi.id
                   LEFT JOIN materiales m ON mi.material_id = m.id
                   LEFT JOIN usuarios u ON sm.usuario_id = u.id
                   LEFT JOIN usuarios ut ON sm.tecnico_id = ut.id";

if (!esSuperAdmin()) {
    $sede_actual = obtenerSedeActual();
    if ($sede_actual) {
        $query_salidas .= " WHERE mi.sede_id = :sede_id";
    }
}

$query_salidas .= " ORDER BY sm.fecha_salida DESC LIMIT 50";

$stmt_salidas = $db->prepare($query_salidas);
if (!esSuperAdmin()) {
    $sede_actual = obtenerSedeActual();
    if ($sede_actual) {
        $stmt_salidas->bindParam(':sede_id', $sede_actual);
    }
}
$stmt_salidas->execute();
$salidas = $stmt_salidas->fetchAll(PDO::FETCH_ASSOC);

// Obtener materiales y técnicos
$materiales = $materialModel->obtenerTodos(['estado' => 'activo', 'stockMayorQue' => 0]);
$tecnicos = $userModel->obtenerTecnicos();

$page_title = "Salidas de Materiales";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5><i class="bi bi-box-arrow-right me-2"></i>Salidas de Materiales</h5>
                    <p class="text-muted mb-0">Registre salidas a proyectos, técnicos y devoluciones</p>
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
    <div class="col-md-3">
        <div class="card bg-gradient-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Salidas Hoy</h6>
                        <h3><?php 
                            $hoy = date('Y-m-d');
                            $count_hoy = count(array_filter($salidas, function($s) use ($hoy) {
                                return date('Y-m-d', strtotime($s['fecha_salida'])) === $hoy;
                            }));
                            echo $count_hoy;
                        ?></h3>
                    </div>
                    <i class="bi bi-arrow-up-circle" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-gradient-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Salidas</h6>
                        <h3><?php echo count($salidas); ?></h3>
                    </div>
                    <i class="bi bi-box2-heart" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-gradient-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">A Técnicos</h6>
                        <h3><?php 
                            $count_tec = count(array_filter($salidas, function($s) {
                                return $s['tipo_salida'] === 'tecnico';
                            }));
                            echo $count_tec;
                        ?></h3>
                    </div>
                    <i class="bi bi-person-check" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-gradient-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">A Proyectos</h6>
                        <h3><?php 
                            $count_proy = count(array_filter($salidas, function($s) {
                                return $s['tipo_salida'] === 'proyecto';
                            }));
                            echo $count_proy;
                        ?></h3>
                    </div>
                    <i class="bi bi-briefcase" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Salidas -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-3">Historial de Salidas</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Material</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Destino</th>
                            <th>Orden</th>
                            <th>Registrado por</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($salidas)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No hay salidas registradas</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($salidas as $salida): ?>
                            <tr>
                                <td><?php echo formatearFechaHora($salida['fecha_salida']); ?></td>
                                <td>
                                    <code><?php echo $salida['codigo']; ?></code>
                                    <strong class="d-block"><?php echo $salida['material_nombre']; ?></strong>
                                </td>
                                <td>
                                    <?php
                                    $badge_class = match($salida['tipo_salida']) {
                                        'tecnico' => 'bg-info',
                                        'proyecto' => 'bg-success',
                                        'devolucion_proveedor' => 'bg-warning',
                                        'ajuste' => 'bg-secondary',
                                        default => 'bg-dark'
                                    };
                                    $icon = match($salida['tipo_salida']) {
                                        'tecnico' => 'person-check',
                                        'proyecto' => 'briefcase',
                                        'devolucion_proveedor' => 'truck',
                                        'ajuste' => 'gear',
                                        default => 'box'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <i class="bi bi-<?php echo $icon; ?> me-1"></i>
                                        <?php echo ucfirst(str_replace('_', ' ', $salida['tipo_salida'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-danger"><?php echo $salida['cantidad']; ?> <?php echo $salida['unidad']; ?></span>
                                </td>
                                <td>
                                    <?php if ($salida['tipo_salida'] === 'tecnico'): ?>
                                        <?php echo $salida['tecnico_nombre'] ?? '-'; ?>
                                    <?php else: ?>
                                        <small><?php echo $salida['numero_orden'] ?? '-'; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $salida['numero_orden'] ?? '-'; ?></td>
                                <td><?php echo $salida['usuario_nombre']; ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick="verDetalleSalida(<?php echo htmlspecialchars(json_encode($salida)); ?>)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmarEliminacion(<?php echo $salida['id']; ?>, 'salida')">
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

<!-- Modal Nueva Salida -->
<div class="modal fade" id="modalNuevaSalida" tabindex="-1" aria-labelledby="modalNuevaSalidaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalNuevaSalidaLabel">
                    <i class="bi bi-plus-circle me-2"></i>Registrar Nueva Salida
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Salida <span class="text-danger">*</span></label>
                            <select name="tipo_salida" class="form-select" required onchange="actualizarCampoDestino()">
                                <option value="">Seleccione tipo...</option>
                                <option value="tecnico">Asignación a Técnico</option>
                                <option value="proyecto">Salida a Proyecto</option>
                                <option value="devolucion_proveedor">Devolución a Proveedor</option>
                                <option value="ajuste">Ajuste de Inventario</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Material <span class="text-danger">*</span></label>
                            <select name="material_id" class="form-select" required>
                                <option value="">Seleccione material...</option>
                                <?php foreach ($materiales as $mat): ?>
                                <option value="<?php echo $mat['id']; ?>">
                                    <?php echo $mat['codigo']; ?> - <?php echo $mat['nombre']; ?> (Stock: <?php echo $mat['stock_actual']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cantidad <span class="text-danger">*</span></label>
                            <input type="number" name="cantidad" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Salida <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_salida" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6" id="div_tecnico" style="display: none;">
                            <label class="form-label">Técnico</label>
                            <select name="tecnico_id" class="form-select">
                                <option value="">Seleccione técnico...</option>
                                <?php foreach ($tecnicos as $tec): ?>
                                <option value="<?php echo $tec['id']; ?>"><?php echo $tec['nombre_completo']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Número de Orden</label>
                            <input type="text" name="numero_orden" class="form-control" placeholder="Ej: ORD-2025-001">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="3" placeholder="Notas adicionales..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="registrar_salida" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Registrar Salida
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.bg-gradient-danger {
    background: linear-gradient(135deg, #ef4444 0%, #ec4899 100%);
}
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
function actualizarCampoDestino() {
    const tipo_salida = document.querySelector('select[name="tipo_salida"]').value;
    const div_tecnico = document.getElementById('div_tecnico');
    if (tipo_salida === 'tecnico') {
        div_tecnico.style.display = 'block';
    } else {
        div_tecnico.style.display = 'none';
    }
}

function verDetalleSalida(salida) {
    console.log('Detalle de salida:', salida);
    // Implementar modal de detalles si es necesario
}

function confirmarEliminacion(id, tipo) {
    if (confirm(`¿Está seguro de que desea eliminar este registro de ${tipo}? Esta acción no se puede deshacer.`)) {
        window.location.href = `eliminar_movimiento.php?id=${id}&tipo=${tipo}`;
    }
}
</script>

<?php include '../layouts/footer.php'; ?>
