<?php
/**
 * Gestión de Usuarios Globales - Superadministrador
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/User.php';
require_once '../../models/Sede.php';

if (!tieneRol(ROL_SUPERADMIN)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);
$sedeModel = new Sede($db);

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'crear') {
        $userModel->username = sanitizar($_POST['username']);
        $userModel->password = $_POST['password'];
        $userModel->nombre_completo = sanitizar($_POST['nombre_completo']);
        $userModel->email = sanitizar($_POST['email']);
        $userModel->telefono = sanitizar($_POST['telefono']);
        $userModel->rol_id = (int)$_POST['rol_id'];
        $userModel->sede_id = (int)$_POST['sede_id'];
        $userModel->estado = $_POST['estado'];
        
        if ($userModel->existeUsername($userModel->username)) {
            setMensaje('danger', 'El nombre de usuario ya existe');
        } else {
            if ($userModel->crearConSede()) {
                registrarActividad($_SESSION['usuario_id'], 'crear_global', 'usuarios', "Usuario creado: {$userModel->username}");
                setMensaje('success', 'Usuario creado exitosamente');
                redirigir('views/superadmin/usuarios_globales.php');
            } else {
                setMensaje('danger', 'Error al crear el usuario');
            }
        }
    }
    
    if ($accion === 'editar') {
        $userModel->id = (int)$_POST['id'];
        $userModel->nombre_completo = sanitizar($_POST['nombre_completo']);
        $userModel->email = sanitizar($_POST['email']);
        $userModel->telefono = sanitizar($_POST['telefono']);
        $userModel->rol_id = (int)$_POST['rol_id'];
        $userModel->sede_id = (int)$_POST['sede_id'];
        $userModel->estado = $_POST['estado'];
        
        // No permitir cambiar el rol de un SUPERADMIN
        $usuario_actual = $userModel->obtenerPorId($userModel->id);
        if ($usuario_actual && $usuario_actual['rol_id'] == ROL_SUPERADMIN) {
            setMensaje('danger', 'No se puede modificar el rol del Superadministrador');
            redirigir('views/superadmin/usuarios_globales.php');
        }
        
        if ($userModel->id == $_SESSION['usuario_id'] && $userModel->estado == 'inactivo') {
            setMensaje('danger', 'No puedes desactivar tu propia cuenta');
        } else {
            if ($userModel->actualizar()) {
                $userModel->cambiarSede($userModel->id, $userModel->sede_id);
                registrarActividad($_SESSION['usuario_id'], 'editar_global', 'usuarios', "Usuario actualizado ID: {$userModel->id}");
                setMensaje('success', 'Usuario actualizado exitosamente');
                redirigir('views/superadmin/usuarios_globales.php');
            } else {
                setMensaje('danger', 'Error al actualizar el usuario');
            }
        }
    }
    
    if ($accion === 'eliminar') {
        $user_id = (int)$_POST['id'];
        
        if ($user_id == $_SESSION['usuario_id']) {
            setMensaje('danger', 'No puedes eliminar tu propia cuenta');
        } else {
            // No permitir eliminar un SUPERADMIN
            $usuario_eliminar = $userModel->obtenerPorId($user_id);
            if ($usuario_eliminar && $usuario_eliminar['rol_id'] == ROL_SUPERADMIN) {
                setMensaje('danger', 'No se puede eliminar un Superadministrador');
                redirigir('views/superadmin/usuarios_globales.php');
            }
            
            if ($usuario_eliminar && $userModel->eliminar($user_id)) {
                registrarActividad($_SESSION['usuario_id'], 'eliminar_global', 'usuarios', "Usuario eliminado: {$usuario_eliminar['username']}");
                setMensaje('success', 'Usuario eliminado exitosamente');
                redirigir('views/superadmin/usuarios_globales.php');
            } else {
                setMensaje('danger', 'Error al eliminar el usuario');
            }
        }
    }
}

// Obtener filtros
$filtros = [];
if (!empty($_GET['rol_id'])) {
    $filtros['rol_id'] = (int)$_GET['rol_id'];
}
if (!empty($_GET['sede_id'])) {
    $filtros['sede_id'] = (int)$_GET['sede_id'];
}
if (!empty($_GET['estado'])) {
    $filtros['estado'] = $_GET['estado'];
}
if (!empty($_GET['buscar'])) {
    $filtros['buscar'] = sanitizar($_GET['buscar']);
}

$usuarios = $userModel->obtenerTodosGlobales($filtros);

// Obtener datos para formularios
$roles = $db->query("SELECT * FROM roles ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$sedes = $sedeModel->obtenerTodas();

$page_title = "Gestión de Usuarios Globales";
include '../layouts/header.php';
?>

<!-- Barra de acciones -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-people me-2"></i>Usuarios Globales del Sistema</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">
                    <i class="bi bi-plus-circle me-2"></i> Nuevo Usuario
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="buscar" class="form-control" placeholder="Nombre, usuario o email..." value="<?php echo $_GET['buscar'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sede</label>
                    <select name="sede_id" class="form-select">
                        <option value="">Todas las sedes</option>
                        <?php foreach ($sedes as $sede): ?>
                        <option value="<?php echo $sede['id']; ?>" <?php echo (isset($_GET['sede_id']) && $_GET['sede_id'] == $sede['id']) ? 'selected' : ''; ?>>
                            <?php echo $sede['nombre']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Rol</label>
                    <select name="rol_id" class="form-select">
                        <option value="">Todos los roles</option>
                        <?php foreach ($roles as $rol): ?>
                        <option value="<?php echo $rol['id']; ?>" <?php echo (isset($_GET['rol_id']) && $_GET['rol_id'] == $rol['id']) ? 'selected' : ''; ?>>
                            <?php echo $rol['nombre']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="activo" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'activo') ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactivo" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid gap-2"><button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filtrar</button></div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tabla de usuarios -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Nombre Completo</th>
                            <th>Sede</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Último Acceso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No se encontraron usuarios</td></tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><strong><?php echo $usuario['username']; ?></strong></td>
                                <td><?php echo $usuario['nombre_completo']; ?></td>
                                <td><?php echo $usuario['sede_nombre'] ?? '<span class="text-muted">No asignada</span>'; ?></td>
                                <td><span class="badge bg-primary"><?php echo $usuario['rol_nombre']; ?></span></td>
                                <td><span class="badge bg-<?php echo getBadgeEstado($usuario['estado']); ?>"><?php echo ucfirst($usuario['estado']); ?></span></td>
                                <td><?php echo $usuario['ultimo_acceso'] ? formatearFechaHora($usuario['ultimo_acceso']) : 'Nunca'; ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($usuario)); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmarEliminar(<?php echo $usuario['id']; ?>, '<?php echo addslashes($usuario['username']); ?>')">
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

<!-- Modal Nuevo Usuario -->
<div class="modal fade" id="modalNuevoUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="accion" value="crear">
                <div class="modal-header"><h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Nuevo Usuario</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Usuario *</label><input type="text" name="username" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Contraseña *</label><input type="password" name="password" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Nombre Completo *</label><input type="text" name="nombre_completo" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Teléfono</label><input type="text" name="telefono" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Sede *</label><select name="sede_id" class="form-select" required><option value="">Seleccione...</option><?php foreach ($sedes as $sede): ?><option value="<?php echo $sede['id']; ?>"><?php echo $sede['nombre']; ?></option><?php endforeach; ?></select></div>
                    <div class="mb-3"><label class="form-label">Rol *</label><select name="rol_id" class="form-select" required><option value="">Seleccione...</option><?php foreach ($roles as $rol): ?><option value="<?php echo $rol['id']; ?>"><?php echo $rol['nombre']; ?></option><?php endforeach; ?></select></div>
                    <div class="mb-3"><label class="form-label">Estado *</label><select name="estado" class="form-select" required><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Crear Usuario</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Usuario -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Editar Usuario</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nombre Completo *</label><input type="text" name="nombre_completo" id="edit_nombre_completo" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" id="edit_email" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Teléfono</label><input type="text" name="telefono" id="edit_telefono" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Sede *</label><select name="sede_id" id="edit_sede_id" class="form-select" required><?php foreach ($sedes as $sede): ?><option value="<?php echo $sede['id']; ?>"><?php echo $sede['nombre']; ?></option><?php endforeach; ?></select></div>
                    <div class="mb-3"><label class="form-label">Rol *</label><select name="rol_id" id="edit_rol_id" class="form-select" required><?php foreach ($roles as $rol): ?><option value="<?php echo $rol['id']; ?>"><?php echo $rol['nombre']; ?></option><?php endforeach; ?></select></div>
                    <div class="mb-3"><label class="form-label">Estado *</label><select name="estado" id="edit_estado" class="form-select" required><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar Cambios</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Eliminar Usuario -->
<div class="modal fade" id="modalEliminarUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formEliminarUsuario">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-header"><h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Confirmar Eliminación</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i><strong>¡Atención!</strong> Esta acción no se puede deshacer.</div>
                    <p>¿Está seguro que desea eliminar el usuario <strong id="delete_username"></strong>?</p>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-danger"><i class="bi bi-trash me-2"></i>Eliminar</button></div>
            </form>
        </div>
    </div>
</div>

<script>
function editarUsuario(usuario) {
    document.getElementById('edit_id').value = usuario.id;
    document.getElementById('edit_nombre_completo').value = usuario.nombre_completo;
    document.getElementById('edit_email').value = usuario.email || '';
    document.getElementById('edit_telefono').value = usuario.telefono || '';
    document.getElementById('edit_sede_id').value = usuario.sede_id;
    document.getElementById('edit_rol_id').value = usuario.rol_id;
    document.getElementById('edit_estado').value = usuario.estado;
    
    const modal = new bootstrap.Modal(document.getElementById('modalEditarUsuario'));
    modal.show();
}

function confirmarEliminar(userId, username) {
    document.getElementById('delete_id').value = userId;
    document.getElementById('delete_username').textContent = username;
    
    const modal = new bootstrap.Modal(document.getElementById('modalEliminarUsuario'));
    modal.show();
}
</script>

<?php include '../layouts/footer.php'; ?>
