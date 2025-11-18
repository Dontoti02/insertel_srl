<?php
/**
 * Crear Administrador - Superadministrador
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

$mensaje = '';
$tipo_mensaje = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = sanitizar($_POST['usuario']);
    $nombre_completo = sanitizar($_POST['nombre_completo']);
    $email = sanitizar($_POST['email']);
    $telefono = sanitizar($_POST['telefono']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $sede_id = !empty($_POST['sede_id']) ? (int)$_POST['sede_id'] : null;
    
    // Validaciones
    $errores = [];
    
    if (empty($usuario)) {
        $errores[] = 'El usuario es requerido';
    } elseif ($userModel->existeUsername($usuario)) {
        $errores[] = 'El usuario ya existe';
    }
    
    if (empty($nombre_completo)) {
        $errores[] = 'El nombre completo es requerido';
    }
    
    if (empty($email)) {
        $errores[] = 'El email es requerido';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El email no es válido';
    } elseif ($userModel->existeEmail($email)) {
        $errores[] = 'El email ya está registrado';
    }
    
    if (empty($password)) {
        $errores[] = 'La contraseña es requerida';
    } elseif (strlen($password) < 6) {
        $errores[] = 'La contraseña debe tener al menos 6 caracteres';
    }
    
    if ($password !== $confirm_password) {
        $errores[] = 'Las contraseñas no coinciden';
    }
    
    if (empty($errores)) {
        // Crear el administrador
        $userModel->username = $usuario;
        $userModel->nombre_completo = $nombre_completo;
        $userModel->email = $email;
        $userModel->telefono = $telefono;
        $userModel->password = password_hash($password, PASSWORD_DEFAULT);
        $userModel->rol_id = ROL_ADMINISTRADOR;
        $userModel->sede_id = $sede_id;
        $userModel->estado = 'activo';
        
        if ($userModel->crearConSede()) {
            registrarActividad($_SESSION['usuario_id'], 'crear_administrador', 'usuarios', 
                "Administrador creado: $nombre_completo ($usuario)" . ($sede_id ? " - Sede ID: $sede_id" : ""));
            
            $mensaje = 'Administrador creado exitosamente.';
            $tipo_mensaje = 'success';
            
            // Limpiar formulario
            $_POST = [];
        } else {
            $mensaje = 'Error al crear el administrador.';
            $tipo_mensaje = 'danger';
        }
    } else {
        $mensaje = implode('<br>', $errores);
        $tipo_mensaje = 'danger';
    }
}

// Obtener sedes disponibles
$sedes_disponibles = $sedeModel->obtenerActivas();

$page_title = "Crear Administrador";
include '../layouts/header.php';
?>

<?php if ($mensaje): ?>
<div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
    <?php echo $mensaje; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5><i class="bi bi-person-plus me-2"></i>Crear Nuevo Administrador</h5>
                    <p class="text-muted mb-0">Crear un administrador para gestionar una sede específica</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="usuarios_globales.php" class="btn btn-outline-info">
                        <i class="bi bi-people"></i> Ver Usuarios
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
    <div class="col-md-8">
        <div class="content-card">
            <form method="POST" id="formCrearAdmin">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Usuario *</label>
                        <input type="text" class="form-control" name="usuario" 
                               value="<?php echo $_POST['usuario'] ?? ''; ?>" 
                               required autocomplete="username">
                        <div class="form-text">Nombre de usuario único para iniciar sesión</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control" name="nombre_completo" 
                               value="<?php echo $_POST['nombre_completo'] ?? ''; ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" 
                               value="<?php echo $_POST['email'] ?? ''; ?>" 
                               required autocomplete="email">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" class="form-control" name="telefono" 
                               value="<?php echo $_POST['telefono'] ?? ''; ?>" 
                               autocomplete="tel">
                    </div>
                </div>
                
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contraseña *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="password" 
                                   id="password" required autocomplete="new-password" minlength="6">
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="togglePassword('password')">
                                <i class="bi bi-eye" id="password-icon"></i>
                            </button>
                        </div>
                        <div class="form-text">Mínimo 6 caracteres</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Confirmar Contraseña *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="confirm_password" 
                                   id="confirm_password" required autocomplete="new-password" minlength="6">
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="togglePassword('confirm_password')">
                                <i class="bi bi-eye" id="confirm_password-icon"></i>
                            </button>
                        </div>
                        <div id="password-match" class="form-text"></div>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <h6><i class="bi bi-info-circle me-2"></i>Información del Rol</h6>
                    <p class="mb-0">
                        El administrador podrá gestionar usuarios, materiales, solicitudes y reportes 
                        de la sede asignada. Si no se asigna una sede específica, tendrá acceso a 
                        todas las sedes del sistema.
                    </p>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="btnCrear">
                        <i class="bi bi-check-circle"></i> Crear Administrador
                    </button>
                    <button type="reset" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Limpiar
                    </button>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="content-card">
            <h6><i class="bi bi-shield-check me-2"></i>Permisos del Administrador</h6>
            <div class="list-group list-group-flush">
                <div class="list-group-item d-flex align-items-center">
                    <i class="bi bi-people text-primary me-2"></i>
                    <span>Gestionar usuarios de la sede</span>
                </div>
                <div class="list-group-item d-flex align-items-center">
                    <i class="bi bi-box text-success me-2"></i>
                    <span>Administrar materiales</span>
                </div>
                <div class="list-group-item d-flex align-items-center">
                    <i class="bi bi-arrow-left-right text-info me-2"></i>
                    <span>Gestionar movimientos</span>
                </div>
                <div class="list-group-item d-flex align-items-center">
                    <i class="bi bi-clipboard-check text-warning me-2"></i>
                    <span>Aprobar solicitudes</span>
                </div>
                <div class="list-group-item d-flex align-items-center">
                    <i class="bi bi-graph-up text-danger me-2"></i>
                    <span>Ver reportes y estadísticas</span>
                </div>
            </div>
        </div>
        
        <div class="content-card mt-3">
            <h6><i class="bi bi-lightbulb me-2"></i>Consejos</h6>
            <div class="alert alert-light">
                <ul class="mb-0">
                    <li><strong>Usuario único:</strong> Debe ser diferente a otros usuarios</li>
                    <li><strong>Email válido:</strong> Se usará para notificaciones</li>
                    <li><strong>Contraseña segura:</strong> Combine letras, números y símbolos</li>
                    <li><strong>Sede específica:</strong> Recomendado para mejor organización</li>
                </ul>
            </div>
        </div>
        
        <?php if (!empty($sedes_disponibles)): ?>
        <div class="content-card mt-3">
            <h6><i class="bi bi-building me-2"></i>Sedes Disponibles</h6>
            <div class="list-group list-group-flush">
                <?php foreach ($sedes_disponibles as $sede): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?php echo $sede['nombre']; ?></strong>
                        <br><small class="text-muted"><?php echo $sede['codigo']; ?></small>
                    </div>
                    <span class="badge bg-success">Activa</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '-icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

// Validación en tiempo real de contraseñas
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    const matchDiv = document.getElementById('password-match');
    const btnCrear = document.getElementById('btnCrear');
    
    if (confirmPassword.length > 0) {
        if (password === confirmPassword) {
            matchDiv.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Las contraseñas coinciden</span>';
            matchDiv.className = 'form-text';
            btnCrear.disabled = false;
        } else {
            matchDiv.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Las contraseñas no coinciden</span>';
            matchDiv.className = 'form-text';
            btnCrear.disabled = true;
        }
    } else {
        matchDiv.innerHTML = '';
        btnCrear.disabled = false;
    }
});

// Validación de usuario único
document.querySelector('input[name="usuario"]').addEventListener('blur', function() {
    const usuario = this.value.trim();
    if (usuario.length > 0) {
        // Aquí podrías agregar una validación AJAX para verificar si el usuario existe
        // Por simplicidad, solo mostramos el mensaje en el servidor
    }
});

// Limpiar formulario
document.querySelector('button[type="reset"]').addEventListener('click', function() {
    document.getElementById('password-match').innerHTML = '';
    document.getElementById('btnCrear').disabled = false;
});
</script>

<?php include '../layouts/footer.php'; ?>
