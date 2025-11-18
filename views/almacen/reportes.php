<?php
/**
 * Reportes - Jefe de Almacén
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';

if (!tieneAlgunRol([ROL_JEFE_ALMACEN, ROL_ADMINISTRADOR])) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();

// Procesar exportación
if (isset($_GET['exportar'])) {
    $tipo = $_GET['exportar'];
    
    if ($tipo === 'stock') {
        $query = "SELECT m.codigo, m.nombre, c.nombre as categoria, m.stock_actual, m.unidad, 
                         m.stock_minimo, m.ubicacion
                  FROM materiales m
                  LEFT JOIN categorias_materiales c ON m.categoria_id = c.id
                  WHERE m.estado = 'activo'
                  ORDER BY m.nombre";
        $stmt = $db->query($query);
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        $headers = ['Código', 'Material', 'Categoría', 'Stock', 'Unidad', 'Mínimo', 'Ubicación'];
        exportarCSV('reporte_stock_' . date('Ymd') . '.csv', $data, $headers);
    }
    
    if ($tipo === 'stock_bajo') {
        $query = "SELECT m.codigo, m.nombre, m.stock_actual, m.stock_minimo, m.unidad, m.ubicacion
                  FROM materiales m
                  WHERE m.stock_actual <= m.stock_minimo AND m.estado = 'activo'
                  ORDER BY m.stock_actual ASC";
        $stmt = $db->query($query);
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        $headers = ['Código', 'Material', 'Stock Actual', 'Stock Mínimo', 'Unidad', 'Ubicación'];
        exportarCSV('reporte_stock_bajo_' . date('Ymd') . '.csv', $data, $headers);
    }
}

$page_title = "Reportes de Almacén";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <h5><i class="bi bi-file-earmark-bar-graph me-2"></i>Reportes de Almacén</h5>
            <p class="text-muted mb-0">Genere reportes sobre el estado del inventario</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="content-card">
            <div class="text-center mb-3">
                <i class="bi bi-boxes text-primary" style="font-size: 48px;"></i>
            </div>
            <h5 class="text-center mb-3">Reporte de Stock Actual</h5>
            <p class="text-muted text-center">
                Lista completa de materiales con su stock actual, mínimos y ubicación.
            </p>
            <div class="d-grid">
                <a href="?exportar=stock" class="btn btn-primary">
                    <i class="bi bi-download me-2"></i>Descargar Reporte
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="content-card">
            <div class="text-center mb-3">
                <i class="bi bi-exclamation-triangle text-danger" style="font-size: 48px;"></i>
            </div>
            <h5 class="text-center mb-3">Reporte de Stock Bajo</h5>
            <p class="text-muted text-center">
                Materiales que han alcanzado o están por debajo del stock mínimo.
            </p>
            <div class="d-grid">
                <a href="?exportar=stock_bajo" class="btn btn-danger">
                    <i class="bi bi-download me-2"></i>Descargar Reporte
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Vista previa de Stock Bajo -->
<?php
$query = "SELECT m.codigo, m.nombre, m.stock_actual, m.stock_minimo, m.unidad, c.nombre as categoria
          FROM materiales m
          LEFT JOIN categorias_materiales c ON m.categoria_id = c.id
          WHERE m.stock_actual <= m.stock_minimo AND m.estado = 'activo'
          ORDER BY m.stock_actual ASC
          LIMIT 10";
$stock_bajo = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (!empty($stock_bajo)): ?>
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <h5 class="text-danger mb-3">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Alertas de Stock Crítico (Top 10)
            </h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Material</th>
                            <th>Categoría</th>
                            <th>Stock Actual</th>
                            <th>Stock Mínimo</th>
                            <th>Déficit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stock_bajo as $item): ?>
                        <tr class="table-danger">
                            <td><code><?php echo $item['codigo']; ?></code></td>
                            <td><strong><?php echo $item['nombre']; ?></strong></td>
                            <td><?php echo $item['categoria'] ?? '-'; ?></td>
                            <td>
                                <span class="badge bg-danger">
                                    <?php echo $item['stock_actual']; ?> <?php echo $item['unidad']; ?>
                                </span>
                            </td>
                            <td><?php echo $item['stock_minimo']; ?> <?php echo $item['unidad']; ?></td>
                            <td>
                                <span class="text-danger fw-bold">
                                    -<?php echo ($item['stock_minimo'] - $item['stock_actual']); ?> <?php echo $item['unidad']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="alert alert-warning mt-3">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Acción requerida:</strong> Considere realizar órdenes de compra para estos materiales.
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../layouts/footer.php'; ?>
