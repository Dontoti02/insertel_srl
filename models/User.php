<?php

/**
 * Modelo de Usuario
 */

class User
{
    private $conn;
    private $table_name = "usuarios";

    public $id;
    public $username;
    public $password;
    public $nombre_completo;
    public $email;
    public $telefono;
    public $imagen_perfil;
    public $rol_id;
    public $sede_id;
    public $estado;
    public $ultimo_acceso;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Autenticar usuario (login)
     */
    public function login($username, $password)
    {
        $query = "SELECT u.*, r.nombre as rol_nombre, s.nombre as sede_nombre, s.codigo as sede_codigo
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.rol_id = r.id
                  LEFT JOIN sedes s ON u.sede_id = s.id
                  WHERE u.username = :username AND u.estado = 'activo'
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar si el usuario existe y la contraseña es correcta
        if ($usuario && password_verify($password, $usuario['password'])) {
            // Actualizar último acceso
            $this->actualizarUltimoAcceso($usuario['id']);
            return $usuario;
        }

        return false;
    }

    /**
     * Crear usuario con sede
     */
    public function crearConSede()
    {
        $query = "INSERT INTO " . $this->table_name . " 
                  (username, password, nombre_completo, email, telefono, rol_id, sede_id, estado)
                  VALUES (:username, :password, :nombre_completo, :email, :telefono, :rol_id, :sede_id, :estado)";

        $stmt = $this->conn->prepare($query);

        // Hash de la contraseña
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":nombre_completo", $this->nombre_completo);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":telefono", $this->telefono);
        $stmt->bindParam(":rol_id", $this->rol_id);
        $stmt->bindParam(":sede_id", $this->sede_id);
        $stmt->bindParam(":estado", $this->estado);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Actualizar usuario
     */
    public function actualizar()
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET nombre_completo = :nombre_completo,
                      email = :email,
                      telefono = :telefono,
                      rol_id = :rol_id,
                      estado = :estado
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre_completo", $this->nombre_completo);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":telefono", $this->telefono);
        $stmt->bindParam(":rol_id", $this->rol_id);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    /**
     * Eliminar usuario (con manejo de todas las relaciones de base de datos)
     */
    public function eliminar($id)
    {
        try {
            $this->conn->beginTransaction();

            // 1. Eliminar registros de historial que referencian al usuario
            $queryHist = "DELETE FROM historial_actividades WHERE usuario_id = :id";
            $stmtHist = $this->conn->prepare($queryHist);
            $stmtHist->bindParam(':id', $id);
            $stmtHist->execute();

            // 2. Eliminar movimientos de inventario donde el usuario es el responsable o técnico asignado
            $queryMov = "DELETE FROM movimientos_inventario WHERE usuario_id = :id1 OR tecnico_asignado_id = :id2";
            $stmtMov = $this->conn->prepare($queryMov);
            $stmtMov->bindParam(':id1', $id);
            $stmtMov->bindParam(':id2', $id);
            $stmtMov->execute();

            // 3. Actualizar solicitudes - establecer usuario_respuesta_id a NULL (ya tiene ON DELETE SET NULL)
            // No es necesario hacer nada aquí, la BD lo maneja automáticamente

            // 4. Eliminar solicitudes donde el usuario es el técnico (ON DELETE CASCADE en BD)
            // No es necesario hacer nada aquí, la BD lo maneja automáticamente

            // 5. Eliminar stock_tecnicos donde el usuario es el técnico
            $queryStock = "DELETE FROM stock_tecnicos WHERE tecnico_id = :id";
            $stmtStock = $this->conn->prepare($queryStock);
            $stmtStock->bindParam(':id', $id);
            $stmtStock->execute();

            // 6. Actualizar devoluciones_materiales - establecer tecnico_id y usuario_id a NULL
            $queryDev = "UPDATE devoluciones_materiales SET tecnico_id = NULL WHERE tecnico_id = :id";
            $stmtDev = $this->conn->prepare($queryDev);
            $stmtDev->bindParam(':id', $id);
            $stmtDev->execute();

            $queryDevUser = "UPDATE devoluciones_materiales SET usuario_id = NULL WHERE usuario_id = :id";
            $stmtDevUser = $this->conn->prepare($queryDevUser);
            $stmtDevUser->bindParam(':id', $id);
            $stmtDevUser->execute();

            // 7. Actualizar entradas_materiales - establecer usuario_id a NULL
            $queryEnt = "UPDATE entradas_materiales SET usuario_id = NULL WHERE usuario_id = :id";
            $stmtEnt = $this->conn->prepare($queryEnt);
            $stmtEnt->bindParam(':id', $id);
            $stmtEnt->execute();

            // 8. Actualizar salidas_materiales - establecer tecnico_id y usuario_id a NULL
            $querySal = "UPDATE salidas_materiales SET tecnico_id = NULL WHERE tecnico_id = :id";
            $stmtSal = $this->conn->prepare($querySal);
            $stmtSal->bindParam(':id', $id);
            $stmtSal->execute();

            $querySalUser = "UPDATE salidas_materiales SET usuario_id = NULL WHERE usuario_id = :id";
            $stmtSalUser = $this->conn->prepare($querySalUser);
            $stmtSalUser->bindParam(':id', $id);
            $stmtSalUser->execute();

            // 9. Actualizar verificaciones_calidad - establecer usuario_id a NULL
            $queryVerif = "UPDATE verificaciones_calidad SET usuario_id = NULL WHERE usuario_id = :id";
            $stmtVerif = $this->conn->prepare($queryVerif);
            $stmtVerif->bindParam(':id', $id);
            $stmtVerif->execute();

            // 10. Actualizar verificacion_calidad_proveedor - establecer usuario_verificador_id a NULL
            $queryVerifProv = "UPDATE verificacion_calidad_proveedor SET usuario_verificador_id = NULL WHERE usuario_verificador_id = :id";
            $stmtVerifProv = $this->conn->prepare($queryVerifProv);
            $stmtVerifProv->bindParam(':id', $id);
            $stmtVerifProv->execute();

            // 11. Eliminar actas_tecnicas donde el usuario es el técnico
            $queryActas = "DELETE FROM actas_tecnicas WHERE tecnico_id = :id";
            $stmtActas = $this->conn->prepare($queryActas);
            $stmtActas->bindParam(':id', $id);
            $stmtActas->execute();

            // 12. Actualizar alertas_sistema - establecer usuario_id a NULL
            $queryAlertas = "UPDATE alertas_sistema SET usuario_id = NULL WHERE usuario_id = :id";
            $stmtAlertas = $this->conn->prepare($queryAlertas);
            $stmtAlertas->bindParam(':id', $id);
            $stmtAlertas->execute();

            // 13. Actualizar sedes - establecer responsable_id a NULL si el usuario es responsable
            $querySedes = "UPDATE sedes SET responsable_id = NULL WHERE responsable_id = :id";
            $stmtSedes = $this->conn->prepare($querySedes);
            $stmtSedes->bindParam(':id', $id);
            $stmtSedes->execute();

            // 14. Finalmente, eliminar el usuario
            $queryUser = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmtUser = $this->conn->prepare($queryUser);
            $stmtUser->bindParam(':id', $id);
            $stmtUser->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error al eliminar usuario: " . $e->getMessage());
            throw $e; // Re-lanzar la excepción para que el controlador pueda mostrar el mensaje
        }
    }

    /**
     * Obtener usuario por ID
     */
    public function obtenerPorId($id)
    {
        $query = "SELECT u.*, r.nombre as rol_nombre, s.nombre as sede_nombre
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.rol_id = r.id
                  LEFT JOIN sedes s ON u.sede_id = s.id
                  WHERE u.id = :id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener todos los usuarios con filtros (para administrador de sede)
     */
    public function obtenerTodos($filtros = [])
    {
        $where = ["1=1"];
        $params = [];

        // Filtrar por sede del administrador
        if (isset($_SESSION['sede_id'])) {
            $where[] = "u.sede_id = :sede_id";
            $params[':sede_id'] = $_SESSION['sede_id'];
        }

        if (!empty($filtros['rol_id'])) {
            $where[] = "u.rol_id = :rol_id";
            $params[':rol_id'] = $filtros['rol_id'];
        }

        if (!empty($filtros['estado'])) {
            $where[] = "u.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        if (!empty($filtros['buscar'])) {
            $where[] = "(u.nombre_completo LIKE :buscar OR u.username LIKE :buscar2 OR u.email LIKE :buscar3)";
            $params[':buscar'] = '%' . $filtros['buscar'] . '%';
            $params[':buscar2'] = '%' . $filtros['buscar'] . '%';
            $params[':buscar3'] = '%' . $filtros['buscar'] . '%';
        }

        $query = "SELECT u.*, r.nombre as rol_nombre, s.nombre as sede_nombre
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.rol_id = r.id
                  LEFT JOIN sedes s ON u.sede_id = s.id
                  WHERE " . implode(' AND ', $where) . "
                  ORDER BY u.created_at DESC";

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener todos los usuarios globales (para superadministrador)
     */
    public function obtenerTodosGlobales($filtros = [])
    {
        $where = ["1=1"];
        $params = [];

        if (!empty($filtros['rol_id'])) {
            $where[] = "u.rol_id = :rol_id";
            $params[':rol_id'] = $filtros['rol_id'];
        }

        if (!empty($filtros['sede_id'])) {
            $where[] = "u.sede_id = :sede_id";
            $params[':sede_id'] = $filtros['sede_id'];
        }

        if (!empty($filtros['estado'])) {
            $where[] = "u.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        if (!empty($filtros['buscar'])) {
            $where[] = "(u.nombre_completo LIKE :buscar OR u.username LIKE :buscar2 OR u.email LIKE :buscar3)";
            $params[':buscar'] = '%' . $filtros['buscar'] . '%';
            $params[':buscar2'] = '%' . $filtros['buscar'] . '%';
            $params[':buscar3'] = '%' . $filtros['buscar'] . '%';
        }

        $query = "SELECT u.*, r.nombre as rol_nombre, s.nombre as sede_nombre
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.rol_id = r.id
                  LEFT JOIN sedes s ON u.sede_id = s.id
                  WHERE " . implode(' AND ', $where) . "
                  ORDER BY u.created_at DESC";

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verificar si existe un username
     */
    public function existeUsername($username, $excluir_id = null)
    {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE username = :username";

        if ($excluir_id) {
            $query .= " AND id != :excluir_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);

        if ($excluir_id) {
            $stmt->bindParam(":excluir_id", $excluir_id);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)$result['total'] > 0;
    }

    /**
     * Verificar si existe un email
     */
    public function existeEmail($email, $excluir_id = null)
    {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE email = :email";

        if ($excluir_id) {
            $query .= " AND id != :excluir_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);

        if ($excluir_id) {
            $stmt->bindParam(":excluir_id", $excluir_id);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)$result['total'] > 0;
    }

    /**
     * Cambiar sede de un usuario
     */
    public function cambiarSede($usuario_id, $sede_id)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET sede_id = :sede_id
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":sede_id", $sede_id);
        $stmt->bindParam(":id", $usuario_id);

        return $stmt->execute();
    }

    /**
     * Obtener usuario por username
     */
    public function obtenerPorUsername($username)
    {
        $query = "SELECT u.*, r.nombre as rol_nombre, s.nombre as sede_nombre
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.rol_id = r.id
                  LEFT JOIN sedes s ON u.sede_id = s.id
                  WHERE u.username = :username
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener administradores disponibles (para asignación a sedes)
     */
    public function obtenerAdministradoresDisponibles()
    {
        $query = "SELECT u.*, s.nombre as sede_actual
                  FROM " . $this->table_name . " u
                  LEFT JOIN sedes s ON u.sede_id = s.id
                  WHERE u.rol_id = :rol_admin AND u.estado = 'activo' AND u.sede_id IS NULL
                  ORDER BY u.nombre_completo";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':rol_admin', ROL_ADMINISTRADOR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar último acceso
     */
    public function actualizarUltimoAcceso($id)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET ultimo_acceso = NOW()
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }


    /**
     * Obtener estadísticas de usuarios por sede
     * Devuelve total de usuarios activos y total de técnicos en la sede
     */
    public function obtenerEstadisticasPorSede($sede_id)
    {
        $stats = [
            'total_usuarios' => 0,
            'tecnicos' => 0
        ];

        // Total usuarios activos en la sede
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE sede_id = :sede_id AND estado = 'activo'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':sede_id', $sede_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_usuarios'] = $result['total'];

        // Total técnicos (ROL_TECNICO) en la sede
        $queryTech = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE sede_id = :sede_id AND rol_id = :rol_tecnico AND estado = 'activo'";
        $stmtTech = $this->conn->prepare($queryTech);
        $stmtTech->bindParam(':sede_id', $sede_id);
        $stmtTech->bindValue(':rol_tecnico', ROL_TECNICO);
        $stmtTech->execute();
        $resultTech = $stmtTech->fetch(PDO::FETCH_ASSOC);
        $stats['tecnicos'] = $resultTech['total'];

        return $stats;
    }

    /**
     * Contar usuarios por rol en una sede específica
     */
    public function contarPorRolPorSede($sede_id)
    {
        $query = "SELECT r.nombre, COUNT(u.id) as total
                  FROM " . $this->table_name . " u
                  JOIN roles r ON u.rol_id = r.id
                  WHERE u.sede_id = :sede_id AND u.estado = 'activo'
                  GROUP BY r.id, r.nombre
                  ORDER BY total DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":sede_id", $sede_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener todos los técnicos activos
     */
    public function obtenerTecnicos()
    {
        $query = "SELECT u.*, s.nombre as sede_nombre
                  FROM " . $this->table_name . " u
                  LEFT JOIN sedes s ON u.sede_id = s.id
                  WHERE u.rol_id = :rol_tecnico AND u.estado = 'activo'";

        // Filtrar por sede si no es superadmin
        if (isset($_SESSION['sede_id']) && !empty($_SESSION['sede_id'])) {
            $query .= " AND u.sede_id = :sede_id";
        }

        $query .= " ORDER BY u.nombre_completo";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':rol_tecnico', ROL_TECNICO);

        if (isset($_SESSION['sede_id']) && !empty($_SESSION['sede_id'])) {
            $stmt->bindValue(':sede_id', $_SESSION['sede_id']);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener URL de imagen de perfil
     */
    public function obtenerUrlImagenPerfil($usuario_id)
    {
        $query = "SELECT imagen_perfil FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $usuario_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['imagen_perfil'] ?? null;
    }

    /**
     * Subir imagen de perfil
     */
    public function subirImagenPerfil($usuario_id, $archivo)
    {
        $resultado = ['exito' => false, 'mensaje' => ''];

        // Validar archivo
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $extensiones_permitidas)) {
            $resultado['mensaje'] = 'Formato de imagen no permitido. Use JPG, PNG o GIF';
            return $resultado;
        }

        if ($archivo['size'] > 5242880) { // 5MB
            $resultado['mensaje'] = 'La imagen es demasiado grande. Máximo 5MB';
            return $resultado;
        }

        // Crear directorio si no existe
        $directorio = '../uploads/perfiles/';
        if (!file_exists($directorio)) {
            mkdir($directorio, 0777, true);
        }

        // Eliminar imagen anterior si existe
        $imagen_anterior = $this->obtenerUrlImagenPerfil($usuario_id);
        if ($imagen_anterior && file_exists('../' . $imagen_anterior)) {
            unlink('../' . $imagen_anterior);
        }

        // Generar nombre único
        $nombre_archivo = 'perfil_' . $usuario_id . '_' . time() . '.' . $extension;
        $ruta_completa = $directorio . $nombre_archivo;

        // Mover archivo
        if (move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
            // Actualizar base de datos
            $ruta_bd = 'uploads/perfiles/' . $nombre_archivo;
            $query = "UPDATE " . $this->table_name . " SET imagen_perfil = :imagen WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':imagen', $ruta_bd);
            $stmt->bindParam(':id', $usuario_id);

            if ($stmt->execute()) {
                $resultado['exito'] = true;
                $resultado['mensaje'] = 'Imagen de perfil actualizada correctamente';
            } else {
                $resultado['mensaje'] = 'Error al actualizar la base de datos';
            }
        } else {
            $resultado['mensaje'] = 'Error al subir la imagen';
        }

        return $resultado;
    }

    /**
     * Eliminar imagen de perfil
     */
    public function eliminarImagenPerfil($usuario_id)
    {
        $imagen = $this->obtenerUrlImagenPerfil($usuario_id);

        if ($imagen && file_exists('../' . $imagen)) {
            unlink('../' . $imagen);
        }

        $query = "UPDATE " . $this->table_name . " SET imagen_perfil = NULL WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $usuario_id);

        return $stmt->execute();
    }

    /**
     * Cambiar contraseña
     */
    public function cambiarPassword($usuario_id, $nueva_password)
    {
        $hashed_password = password_hash($nueva_password, PASSWORD_DEFAULT);

        $query = "UPDATE " . $this->table_name . " SET password = :password WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':id', $usuario_id);

        return $stmt->execute();
    }
}
