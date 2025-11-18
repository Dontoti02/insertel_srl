<?php
/**
 * Dashboard - Administrador
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/User.php';
require_once '../../models/Material.php';
require_once '../../models/Solicitud.php';
require_once '../../models/Movimiento.php';

if (!tieneRol(ROL_ADMINISTRADOR)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();

// Obtener estadísticas
$userModel = new User($db);
$materialModel = new Material($db);
$movimientoModel = new Movimiento($db);

$stats_usuarios = $userModel->contarPorRolPorSede(obtenerSedeActual());
$stats_materiales = $materialModel->obtenerEstadisticas();
$materiales_stock_bajo = $materialModel->obtenerStockBajo();

// Datos para gráficas
// Gráfica 1: Movimientos por mes (últimos 6 meses)
$query_movimientos = "SELECT 
    DATE_FORMAT(mi.fecha_movimiento, '%Y-%m') as mes,
    COUNT(*) as total_movimientos,
    SUM(CASE WHEN mi.tipo_movimiento = 'entrada' THEN 1 ELSE 0 END) as entradas,
    SUM(CASE WHEN mi.tipo_movimiento = 'salida' THEN 1 ELSE 0 END) as salidas
    FROM movimientos_inventario mi
    WHERE mi.fecha_movimiento >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
      AND mi.material_id IN (SELECT id FROM materiales WHERE sede_id = :sede_id)
    GROUP BY DATE_FORMAT(mi.fecha_movimiento, '%Y-%m')
    ORDER BY mes DESC
    LIMIT 6";
$stmt_movimientos = $db->prepare($query_movimientos);
$stmt_movimientos->bindValue(':sede_id', obtenerSedeActual());
$stmt_movimientos->execute();
$datos_movimientos = $stmt_movimientos->fetchAll(PDO::FETCH_ASSOC);

// Gráfica 2: Materiales por categoría
$query_categorias = "SELECT 
    c.nombre as categoria,
    COUNT(m.id) as total_materiales,
    SUM(m.stock_actual) as stock_total
    FROM categorias_materiales c
    LEFT JOIN materiales m ON c.id = m.categoria_id AND m.estado = 'activo' AND m.sede_id = :sede_id
    GROUP BY c.id, c.nombre
    ORDER BY total_materiales DESC";
$stmt_categorias = $db->prepare($query_categorias);
$stmt_categorias->bindValue(':sede_id', obtenerSedeActual());
$stmt_categorias->execute();
$datos_categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Dashboard Administrador";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5><i class="bi bi-building me-2"></i>Sede Asignada: <strong><?php echo $_SESSION['sede_nombre'] ?? 'Sin sede'; ?></strong>
                        <?php if (!empty($_SESSION['sede_codigo'])): ?>
                            (<code><?php echo $_SESSION['sede_codigo']; ?></code>)
                        <?php endif; ?>
                    </h5>
                    <small class="text-muted">Este panel muestra únicamente datos de su sede</small>
                </div>
                <a href="sedes.php" class="btn btn-outline-primary">
                    <i class="bi bi-geo"></i> Ver Sede
                </a>
            </div>
        </div>
    </div>
    <?php if (($stats_materiales['total_materiales'] ?? 0) === 0): ?>
    <div class="col-12">
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            No hay materiales registrados en su sede. Cuando ingrese a módulos de inventario, verá listas vacías.
        </div>
    </div>
    <?php endif; ?>
</div>
<style>
.chart-container {
    position: relative;
    height: 350px;
    margin: 20px 0;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.content-card .card-header {
    border-bottom: 3px solid #e2e8f0;
    padding: 20px 25px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 16px 16px 0 0;
}

.content-card .card-header h5 {
    margin: 0;
    color: var(--color-dark);
    font-weight: 700;
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.content-card .card-header h5 i {
    color: var(--color-primary);
    font-size: 24px;
}

.content-card .card-header small {
    display: block;
    margin-top: 5px;
    color: #64748b;
    font-weight: 500;
}

canvas {
    max-height: 300px !important;
    border-radius: 12px;
}

/* Enhanced stat cards with different colors */
.stat-card.materials {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
    border-left: 4px solid var(--color-primary);
}

