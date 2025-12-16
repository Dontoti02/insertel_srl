<?php

/**
 * Procesar asignación de administrador a sede
 */

require_once '../config/constants.php';
require_once '../config/functions.php';
require_once '../config/database.php';
require_once '../models/User.php';

header('Content-Type: application/json');

if (!tieneRol(ROL_SUPERADMIN)) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $userModel = new User($db);

    $sede_id = isset($_POST['sede_id']) ? (int)$_POST['sede_id'] : 0;
    $admin_id = isset($_POST['admin_id']) ? (int)$_POST['admin_id'] : 0;

    if (!$sede_id || !$admin_id) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }

    // 1. Primero, si este administrador ya era responsable de otra sede, quitarle esa responsabilidad
    // Esto evita que una sede antigua siga apuntando a este admin como responsable
    $queryLimpiar = "UPDATE sedes SET responsable_id = NULL WHERE responsable_id = :admin_id";
    $stmtLimpiar = $db->prepare($queryLimpiar);
    $stmtLimpiar->bindParam(':admin_id', $admin_id);
    $stmtLimpiar->execute();

    // 2. Cambiar la sede del administrador en la tabla usuarios
    if ($userModel->cambiarSede($admin_id, $sede_id)) {

        // 3. También actualizar el responsable_id en la tabla sedes
        $query = "UPDATE sedes SET responsable_id = :admin_id WHERE id = :sede_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':admin_id', $admin_id);
        $stmt->bindParam(':sede_id', $sede_id);

        if ($stmt->execute()) {
            registrarActividad(
                $_SESSION['usuario_id'],
                'asignar_admin_sede',
                'sedes',
                "Administrador ID $admin_id asignado a sede ID $sede_id"
            );

            echo json_encode([
                'success' => true,
                'message' => '✓ Administrador asignado exitosamente.'
            ]);
        } else {
            // Si falla la actualización de la sede, loguear error
            error_log("Error al actualizar responsable_id en sedes para sede_id: $sede_id");
            echo json_encode([
                'success' => false,
                'message' => '✗ Error al actualizar el responsable de la sede.'
            ]);
        }
    } else {
        error_log("Error al cambiar sede_id en usuarios para admin_id: $admin_id");
        echo json_encode([
            'success' => false,
            'message' => '✗ Error al asignar el administrador.'
        ]);
    }
} catch (Exception $e) {
    error_log("Excepción en asignar_admin_sede.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
