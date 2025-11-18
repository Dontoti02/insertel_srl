<?php
require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/Movimiento.php';
require_once '../../models/Material.php';

if (!tieneAlgunRol([ROL_JEFE_ALMACEN, ROL_ADMINISTRADOR])) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();
$movimientoModel = new Movimiento($db);
$materialModel = new Material($db);

if (isset($_GET['id']) && isset($_GET['tipo'])) {
    $id = (int)$_GET['id'];
    $tipo = sanitizar($_GET['tipo']);

    $table_name = '';
    $redirect_url = '';

    if ($tipo === 'entrada') {
        $table_name = 'entradas_materiales';
        $redirect_url = 'entradas_materiales.php';
    } elseif ($tipo === 'salida') {
        $table_name = 'salidas_materiales';
        $redirect_url = 'salidas_materiales.php';
    } else {
        setMensaje('danger', 'Tipo de movimiento no válido.');
        redirigir('views/almacen/dashboard.php');
    }

    try {
        $db->beginTransaction();

        // Obtener el movimiento_id
        $query_mov = "SELECT movimiento_id FROM $table_name WHERE id = :id";
        $stmt_mov = $db->prepare($query_mov);
        $stmt_mov->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt_mov->execute();
        $movimiento_id = $stmt_mov->fetchColumn();

        if (!$movimiento_id) {
            throw new Exception('Registro no encontrado.');
        }

        // Obtener detalles del movimiento
        $movimiento = $movimientoModel->obtenerPorId($movimiento_id);
        if (!$movimiento) {
            throw new Exception('Movimiento de inventario no encontrado.');
        }

        // Eliminar el registro de entrada/salida
        $query_delete_detalle = "DELETE FROM $table_name WHERE id = :id";
        $stmt_delete_detalle = $db->prepare($query_delete_detalle);
        $stmt_delete_detalle->bindParam(':id', $id, PDO::PARAM_INT);
        if (!$stmt_delete_detalle->execute()) {
            throw new Exception("Error al eliminar el detalle de $tipo.");
        }

        // Eliminar el movimiento de inventario
        if (!$movimientoModel->eliminar($movimiento_id)) {
            throw new Exception('Error al eliminar el movimiento de inventario.');
        }

        $db->commit();
        registrarActividad($_SESSION['usuario_id'], 'eliminar', $table_name, "Registro de $tipo eliminado (ID: $id, Movimiento ID: $movimiento_id)");
        setMensaje('success', "El registro de $tipo ha sido eliminado correctamente.");

    } catch (Exception $e) {
        $db->rollBack();
        setMensaje('danger', 'Error al eliminar el registro: ' . $e->getMessage());
    }

    redirigir("views/almacen/$redirect_url");
} else {
    setMensaje('danger', 'Parámetros inválidos.');
    redirigir('views/almacen/dashboard.php');
}
?>