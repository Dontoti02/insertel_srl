<?php
/**
 * Configuración del Sistema - Superadministrador
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/Configuracion.php';

if (!tieneRol(ROL_SUPERADMIN)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();
$configModel = new Configuracion($db);

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
        
        if ($accion === 'actualizar_config' && isset($_POST['configuraciones'])) {
            $configuraciones = $_POST['configuraciones'];
            
            if ($configModel->actualizarMultiples($configuraciones)) {
                registrarActividad($_SESSION['usuario_id'], 'actualizar', 'configuracion', 'Actualización de configuración del sistema');
                setMensaje('success', 'Configuración actualizada exitosamente.');
            } else {
                setMensaje('danger', 'Error al actualizar la configuración.');
            }
            redirigir('views/superadmin/configuracion_sistema.php');
        }
        
        if ($accion === 'limpiar_logs') {
            try {
                $dias = intval($_POST['dias_antiguos'] ?? 30);
                $fecha_limite = date('Y-m-d H:i:s', strtotime("-$dias days"));
                
                $query = "DELETE FROM historial_actividades WHERE fecha < :fecha_limite";
                $stmt = $db->prepare($query);
                $stmt->execute([':fecha_limite' => $fecha_limite]);
                $registros_eliminados = $stmt->rowCount();
                
                registrarActividad($_SESSION['usuario_id'], 'limpiar', 'logs', "Logs antiguos eliminados: $registros_eliminados registros");
                setMensaje('success', "Se eliminaron $registros_eliminados registros de auditoría más antiguos de $dias días.");
            } catch (Exception $e) {
                setMensaje('danger', 'Error al limpiar los logs: ' . $e->getMessage());
            }
            redirigir('views/superadmin/configuracion_sistema.php');
        }
        
        if ($accion === 'optimizar_db') {
            try {
                $tablas = ['usuarios', 'materiales', 'movimientos_inventario', 'solicitudes', 'auditoria', 'configuracion_sistema'];
                $optimizadas = 0;
                
                foreach ($tablas as $tabla) {
                    try {
                        // Verificar que la tabla existe antes de optimizar
                        $check = $db->query("SHOW TABLES LIKE '$tabla'");
                        $check->fetchAll();
                        
                        if ($check->rowCount() > 0) {
                            $db->query("OPTIMIZE TABLE $tabla");
                            $optimizadas++;
                        }
                    } catch (Exception $e) {
                        // Continuar con la siguiente tabla si hay error
                        continue;
                    }
                }
                
                registrarActividad($_SESSION['usuario_id'], 'optimizar', 'base_datos', "Base de datos optimizada: $optimizadas tablas");
                setMensaje('success', "Base de datos optimizada correctamente. Se optimizaron $optimizadas tablas.");
            } catch (Exception $e) {
                setMensaje('danger', 'Error al optimizar la base de datos: ' . $e->getMessage());
            }
            redirigir('views/superadmin/configuracion_sistema.php');
        }
    }
}

// Obtener todas las configuraciones
$categorias = $configModel->obtenerCategorias();
$configuraciones_por_categoria = [];
foreach ($categorias as $categoria) {
    $configuraciones_por_categoria[$categoria] = $configModel->obtenerPorCategoria($categoria);
}

// Obtener estadísticas del sistema
try {
    $stmt_usuarios = $db->query("SELECT COUNT(*) as total FROM usuarios");
    $result_usuarios = $stmt_usuarios->fetch(PDO::FETCH_ASSOC);
    $total_usuarios = $result_usuarios['total'] ?? 0;
    
    $stmt_materiales = $db->query("SELECT COUNT(*) as total FROM materiales");
    $result_materiales = $stmt_materiales->fetch(PDO::FETCH_ASSOC);
    $total_materiales = $result_materiales['total'] ?? 0;
    
    $stmt_logs = $db->query("SELECT COUNT(*) as total FROM historial_actividades");
    $result_logs = $stmt_logs->fetch(PDO::FETCH_ASSOC);
    $total_logs = $result_logs['total'] ?? 0;
    
    $stmt_db_size = $db->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb FROM information_schema.tables WHERE table_schema = DATABASE()");
    $result_db_size = $stmt_db_size->fetch(PDO::FETCH_ASSOC);
    $db_size = $result_db_size['size_mb'] ?? 0;
} catch (Exception $e) {
    $total_usuarios = 0;
    $total_materiales = 0;
    $total_logs = 0;
    $db_size = 0;
}

$page_title = "Configuración del Sistema";
include '../layouts/header.php';
?>

<!-- Estadísticas del Sistema -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="content-card text-center">
            <i class="bi bi-people" style="font-size: 32px; color: #6366f1;"></i>
            <h6 class="mt-2 mb-1">Usuarios</h6>
            <h4 class="mb-0" style="color: #6366f1;"><?php echo $total_usuarios; ?></h4>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="content-card text-center">
            <i class="bi bi-box" style="font-size: 32px; color: #10b981;"></i>
            <h6 class="mt-2 mb-1">Materiales</h6>
            <h4 class="mb-0" style="color: #10b981;"><?php echo $total_materiales; ?></h4>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="content-card text-center">
            <i class="bi bi-file-text" style="font-size: 32px; color: #f59e0b;"></i>
            <h6 class="mt-2 mb-1">Registros Auditoría</h6>
            <h4 class="mb-0" style="color: #f59e0b;"><?php echo $total_logs; ?></h4>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="content-card text-center">
            <i class="bi bi-hdd" style="font-size: 32px; color: #ef4444;"></i>
            <h6 class="mt-2 mb-1">Tamaño BD</h6>
            <h4 class="mb-0" style="color: #ef4444;"><?php echo $db_size; ?> MB</h4>
        </div>
    </div>
</div>

<!-- Herramientas de Administración -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <h5><i class="bi bi-tools me-2"></i>Herramientas de Administración</h5>
            <div class="row mt-3">
                <div class="col-md-6 mb-3">
                    <form method="POST" onsubmit="return confirm('¿Está seguro de que desea limpiar los logs antiguos?');">
                        <input type="hidden" name="accion" value="limpiar_logs">
                        <div class="input-group">
                            <input type="number" name="dias_antiguos" class="form-control" value="30" min="1" placeholder="Días">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-trash me-2"></i>Limpiar Logs
                            </button>
                        </div>
                        <small class="text-muted d-block mt-1">Elimina registros de auditoría más antiguos de X días</small>
                    </form>
                </div>
                <div class="col-md-6 mb-3">
                    <form method="POST" onsubmit="return confirm('¿Está seguro de que desea optimizar la base de datos?');">
                        <input type="hidden" name="accion" value="optimizar_db">
                        <button type="submit" class="btn btn-info w-100">
                            <i class="bi bi-speedometer2 me-2"></i>Optimizar Base de Datos
                        </button>
                        <small class="text-muted d-block mt-1">Optimiza todas las tablas del sistema</small>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Configuración General del Sistema -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <h5><i class="bi bi-gear-wide-connected me-2"></i>Configuración General del Sistema</h5>
            <p class="text-muted mb-3">Ajuste los parámetros de funcionamiento de la aplicación.</p>
            
            <form method="POST">
                <input type="hidden" name="accion" value="actualizar_config">
                
                <?php if (!empty($configuraciones_por_categoria)): ?>
                    <!-- Tabs para categorías -->
                    <ul class="nav nav-tabs mb-4" role="tablist">
                        <?php $primera = true; foreach ($configuraciones_por_categoria as $categoria => $configuraciones): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo $primera ? 'active' : ''; ?>" 
                                        id="tab-<?php echo $categoria; ?>" 
                                        data-bs-toggle="tab" 
                                        data-bs-target="#content-<?php echo $categoria; ?>" 
                                        type="button" role="tab">
                                    <?php echo ucfirst($categoria); ?>
                                </button>
                            </li>
                            <?php $primera = false; endforeach; ?>
                    </ul>

                    <!-- Contenido de tabs -->
                    <div class="tab-content">
                        <?php $primera = true; foreach ($configuraciones_por_categoria as $categoria => $configuraciones): ?>
                            <div class="tab-pane fade <?php echo $primera ? 'show active' : ''; ?>" 
                                 id="content-<?php echo $categoria; ?>" role="tabpanel">
                                
                                <?php foreach ($configuraciones as $config): ?>
                                    <div class="mb-4">
                                        <label for="<?php echo $config['clave']; ?>" class="form-label fw-bold">
                                            <?php echo str_replace('_', ' ', ucfirst($config['clave'])); ?>
                                        </label>
                                        
                                        <?php if ($config['tipo'] === 'texto'): ?>
                                            <input type="text" class="form-control" 
                                                   id="<?php echo $config['clave']; ?>" 
                                                   name="configuraciones[<?php echo $config['clave']; ?>]" 
                                                   value="<?php echo htmlspecialchars($config['valor']); ?>">
                                        <?php elseif ($config['tipo'] === 'numero'): ?>
                                            <input type="number" class="form-control" 
                                                   id="<?php echo $config['clave']; ?>" 
                                                   name="configuraciones[<?php echo $config['clave']; ?>]" 
                                                   value="<?php echo htmlspecialchars($config['valor']); ?>">
                                        <?php elseif ($config['tipo'] === 'boolean'): ?>
                                            <select class="form-select" 
                                                    id="<?php echo $config['clave']; ?>" 
                                                    name="configuraciones[<?php echo $config['clave']; ?>]">
                                                <option value="1" <?php echo ($config['valor'] == '1') ? 'selected' : ''; ?>>Habilitado</option>
                                                <option value="0" <?php echo ($config['valor'] == '0') ? 'selected' : ''; ?>>Deshabilitado</option>
                                            </select>
                                        <?php else: ?>
                                            <textarea class="form-control" rows="3"
                                                      id="<?php echo $config['clave']; ?>" 
                                                      name="configuraciones[<?php echo $config['clave']; ?>]"><?php echo htmlspecialchars($config['valor']); ?></textarea>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($config['descripcion'])): ?>
                                            <small class="text-muted d-block mt-2">
                                                <i class="bi bi-info-circle me-1"></i><?php echo $config['descripcion']; ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php $primera = false; endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="<?php echo BASE_URL; ?>views/superadmin/dashboard.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Guardar Cambios
                        </button>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>No hay configuraciones disponibles en el sistema.
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<style>
    .nav-tabs .nav-link {
        color: #6b7280;
        border-bottom: 2px solid transparent;
        transition: all 0.3s ease;
    }
    
    .nav-tabs .nav-link:hover {
        color: #6366f1;
        border-bottom-color: #6366f1;
    }
    
    .nav-tabs .nav-link.active {
        color: #6366f1;
        border-bottom-color: #6366f1;
        background: transparent;
    }
</style>

<?php include '../layouts/footer.php'; ?>
