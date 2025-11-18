<?php
/**
 * AJAX: Obtener datos de una sede a eliminar
 */

require_once '../config/constants.php';
require_once '../config/functions.php';
require_once '../config/database.php';
require_once '../models/Sede.php';

header('Content-Type: application/json');

// Verificar que sea superadmin
if (!tieneRol(ROL_SUPERADMIN)) {
    echo json_encode([
        'success' => false,
        'error' => 'No tienes permisos para acceder a esta función'
    ]);
    exit;
}

// Obtener sede_id
if (!isset($_POST['sede_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'No se especificó la sede'
    ]);
    exit;
}

$sede_id = (int)$_POST['sede_id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    $sedeModel = new Sede($db);
    
    // Obtener datos de qué se eliminará
    $datos = $sedeModel->obtenerDatosAEliminar($sede_id);
    
    echo json_encode([
        'success' => true,
        'usuarios' => $datos['usuarios'],
        'materiales' => $datos['materiales'],
        'movimientos' => $datos['movimientos'],
        'solicitudes' => $datos['solicitudes'],
        'asignaciones' => $datos['asignaciones']
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener datos: ' . $e->getMessage()
    ]);
}

?>
