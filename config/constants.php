<?php

/**
 * Constantes del sistema
 * INSERTEL S.R.L.
 */

// Configuración de la aplicación
define('APP_NAME', 'INSERTEL S.R.L.'); // Nombre por defecto, puede ser actualizado desde BD
define('APP_VERSION', '1.0.0');
// Detectar URL base dinámicamente (para soportar localhost y túneles como ngrok)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
// Asegurar que termine en /
define('BASE_URL', $protocol . $host . '/insertel/');
define('ENVIRONMENT', 'development'); // development | production

// Rutas del sistema
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads/');
define('ACTAS_PATH', UPLOAD_PATH . 'actas/');
define('TEMPLATES_PATH', ROOT_PATH . '/templates/');

// Roles del sistema
define('ROL_SUPERADMIN', 5);        // Superadministrador del sistema
define('ROL_ADMINISTRADOR', 1);     // Administrador de sede
define('ROL_JEFE_ALMACEN', 2);      // Jefe de almacén de sede
define('ROL_ASISTENTE_ALMACEN', 3); // Asistente de almacén de sede
define('ROL_TECNICO', 4);           // Técnico de sede

// Estados
define('ESTADO_ACTIVO', 'activo');
define('ESTADO_INACTIVO', 'inactivo');

// Estados de solicitudes
define('SOLICITUD_PENDIENTE', 'pendiente');
define('SOLICITUD_APROBADA', 'aprobada');
define('SOLICITUD_RECHAZADA', 'rechazada');
define('SOLICITUD_COMPLETADA', 'completada');

// Tipos de movimientos
define('MOVIMIENTO_ENTRADA', 'entrada');
define('MOVIMIENTO_SALIDA', 'salida');
define('MOVIMIENTO_AJUSTE', 'ajuste');

// Configuración de paginación
define('REGISTROS_POR_PAGINA', 20);

// Configuración de archivos
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xlsx', 'xls', 'csv']);
define('EXCEL_EXTENSIONS', ['xlsx', 'xls', 'csv']);

// Zona horaria
date_default_timezone_set('America/Lima');

// Configuración de moneda
define('CURRENCY_SYMBOL', 'S/');      // Símbolo de moneda (Soles)
define('CURRENCY_CODE', 'PEN');       // Código ISO de moneda (Peruvian Nuevo Sol)
define('CURRENCY_NAME', 'Soles');     // Nombre de la moneda
define('DECIMAL_PLACES', 2);          // Decimales para precios

// Configuración de Mailtrap (Email Service)
// Token configurado y listo para usar
define('MAILTRAP_API_TOKEN', '57046c07cceb24c9dd959618a9a7f717');  // ✅ CONFIGURADO
define('MAILTRAP_FROM_EMAIL', 'noreply@insertel.com');
define('MAILTRAP_FROM_NAME', 'INSERTEL S.R.L.');

// Configuración de sesión
// Solo aplicar ini_set si la sesión aún NO está activa, para evitar warnings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Cambiar a 1 en producción con HTTPS
}
