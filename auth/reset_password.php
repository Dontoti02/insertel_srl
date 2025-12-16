<?php
/**
 * Página de restablecimiento de contraseña - Sistema INSERTEL
 */

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/functions.php';

// Si ya está autenticado, redirigir
if (estaAutenticado()) {
    redirigirSegunRol();
}

$token = $_GET['token'] ?? '';
$error = '';
$success = false;

// Verificar token
if (empty($token)) {
    $error = 'Token inválido o faltante.';
} else {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Verificar si el token es válido
        $query = "SELECT id, nombre_completo, reset_expiry FROM usuarios 
                 WHERE reset_token = :token AND estado = 'activo'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            $error = 'Token inválido o ya utilizado.';
        } else {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar si el token ha expirado
            if (strtotime($user['reset_expiry']) < time()) {
                $error = 'El token ha expirado. Por favor solicita una nueva recuperación de contraseña.';
            }
        }
        
        // Procesar formulario de restablecimiento
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password']) && !$error) {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Validar contraseñas
            if (empty($password) || strlen($password) < 6) {
                $error = 'La contraseña debe tener al menos 6 caracteres.';
            } elseif ($password !== $confirm_password) {
                $error = 'Las contraseñas no coinciden.';
            } else {
                // Actualizar contraseña y limpiar token
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query = "UPDATE usuarios SET 
                         password = :password, 
                         reset_token = NULL, 
                         reset_expiry = NULL 
                         WHERE id = :id";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':id', $user['id']);
                
                if ($stmt->execute()) {
                    $success = true;
                    // Redirigir después de 3 segundos
                    header('refresh:3;url=login.php');
                } else {
                    $error = 'Error al actualizar la contraseña. Por favor intenta nuevamente.';
                }
            }
        }
        
    } catch (Exception $e) {
        $error = 'Error del sistema. Por favor contacta al administrador.';
        error_log("Error en reset_password: " . $e->getMessage());
    }
}

$nombre_empresa = obtenerNombreEmpresa();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - <?php echo $nombre_empresa; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .card-header {
            background: linear-gradient(135deg, #1a2cff 0%, #0f1fb8 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            text-align: center;
            padding: 2rem;
        }
        
        .logo {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 24px;
            font-weight: bold;
            color: #1a2cff;
        }
        
        .form-control {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #1a2cff;
            box-shadow: 0 0 0 0.2rem rgba(26, 44, 255, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1a2cff 0%, #0f1fb8 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 44, 255, 0.3);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            font-weight: 500;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }
        
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background: #dc3545; width: 33%; }
        .strength-medium { background: #ffc107; width: 66%; }
        .strength-strong { background: #28a745; width: 100%; }
        
        .back-link {
            color: #1a2cff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: #0f1fb8;
        }
        
        .spinner-border {
            width: 1rem;
            height: 1rem;
        }
        
        .loading {
            pointer-events: none;
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <div class="logo">
                            <i class="bi bi-key"></i>
                        </div>
                        <h3 class="mb-2">Restablecer Contraseña</h3>
                        <p class="mb-0 opacity-75">Ingresa tu nueva contraseña</p>
                    </div>
                    
                    <div class="card-body p-4">
                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <strong>¡Contraseña actualizada!</strong><br>
                                Serás redirigido al inicio de sesión en 3 segundos...
                            </div>
                            <div class="text-center mt-3">
                                <a href="login.php" class="btn btn-primary">
                                    <i class="bi bi-arrow-left me-2"></i>Ir al Inicio de Sesión
                                </a>
                            </div>
                        <?php elseif ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo $error; ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="forgot_password.php" class="btn btn-outline-primary">
                                    <i class="bi bi-arrow-left me-2"></i>Solicitar Nueva Recuperación
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="info-box">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Requisitos de contraseña:</strong> Mínimo 6 caracteres, combina letras y números para mayor seguridad.
                            </div>
                            
                            <form method="POST" id="resetForm">
                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="bi bi-lock me-2"></i>Nueva Contraseña
                                    </label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Mínimo 6 caracteres" required minlength="6">
                                    <div class="password-strength" id="passwordStrength"></div>
                                    <div class="form-text">Usa una contraseña segura con letras y números.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">
                                        <i class="bi bi-lock-fill me-2"></i>Confirmar Contraseña
                                    </label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           placeholder="Repite tu contraseña" required>
                                    <div class="invalid-feedback" id="passwordMatch"></div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <span class="btn-text">
                                            <i class="bi bi-check-circle me-2"></i>Restablecer Contraseña
                                        </span>
                                        <span class="spinner-border spinner-border-sm d-none" id="spinner"></span>
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                        
                        <div class="text-center mt-4">
                            <a href="login.php" class="back-link">
                                <i class="bi bi-arrow-left me-1"></i>Volver al Inicio de Sesión
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <small class="text-white-50">
                        &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Todos los derechos reservados
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('resetForm');
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirm_password');
            const submitBtn = document.getElementById('submitBtn');
            const spinner = document.getElementById('spinner');
            const btnText = submitBtn.querySelector('.btn-text');
            const passwordStrength = document.getElementById('passwordStrength');
            const passwordMatch = document.getElementById('passwordMatch');
            
            if (form) {
                // Validación de contraseña en tiempo real
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    let strength = 0;
                    
                    if (password.length >= 6) strength++;
                    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
                    if (password.match(/[0-9]/)) strength++;
                    if (password.match(/[^a-zA-Z0-9]/)) strength++;
                    
                    passwordStrength.className = 'password-strength';
                    if (password.length > 0) {
                        if (strength <= 1) {
                            passwordStrength.classList.add('strength-weak');
                        } else if (strength === 2) {
                            passwordStrength.classList.add('strength-medium');
                        } else {
                            passwordStrength.classList.add('strength-strong');
                        }
                    }
                });
                
                // Validación de coincidencia de contraseñas
                function checkPasswordMatch() {
                    if (confirmInput.value && passwordInput.value !== confirmInput.value) {
                        confirmInput.classList.add('is-invalid');
                        passwordMatch.textContent = 'Las contraseñas no coinciden';
                        return false;
                    } else {
                        confirmInput.classList.remove('is-invalid');
                        passwordMatch.textContent = '';
                        return true;
                    }
                }
                
                confirmInput.addEventListener('input', checkPasswordMatch);
                passwordInput.addEventListener('input', checkPasswordMatch);
                
                form.addEventListener('submit', function(e) {
                    if (!checkPasswordMatch()) {
                        e.preventDefault();
                        return;
                    }
                    
                    // Mostrar loading
                    submitBtn.classList.add('loading');
                    spinner.classList.remove('d-none');
                    btnText.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Actualizando...';
                    submitBtn.disabled = true;
                });
            }
            
            // Auto-focus en el campo password
            if (passwordInput) {
                passwordInput.focus();
            }
        });
    </script>
</body>
</html>
