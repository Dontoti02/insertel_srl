<?php
/**
 * Gestión de Sedes - Administrador
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/Sede.php';
require_once '../../models/User.php';

if (!tieneRol(ROL_ADMINISTRADOR) && !tieneRol(ROL_SUPERADMIN)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();
$sedeModel = new Sede($db);
$userModel = new User($db);

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if (!esSuperAdmin()) {
        if ($accion !== 'editar') {
            setMensaje('danger', 'Acción no permitida para su rol');
            redirigir('views/admin/sedes.php');
        }
    }
    
    if ($accion === 'crear') {
        if (!esSuperAdmin()) {
            setMensaje('danger', 'Solo el Superadmin puede crear sedes');
            redirigir('views/admin/sedes.php');
        }
        $sedeModel->nombre = sanitizar($_POST['nombre']);
        $sedeModel->codigo = sanitizar($_POST['codigo']);
        $sedeModel->direccion = sanitizar($_POST['direccion']);
        $sedeModel->telefono = sanitizar($_POST['telefono']);
        $sedeModel->email = sanitizar($_POST['email']);
        $sedeModel->responsable_id = !empty($_POST['responsable_id']) ? (int)$_POST['responsable_id'] : null;
        $sedeModel->estado = $_POST['estado'];
        
        if ($sedeModel->existeCodigo($sedeModel->codigo)) {
            setMensaje('danger', 'El código de sede ya existe');
        } else {
            $id = $sedeModel->crear();
            if ($id) {
                if (!empty($sedeModel->responsable_id)) {
                    $userModel->cambiarSede($sedeModel->responsable_id, $id);
                }
                registrarActividad($_SESSION['usuario_id'], 'crear', 'sedes', "Sede creada: {$sedeModel->nombre}");
                setMensaje('success', 'Sede creada exitosamente');
                redirigir('views/admin/sedes.php');
            } else {
                setMensaje('danger', 'Error al crear la sede');
            }
        }
    }
    
    if ($accion === 'editar') {
        if (!esSuperAdmin()) {
            $sede_id_post = (int)($_POST['id'] ?? 0);
            verificarAccesoSede($sede_id_post);
        }
        $sedeModel->id = (int)$_POST['id'];
        $sedeModel->nombre = sanitizar($_POST['nombre']);
        $sedeModel->codigo = sanitizar($_POST['codigo']);
        $sedeModel->direccion = sanitizar($_POST['direccion']);
        $sedeModel->telefono = sanitizar($_POST['telefono']);
        $sedeModel->email = sanitizar($_POST['email']);
        $sedeModel->responsable_id = !empty($_POST['responsable_id']) ? (int)$_POST['responsable_id'] : null;
        $sedeModel->estado = $_POST['estado'];
        
        if ($sedeModel->existeCodigo($sedeModel->codigo, $sedeModel->id)) {
            setMensaje('danger', 'El código de sede ya existe');
        } else {
            // Obtener la sede antes de la actualización para comparar el responsable
            $sede_anterior = $sedeModel->obtenerPorId($sedeModel->id);
            $old_responsable_id = $sede_anterior['responsable_id'] ?? null;

            if ($sedeModel->actualizar()) {
                // Si el responsable ha cambiado
                if ($old_responsable_id != $sedeModel->responsable_id) {
                    // Desasignar la sede del responsable anterior si existía
                    if (!empty($old_responsable_id)) {
                        $userModel->cambiarSede($old_responsable_id, null); // Establecer sede_id a NULL
                    }
                    // Asignar la sede al nuevo responsable si se ha seleccionado uno
                    if (!empty($sedeModel->responsable_id)) {
                        $userModel->cambiarSede($sedeModel->responsable_id, $sedeModel->id);
                    }
                }
                registrarActividad($_SESSION['usuario_id'], 'editar', 'sedes', "Sede actualizada ID: {$sedeModel->id}");
                setMensaje('success', 'Sede actualizada exitosamente');
                redirigir('views/admin/sedes.php');
            } else {
                setMensaje('danger', 'Error al actualizar la sede');
            }
        }
    }
    
    if ($accion === 'eliminar') {
        if (!esSuperAdmin()) {
            setMensaje('danger', 'Solo el Superadmin puede eliminar sedes');
            redirigir('views/admin/sedes.php');
        }
        $sede_id = (int)$_POST['id'];
        $sede_eliminar = $sedeModel->obtenerPorId($sede_id);
        
        if ($sede_eliminar && $sedeModel->eliminar($sede_id)) {
            registrarActividad($_SESSION['usuario_id'], 'eliminar', 'sedes', "Sede eliminada: {$sede_eliminar['nombre']}");
            setMensaje('success', 'Sede eliminada exitosamente');
            redirigir('views/admin/sedes.php');
        } else {
            setMensaje('danger', 'No se puede eliminar la sede. Verifique que no tenga usuarios asignados.');
        }
    }
}

// Obtener filtros
$filtros = [];
if (!empty($_GET['estado'])) {
    $filtros['estado'] = $_GET['estado'];
}
if (!empty($_GET['buscar'])) {
    $filtros['buscar'] = sanitizar($_GET['buscar']);
}

if (!esSuperAdmin() && obtenerSedeActual()) {
    $sede_unica = $sedeModel->obtenerPorId(obtenerSedeActual());
    $sedes = $sede_unica ? [$sede_unica] : [];
} else {
    $sedes = $sedeModel->obtenerTodas($filtros);
}

// Obtener usuarios para responsables (solo admin y jefes de almacén)
$usuarios_responsables = $userModel->obtenerTodos(['rol_id' => [ROL_ADMINISTRADOR, ROL_JEFE_ALMACEN]]);

$page_title = "Gestión de Sedes";
include '../layouts/header.php';
?>

<!-- Barra de acciones -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-building me-2"></i>
                    Sedes de la Empresa
                </h5>
                <?php if (esSuperAdmin()): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaSede">
                    <i class="bi bi-plus-circle me-2"></i>
                    Nueva Sede
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="buscar" class="form-control" placeholder="Nombre o código de sede..." value="<?php echo $_GET['buscar'] ?? ''; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="activa" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'activa') ? 'selected' : ''; ?>>Activa</option>
                        <option value="inactiva" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'inactiva') ? 'selected' : ''; ?>>Inactiva</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tabla de sedes -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Dirección</th>
                            <th>Responsable</th>
                            <th>Usuarios</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sedes)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No se encontraron sedes
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($sedes as $sede): ?>
                            <tr>
                                <td><code><?php echo $sede['codigo']; ?></code></td>
                                <td><strong><?php echo $sede['nombre']; ?></strong></td>
                                <td><?php echo $sede['direccion'] ?? '-'; ?></td>
                                <td><?php echo $sede['responsable_nombre'] ?? 'Sin asignar'; ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $sede['total_usuarios']; ?> usuarios</span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $sede['estado'] == 'activa' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($sede['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editarSede(<?php echo htmlspecialchars(json_encode($sede)); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if (esSuperAdmin()): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmarEliminar(<?php echo $sede['id']; ?>, '<?php echo addslashes($sede['nombre']); ?>')">
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

<!-- Modal Nueva Sede -->
<div class="modal fade" id="modalNuevaSede" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="accion" value="crear">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-building-add me-2"></i>Nueva Sede</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Código *</label>
                            <input type="text" name="codigo" class="form-control" required maxlength="20" placeholder="Ej: SC01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" class="form-control" required maxlength="100">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" class="form-control" maxlength="255">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control" maxlength="20">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" maxlength="100">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Responsable</label>
                            <select name="responsable_id" class="form-select">
                                <option value="">Sin asignar</option>
                                <?php foreach ($usuarios_responsables as $user): ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo $user['nombre_completo']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estado *</label>
                            <select name="estado" class="form-select" required>
                                <option value="activa">Activa</option>
                                <option value="inactiva">Inactiva</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Sede</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Sede -->
<div class="modal fade" id="modalEditarSede" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Editar Sede</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Código *</label>
                            <input type="text" name="codigo" id="edit_codigo" class="form-control" required maxlength="20">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" id="edit_nombre" class="form-control" required maxlength="100">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" id="edit_direccion" class="form-control" maxlength="255">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" id="edit_telefono" class="form-control" maxlength="20">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control" maxlength="100">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Responsable</label>
                            <select name="responsable_id" id="edit_responsable_id" class="form-select">
                                <option value="">Sin asignar</option>
                                <?php foreach ($usuarios_responsables as $user): ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo $user['nombre_completo']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estado *</label>
                            <select name="estado" id="edit_estado" class="form-select" required>
                                <option value="activa">Activa</option>
                                <option value="inactiva">Inactiva</option>
                            </select>
                        </div>
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

<!-- Modal Eliminar Sede -->
<div class="modal fade" id="modalEliminarSede" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formEliminarSede">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>¡Atención!</strong> Esta acción no se puede deshacer.
                    </div>
                    <p>¿Está seguro que desea eliminar la sede <strong id="delete_nombre"></strong>?</p>
                    <p class="text-muted">Solo se puede eliminar si no tiene usuarios asignados.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>Eliminar Sede
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarSede(sede) {
    document.getElementById('edit_id').value = sede.id;
    document.getElementById('edit_codigo').value = sede.codigo;
    document.getElementById('edit_nombre').value = sede.nombre;
    document.getElementById('edit_direccion').value = sede.direccion || '';
    document.getElementById('edit_telefono').value = sede.telefono || '';
    document.getElementById('edit_email').value = sede.email || '';
    document.getElementById('edit_responsable_id').value = sede.responsable_id || '';
    document.getElementById('edit_estado').value = sede.estado;
    
    const modal = new bootstrap.Modal(document.getElementById('modalEditarSede'));
    modal.show();
}

function confirmarEliminar(sedeId, nombre) {
    document.getElementById('delete_id').value = sedeId;
    document.getElementById('delete_nombre').textContent = nombre;
    
    const modal = new bootstrap.Modal(document.getElementById('modalEliminarSede'));
    modal.show();
}
</script>

<?php include '../layouts/footer.php'; ?>
