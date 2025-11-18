<?php
/**
 * Gestión de Materiales - Asistente de Almacén
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/Material.php';

if (!tieneRol(ROL_ASISTENTE_ALMACEN)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();
$materialModel = new Material($db);

$filtros = [];
if (!empty($_GET['buscar'])) {
    $filtros['buscar'] = sanitizar($_GET['buscar']);
}
if (!empty($_GET['categoria_id'])) {
    $filtros['categoria_id'] = (int)$_GET['categoria_id'];
}

// Paginación
$registros_por_pagina = 20;
$pagina_actual = (int)($_GET['pagina'] ?? 1);
if ($pagina_actual < 1) $pagina_actual = 1;

// Obtener total de registros
$query_total = "SELECT COUNT(*) as total FROM materiales WHERE 1=1";
if (!empty($filtros['buscar'])) {
    $query_total .= " AND (codigo LIKE '%{$filtros['buscar']}%' OR nombre LIKE '%{$filtros['buscar']}%')";
}
if (!empty($filtros['categoria_id'])) {
    $query_total .= " AND categoria_id = {$filtros['categoria_id']}";
}
$total_registros = $db->query($query_total)->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Asegurar que la página actual no exceda el total
if ($pagina_actual > $total_paginas && $total_paginas > 0) {
    $pagina_actual = $total_paginas;
}

$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Obtener materiales con paginación
$query_materiales = "SELECT m.*, c.nombre as categoria_nombre 
                     FROM materiales m 
                     LEFT JOIN categorias_materiales c ON m.categoria_id = c.id 
                     WHERE 1=1";
if (!empty($filtros['buscar'])) {
    $query_materiales .= " AND (m.codigo LIKE '%{$filtros['buscar']}%' OR m.nombre LIKE '%{$filtros['buscar']}%')";
}
if (!empty($filtros['categoria_id'])) {
    $query_materiales .= " AND m.categoria_id = {$filtros['categoria_id']}";
}
$query_materiales .= " ORDER BY m.nombre ASC LIMIT {$registros_por_pagina} OFFSET {$offset}";

$materiales = $db->query($query_materiales)->fetchAll(PDO::FETCH_ASSOC);

$query_cat = "SELECT * FROM categorias_materiales ORDER BY nombre";
$categorias = $db->query($query_cat)->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Consulta de Materiales";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <h5><i class="bi bi-box me-2"></i>Materiales Disponibles</h5>
            <p class="text-muted mb-0">Consulta el inventario disponible en almacén</p>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="buscar" class="form-control" placeholder="Buscar por código o nombre..." value="<?php echo $_GET['buscar'] ?? ''; ?>">
                </div>
                <div class="col-md-4">
                    <select name="categoria_id" class="form-select">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_GET['categoria_id']) && $_GET['categoria_id'] == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo $cat['nombre']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Material</th>
                            <th>Categoría</th>
                            <th>Stock</th>
                            <th>Ubicación</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($materiales)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No se encontraron materiales</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($materiales as $mat): ?>
                            <tr>
                                <td><code><?php echo $mat['codigo']; ?></code></td>
                                <td><strong><?php echo $mat['nombre']; ?></strong></td>
                                <td><?php echo $mat['categoria_nombre'] ?? '-'; ?></td>
                                <td>
                                    <?php if ($mat['stock_actual'] <= $mat['stock_minimo']): ?>
                                    <span class="badge bg-danger">
                                        <?php echo $mat['stock_actual']; ?> <?php echo $mat['unidad']; ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-success">
                                        <?php echo $mat['stock_actual']; ?> <?php echo $mat['unidad']; ?>
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $mat['ubicacion'] ?? '-'; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo getBadgeEstado($mat['estado']); ?>">
                                        <?php echo ucfirst($mat['estado']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                <div class="text-muted small">
                    Mostrando <strong><?php echo (($pagina_actual - 1) * $registros_por_pagina) + 1; ?></strong> 
                    a <strong><?php echo min($pagina_actual * $registros_por_pagina, $total_registros); ?></strong> 
                    de <strong><?php echo $total_registros; ?></strong> materiales
                </div>
                
                <nav aria-label="Paginación">
                    <ul class="pagination mb-0">
                        <!-- Botón Primera Página -->
                        <?php if ($pagina_actual > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?pagina=1<?php echo !empty($_GET['buscar']) ? '&buscar=' . urlencode($_GET['buscar']) : ''; ?><?php echo !empty($_GET['categoria_id']) ? '&categoria_id=' . $_GET['categoria_id'] : ''; ?>">
                                    <i class="bi bi-chevron-double-left"></i>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="bi bi-chevron-double-left"></i></span>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Botón Anterior -->
                        <?php if ($pagina_actual > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?><?php echo !empty($_GET['buscar']) ? '&buscar=' . urlencode($_GET['buscar']) : ''; ?><?php echo !empty($_GET['categoria_id']) ? '&categoria_id=' . $_GET['categoria_id'] : ''; ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="bi bi-chevron-left"></i></span>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Números de página -->
                        <?php 
                        $inicio = max(1, $pagina_actual - 2);
                        $fin = min($total_paginas, $pagina_actual + 2);
                        
                        if ($inicio > 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = $inicio; $i <= $fin; $i++): ?>
                            <?php if ($i == $pagina_actual): ?>
                                <li class="page-item active">
                                    <span class="page-link"><?php echo $i; ?></span>
                                </li>
                            <?php else: ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo !empty($_GET['buscar']) ? '&buscar=' . urlencode($_GET['buscar']) : ''; ?><?php echo !empty($_GET['categoria_id']) ? '&categoria_id=' . $_GET['categoria_id'] : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($fin < $total_paginas): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Botón Siguiente -->
                        <?php if ($pagina_actual < $total_paginas): ?>
                            <li class="page-item">
                                <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?><?php echo !empty($_GET['buscar']) ? '&buscar=' . urlencode($_GET['buscar']) : ''; ?><?php echo !empty($_GET['categoria_id']) ? '&categoria_id=' . $_GET['categoria_id'] : ''; ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="bi bi-chevron-right"></i></span>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Botón Última Página -->
                        <?php if ($pagina_actual < $total_paginas): ?>
                            <li class="page-item">
                                <a class="page-link" href="?pagina=<?php echo $total_paginas; ?><?php echo !empty($_GET['buscar']) ? '&buscar=' . urlencode($_GET['buscar']) : ''; ?><?php echo !empty($_GET['categoria_id']) ? '&categoria_id=' . $_GET['categoria_id'] : ''; ?>">
                                    <i class="bi bi-chevron-double-right"></i>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="bi bi-chevron-double-right"></i></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
