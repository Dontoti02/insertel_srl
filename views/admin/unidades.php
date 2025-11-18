<?php
/**
 * Gestión de Unidades de Medida - Administrador
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';

if (!tieneRol(ROL_ADMINISTRADOR)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'crear') {
        $nombre = sanitizar($_POST['nombre']);
        $simbolo = sanitizar($_POST['simbolo']);
        $descripcion = sanitizar($_POST['descripcion'] ?? '');
        
        if (empty($nombre) || empty($simbolo)) {
            setMensaje('danger', 'El nombre y símbolo son requeridos');
        } else {
            // Verificar si ya existe
            $query_check = "SELECT id FROM unidades_medida WHERE nombre = :nombre OR simbolo = :simbolo";
            $stmt_check = $db->prepare($query_check);
            $stmt_check->bindParam(':nombre', $nombre);
            $stmt_check->bindParam(':simbolo', $simbolo);
            $stmt_check->execute();
            
            if ($stmt_check->rowCount() > 0) {
                setMensaje('danger', 'Ya existe una unidad con ese nombre o símbolo');
            } else {
                $query = "INSERT INTO unidades_medida (nombre, simbolo, descripcion) 
                         VALUES (:nombre, :simbolo, :descripcion)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':simbolo', $simbolo);
                $stmt->bindParam(':descripcion', $descripcion);
                
                if ($stmt->execute()) {
                    registrarActividad($_SESSION['usuario_id'], 'crear', 'unidades_medida', "Unidad creada: $nombre");
                    setMensaje('success', 'Unidad creada exitosamente');
                    redirigir('views/admin/unidades.php');
                } else {
                    setMensaje('danger', 'Error al crear la unidad');
                }
            }
        }
    }
    
    if ($accion === 'editar') {
        $id = (int)$_POST['id'];
        $nombre = sanitizar($_POST['nombre']);
        $simbolo = sanitizar($_POST['simbolo']);
        $descripcion = sanitizar($_POST['descripcion'] ?? '');
        
        if (empty($nombre) || empty($simbolo)) {
            setMensaje('danger', 'El nombre y símbolo son requeridos');
        } else {
            // Verificar si ya existe otro con el mismo nombre o símbolo
            $query_check = "SELECT id FROM unidades_medida WHERE (nombre = :nombre OR simbolo = :simbolo) AND id != :id";
            $stmt_check = $db->prepare($query_check);
            $stmt_check->bindParam(':nombre', $nombre);
            $stmt_check->bindParam(':simbolo', $simbolo);
            $stmt_check->bindParam(':id', $id);
            $stmt_check->execute();
            
            if ($stmt_check->rowCount() > 0) {
                setMensaje('danger', 'Ya existe otra unidad con ese nombre o símbolo');
            } else {
                $query = "UPDATE unidades_medida SET nombre = :nombre, simbolo = :simbolo, descripcion = :descripcion WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':simbolo', $simbolo);
                $stmt->bindParam(':descripcion', $descripcion);
                
                if ($stmt->execute()) {
                    registrarActividad($_SESSION['usuario_id'], 'editar', 'unidades_medida', "Unidad actualizada ID: $id");
                    setMensaje('success', 'Unidad actualizada exitosamente');
                    redirigir('views/admin/unidades.php');
                } else {
                    setMensaje('danger', 'Error al actualizar la unidad');
                }
            }
        }
    }
    
    if ($accion === 'eliminar') {
        $id = (int)$_POST['id'];
        
        // Verificar que no hay materiales usando esta unidad
        $query_check = "SELECT COUNT(*) as total FROM materiales WHERE unidad = (SELECT nombre FROM unidades_medida WHERE id = :id)";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(':id', $id);
        $stmt_check->execute();
        $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            setMensaje('danger', 'No se puede eliminar la unidad. Hay ' . $result['total'] . ' material(es) usando esta unidad');
        } else {
            $query = "DELETE FROM unidades_medida WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                registrarActividad($_SESSION['usuario_id'], 'eliminar', 'unidades_medida', "Unidad eliminada ID: $id");
                setMensaje('success', 'Unidad eliminada exitosamente');
                redirigir('views/admin/unidades.php');
            } else {
                setMensaje('danger', 'Error al eliminar la unidad');
            }
        }
    }
}

// Obtener unidades
$query = "SELECT u.*, COUNT(m.id) as total_materiales 
          FROM unidades_medida u
          LEFT JOIN materiales m ON u.nombre COLLATE utf8mb4_unicode_ci = m.unidad COLLATE utf8mb4_unicode_ci
          GROUP BY u.id
          ORDER BY u.nombre";
$stmt = $db->query($query);
$unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Gestión de Unidades";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5><i class="bi bi-rulers me-2"></i>Gestión de Unidades de Medida</h5>
                    <p class="text-muted mb-0">Administre las unidades de medida del sistema</p>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaUnidad">
                    <i class="bi bi-plus-circle me-1"></i>Nueva Unidad
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card bg-gradient-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Unidades</h6>
                        <h3><?php echo count($unidades); ?></h3>
                    </div>
                    <i class="bi bi-rulers" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-gradient-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">En Uso</h6>
                        <h3><?php echo count(array_filter($unidades, function($u) { return $u['total_materiales'] > 0; })); ?></h3>
                    </div>
                    <i class="bi bi-check-circle" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Unidades -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-3">Unidades Registradas</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Símbolo</th>
                            <th>Descripción</th>
                            <th>Materiales</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($unidades)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No hay unidades registradas</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($unidades as $unidad): ?>
                            <tr>
                                <td><strong><?php echo $unidad['nombre']; ?></strong></td>
                                <td>
                                    <code><?php echo $unidad['simbolo']; ?></code>
                                </td>
                                <td><?php echo $unidad['descripcion'] ?? '-'; ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $unidad['total_materiales']; ?></span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" data-bs-target="#modalEditarUnidad"
                                            onclick="cargarUnidad(<?php echo htmlspecialchars(json_encode($unidad)); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($unidad['total_materiales'] == 0): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="confirmarEliminar(<?php echo $unidad['id']; ?>, '<?php echo $unidad['nombre']; ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php endif; ?>
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

<!-- Modal Nueva Unidad -->
<div class="modal fade" id="modalNuevaUnidad" tabindex="-1" aria-labelledby="modalNuevaUnidadLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalNuevaUnidadLabel">
                    <i class="bi bi-plus-circle me-2"></i>Nueva Unidad
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Kilogramo" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Símbolo <span class="text-danger">*</span></label>
                        <input type="text" name="simbolo" class="form-control" placeholder="Ej: kg" required maxlength="10">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="accion" value="crear" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Crear Unidad
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Unidad -->
<div class="modal fade" id="modalEditarUnidad" tabindex="-1" aria-labelledby="modalEditarUnidadLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalEditarUnidadLabel">
                    <i class="bi bi-pencil me-2"></i>Editar Unidad
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Símbolo <span class="text-danger">*</span></label>
                        <input type="text" name="simbolo" id="edit_simbolo" class="form-control" required maxlength="10">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" id="edit_descripcion" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="accion" value="editar" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Eliminar Unidad</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-body">
                    <p>¿Está seguro de que desea eliminar la unidad <strong id="delete_nombre"></strong>?</p>
                    <p class="text-muted">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="accion" value="eliminar" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.bg-gradient-info {
    background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
}
.bg-gradient-success {
    background: linear-gradient(135deg, #10b981 0%, #14b8a6 100%);
}
</style>

<script>
function cargarUnidad(unidad) {
    document.getElementById('edit_id').value = unidad.id;
    document.getElementById('edit_nombre').value = unidad.nombre;
    document.getElementById('edit_simbolo').value = unidad.simbolo;
    document.getElementById('edit_descripcion').value = unidad.descripcion || '';
}

function confirmarEliminar(id, nombre) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_nombre').textContent = nombre;
    const modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
    modal.show();
}
</script>

<?php include '../layouts/footer.php'; ?>
