<?php
/**
 * Detalle de Sede - Superadministrador
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

$sede_id = $_GET['id'] ?? null;

if (!$sede_id) {
    setMensaje('error', 'ID de sede no especificado');
    header('Location: sedes.php');
    exit;
}

$sede = $sedeModel->obtenerPorId($sede_id);

if (!$sede) {
    setMensaje('error', 'Sede no encontrada');
    header('Location: sedes.php');
    exit;
}

// Estadísticas de la sede
$stats = [];

// Usuarios por rol
$query_usuarios = "SELECT 
                    COUNT(*) as total_usuarios,
                    SUM(CASE WHEN rol_id = ? THEN 1 ELSE 0 END) as administradores,
                    SUM(CASE WHEN rol_id = ? THEN 1 ELSE 0 END) as jefes_almacen,
                    SUM(CASE WHEN rol_id = ? THEN 1 ELSE 0 END) as asistentes,
                    SUM(CASE WHEN rol_id = ? THEN 1 ELSE 0 END) as tecnicos
                  FROM usuarios WHERE sede_id = ?";
$stmt_usuarios = $db->prepare($query_usuarios);
$stmt_usuarios->execute([ROL_ADMINISTRADOR, ROL_JEFE_ALMACEN, ROL_ASISTENTE_ALMACEN, ROL_TECNICO, $sede_id]);
$stats['usuarios'] = $stmt_usuarios->fetch(PDO::FETCH_ASSOC);

// Inventario
$query_inv = "SELECT 
                COUNT(*) as total_materiales,
                SUM(stock_actual) as total_items,
                SUM(stock_actual * costo_unitario) as valor_total,
                COUNT(CASE WHEN stock_actual <= stock_minimo THEN 1 END) as criticos,
                COUNT(CASE WHEN stock_actual > stock_maximo THEN 1 END) as exceso
              FROM materiales WHERE sede_id = ?";
$stmt_inv = $db->prepare($query_inv);
$stmt_inv->execute([$sede_id]);
$stats['inventario'] = $stmt_inv->fetch(PDO::FETCH_ASSOC);

// Movimientos últimos 30 días
$query_mov = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN tipo_movimiento = 'entrada' THEN 1 ELSE 0 END) as entradas,
                SUM(CASE WHEN tipo_movimiento = 'salida' THEN 1 ELSE 0 END) as salidas,
                SUM(CASE WHEN tipo_movimiento = 'ajuste' THEN 1 ELSE 0 END) as ajustes
              FROM movimientos_inventario 
              WHERE sede_id = ? AND fecha_movimiento >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$stmt_mov = $db->prepare($query_mov);
$stmt_mov->execute([$sede_id]);
$stats['movimientos'] = $stmt_mov->fetch(PDO::FETCH_ASSOC);

// Solicitudes
$query_sol = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'aprobada' THEN 1 ELSE 0 END) as aprobadas,
                SUM(CASE WHEN estado = 'rechazada' THEN 1 ELSE 0 END) as rechazadas
              FROM solicitudes WHERE sede_id = ?";
$stmt_sol = $db->prepare($query_sol);
$stmt_sol->execute([$sede_id]);
$stats['solicitudes'] = $stmt_sol->fetch(PDO::FETCH_ASSOC);

// Usuarios de la sede
$query_users = "SELECT id, username, nombre_completo, email, rol_id, estado, ultimo_acceso 
                FROM usuarios WHERE sede_id = ? ORDER BY nombre_completo";
$stmt_users = $db->prepare($query_users);
$stmt_users->execute([$sede_id]);
$usuarios_sede = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

// Materiales críticos
$query_criticos = "SELECT id, codigo, nombre, stock_actual, stock_minimo, stock_maximo, unidad
                   FROM materiales WHERE sede_id = ? AND stock_actual <= stock_minimo
                   ORDER BY stock_actual ASC LIMIT 10";
$stmt_criticos = $db->prepare($query_criticos);
$stmt_criticos->execute([$sede_id]);
$materiales_criticos = $stmt_criticos->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Detalle de Sede: " . $sede['nombre'];
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1"><?php echo $sede['nombre']; ?></h4>
                <p class="text-muted mb-0">
                    <i class="bi bi-geo-alt me-2"></i><?php echo $sede['ubicacion'] ?? 'No especificada'; ?>
                </p>
            </div>
            <div>
                <span class="badge bg-<?php echo $sede['estado'] === 'activa' ? 'success' : 'secondary'; ?> fs-6">
                    <?php echo ucfirst($sede['estado']); ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas Principales -->
<div class="row mb-4">
    <div class="col-lg-3 mb-4">
        <div class="content-card">
            <div class="d-flex align-items-center mb-3">
                <i class="bi bi-people-fill me-2" style="color: #6366f1; font-size: 1.5rem;"></i>
                <h6 class="mb-0">Usuarios</h6>
            </div>
            <h3 class="mb-2"><?php echo $stats['usuarios']['total_usuarios'] ?? 0; ?></h3>
            <ul class="list-group list-group-sm">
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Administradores:</span>
                    <strong><?php echo $stats['usuarios']['administradores'] ?? 0; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Jefes de Almacén:</span>
                    <strong><?php echo $stats['usuarios']['jefes_almacen'] ?? 0; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Asistentes:</span>
                    <strong><?php echo $stats['usuarios']['asistentes'] ?? 0; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Técnicos:</span>
                    <strong><?php echo $stats['usuarios']['tecnicos'] ?? 0; ?></strong>
                </li>
            </ul>
        </div>
    </div>

    <div class="col-lg-3 mb-4">
        <div class="content-card">
            <div class="d-flex align-items-center mb-3">
                <i class="bi bi-box-seam me-2" style="color: #10b981; font-size: 1.5rem;"></i>
                <h6 class="mb-0">Inventario</h6>
            </div>
            <h3 class="mb-2"><?php echo $stats['inventario']['total_materiales'] ?? 0; ?></h3>
            <ul class="list-group list-group-sm">
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Total Items:</span>
                    <strong><?php echo number_format($stats['inventario']['total_items'] ?? 0); ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Valor Total:</span>
                    <strong><?php echo formatearMoneda($stats['inventario']['valor_total'] ?? 0); ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Stock Crítico:</span>
                    <strong class="text-danger"><?php echo $stats['inventario']['criticos'] ?? 0; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Stock Exceso:</span>
                    <strong class="text-warning"><?php echo $stats['inventario']['exceso'] ?? 0; ?></strong>
                </li>
            </ul>
        </div>
    </div>

    <div class="col-lg-3 mb-4">
        <div class="content-card">
            <div class="d-flex align-items-center mb-3">
                <i class="bi bi-arrow-left-right me-2" style="color: #06b6d4; font-size: 1.5rem;"></i>
                <h6 class="mb-0">Movimientos (30 días)</h6>
            </div>
            <h3 class="mb-2"><?php echo $stats['movimientos']['total'] ?? 0; ?></h3>
            <ul class="list-group list-group-sm">
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Entradas:</span>
                    <strong class="text-success"><?php echo $stats['movimientos']['entradas'] ?? 0; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Salidas:</span>
                    <strong class="text-danger"><?php echo $stats['movimientos']['salidas'] ?? 0; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Ajustes:</span>
                    <strong class="text-warning"><?php echo $stats['movimientos']['ajustes'] ?? 0; ?></strong>
                </li>
            </ul>
        </div>
    </div>

    <div class="col-lg-3 mb-4">
        <div class="content-card">
            <div class="d-flex align-items-center mb-3">
                <i class="bi bi-clipboard-check me-2" style="color: #f59e0b; font-size: 1.5rem;"></i>
                <h6 class="mb-0">Solicitudes</h6>
            </div>
            <h3 class="mb-2"><?php echo $stats['solicitudes']['total'] ?? 0; ?></h3>
            <ul class="list-group list-group-sm">
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Pendientes:</span>
                    <strong class="text-warning"><?php echo $stats['solicitudes']['pendientes'] ?? 0; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Aprobadas:</span>
                    <strong class="text-success"><?php echo $stats['solicitudes']['aprobadas'] ?? 0; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Rechazadas:</span>
                    <strong class="text-danger"><?php echo $stats['solicitudes']['rechazadas'] ?? 0; ?></strong>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Información de la Sede -->
<div class="row mb-4">
    <div class="col-lg-6 mb-4">
        <div class="content-card">
            <h5 class="mb-3"><i class="bi bi-info-circle me-2"></i>Información General</h5>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Código:</span>
                    <strong><?php echo $sede['codigo']; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Nombre:</span>
                    <strong><?php echo $sede['nombre']; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Ubicación:</span>
                    <strong><?php echo $sede['ubicacion'] ?? 'No especificada'; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Teléfono:</span>
                    <strong><?php echo $sede['telefono'] ?? 'No especificado'; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Email:</span>
                    <strong><?php echo $sede['email'] ?? 'No especificado'; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Estado:</span>
                    <strong>
                        <span class="badge bg-<?php echo $sede['estado'] === 'activa' ? 'success' : 'secondary'; ?>">
                            <?php echo ucfirst($sede['estado']); ?>
                        </span>
                    </strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Fecha Creación:</span>
                    <strong><?php echo isset($sede['fecha_creacion']) ? date('d/m/Y H:i', strtotime($sede['fecha_creacion'])) : 'No disponible'; ?></strong>
                </li>
            </ul>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="content-card">
            <h5 class="mb-3"><i class="bi bi-gear me-2"></i>Acciones</h5>
            <div class="d-grid gap-2">
                <a href="sedes.php?editar=<?php echo $sede['id']; ?>" class="btn btn-primary">
                    <i class="bi bi-pencil me-2"></i>Editar Sede
                </a>
                <a href="estadisticas_sedes.php" class="btn btn-secondary">
                    <i class="bi bi-graph-up me-2"></i>Ver Estadísticas Globales
                </a>
                <a href="sedes.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Volver a Sedes
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Materiales en Stock Crítico -->
<?php if (!empty($materiales_criticos)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-3"><i class="bi bi-exclamation-triangle-fill me-2" style="color: #ef4444;"></i>Materiales en Stock Crítico (<?php echo count($materiales_criticos); ?>)</h5>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Material</th>
                            <th>Stock Actual</th>
                            <th>Stock Mínimo</th>
                            <th>Unidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materiales_criticos as $material): ?>
                        <tr>
                            <td><strong><?php echo $material['codigo']; ?></strong></td>
                            <td><?php echo $material['nombre']; ?></td>
                            <td><span class="badge bg-danger"><?php echo $material['stock_actual']; ?></span></td>
                            <td><?php echo $material['stock_minimo']; ?></td>
                            <td><?php echo $material['unidad']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Usuarios de la Sede -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-3"><i class="bi bi-people me-2"></i>Usuarios de la Sede (<?php echo count($usuarios_sede); ?>)</h5>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Usuario</th>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Último Acceso</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios_sede as $usuario): ?>
                        <tr>
                            <td><strong><?php echo $usuario['username'] ?? 'N/A'; ?></strong></td>
                            <td><?php echo $usuario['nombre_completo']; ?></td>
                            <td><?php echo $usuario['email']; ?></td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo getNombreRol($usuario['rol_id']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $usuario['estado'] === 'activo' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($usuario['estado']); ?>
                                </span>
                            </td>
                            <td><?php echo $usuario['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])) : 'Nunca'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
