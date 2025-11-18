<?php
/**
 * AJAX: Generar respaldo de base de datos
 */

require_once '../config/constants.php';
require_once '../config/functions.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// Verificar que sea superadmin
if (!tieneRol(ROL_SUPERADMIN)) {
    echo json_encode([
        'success' => false,
        'error' => 'No tienes permisos para acceder a esta función'
    ]);
    exit;
}

// Database credentials
$db_host = "localhost";
$db_name = "insertel_db";
$db_user = "root";
$db_pass = "";

// Backup directory
$backup_dir = ROOT_PATH . '/uploads/backups/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

try {
    $filename = 'backup_' . date('Ymd_His') . '.sql';
    $filepath = $backup_dir . $filename;
    
    // Verificar si mysqldump está disponible
    $which_command = shell_exec('which mysqldump');
    if (empty($which_command)) {
        // Intentar con ruta común en Windows
        $mysqldump_path = 'mysqldump';
    } else {
        $mysqldump_path = trim($which_command);
    }
    
    // Construct mysqldump command
    $command = $mysqldump_path . " --host={$db_host} --user={$db_user} ";
    if (!empty($db_pass)) {
        $command .= "--password={$db_pass} ";
    }
    $command .= $db_name . " > " . escapeshellarg($filepath);
    
    // Execute command
    exec($command, $output, $return_var);
    
    if ($return_var === 0 && file_exists($filepath)) {
        $database = new Database();
        $db = $database->getConnection();
        registrarActividad($_SESSION['usuario_id'], 'generar', 'respaldos', 'Respaldo de BD generado: ' . $filename);
        
        echo json_encode([
            'success' => true,
            'message' => 'Respaldo generado exitosamente: ' . $filename,
            'filename' => $filename,
            'size' => filesize($filepath)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Error al generar el respaldo. Código: ' . $return_var,
            'output' => implode("\n", $output)
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>
