<?php
/**
 * Gestión de Respaldos de Base de Datos - Superadministrador
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';

if (!tieneRol(ROL_SUPERADMIN)) {
    redirigirSegunRol();
}

$database = new Database();
$db_config = $database->getConnection();

// Database credentials from Database class
$db_host = "localhost";
$db_name = "insertel_db";
$db_user = "root";
$db_pass = "";

// Backup directory
$backup_dir = ROOT_PATH . '/uploads/backups/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

// Procesar descarga de respaldo
if (isset($_GET['descargar'])) {
    $file_to_download = basename($_GET['descargar']);
    $filepath = $backup_dir . $file_to_download;
    
    if (file_exists($filepath) && strpos(realpath($filepath), realpath($backup_dir)) === 0) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file_to_download . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        setMensaje('danger', 'Archivo no válido o no encontrado.');
        redirigir('views/superadmin/respaldos.php');
    }
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'generar_backup') {
        try {
            $filename = 'backup_' . date('Ymd_His') . '.sql';
            $filepath = $backup_dir . $filename;
            
            // Construct mysqldump command
            $command = "mysqldump --host={$db_host} --user={$db_user} ";
            if (!empty($db_pass)) {
                $command .= "--password={$db_pass} ";
            }
            $command .= $db_name . " > " . escapeshellarg($filepath) . " 2>&1";

            // Execute command
            exec($command, $output, $return_var);

            if ($return_var === 0 && file_exists($filepath) && filesize($filepath) > 0) {
                registrarActividad($_SESSION['usuario_id'], 'generar', 'respaldos', 'Respaldo de BD generado: ' . $filename);
                setMensaje('success', 'Respaldo de base de datos generado exitosamente: ' . $filename . ' (' . round(filesize($filepath) / 1024 / 1024, 2) . ' MB)');
            } else {
                setMensaje('danger', 'Error al generar el respaldo. Verifique que mysqldump esté instalado y accesible. Código: ' . $return_var);
                if (!empty($output)) {
                    error_log("Backup error: " . implode("\n", $output));
                }
            }
        } catch (Exception $e) {
            setMensaje('danger', 'Error al generar respaldo: ' . $e->getMessage());
        }
    } elseif ($accion === 'eliminar_backup') {
        $file_to_delete = basename($_POST['file']); // Sanitize filename
        $filepath = $backup_dir . $file_to_delete;

        if (file_exists($filepath) && strpos($filepath, $backup_dir) === 0) { // Ensure file is within backup_dir
            if (unlink($filepath)) {
                registrarActividad($_SESSION['usuario_id'], 'eliminar', 'respaldos', 'Respaldo de BD eliminado: ' . $file_to_delete);
                setMensaje('success', 'Respaldo eliminado exitosamente: ' . $file_to_delete);
            } else {
                setMensaje('danger', 'Error al eliminar el respaldo.');
            }
        } else {
            setMensaje('danger', 'Archivo no válido o no encontrado.');
        }
    }
    redirigir('views/superadmin/respaldos.php');
}

// Obtener lista de respaldos
$backups = [];
if (is_dir($backup_dir)) {
    $files = glob($backup_dir . '*.sql');
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a); // Sort by modification time, newest first
    });
    foreach ($files as $file) {
        $backups[] = [
            'name' => basename($file),
            'size' => filesize($file),
            'date' => filemtime($file)
        ];
    }
}

$page_title = "Gestión de Respaldos";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <h5><i class="bi bi-cloud-download me-2"></i>Gestión de Respaldos de Base de Datos</h5>
            <p class="text-muted mb-0">Cree, descargue y gestione los respaldos de su base de datos.</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6>Respaldos Disponibles</h6>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="accion" value="generar_backup">
                    <button type="submit" class="btn btn-primary" onclick="return confirm('¿Está seguro que desea generar un nuevo respaldo de la base de datos?')">
                        <i class="bi bi-plus-circle me-2"></i> Generar Nuevo Respaldo
                    </button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nombre del Archivo</th>
                            <th>Tamaño</th>
                            <th>Fecha de Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($backups)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No hay respaldos disponibles.</td></tr>
                        <?php else: ?>
                            <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td><strong><?php echo $backup['name']; ?></strong></td>
                                <td><?php echo round($backup['size'] / 1024 / 1024, 2); ?> MB</td>
                                <td><?php echo formatearFechaHora(date('Y-m-d H:i:s', $backup['date'])); ?></td>
                                <td>
                                    <a href="?descargar=<?php echo urlencode($backup['name']); ?>" class="btn btn-sm btn-outline-info me-1">
                                        <i class="bi bi-download"></i> Descargar
                                    </a>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="accion" value="eliminar_backup">
                                        <input type="hidden" name="file" value="<?php echo $backup['name']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Está seguro que desea eliminar este respaldo? Esta acción no se puede deshacer.')">
                                            <i class="bi bi-trash"></i> Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
