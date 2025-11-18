<?php
/**
 * Estadísticas por Sede - Superadministrador
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

$sedes = $sedeModel->obtenerTodas();
$estadisticas_sedes = [];

foreach ($sedes as $sede) {
    $sede_id = $sede['id'];
    $stats = [];

    // Estadísticas de Usuarios
    $stats['usuarios'] = $userModel->obtenerEstadisticasPorSede($sede_id);

    // Estadísticas de Inventario
    $query_inv = "SELECT 
                    COUNT(*) as total_materiales,
                    SUM(stock_actual) as total_items,
                    SUM(stock_actual * costo_unitario) as valor_total,
                    COUNT(CASE WHEN stock_actual <= stock_minimo THEN 1 END) as criticos
                  FROM materiales WHERE sede_id = :sede_id";
    $stmt_inv = $db->prepare($query_inv);
    $stmt_inv->execute([':sede_id' => $sede_id]);
    $stats['inventario'] = $stmt_inv->fetch(PDO::FETCH_ASSOC);

    // Estadísticas de Movimientos (últimos 30 días)
    $query_mov = "SELECT COUNT(*) as total FROM movimientos_inventario 
                  WHERE sede_id = :sede_id AND fecha_movimiento >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $stmt_mov = $db->prepare($query_mov);
    $stmt_mov->execute([':sede_id' => $sede_id]);
    $stats['movimientos'] = $stmt_mov->fetch(PDO::FETCH_ASSOC);

    // Estadísticas de Solicitudes (pendientes)
    $query_sol = "SELECT COUNT(*) as total FROM solicitudes 
                  WHERE sede_id = :sede_id AND estado = 'pendiente'";
    $stmt_sol = $db->prepare($query_sol);
    $stmt_sol->execute([':sede_id' => $sede_id]);
    $stats['solicitudes'] = $stmt_sol->fetch(PDO::FETCH_ASSOC);
    
    $estadisticas_sedes[$sede_id] = $stats;
}

$page_title = "Estadísticas por Sede";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <h5><i class="bi bi-pie-chart me-2"></i>Estadísticas por Sede</h5>
            <p class="text-muted mb-0">Comparativa de indicadores clave entre las diferentes sedes.</p>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-6 mb-4">
        <div class="content-card">
            <div class="d-flex align-items-center mb-3">
                <i class="bi bi-people-fill me-2" style="color: #6366f1; font-size: 1.5rem;"></i>
                <h5 class="mb-0">Usuarios por Sede</h5>
            </div>
            <canvas id="chartUsuariosPorSede"></canvas>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="content-card">
            <div class="d-flex align-items-center mb-3">
                <i class="bi bi-cash-coin me-2" style="color: #f59e0b; font-size: 1.5rem;"></i>
                <h5 class="mb-0">Valor de Inventario por Sede</h5>
            </div>
            <div style="position: relative; height: 300px;">
                <canvas id="chartValorInventario"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-6 mb-4">
        <div class="content-card">
            <div class="d-flex align-items-center mb-3">
                <i class="bi bi-graph-up-arrow me-2" style="color: #10b981; font-size: 1.5rem;"></i>
                <h5 class="mb-0">Materiales por Sede</h5>
            </div>
            <canvas id="chartMaterialesPorSede"></canvas>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="content-card">
            <div class="d-flex align-items-center mb-3">
                <i class="bi bi-exclamation-triangle-fill me-2" style="color: #ef4444; font-size: 1.5rem;"></i>
                <h5 class="mb-0">Stock Crítico por Sede</h5>
            </div>
            <canvas id="chartStockCritico"></canvas>
        </div>
    </div>
</div>

<div class="row">
    <?php foreach ($sedes as $sede): 
        $stats = $estadisticas_sedes[$sede['id']];
    ?>
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="content-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><?php echo $sede['nombre']; ?></h5>
                <span class="badge bg-<?php echo $sede['estado'] === 'activa' ? 'success' : 'secondary'; ?>">
                    <?php echo ucfirst($sede['estado']); ?>
                </span>
            </div>
            
            <h6 class="text-muted small">INVENTARIO</h6>
            <ul class="list-group list-group-flush mb-3">
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Valor Total:</span>
                    <strong><?php echo formatearMoneda($stats['inventario']['valor_total'] ?? 0); ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Materiales Únicos:</span>
                    <strong><?php echo $stats['inventario']['total_materiales'] ?? 0; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Items en Stock Crítico:</span>
                    <strong class="text-danger"><?php echo $stats['inventario']['criticos'] ?? 0; ?></strong>
                </li>
            </ul>

            <h6 class="text-muted small">USUARIOS</h6>
            <ul class="list-group list-group-flush mb-3">
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Total:</span>
                    <strong><?php echo $stats['usuarios']['total_usuarios'] ?? 0; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Técnicos:</span>
                    <strong><?php echo $stats['usuarios']['tecnicos'] ?? 0; ?></strong>
                </li>
            </ul>

            <h6 class="text-muted small">ACTIVIDAD RECIENTE</h6>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Movimientos (30d):</span>
                    <strong><?php echo $stats['movimientos']['total'] ?? 0; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Solicitudes Pendientes:</span>
                    <strong><?php echo $stats['solicitudes']['total'] ?? 0; ?></strong>
                </li>
            </ul>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sedesData = <?php echo json_encode($sedes); ?>;
    const estadisticasData = <?php echo json_encode($estadisticas_sedes); ?>;

    const labels = sedesData.map(s => s.nombre);
    const userCounts = sedesData.map(s => estadisticasData[s.id].usuarios.total_usuarios || 0);
    const materialCounts = sedesData.map(s => estadisticasData[s.id].inventario.total_materiales || 0);
    const inventoryValues = sedesData.map(s => estadisticasData[s.id].inventario.valor_total || 0);
    const stockCritico = sedesData.map(s => estadisticasData[s.id].inventario.criticos || 0);
    const movimientos = sedesData.map(s => estadisticasData[s.id].movimientos.total || 0);
    const solicitudes = sedesData.map(s => estadisticasData[s.id].solicitudes.total || 0);

    // Paleta de colores vibrante
    const colors = {
        primary: '#6366f1',
        secondary: '#06b6d4',
        success: '#10b981',
        warning: '#f59e0b',
        danger: '#ef4444',
        purple: '#8b5cf6',
        pink: '#ec4899',
        orange: '#f97316'
    };

    const colorArray = [
        { bg: 'rgba(99, 102, 241, 0.7)', border: '#6366f1' },
        { bg: 'rgba(6, 182, 212, 0.7)', border: '#06b6d4' },
        { bg: 'rgba(16, 185, 129, 0.7)', border: '#10b981' },
        { bg: 'rgba(245, 158, 11, 0.7)', border: '#f59e0b' },
        { bg: 'rgba(239, 68, 68, 0.7)', border: '#ef4444' },
        { bg: 'rgba(139, 92, 246, 0.7)', border: '#8b5cf6' }
    ];

    // Configuración común para gráficos
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    font: { size: 12, weight: '500' },
                    padding: 15,
                    usePointStyle: true
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleFont: { size: 13, weight: 'bold' },
                bodyFont: { size: 12 },
                borderColor: '#fff',
                borderWidth: 1,
                displayColors: true
            }
        }
    };

    // 1. Gráfico de Usuarios por Sede (Barras)
    const ctxUsuarios = document.getElementById('chartUsuariosPorSede').getContext('2d');
    new Chart(ctxUsuarios, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Número de Usuarios',
                data: userCounts,
                backgroundColor: colorArray.map(c => c.bg),
                borderColor: colorArray.map(c => c.border),
                borderWidth: 2,
                borderRadius: 8,
                hoverBackgroundColor: colorArray.map(c => c.bg.replace('0.7', '0.9'))
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: { size: 11 }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // 2. Gráfico de Materiales por Sede (Barras)
    const ctxMateriales = document.getElementById('chartMaterialesPorSede').getContext('2d');
    new Chart(ctxMateriales, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Materiales Únicos',
                data: materialCounts,
                backgroundColor: colorArray.map(c => c.bg),
                borderColor: colorArray.map(c => c.border),
                borderWidth: 2,
                borderRadius: 8,
                hoverBackgroundColor: colorArray.map(c => c.bg.replace('0.7', '0.9'))
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: { size: 11 }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // 3. Gráfico de Valor de Inventario (Doughnut)
    const ctxInventario = document.getElementById('chartValorInventario').getContext('2d');
    new Chart(ctxInventario, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                label: 'Valor de Inventario (S/.)',
                data: inventoryValues,
                backgroundColor: colorArray.map(c => c.bg),
                borderColor: colorArray.map(c => c.border),
                borderWidth: 2,
                hoverBackgroundColor: colorArray.map(c => c.bg.replace('0.7', '0.9'))
            }]
        },
        options: {
            ...commonOptions,
            maintainAspectRatio: false,
            plugins: {
                ...commonOptions.plugins,
                tooltip: {
                    ...commonOptions.plugins.tooltip,
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed;
                            return 'Valor: S/. ' + value.toLocaleString('es-PE', {maximumFractionDigits: 2});
                        }
                    }
                }
            }
        }
    });

    // 4. Gráfico de Stock Crítico (Barras)
    const ctxStockCritico = document.getElementById('chartStockCritico').getContext('2d');
    new Chart(ctxStockCritico, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Items en Stock Crítico',
                data: stockCritico,
                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                borderColor: '#ef4444',
                borderWidth: 2,
                borderRadius: 8,
                hoverBackgroundColor: 'rgba(239, 68, 68, 0.9)'
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: { size: 11 }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // 5. Gráfico de Movimientos (Línea)
    const ctxMovimientos = document.getElementById('chartMovimientos').getContext('2d');
    new Chart(ctxMovimientos, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Movimientos (30 días)',
                data: movimientos,
                borderColor: '#06b6d4',
                backgroundColor: 'rgba(6, 182, 212, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 6,
                pointBackgroundColor: '#06b6d4',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                hoverPointRadius: 8,
                hoverPointBackgroundColor: '#0891b2'
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: { size: 11 }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // 6. Gráfico de Solicitudes Pendientes (Radar)
    const ctxSolicitudes = document.getElementById('chartSolicitudes').getContext('2d');
    new Chart(ctxSolicitudes, {
        type: 'radar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Solicitudes Pendientes',
                data: solicitudes,
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139, 92, 246, 0.2)',
                borderWidth: 2,
                pointRadius: 5,
                pointBackgroundColor: '#8b5cf6',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                hoverPointRadius: 7
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                r: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: { size: 10 }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            }
        }
    });
});
</script>

<?php include '../layouts/footer.php'; ?>
