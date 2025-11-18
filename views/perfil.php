<?php
/**
 * Página de Perfil de Usuario
 */

require_once '../config/constants.php';
require_once '../config/functions.php';
require_once '../config/database.php';
require_once '../models/User.php';

if (!estaAutenticado()) {
    redirigir('auth/login.php');
}

$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);

// Obtener datos del usuario actual
$usuario = $userModel->obtenerPorId($_SESSION['usuario_id']);

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'actualizar_perfil') {
        $userModel->id = $_SESSION['usuario_id'];
        $userModel->nombre_completo = sanitizar($_POST['nombre_completo']);
        $userModel->email = sanitizar($_POST['email']);
        $userModel->telefono = sanitizar($_POST['telefono']);
        $userModel->rol_id = $usuario['rol_id']; // Mantener el rol actual
        $userModel->estado = $usuario['estado']; // Mantener el estado actual
        
        if ($userModel->actualizar()) {
            // Actualizar datos en sesión
            $_SESSION['nombre_completo'] = $userModel->nombre_completo;
            
            registrarActividad($_SESSION['usuario_id'], 'actualizar', 'perfil', 'Perfil actualizado');
            setMensaje('success', 'Perfil actualizado exitosamente');
            redirigir('views/perfil.php');
        } else {
            setMensaje('danger', 'Error al actualizar el perfil');
        }
    }
    
    if ($accion === 'cambiar_password') {
        $password_actual = $_POST['password_actual'];
        $nueva_password = $_POST['nueva_password'];
        $confirmar_password = $_POST['confirmar_password'];
        
        // Verificar contraseña actual
        if (!password_verify($password_actual, $usuario['password'])) {
            setMensaje('danger', 'La contraseña actual es incorrecta');
        } elseif ($nueva_password !== $confirmar_password) {
            setMensaje('danger', 'Las contraseñas nuevas no coinciden');
        } elseif (strlen($nueva_password) < 6) {
            setMensaje('danger', 'La nueva contraseña debe tener al menos 6 caracteres');
        } else {
            if ($userModel->cambiarPassword($_SESSION['usuario_id'], $nueva_password)) {
                registrarActividad($_SESSION['usuario_id'], 'cambiar_password', 'perfil', 'Contraseña cambiada');
                setMensaje('success', 'Contraseña cambiada exitosamente');
                redirigir('views/perfil.php');
            } else {
                setMensaje('danger', 'Error al cambiar la contraseña');
            }
        }
    }

    if ($accion === 'subir_imagen') {
        if (isset($_FILES['imagen_perfil']) && $_FILES['imagen_perfil']['error'] !== UPLOAD_ERR_NO_FILE) {
            $resultado = $userModel->subirImagenPerfil($_SESSION['usuario_id'], $_FILES['imagen_perfil']);
            
            if ($resultado['exito']) {
                registrarActividad($_SESSION['usuario_id'], 'subir_imagen', 'perfil', 'Imagen de perfil actualizada');
                setMensaje('success', $resultado['mensaje']);
            } else {
                setMensaje('danger', $resultado['mensaje']);
            }
            redirigir('views/perfil.php');
        }
    }

    if ($accion === 'eliminar_imagen') {
        if ($userModel->eliminarImagenPerfil($_SESSION['usuario_id'])) {
            registrarActividad($_SESSION['usuario_id'], 'eliminar_imagen', 'perfil', 'Imagen de perfil eliminada');
            setMensaje('success', 'Imagen de perfil eliminada correctamente');
        } else {
            setMensaje('danger', 'Error al eliminar la imagen de perfil');
        }
        redirigir('views/perfil.php');
    }
}

$page_title = "Mi Perfil";
include 'layouts/header.php';

// Obtener imagen de perfil para mostrar en el header
$url_imagen_perfil = $userModel->obtenerUrlImagenPerfil($usuario['id']);
?>

<style>
    .perfil-header-logo {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        object-fit: cover;
        border: 2px solid #6366f1;
    }
</style>

