<?php
/**
 * Auditoría del Sistema - Superadministrador
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/User.php';

if (!tieneRol(ROL_SUPERADMIN)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);

// Obtener filtros
$filtros = [];
$params = [];
$where = ["1=1"];

if (!empty($_GET['usuario_id'])) {
    $filtros['usuario_id'] = (int)$_GET['usuario_id'];
    $where[] = "h.usuario_id = :usuario_id";
    $params[':usuario_id'] = $filtros['usuario_id'];
}
if (!empty($_GET['modulo'])) {
    $filtros['modulo'] = sanitizar($_GET['modulo']);
    $where[] = "h.modulo = :modulo";
    $params[':modulo'] = $filtros['modulo'];
}
if (!empty($_GET['fecha_desde'])) {
    $filtros['fecha_desde'] = $_GET['fecha_desde'];
    $where[] = "DATE(h.fecha) >= :fecha_desde";
    $params[':fecha_desde'] = $filtros['fecha_desde'];
}
if (!empty($_GET['fecha_hasta'])) {
    $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
    $where[] = "DATE(h.fecha) <= :fecha_hasta";
    $params[':fecha_hasta'] = $filtros['fecha_hasta'];
}

// Paginación
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$registros_por_pagina = 50;
$offset = ($pagina - 1) * $registros_por_pagina;

// Total de registros
$query_total = "SELECT COUNT(*) FROM historial_actividades h WHERE " . implode(' AND ', $where);
$stmt_total = $db->prepare($query_total);
$stmt_total->execute($params);
$total_registros = $stmt_total->fetchColumn();
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Obtener registros de auditoría
$query = "SELECT h.*, u.username, u.nombre_completo
          FROM historial_actividades h
          LEFT JOIN usuarios u ON h.usuario_id = u.id
          WHERE " . implode(' AND ', $where) . "
          ORDER BY h.fecha DESC
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
foreach ($params as $key => &$val) {
    $stmt->bindParam($key, $val);
}
$stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener datos para filtros
$usuarios = $userModel->obtenerTodosGlobales();
$modulos = $db->query("SELECT DISTINCT modulo FROM historial_actividades ORDER BY modulo")->fetchAll(PDO::FETCH_COLUMN);

$page_title = "Auditoría del Sistema";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <h5><i class="bi bi-shield-check me-2"></i>Auditoría del Sistema</h5>
            <p class="text-muted mb-0">Registro de todas las acciones importantes realizadas en el sistema.</p>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <form method="GET" class="row g-3">
                <div class="col-md-3"><label class="form-label">Usuario</label><select name="usuario_id" class="form-select"><option value="">Todos</option><?php foreach ($usuarios as $usuario): ?><option value="<?php echo $usuario['id']; ?>" <?php echo (isset($filtros['usuario_id']) && $filtros['usuario_id'] == $usuario['id']) ? 'selected' : ''; ?>><?php echo $usuario['nombre_completo']; ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><label class="form-label">Módulo</label><select name="modulo" class="form-select"><option value="">Todos</option><?php foreach ($modulos as $modulo): ?><option value="<?php echo $modulo; ?>" <?php echo (isset($filtros['modulo']) && $filtros['modulo'] == $modulo) ? 'selected' : ''; ?>><?php echo ucfirst($modulo); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2"><label class="form-label">Desde</label><input type="date" name="fecha_desde" class="form-control" value="<?php echo $filtros['fecha_desde'] ?? ''; ?>"></div>
                <div class="col-md-2"><label class="form-label">Hasta</label><input type="date" name="fecha_hasta" class="form-control" value="<?php echo $filtros['fecha_hasta'] ?? ''; ?>"></div>
                <div class="col-md-2"><label class="form-label">&nbsp;</label><div class="d-grid"><button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filtrar</button></div></div>
            </form>
        </div>
    </div>
</div>

<!-- Tabla de Auditoría -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Usuario</th>
                            <th>Módulo</th>
                            <th>Acción</th>
                            <th>Descripción</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($actividades)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No hay registros de actividad</td></tr>
                        <?php else: ?>
                            <?php foreach ($actividades as $actividad): ?>
                            <tr>
                                <td><?php echo formatearFechaHora($actividad['fecha']); ?></td>
                                <td><?php echo $actividad['nombre_completo'] ?? 'Sistema'; ?></td>
                                <td><?php echo ucfirst($actividad['modulo']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo $actividad['accion']; ?></span></td>
                                <td><?php echo $actividad['descripcion']; ?></td>
                                <td><?php echo $actividad['ip_address']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <li class="page-item <?php echo ($i == $pagina) ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
