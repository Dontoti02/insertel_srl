<?php

/**
 * Funciones auxiliares del sistema
 */

/**
 * Obtiene el nombre de la empresa desde la configuración
 */
function obtenerNombreEmpresa()
{
    static $nombre_empresa = null;

    if ($nombre_empresa === null) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $stmt = $db->prepare("SELECT valor FROM configuracion_sistema WHERE clave = 'empresa_nombre' LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $nombre_empresa = $result['valor'] ?? 'INSERTEL S.R.L.';
        } catch (Exception $e) {
            $nombre_empresa = 'INSERTEL S.R.L.';
        }
    }

    return $nombre_empresa;
}

// Definir APP_NAME dinámicamente (se define después de que Database esté disponible)
if (!defined('APP_NAME')) {
    define('APP_NAME', 'INSERTEL S.R.L.');
}

/**
 * Actualiza APP_NAME desde la configuración de la BD
 * Debe llamarse después de que Database esté disponible
 */
function actualizarNombreEmpresa()
{
    try {
        $database = new Database();
        $db = $database->getConnection();
        $stmt = $db->prepare("SELECT valor FROM configuracion_sistema WHERE clave = 'empresa_nombre' LIMIT 1");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $nombre = $result['valor'] ?? 'INSERTEL S.R.L.';

        // Usar variable global para el nombre dinámico
        if (!defined('APP_NAME_ACTUALIZADO')) {
            $GLOBALS['APP_NAME_DINAMICO'] = $nombre;
            define('APP_NAME_ACTUALIZADO', true);
        }
    } catch (Exception $e) {
        // Silenciosamente fallar si hay error
    }
}

/**
 * Inicia la sesión si no está iniciada
 */
function iniciarSesion()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Verifica si el usuario está autenticado
 */
function estaAutenticado()
{
    iniciarSesion();
    return isset($_SESSION['usuario_id']) && isset($_SESSION['rol_id']);
}

/**
 * Sincroniza los datos de la sesión con la base de datos
 * Útil cuando el superadmin cambia la sede de un usuario mientras tiene sesión activa
 */
function sincronizarDatosSesion()
{
    if (!estaAutenticado()) {
        return false;
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        // Incluimos ultimo_acceso para verificar si necesitamos actualizarlo
        $query = "SELECT u.id, u.username, u.nombre_completo, u.rol_id, u.sede_id, u.estado, u.ultimo_acceso,
                         r.nombre as rol_nombre, s.nombre as sede_nombre, s.codigo as sede_codigo
                  FROM usuarios u
                  LEFT JOIN roles r ON u.rol_id = r.id
                  LEFT JOIN sedes s ON u.sede_id = s.id
                  WHERE u.id = :usuario_id
                  LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':usuario_id', $_SESSION['usuario_id'], PDO::PARAM_INT);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            // Usuario no encontrado, marcar para cerrar sesión
            $_SESSION['debe_cerrar_sesion'] = true;
            return false;
        }

        // Verificar si el usuario fue desactivado
        if ($usuario['estado'] !== 'activo') {
            $_SESSION['debe_cerrar_sesion'] = true;
            return false;
        }

        // Actualizar último acceso si ha pasado más de 5 minutos (300 segundos)
        // Esto asegura que los usuarios activos no aparezcan como "Nunca" o con fechas antiguas
        $ultimo_acceso_ts = $usuario['ultimo_acceso'] ? strtotime($usuario['ultimo_acceso']) : 0;
        if ((time() - $ultimo_acceso_ts) > 300) {
            $queryUpdate = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id";
            $stmtUpdate = $db->prepare($queryUpdate);
            $stmtUpdate->bindParam(':id', $_SESSION['usuario_id']);
            $stmtUpdate->execute();
        }

        // Verificar si hay cambios en los datos críticos
        $cambios_detectados = false;

        // Verificar cambio de sede
        $sede_actual = $_SESSION['sede_id'] ?? null;
        $sede_nueva = $usuario['sede_id'] ?? null;

        if ($sede_actual != $sede_nueva) {
            $cambios_detectados = true;
        }

        // Verificar cambio de rol
        if (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] != $usuario['rol_id']) {
            $cambios_detectados = true;
        }

        // Actualizar datos de la sesión
        $_SESSION['username'] = $usuario['username'];
        $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
        $_SESSION['rol_id'] = $usuario['rol_id'];
        $_SESSION['rol_nombre'] = $usuario['rol_nombre'];
        $_SESSION['sede_id'] = $sede_nueva;
        $_SESSION['sede_nombre'] = $usuario['sede_nombre'] ?? null;
        $_SESSION['sede_codigo'] = $usuario['sede_codigo'] ?? null;

        // Si hubo cambios, marcar para notificar al usuario
        if ($cambios_detectados) {
            $_SESSION['datos_actualizados'] = true;
        }

        return true;
    } catch (Exception $e) {
        error_log("Error al sincronizar datos de sesión: " . $e->getMessage());
        return false;
    }
}


