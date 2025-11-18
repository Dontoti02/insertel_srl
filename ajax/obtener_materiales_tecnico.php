<?php
/**
 * AJAX - Obtener materiales asignados a un técnico
 */

require_once '../config/constants.php';
require_once '../config/functions.php';
require_once '../config/database.php';
require_once '../models/AsignacionTecnico.php';

// Iniciar sesión de forma centralizada (evita conflictos con ini_set de sesión)
iniciarSesion();

header('Content-Type: application/json');

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Validar permisos
if (!tieneAlgunRol([ROL_JEFE_ALMACEN, ROL_ADMINISTRADOR])) {
    echo json_encode(['success' => false, 'message' => 'No tiene permisos']);
    exit;
}

$tecnico_id = (int)($_POST['tecnico_id'] ?? 0);

if ($tecnico_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de técnico inválido']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $asignacionModel = new AsignacionTecnico($db);
    $materiales = $asignacionModel->obtenerPorTecnico($tecnico_id);
    
    // Formatear fechas
    foreach ($materiales as &$material) {
        $material['fecha_asignacion'] = formatearFechaHora($material['fecha_asignacion']);
    }
    
    echo json_encode([
        'success' => true,
        'materiales' => $materiales
    ]);
    
} catch (Exception $e) {
    error_log("Error en obtener_materiales_tecnico.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener materiales: ' . $e->getMessage()
    ]);
}
?>
