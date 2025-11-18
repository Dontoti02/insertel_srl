<?php
/**
 * Página de registro de administrador
 */

require_once '../config/constants.php';
require_once '../config/functions.php';
require_once '../config/database.php';
require_once '../models/User.php';

$error = '';
$success = '';

// Procesar el registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizar($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $nombre_completo = sanitizar($_POST['nombre_completo'] ?? '');
    $email = sanitizar($_POST['email'] ?? '');
    $telefono = sanitizar($_POST['telefono'] ?? '');

    // Validaciones
    if (empty($username) || empty($password) || empty($nombre_completo) || empty($email)) {
        $error = 'Por favor complete todos los campos obligatorios';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif (!validarEmail($email)) {
        $error = 'El email no es válido';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        $userModel = new User($db);

        // Verificar si el usuario ya existe
        if ($userModel->existeUsername($username)) {
            $error = 'El nombre de usuario ya está en uso';
        } else {
            // Crear el usuario administrador
            $userModel->username = $username;
            $userModel->password = $password;
            $userModel->nombre_completo = $nombre_completo;
            $userModel->email = $email;
            $userModel->telefono = $telefono;
            $userModel->rol_id = ROL_ADMINISTRADOR; // Rol de administrador
            $userModel->estado = 'activo';

            $user_id = $userModel->crear();

            if ($user_id) {
                registrarActividad($user_id, 'registro', 'autenticacion', 'Nuevo administrador registrado');
                $success = 'Cuenta de administrador creada exitosamente. Ya puedes iniciar sesión.';
                
                // Limpiar campos
                $username = $nombre_completo = $email = $telefono = '';
            } else {
                $error = 'Error al crear la cuenta. Intente nuevamente.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Administrador - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --color-primary: #0d3b66;
            --color-secondary: #3a86ff;
            --color-dark: #1a1a2e;
            --color-light: #f8f9fa;
        }

        body {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px 0;
        }

        .register-container {
            max-width: 550px;
            width: 100%;
            padding: 20px;
        }

        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .register-header {
            background: var(--color-primary);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .register-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin: 10px 0 5px 0;
        }

        .register-header p {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }

        .register-body {
            padding: 40px 30px;
        }

        .form-label {
            font-weight: 500;
            color: var(--color-dark);
            margin-bottom: 8px;
        }

        .form-control {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--color-secondary);
            box-shadow: 0 0 0 0.25rem rgba(58, 134, 255, 0.15);
        }

        .btn-register {
            background: var(--color-secondary);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }

        .btn-register:hover {
            background: var(--color-primary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .alert {
            border-radius: 8px;
            border: none;
        }

        .logo-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .input-group-text {
            background: transparent;
            border: 2px solid #e0e0e0;
            border-right: none;
        }

        .input-group .form-control {
            border-left: none;
        }

        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control {
            border-color: var(--color-secondary);
        }

        .required {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <i class="bi bi-person-plus-fill logo-icon"></i>
                <h1>Registro de Administrador</h1>
                <p><?php echo APP_NAME; ?></p>
            </div>
            <div class="register-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php echo $success; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="login.php" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            Ir a Iniciar Sesión
                        </a>
                    </div>
                <?php else: ?>

                <form method="POST" action="" id="registerForm">
                    <div class="mb-3">
                        <label for="nombre_completo" class="form-label">
                            <i class="bi bi-person-circle me-1"></i> Nombre Completo <span class="required">*</span>
                        </label>
                        <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" 
                               placeholder="Ej: Juan Pérez López" required autofocus
                               value="<?php echo htmlspecialchars($nombre_completo ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="bi bi-person-badge me-1"></i> Nombre de Usuario <span class="required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-at"></i>
                            </span>
                            <input type="text" class="form-control" id="username" name="username" 
                                   placeholder="usuario" required
                                   value="<?php echo htmlspecialchars($username ?? ''); ?>">
                        </div>
                        <small class="text-muted">Sin espacios ni caracteres especiales</small>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope me-1"></i> Email <span class="required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-envelope-fill"></i>
                            </span>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="correo@ejemplo.com" required
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="telefono" class="form-label">
                            <i class="bi bi-telephone me-1"></i> Teléfono
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-phone"></i>
                            </span>
                            <input type="tel" class="form-control" id="telefono" name="telefono" 
                                   placeholder="999 999 999"
                                   value="<?php echo htmlspecialchars($telefono ?? ''); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="bi bi-shield-lock me-1"></i> Contraseña <span class="required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock-fill"></i>
                            </span>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Mínimo 6 caracteres" required minlength="6">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                        <small class="text-muted">Mínimo 6 caracteres</small>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">
                            <i class="bi bi-shield-check me-1"></i> Confirmar Contraseña <span class="required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock-fill"></i>
                            </span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Repita la contraseña" required minlength="6">
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <small>
                            <strong>Nota:</strong> Esta cuenta tendrá permisos de <strong>Administrador</strong> 
                            con acceso completo al sistema.
                        </small>
                    </div>

                    <button type="submit" class="btn btn-register">
                        <i class="bi bi-person-plus me-2"></i>
                        Crear Cuenta de Administrador
                    </button>
                </form>

                <hr class="my-4">
                
                <div class="text-center">
                    <p class="mb-2 text-muted">¿Ya tienes una cuenta?</p>
                    <a href="login.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-left me-2"></i>
                        Volver a Iniciar Sesión
                    </a>
                </div>

                <?php endif; ?>
            </div>
        </div>
        <div class="text-center mt-3">
            <small class="text-white">
                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Todos los derechos reservados
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar/Ocultar contraseña
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('bi-eye');
                eyeIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('bi-eye-slash');
                eyeIcon.classList.add('bi-eye');
            }
        });

        // Validar que las contraseñas coincidan
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas no coinciden. Por favor verifica.');
                document.getElementById('confirm_password').focus();
                return false;
            }

            if (password.length < 6) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 6 caracteres.');
                document.getElementById('password').focus();
                return false;
            }
        });

        // Validar username sin espacios
        document.getElementById('username').addEventListener('input', function(e) {
            this.value = this.value.replace(/\s+/g, '').toLowerCase();
        });
    </script>
</body>
</html>