/**
 * Verifica si el usuario tiene un rol específico
 */
function tieneRol($rol_id)
{
    iniciarSesion();
    // Convertir ambos a enteros para comparación estricta
    return isset($_SESSION['rol_id']) && (int)$_SESSION['rol_id'] === (int)$rol_id;
}

/**
 * Verifica si el usuario tiene uno de varios roles
 */
function tieneAlgunRol($roles_array)
{
    iniciarSesion();
    return isset($_SESSION['rol_id']) && in_array($_SESSION['rol_id'], $roles_array);
}

/**
 * Verifica si el usuario es superadmin
 */
function esSuperAdmin()
{
    return tieneRol(ROL_SUPERADMIN);
}

/**
 * Verifica si el usuario puede acceder a una sede específica
 */
function puedeAccederSede($sede_id)
{
    iniciarSesion();

    // Superadmin puede acceder a todas las sedes
    if (esSuperAdmin()) {
        return true;
    }

    // Otros usuarios solo pueden acceder a su sede asignada
    return isset($_SESSION['sede_id']) && $_SESSION['sede_id'] == $sede_id;
}

/**
 * Obtener sede actual del usuario
 */
function obtenerSedeActual()
{
    iniciarSesion();
    return $_SESSION['sede_id'] ?? null;
}

/**
 * Verificar si usuario pertenece a la misma sede que un recurso
 */
function mismaSede($recurso_sede_id)
{
    if (esSuperAdmin()) {
        return true; // Superadmin puede ver todo
    }

    return obtenerSedeActual() == $recurso_sede_id;
}

/**
 * Redirige a una URL
 */
function redirigir($url)
{
    header("Location: " . BASE_URL . $url);
    exit();
}

/**
 * Redirecciona según el rol del usuario
 */
function redirigirSegunRol()
{
    if (!estaAutenticado()) {
        redirigir('auth/login.php');
        return;
    }

    switch ($_SESSION['rol_id']) {
        case ROL_SUPERADMIN:
            redirigir('views/superadmin/dashboard.php');
            break;
        case ROL_ADMINISTRADOR:
            redirigir('views/admin/dashboard.php');
            break;
        case ROL_JEFE_ALMACEN:
            redirigir('views/almacen/dashboard.php');
            break;
        case ROL_ASISTENTE_ALMACEN:
            redirigir('views/asistente/dashboard.php');
            break;
        case ROL_TECNICO:
            redirigir('views/tecnico/dashboard.php');
            break;
        default:
            cerrarSesion();
    }
}

/**
 * Cierra la sesión del usuario
 */
function cerrarSesion()
{
    iniciarSesion();
    session_unset();
    session_destroy();
    redirigir('auth/login.php');
}

/**
 * Registra una actividad en el historial
 */
