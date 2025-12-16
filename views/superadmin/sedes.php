<?php

/**
 * Gestión de Sedes - Superadministrador
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/Sede.php';
require_once '../../models/User.php';

if (!tieneRol(ROL_SUPERADMIN)) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();
$sedeModel = new Sede($db);
$userModel = new User($db);

$mensaje = '';
$tipo_mensaje = '';
$accion = $_GET['accion'] ?? 'listar';
$sede_id = $_GET['id'] ?? null;

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'];

    if ($accion === 'crear') {
        $sedeModel->nombre = sanitizar($_POST['nombre']);
        $sedeModel->codigo = sanitizar($_POST['codigo']);
        $sedeModel->direccion = sanitizar($_POST['direccion']);
        $sedeModel->telefono = sanitizar($_POST['telefono']);
        $sedeModel->email = sanitizar($_POST['email']);
        $sedeModel->responsable_id = !empty($_POST['responsable_id']) ? (int)$_POST['responsable_id'] : null;
        $sedeModel->estado = sanitizar($_POST['estado']);

        // Verificar que el código no exista
        if ($sedeModel->existeCodigo($sedeModel->codigo)) {
            $mensaje = 'El código de sede ya existe. Por favor, use uno diferente.';
            $tipo_mensaje = 'danger';
        } else {
            if ($sedeModel->crear()) {
                registrarActividad(
                    $_SESSION['usuario_id'],
                    'crear_sede',
                    'sedes',
                    "Sede creada: {$sedeModel->nombre} ({$sedeModel->codigo})"
                );
                $mensaje = 'Sede creada exitosamente.';
                $tipo_mensaje = 'success';
                $accion = 'listar';
            } else {
                $mensaje = 'Error al crear la sede.';
                $tipo_mensaje = 'danger';
            }
        }
    } elseif ($accion === 'actualizar') {
        $sedeModel->id = (int)$_POST['sede_id'];
        $sedeModel->nombre = sanitizar($_POST['nombre']);
        $sedeModel->codigo = sanitizar($_POST['codigo']);
        $sedeModel->direccion = sanitizar($_POST['direccion']);
        $sedeModel->telefono = sanitizar($_POST['telefono']);
        $sedeModel->email = sanitizar($_POST['email']);
        $sedeModel->responsable_id = !empty($_POST['responsable_id']) ? (int)$_POST['responsable_id'] : null;
        $sedeModel->estado = sanitizar($_POST['estado']);

        // Verificar que el código no exista (excluyendo la sede actual)
        if ($sedeModel->existeCodigo($sedeModel->codigo, $sedeModel->id)) {
            $mensaje = 'El código de sede ya existe. Por favor, use uno diferente.';
            $tipo_mensaje = 'danger';
        } else {
            if ($sedeModel->actualizar()) {
                registrarActividad(
                    $_SESSION['usuario_id'],
                    'actualizar_sede',
                    'sedes',
                    "Sede actualizada: {$sedeModel->nombre} ({$sedeModel->codigo})"
                );
                $mensaje = 'Sede actualizada exitosamente.';
                $tipo_mensaje = 'success';
                $accion = 'listar';
            } else {
                $mensaje = 'Error al actualizar la sede.';
                $tipo_mensaje = 'danger';
            }
        }
    }
}

// Eliminar sede
if ($accion === 'eliminar' && $sede_id) {
    if ($sedeModel->eliminar($sede_id)) {
        registrarActividad($_SESSION['usuario_id'], 'eliminar_sede', 'sedes', "Sede eliminada ID: $sede_id");
        $mensaje = 'Sede eliminada exitosamente.';
        $tipo_mensaje = 'success';
    } else {
        $mensaje = 'No se puede eliminar la sede. Tiene usuarios asignados.';
        $tipo_mensaje = 'danger';
    }
    $accion = 'listar';
}

// Obtener datos según la acción
$sedes = [];
$sede_actual = null;
$administradores = [];

if ($accion === 'listar' || $accion === 'crear') {
    $sedes = $sedeModel->obtenerTodas();
    $administradores = $userModel->obtenerAdministradoresDisponibles();
} elseif ($accion === 'editar' && $sede_id) {
    $sede_actual = $sedeModel->obtenerPorId($sede_id);
    $administradores = $userModel->obtenerAdministradoresDisponibles();
    if (!$sede_actual) {
        $mensaje = 'Sede no encontrada.';
        $tipo_mensaje = 'danger';
        $accion = 'listar';
        $sedes = $sedeModel->obtenerTodas();
    }
}

$page_title = "Gestión de Sedes";
include '../layouts/header.php';
?>

<?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
        <?php echo $mensaje; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($accion === 'listar'): ?>
    <!-- Vista de Lista -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5><i class="bi bi-building me-2"></i>Gestión de Sedes</h5>
                        <p class="text-muted mb-0">Administrar todas las sedes del sistema</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="?accion=crear" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nueva Sede
                        </a>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
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
                                <th>Sede</th>
                                <th>Código</th>
                                <th>Responsable</th>
                                <th>Contacto</th>
                                <th>Usuarios</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sedes as $sede): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?php echo $sede['nombre']; ?></strong>
                                            <?php if ($sede['direccion']): ?>
                                                <br><small class="text-muted"><?php echo $sede['direccion']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><code><?php echo $sede['codigo']; ?></code></td>
                                    <td>
                                        <?php if ($sede['responsable_nombre']): ?>
                                            <div>
                                                <strong><?php echo $sede['responsable_nombre']; ?></strong>
                                                <br><small class="text-muted">Administrador</small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Sin asignar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($sede['telefono'] || $sede['email']): ?>
                                            <?php if ($sede['telefono']): ?>
                                                <div><i class="bi bi-telephone me-1"></i><?php echo $sede['telefono']; ?></div>
                                            <?php endif; ?>
                                            <?php if ($sede['email']): ?>
                                                <div><i class="bi bi-envelope me-1"></i><?php echo $sede['email']; ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">No disponible</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $sede['total_usuarios']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $sede['estado'] === 'activa' ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo ucfirst($sede['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="sede_detalle.php?id=<?php echo $sede['id']; ?>"
                                                class="btn btn-outline-info" title="Ver Detalle">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-primary" onclick="abrirModalAsignar(<?php echo $sede['id']; ?>, '<?php echo htmlspecialchars($sede['nombre'], ENT_QUOTES); ?>', <?php echo $sede['responsable_id'] ?? 'null'; ?>)" title="Asignar Administrador"><i class="bi bi-person-check"></i></button>
                                            <a href="?accion=editar&id=<?php echo $sede['id']; ?>"
                                                class="btn btn-outline-warning" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button class="btn btn-outline-danger"
                                                onclick="abrirModalEliminar(<?php echo $sede['id']; ?>, '<?php echo htmlspecialchars($sede['nombre']); ?>')"
                                                title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
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

<?php elseif ($accion === 'crear' || $accion === 'editar'): ?>
    <!-- Formulario de Crear/Editar -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5><i class="bi bi-<?php echo $accion === 'crear' ? 'plus-circle' : 'pencil'; ?> me-2"></i>
                            <?php echo $accion === 'crear' ? 'Nueva Sede' : 'Editar Sede'; ?>
                        </h5>
                        <p class="text-muted mb-0">
                            <?php echo $accion === 'crear' ? 'Crear una nueva sede en el sistema' : 'Modificar información de la sede'; ?>
                        </p>
                    </div>
                    <a href="?accion=listar" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Volver a Lista
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="content-card">
                <form method="POST">
                    <input type="hidden" name="accion" value="<?php echo $accion === 'crear' ? 'crear' : 'actualizar'; ?>">
                    <?php if ($accion === 'editar'): ?>
                        <input type="hidden" name="sede_id" value="<?php echo $sede_actual['id']; ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre de la Sede *</label>
                            <input type="text" class="form-control" name="nombre"
                                value="<?php echo $sede_actual['nombre'] ?? ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Código *</label>
                            <input type="text" class="form-control" name="codigo"
                                value="<?php echo $sede_actual['codigo'] ?? ''; ?>"
                                style="text-transform: uppercase;" required>
                            <div class="form-text">Código único para identificar la sede</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Dirección</label>
                        <textarea class="form-control" name="direccion" rows="2"><?php echo $sede_actual['direccion'] ?? ''; ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="telefono"
                                value="<?php echo $sede_actual['telefono'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email"
                                value="<?php echo $sede_actual['email'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="estado" required>
                                <option value="activa" <?php echo (isset($sede_actual['estado']) && $sede_actual['estado'] === 'activa') ? 'selected' : ''; ?>>
                                    Activa
                                </option>
                                <option value="inactiva" <?php echo (isset($sede_actual['estado']) && $sede_actual['estado'] === 'inactiva') ? 'selected' : ''; ?>>
                                    Inactiva
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i>
                            <?php echo $accion === 'crear' ? 'Crear Sede' : 'Actualizar Sede'; ?>
                        </button>
                        <a href="?accion=listar" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-4">
            <div class="content-card">
                <h6><i class="bi bi-info-circle me-2"></i>Información</h6>
                <div class="alert alert-info">
                    <h6>Consejos:</h6>
                    <ul class="mb-0">
                        <li><strong>Código:</strong> Use un código corto y descriptivo (ej: LIM01, AQP02)</li>
                        <li><strong>Responsable:</strong> Asigne un administrador que gestionará esta sede</li>
                        <li><strong>Estado:</strong> Solo las sedes activas aparecerán en los formularios</li>
                    </ul>
                </div>

                <?php if ($accion === 'editar' && $sede_actual): ?>
                    <div class="alert alert-warning">
                        <h6>Información de la Sede:</h6>
                        <ul class="mb-0">
                            <li><strong>Creada:</strong> <?php echo isset($sede_actual['fecha_creacion']) ? formatearFecha($sede_actual['fecha_creacion']) : 'No disponible'; ?></li>
                            <li><strong>Usuarios:</strong> <?php echo $sede_actual['total_usuarios'] ?? 0; ?></li>
                            <?php if (isset($sede_actual['fecha_actualizacion']) && isset($sede_actual['fecha_creacion']) && $sede_actual['fecha_actualizacion'] !== $sede_actual['fecha_creacion']): ?>
                                <li><strong>Última actualización:</strong> <?php echo formatearFecha($sede_actual['fecha_actualizacion']); ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php endif; ?>

<div class="modal fade" id="modalEliminarSede" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle me-2"></i>Eliminar Sede
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" role="alert">
                    <h6 class="alert-heading">
                        <i class="bi bi-exclamation-circle me-2"></i>⚠️ ADVERTENCIA IMPORTANTE
                    </h6>
                    <p class="mb-0">
                        Al eliminar esta sede, se eliminarán <strong>TODOS</strong> los datos asociados a ella de forma permanente e irreversible.
                    </p>
                </div>

                <h6 class="mb-3">Sede a eliminar: <strong id="nombreSedeEliminar"></strong></h6>

                <div class="alert alert-warning">
                    <h6>Se eliminarán los siguientes datos:</h6>
                    <ul class="mb-0" id="listaEliminacion">
                        <li>Cargando información...</li>
                    </ul>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="confirmarEliminar" required>
                    <label class="form-check-label" for="confirmarEliminar">
                        Entiendo que esta acción es <strong>irreversible</strong> y eliminaré todos los datos de la sede
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-danger" id="btnConfirmarEliminar" disabled>
                    <i class="bi bi-trash me-2"></i>Eliminar Sede y Todos sus Datos
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let sedeIdAEliminar = null;

    function abrirModalEliminar(sedeId, nombreSede) {
        sedeIdAEliminar = sedeId;
        document.getElementById('nombreSedeEliminar').textContent = nombreSede;
        document.getElementById('confirmarEliminar').checked = false;
        document.getElementById('btnConfirmarEliminar').disabled = true;

        // Obtener datos de qué se va a eliminar
        fetch('<?php echo BASE_URL; ?>ajax/obtener_datos_sede.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'sede_id=' + sedeId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let html = '';
                    if (data.usuarios > 0) html += '<li><strong>' + data.usuarios + '</strong> usuario(s)</li>';
                    if (data.materiales > 0) html += '<li><strong>' + data.materiales + '</strong> material(es)</li>';
                    if (data.movimientos > 0) html += '<li><strong>' + data.movimientos + '</strong> movimiento(s) de inventario</li>';
                    if (data.solicitudes > 0) html += '<li><strong>' + data.solicitudes + '</strong> solicitud(es)</li>';
                    if (data.asignaciones > 0) html += '<li><strong>' + data.asignaciones + '</strong> asignación(es) a técnicos</li>';

                    if (html === '') {
                        html = '<li>No hay datos asociados a esta sede</li>';
                    }

                    document.getElementById('listaEliminacion').innerHTML = html;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('listaEliminacion').innerHTML = '<li>Error al obtener información</li>';
            });

        // Mostrar modal
        new bootstrap.Modal(document.getElementById('modalEliminarSede')).show();
    }

    // Habilitar botón cuando se confirma
    document.getElementById('confirmarEliminar').addEventListener('change', function() {
        document.getElementById('btnConfirmarEliminar').disabled = !this.checked;
    });

    // Eliminar cuando se confirma
    document.getElementById('btnConfirmarEliminar').addEventListener('click', function() {
        if (sedeIdAEliminar && document.getElementById('confirmarEliminar').checked) {
            window.location.href = '?accion=eliminar&id=' + sedeIdAEliminar;
        }
    });

    // Convertir código a mayúsculas automáticamente
    document.querySelector('input[name="codigo"]')?.addEventListener('input', function(e) {
        e.target.value = e.target.value.toUpperCase();
    });
</script>

<!-- Incluir modal de asignar administrador -->
<?php include 'modals/modal_asignar_admin.php'; ?>

<!-- Script para gestión de asignación de administradores -->
<script src="../../assets/js/asignar_admin.js"></script>

<?php include '../layouts/footer.php'; ?>