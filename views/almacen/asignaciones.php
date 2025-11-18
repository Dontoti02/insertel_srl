<?php
/**
 * Asignaciones - Listado de Técnicos y Materiales
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/AsignacionTecnico.php';

if (!tieneAlgunRol([ROL_JEFE_ALMACEN, ROL_ADMINISTRADOR])) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();

$asignacionModel = new AsignacionTecnico($db);

// Obtener todos los técnicos con asignaciones
$tecnicos_con_asignaciones = $asignacionModel->obtenerTecnicosConAsignaciones();

// Obtener filtros
$filtro_tecnico = sanitizar($_GET['tecnico'] ?? '');
$filtro_material = sanitizar($_GET['material'] ?? '');
$filtro_fecha = sanitizar($_GET['fecha'] ?? '');

// Aplicar filtros
$asignaciones_filtradas = [];
foreach ($tecnicos_con_asignaciones as $tecnico) {
    // Filtro por nombre de técnico
    if (!empty($filtro_tecnico) && stripos($tecnico['tecnico_nombre'], $filtro_tecnico) === false) {
        continue;
    }
    
    // Filtro por fecha
    if (!empty($filtro_fecha)) {
        $fecha_asignacion = date('Y-m-d', strtotime($tecnico['fecha_asignacion']));
        if ($fecha_asignacion !== $filtro_fecha) {
            continue;
        }
    }
    
    // Si hay filtro de material, obtener materiales del técnico
    if (!empty($filtro_material)) {
        $materiales_tecnico = $asignacionModel->obtenerPorTecnico($tecnico['id']);
        $tiene_material = false;
        foreach ($materiales_tecnico as $mat) {
            if (stripos($mat['material_nombre'], $filtro_material) !== false || 
                stripos($mat['codigo'], $filtro_material) !== false) {
                $tiene_material = true;
                break;
            }
        }
        if (!$tiene_material) {
            continue;
        }
    }
    
    $asignaciones_filtradas[] = $tecnico;
}

$page_title = "Asignaciones de Materiales";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4><i class="bi bi-diagram-3 me-2"></i>Asignaciones de Materiales a Técnicos</h4>
            <a href="<?php echo $base_url; ?>views/almacen/asignar_tecnicos.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Nueva Asignación
            </a>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="filtro_tecnico" class="form-label">
                            <i class="bi bi-person me-1"></i>Técnico
                        </label>
                        <input type="text" class="form-control" id="filtro_tecnico" name="tecnico" 
                               placeholder="Buscar por nombre..." value="<?php echo htmlspecialchars($filtro_tecnico); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="filtro_material" class="form-label">
                            <i class="bi bi-box me-1"></i>Material
                        </label>
                        <input type="text" class="form-control" id="filtro_material" name="material" 
                               placeholder="Buscar por nombre o código..." value="<?php echo htmlspecialchars($filtro_material); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="filtro_fecha" class="form-label">
                            <i class="bi bi-calendar me-1"></i>Fecha
                        </label>
                        <input type="date" class="form-control" id="filtro_fecha" name="fecha" 
                               value="<?php echo htmlspecialchars($filtro_fecha); ?>">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i>Filtrar
                        </button>
                    </div>
                </form>
                <?php if (!empty($filtro_tecnico) || !empty($filtro_material) || !empty($filtro_fecha)): ?>
                    <div class="mt-2">
                        <a href="<?php echo $base_url; ?>views/almacen/asignaciones.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>Limpiar filtros
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (empty($tecnicos_con_asignaciones)): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                No hay asignaciones registradas. <a href="<?php echo $base_url; ?>views/almacen/asignar_tecnicos.php">Crear una nueva asignación</a>
            </div>
        </div>
    </div>
<?php elseif (empty($asignaciones_filtradas)): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-warning" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                No se encontraron asignaciones con los filtros aplicados.
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Técnico</th>
                                <th>Email</th>
                                <th>Materiales</th>
                                <th>Valor Total</th>
                                <th>Fecha Asignación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($asignaciones_filtradas as $tecnico): ?>
                                <tr>
                                    <td>
                                        <code><?php echo htmlspecialchars($tecnico['codigo_asignacion']); ?></code>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($tecnico['tecnico_nombre']); ?></strong>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($tecnico['email']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $tecnico['total_materiales']; ?> items</span>
                                    </td>
                                    <td>
                                        <strong><?php echo formatearMoneda($tecnico['valor_total']); ?></strong>
                                    </td>
                                    <td>
                                        <small><?php echo formatearFechaHora($tecnico['fecha_asignacion']); ?></small>
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

<?php include '../layouts/footer.php'; ?>