function registrarActividad($usuario_id, $accion, $modulo, $descripcion = '')
{
    try {
        $database = new Database();
        $db = $database->getConnection();

        $sede_info = obtenerSedeActual();
        if ($sede_info) {
            $descripcion = trim(($descripcion ?? '')) . " [sede:" . $sede_info . "]";
        }

        $query = "INSERT INTO historial_actividades (usuario_id, accion, modulo, descripcion, ip_address) 
                  VALUES (:usuario_id, :accion, :modulo, :descripcion, :ip)";

        $stmt = $db->prepare($query);
        $stmt->execute([
            ':usuario_id' => $usuario_id,
            ':accion' => $accion,
            ':modulo' => $modulo,
            ':descripcion' => $descripcion,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        error_log("Error al registrar actividad: " . $e->getMessage());
    }
}

function verificarAccesoSede($sede_id)
{
    if (!puedeAccederSede($sede_id)) {
        setMensaje('danger', 'Acceso denegado a esta sede');
        redirigir('views/admin/sedes.php');
    }
}

/**
 * Sanitiza una cadena de texto
 */
function sanitizar($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Genera un código único
 */
function generarCodigo($prefijo = '')
{
    return $prefijo . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Formatea una fecha
 */
function formatearFecha($fecha, $formato = 'd/m/Y')
{
    if (empty($fecha)) return '';
    $date = new DateTime($fecha);
    return $date->format($formato);
}

/**
 * Formatea una fecha y hora
 */
function formatearFechaHora($fecha, $formato = 'd/m/Y H:i')
{
    if (empty($fecha)) return '';
    $date = new DateTime($fecha);
    return $date->format($formato);
}

/**
 * Formatea un número como moneda en Soles
 */
function formatearMoneda($monto)
{
    return CURRENCY_SYMBOL . ' ' . number_format($monto, DECIMAL_PLACES, '.', ',');
}

/**
 * Muestra un mensaje de alerta (toast)
 */
function setMensaje($tipo, $mensaje)
{
    iniciarSesion();
    $_SESSION['mensaje'] = [
        'tipo' => $tipo,
        'texto' => $mensaje
    ];
}

/**
 * Obtiene y limpia el mensaje de alerta
 */
function getMensaje()
{
    iniciarSesion();
    if (isset($_SESSION['mensaje'])) {
        $mensaje = $_SESSION['mensaje'];
        unset($_SESSION['mensaje']);
        return $mensaje;
    }
    return null;
}

function crearTokenFormulario($clave)
{
    iniciarSesion();
    $token = bin2hex(random_bytes(16));
    $_SESSION['form_tokens'][$clave] = $token;
    return $token;
}

function validarTokenFormulario($clave, $token)
{
    iniciarSesion();
    return isset($_SESSION['form_tokens'][$clave]) && hash_equals($_SESSION['form_tokens'][$clave], $token);
}

function consumirTokenFormulario($clave)
{
    iniciarSesion();
    if (isset($_SESSION['form_tokens'][$clave])) {
        unset($_SESSION['form_tokens'][$clave]);
    }
}

/**
 * Valida un email
 */
function validarEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Verifica si un archivo subido es válido
 */
function validarArchivo($file, $max_size = MAX_FILE_SIZE)
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valido' => false, 'mensaje' => 'Error al subir el archivo'];
    }

    if ($file['size'] > $max_size) {
        return ['valido' => false, 'mensaje' => 'El archivo excede el tamaño máximo permitido'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return ['valido' => false, 'mensaje' => 'Extensión de archivo no permitida'];
    }

    return ['valido' => true, 'extension' => $ext];
}

/**
 * Exporta datos a CSV
 */
function exportarCSV($filename, $data, $headers = [])
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM para UTF-8

    if (!empty($headers)) {
        fputcsv($output, $headers);
    }

    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

/**
 * Obtiene el nombre del rol por ID
 */
function getNombreRol($rol_id)
{
    $roles = [
        ROL_ADMINISTRADOR => 'Administrador',
        ROL_JEFE_ALMACEN => 'Jefe de Almacén',
        ROL_ASISTENTE_ALMACEN => 'Asistente de Almacén',
        ROL_TECNICO => 'Técnico'
    ];
    return $roles[$rol_id] ?? 'Desconocido';
}

/**
 * Obtiene la clase CSS para el badge de estado
 */
function getBadgeEstado($estado)
{
    $badges = [
        // Estados de materiales con colores más distintivos
        'activo' => 'success text-white badge-activo-override',
        'inactivo' => 'danger text-white badge-inactivo-override',

        // Estados de solicitudes
        'pendiente' => 'warning text-dark',
        'aprobada' => 'info text-white',
        'rechazada' => 'danger text-white',
        'completada' => 'success text-white'
    ];
    return $badges[$estado] ?? 'secondary text-white';
}

/**
 * Valida si un archivo es un Excel/CSV válido
 */
function validarArchivoExcel($archivo)
{
    $errores = [];

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $errores[] = 'Error al subir el archivo';
        return $errores;
    }

    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, EXCEL_EXTENSIONS)) {
        $errores[] = 'Formato de archivo no válido. Use: ' . implode(', ', EXCEL_EXTENSIONS);
    }

    if ($archivo['size'] > MAX_FILE_SIZE) {
        $errores[] = 'El archivo es demasiado grande. Máximo: ' . number_format(MAX_FILE_SIZE / 1024 / 1024, 1) . 'MB';
    }

    return $errores;
}

/**
 * Procesa un archivo CSV y devuelve los datos como array
 */
function procesarArchivoCSV($archivo_path, $delimitador = ',')
{
    $datos = [];

    if (($handle = fopen($archivo_path, 'r')) !== FALSE) {
        $fila = 0;
        $headers = [];

        while (($data = fgetcsv($handle, 1000, $delimitador)) !== FALSE) {
            $fila++;

            if ($fila === 1) {
                $headers = $data;
                continue;
            }

            if (count($data) >= count($headers)) {
                $datos[] = array_combine($headers, array_slice($data, 0, count($headers)));
            }
        }

        fclose($handle);
    }

    return $datos;
}

/**
 * Exporta datos a Excel (formato HTML compatible)
 */
function exportarExcel($filename, $data, $headers = [])
{
    // Asegurar extensión .xls
    if (substr($filename, -4) !== '.xls') {
        $filename = str_replace('.csv', '', $filename) . '.xls';
    }

    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "<html xmlns:x='urn:schemas-microsoft-com:office:excel'>";
    echo "<head>";
    echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";
    echo "<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Reporte</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->";
    echo "</head>";
    echo "<body>";
    echo "<table border='1'>";

    if (!empty($headers)) {
        echo "<thead><tr style='background-color: #4472C4; color: white;'>";
        foreach ($headers as $header) {
            echo "<th style='padding: 10px;'>" . htmlspecialchars($header) . "</th>";
        }
        echo "</tr></thead>";
    }

    echo "<tbody>";
    foreach ($data as $row) {
        echo "<tr>";
        foreach ($row as $cell) {
            echo "<td style='padding: 5px;'>" . htmlspecialchars($cell ?? '') . "</td>";
        }
        echo "</tr>";
    }
    echo "</tbody></table></body></html>";
    exit();
}
