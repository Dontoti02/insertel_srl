<?php
/**
 * AJAX - Obtener detalles de un acta técnica
 */

require_once '../config/constants.php';
require_once '../config/functions.php';
require_once '../config/database.php';
require_once '../models/Acta.php';
require_once '../models/User.php';

iniciarSesion();

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$acta_id = (int)($_POST['acta_id'] ?? 0);

if ($acta_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de acta inválido']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $actaModel = new Acta($db);
    $userModel = new User($db);

    // Obtener el acta por ID
    $query = "SELECT a.*, u_tec.nombre_completo as tecnico_nombre, u_rep.nombre_completo as reporta_nombre
              FROM actas_tecnicas a
              LEFT JOIN usuarios u_tec ON a.tecnico_id = u_tec.id
              LEFT JOIN usuarios u_rep ON a.usuario_reporta_id = u_rep.id
              WHERE a.id = :acta_id LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":acta_id", $acta_id);
    $stmt->execute();
    $acta = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($acta) {
        // Formatear fechas para la vista
        $acta['fecha_servicio'] = formatearFecha($acta['fecha_servicio']);
        $acta['created_at'] = formatearFechaHora($acta['created_at']);
        $acta['updated_at'] = formatearFechaHora($acta['updated_at']);

        echo json_encode([
            'success' => true,
            'acta' => $acta
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Acta no encontrada']);
    }
    
} catch (Exception $e) {
    error_log("Error en obtener_detalle_acta.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener detalles del acta: ' . $e->getMessage()
    ]);
}
?>
