<?php
/**
 * Gestión de Proveedores - Administrador
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
        $ruc = sanitizar($_POST['ruc']);
        $email = sanitizar($_POST['email']);
        $telefono = sanitizar($_POST['telefono']);
        $direccion = sanitizar($_POST['direccion'] ?? '');
        $contacto = sanitizar($_POST['contacto'] ?? '');
        $estado = $_POST['estado'] ?? 'activo';
        
        if (empty($nombre) || empty($ruc)) {
            setMensaje('danger', 'El nombre y RUC son requeridos');
        } else {
            // Verificar si ya existe
            $query_check = "SELECT id FROM proveedores WHERE nombre = :nombre OR ruc = :ruc";
            $stmt_check = $db->prepare($query_check);
            $stmt_check->bindParam(':nombre', $nombre);
            $stmt_check->bindParam(':ruc', $ruc);
            $stmt_check->execute();
            
            if ($stmt_check->rowCount() > 0) {
                setMensaje('danger', 'Ya existe un proveedor con ese nombre o RUC');
            } else {
                $query = "INSERT INTO proveedores (nombre, ruc, email, telefono, direccion, contacto, estado) 
                         VALUES (:nombre, :ruc, :email, :telefono, :direccion, :contacto, :estado)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':ruc', $ruc);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':telefono', $telefono);
                $stmt->bindParam(':direccion', $direccion);
                $stmt->bindParam(':contacto', $contacto);
                $stmt->bindParam(':estado', $estado);
                
                if ($stmt->execute()) {
                    registrarActividad($_SESSION['usuario_id'], 'crear', 'proveedores', "Proveedor creado: $nombre");
                    setMensaje('success', 'Proveedor creado exitosamente');
                    redirigir('views/admin/proveedores.php');
                } else {
                    setMensaje('danger', 'Error al crear el proveedor');
                }
            }
        }
    }
    
    if ($accion === 'editar') {
        $id = (int)$_POST['id'];
        $nombre = sanitizar($_POST['nombre']);
        $ruc = sanitizar($_POST['ruc']);
        $email = sanitizar($_POST['email']);
        $telefono = sanitizar($_POST['telefono']);
        $direccion = sanitizar($_POST['direccion'] ?? '');
        $contacto = sanitizar($_POST['contacto'] ?? '');
        $estado = $_POST['estado'] ?? 'activo';
        
        if (empty($nombre) || empty($ruc)) {
            setMensaje('danger', 'El nombre y RUC son requeridos');
        } else {
            $query = "UPDATE proveedores SET nombre = :nombre, ruc = :ruc, email = :email, telefono = :telefono, 
                     direccion = :direccion, contacto = :contacto, estado = :estado WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':ruc', $ruc);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':telefono', $telefono);
            $stmt->bindParam(':direccion', $direccion);
            $stmt->bindParam(':contacto', $contacto);
            $stmt->bindParam(':estado', $estado);
            
            if ($stmt->execute()) {
                registrarActividad($_SESSION['usuario_id'], 'editar', 'proveedores', "Proveedor actualizado ID: $id");
                setMensaje('success', 'Proveedor actualizado exitosamente');
                redirigir('views/admin/proveedores.php');
            } else {
                setMensaje('danger', 'Error al actualizar el proveedor');
            }
        }
    }
    
    if ($accion === 'eliminar') {
        $id = (int)$_POST['id'];
        
        // Verificar que no hay materiales usando este proveedor
        $query_check = "SELECT COUNT(*) as total FROM materiales WHERE proveedor_id = :id";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(':id', $id);
        $stmt_check->execute();
        $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            setMensaje('danger', 'No se puede eliminar el proveedor. Hay ' . $result['total'] . ' material(es) asociado(s)');
        } else {
            $query = "DELETE FROM proveedores WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                registrarActividad($_SESSION['usuario_id'], 'eliminar', 'proveedores', "Proveedor eliminado ID: $id");
                setMensaje('success', 'Proveedor eliminado exitosamente');
                redirigir('views/admin/proveedores.php');
            } else {
                setMensaje('danger', 'Error al eliminar el proveedor');
            }
        }
    }
}

// Paginación
$registros_por_pagina = 20;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Obtener total de proveedores
$query_total = "SELECT COUNT(DISTINCT p.id) as total FROM proveedores p";
$stmt_total = $db->query($query_total);
$result_total = $stmt_total->fetch(PDO::FETCH_ASSOC);
$total_proveedores = $result_total['total'];
$total_paginas = ceil($total_proveedores / $registros_por_pagina);

// Obtener proveedores paginados
$query = "SELECT p.*, COUNT(m.id) as total_materiales 
          FROM proveedores p
          LEFT JOIN materiales m ON p.id = m.proveedor_id
          GROUP BY p.id
          ORDER BY p.nombre
          LIMIT :offset, :limit";
$stmt = $db->prepare($query);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
$stmt->execute();
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Gestión de Proveedores";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5><i class="bi bi-truck me-2"></i>Gestión de Proveedores</h5>
                    <p class="text-muted mb-0">Administre los proveedores del sistema</p>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaProveedor">
                    <i class="bi bi-plus-circle me-1"></i>Nuevo Proveedor
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
                        <h6 class="card-title">Total Proveedores</h6>
                        <h3><?php echo $total_proveedores; ?></h3>
                    </div>
                    <i class="bi bi-truck" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-gradient-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Activos</h6>
                        <h3><?php echo count(array_filter($proveedores, function($p) { return ($p['estado'] ?? 'activo') === 'activo'; })); ?></h3>
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
                        <h6 class="card-title">Con Materiales</h6>
                        <h3><?php echo count(array_filter($proveedores, function($p) { return $p['total_materiales'] > 0; })); ?></h3>
                    </div>
                    <i class="bi bi-box" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Proveedores -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-3">Proveedores Registrados</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>RUC</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Materiales</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($proveedores)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No hay proveedores registrados</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($proveedores as $proveedor): ?>
                            <tr>
                                <td><strong><?php echo $proveedor['nombre']; ?></strong></td>
                                <td><code><?php echo $proveedor['ruc']; ?></code></td>
                                <td><?php echo $proveedor['email'] ?? '-'; ?></td>
                                <td><?php echo $proveedor['telefono'] ?? '-'; ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $proveedor['total_materiales']; ?></span>
                                </td>
                                <td>
                                    <?php
                                    $estado = $proveedor['estado'] ?? 'activo';
                                    $badge_class = $estado === 'activo' ? 'bg-success' : 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($estado); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" data-bs-target="#modalEditarProveedor"
                                            onclick="cargarProveedor(<?php echo htmlspecialchars(json_encode($proveedor)); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($proveedor['total_materiales'] == 0): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="confirmarEliminar(<?php echo $proveedor['id']; ?>, '<?php echo $proveedor['nombre']; ?>')">
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
            
            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
            <nav aria-label="Paginación" class="mt-4">
                <ul class="pagination justify-content-center">
                    <!-- Botón Primera Página -->
                    <?php if ($pagina_actual > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=1">
                            <i class="bi bi-chevron-double-left"></i> Primera
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Botón Página Anterior -->
                    <?php if ($pagina_actual > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>">
                            <i class="bi bi-chevron-left"></i> Anterior
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Números de Página -->
                    <?php
                    $inicio = max(1, $pagina_actual - 2);
                    $fin = min($total_paginas, $pagina_actual + 2);
                    
                    if ($inicio > 1): ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = $inicio; $i <= $fin; $i++): ?>
                    <li class="page-item <?php echo $i === $pagina_actual ? 'active' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($fin < $total_paginas): ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Botón Página Siguiente -->
                    <?php if ($pagina_actual < $total_paginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>">
                            Siguiente <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Botón Última Página -->
                    <?php if ($pagina_actual < $total_paginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=<?php echo $total_paginas; ?>">
                            Última <i class="bi bi-chevron-double-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <!-- Información de Paginación -->
            <div class="text-center text-muted mt-3">
                <small>
                    Mostrando <?php echo count($proveedores); ?> de <?php echo $total_proveedores; ?> proveedores 
                    | Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?>
                </small>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Nueva Proveedor -->
<div class="modal fade" id="modalNuevaProveedor" tabindex="-1" aria-labelledby="modalNuevaProveedorLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalNuevaProveedorLabel">
                    <i class="bi bi-plus-circle me-2"></i>Nuevo Proveedor
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">RUC <span class="text-danger">*</span></label>
                            <input type="text" name="ruc" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="telefono" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Dirección</label>
                            <textarea name="direccion" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Contacto</label>
                            <input type="text" name="contacto" class="form-control" placeholder="Nombre de contacto">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="accion" value="crear" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Crear Proveedor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Proveedor -->
<div class="modal fade" id="modalEditarProveedor" tabindex="-1" aria-labelledby="modalEditarProveedorLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalEditarProveedorLabel">
                    <i class="bi bi-pencil me-2"></i>Editar Proveedor
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">RUC <span class="text-danger">*</span></label>
                            <input type="text" name="ruc" id="edit_ruc" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="telefono" id="edit_telefono" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Dirección</label>
                            <textarea name="direccion" id="edit_direccion" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Contacto</label>
                            <input type="text" name="contacto" id="edit_contacto" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select name="estado" id="edit_estado" class="form-select">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
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
                <h5 class="modal-title">Eliminar Proveedor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-body">
                    <p>¿Está seguro de que desea eliminar el proveedor <strong id="delete_nombre"></strong>?</p>
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
function cargarProveedor(proveedor) {
    document.getElementById('edit_id').value = proveedor.id;
    document.getElementById('edit_nombre').value = proveedor.nombre;
    document.getElementById('edit_ruc').value = proveedor.ruc;
    document.getElementById('edit_email').value = proveedor.email || '';
    document.getElementById('edit_telefono').value = proveedor.telefono || '';
    document.getElementById('edit_direccion').value = proveedor.direccion || '';
    document.getElementById('edit_contacto').value = proveedor.contacto || '';
    document.getElementById('edit_estado').value = proveedor.estado || 'activo';
}

function confirmarEliminar(id, nombre) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_nombre').textContent = nombre;
    const modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
    modal.show();
}
</script>

<?php include '../layouts/footer.php'; ?>
