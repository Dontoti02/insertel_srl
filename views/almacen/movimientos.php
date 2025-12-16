<?php

/**
 * Movimientos de Inventario
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/Movimiento.php';
require_once '../../models/Material.php';
require_once '../../models/User.php';

// Verificar autenticación
if (!estaAutenticado()) {
    redirigir('auth/login.php');
}

$database = new Database();
$db = $database->getConnection();

// Paginación
$registros_por_pagina = 20;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Filtros
$where = ["1=1"];
$params = [];

// Filtrar por sede si no es superadmin
if (!esSuperAdmin()) {
    $sede_actual = obtenerSedeActual();
    if ($sede_actual) {
        $where[] = "mi.sede_id = :sede_id";
        $params[':sede_id'] = $sede_actual;
    }
} elseif (!empty($_GET['sede_id'])) {
    $where[] = "mi.sede_id = :sede_id";
    $params[':sede_id'] = $_GET['sede_id'];
}

if (!empty($_GET['tipo'])) {
    $where[] = "mi.tipo_movimiento = :tipo";
    $params[':tipo'] = $_GET['tipo'];
}

if (!empty($_GET['material_id'])) {
    $where[] = "mi.material_id = :material_id";
    $params[':material_id'] = $_GET['material_id'];
}

if (!empty($_GET['fecha_desde'])) {
    $where[] = "DATE(mi.fecha_movimiento) >= :fecha_desde";
    $params[':fecha_desde'] = $_GET['fecha_desde'];
}

if (!empty($_GET['fecha_hasta'])) {
    $where[] = "DATE(mi.fecha_movimiento) <= :fecha_hasta";
    $params[':fecha_hasta'] = $_GET['fecha_hasta'];
}

$where_clause = implode(' AND ', $where);

// Contar total de registros
$count_query = "SELECT COUNT(*) as total
                FROM movimientos_inventario mi
                LEFT JOIN materiales m ON mi.material_id = m.id
                WHERE " . $where_clause;

$count_stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_registros = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Obtener registros
$query = "SELECT mi.*, m.nombre as material_nombre, m.codigo as material_codigo, 
                 u.nombre_completo as usuario_nombre,
                 ut.nombre_completo as tecnico_nombre,
                 s.nombre as sede_nombre
          FROM movimientos_inventario mi
          LEFT JOIN materiales m ON mi.material_id = m.id
          LEFT JOIN usuarios u ON mi.usuario_id = u.id
          LEFT JOIN usuarios ut ON mi.tecnico_asignado_id = ut.id
          LEFT JOIN sedes s ON mi.sede_id = s.id
          WHERE " . $where_clause . "
          ORDER BY mi.fecha_movimiento DESC
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener materiales para filtro
$materialModel = new Material($db);
$materiales = $materialModel->obtenerTodos();

$page_title = "Movimientos de Inventario";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5><i class="bi bi-arrow-left-right me-2"></i>Movimientos de Inventario</h5>
                    <p class="text-muted mb-0">Registro detallado de entradas y salidas</p>
                </div>
                <div>
                    <a href="entradas_materiales.php" class="btn btn-success me-2">
                        <i class="bi bi-plus-lg me-2"></i>Nueva Entrada
                    </a>
                    <a href="salidas_materiales.php" class="btn btn-danger">
                        <i class="bi bi-dash-lg me-2"></i>Nueva Salida
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tipo Movimiento</label>
                    <select name="tipo" class="form-select">
                        <option value="">Todos</option>
                        <option value="entrada" <?php echo (isset($_GET['tipo']) && $_GET['tipo'] == 'entrada') ? 'selected' : ''; ?>>Entrada</option>
                        <option value="salida" <?php echo (isset($_GET['tipo']) && $_GET['tipo'] == 'salida') ? 'selected' : ''; ?>>Salida</option>
                        <option value="ajuste" <?php echo (isset($_GET['tipo']) && $_GET['tipo'] == 'ajuste') ? 'selected' : ''; ?>>Ajuste</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Material</label>
                    <select name="material_id" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($materiales as $mat): ?>
                            <option value="<?php echo $mat['id']; ?>" <?php echo (isset($_GET['material_id']) && $_GET['material_id'] == $mat['id']) ? 'selected' : ''; ?>>
                                <?php echo $mat['nombre']; ?> (<?php echo $mat['codigo']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control" value="<?php echo $_GET['fecha_desde'] ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $_GET['fecha_hasta'] ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tabla de Movimientos -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Material</th>
                            <th>Cantidad</th>
                            <th>Motivo</th>
                            <th>Responsable</th>
                            <th>Técnico / Ref.</th>
                            <?php if (esSuperAdmin()): ?>
                                <th>Sede</th>
                            <?php endif; ?>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movimientos)): ?>
                            <tr>
                                <td colspan="<?php echo esSuperAdmin() ? 9 : 8; ?>" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                    No se encontraron movimientos con los filtros seleccionados
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($movimientos as $mov): ?>
                                <tr>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($mov['fecha_movimiento'])); ?><br>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($mov['fecha_movimiento'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($mov['tipo_movimiento'] == 'entrada'): ?>
                                            <span class="badge bg-success"><i class="bi bi-arrow-down me-1"></i>Entrada</span>
                                        <?php elseif ($mov['tipo_movimiento'] == 'salida'): ?>
                                            <span class="badge bg-danger"><i class="bi bi-arrow-up me-1"></i>Salida</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-arrow-left-right me-1"></i>Ajuste</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo $mov['material_nombre']; ?></strong><br>
                                        <small class="text-muted"><?php echo $mov['material_codigo']; ?></small>
                                    </td>
                                    <td>
                                        <span class="fw-bold fs-6"><?php echo $mov['cantidad']; ?></span>
                                    </td>
                                    <td><?php echo ucfirst($mov['motivo']); ?></td>
                                    <td>
                                        <small><?php echo $mov['usuario_nombre']; ?></small>
                                    </td>
                                    <td>
                                        <?php if ($mov['tecnico_nombre']): ?>
                                            <i class="bi bi-person-badge me-1 text-primary"></i><?php echo $mov['tecnico_nombre']; ?>
                                        <?php elseif ($mov['documento_referencia']): ?>
                                            <i class="bi bi-file-text me-1 text-muted"></i><?php echo $mov['documento_referencia']; ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <?php if (esSuperAdmin()): ?>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $mov['sede_nombre']; ?></span>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary"
                                                onclick="editarMovimiento(<?php echo htmlspecialchars(json_encode($mov)); ?>)"
                                                title="Editar Observaciones">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger"
                                                onclick="confirmarEliminacion(<?php echo $mov['id']; ?>)"
                                                title="Eliminar y Revertir Stock">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted">
                        Mostrando <?php echo count($movimientos); ?> de <?php echo $total_registros; ?> registros
                    </div>
                    <nav aria-label="Paginación">
                        <ul class="pagination mb-0">
                            <?php if ($pagina_actual > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual - 1])); ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <?php if ($i == 1 || $i == $total_paginas || ($i >= $pagina_actual - 2 && $i <= $pagina_actual + 2)): ?>
                                    <li class="page-item <?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php elseif ($i == $pagina_actual - 3 || $i == $pagina_actual + 3): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($pagina_actual < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual + 1])); ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Editar Movimiento -->
<div class="modal fade" id="modalEditarMovimiento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Movimiento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="actualizar_movimiento.php">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Solo se pueden editar los detalles descriptivos. Para corregir cantidades o materiales, elimine el movimiento y créelo de nuevo.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motivo</label>
                        <input type="text" name="motivo" id="edit_motivo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Documento Referencia</label>
                        <input type="text" name="documento_referencia" id="edit_documento" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" id="edit_observaciones" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function confirmarEliminacion(id) {
        if (confirm('¿Está seguro de eliminar este movimiento? Esta acción revertirá el stock del material.')) {
            window.location.href = '../almacen/eliminar_movimiento_general.php?id=' + id;
        }
    }

    function editarMovimiento(mov) {
        document.getElementById('edit_id').value = mov.id;
        document.getElementById('edit_motivo').value = mov.motivo;
        document.getElementById('edit_documento').value = mov.documento_referencia || '';
        document.getElementById('edit_observaciones').value = mov.observaciones || '';

        new bootstrap.Modal(document.getElementById('modalEditarMovimiento')).show();
    }
</script>

<?php include '../layouts/footer.php'; ?>