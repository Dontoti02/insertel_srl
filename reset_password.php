<?php
require_once 'config/database.php';
require_once 'models/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$username = 'lopezchi';
$new_password = '123456';

// Obtener ID
$query = "SELECT id FROM usuarios WHERE username = :username";
$stmt = $db->prepare($query);
$stmt->bindParam(':username', $username);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    if ($user->cambiarPassword($row['id'], $new_password)) {
        echo "Contraseña de '$username' restablecida a '$new_password' correctamente.";
    } else {
        echo "Error al restablecer contraseña.";
    }
} else {
    echo "Usuario no encontrado.";
}
