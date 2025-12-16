<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$username = $_GET['user'] ?? 'lopezchi';
$password = $_GET['pass'] ?? '';

echo "<h1>Diagnóstico de Login para: $username</h1>";

// 1. Buscar usuario sin filtros extra
$query = "SELECT * FROM usuarios WHERE username = :username";
$stmt = $db->prepare($query);
$stmt->bindParam(':username', $username);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<p style='color:red'>Usuario no encontrado en la base de datos.</p>";
    // Buscar usuarios parecidos
    $query = "SELECT username FROM usuarios WHERE username LIKE :username";
    $stmt = $db->prepare($query);
    $term = "%$username%";
    $stmt->bindParam(':username', $term);
    $stmt->execute();
    $parecidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($parecidos) {
        echo "<p>Usuarios similares encontrados:</p><ul>";
        foreach ($parecidos as $u) {
            echo "<li>" . $u['username'] . "</li>";
        }
        echo "</ul>";
    }
    exit;
}

echo "<p><strong>ID:</strong> " . $user['id'] . "</p>";
echo "<p><strong>Username:</strong> '" . $user['username'] . "'</p>";
echo "<p><strong>Estado:</strong> '" . $user['estado'] . "'</p>";
echo "<p><strong>Rol ID:</strong> " . $user['rol_id'] . "</p>";
echo "<p><strong>Sede ID:</strong> " . $user['sede_id'] . "</p>";
echo "<p><strong>Hash almacenado:</strong> " . $user['password'] . "</p>";

if ($user['estado'] !== 'activo') {
    echo "<p style='color:red'>El usuario NO está activo. El login requiere estado 'activo'.</p>";
} else {
    echo "<p style='color:green'>El usuario está activo.</p>";
}

if ($password) {
    echo "<h2>Prueba de contraseña: '$password'</h2>";
    if (password_verify($password, $user['password'])) {
        echo "<p style='color:green'><strong>¡Contraseña CORRECTA!</strong></p>";
        echo "<p>El login debería funcionar. Si no funciona, revisa cookies o sesiones.</p>";
    } else {
        echo "<p style='color:red'><strong>Contraseña INCORRECTA.</strong></p>";
        echo "<p>El hash almacenado no coincide con la contraseña proporcionada.</p>";
        echo "<p>Hash de prueba (para '$password'): " . password_hash($password, PASSWORD_DEFAULT) . "</p>";
    }
} else {
    echo "<p><em>Para probar contraseña, añade ?user=$username&pass=TU_PASSWORD a la URL.</em></p>";
}