.stat-card.users {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(20, 184, 166, 0.05) 100%);
    border-left: 4px solid var(--color-success);
}

.stat-card.requests {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.05) 0%, rgba(249, 115, 22, 0.05) 100%);
    border-left: 4px solid var(--color-warning);
}

.stat-card.movements {
    background: linear-gradient(135deg, rgba(6, 182, 212, 0.05) 0%, rgba(20, 184, 166, 0.05) 100%);
    border-left: 4px solid var(--color-secondary);
}

.stat-card.low-stock {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.05) 0%, rgba(236, 72, 153, 0.05) 100%);
    border-left: 4px solid var(--color-danger);
}

.stat-card.value {
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.05) 0%, rgba(236, 72, 153, 0.05) 100%);
    border-left: 4px solid var(--color-purple);
}

/* Alert styling for low stock */
.low-stock-alert {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(236, 72, 153, 0.1) 100%);
    border: 2px solid var(--color-danger);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.low-stock-alert h6 {
    color: var(--color-danger);
    font-weight: 700;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.low-stock-item {
    background: white;
    border-radius: 8px;
    padding: 12px 15px;
    margin-bottom: 8px;
    border-left: 3px solid var(--color-danger);
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.1);
}

.low-stock-item:last-child {
    margin-bottom: 0;
}

/* Quick actions */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.quick-action-card {
    background: white;
    border-radius: 16px;
    padding: 25px;
    text-align: center;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.quick-action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
}

.quick-action-card.primary {
    border-color: var(--color-primary);
}

.quick-action-card.success {
    border-color: var(--color-success);
}

.quick-action-card.warning {
    border-color: var(--color-warning);
}

.quick-action-card.secondary {
    border-color: var(--color-secondary);
}

.quick-action-card .icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin: 0 auto 15px;
    color: white;
}

.quick-action-card.primary .icon {
    background: var(--gradient-primary);
}

.quick-action-card.success .icon {
    background: var(--gradient-success);
}

.quick-action-card.warning .icon {
    background: var(--gradient-warning);
}

.quick-action-card.secondary .icon {
    background: var(--gradient-secondary);
}

.quick-action-card h6 {
    font-weight: 700;
    color: var(--color-dark);
    margin-bottom: 8px;
}

.quick-action-card p {
    color: #64748b;
    font-size: 14px;
    margin: 0;
}
</style>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stat-card materials">
            <div class="icon blue">
                <i class="bi bi-box"></i>
            </div>
            <h3><?php echo $stats_materiales['total_materiales'] ?? 0; ?></h3>
            <p>Total Materiales</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card users">
            <div class="icon green">
                <i class="bi bi-people"></i>
            </div>
            <h3><?php 
                $total_usuarios = 0;
                foreach ($stats_usuarios as $stat) {
                    $total_usuarios += $stat['total'];
                }
                echo $total_usuarios;
            ?></h3>
            <p>Usuarios Activos</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card low-stock">
            <div class="icon red">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <h3><?php echo $stats_materiales['stock_bajo'] ?? 0; ?></h3>
            <p>Stock Bajo</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card movements">
            <div class="icon teal">
                <i class="bi bi-arrow-left-right"></i>
            </div>
            <h3><?php echo formatearMoneda($stats_materiales['valor_inventario'] ?? 0); ?></h3>
            <p>Valor Inventario</p>
        </div>
    </div>
</div>

