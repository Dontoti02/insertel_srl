<?php

/**
 * Reportes Globales - Superadministrador
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/Sede.php';

if (!tieneRol(ROL_SUPERADMIN)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();
$sedeModel = new Sede($db);
$sedes = $sedeModel->obtenerTodas();

// Procesar exportación
if (isset($_GET['exportar'])) {
    $tipo = $_GET['exportar'];
    $sede_id = $_GET['sede_id'] ?? null;

    $params = [];
    $sede_where = '';
    if ($sede_id) {
        $params[':sede_id'] = $sede_id;
    }

    if ($tipo === 'materiales') {
        $sede_where = $sede_id ? " AND m.sede_id = :sede_id" : "";
        $query = "SELECT s.nombre as sede, m.codigo, m.nombre, c.nombre as categoria, m.stock_actual, m.unidad, 
                         m.stock_minimo, m.stock_maximo, m.ubicacion, m.costo_unitario
                  FROM materiales m
                  LEFT JOIN categorias_materiales c ON m.categoria_id = c.id
                  LEFT JOIN sedes s ON m.sede_id = s.id
                  WHERE m.estado = 'activo' {$sede_where}
                  ORDER BY s.nombre, m.nombre";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        $headers = ['Sede', 'Código', 'Material', 'Categoría', 'Stock', 'Unidad', 'Mín', 'Máx', 'Ubicación', 'Costo'];
        exportarExcel('reporte_global_materiales_' . date('Ymd') . '.xls', $data, $headers);
    }

    if ($tipo === 'movimientos') {
        $fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
        $fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
        $params[':desde'] = $fecha_desde;
        $params[':hasta'] = $fecha_hasta;

        $sede_where = $sede_id ? " AND mi.sede_id = :sede_id" : "";
        $query = "SELECT s.nombre as sede, DATE(mi.fecha_movimiento) as fecha, m.codigo, m.nombre, 
                         mi.tipo_movimiento, mi.cantidad, u.nombre_completo, mi.motivo
                  FROM movimientos_inventario mi
                  INNER JOIN materiales m ON mi.material_id = m.id
                  INNER JOIN usuarios u ON mi.usuario_id = u.id
                  LEFT JOIN sedes s ON mi.sede_id = s.id
                  WHERE DATE(mi.fecha_movimiento) BETWEEN :desde AND :hasta {$sede_where}
                  ORDER BY s.nombre, mi.fecha_movimiento DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        $headers = ['Sede', 'Fecha', 'Código', 'Material', 'Tipo', 'Cantidad', 'Usuario', 'Motivo'];
        exportarExcel('reporte_global_movimientos_' . date('Ymd') . '.xls', $data, $headers);
    }

    if ($tipo === 'solicitudes') {
        $sede_where = $sede_id ? " AND s.sede_id = :sede_id" : "";
        $query = "SELECT sed.nombre as sede, s.codigo_solicitud, u.nombre_completo, DATE(s.fecha_solicitud) as fecha,
                         s.estado, s.motivo
                  FROM solicitudes s
                  INNER JOIN usuarios u ON s.tecnico_id = u.id
                  LEFT JOIN sedes sed ON s.sede_id = sed.id
                  WHERE 1=1 {$sede_where}
                  ORDER BY sed.nombre, s.fecha_solicitud DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        $headers = ['Sede', 'Código', 'Técnico', 'Fecha', 'Estado', 'Motivo'];
        exportarExcel('reporte_global_solicitudes_' . date('Ymd') . '.xls', $data, $headers);
    }
}

$page_title = "Reportes Globales";
include '../layouts/header.php';

// Obtener datos para gráficas
$stats_sede_id = $_GET['stats_sede_id'] ?? null;
$periodo_grafica = $_GET['periodo_grafica'] ?? 'mensual';
$stats_params = [];
$stats_sede_where = '';
if ($stats_sede_id) {
    $stats_params[':sede_id'] = $stats_sede_id;
    $stats_sede_where = ' AND sede_id = :sede_id';
}

// Determinar rango de fechas según período
$fecha_inicio = date('Y-m-d');
$label_fecha = 'Últimos 30 días';
$group_by_format = 'DATE';

switch ($periodo_grafica) {
    case 'semanal':
        $fecha_inicio = date('Y-m-d', strtotime('-7 days'));
        $label_fecha = 'Últimos 7 días';
        $group_by_format = 'DATE';
        break;
    case 'mensual':
        $fecha_inicio = date('Y-m-d', strtotime('-30 days'));
        $label_fecha = 'Últimos 30 días';
        $group_by_format = 'DATE';
        break;
    case 'anual':
        $fecha_inicio = date('Y-m-d', strtotime('-365 days'));
        $label_fecha = 'Últimos 365 días';
        $group_by_format = 'WEEK';
        break;
}

$stats_params[':fecha_inicio'] = $fecha_inicio;

// Datos para gráfica de evolución de valor de inventario
if ($group_by_format === 'DATE') {
    $query_valor = "SELECT DATE(fecha_movimiento) as fecha, 
                           SUM(CASE WHEN tipo_movimiento = 'entrada' THEN cantidad ELSE -cantidad END) as cambio_cantidad
                    FROM movimientos_inventario
                    WHERE fecha_movimiento >= :fecha_inicio
                    {$stats_sede_where}
                    GROUP BY DATE(fecha_movimiento)
                    ORDER BY fecha ASC";
} else {
    $query_valor = "SELECT CONCAT(YEAR(fecha_movimiento), '-W', WEEK(fecha_movimiento)) as fecha,
                           SUM(CASE WHEN tipo_movimiento = 'entrada' THEN cantidad ELSE -cantidad END) as cambio_cantidad
                    FROM movimientos_inventario
                    WHERE fecha_movimiento >= :fecha_inicio
                    {$stats_sede_where}
                    GROUP BY YEAR(fecha_movimiento), WEEK(fecha_movimiento)
                    ORDER BY fecha ASC";
}

$stmt_valor = $db->prepare($query_valor);
$stmt_valor->execute($stats_params);
$movimientos_diarios = $stmt_valor->fetchAll(PDO::FETCH_ASSOC);

// Calcular valor acumulado
$valor_acumulado = [];
$acumulado = 0;
foreach ($movimientos_diarios as $mov) {
    $acumulado += (int)$mov['cambio_cantidad'];
    $valor_acumulado[] = [
        'fecha' => $mov['fecha'],
        'valor' => $acumulado
    ];
}

// Datos para gráfica de entradas y salidas
if ($group_by_format === 'DATE') {
    $query_movimientos = "SELECT DATE(fecha_movimiento) as fecha,
                                 tipo_movimiento,
                                 SUM(cantidad) as total_cantidad
                          FROM movimientos_inventario
                          WHERE fecha_movimiento >= :fecha_inicio
                          {$stats_sede_where}
                          GROUP BY DATE(fecha_movimiento), tipo_movimiento
                          ORDER BY fecha ASC";
} else {
    $query_movimientos = "SELECT CONCAT(YEAR(fecha_movimiento), '-W', WEEK(fecha_movimiento)) as fecha,
                                 tipo_movimiento,
                                 SUM(cantidad) as total_cantidad
                          FROM movimientos_inventario
                          WHERE fecha_movimiento >= :fecha_inicio
                          {$stats_sede_where}
                          GROUP BY YEAR(fecha_movimiento), WEEK(fecha_movimiento), tipo_movimiento
                          ORDER BY fecha ASC";
}

$stmt_movimientos = $db->prepare($query_movimientos);
$stmt_movimientos->execute($stats_params);
$movimientos_tipo = $stmt_movimientos->fetchAll(PDO::FETCH_ASSOC);

// Organizar datos por tipo
$entradas_por_fecha = [];
$salidas_por_fecha = [];
$todas_fechas = [];

foreach ($movimientos_tipo as $mov) {
    $fecha = $mov['fecha'];
    if (!in_array($fecha, $todas_fechas)) {
        $todas_fechas[] = $fecha;
        $entradas_por_fecha[$fecha] = 0;
        $salidas_por_fecha[$fecha] = 0;
    }

    if ($mov['tipo_movimiento'] === 'entrada') {
        $entradas_por_fecha[$fecha] = (int)$mov['total_cantidad'];
    } else {
        $salidas_por_fecha[$fecha] = (int)$mov['total_cantidad'];
    }
}

sort($todas_fechas);
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <h5><i class="bi bi-file-earmark-bar-graph me-2"></i>Generador de Reportes Globales</h5>
            <p class="text-muted mb-0">Exporte datos de todas las sedes o filtre por una específica.</p>
        </div>
    </div>
</div>

<!-- Reportes Disponibles -->
<div class="row">
    <!-- Reporte de Materiales -->
    <div class="col-lg-4 mb-4">
        <div class="content-card">
            <div class="text-center mb-3"><i class="bi bi-box text-primary" style="font-size: 48px;"></i></div>
            <h5 class="text-center mb-3">Inventario de Materiales</h5>
            <form method="GET">
                <input type="hidden" name="exportar" value="materiales">
                <div class="mb-3"><label class="form-label small">Sede:</label><select name="sede_id" class="form-select form-select-sm">
                        <option value="">Todas las sedes</option><?php foreach ($sedes as $sede): ?><option value="<?php echo $sede['id']; ?>"><?php echo $sede['nombre']; ?></option><?php endforeach; ?>
                    </select></div>
                <div class="d-grid"><button type="submit" class="btn btn-primary"><i class="bi bi-file-earmark-excel me-2"></i>Descargar Excel</button></div>
            </form>
        </div>
    </div>

    <!-- Reporte de Movimientos -->
    <div class="col-lg-4 mb-4">
        <div class="content-card">
            <div class="text-center mb-3"><i class="bi bi-arrow-left-right text-success" style="font-size: 48px;"></i></div>
            <h5 class="text-center mb-3">Movimientos de Inventario</h5>
            <form method="GET">
                <input type="hidden" name="exportar" value="movimientos">
                <div class="mb-2"><label class="form-label small">Sede:</label><select name="sede_id" class="form-select form-select-sm">
                        <option value="">Todas las sedes</option><?php foreach ($sedes as $sede): ?><option value="<?php echo $sede['id']; ?>"><?php echo $sede['nombre']; ?></option><?php endforeach; ?>
                    </select></div>
                <div class="mb-2"><label class="form-label small">Desde:</label><input type="date" name="fecha_desde" class="form-control form-control-sm" value="<?php echo date('Y-m-01'); ?>"></div>
                <div class="mb-3"><label class="form-label small">Hasta:</label><input type="date" name="fecha_hasta" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>"></div>
                <div class="d-grid"><button type="submit" class="btn btn-success"><i class="bi bi-file-earmark-excel me-2"></i>Descargar Excel</button></div>
            </form>
        </div>
    </div>

    <!-- Reporte de Solicitudes -->
    <div class="col-lg-4 mb-4">
        <div class="content-card">
            <div class="text-center mb-3"><i class="bi bi-clipboard-check text-warning" style="font-size: 48px;"></i></div>
            <h5 class="text-center mb-3">Solicitudes de Técnicos</h5>
            <form method="GET">
                <input type="hidden" name="exportar" value="solicitudes">
                <div class="mb-3"><label class="form-label small">Sede:</label><select name="sede_id" class="form-select form-select-sm">
                        <option value="">Todas las sedes</option><?php foreach ($sedes as $sede): ?><option value="<?php echo $sede['id']; ?>"><?php echo $sede['nombre']; ?></option><?php endforeach; ?>
                    </select></div>
                <div class="d-grid"><button type="submit" class="btn btn-warning"><i class="bi bi-file-earmark-excel me-2"></i>Descargar Excel</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Estadísticas Globales -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-4"><i class="bi bi-graph-up me-2"></i>Estadísticas Globales del Sistema</h5>
            <?php
            $stats_sede_id = $_GET['stats_sede_id'] ?? null;
            $stats_params = [];
            $stats_sede_where = '';
            if ($stats_sede_id) {
                $stats_params[':sede_id'] = $stats_sede_id;
                $stats_sede_where = ' WHERE sede_id = :sede_id';
            }
            ?>
            <form method="GET" class="row g-3 mb-4 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Filtrar estadísticas por Sede:</label>
                    <select name="stats_sede_id" class="form-select">
                        <option value="">Todas las sedes</option>
                        <?php foreach ($sedes as $sede): ?>
                            <option value="<?php echo $sede['id']; ?>" <?php echo ($stats_sede_id == $sede['id']) ? 'selected' : ''; ?>><?php echo $sede['nombre']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary">Aplicar</button>
                </div>
            </form>

            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted">Resumen de Inventario <?php if ($stats_sede_id) echo " (Sede)"; ?></h6>
                    <?php
                    $query = "SELECT 
                                COUNT(*) as total_materiales,
                                SUM(stock_actual) as total_items,
                                SUM(stock_actual * costo_unitario) as valor_total,
                                COUNT(CASE WHEN stock_actual <= stock_minimo THEN 1 END) as criticos
                              FROM materiales {$stats_sede_where}";
                    $stmt = $db->prepare($query);
                    $stmt->execute($stats_params);
                    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between"><span>Total de Materiales:</span><strong><?php echo $stats['total_materiales']; ?></strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Total de Items:</span><strong><?php echo number_format($stats['total_items'] ?? 0); ?></strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Valor Total:</span><strong><?php echo formatearMoneda($stats['valor_total'] ?? 0); ?></strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Materiales en Estado Crítico:</span><strong class="text-danger"><?php echo $stats['criticos']; ?></strong></li>
                    </ul>
                </div>

                <div class="col-md-6">
                    <h6 class="text-muted">Actividad del Mes <?php if ($stats_sede_id) echo " (Sede)"; ?></h6>
                    <?php
                    $mov_sede_where = $stats_sede_id ? ' AND sede_id = :sede_id' : '';
                    $query = "SELECT tipo_movimiento, COUNT(*) as total, SUM(cantidad) as suma_cantidad
                              FROM movimientos_inventario
                              WHERE MONTH(fecha_movimiento) = MONTH(CURRENT_DATE())
                              AND YEAR(fecha_movimiento) = YEAR(CURRENT_DATE())
                              {$mov_sede_where}
                              GROUP BY tipo_movimiento";
                    $stmt = $db->prepare($query);
                    $stmt->execute($stats_params);
                    $mov_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <ul class="list-group">
                        <?php foreach ($mov_stats as $stat): ?>
                            <li class="list-group-item d-flex justify-content-between"><span><?php echo ucfirst($stat['tipo_movimiento']); ?>s:</span><strong><?php echo $stat['total']; ?> movimientos</strong></li>
                        <?php endforeach; ?>

                        <?php
                        $sol_sede_where = $stats_sede_id ? ' AND sede_id = :sede_id' : '';
                        $query_sol = "SELECT COUNT(*) as total FROM solicitudes 
                                      WHERE MONTH(fecha_solicitud) = MONTH(CURRENT_DATE()) {$sol_sede_where}";
                        $stmt = $db->prepare($query_sol);
                        $stmt->execute($stats_params);
                        $sol_stat = $stmt->fetch(PDO::FETCH_ASSOC);
                        ?>
                        <li class="list-group-item d-flex justify-content-between"><span>Solicitudes Recibidas:</span><strong><?php echo $sol_stat['total']; ?></strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros de Gráficas -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <h6 class="mb-3"><i class="bi bi-funnel me-2"></i>Filtros de Gráficas</h6>
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="stats_sede_id" value="<?php echo $stats_sede_id; ?>">

                <div class="col-md-4">
                    <label class="form-label">Período:</label>
                    <select name="periodo_grafica" class="form-select" onchange="this.form.submit()">
                        <option value="semanal" <?php echo ($periodo_grafica === 'semanal') ? 'selected' : ''; ?>>
                            📅 Semanal (Últimos 7 días)
                        </option>
                        <option value="mensual" <?php echo ($periodo_grafica === 'mensual') ? 'selected' : ''; ?>>
                            📊 Mensual (Últimos 30 días)
                        </option>
                        <option value="anual" <?php echo ($periodo_grafica === 'anual') ? 'selected' : ''; ?>>
                            📈 Anual (Últimos 365 días)
                        </option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Sede:</label>
                    <select name="stats_sede_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Todas las sedes</option>
                        <?php foreach ($sedes as $sede): ?>
                            <option value="<?php echo $sede['id']; ?>" <?php echo ($stats_sede_id == $sede['id']) ? 'selected' : ''; ?>>
                                <?php echo $sede['nombre']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Gráficas de Análisis -->
<div class="row mt-4">
    <!-- Gráfica 1: Evolución del Valor de Inventario -->
    <div class="col-lg-6 mb-4">
        <div class="content-card">
            <h6 class="mb-3"><i class="bi bi-graph-up me-2"></i>Evolución del Inventario (<?php echo $label_fecha; ?>)</h6>
            <canvas id="chartValorInventario" height="80"></canvas>
            <small class="text-muted d-block mt-2">
                <i class="bi bi-info-circle me-1"></i>
                Muestra la evolución acumulada de cantidad de items en el inventario
            </small>
            <div class="mt-2 p-2 bg-light rounded">
                <small><strong>Datos:</strong> <span id="countValor">0</span> registros | <strong>Rango:</strong> <?php echo $label_fecha; ?></small>
            </div>
        </div>
    </div>

    <!-- Gráfica 2: Entradas y Salidas de Materiales -->
    <div class="col-lg-6 mb-4">
        <div class="content-card">
            <h6 class="mb-3"><i class="bi bi-arrow-left-right me-2"></i>Entradas y Salidas de Materiales (<?php echo $label_fecha; ?>)</h6>
            <canvas id="chartMovimientos" height="80"></canvas>
            <small class="text-muted d-block mt-2">
                <i class="bi bi-info-circle me-1"></i>
                Comparativa de entradas (verde) vs salidas (rojo) de materiales
            </small>
            <div class="mt-2 p-2 bg-light rounded">
                <small><strong>Datos:</strong> <span id="countMovimientos">0</span> registros | <strong>Rango:</strong> <?php echo $label_fecha; ?></small>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<script>
    // Datos para gráfica de evolución de inventario
    const datosValor = <?php echo json_encode($valor_acumulado); ?>;
    const fechasValor = datosValor.map(d => d.fecha);
    const valoresInventario = datosValor.map(d => d.valor);

    // Mostrar conteo de datos
    document.getElementById('countValor').textContent = datosValor.length;

    // Verificar si hay datos
    if (datosValor.length === 0) {
        console.warn('No hay datos para la gráfica de evolución de inventario');
    }

    const ctxValor = document.getElementById('chartValorInventario').getContext('2d');
    new Chart(ctxValor, {
        type: 'line',
        data: {
            labels: fechasValor.length > 0 ? fechasValor : ['Sin datos'],
            datasets: [{
                label: 'Cantidad Acumulada',
                data: valoresInventario.length > 0 ? valoresInventario : [0],
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#6366f1',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                filler: {
                    propagate: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Datos para gráfica de entradas y salidas
    const todasFechas = <?php echo json_encode($todas_fechas); ?>;
    const entradasPorFecha = <?php echo json_encode($entradas_por_fecha); ?>;
    const salidasPorFecha = <?php echo json_encode($salidas_por_fecha); ?>;
    const entradasData = todasFechas.map(fecha => entradasPorFecha[fecha] || 0);
    const salidasData = todasFechas.map(fecha => salidasPorFecha[fecha] || 0);

    // Mostrar conteo de datos
    document.getElementById('countMovimientos').textContent = todasFechas.length;

    // Verificar si hay datos
    if (todasFechas.length === 0) {
        console.warn('No hay datos para la gráfica de movimientos');
    }

    const ctxMovimientos = document.getElementById('chartMovimientos').getContext('2d');
    new Chart(ctxMovimientos, {
        type: 'line',
        data: {
            labels: todasFechas.length > 0 ? todasFechas : ['Sin datos'],
            datasets: [{
                    label: 'Entradas',
                    data: entradasData.length > 0 ? entradasData : [0],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                },
                {
                    label: 'Salidas',
                    data: salidasData.length > 0 ? salidasData : [0],
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#ef4444',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    console.log('Datos de evolución:', datosValor);
    console.log('Datos de movimientos:', {
        entradas: entradasData,
        salidas: salidasData
    });
</script>

<?php include '../layouts/footer.php'; ?>