<?php
/**
 * Verificación de Calidad de Proveedores - Jefe de Almacén
 * Registra y verifica la calidad de materiales recibidos
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';

if (!tieneAlgunRol([ROL_JEFE_ALMACEN, ROL_ADMINISTRADOR])) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();

// Procesar verificación de calidad
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_verificacion'])) {
    $entrada_id = (int)$_POST['entrada_id'];
    $cantidad_conforme = (int)$_POST['cantidad_conforme'];
    $cantidad_no_conforme = (int)$_POST['cantidad_no_conforme'];
    $defectos_encontrados = sanitizar($_POST['defectos_encontrados'] ?? '');
    $observaciones = sanitizar($_POST['observaciones'] ?? '');
    $estado_verificacion = sanitizar($_POST['estado_verificacion']);

    try {
        $db->beginTransaction();

        // Obtener información de la entrada
        $query_entrada = "SELECT em.*, mi.material_id, mi.cantidad, p.id as proveedor_id
                         FROM entradas_materiales em
                         LEFT JOIN movimientos_inventario mi ON em.movimiento_id = mi.id
                         LEFT JOIN proveedores p ON em.proveedor_id = p.id
                         WHERE em.id = :entrada_id";
        $stmt_entrada = $db->prepare($query_entrada);
        $stmt_entrada->bindParam(':entrada_id', $entrada_id);
        $stmt_entrada->execute();
        $entrada = $stmt_entrada->fetch(PDO::FETCH_ASSOC);

        if (!$entrada) {
            throw new Exception('Entrada no encontrada');
        }

        // Verificar que las cantidades sean válidas
        $total_verificado = $cantidad_conforme + $cantidad_no_conforme;
        if ($total_verificado != $entrada['cantidad']) {
            throw new Exception("Total verificado ($total_verificado) no coincide con cantidad recibida ({$entrada['cantidad']})");
        }

        // Registrar verificación de calidad
        $query_verificacion = "INSERT INTO verificacion_calidad_proveedor 
                             (entrada_id, proveedor_id, material_id, cantidad_recibida, 
                              cantidad_conforme, cantidad_no_conforme, defectos_encontrados,
                              observaciones, estado_verificacion, fecha_verificacion, usuario_verificador_id)
                             VALUES (:entrada_id, :proveedor_id, :material_id, :cantidad_recibida,
                                     :cantidad_conforme, :cantidad_no_conforme, :defectos_encontrados,
                                     :observaciones, :estado_verificacion, NOW(), :usuario_verificador_id)";
        
        $stmt_verificacion = $db->prepare($query_verificacion);
        $stmt_verificacion->bindParam(':entrada_id', $entrada_id);
        $stmt_verificacion->bindParam(':proveedor_id', $entrada['proveedor_id']);
        $stmt_verificacion->bindParam(':material_id', $entrada['material_id']);
        $stmt_verificacion->bindParam(':cantidad_recibida', $entrada['cantidad']);
        $stmt_verificacion->bindParam(':cantidad_conforme', $cantidad_conforme);
        $stmt_verificacion->bindParam(':cantidad_no_conforme', $cantidad_no_conforme);
        $stmt_verificacion->bindParam(':defectos_encontrados', $defectos_encontrados);
        $stmt_verificacion->bindParam(':observaciones', $observaciones);
        $stmt_verificacion->bindParam(':estado_verificacion', $estado_verificacion);
        $stmt_verificacion->bindParam(':usuario_verificador_id', $_SESSION['usuario_id']);

        if (!$stmt_verificacion->execute()) {
            throw new Exception('Error al registrar verificación');
        }

        // Si hay material no conforme, crear alerta
        if ($cantidad_no_conforme > 0) {
            $query_alerta = "UPDATE entradas_materiales SET observaciones = CONCAT(COALESCE(observaciones, ''), 
                            '\nNo conforme: $cantidad_no_conforme unidades - $defectos_encontrados')
                            WHERE id = :entrada_id";
            $stmt_alerta = $db->prepare($query_alerta);
            $stmt_alerta->bindParam(':entrada_id', $entrada_id);
            $stmt_alerta->execute();
        }

        $db->commit();
        registrarActividad($_SESSION['usuario_id'], 'crear', 'verificacion_calidad', 
            "Verificación de calidad registrada para entrada ID: $entrada_id");
        setMensaje('success', 'Verificación de calidad registrada correctamente');
        redirigir('views/almacen/verificacion_calidad.php');

    } catch (Exception $e) {
        $db->rollBack();
        setMensaje('danger', 'Error: ' . $e->getMessage());
    }
}

// Obtener entradas sin verificar
$query_sin_verificar = "SELECT em.*, m.nombre as material_nombre, m.codigo, m.unidad,
                               p.nombre as proveedor_nombre, u.nombre_completo as usuario_nombre,
                               mi.cantidad
                        FROM entradas_materiales em
                        LEFT JOIN movimientos_inventario mi ON em.movimiento_id = mi.id
                        LEFT JOIN materiales m ON mi.material_id = m.id
                        LEFT JOIN proveedores p ON em.proveedor_id = p.id
                        LEFT JOIN usuarios u ON em.usuario_id = u.id
                        WHERE em.id NOT IN (SELECT entrada_id FROM verificacion_calidad_proveedor)
                        AND em.tipo_entrada = 'proveedor'";

if (!esSuperAdmin()) {
    $sede_actual = obtenerSedeActual();
    if ($sede_actual) {
        $query_sin_verificar .= " AND mi.sede_id = :sede_id";
    }
}

$query_sin_verificar .= " ORDER BY em.fecha_entrada DESC";

$stmt_sin_verificar = $db->prepare($query_sin_verificar);
if (!esSuperAdmin()) {
    $sede_actual = obtenerSedeActual();
    if ($sede_actual) {
        $stmt_sin_verificar->bindParam(':sede_id', $sede_actual);
    }
}
$stmt_sin_verificar->execute();
$entradas_sin_verificar = $stmt_sin_verificar->fetchAll(PDO::FETCH_ASSOC);

// Obtener verificaciones realizadas
$query_verificaciones = "SELECT vcp.*, m.nombre as material_nombre, m.codigo,
                                p.nombre as proveedor_nombre, u.nombre_completo as usuario_nombre
                         FROM verificacion_calidad_proveedor vcp
                         LEFT JOIN materiales m ON vcp.material_id = m.id
                         LEFT JOIN proveedores p ON vcp.proveedor_id = p.id
                         LEFT JOIN usuarios u ON vcp.usuario_verificador_id = u.id
                         ORDER BY vcp.fecha_verificacion DESC
                         LIMIT 50";

$stmt_verificaciones = $db->prepare($query_verificaciones);
$stmt_verificaciones->execute();
$verificaciones = $stmt_verificaciones->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Verificación de Calidad";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5><i class="bi bi-check-circle me-2"></i>Verificación de Calidad de Proveedores</h5>
                    <p class="text-muted mb-0">Registre y verifique la conformidad de materiales recibidos</p>
                </div>
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
                        <h6 class="card-title">Pendientes de Verificar</h6>
                        <h3><?php echo count($entradas_sin_verificar); ?></h3>
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
                        <h6 class="card-title">Verificaciones Conformes</h6>
                        <h3><?php 
                            $count_conforme = count(array_filter($verificaciones, function($v) {
                                return $v['estado_verificacion'] === 'conforme';
                            }));
                            echo $count_conforme;
                        ?></h3>
                    </div>
                    <i class="bi bi-check-circle" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-gradient-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">No Conformes</h6>
                        <h3><?php 
                            $count_no_conforme = count(array_filter($verificaciones, function($v) {
                                return $v['estado_verificacion'] === 'no_conforme';
                            }));
                            echo $count_no_conforme;
                        ?></h3>
                    </div>
                    <i class="bi bi-x-circle" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Entradas Pendientes de Verificar -->
<?php if (!empty($entradas_sin_verificar)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-3">
                <i class="bi bi-hourglass-split text-info me-2"></i>
                Entradas Pendientes de Verificación
            </h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Material</th>
                            <th>Proveedor</th>
                            <th>Cantidad</th>
                            <th>Lote</th>
                            <th>Registrado por</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entradas_sin_verificar as $entrada): ?>
                        <tr>
                            <td><?php echo formatearFechaHora($entrada['fecha_entrada']); ?></td>
                            <td>
                                <code><?php echo $entrada['codigo']; ?></code>
                                <strong class="d-block"><?php echo $entrada['material_nombre']; ?></strong>
                            </td>
                            <td><?php echo $entrada['proveedor_nombre'] ?? '-'; ?></td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo $entrada['cantidad']; ?> <?php echo $entrada['unidad']; ?>
                                </span>
                            </td>
                            <td><?php echo $entrada['numero_lote']; ?></td>
                            <td><?php echo $entrada['usuario_nombre']; ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                                        data-bs-target="#modalVerificacion" 
                                        onclick="cargarEntrada(<?php echo htmlspecialchars(json_encode($entrada)); ?>)">
                                    <i class="bi bi-check-circle me-1"></i>Verificar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Historial de Verificaciones -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-3">Historial de Verificaciones</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fecha Verificación</th>
                            <th>Material</th>
                            <th>Proveedor</th>
                            <th>Recibido</th>
                            <th>Conforme</th>
                            <th>No Conforme</th>
                            <th>Estado</th>
                            <th>Verificador</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($verificaciones)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No hay verificaciones registradas</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($verificaciones as $verificacion): ?>
                            <tr>
                                <td><?php echo formatearFechaHora($verificacion['fecha_verificacion']); ?></td>
                                <td>
                                    <code><?php echo $verificacion['codigo']; ?></code>
                                    <strong class="d-block"><?php echo $verificacion['material_nombre']; ?></strong>
                                </td>
                                <td><?php echo $verificacion['proveedor_nombre'] ?? '-'; ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo $verificacion['cantidad_recibida']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-success">
                                        <?php echo $verificacion['cantidad_conforme']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($verificacion['cantidad_no_conforme'] > 0): ?>
                                    <span class="badge bg-danger">
                                        <?php echo $verificacion['cantidad_no_conforme']; ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $badge_class = match($verificacion['estado_verificacion']) {
                                        'conforme' => 'bg-success',
                                        'no_conforme' => 'bg-danger',
                                        'parcial' => 'bg-warning',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($verificacion['estado_verificacion']); ?>
                                    </span>
                                </td>
                                <td><?php echo $verificacion['usuario_nombre']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Verificación -->
<div class="modal fade" id="modalVerificacion" tabindex="-1" aria-labelledby="modalVerificacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalVerificacionLabel">
                    <i class="bi bi-check-circle me-2"></i>Verificación de Calidad
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="entrada_id" id="entrada_id">
                    
                    <div class="alert alert-info">
                        <strong>Material:</strong> <span id="material_info"></span><br>
                        <strong>Cantidad Recibida:</strong> <span id="cantidad_recibida"></span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Cantidad Conforme <span class="text-danger">*</span></label>
                            <input type="number" name="cantidad_conforme" class="form-control" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cantidad No Conforme</label>
                            <input type="number" name="cantidad_no_conforme" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Defectos Encontrados</label>
                            <textarea name="defectos_encontrados" class="form-control" rows="2" placeholder="Describa los defectos..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado de Verificación <span class="text-danger">*</span></label>
                            <select name="estado_verificacion" class="form-select" required>
                                <option value="conforme">Conforme</option>
                                <option value="no_conforme">No Conforme</option>
                                <option value="parcial">Parcialmente Conforme</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2" placeholder="Notas adicionales..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="registrar_verificacion" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Registrar Verificación
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
.bg-gradient-success {
    background: linear-gradient(135deg, #10b981 0%, #14b8a6 100%);
}
.bg-gradient-danger {
    background: linear-gradient(135deg, #ef4444 0%, #ec4899 100%);
}
</style>

<script>
function cargarEntrada(entrada) {
    document.getElementById('entrada_id').value = entrada.id;
    document.getElementById('material_info').textContent = entrada.codigo + ' - ' + entrada.material_nombre;
    document.getElementById('cantidad_recibida').textContent = entrada.cantidad + ' ' + entrada.unidad;
}
</script>

<?php include '../layouts/footer.php'; ?>
