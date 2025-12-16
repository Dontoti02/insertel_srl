<?php

/**
 * Estadísticas de Uso de Materiales - Jefe de Almacén
 * Muestra qué materiales usan más los técnicos de la sede
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';

if (!tieneRol(ROL_JEFE_ALMACEN)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();

$sede_id = $_SESSION['sede_id'] ?? null;

// Obtener materiales más usados por técnicos
$query_materiales_usados = "SELECT m.id, m.codigo, m.nombre, m.unidad, c.nombre as categoria,
                                   COUNT(DISTINCT lm.tecnico_id) as tecnicos_usuarios,
                                   SUM(lm.cantidad) as total_usado,
                                   COUNT(lm.id) as veces_usado,
                                   MAX(lm.fecha_liquidacion) as ultima_liquidacion
                           FROM liquidaciones_materiales lm
                           INNER JOIN materiales m ON lm.material_id = m.id
                           LEFT JOIN categorias_materiales c ON m.categoria_id = c.id
                           WHERE lm.sede_id = :sede_id
                           GROUP BY m.id, m.codigo, m.nombre, m.unidad, c.nombre
                           ORDER BY total_usado DESC
                           LIMIT 20";

$stmt_materiales = $db->prepare($query_materiales_usados);
$stmt_materiales->execute([':sede_id' => $sede_id]);
$materiales_mas_usados = $stmt_materiales->fetchAll(PDO::FETCH_ASSOC);

// Obtener técnicos con más consumo
$query_tecnicos = "SELECT u.id, u.nombre_completo, u.email,
                         COUNT(DISTINCT lm.material_id) as materiales_diferentes,
                         SUM(lm.cantidad) as total_items_usados,
                         COUNT(lm.id) as servicios_realizados,
                         MAX(lm.fecha_liquidacion) as ultima_liquidacion
                  FROM liquidaciones_materiales lm
                  INNER JOIN usuarios u ON lm.tecnico_id = u.id
                  WHERE lm.sede_id = :sede_id
                  GROUP BY u.id, u.nombre_completo, u.email
                  ORDER BY total_items_usados DESC";

$stmt_tecnicos = $db->prepare($query_tecnicos);
$stmt_tecnicos->execute([':sede_id' => $sede_id]);
$tecnicos_consumo = $stmt_tecnicos->fetchAll(PDO::FETCH_ASSOC);

// Obtener uso por tipo de servicio
$query_servicios = "SELECT at.tipo_servicio,
                          COUNT(DISTINCT lm.id) as liquidaciones,
                          COUNT(DISTINCT lm.tecnico_id) as tecnicos,
                          SUM(lm.cantidad) as total_materiales
                   FROM liquidaciones_materiales lm
                   INNER JOIN actas_tecnicas at ON lm.acta_id = at.id
                   WHERE lm.sede_id = :sede_id
                   GROUP BY at.tipo_servicio
                   ORDER BY total_materiales DESC";

$stmt_servicios = $db->prepare($query_servicios);
$stmt_servicios->execute([':sede_id' => $sede_id]);
$uso_por_servicio = $stmt_servicios->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas generales
$query_stats = "SELECT 
                   COUNT(DISTINCT lm.material_id) as materiales_diferentes,
                   COUNT(DISTINCT lm.tecnico_id) as tecnicos_activos,
                   SUM(lm.cantidad) as total_items_liquidados,
                   COUNT(lm.id) as total_liquidaciones
               FROM liquidaciones_materiales lm
               WHERE lm.sede_id = :sede_id";

$stmt_stats = $db->prepare($query_stats);
$stmt_stats->execute([':sede_id' => $sede_id]);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Obtener materiales por categoría
$query_categorias = "SELECT c.nombre as categoria,
                           COUNT(DISTINCT lm.material_id) as materiales_usados,
                           SUM(lm.cantidad) as total_usado
                    FROM liquidaciones_materiales lm
                    INNER JOIN materiales m ON lm.material_id = m.id
                    LEFT JOIN categorias_materiales c ON m.categoria_id = c.id
                    WHERE lm.sede_id = :sede_id
                    GROUP BY c.nombre
                    ORDER BY total_usado DESC";

$stmt_categorias = $db->prepare($query_categorias);
$stmt_categorias->execute([':sede_id' => $sede_id]);
$uso_por_categoria = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Estadísticas de Uso de Materiales";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Estadísticas de Uso de Materiales</h5>
                    <p class="text-muted mb-0 mt-1">Análisis de consumo de materiales por técnicos en tu sede</p>
                </div>
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>Imprimir Reporte
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas Generales -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="icon blue">
                <i class="bi bi-box-seam"></i>
            </div>
            <h3><?php echo $stats['materiales_diferentes'] ?? 0; ?></h3>
            <p>Materiales Utilizados</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="icon green">
                <i class="bi bi-people"></i>
            </div>
            <h3><?php echo $stats['tecnicos_activos'] ?? 0; ?></h3>
            <p>Técnicos Activos</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="icon orange">
                <i class="bi bi-clipboard-check"></i>
            </div>
            <h3><?php echo $stats['total_liquidaciones'] ?? 0; ?></h3>
            <p>Total Liquidaciones</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="icon purple">
                <i class="bi bi-stack"></i>
            </div>
            <h3><?php echo $stats['total_items_liquidados'] ?? 0; ?></h3>
            <p>Items Consumidos</p>
        </div>
    </div>
</div>

<!-- Materiales Más Usados -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-trophy me-2"></i>Top 20 - Materiales Más Usados</h5>
            </div>

            <?php if (empty($materiales_mas_usados)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 48px;"></i>
                    <p class="text-muted mt-3">No hay datos de uso de materiales aún</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Código</th>
                                <th>Material</th>
                                <th>Categoría</th>
                                <th>Total Usado</th>
                                <th>Técnicos</th>
                                <th>Veces Usado</th>
                                <th>Última Liquidación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materiales_mas_usados as $index => $material): ?>
                                <tr>
                                    <td>
                                        <?php if ($index < 3): ?>
                                            <span class="badge bg-<?php echo $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'bronze'); ?>">
                                                <?php echo $index + 1; ?>°
                                            </span>
                                        <?php else: ?>
                                            <?php echo $index + 1; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?php echo $material['codigo']; ?></code></td>
                                    <td><strong><?php echo $material['nombre']; ?></strong></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $material['categoria'] ?? 'Sin categoría'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success" style="font-size: 14px;">
                                            <?php echo $material['total_usado']; ?> <?php echo $material['unidad']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $material['tecnicos_usuarios']; ?> técnico(s)</td>
                                    <td><?php echo $material['veces_usado']; ?> vez(ces)</td>
                                    <td><?php echo formatearFecha($material['ultima_liquidacion']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Técnicos con Mayor Consumo -->
<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-person-badge me-2"></i>Técnicos con Mayor Consumo</h5>
            </div>

            <?php if (empty($tecnicos_consumo)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 48px;"></i>
                    <p class="text-muted mt-3">No hay datos de consumo por técnicos</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Técnico</th>
                                <th>Items Usados</th>
                                <th>Servicios</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tecnicos_consumo as $tecnico): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $tecnico['nombre_completo']; ?></strong><br>
                                        <small class="text-muted"><?php echo $tecnico['email']; ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo $tecnico['total_items_usados']; ?> items
                                        </span>
                                    </td>
                                    <td><?php echo $tecnico['servicios_realizados']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Uso por Tipo de Servicio -->
    <div class="col-md-6 mb-3">
        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-tools me-2"></i>Uso por Tipo de Servicio</h5>
            </div>

            <?php if (empty($uso_por_servicio)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 48px;"></i>
                    <p class="text-muted mt-3">No hay datos de uso por servicio</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Tipo Servicio</th>
                                <th>Materiales</th>
                                <th>Técnicos</th>
                                <th>Liquidaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($uso_por_servicio as $servicio): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $servicio['tipo_servicio']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo $servicio['total_materiales']; ?></strong> items
                                    </td>
                                    <td><?php echo $servicio['tecnicos']; ?></td>
                                    <td><?php echo $servicio['liquidaciones']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Uso por Categoría -->
<?php if (!empty($uso_por_categoria)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="content-card">
                <div class="card-header">
                    <h5><i class="bi bi-tags me-2"></i>Consumo por Categoría de Material</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Categoría</th>
                                <th>Materiales Diferentes</th>
                                <th>Total Usado</th>
                                <th>Porcentaje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_general = array_sum(array_column($uso_por_categoria, 'total_usado'));
                            foreach ($uso_por_categoria as $categoria):
                                $porcentaje = $total_general > 0 ? ($categoria['total_usado'] / $total_general) * 100 : 0;
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $categoria['categoria'] ?? 'Sin categoría'; ?></strong>
                                    </td>
                                    <td><?php echo $categoria['materiales_usados']; ?> material(es)</td>
                                    <td>
                                        <span class="badge bg-success">
                                            <?php echo $categoria['total_usado']; ?> items
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 25px;">
                                            <div class="progress-bar bg-primary"
                                                role="progressbar"
                                                style="width: <?php echo $porcentaje; ?>%"
                                                aria-valuenow="<?php echo $porcentaje; ?>"
                                                aria-valuemin="0"
                                                aria-valuemax="100">
                                                <?php echo number_format($porcentaje, 1); ?>%
                                            </div>
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
<?php endif; ?>

<style>
    .badge.bg-bronze {
        background-color: #cd7f32 !important;
    }

    .stat-card .icon.purple {
        background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
    }

    @media print {

        .btn,
        .sidebar,
        .header,
        .menu-section {
            display: none !important;
        }
    }
</style>

<?php include '../layouts/footer.php'; ?>