<!-- Información del Perfil -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="content-card text-center">
            <!-- Avatar/Imagen de Perfil -->
            <div class="position-relative d-inline-block mb-3">
                <?php 
                $url_imagen = $userModel->obtenerUrlImagenPerfil($usuario['id']);
                if ($url_imagen && file_exists('../' . $url_imagen)):
                ?>
                    <img src="<?php echo BASE_URL . $url_imagen; ?>" alt="Perfil" 
                         class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover; border: 4px solid #6366f1;">
                <?php else: ?>
                    <div class="user-avatar mx-auto" style="width: 120px; height: 120px; font-size: 48px; display: flex; align-items: center; justify-content: center;">
                        <?php echo strtoupper(substr($usuario['nombre_completo'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <button type="button" class="btn btn-sm btn-primary position-absolute bottom-0 end-0" 
                        data-bs-toggle="modal" data-bs-target="#modalSubirImagen"
                        style="border-radius: 50%; width: 40px; height: 40px; padding: 0;">
                    <i class="bi bi-camera-fill"></i>
                </button>
            </div>

            <h5><?php echo $usuario['nombre_completo']; ?></h5>
            <p class="text-muted"><?php echo $usuario['rol_nombre']; ?></p>
            <div class="row text-center mt-4">
                <div class="col-6">
                    <small class="text-muted">Usuario</small>
                    <div class="fw-bold"><?php echo $usuario['username']; ?></div>
                </div>
                <div class="col-6">
                    <small class="text-muted">Estado</small>
                    <div>
                        <span class="badge bg-<?php echo getBadgeEstado($usuario['estado']); ?>">
                            <?php echo ucfirst($usuario['estado']); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="row text-center mt-3">
                <div class="col-12">
                    <small class="text-muted">Último acceso</small>
                    <div class="fw-bold">
                        <?php echo $usuario['ultimo_acceso'] ? formatearFechaHora($usuario['ultimo_acceso']) : 'Nunca'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <!-- Actualizar Información Personal -->
        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-person me-2"></i>Información Personal</h5>
            </div>
            <form method="POST">
                <input type="hidden" name="accion" value="actualizar_perfil">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre Completo *</label>
                        <input type="text" name="nombre_completo" class="form-control" 
                               value="<?php echo $usuario['nombre_completo']; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo $usuario['email']; ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control" 
                               value="<?php echo $usuario['telefono']; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fecha de Registro</label>
                        <input type="text" class="form-control" 
                               value="<?php echo formatearFechaHora($usuario['created_at']); ?>" readonly>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Actualizar Información
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Cambiar Contraseña -->
<div class="row">
    <div class="col-md-8 offset-md-4">
        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-shield-lock me-2"></i>Cambiar Contraseña</h5>
            </div>
            <form method="POST">
                <input type="hidden" name="accion" value="cambiar_password">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Contraseña Actual *</label>
                        <input type="password" name="password_actual" class="form-control" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nueva Contraseña *</label>
                        <input type="password" name="nueva_password" class="form-control" 
                               minlength="6" required>
                        <small class="text-muted">Mínimo 6 caracteres</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Confirmar Nueva Contraseña *</label>
                        <input type="password" name="confirmar_password" class="form-control" 
                               minlength="6" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-key me-2"></i>Cambiar Contraseña
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Subir Imagen de Perfil -->
<div class="modal fade" id="modalSubirImagen" tabindex="-1" aria-labelledby="modalSubirImagenLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSubirImagenLabel">
                    <i class="bi bi-image me-2"></i>Cambiar Imagen de Perfil
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="subir_imagen">
                    
                    <div class="mb-3">
                        <label class="form-label">Seleccionar Imagen *</label>
                        <input type="file" name="imagen_perfil" class="form-control" 
                               accept="image/jpeg,image/png,image/gif" required
                               id="inputImagen">
                        <small class="text-muted d-block mt-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Formatos permitidos: JPG, PNG, GIF | Tamaño máximo: 5MB
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Vista Previa:</label>
                        <div id="previewImagen" class="text-center">
                            <img id="imgPreview" src="" alt="Vista previa" 
                                 style="max-width: 100%; max-height: 300px; display: none; border-radius: 8px;">
                            <p class="text-muted" id="textoPreview">No hay imagen seleccionada</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-2"></i>Subir Imagen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Eliminar Imagen de Perfil -->
<?php if ($url_imagen && file_exists('../' . $url_imagen)): ?>
<div class="modal fade" id="modalEliminarImagen" tabindex="-1" aria-labelledby="modalEliminarImagenLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEliminarImagenLabel">
                    <i class="bi bi-trash me-2"></i>Eliminar Imagen de Perfil
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="eliminar_imagen">
                    <p class="text-muted">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        ¿Está seguro de que desea eliminar su imagen de perfil? 
                        Se mostrará el avatar con su inicial.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>Eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Vista previa de imagen
document.getElementById('inputImagen').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('imgPreview');
    const textoPreview = document.getElementById('textoPreview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            preview.src = event.target.result;
            preview.style.display = 'block';
            textoPreview.style.display = 'none';
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
        textoPreview.style.display = 'block';
    }
});
</script>

<?php include 'layouts/footer.php'; ?>
