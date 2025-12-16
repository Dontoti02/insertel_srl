<?php
require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/Movimiento.php';

if (!tieneAlgunRol([ROL_JEFE_ALMACEN, ROL_ADMINISTRADOR])) {
    redirigirSegunRol();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $motivo = sanitizar($_POST['motivo']);
    $documento_referencia = sanitizar($_POST['documento_referencia'] ?? '');
    $observaciones = sanitizar($_POST['observaciones'] ?? '');

    $database = new Database();
    $db = $database->getConnection();
    $movimientoModel = new Movimiento($db);

    try {
        if ($movimientoModel->actualizar($id, $motivo, $documento_referencia, $observaciones)) {
            registrarActividad($_SESSION['usuario_id'], 'actualizar', 'movimientos_inventario', "Movimiento actualizado ID: $id");
            setMensaje('success', 'Movimiento actualizado correctamente.');
        } else {
            throw new Exception("No se pudo actualizar el movimiento.");
        }
    } catch (Exception $e) {
        setMensaje('danger', 'Error: ' . $e->getMessage());
    }
}

// Redirigir a la página anterior o a movimientos
$redirect = $_SERVER['HTTP_REFERER'] ?? 'movimientos.php';
header("Location: $redirect");
exit;
