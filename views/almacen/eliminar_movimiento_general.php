<?php
require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/Movimiento.php';

if (!tieneAlgunRol([ROL_JEFE_ALMACEN, ROL_ADMINISTRADOR])) {
    redirigirSegunRol();
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    $database = new Database();
    $db = $database->getConnection();
    $movimientoModel = new Movimiento($db);

    try {
        $db->beginTransaction();

        if ($movimientoModel->eliminar($id)) {
            $db->commit();
            registrarActividad($_SESSION['usuario_id'], 'eliminar', 'movimientos_inventario', "Movimiento eliminado ID: $id");
            setMensaje('success', 'Movimiento eliminado correctamente y stock ajustado.');
        } else {
            throw new Exception("No se pudo eliminar el movimiento.");
        }
    } catch (Exception $e) {
        $db->rollBack();
        setMensaje('danger', 'Error: ' . $e->getMessage());
    }
}

// Redirigir a la página anterior o a movimientos
$redirect = $_SERVER['HTTP_REFERER'] ?? 'movimientos.php';
header("Location: $redirect");
exit;
