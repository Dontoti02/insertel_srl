<?php
/**
 * Página de inicio de sesión
 */

require_once '../config/constants.php';
require_once '../config/functions.php';
require_once '../config/database.php';
require_once '../models/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db); // Inicializar el modelo User


// Si ya está autenticado, redirigir
if (estaAutenticado()) {
    redirigirSegunRol();
}

$error = '';

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizar($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Por favor complete todos los campos';
    } else {
        $usuario = $user->login($username, $password);

        if ($usuario) {
            // Iniciar sesión
            iniciarSesion();
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['username'] = $usuario['username'];
            $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
            $_SESSION['rol_id'] = $usuario['rol_id'];
            $_SESSION['rol_nombre'] = $usuario['rol_nombre'];
            $_SESSION['sede_id'] = $usuario['sede_id'] ?? null;
            $_SESSION['sede_nombre'] = $usuario['sede_nombre'] ?? null;
            $_SESSION['sede_codigo'] = $usuario['sede_codigo'] ?? null;


            // Registrar actividad
            registrarActividad($usuario['id'], 'login', 'autenticacion', 'Inicio de sesión exitoso');

            // Redirigir según rol
            redirigirSegunRol();
        } else {
            $error = 'Usuario o contraseña incorrectos';
        }
    }
}

$nombre_empresa = obtenerNombreEmpresa();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - <?php echo $nombre_empresa; ?></title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
        <div class="login-header">
            <div class="logo">
                <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
                    <rect width="36" height="36" rx="8" fill="#1a2cff"/>
                    <path d="M12 14h12v8H12v-8zm2 2v4h8v-4h-8zm-2-4h12v2H12v-2zm0 12h12v2H12v-2z" fill="white"/>
                </svg>
            </div>
            <h1><?php echo $nombre_empresa; ?></h1>
            <p>Sistema de Gestión de Inventario</p>
        </div>
            
            <!-- Mostrar errores -->
            <?php if (!empty($error)): ?>
            <div class="error-message show" style="text-align: center; margin: 16px 0; padding: 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #dc2626;">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <!-- Mostrar éxito -->
            <?php if (!empty($success)): ?>
            <div class="success-message show">
                <div class="success-icon">
                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                        <circle cx="14" cy="14" r="14" fill="#10B981"/>
                        <path d="M9 14l3 3 7-7" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3>¡Bienvenido!</h3>
                <p><?php echo $success; ?></p>
            </div>
            <?php else: ?>
            
            <form class="login-form" method="POST" action="">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" required autocomplete="username" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           placeholder="Ingrese su usuario">
                    <span class="error-message" id="usernameError"></span>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" required autocomplete="current-password"
                               placeholder="Ingrese su contraseña">
                        <button type="button" class="password-toggle" id="passwordToggle" aria-label="Mostrar/ocultar contraseña">
                            <svg class="eye-open" width="18" height="18" viewBox="0 0 18 18" fill="none">
                                <path d="M9 3.75C5.25 3.75 2.04 6.24 1.5 9c.54 2.76 3.75 5.25 7.5 5.25s6.96-2.49 7.5-5.25c-.54-2.76-3.75-5.25-7.5-5.25zm0 8.75a3.5 3.5 0 110-7 3.5 3.5 0 010 7zm0-5.5a2 2 0 100 4 2 2 0 000-4z" fill="currentColor"/>
                            </svg>
                            <svg class="eye-closed" width="18" height="18" viewBox="0 0 18 18" fill="none">
                                <path d="M2.25 2.25l13.5 13.5m-4.125-4.125a3 3 0 01-4.243-4.243m4.243 4.243L9 9m2.625 2.625L15 15M9 5.25c1.83 0 3.51.63 4.84 1.68M3.16 6.93A10.97 10.97 0 019 5.25" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                    <span class="error-message" id="passwordError"></span>
                </div>


                <button type="submit" class="login-btn">
                    <span class="btn-text">Iniciar Sesión</span>
                    <div class="btn-loader">
                        <div class="spinner"></div>
                    </div>
                </button>
            </form>
            <?php endif; ?>

        </div>
        
        <!-- Copyright -->
        <div style="text-align: center; margin-top: 20px; color: #64748b; font-size: 14px;">
            &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Todos los derechos reservados
        </div>
    </div>

    <script>
        // Funcionalidad del toggle de contraseña
        document.addEventListener('DOMContentLoaded', function() {
            const passwordToggle = document.getElementById('passwordToggle');
            const passwordInput = document.getElementById('password');
            
            if (passwordToggle && passwordInput) {
                passwordToggle.addEventListener('click', function() {
                    const type = passwordInput.type === 'password' ? 'text' : 'password';
                    passwordInput.type = type;
                    
                    passwordToggle.classList.toggle('show-password', type === 'text');
                });
            }
            
            // Auto-focus en el campo de usuario
            const usernameInput = document.getElementById('username');
            if (usernameInput && !usernameInput.value) {
                usernameInput.focus();
            }
        });
    </script>
</body>
</html>