<div class="row">
    <!-- Usuarios por Rol -->
    <div class="col-md-6 mb-4">
        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-people me-2"></i>Distribución de Usuarios</h5>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Rol</th>
                        <th class="text-end">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats_usuarios as $stat): ?>
                    <tr>
                        <td><?php echo $stat['nombre']; ?></td>
                        <td class="text-end">
                            <span class="badge bg-primary"><?php echo $stat['total']; ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Solicitudes -->
    <div class="col-md-6 mb-4">
        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-box-seam me-2"></i>Resumen de Inventario</h5>
            </div>
            <table class="table">
                <tbody>
                    <tr>
                        <td><i class="bi bi-box text-primary me-2"></i>Total Materiales</td>
                        <td class="text-end">
                            <span class="badge bg-primary"><?php echo $stats_materiales['total_materiales'] ?? 0; ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td><i class="bi bi-exclamation-triangle text-warning me-2"></i>Stock Bajo</td>
                        <td class="text-end">
                            <span class="badge bg-warning"><?php echo $stats_materiales['stock_bajo'] ?? 0; ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td><i class="bi bi-currency-dollar text-success me-2"></i>Valor Total</td>
                        <td class="text-end">
                            <span class="badge bg-success"><?php echo formatearMoneda($stats_materiales['valor_inventario'] ?? 0); ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Materiales con Stock Bajo -->
<?php if (!empty($materiales_stock_bajo)): ?>
<div class="row">
    <div class="col-12 mb-4">
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-exclamation-triangle text-warning me-2"></i>Materiales con Stock Bajo</h5>
                <a href="../admin/materiales.php?stock_bajo=1" class="btn btn-sm btn-primary">
                    Ver Todos
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Material</th>
                            <th>Categoría</th>
                            <th>Stock Actual</th>
                            <th>Stock Mínimo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materiales_stock_bajo as $material): ?>
                        <tr>
                            <td><code><?php echo $material['codigo']; ?></code></td>
                            <td><?php echo $material['nombre']; ?></td>
                            <td><?php echo $material['categoria_nombre']; ?></td>
                            <td>
                                <span class="badge bg-danger"><?php echo $material['stock_actual']; ?> <?php echo $material['unidad']; ?></span>
                            </td>
                            <td><?php echo $material['stock_minimo']; ?> <?php echo $material['unidad']; ?></td>
                            <td>
                                <span class="badge bg-warning">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Crítico
                                </span>
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

<!-- Gráficas -->
<div class="row">
    <!-- Gráfica 1: Movimientos por Mes -->
    <div class="col-md-6 mb-4">
        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-bar-chart me-2"></i>Movimientos por Mes</h5>
                <small class="text-muted">Entradas y salidas de inventario - Últimos 6 meses</small>
            </div>
            <div class="chart-container">
                <canvas id="chartMovimientos"></canvas>
            </div>
        </div>
    </div>

    <!-- Gráfica 2: Materiales por Categoría -->
    <div class="col-md-6 mb-4">
        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-pie-chart me-2"></i>Materiales por Categoría</h5>
                <small class="text-muted">Distribución de materiales activos por categoría</small>
            </div>
            <div class="chart-container">
                <canvas id="chartCategorias"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Datos para gráfica de movimientos
const datosMovimientos = <?php echo json_encode(array_reverse($datos_movimientos)); ?>;

// Verificar si hay datos
if (datosMovimientos && datosMovimientos.length > 0) {
    const labelsMovimientos = datosMovimientos.map(item => {
        const fecha = new Date(item.mes + '-01');
        return fecha.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' });
    });
    const entradasData = datosMovimientos.map(item => parseInt(item.entradas) || 0);
    const salidasData = datosMovimientos.map(item => parseInt(item.salidas) || 0);

    // Gráfica de Movimientos (Barras)
    const ctxMovimientos = document.getElementById('chartMovimientos').getContext('2d');
    const chartMovimientos = new Chart(ctxMovimientos, {
        type: 'bar',
        data: {
            labels: labelsMovimientos,
            datasets: [{
                label: 'Entradas',
                data: entradasData,
                backgroundColor: 'rgba(16, 185, 129, 0.8)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 2,
                borderRadius: 8
            }, {
                label: 'Salidas',
                data: salidasData,
                backgroundColor: 'rgba(239, 68, 68, 0.8)',
                borderColor: 'rgba(239, 68, 68, 1)',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: {
                            weight: 600
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: 'white',
                    bodyColor: 'white',
                    borderColor: 'rgba(99, 102, 241, 0.8)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + ' movimientos';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            weight: 600
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        stepSize: 1,
                        font: {
                            weight: 600
                        }
                    }
                }
            }
        }
    });
} else {
    // Mostrar mensaje si no hay datos
    document.getElementById('chartMovimientos').parentElement.innerHTML = 
        '<div class="text-center text-muted py-5"><i class="bi bi-bar-chart fs-1 d-block mb-2"></i>No hay datos de movimientos</div>';
}

