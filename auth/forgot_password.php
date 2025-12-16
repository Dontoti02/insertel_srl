<?php
/**
 * Página de recuperación de contraseña - Sistema INSERTEL
 * Funciona con PHP mail para XAMPP
 */

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/functions.php';

// Si ya está autenticado, redirigir
if (estaAutenticado()) {
    redirigirSegunRol();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = sanitizar($_POST['email']);
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Verificar si el email existe en la base de datos
        $query = "SELECT id, nombre_completo FROM usuarios WHERE email = :email AND estado = 'activo'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Guardar token en la base de datos
            $query = "UPDATE usuarios SET reset_token = :token, reset_expiry = :expiry WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':expiry', $expiry);
            $stmt->bindParam(':id', $user['id']);
            $stmt->execute();
            
            // Enviar correo
            $reset_link = BASE_URL . "auth/reset_password.php?token=" . $token;
            $subject = "Recuperación de Contraseña - " . APP_NAME;
            
            $message = "
            <html>
            <head>
                <title>Recuperación de Contraseña</title>
            </head>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                    <div style='text-align: center; margin-bottom: 30px;'>
                        <h2 style='color: #1a2cff; margin: 0;'>" . APP_NAME . "</h2>
                        <p style='margin: 5px 0; color: #666;'>Sistema de Gestión</p>
                    </div>
                    
                    <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>
                        <h3 style='color: #333; margin-top: 0;'>Recuperación de Contraseña</h3>
                        <p>Hola <strong>" . $user['nombre_completo'] . "</strong>,</p>
                        <p>Recibimos una solicitud para restablecer tu contraseña. Si no realizaste esta solicitud, puedes ignorar este correo.</p>
                        <p>Para restablecer tu contraseña, haz clic en el siguiente enlace:</p>
                        
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='" . $reset_link . "' 
                               style='background: #1a2cff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>
                                Restablecer Contraseña
                            </a>
                        </div>
                        
                        <p style='font-size: 14px; color: #666;'>
                            O copia y pega este enlace en tu navegador:<br>
                            <span style='word-break: break-all;'>" . $reset_link . "</span>
                        </p>
                        
                        <p style='font-size: 14px; color: #666;'>
                            <strong>Importante:</strong> Este enlace expirará en 1 hora.
                        </p>
                    </div>
                    
                    <div style='border-top: 1px solid #ddd; padding-top: 20px; text-align: center; font-size: 12px; color: #666;'>
                        <p>Este es un correo automático, por favor no responda.</p>
                        <p>&copy; " . date('Y') . " " . APP_NAME . " - Todos los derechos reservados</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            // Headers para correo HTML
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: " . APP_NAME . " <noreply@insertel.com>" . "\r\n";
            
            if (mail($email, $subject, $message, $headers)) {
                $success = true;
                $success_message = "Se ha enviado un correo a $email con las instrucciones para restablecer tu contraseña.";
            } else {
                $error = "Error al enviar el correo. Por favor intenta nuevamente.";
            }
        } else {
            // No revelar si el email existe o no por seguridad
            $success = true;
            $success_message = "Si el correo está registrado, recibirás un email con las instrucciones.";
        }
        
    } catch (Exception $e) {
        $error = "Error del sistema. Por favor contacta al administrador.";
        error_log("Error en forgot_password: " . $e->getMessage());
    }
}

$nombre_empresa = obtenerNombreEmpresa();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - <?php echo $nombre_empresa; ?></title>
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
        
        .info-box {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-left: 4px solid #1a2cff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
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
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <h3 class="mb-2">Recuperar Contraseña</h3>
                        <p class="mb-0 opacity-75">Ingresa tu correo para recibir instrucciones</p>
                    </div>
                    
                    <div class="card-body p-4">
                        <?php if (isset($success) && $success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php echo $success_message; ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="login.php" class="btn btn-primary">
                                    <i class="bi bi-arrow-left me-2"></i>Volver al Inicio de Sesión
                                </a>
                            </div>
                        <?php elseif (isset($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!isset($success) || !$success): ?>
                            <div class="info-box">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Importante:</strong> Recibirás un correo electrónico con un enlace para restablecer tu contraseña. El enlace será válido por 1 hora.
                            </div>
                            
                            <form method="POST" id="forgotForm">
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="bi bi-envelope me-2"></i>Correo Electrónico
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                           placeholder="tu@correo.com" required>
                                    <div class="form-text">Te enviaremos las instrucciones a este correo.</div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <span class="btn-text">
                                            <i class="bi bi-send me-2"></i>Enviar Correo de Recuperación
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
            const form = document.getElementById('forgotForm');
            const submitBtn = document.getElementById('submitBtn');
            const spinner = document.getElementById('spinner');
            const btnText = submitBtn.querySelector('.btn-text');
            
            if (form) {
                form.addEventListener('submit', function() {
                    // Mostrar loading
                    submitBtn.classList.add('loading');
                    spinner.classList.remove('d-none');
                    btnText.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Enviando...';
                    submitBtn.disabled = true;
                });
            }
            
            // Auto-focus en el campo email
            const emailInput = document.getElementById('email');
            if (emailInput) {
                emailInput.focus();
            }
        });
    </script>
</body>
</html>
