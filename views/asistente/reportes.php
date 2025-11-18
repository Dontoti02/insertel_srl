<?php
/**
 * Reportes Operativos - Asistente de Almacén
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';

if (!tieneRol(ROL_ASISTENTE_ALMACEN)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();

// Procesar exportación
if (isset($_GET['exportar'])) {
    $tipo = $_GET['exportar'];
    $fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
    $fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

    if ($tipo === 'movimientos') {
        $query = "SELECT DATE(mi.fecha_movimiento) as fecha, m.codigo, m.nombre, 
                         mi.tipo_movimiento, mi.cantidad, u.nombre_completo, mi.motivo
                  FROM movimientos_inventario mi
                  INNER JOIN materiales m ON mi.material_id = m.id
                  INNER JOIN usuarios u ON mi.usuario_id = u.id
                  WHERE DATE(mi.fecha_movimiento) BETWEEN :desde AND :hasta
                  AND mi.sede_id = :sede_id
                  ORDER BY mi.fecha_movimiento DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([':desde' => $fecha_desde, ':hasta' => $fecha_hasta, ':sede_id' => obtenerSedeActual()]);
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        $headers = ['Fecha', 'Código', 'Material', 'Tipo', 'Cantidad', 'Usuario', 'Motivo'];
        exportarCSV('reporte_movimientos_' . date('Ymd') . '.csv', $data, $headers);
    }

    if ($tipo === 'entradas') {
        $query = "SELECT DATE(em.fecha_entrada) as fecha, m.codigo, m.nombre, 
                         em.cantidad, p.nombre as proveedor, em.numero_lote, em.fecha_vencimiento
                  FROM entradas_materiales em
                  INNER JOIN movimientos_inventario mi ON em.movimiento_id = mi.id
                  INNER JOIN materiales m ON mi.material_id = m.id
                  LEFT JOIN proveedores p ON em.proveedor_id = p.id
                  WHERE DATE(em.fecha_entrada) BETWEEN :desde AND :hasta
                  ORDER BY em.fecha_entrada DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([':desde' => $fecha_desde, ':hasta' => $fecha_hasta]);
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        $headers = ['Fecha', 'Código', 'Material', 'Cantidad', 'Proveedor', 'Lote', 'Vencimiento'];
        exportarCSV('reporte_entradas_' . date('Ymd') . '.csv', $data, $headers);
    }

    if ($tipo === 'salidas') {
        $query = "SELECT DATE(sm.fecha_salida) as fecha, m.codigo, m.nombre, 
                         sm.cantidad, u.nombre_completo as destino, sm.numero_orden
                  FROM salidas_materiales sm
                  INNER JOIN movimientos_inventario mi ON sm.movimiento_id = mi.id
                  INNER JOIN materiales m ON mi.material_id = m.id
                  LEFT JOIN usuarios u ON sm.tecnico_id = u.id
                  WHERE DATE(sm.fecha_salida) BETWEEN :desde AND :hasta
                  ORDER BY sm.fecha_salida DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([':desde' => $fecha_desde, ':hasta' => $fecha_hasta]);
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        $headers = ['Fecha', 'Código', 'Material', 'Cantidad', 'Destino', 'Orden'];
        exportarCSV('reporte_salidas_' . date('Ymd') . '.csv', $data, $headers);
    }
}

// Obtener datos para mostrar
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
$tipo_reporte = $_GET['tipo'] ?? 'movimientos';

// Obtener movimientos del período
$query = "SELECT DATE(mi.fecha_movimiento) as fecha, m.codigo, m.nombre, 
                 mi.tipo_movimiento, mi.cantidad, u.nombre_completo, mi.motivo
          FROM movimientos_inventario mi
          INNER JOIN materiales m ON mi.material_id = m.id
          INNER JOIN usuarios u ON mi.usuario_id = u.id
          WHERE DATE(mi.fecha_movimiento) BETWEEN :desde AND :hasta
          AND mi.sede_id = :sede_id
          ORDER BY mi.fecha_movimiento DESC
          LIMIT 100";
$stmt = $db->prepare($query);
$stmt->execute([':desde' => $fecha_desde, ':hasta' => $fecha_hasta, ':sede_id' => obtenerSedeActual()]);
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Reportes Operativos";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <h5><i class="bi bi-file-earmark-bar-graph me-2"></i>Reportes Operativos</h5>
            <p class="text-muted mb-0">Genere reportes de movimientos diarios del almacén</p>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Fecha Desde</label>
                    <input type="date" name="fecha_desde" class="form-control" value="<?php echo $fecha_desde; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $fecha_hasta; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo de Reporte</label>
                    <select name="tipo" class="form-select">
                        <option value="movimientos" <?php echo $tipo_reporte === 'movimientos' ? 'selected' : ''; ?>>Movimientos</option>
                        <option value="entradas" <?php echo $tipo_reporte === 'entradas' ? 'selected' : ''; ?>>Entradas</option>
                        <option value="salidas" <?php echo $tipo_reporte === 'salidas' ? 'selected' : ''; ?>>Salidas</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-search me-1"></i>Generar
                    </button>
                    <a href="?exportar=<?php echo $tipo_reporte; ?>&fecha_desde=<?php echo $fecha_desde; ?>&fecha_hasta=<?php echo $fecha_hasta; ?>" class="btn btn-success">
                        <i class="bi bi-download me-1"></i>Exportar CSV
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tabla de Movimientos -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-3">Movimientos del Período</h5>
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Código</th>
                            <th>Material</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Usuario</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movimientos)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No hay movimientos en el período seleccionado</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($movimientos as $mov): ?>
                            <tr>
                                <td><?php echo formatearFecha($mov['fecha']); ?></td>
                                <td><code><?php echo $mov['codigo']; ?></code></td>
                                <td><?php echo $mov['nombre']; ?></td>
                                <td>
                                    <?php
                                    $badge_class = match($mov['tipo_movimiento']) {
                                        'entrada' => 'bg-success',
                                        'salida' => 'bg-danger',
                                        'ajuste' => 'bg-warning',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($mov['tipo_movimiento']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $mov['cantidad']; ?></span>
                                </td>
                                <td><?php echo $mov['nombre_completo']; ?></td>
                                <td><?php echo $mov['motivo']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
