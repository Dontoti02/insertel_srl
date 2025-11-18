<?php
/**
 * Gestión de Categorías de Materiales - Administrador
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
        $descripcion = sanitizar($_POST['descripcion'] ?? '');
        $estado = $_POST['estado'] ?? 'activo';
        
        if (empty($nombre)) {
            setMensaje('danger', 'El nombre de la categoría es requerido');
        } else {
            // Verificar si ya existe
            $query_check = "SELECT id FROM categorias_materiales WHERE nombre = :nombre";
            $stmt_check = $db->prepare($query_check);
            $stmt_check->bindParam(':nombre', $nombre);
            $stmt_check->execute();
            
            if ($stmt_check->rowCount() > 0) {
                setMensaje('danger', 'Ya existe una categoría con ese nombre');
            } else {
                $query = "INSERT INTO categorias_materiales (nombre, descripcion, estado) 
                         VALUES (:nombre, :descripcion, :estado)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':descripcion', $descripcion);
                $stmt->bindParam(':estado', $estado);
                
                if ($stmt->execute()) {
                    registrarActividad($_SESSION['usuario_id'], 'crear', 'categorias_materiales', "Categoría creada: $nombre");
                    setMensaje('success', 'Categoría creada exitosamente');
                    redirigir('views/admin/categorias.php');
                } else {
                    setMensaje('danger', 'Error al crear la categoría');
                }
            }
        }
    }
    
    if ($accion === 'editar') {
        $id = (int)$_POST['id'];
        $nombre = sanitizar($_POST['nombre']);
        $descripcion = sanitizar($_POST['descripcion'] ?? '');
        $estado = $_POST['estado'] ?? 'activo';
        
        if (empty($nombre)) {
            setMensaje('danger', 'El nombre de la categoría es requerido');
        } else {
            $query = "UPDATE categorias_materiales SET nombre = :nombre, descripcion = :descripcion, estado = :estado WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':estado', $estado);
            
            if ($stmt->execute()) {
                registrarActividad($_SESSION['usuario_id'], 'editar', 'categorias_materiales', "Categoría actualizada ID: $id");
                setMensaje('success', 'Categoría actualizada exitosamente');
                redirigir('views/admin/categorias.php');
            } else {
                setMensaje('danger', 'Error al actualizar la categoría');
            }
        }
    }
    
    if ($accion === 'eliminar') {
        $id = (int)$_POST['id'];
        
        // Verificar que no hay materiales usando esta categoría
        $query_check = "SELECT COUNT(*) as total FROM materiales WHERE categoria_id = :id";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(':id', $id);
        $stmt_check->execute();
        $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            setMensaje('danger', 'No se puede eliminar la categoría. Hay ' . $result['total'] . ' material(es) usando esta categoría');
        } else {
            $query = "DELETE FROM categorias_materiales WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                registrarActividad($_SESSION['usuario_id'], 'eliminar', 'categorias_materiales', "Categoría eliminada ID: $id");
                setMensaje('success', 'Categoría eliminada exitosamente');
                redirigir('views/admin/categorias.php');
            } else {
                setMensaje('danger', 'Error al eliminar la categoría');
            }
        }
    }
}

// Obtener categorías
$query = "SELECT c.*, COUNT(m.id) as total_materiales 
          FROM categorias_materiales c
          LEFT JOIN materiales m ON c.id = m.categoria_id
          GROUP BY c.id
          ORDER BY c.nombre";
$stmt = $db->query($query);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Gestión de Categorías";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5><i class="bi bi-tags me-2"></i>Gestión de Categorías</h5>
                    <p class="text-muted mb-0">Administre las categorías de materiales del sistema</p>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaCategoria">
                    <i class="bi bi-plus-circle me-1"></i>Nueva Categoría
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-gradient-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Categorías</h6>
                        <h3><?php echo count($categorias); ?></h3>
                    </div>
                    <i class="bi bi-tags" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-gradient-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Activas</h6>
                        <h3><?php echo count(array_filter($categorias, function($c) { return ($c['estado'] ?? 'activo') === 'activo'; })); ?></h3>
                    </div>
                    <i class="bi bi-check-circle" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-gradient-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Inactivas</h6>
                        <h3><?php echo count(array_filter($categorias, function($c) { return ($c['estado'] ?? 'activo') === 'inactivo'; })); ?></h3>
                    </div>
                    <i class="bi bi-x-circle" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Categorías -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-3">Categorías Registradas</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Materiales</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categorias)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No hay categorías registradas</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($categorias as $categoria): ?>
                            <tr>
                                <td><strong><?php echo $categoria['nombre']; ?></strong></td>
                                <td><?php echo $categoria['descripcion'] ?? '-'; ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $categoria['total_materiales']; ?></span>
                                </td>
                                <td>
                                    <?php
                                    $estado = $categoria['estado'] ?? 'activo';
                                    $badge_class = $estado === 'activo' ? 'bg-success' : 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($estado); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" data-bs-target="#modalEditarCategoria"
                                            onclick="cargarCategoria(<?php echo htmlspecialchars(json_encode($categoria)); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($categoria['total_materiales'] == 0): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="confirmarEliminar(<?php echo $categoria['id']; ?>, '<?php echo $categoria['nombre']; ?>')">
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

<!-- Modal Nueva Categoría -->
<div class="modal fade" id="modalNuevaCategoria" tabindex="-1" aria-labelledby="modalNuevaCategoriaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalNuevaCategoriaLabel">
                    <i class="bi bi-plus-circle me-2"></i>Nueva Categoría
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="accion" value="crear" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Crear Categoría
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Categoría -->
<div class="modal fade" id="modalEditarCategoria" tabindex="-1" aria-labelledby="modalEditarCategoriaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalEditarCategoriaLabel">
                    <i class="bi bi-pencil me-2"></i>Editar Categoría
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
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" id="edit_descripcion" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" id="edit_estado" class="form-select">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
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
                <h5 class="modal-title">Eliminar Categoría</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-body">
                    <p>¿Está seguro de que desea eliminar la categoría <strong id="delete_nombre"></strong>?</p>
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
.bg-gradient-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
}
</style>

<script>
function cargarCategoria(categoria) {
    document.getElementById('edit_id').value = categoria.id;
    document.getElementById('edit_nombre').value = categoria.nombre;
    document.getElementById('edit_descripcion').value = categoria.descripcion || '';
    document.getElementById('edit_estado').value = categoria.estado;
}

function confirmarEliminar(id, nombre) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_nombre').textContent = nombre;
    const modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
    modal.show();
}
</script>

<?php include '../layouts/footer.php'; ?>
