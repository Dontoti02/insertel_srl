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

// Verificar mensajes de sesión (Flash Messages)
$mensaje_sesion = getMensaje();
if ($mensaje_sesion) {
    $mensaje = $mensaje_sesion['texto'];
    $tipo_mensaje = $mensaje_sesion['tipo'];
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = sanitizar($_POST['usuario']);
    $nombre_completo = sanitizar($_POST['nombre_completo']);
    $email = sanitizar($_POST['email']);
    $telefono = sanitizar($_POST['telefono']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validaciones
    $errores = [];

    // Validar Usuario Duplicado
    if (empty($usuario)) {
        $errores[] = 'El usuario es requerido';
    } elseif ($userModel->existeUsername($usuario)) {
        $errores[] = 'El nombre de usuario ya está en uso por otro administrador.';
    }

    if (empty($nombre_completo)) {
        $errores[] = 'El nombre completo es requerido';
    }

    // Validar Email Duplicado
    if (empty($email)) {
        $errores[] = 'El email es requerido';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El formato del email no es válido';
    } elseif ($userModel->existeEmail($email)) {
        $errores[] = 'El correo electrónico ya está registrado en el sistema.';
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
        // Crear el administrador sin sede (acceso global)
        $userModel->username = $usuario;
        $userModel->nombre_completo = $nombre_completo;
        $userModel->email = $email;
        $userModel->telefono = $telefono;
        $userModel->password = $password;
        $userModel->rol_id = ROL_ADMINISTRADOR;
        $userModel->sede_id = null; // Sin sede asignada
        $userModel->estado = 'activo';

        if ($userModel->crearConSede()) {
            registrarActividad(
                $_SESSION['usuario_id'],
                'crear_administrador',
                'usuarios',
                "Administrador creado: $nombre_completo ($usuario)"
            );

            // Crear mensaje de éxito detallado
            $mensaje_exito = "✓ <strong>Administrador creado exitosamente</strong><br><br>";
            $mensaje_exito .= "📋 <strong>Detalles:</strong><br>";
            $mensaje_exito .= "• <strong>Nombre:</strong> $nombre_completo<br>";
            $mensaje_exito .= "• <strong>Usuario:</strong> $usuario<br>";
            $mensaje_exito .= "• <strong>Email:</strong> $email<br>";
            $mensaje_exito .= "• <strong>Acceso:</strong> Global (todas las sedes)<br>";
            $mensaje_exito .= "<br>El administrador ya puede iniciar sesión en el sistema.";

            // Guardar mensaje de éxito en sesión y redirigir
            setMensaje('success', $mensaje_exito);
            header('Location: crear_admin.php');
            exit;
        } else {
            $mensaje = 'Ocurrió un error interno al intentar crear el administrador.';
            $tipo_mensaje = 'error';
        }
    } else {
        // Mostrar errores de validación
        $mensaje = implode('<br>', $errores);
        $tipo_mensaje = 'error';
    }
}

$page_title = "Crear Administrador";
include '../layouts/header.php';
?>

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5><i class="bi bi-person-plus me-2"></i>Crear Nuevo Administrador</h5>
                    <p class="text-muted mb-0">Crear un administrador con acceso global al sistema</p>
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
                            value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>"
                            required autocomplete="username">
                        <div class="form-text">Nombre de usuario único para iniciar sesión</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control" name="nombre_completo"
                            value="<?php echo htmlspecialchars($_POST['nombre_completo'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            required autocomplete="email">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" class="form-control" name="telefono"
                            value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>"
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
                        El administrador tendrá acceso global a todas las sedes del sistema y podrá
                        gestionar usuarios, materiales, solicitudes y reportes.
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
                    <span>Gestionar usuarios del sistema</span>
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
                    <li><strong>Acceso global:</strong> Podrá administrar todas las sedes</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Mostrar alertas con SweetAlert2
    <?php if ($mensaje): ?>
        Swal.fire({
            title: '<?php echo ($tipo_mensaje === 'success') ? '¡Éxito!' : '¡Atención!'; ?>',
            html: '<?php echo $mensaje; ?>',
            icon: '<?php echo ($tipo_mensaje === 'success') ? 'success' : 'error'; ?>',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#1a2cff',
            width: '600px'
        });
    <?php endif; ?>

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

    // Limpiar formulario
    document.querySelector('button[type="reset"]').addEventListener('click', function() {
        document.getElementById('password-match').innerHTML = '';
        document.getElementById('btnCrear').disabled = false;
    });
</script>

<?php include '../layouts/footer.php'; ?>