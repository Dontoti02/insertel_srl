<?php
/**
 * Página para restablecer contraseña
 */

require_once '../config/constants.php';
require_once '../config/functions.php';
require_once '../config/database.php';
require_once '../models/PasswordRecovery.php';

// Si ya está autenticado, redirigir
if (estaAutenticado()) {
    redirigirSegunRol();
}

$token = $_GET['token'] ?? '';
$message = '';
$message_type = '';
$valid_token = false;
$user_data = null;

$database = new Database();
$db = $database->getConnection();
$recovery = new PasswordRecovery($db);

// Validar token
if (!empty($token)) {
    $user_data = $recovery->validateRecoveryToken($token);
    if ($user_data) {
        $valid_token = true;
    } else {
        $message = 'El enlace de recuperación es inválido o ha expirado.';
        $message_type = 'error';
    }
} else {
    $message = 'Token de recuperación no proporcionado.';
    $message_type = 'error';
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $message = 'Por favor complete todos los campos';
        $message_type = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = 'La contraseña debe tener al menos 6 caracteres';
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Las contraseñas no coinciden';
        $message_type = 'error';
    } else {
        // Actualizar contraseña
        if ($recovery->updateUserPassword($user_data['user_id'], $new_password)) {
            // Marcar token como usado
            $recovery->markTokenAsUsed($token);
            
            // Eliminar todos los tokens de remember del usuario por seguridad
            $recovery->deleteAllRememberTokens($user_data['user_id']);
            
            // Registrar actividad
            registrarActividad($user_data['user_id'], 'cambio_password', 'autenticacion', 'Contraseña cambiada mediante recuperación');
            
            $message = '¡Contraseña actualizada exitosamente! Ya puede iniciar sesión con su nueva contraseña.';
            $message_type = 'success';
            $valid_token = false; // Ocultar formulario
        } else {
            $message = 'Error al actualizar la contraseña. Intente nuevamente.';
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../CSS/style.css">
    <style>
        /* Estilos específicos para reset password */
        .password-strength {
            margin-top: 8px;
            font-size: 12px;
        }

        .strength-weak { color: #dc2626; }
        .strength-medium { color: #f59e0b; }
        .strength-strong { color: #059669; }

        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .message.success {
            background: #f0f9ff;
            border: 1px solid #0ea5e9;
            color: #0c4a6e;
        }

        .message.error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #64748b;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s ease;
        }

        .back-link a:hover {
            color: #1a2cff;
        }

        .copyright {
            text-align: center;
            margin-top: 24px;
            color: #64748b;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
                        <rect width="36" height="36" rx="8" fill="#1a2cff"/>
                        <path d="M18 10c-1.1 0-2 .9-2 2v2h-2c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2v-8c0-1.1-.9-2-2-2h-2v-2c0-1.1-.9-2-2-2zm0 2c.6 0 1 .4 1 1v2h-2v-2c0-.6.4-1 1-1zm-3 6h6v6h-6v-6z" fill="white"/>
                    </svg>
                </div>
                <h1>Nueva Contraseña</h1>
                <p>Ingrese su nueva contraseña segura</p>
            </div>
            
            <!-- Mostrar mensajes -->
            <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <?php if ($valid_token): ?>
            <form method="POST" action="" id="resetForm">
                <div class="form-group">
                    <label for="new_password">Nueva Contraseña</label>
                    <input type="password" id="new_password" name="new_password" required 
                           placeholder="Ingrese su nueva contraseña">
                    <div class="password-strength" id="passwordStrength"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Confirme su nueva contraseña">
                    <div id="passwordMatch" class="password-strength"></div>
                </div>

                <button type="submit" class="login-btn" id="submitBtn">
                    Actualizar Contraseña
                </button>
            </form>
            <?php endif; ?>
            
            <div class="back-link">
                <a href="login.php">← Volver al Login</a>
            </div>
        </div>
        
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Todos los derechos reservados
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordStrength = document.getElementById('passwordStrength');
            const passwordMatch = document.getElementById('passwordMatch');
            const submitBtn = document.getElementById('submitBtn');

            // Validación de fortaleza de contraseña
            function checkPasswordStrength(password) {
                let strength = 0;
                let feedback = [];

                if (password.length >= 8) strength++;
                else feedback.push('al menos 8 caracteres');

                if (/[a-z]/.test(password)) strength++;
                else feedback.push('minúsculas');

                if (/[A-Z]/.test(password)) strength++;
                else feedback.push('mayúsculas');

                if (/[0-9]/.test(password)) strength++;
                else feedback.push('números');

                if (/[^A-Za-z0-9]/.test(password)) strength++;
                else feedback.push('símbolos');

                return { strength, feedback };
            }

            newPasswordInput.addEventListener('input', function() {
                const password = this.value;
                const result = checkPasswordStrength(password);
                
                let strengthText = '';
                let strengthClass = '';

                if (password.length === 0) {
                    strengthText = '';
                } else if (result.strength <= 2) {
                    strengthText = 'Débil - Necesita: ' + result.feedback.join(', ');
                    strengthClass = 'strength-weak';
                } else if (result.strength <= 4) {
                    strengthText = 'Media - Puede mejorar: ' + result.feedback.join(', ');
                    strengthClass = 'strength-medium';
                } else {
                    strengthText = 'Fuerte - ¡Excelente!';
                    strengthClass = 'strength-strong';
                }

                passwordStrength.textContent = strengthText;
                passwordStrength.className = 'password-strength ' + strengthClass;
            });

            // Validación de coincidencia de contraseñas
            function checkPasswordMatch() {
                const password = newPasswordInput.value;
                const confirm = confirmPasswordInput.value;

                if (confirm.length === 0) {
                    passwordMatch.textContent = '';
                    passwordMatch.className = '';
                } else if (password === confirm) {
                    passwordMatch.textContent = '✓ Las contraseñas coinciden';
                    passwordMatch.className = 'strength-strong';
                } else {
                    passwordMatch.textContent = '✗ Las contraseñas no coinciden';
                    passwordMatch.className = 'strength-weak';
                }
            }

            confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            newPasswordInput.addEventListener('input', checkPasswordMatch);

            // Validación del formulario
            document.getElementById('resetForm').addEventListener('submit', function(e) {
                const password = newPasswordInput.value;
                const confirm = confirmPasswordInput.value;

                if (password.length < 6) {
                    e.preventDefault();
                    alert('La contraseña debe tener al menos 6 caracteres');
                    return;
                }

                if (password !== confirm) {
                    e.preventDefault();
                    alert('Las contraseñas no coinciden');
                    return;
                }
            });

            // Auto-focus first input
            if (newPasswordInput) {
                newPasswordInput.focus();
            }
        });
    </script>
</body>
</html>
