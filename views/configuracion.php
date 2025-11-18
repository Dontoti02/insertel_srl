<?php
/**
 * Página de Configuración del Sistema
 */

require_once '../config/constants.php';
require_once '../config/functions.php';
require_once '../config/database.php';

if (!estaAutenticado()) {
    redirigir('auth/login.php');
}

$database = new Database();
$db = $database->getConnection();

// Obtener estadísticas del sistema
$stats = [];

// Contar usuarios por rol
$query = "SELECT r.nombre, COUNT(u.id) as total
          FROM roles r
          LEFT JOIN usuarios u ON r.id = u.rol_id AND u.estado = 'activo'";

// Excluir SuperAdmin para roles que no son SuperAdmin
if (!esSuperAdmin()) {
    $query .= " AND r.id != " . ROL_SUPERADMIN;
}

$query .= " GROUP BY r.id, r.nombre";
$stmt = $db->query($query);
$stats['usuarios_por_rol'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas generales
$query = "SELECT 
            (SELECT COUNT(*) FROM usuarios WHERE estado = 'activo') as usuarios_activos,
            (SELECT COUNT(*) FROM materiales WHERE estado = 'activo') as materiales_activos,
            (SELECT COUNT(*) FROM solicitudes WHERE estado = 'pendiente') as solicitudes_pendientes,
            (SELECT COUNT(*) FROM movimientos_inventario WHERE DATE(fecha_movimiento) = CURDATE()) as movimientos_hoy";
$stmt = $db->query($query);
$stats['generales'] = $stmt->fetch(PDO::FETCH_ASSOC);

// Información del sistema
$info_sistema = [
    'version' => '1.0.0',
    'php_version' => phpversion(),
    'mysql_version' => $db->query('SELECT VERSION() as version')->fetch()['version'],
    'servidor' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido',
    'timezone' => date_default_timezone_get(),
    'memoria_limite' => ini_get('memory_limit'),
    'tiempo_ejecucion' => ini_get('max_execution_time') . 's'
];

// Procesar acciones de configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'limpiar_logs' && tieneRol(ROL_ADMINISTRADOR)) {
        // Limpiar logs antiguos (más de 30 días)
        $query = "DELETE FROM historial_actividades WHERE fecha < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $stmt = $db->prepare($query);
        if ($stmt->execute()) {
            $registros_eliminados = $stmt->rowCount();
            registrarActividad($_SESSION['usuario_id'], 'limpiar_logs', 'configuracion', "Eliminados $registros_eliminados registros antiguos");
            setMensaje('success', "Se eliminaron $registros_eliminados registros de actividad antiguos");
        } else {
            setMensaje('danger', 'Error al limpiar los logs');
        }
        redirigir('views/configuracion.php');
    }
    
    if ($accion === 'optimizar_db' && tieneRol(ROL_ADMINISTRADOR)) {
        // Optimizar tablas de la base de datos
        $tablas = ['usuarios', 'materiales', 'solicitudes', 'movimientos_inventario', 'historial_actividades'];
        $optimizadas = 0;
        
        foreach ($tablas as $tabla) {
            $query = "OPTIMIZE TABLE $tabla";
            if ($db->query($query)) {
                $optimizadas++;
            }
        }
        
        registrarActividad($_SESSION['usuario_id'], 'optimizar_db', 'configuracion', "Optimizadas $optimizadas tablas");
        setMensaje('success', "Base de datos optimizada. $optimizadas tablas procesadas");
        redirigir('views/configuracion.php');
    }
}

$page_title = "Configuración del Sistema";
include 'layouts/header.php';
?>

<!-- Estadísticas del Sistema -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon blue">
                <i class="bi bi-people"></i>
            </div>
            <h3><?php echo $stats['generales']['usuarios_activos']; ?></h3>
            <p>Usuarios Activos</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon green">
                <i class="bi bi-box"></i>
            </div>
            <h3><?php echo $stats['generales']['materiales_activos']; ?></h3>
            <p>Materiales Registrados</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon orange">
                <i class="bi bi-clock-history"></i>
            </div>
            <h3><?php echo $stats['generales']['solicitudes_pendientes']; ?></h3>
            <p>Solicitudes Pendientes</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon red">
                <i class="bi bi-arrow-repeat"></i>
            </div>
            <h3><?php echo $stats['generales']['movimientos_hoy']; ?></h3>
            <p>Movimientos Hoy</p>
        </div>
    </div>
</div>

