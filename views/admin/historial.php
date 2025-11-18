<?php
/**
 * Historial de Actividades - Administrador
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';

if (!tieneRol(ROL_ADMINISTRADOR)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();

// Paginación
$registros_por_pagina = 25;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Filtros
$where = ["1=1"];
$params = [];

if (!empty($_GET['usuario_id'])) {
    $where[] = "h.usuario_id = :usuario_id";
    $params[':usuario_id'] = (int)$_GET['usuario_id'];
}

if (!empty($_GET['modulo'])) {
    $where[] = "h.modulo = :modulo";
    $params[':modulo'] = $_GET['modulo'];
}

if (!empty($_GET['fecha_desde'])) {
    $where[] = "DATE(h.fecha) >= :fecha_desde";
    $params[':fecha_desde'] = $_GET['fecha_desde'];
}

if (!empty($_GET['fecha_hasta'])) {
    $where[] = "DATE(h.fecha) <= :fecha_hasta";
    $params[':fecha_hasta'] = $_GET['fecha_hasta'];
}

$where_clause = implode(' AND ', $where);

// Contar total de registros
$count_query = "SELECT COUNT(*) as total
                FROM historial_actividades h
                INNER JOIN usuarios u ON h.usuario_id = u.id
                WHERE " . $where_clause;

$count_stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_registros = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Obtener registros de la página actual
$query = "SELECT h.*, u.nombre_completo, u.username
          FROM historial_actividades h
          INNER JOIN usuarios u ON h.usuario_id = u.id
          WHERE " . $where_clause . "
          ORDER BY h.fecha DESC
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener usuarios para filtro
$usuarios = $db->query("SELECT id, nombre_completo FROM usuarios ORDER BY nombre_completo")->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Historial de Actividades";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <h5><i class="bi bi-clock-history me-2"></i>Auditoría del Sistema</h5>
            <p class="text-muted mb-0">Registro de todas las acciones realizadas en el sistema</p>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Usuario</label>
                    <select name="usuario_id" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($usuarios as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo (isset($_GET['usuario_id']) && $_GET['usuario_id'] == $user['id']) ? 'selected' : ''; ?>>
                            <?php echo $user['nombre_completo']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Módulo</label>
                    <select name="modulo" class="form-select">
                        <option value="">Todos</option>
                        <option value="usuarios" <?php echo (isset($_GET['modulo']) && $_GET['modulo'] == 'usuarios') ? 'selected' : ''; ?>>Usuarios</option>
                        <option value="materiales" <?php echo (isset($_GET['modulo']) && $_GET['modulo'] == 'materiales') ? 'selected' : ''; ?>>Materiales</option>
                        <option value="solicitudes" <?php echo (isset($_GET['modulo']) && $_GET['modulo'] == 'solicitudes') ? 'selected' : ''; ?>>Solicitudes</option>
                        <option value="movimientos" <?php echo (isset($_GET['modulo']) && $_GET['modulo'] == 'movimientos') ? 'selected' : ''; ?>>Movimientos</option>
                        <option value="autenticacion" <?php echo (isset($_GET['modulo']) && $_GET['modulo'] == 'autenticacion') ? 'selected' : ''; ?>>Autenticación</option>
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
                <div class="col-md-3">
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

<!-- Tabla de Historial -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Fecha/Hora</th>
                            <th>Usuario</th>
                            <th>Módulo</th>
                            <th>Acción</th>
                            <th>Descripción</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($actividades)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No hay actividades registradas con los filtros seleccionados
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($actividades as $act): ?>
                            <tr>
                                <td><?php echo formatearFechaHora($act['fecha']); ?></td>
                                <td>
                                    <strong><?php echo $act['nombre_completo']; ?></strong><br>
                                    <small class="text-muted"><?php echo $act['username']; ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo ucfirst($act['modulo']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $color = 'primary';
                                    if ($act['accion'] == 'crear') $color = 'success';
                                    if ($act['accion'] == 'editar') $color = 'warning';
                                    if ($act['accion'] == 'eliminar') $color = 'danger';
                                    if ($act['accion'] == 'login') $color = 'info';
                                    ?>
                                    <span class="badge bg-<?php echo $color; ?>">
                                        <?php echo ucfirst($act['accion']); ?>
                                    </span>
                                </td>
                                <td><?php echo $act['descripcion'] ?: '-'; ?></td>
                                <td><code><?php echo $act['ip_address']; ?></code></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Información de paginación -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Mostrando <?php echo count($actividades); ?> de <?php echo $total_registros; ?> registros
                    (Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?>)
                </div>
                
                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                <nav aria-label="Paginación historial">
                    <ul class="pagination pagination-sm mb-0">
                        <!-- Botón Anterior -->
                        <?php if ($pagina_actual > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual - 1])); ?>">
                                <i class="bi bi-chevron-left"></i> Anterior
                            </a>
                        </li>
                        <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link"><i class="bi bi-chevron-left"></i> Anterior</span>
                        </li>
                        <?php endif; ?>
                        
                        <!-- Números de página -->
                        <?php
                        $inicio = max(1, $pagina_actual - 2);
                        $fin = min($total_paginas, $pagina_actual + 2);
                        
                        if ($inicio > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => 1])); ?>">1</a>
                            </li>
                            <?php if ($inicio > 2): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $inicio; $i <= $fin; $i++): ?>
                        <li class="page-item <?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($fin < $total_paginas): ?>
                            <?php if ($fin < $total_paginas - 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $total_paginas])); ?>">
                                    <?php echo $total_paginas; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Botón Siguiente -->
                        <?php if ($pagina_actual < $total_paginas): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual + 1])); ?>">
                                Siguiente <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                        <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link">Siguiente <i class="bi bi-chevron-right"></i></span>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
