<?php
/**
 * Asignación de Materiales a Técnicos - Jefe de Almacen
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/AsignacionTecnico.php';
require_once '../../models/User.php';
require_once '../../models/Material.php';

if (!tieneAlgunRol([ROL_JEFE_ALMACEN, ROL_ADMINISTRADOR])) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();

$asignacionModel = new AsignacionTecnico($db);
$userModel = new User($db);
$materialModel = new Material($db);

// Procesar nueva asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar'])) {
    $tecnico_id = (int)$_POST['tecnico_id'];
    $materiales = $_POST['materiales'] ?? [];
    $comentario = sanitizar($_POST['comentario'] ?? '');

    if (empty($tecnico_id) || empty($materiales)) {
        setMensaje('danger', 'Debe seleccionar un técnico y al menos un material.');
    } else {
        $resultado = $asignacionModel->crear($tecnico_id, $_SESSION['usuario_id'], $materiales, $comentario);
        if ($resultado === true) {
            registrarActividad($_SESSION['usuario_id'], 'crear', 'asignacion_tecnicos', "Nueva asignación para técnico ID: {$tecnico_id}");
            setMensaje('success', 'Materiales asignados correctamente.');
        } else {
            $mensaje_error = is_string($resultado) ? $resultado : 'Error al asignar los materiales.';
            setMensaje('danger', $mensaje_error);
        }
    }
    redirigir('views/almacen/asignar_tecnicos.php');
}

// Obtener datos para la vista
$tecnicos = $asignacionModel->obtenerTecnicosDisponibles();
$materiales = $materialModel->obtenerTodos(['estado' => 'activo', 'stockMayorQue' => 0]);
$asignaciones = $asignacionModel->obtenerTecnicosConAsignaciones();

$page_title = "Asignar Materiales a Técnicos";
include '../layouts/header.php';
?>

<div class="row">
    <!-- Tabla de Asignaciones -->
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="bi bi-list-check me-2"></i>Historial de Asignaciones</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaAsignacion">
                    <i class="bi bi-plus-circle me-1"></i>Nueva Asignación
                </button>
            </div>
            <hr>
            <div class="table-responsive mt-3">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Técnico</th>
                            <th>Fecha</th>
                            <th>Asignado por</th>
                            <th>Items</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($asignaciones)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No hay asignaciones registradas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($asignaciones as $asig): ?>
                                <tr>
                                    <td><code><?php echo $asig['codigo_asignacion']; ?></code></td>
                                    <td><?php echo $asig['tecnico_nombre']; ?></td>
                                    <td><?php echo formatearFechaHora($asig['fecha_asignacion']); ?></td>
                                    <td><?php echo $asig['jefe_almacen_nombre']; ?></td>
                                    <td><span class="badge bg-info"><?php echo $asig['total_materiales']; ?> items</span></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalVerMateriales" onclick="cargarMaterialesTecnico(<?php echo isset($asig['tecnico_id']) ? $asig['tecnico_id'] : $asig['id']; ?>, '<?php echo htmlspecialchars($asig['tecnico_nombre']); ?>')">
                                            <i class="bi bi-eye me-1"></i>Ver
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Nueva Asignación -->
<div class="modal fade" id="modalNuevaAsignacion" tabindex="-1" aria-labelledby="modalNuevaAsignacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalNuevaAsignacionLabel">
                    <i class="bi bi-person-plus-fill me-2"></i>Nueva Asignación de Materiales
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formNuevaAsignacion">
                    <div class="mb-3">
                        <label for="tecnico_id" class="form-label">Técnico <span class="text-danger">*</span></label>
                        <select name="tecnico_id" id="tecnico_id" class="form-select" required>
                            <option value="">Seleccione un técnico...</option>
                            <?php foreach ($tecnicos as $tecnico): ?>
                                <option value="<?php echo $tecnico['id']; ?>"><?php echo $tecnico['nombre_completo']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Materiales a Asignar <span class="text-danger">*</span></label>
                        <div id="materiales-container">
                            <!-- Entradas de material se agregarán aquí -->
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="add-material">
                            <i class="bi bi-plus-circle me-1"></i>Añadir Material
                        </button>
                    </div>

                    <div class="mb-3">
                        <label for="comentario" class="form-label">Comentario (Opcional)</label>
                        <textarea name="comentario" id="comentario" class="form-control" rows="2" placeholder="Ingrese observaciones sobre la asignación..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="formNuevaAsignacion" name="asignar" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i>Asignar Materiales
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Plantilla para el selector de material -->
<template id="material-template">
    <div class="input-group mb-2 material-entry">
        <select name="materiales[0][id]" class="form-select">
            <option value="">Seleccione material...</option>
            <?php foreach ($materiales as $mat): ?>
                <option value="<?php echo $mat['id']; ?>" data-stock="<?php echo $mat['stock_actual']; ?>">
                    <?php echo $mat['nombre']; ?> (Stock: <?php echo $mat['stock_actual']; ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="materiales[0][cantidad]" class="form-control" placeholder="Cant." min="1" style="max-width: 80px;">
        <button type="button" class="btn btn-outline-danger remove-material"><i class="bi bi-trash"></i></button>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('materiales-container');
    const addButton = document.getElementById('add-material');
    const template = document.getElementById('material-template');
    let materialIndex = 0;

    function addMaterialEntry() {
        const clone = template.content.cloneNode(true);
        const newEntry = clone.querySelector('.material-entry');
        
        const select = newEntry.querySelector('select');
        select.name = `materiales[${materialIndex}][id]`;
        
        const input = newEntry.querySelector('input');
        input.name = `materiales[${materialIndex}][cantidad]`;

        select.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const stock = selectedOption.dataset.stock;
            input.max = stock;
        });

        container.appendChild(newEntry);
        materialIndex++;
    }

    addButton.addEventListener('click', addMaterialEntry);

    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-material')) {
            e.target.closest('.material-entry').remove();
        }
    });

    // Añadir una entrada inicial
    addMaterialEntry();
});

// Función para cargar materiales del técnico
function cargarMaterialesTecnico(tecnicoId, tecnicoNombre) {
    const contenido = document.getElementById('contenidoMateriales');
    
    contenido.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>
    `;
    
    fetch('<?php echo $base_url; ?>ajax/obtener_materiales_tecnico.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'tecnico_id=' + tecnicoId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.materiales.length > 0) {
            let html = `
                <div class="mb-3">
                    <h6 class="text-primary"><i class="bi bi-person-fill me-2"></i>${tecnicoNombre}</h6>
                    <hr>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 25%;">Código</th>
                                <th style="width: 35%;">Material</th>
                                <th style="width: 10%;" class="text-center">Cant.</th>
                                <th style="width: 15%;" class="text-end">Costo Unit.</th>
                                <th style="width: 15%;" class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            let totalValor = 0;
            data.materiales.forEach(item => {
                const total = item.cantidad * (item.costo_unitario || 0);
                totalValor += total;
                html += `
                    <tr>
                        <td><code class="text-primary">${item.codigo}</code></td>
                        <td>
                            <strong>${item.material_nombre}</strong>
                            <br>
                            <small class="text-muted">${item.unidad}</small>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info">${item.cantidad}</span>
                        </td>
                        <td class="text-end">
                            <strong><?php echo CURRENCY_SYMBOL; ?> ${parseFloat(item.costo_unitario || 0).toFixed(2)}</strong>
                        </td>
                        <td class="text-end">
                            <strong class="text-success"><?php echo CURRENCY_SYMBOL; ?> ${total.toFixed(2)}</strong>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted d-block">Total de Materiales</small>
                                <h5 class="text-primary">${data.materiales.length} items</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted d-block">Valor Total Asignado</small>
                                <h5 class="text-success"><?php echo CURRENCY_SYMBOL; ?> ${totalValor.toFixed(2)}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            contenido.innerHTML = html;
        } else {
            contenido.innerHTML = `
                <div class="alert alert-warning" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    No hay materiales asignados a este técnico.
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        contenido.innerHTML = `
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                Error al cargar los materiales.
            </div>
        `;
    });
}
</script>

<!-- Modal Ver Materiales Asignados -->
<div class="modal fade" id="modalVerMateriales" tabindex="-1" aria-labelledby="modalVerMaterialesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalVerMaterialesLabel">
                    <i class="bi bi-box-seam me-2"></i>Materiales Asignados
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="contenidoMateriales">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
