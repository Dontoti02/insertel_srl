<?php
/**
 * Dashboard del Superadministrador
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/Sede.php';
require_once '../../models/User.php';

if (!tieneRol(ROL_SUPERADMIN)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();
$sedeModel = new Sede($db);
$userModel = new User($db);

// Obtener estadísticas generales
$sedes = $sedeModel->obtenerTodas();
$total_sedes = count($sedes);
$sedes_activas = count(array_filter($sedes, function($s) { return $s['estado'] === 'activa'; }));

// Estadísticas de usuarios
$query_usuarios = "SELECT 
    COUNT(*) as total_usuarios,
    SUM(CASE WHEN rol_id = 5 THEN 1 ELSE 0 END) as superadmins,
    SUM(CASE WHEN rol_id = 1 THEN 1 ELSE 0 END) as administradores,
    SUM(CASE WHEN rol_id = 2 THEN 1 ELSE 0 END) as jefes_almacen,
    SUM(CASE WHEN rol_id = 3 THEN 1 ELSE 0 END) as asistentes,
    SUM(CASE WHEN rol_id = 4 THEN 1 ELSE 0 END) as tecnicos,
    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as usuarios_activos
FROM usuarios";
$stmt = $db->prepare($query_usuarios);
$stmt->execute();
$stats_usuarios = $stmt->fetch(PDO::FETCH_ASSOC);

// Estadísticas de materiales por sede
$query_materiales = "SELECT 
    s.nombre as sede_nombre,
    COUNT(m.id) as total_materiales,
    SUM(m.stock_actual) as total_stock,
    SUM(CASE WHEN m.estado = 'activo' THEN 1 ELSE 0 END) as materiales_activos
FROM sedes s
LEFT JOIN materiales m ON s.id = m.sede_id
WHERE s.estado = 'activa'
GROUP BY s.id, s.nombre
ORDER BY s.nombre";
$stmt = $db->prepare($query_materiales);
$stmt->execute();
$stats_materiales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Paginación de actividad reciente
$registros_por_pagina = 20;
$pagina_actividad = isset($_GET['pag_actividad']) ? max(1, (int)$_GET['pag_actividad']) : 1;
$offset_actividad = ($pagina_actividad - 1) * $registros_por_pagina;

// Filtros
$filtro_accion = isset($_GET['filtro_accion']) ? sanitizar($_GET['filtro_accion']) : '';
$filtro_usuario = isset($_GET['filtro_usuario']) ? sanitizar($_GET['filtro_usuario']) : '';
$filtro_fecha = isset($_GET['filtro_fecha']) ? sanitizar($_GET['filtro_fecha']) : '';

// Construir WHERE clause
$where_clauses = [];
$params = [];

if (!empty($filtro_accion)) {
    $where_clauses[] = "ha.accion LIKE ?";
    $params[] = '%' . $filtro_accion . '%';
}

if (!empty($filtro_usuario)) {
    $where_clauses[] = "u.nombre_completo LIKE ?";
    $params[] = '%' . $filtro_usuario . '%';
}

if (!empty($filtro_fecha)) {
    $where_clauses[] = "DATE(ha.fecha) = ?";
    $params[] = $filtro_fecha;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Contar total de registros de actividad con filtros
$query_total_actividad = "SELECT COUNT(*) as total FROM historial_actividades ha
                          INNER JOIN usuarios u ON ha.usuario_id = u.id
                          {$where_sql}";
$stmt_total = $db->prepare($query_total_actividad);
$stmt_total->execute($params);
$total_actividades = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas_actividad = ceil($total_actividades / $registros_por_pagina);

$page_title = "Dashboard Superadministrador";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5><i class="bi bi-speedometer2 me-2"></i>Dashboard Superadministrador</h5>
                    <p class="text-muted mb-0">Panel de control general del sistema multi-sede</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="sedes.php" class="btn btn-primary">
                        <i class="bi bi-building"></i> Gestionar Sedes
                    </a>
                    <a href="usuarios_globales.php" class="btn btn-success">
                        <i class="bi bi-people"></i> Usuarios Globales
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas Generales -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card bg-gradient-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Superadmins</h6>
                        <h2><?php echo $stats_usuarios['superadmins']; ?></h2>
                        <small>control total</small>
                    </div>
                    <i class="bi bi-shield-check" style="font-size: 3rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-gradient-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Sedes</h6>
                        <h2><?php echo $total_sedes; ?></h2>
                        <small><?php echo $sedes_activas; ?> activas</small>
                    </div>
                    <i class="bi bi-building" style="font-size: 3rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-gradient-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Usuarios</h6>
                        <h2><?php echo number_format($stats_usuarios['total_usuarios']); ?></h2>
                        <small><?php echo $stats_usuarios['usuarios_activos']; ?> activos</small>
                    </div>
                    <i class="bi bi-people" style="font-size: 3rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-gradient-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Administradores</h6>
                        <h2><?php echo $stats_usuarios['administradores']; ?></h2>
                        <small>por sede</small>
                    </div>
                    <i class="bi bi-person-badge" style="font-size: 3rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-gradient-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Técnicos</h6>
                        <h2><?php echo $stats_usuarios['tecnicos']; ?></h2>
                        <small>en todas las sedes</small>
                    </div>
                    <i class="bi bi-tools" style="font-size: 3rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-gradient-secondary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Jefes Almacén</h6>
                        <h2><?php echo $stats_usuarios['jefes_almacen']; ?></h2>
                        <small>por sede</small>
                    </div>
                    <i class="bi bi-person-gear" style="font-size: 3rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Distribución de Roles -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="content-card">
            <h5 class="mb-3"><i class="bi bi-pie-chart me-2"></i>Distribución de Roles</h5>
            <div class="row">
                <div class="col-4 mb-3">
                    <div class="d-flex align-items-center">
                        <div class="role-indicator bg-danger me-3"></div>
                        <div>
                            <h6 class="mb-0"><?php echo $stats_usuarios['superadmins']; ?></h6>
                            <small class="text-muted">Superadministradores</small>
                        </div>
                    </div>
                </div>
                <div class="col-4 mb-3">
                    <div class="d-flex align-items-center">
                        <div class="role-indicator bg-primary me-3"></div>
                        <div>
                            <h6 class="mb-0"><?php echo $stats_usuarios['administradores']; ?></h6>
                            <small class="text-muted">Administradores</small>
                        </div>
                    </div>
                </div>
                <div class="col-4 mb-3">
                    <div class="d-flex align-items-center">
                        <div class="role-indicator bg-success me-3"></div>
                        <div>
                            <h6 class="mb-0"><?php echo $stats_usuarios['jefes_almacen']; ?></h6>
                            <small class="text-muted">Jefes de Almacén</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 mb-3">
                    <div class="d-flex align-items-center">
                        <div class="role-indicator bg-info me-3"></div>
                        <div>
                            <h6 class="mb-0"><?php echo $stats_usuarios['asistentes']; ?></h6>
                            <small class="text-muted">Asistentes</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 mb-3">
                    <div class="d-flex align-items-center">
                        <div class="role-indicator bg-warning me-3"></div>
                        <div>
                            <h6 class="mb-0"><?php echo $stats_usuarios['tecnicos']; ?></h6>
                            <small class="text-muted">Técnicos</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="content-card">
            <h5 class="mb-3"><i class="bi bi-graph-up me-2"></i>Acciones Rápidas</h5>
            <div class="d-grid gap-2">
                <a href="sedes.php?accion=crear" class="btn btn-outline-primary">
                    <i class="bi bi-plus-circle me-2"></i>Crear Nueva Sede
                </a>
                <a href="crear_admin.php" class="btn btn-outline-success">
                    <i class="bi bi-person-plus me-2"></i>Crear Administrador
                </a>
                <a href="reportes_globales.php" class="btn btn-outline-info">
                    <i class="bi bi-file-earmark-bar-graph me-2"></i>Reportes Globales
                </a>
                <a href="configuracion_sistema.php" class="btn btn-outline-warning">
                    <i class="bi bi-gear me-2"></i>Configuración Sistema
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Resumen por Sede -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-3"><i class="bi bi-building me-2"></i>Resumen por Sede</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Sede</th>
                            <th>Código</th>
                            <th>Responsable</th>
                            <th>Usuarios</th>
                            <th>Materiales</th>
                            <th>Stock Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sedes as $sede): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="sede-indicator <?php echo $sede['estado'] === 'activa' ? 'bg-success' : 'bg-secondary'; ?> me-2"></div>
                                    <strong><?php echo $sede['nombre']; ?></strong>
                                </div>
                            </td>
                            <td><code><?php echo $sede['codigo']; ?></code></td>
                            <td><?php echo $sede['responsable_nombre'] ?? '<span class="text-muted">Sin asignar</span>'; ?></td>
                            <td>
                                <span class="badge bg-primary"><?php echo $sede['total_usuarios']; ?></span>
                            </td>
                            <td>
                                <?php 
                                $materiales_sede = array_filter($stats_materiales, function($m) use ($sede) {
                                    return $m['sede_nombre'] === $sede['nombre'];
                                });

                                // Obtener la primera fila de materiales de la sede, si existe
                                $materiales_sede_primero = !empty($materiales_sede) ? reset($materiales_sede) : null;

                                $total_materiales_sede = $materiales_sede_primero['total_materiales'] ?? 0;
                                ?>
                                <span class="badge bg-success"><?php echo $total_materiales_sede; ?></span>
                            </td>
                            <td>
                                <?php 
                                $total_stock_sede = $materiales_sede_primero ? number_format($materiales_sede_primero['total_stock']) : '0';
                                ?>
                                <?php echo $total_stock_sede; ?> unidades
                            </td>
                            <td>
                                <span class="badge <?php echo $sede['estado'] === 'activa' ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo ucfirst($sede['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="sede_detalle.php?id=<?php echo $sede['id']; ?>" 
                                       class="btn btn-outline-primary" title="Ver Detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="sedes.php?accion=editar&id=<?php echo $sede['id']; ?>" 
                                       class="btn btn-outline-warning" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($sede['total_usuarios'] == 0): ?>
                                    <button class="btn btn-outline-danger" 
                                            onclick="confirmarEliminar(<?php echo $sede['id']; ?>)" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Actividad Reciente -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-3"><i class="bi bi-clock-history me-2"></i>Actividad Reciente del Sistema</h5>
            
            <!-- Filtros -->
            <form method="GET" class="row g-3 mb-4 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Filtrar por Acción:</label>
                    <input type="text" name="filtro_accion" class="form-control" 
                           placeholder="Ej: crear, editar, eliminar..." 
                           value="<?php echo htmlspecialchars($filtro_accion); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Filtrar por Usuario:</label>
                    <input type="text" name="filtro_usuario" class="form-control" 
                           placeholder="Nombre del usuario..." 
                           value="<?php echo htmlspecialchars($filtro_usuario); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Filtrar por Fecha:</label>
                    <input type="date" name="filtro_fecha" class="form-control" 
                           value="<?php echo htmlspecialchars($filtro_fecha); ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-2"></i>Filtrar
                    </button>
                    <?php if (!empty($filtro_accion) || !empty($filtro_usuario) || !empty($filtro_fecha)): ?>
                    <a href="dashboard.php" class="btn btn-secondary w-100 mt-2">
                        <i class="bi bi-arrow-clockwise me-2"></i>Limpiar
                    </a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Tabla de Actividad -->
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha/Hora</th>
                            <th>Acción</th>
                            <th>Usuario</th>
                            <th>Sede</th>
                            <th>Descripción</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Obtener actividad reciente con paginación y filtros
                        $query_actividad = "SELECT ha.*, u.nombre_completo, s.nombre as sede_nombre
                                           FROM historial_actividades ha
                                           INNER JOIN usuarios u ON ha.usuario_id = u.id
                                           LEFT JOIN sedes s ON u.sede_id = s.id
                                           {$where_sql}
                                           ORDER BY ha.fecha DESC
                                           LIMIT ? OFFSET ?";
                        $stmt = $db->prepare($query_actividad);
                        
                        // Agregar parámetros de paginación
                        $params_paginacion = array_merge($params, [$registros_por_pagina, $offset_actividad]);
                        $stmt->execute($params_paginacion);
                        $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (empty($actividades)):
                        ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="bi bi-inbox me-2"></i>No hay registros de actividad
                            </td>
                        </tr>
                        <?php else:
                            foreach ($actividades as $actividad):
                        ?>
                        <tr>
                            <td>
                                <small class="text-muted">
                                    <?php echo formatearFechaHora($actividad['fecha']); ?>
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo ucfirst($actividad['accion']); ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo $actividad['nombre_completo']; ?></strong>
                            </td>
                            <td>
                                <?php if ($actividad['sede_nombre']): ?>
                                    <span class="badge bg-secondary"><?php echo $actividad['sede_nombre']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small><?php echo $actividad['descripcion']; ?></small>
                            </td>
                            <td>
                                <code class="small"><?php echo $actividad['ip_address'] ?? '-'; ?></code>
                            </td>
                        </tr>
                        <?php endforeach;
                        endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($total_paginas_actividad > 1): ?>
            <nav aria-label="Paginación de Actividad" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($pagina_actividad > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?pag_actividad=1<?php echo !empty($filtro_accion) ? '&filtro_accion=' . urlencode($filtro_accion) : ''; ?><?php echo !empty($filtro_usuario) ? '&filtro_usuario=' . urlencode($filtro_usuario) : ''; ?><?php echo !empty($filtro_fecha) ? '&filtro_fecha=' . urlencode($filtro_fecha) : ''; ?>">
                            <i class="bi bi-chevron-double-left"></i> Primera
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?pag_actividad=<?php echo $pagina_actividad - 1; ?><?php echo !empty($filtro_accion) ? '&filtro_accion=' . urlencode($filtro_accion) : ''; ?><?php echo !empty($filtro_usuario) ? '&filtro_usuario=' . urlencode($filtro_usuario) : ''; ?><?php echo !empty($filtro_fecha) ? '&filtro_fecha=' . urlencode($filtro_fecha) : ''; ?>">
                            <i class="bi bi-chevron-left"></i> Anterior
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php
                    // Mostrar números de página (máximo 5 botones)
                    $inicio = max(1, $pagina_actividad - 2);
                    $fin = min($total_paginas_actividad, $pagina_actividad + 2);
                    
                    if ($inicio > 1): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif;
                    
                    for ($i = $inicio; $i <= $fin; $i++):
                        $activa = ($i == $pagina_actividad) ? 'active' : '';
                    ?>
                    <li class="page-item <?php echo $activa; ?>">
                        <a class="page-link" href="?pag_actividad=<?php echo $i; ?><?php echo !empty($filtro_accion) ? '&filtro_accion=' . urlencode($filtro_accion) : ''; ?><?php echo !empty($filtro_usuario) ? '&filtro_usuario=' . urlencode($filtro_usuario) : ''; ?><?php echo !empty($filtro_fecha) ? '&filtro_fecha=' . urlencode($filtro_fecha) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor;
                    
                    if ($fin < $total_paginas_actividad): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>

                    <?php if ($pagina_actividad < $total_paginas_actividad): ?>
                    <li class="page-item">
                        <a class="page-link" href="?pag_actividad=<?php echo $pagina_actividad + 1; ?><?php echo !empty($filtro_accion) ? '&filtro_accion=' . urlencode($filtro_accion) : ''; ?><?php echo !empty($filtro_usuario) ? '&filtro_usuario=' . urlencode($filtro_usuario) : ''; ?><?php echo !empty($filtro_fecha) ? '&filtro_fecha=' . urlencode($filtro_fecha) : ''; ?>">
                            Siguiente <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?pag_actividad=<?php echo $total_paginas_actividad; ?><?php echo !empty($filtro_accion) ? '&filtro_accion=' . urlencode($filtro_accion) : ''; ?><?php echo !empty($filtro_usuario) ? '&filtro_usuario=' . urlencode($filtro_usuario) : ''; ?><?php echo !empty($filtro_fecha) ? '&filtro_fecha=' . urlencode($filtro_fecha) : ''; ?>">
                            Última <i class="bi bi-chevron-double-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="text-center text-muted small mt-2">
                Mostrando página <strong><?php echo $pagina_actividad; ?></strong> de <strong><?php echo $total_paginas_actividad; ?></strong> 
                (<?php echo $total_actividades; ?> registros totales)
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function confirmarEliminar(sedeId) {
    if (confirm('¿Está seguro de que desea eliminar esta sede? Esta acción no se puede deshacer.')) {
        window.location.href = 'sedes.php?accion=eliminar&id=' + sedeId;
    }
}
</script>

<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
}
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
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}
.bg-gradient-secondary {
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
}

.role-indicator, .sede-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}
</style>

<?php include '../layouts/footer.php'; ?>