<div class="row">
    <!-- Información del Sistema -->
    <div class="col-md-6">
        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-info-circle me-2"></i>Información del Sistema</h5>
            </div>
            <table class="table table-borderless">
                <tr>
                    <td><strong>Versión del Sistema:</strong></td>
                    <td><?php echo $info_sistema['version']; ?></td>
                </tr>
                <tr>
                    <td><strong>Versión PHP:</strong></td>
                    <td><?php echo $info_sistema['php_version']; ?></td>
                </tr>
                <tr>
                    <td><strong>Versión MySQL:</strong></td>
                    <td><?php echo explode('-', $info_sistema['mysql_version'])[0]; ?></td>
                </tr>
                <tr>
                    <td><strong>Servidor Web:</strong></td>
                    <td><?php echo $info_sistema['servidor']; ?></td>
                </tr>
                <tr>
                    <td><strong>Zona Horaria:</strong></td>
                    <td><?php echo $info_sistema['timezone']; ?></td>
                </tr>
                <tr>
                    <td><strong>Límite de Memoria:</strong></td>
                    <td><?php echo $info_sistema['memoria_limite']; ?></td>
                </tr>
                <tr>
                    <td><strong>Tiempo Máx. Ejecución:</strong></td>
                    <td><?php echo $info_sistema['tiempo_ejecucion']; ?></td>
                </tr>
            </table>
        </div>
    </div>
    
    <!-- Usuarios por Rol -->
    <div class="col-md-6">
        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-person-badge me-2"></i>Usuarios por rol</h5>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Rol</th>
                            <th class="text-center">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['usuarios_por_rol'] as $rol): ?>
                        <tr>
                            <td><?php echo $rol['nombre']; ?></td>
                            <td class="text-center">
                                <span class="badge bg-primary"><?php echo $rol['total']; ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (tieneRol(ROL_ADMINISTRADOR)): ?>
<!-- Herramientas de Administracion -->
<div class="row mt-4">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-tools me-2"></i>Herramientas de Administracion</h5>
                <small class="text-muted">Solo disponible para administradores</small>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="border rounded p-3 mb-3">
                        <h6><i class="bi bi-trash me-2"></i>Limpiar Logs Antiguos</h6>
                        <p class="text-muted mb-3">Elimina registros de actividad de más de 30 días para optimizar el rendimiento.</p>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="accion" value="limpiar_logs">
                            <button type="submit" class="btn btn-outline-warning btn-sm" 
                                    onclick="return confirm('¿Está seguro de eliminar los logs antiguos?')">
                                <i class="bi bi-trash me-1"></i>Limpiar Logs
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="border rounded p-3 mb-3">
                        <h6><i class="bi bi-speedometer2 me-2"></i>Optimizar Base de Datos</h6>
                        <p class="text-muted mb-3">Optimiza las tablas de la base de datos para mejorar el rendimiento.</p>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="accion" value="optimizar_db">
                            <button type="submit" class="btn btn-outline-primary btn-sm"
                                    onclick="return confirm('¿Desea optimizar la base de datos?')">
                                <i class="bi bi-speedometer2 me-1"></i>Optimizar DB
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="border rounded p-3 mb-3">
                        <h6><i class="bi bi-download me-2"></i>Respaldo de Datos</h6>
                        <p class="text-muted mb-3">Generar respaldo completo de la base de datos.</p>
                        <button class="btn btn-outline-success btn-sm" onclick="alert('Funcionalidad en desarrollo')">
                            <i class="bi bi-download me-1"></i>Generar Respaldo
                        </button>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="border rounded p-3 mb-3">
                        <h6><i class="bi bi-graph-up me-2"></i>Estadísticas Avanzadas</h6>
                        <p class="text-muted mb-3">Ver reportes detallados del sistema.</p>
                        <a href="<?php echo BASE_URL; ?>views/admin/reportes.php" class="btn btn-outline-info btn-sm">
                            <i class="bi bi-graph-up me-1"></i>Ver Reportes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Información de Sesión -->
<div class="row mt-4">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-person-circle me-2"></i>Información de Sesión Actual</h5>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Usuario:</strong></td>
                            <td><?php echo $_SESSION['username']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Nombre:</strong></td>
                            <td><?php echo $_SESSION['nombre_completo']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Rol:</strong></td>
                            <td><span class="badge bg-primary"><?php echo $_SESSION['rol_nombre']; ?></span></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>IP Address:</strong></td>
                            <td><?php echo $_SERVER['REMOTE_ADDR']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>User Agent:</strong></td>
                            <td class="text-truncate" style="max-width: 200px;" title="<?php echo $_SERVER['HTTP_USER_AGENT']; ?>">
                                <?php echo substr($_SERVER['HTTP_USER_AGENT'], 0, 50) . '...'; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Sesión Iniciada:</strong></td>
                            <td><?php echo date('d/m/Y H:i:s'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>