// Datos para gráfica de categorías
const datosCategorias = <?php echo json_encode($datos_categorias); ?>;

if (datosCategorias && datosCategorias.length > 0) {
    const labelsCategorias = datosCategorias.map(item => item.categoria);
    const materialesData = datosCategorias.map(item => parseInt(item.total_materiales) || 0);

    // Colores vibrantes para el gráfico de dona
    const coloresCategorias = [
        'rgba(99, 102, 241, 0.8)',   // Indigo
        'rgba(16, 185, 129, 0.8)',   // Emerald
        'rgba(245, 158, 11, 0.8)',   // Amber
        'rgba(239, 68, 68, 0.8)',    // Red
        'rgba(139, 92, 246, 0.8)',   // Violet
        'rgba(6, 182, 212, 0.8)',    // Cyan
        'rgba(236, 72, 153, 0.8)',   // Pink
        'rgba(249, 115, 22, 0.8)'    // Orange
    ];

    const ctxCategorias = document.getElementById('chartCategorias').getContext('2d');
    const chartCategorias = new Chart(ctxCategorias, {
        type: 'doughnut',
        data: {
            labels: labelsCategorias,
            datasets: [{
                data: materialesData,
                backgroundColor: coloresCategorias,
                borderColor: coloresCategorias.map(color => color.replace('0.8', '1')),
                borderWidth: 3,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: {
                            weight: 600
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: 'white',
                    bodyColor: 'white',
                    borderColor: 'rgba(99, 102, 241, 0.8)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed * 100) / total).toFixed(1);
                            return context.label + ': ' + context.parsed + ' materiales (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
} else {
    // Mostrar mensaje si no hay datos
    document.getElementById('chartCategorias').parentElement.innerHTML = 
        '<div class="text-center text-muted py-5"><i class="bi bi-pie-chart fs-1 d-block mb-2"></i>No hay datos de categorías</div>';
}
</script>

<!-- Alerta de Stock Bajo -->
<?php if (!empty($materiales_stock_bajo)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="low-stock-alert">
            <h6><i class="bi bi-exclamation-triangle"></i>Materiales con Stock Bajo</h6>
            <?php foreach ($materiales_stock_bajo as $material): ?>
            <div class="low-stock-item">
                <strong><?php echo $material['nombre']; ?></strong>
                <span class="float-end">
                    <span class="badge bg-danger"><?php echo $material['stock_actual']; ?> <?php echo $material['unidad']; ?></span>
                    <small class="text-muted ms-2">Mín: <?php echo $material['stock_minimo']; ?></small>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Acciones Rápidas -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-lightning me-2"></i>Acciones Rápidas</h5>
                <small>Accede rápidamente a las funciones más utilizadas</small>
            </div>
            <div class="quick-actions">
                <a href="materiales.php" class="quick-action-card primary text-decoration-none">
                    <div class="icon">
                        <i class="bi bi-box"></i>
                    </div>
                    <h6>Gestionar Materiales</h6>
                    <p>Ver, agregar y editar materiales del inventario</p>
                </a>
                
                <a href="usuarios.php" class="quick-action-card success text-decoration-none">
                    <div class="icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <h6>Gestionar Usuarios</h6>
                    <p>Administrar usuarios y permisos del sistema</p>
                </a>
                
                <a href="solicitudes.php" class="quick-action-card warning text-decoration-none">
                    <div class="icon">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <h6>Revisar Solicitudes</h6>
                    <p>Aprobar o rechazar solicitudes pendientes</p>
                </a>
                
                <a href="reportes.php" class="quick-action-card secondary text-decoration-none">
                    <div class="icon">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <h6>Ver Reportes</h6>
                    <p>Generar reportes detallados del sistema</p>
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
