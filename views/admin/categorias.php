<?php

/**
 * Gestión de Categorías de Materiales - Administrador
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';

// Verificar permisos
if (!tieneRol(ROL_ADMINISTRADOR) && !tieneRol(ROL_SUPERADMIN)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // Crear categoría
    if ($accion === 'crear') {
        $nombre = sanitizar($_POST['nombre'] ?? '');
        $descripcion = sanitizar($_POST['descripcion'] ?? '');
        if (empty($nombre)) {
            setMensaje('danger', 'El nombre de la categoría es requerido');
        } else {
            // Verificar duplicado
            $check = $db->prepare('SELECT id FROM categorias_materiales WHERE nombre = :nombre');
            $check->bindParam(':nombre', $nombre);
            $check->execute();
            if ($check->rowCount() > 0) {
                setMensaje('danger', 'Ya existe una categoría con ese nombre');
            } else {
                $stmt = $db->prepare('INSERT INTO categorias_materiales (nombre, descripcion) VALUES (:nombre, :descripcion)');
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':descripcion', $descripcion);
                if ($stmt->execute()) {
                    registrarActividad($_SESSION['usuario_id'], 'crear', 'categorias_materiales', "Categoría creada: $nombre");
                    setMensaje('success', 'Categoría creada exitosamente');
                } else {
                    setMensaje('danger', 'Error al crear la categoría');
                }
                redirigir('views/admin/categorias.php');
            }
        }
    }

    // Editar categoría
    if ($accion === 'editar') {
        $id = (int)($_POST['id'] ?? 0);
        $nombre = sanitizar($_POST['nombre'] ?? '');
        $descripcion = sanitizar($_POST['descripcion'] ?? '');
        if (empty($nombre)) {
            setMensaje('danger', 'El nombre de la categoría es requerido');
        } else {
            $stmt = $db->prepare('UPDATE categorias_materiales SET nombre = :nombre, descripcion = :descripcion WHERE id = :id');
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':descripcion', $descripcion);
            if ($stmt->execute()) {
                registrarActividad($_SESSION['usuario_id'], 'editar', 'categorias_materiales', "Categoría actualizada ID: $id");
                setMensaje('success', 'Categoría actualizada exitosamente');
            } else {
                setMensaje('danger', 'Error al actualizar la categoría');
            }
            redirigir('views/admin/categorias.php');
        }
    }

    // Eliminar categoría (también elimina materiales asociados)
    if ($accion === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        // Eliminar materiales asociados
        $delMaterials = $db->prepare('DELETE FROM materiales WHERE categoria_id = :id');
        $delMaterials->bindParam(':id', $id);
        $delMaterials->execute();
        // Eliminar categoría
        $stmt = $db->prepare('DELETE FROM categorias_materiales WHERE id = :id');
        $stmt->bindParam(':id', $id);
        if ($stmt->execute()) {
            registrarActividad($_SESSION['usuario_id'], 'eliminar', 'categorias_materiales', "Categoría eliminada ID: $id");
            setMensaje('success', 'Categoría y sus materiales asociados eliminados exitosamente');
        } else {
            setMensaje('danger', 'Error al eliminar la categoría');
        }
        redirigir('views/admin/categorias.php');
    }
}

// Obtener categorías con conteo de materiales
$query = "SELECT c.*, COUNT(m.id) AS total_materiales
          FROM categorias_materiales c
          LEFT JOIN materiales m ON c.id = m.categoria_id
          GROUP BY c.id
          ORDER BY c.nombre";
$stmt = $db->query($query);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Gestión de Categorías';
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card d-flex justify-content-between align-items-center">
            <h5><i class="bi bi-tags me-2"></i>Gestión de Categorías</h5>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaCategoria">
                <i class="bi bi-plus-circle me-1"></i> Nueva Categoría
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Materiales</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categorias)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No hay categorías registradas</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categorias as $cat): ?>
                                <tr>
                                    <td><strong><?php echo $cat['nombre']; ?></strong></td>
                                    <td><?php echo $cat['descripcion'] ?? '-'; ?></td>
                                    <td><span class="badge bg-info"><?php echo $cat['total_materiales']; ?> materiales</span></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#modalEditarCategoria" onclick="cargarCategoria(<?php echo htmlspecialchars(json_encode($cat)); ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmarEliminar(<?php echo $cat['id']; ?>, '<?php echo addslashes($cat['nombre']); ?>')">
                                            <i class="bi bi-trash"></i>
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

<!-- Modal Nueva Categoría -->
<div class="modal fade" id="modalNuevaCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="accion" value="crear">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-1"></i> Nueva Categoría</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Categoría -->
<div class="modal fade" id="modalEditarCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-pencil me-1"></i> Editar Categoría</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" id="edit_descripcion" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Eliminar Categoría</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de eliminar la categoría <strong id="delete_nombre"></strong>?</p>
                    <p class="text-muted">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function cargarCategoria(cat) {
        document.getElementById('edit_id').value = cat.id;
        document.getElementById('edit_nombre').value = cat.nombre;
        document.getElementById('edit_descripcion').value = cat.descripcion || '';
    }

    function confirmarEliminar(id, nombre) {
        document.getElementById('delete_id').value = id;
        document.getElementById('delete_nombre').textContent = nombre;
        const modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
        modal.show();
    }
</script>

<?php include '../layouts/footer.php'; ?>