<?php
/**
 * Página para solicitar recuperación de contraseña
 */

require_once '../config/constants.php';
require_once '../config/functions.php';
require_once '../config/database.php';
require_once '../models/PasswordRecovery.php';

// Si ya está autenticado, redirigir
if (estaAutenticado()) {
    redirigirSegunRol();
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = sanitizar($_POST['identifier'] ?? ''); // Email o username
    
    if (empty($identifier)) {
        $message = 'Por favor ingrese su email o nombre de usuario';
        $message_type = 'error';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        $recovery = new PasswordRecovery($db);
        
        // Buscar usuario por email o username
        $user = null;
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $user = $recovery->getUserByEmail($identifier);
        } else {
            $user = $recovery->getUserByUsername($identifier);
        }
        
        if ($user) {
            // Crear token de recuperación
            $token = $recovery->createRecoveryToken($user['id']);
            
            if ($token) {
                // Crear enlace de recuperación
                $recovery_link = BASE_URL . "auth/reset_password.php?token=" . $token;
                
                // Simular envío de email (en producción usar un servicio de email)
                // Por ahora, mostraremos el enlace directamente
                $message = "Se ha generado un enlace de recuperación. En producción se enviaría por email.<br><br>";
                $message .= "<strong>Enlace de recuperación:</strong><br>";
                $message .= "<a href='$recovery_link' class='recovery-link'>$recovery_link</a><br><br>";
                $message .= "<small>Este enlace es válido por 1 hora.</small>";
                $message_type = 'success';
                
                // Registrar actividad
                registrarActividad($user['id'], 'solicitud_recuperacion', 'autenticacion', 'Solicitud de recuperación de contraseña');
            } else {
                $message = 'Error al generar el token de recuperación. Intente nuevamente.';
                $message_type = 'error';
            }
        } else {
            // Por seguridad, no revelamos si el usuario existe o no
            $message = "Si el usuario existe, se ha enviado un enlace de recuperación a su email registrado.";
            $message_type = 'info';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../CSS/style.css">
    <style>
        /* Estilos específicos para recuperación de contraseña */
        .recovery-link {
            color: #1a2cff;
            text-decoration: none;
            font-weight: 600;
            word-break: break-all;
        }

        .recovery-link:hover {
            color: #091df8;
            text-decoration: underline;
        }

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

        .message.info {
            background: #eff6ff;
            border: 1px solid #93c5fd;
            color: #1d4ed8;
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
                        <path d="M18 10c-1.1 0-2 .9-2 2v2h-2c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2v-8c0-1.1-.9-2-2-2h-2v-2c0-1.1-.9-2-2-2zm0 2c.6 0 1 .4 1 1v2h-2v-2c0-.6.4-1 1-1z" fill="white"/>
                    </svg>
                </div>
                <h1>Recuperar Contraseña</h1>
                <p>Ingrese su email o usuario para recuperar su contraseña</p>
            </div>
            
            <!-- Mostrar mensajes -->
            <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="identifier">Email o Usuario</label>
                    <input type="text" id="identifier" name="identifier" required 
                           placeholder="Ingrese su email o usuario"
                           value="<?php echo isset($_POST['identifier']) ? htmlspecialchars($_POST['identifier']) : ''; ?>">
                </div>

                <button type="submit" class="login-btn">
                    Enviar Enlace de Recuperación
                </button>
            </form>
            
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
            // Auto-focus first input
            document.getElementById('identifier').focus();
        });
    </script>
</body>
</html>
