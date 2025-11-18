<?php
/**
 * Configuración Avanzada del Sistema - Solo Administradores
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/Configuracion.php';
require_once '../../models/Alerta.php';

if (!tieneRol(ROL_ADMINISTRADOR)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();
$configModel = new Configuracion($db);
$alertaModel = new Alerta($db);

// Procesar actualizaciones de configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'actualizar_config') {
        $configuraciones = [];
        
        // Configuraciones de inventario
        if (isset($_POST['stock_minimo_global'])) {
            $configuraciones['stock_minimo_global'] = (int)$_POST['stock_minimo_global'];
        }
        if (isset($_POST['dias_alerta_vencimiento'])) {
            $configuraciones['dias_alerta_vencimiento'] = (int)$_POST['dias_alerta_vencimiento'];
        }
        if (isset($_POST['moneda_sistema'])) {
            $configuraciones['moneda_sistema'] = sanitizar($_POST['moneda_sistema']);
        }
        
        // Configuraciones de notificaciones
        if (isset($_POST['email_notificaciones'])) {
            $configuraciones['email_notificaciones'] = sanitizar($_POST['email_notificaciones']);
        }
        if (isset($_POST['horas_respuesta_solicitud'])) {
            $configuraciones['horas_respuesta_solicitud'] = (int)$_POST['horas_respuesta_solicitud'];
        }
        
        // Configuraciones de empresa
        if (isset($_POST['empresa_nombre'])) {
            $configuraciones['empresa_nombre'] = sanitizar($_POST['empresa_nombre']);
        }
        if (isset($_POST['empresa_ruc'])) {
            $configuraciones['empresa_ruc'] = sanitizar($_POST['empresa_ruc']);
        }
        
        if ($configModel->actualizarMultiples($configuraciones)) {
            registrarActividad($_SESSION['usuario_id'], 'actualizar', 'configuracion', 'Configuraciones del sistema actualizadas');
            setMensaje('success', 'Configuraciones actualizadas exitosamente');
        } else {
            setMensaje('danger', 'Error al actualizar las configuraciones');
        }
        redirigir('views/admin/configuracion_avanzada.php');
    }
    
    if ($accion === 'generar_alertas') {
        $alertas_stock = $alertaModel->generarAlertasStockMinimo();
        $alertas_vencimiento = $alertaModel->generarAlertasVencimiento();
        $alertas_solicitudes = $alertaModel->generarAlertasSolicitudesPendientes();
        
        $total_alertas = $alertas_stock + $alertas_vencimiento + $alertas_solicitudes;
        
        registrarActividad($_SESSION['usuario_id'], 'generar_alertas', 'sistema', "Generadas $total_alertas alertas automáticas");
        setMensaje('success', "Se generaron $total_alertas alertas automáticas");
        redirigir('views/admin/configuracion_avanzada.php');
    }
    
    if ($accion === 'limpiar_alertas') {
        $alertaModel->limpiarAntiguas(30);
        registrarActividad($_SESSION['usuario_id'], 'limpiar_alertas', 'sistema', 'Alertas antiguas eliminadas');
        setMensaje('success', 'Alertas antiguas eliminadas exitosamente');
        redirigir('views/admin/configuracion_avanzada.php');
    }
}

// Obtener configuraciones actuales
$config_inventario = $configModel->obtenerConfigInventario();
$config_notificaciones = $configModel->obtenerConfigNotificaciones();
$config_empresa = $configModel->obtenerConfigEmpresa();

// Obtener estadísticas de alertas
$stats_alertas = $alertaModel->obtenerEstadisticas();

$page_title = "Configuración Avanzada";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <h5><i class="bi bi-gear me-2"></i>Configuración Avanzada del Sistema</h5>
            <p class="text-muted mb-0">Gestione los parámetros globales del sistema INSERTEL</p>
        </div>
    </div>
</div>

<form method="POST">
    <input type="hidden" name="accion" value="actualizar_config">
    
    <div class="row">
        <!-- Configuraciones de Inventario -->
        <div class="col-md-6 mb-4">
            <div class="content-card">
                <div class="card-header">
                    <h6><i class="bi bi-box me-2"></i>Configuraciones de Inventario</h6>
                </div>
                <div class="mb-3">
                    <label class="form-label">Stock Mínimo Global por Defecto</label>
                    <input type="number" name="stock_minimo_global" class="form-control" 
                           value="<?php echo $config_inventario['stock_minimo_global']; ?>" min="0">
                    <small class="text-muted">Valor por defecto para nuevos materiales</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Días de Anticipación para Alertas de Vencimiento</label>
                    <input type="number" name="dias_alerta_vencimiento" class="form-control" 
                           value="<?php echo $config_inventario['dias_alerta_vencimiento']; ?>" min="1" max="365">
                    <small class="text-muted">Días antes del vencimiento para generar alerta</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Moneda del Sistema</label>
                    <select name="moneda_sistema" class="form-select">
                        <option value="PEN" <?php echo $config_inventario['moneda_sistema'] == 'PEN' ? 'selected' : ''; ?>>Soles Peruanos (PEN)</option>
                        <option value="USD" <?php echo $config_inventario['moneda_sistema'] == 'USD' ? 'selected' : ''; ?>>Dólares Americanos (USD)</option>
                        <option value="EUR" <?php echo $config_inventario['moneda_sistema'] == 'EUR' ? 'selected' : ''; ?>>Euros (EUR)</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Configuraciones de Notificaciones -->
        <div class="col-md-6 mb-4">
            <div class="content-card">
                <div class="card-header">
                    <h6><i class="bi bi-bell me-2"></i>Configuraciones de Notificaciones</h6>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email para Notificaciones del Sistema</label>
                    <input type="email" name="email_notificaciones" class="form-control" 
                           value="<?php echo $config_notificaciones['email_notificaciones']; ?>">
                    <small class="text-muted">Email principal para recibir notificaciones automáticas</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Horas Máximas para Responder Solicitudes</label>
                    <input type="number" name="horas_respuesta_solicitud" class="form-control" 
                           value="<?php echo $config_notificaciones['horas_respuesta_solicitud']; ?>" min="1" max="168">
                    <small class="text-muted">Tiempo límite antes de generar alerta por solicitud pendiente</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Configuraciones de Empresa -->
        <div class="col-md-6 mb-4">
            <div class="content-card">
                <div class="card-header">
                    <h6><i class="bi bi-building me-2"></i>Información de la Empresa</h6>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nombre de la Empresa</label>
                    <input type="text" name="empresa_nombre" class="form-control" 
                           value="<?php echo $config_empresa['empresa_nombre']; ?>" maxlength="200">
                </div>
                <div class="mb-3">
                    <label class="form-label">RUC de la Empresa</label>
                    <input type="text" name="empresa_ruc" class="form-control" 
                           value="<?php echo $config_empresa['empresa_ruc']; ?>" maxlength="20">
                </div>
            </div>
        </div>
        
        <!-- Estadísticas de Alertas -->
        <div class="col-md-6 mb-4">
            <div class="content-card">
                <div class="card-header">
                    <h6><i class="bi bi-exclamation-triangle me-2"></i>Estadísticas de Alertas (Últimos 7 días)</h6>
                </div>
                <?php if (empty($stats_alertas)): ?>
                <p class="text-muted">No hay alertas registradas en los últimos 7 días</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Total</th>
                                <th>No Leídas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats_alertas as $stat): ?>
                            <tr>
                                <td><?php echo ucfirst(str_replace('_', ' ', $stat['tipo'])); ?></td>
                                <td><span class="badge bg-info"><?php echo $stat['total']; ?></span></td>
                                <td><span class="badge bg-warning"><?php echo $stat['no_leidas']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6>Guardar Configuraciones</h6>
                        <small class="text-muted">Los cambios se aplicarán inmediatamente en todo el sistema</small>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Guardar Configuraciones
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Herramientas de Sistema -->
<div class="row mt-4">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header">
                <h6><i class="bi bi-tools me-2"></i>Herramientas del Sistema</h6>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="border rounded p-3 text-center">
                        <i class="bi bi-bell-fill text-warning" style="font-size: 2rem;"></i>
                        <h6 class="mt-2">Generar Alertas</h6>
                        <p class="text-muted small">Ejecutar manualmente la generación de alertas automáticas</p>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="accion" value="generar_alertas">
                            <button type="submit" class="btn btn-outline-warning btn-sm">
                                <i class="bi bi-bell me-1"></i>Generar Alertas
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="border rounded p-3 text-center">
                        <i class="bi bi-trash-fill text-danger" style="font-size: 2rem;"></i>
                        <h6 class="mt-2">Limpiar Alertas</h6>
                        <p class="text-muted small">Eliminar alertas antiguas (más de 30 días)</p>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="accion" value="limpiar_alertas">
                            <button type="submit" class="btn btn-outline-danger btn-sm"
                                    onclick="return confirm('¿Eliminar alertas antiguas?')">
                                <i class="bi bi-trash me-1"></i>Limpiar Alertas
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="border rounded p-3 text-center">
                        <i class="bi bi-building-fill text-primary" style="font-size: 2rem;"></i>
                        <h6 class="mt-2">Gestionar Sedes</h6>
                        <p class="text-muted small">Administrar las sedes y ubicaciones de la empresa</p>
                        <a href="sedes.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-building me-1"></i>Gestionar Sedes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Información del Sistema -->
<div class="row mt-4">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header">
                <h6><i class="bi bi-info-circle me-2"></i>Información del Sistema</h6>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td><strong>Versión del Sistema:</strong></td>
                            <td>INSERTEL v2.0.0</td>
                        </tr>
                        <tr>
                            <td><strong>Base de Datos:</strong></td>
                            <td>
                                <?php 
                                $version = $db->query('SELECT VERSION() as version')->fetch()['version'];
                                echo 'MySQL ' . explode('-', $version)[0]; 
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>PHP:</strong></td>
                            <td><?php echo phpversion(); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td><strong>Servidor:</strong></td>
                            <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Zona Horaria:</strong></td>
                            <td><?php echo date_default_timezone_get(); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Última Actualización:</strong></td>
                            <td><?php echo date('d/m/Y H:i:s'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
