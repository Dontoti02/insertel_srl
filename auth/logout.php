<?php
/**
 * Cerrar sesión
 */

require_once '../config/constants.php';
require_once '../config/functions.php';
require_once '../config/database.php';
require_once '../models/PasswordRecovery.php';

iniciarSesion();

if (isset($_SESSION['usuario_id'])) {
    $user_id = $_SESSION['usuario_id'];
    
    // Eliminar token de "recordar sesión" si existe
    if (isset($_COOKIE['remember_token'])) {
        $database = new Database();
        $db = $database->getConnection();
        $recovery = new PasswordRecovery($db);
        
        $recovery->deleteRememberToken($_COOKIE['remember_token']);
        
        // Eliminar cookie
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
    
    registrarActividad($user_id, 'logout', 'autenticacion', 'Cierre de sesión');
}

cerrarSesion();
