<?php
/**
 * Modelo de Usuario
 */

class User {
    private $conn;
    private $table_name = "usuarios";

    public $id;
    public $username;
    public $password;
    public $nombre_completo;
    public $email;
    public $telefono;
    public $rol_id;
    public $sede_id;
    public $estado;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Autenticar usuario
     */
    public function login($username, $password) {
        $query = "SELECT u.*, 
                         s.nombre as sede_nombre, s.codigo as sede_codigo,
                         CASE 
                            WHEN u.rol_id = 5 THEN 'Superadministrador'
                            WHEN u.rol_id = 1 THEN 'Administrador'
                            WHEN u.rol_id = 2 THEN 'Jefe de Almacén'
                            WHEN u.rol_id = 3 THEN 'Asistente de Almacén'
                            WHEN u.rol_id = 4 THEN 'Técnico'
                            ELSE 'Sin rol'
                         END as rol_nombre
                  FROM " . $this->table_name . " u
                  LEFT JOIN sedes s ON u.sede_id = s.id
                  WHERE u.username = :username AND u.estado = 'activo'
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $row['password'])) {
                // Actualizar último acceso
                $this->actualizarUltimoAcceso($row['id']);
                return $row;
            }
        }
        return false;
    }

    /**
     * Actualizar último acceso
     */
    private function actualizarUltimoAcceso($user_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET ultimo_acceso = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $user_id);
        $stmt->execute();
    }

    /**
     * Crear nuevo usuario
     */
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (username, password, nombre_completo, email, telefono, rol_id, estado) 
                  VALUES (:username, :password, :nombre_completo, :email, :telefono, :rol_id, :estado)";

        $stmt = $this->conn->prepare($query);

        // Hash del password
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":nombre_completo", $this->nombre_completo);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":telefono", $this->telefono);
        $stmt->bindParam(":rol_id", $this->rol_id);
        $stmt->bindParam(":estado", $this->estado);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Obtener todos los usuarios
     */
    public function obtenerTodos($filtros = []) {
        $where = ["1=1"];
        $params = [];

        if (!empty($filtros['rol_id'])) {
            $where[] = "u.rol_id = :rol_id";
            $params[':rol_id'] = $filtros['rol_id'];
        }

        if (!empty($filtros['estado'])) {
            $where[] = "u.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }


        if (!empty($filtros['buscar'])) {
            $where[] = "(u.nombre_completo LIKE :buscar OR u.username LIKE :buscar OR u.email LIKE :buscar)";
            $params[':buscar'] = '%' . $filtros['buscar'] . '%';
        }

        if (!esSuperAdmin() && obtenerSedeActual()) {
            $where[] = "u.sede_id = :sede_id";
            $params[':sede_id'] = obtenerSedeActual();
        }

        // Excluir SuperAdmin users para roles que no son SuperAdmin
        if (!esSuperAdmin()) {
            $where[] = "u.rol_id != :rol_superadmin";
            $params[':rol_superadmin'] = ROL_SUPERADMIN;
        }

        $query = "SELECT u.*, r.nombre as rol_nombre 
                  FROM " . $this->table_name . " u
                  INNER JOIN roles r ON u.rol_id = r.id
                  WHERE " . implode(' AND ', $where) . "
                  ORDER BY u.created_at DESC";

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            // Convertir arrays a string para evitar el error "Array to string conversion"
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener usuario por ID
     */
    public function obtenerPorId($id) {
        $query = "SELECT u.*, r.nombre as rol_nombre 
                  FROM " . $this->table_name . " u
                  INNER JOIN roles r ON u.rol_id = r.id
                  WHERE u.id = :id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar usuario
     */
    public function actualizar() {
        // Verificar si el usuario actual es SUPERADMIN y se intenta cambiar su rol
        $query_check = "SELECT rol_id FROM " . $this->table_name . " WHERE id = :id";
        $stmt_check = $this->conn->prepare($query_check);
        $stmt_check->bindParam(":id", $this->id);
        $stmt_check->execute();
        $usuario_actual = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario_actual && $usuario_actual['rol_id'] == ROL_SUPERADMIN && $this->rol_id != ROL_SUPERADMIN) {
            throw new Exception("No se puede cambiar el rol del Superadministrador");
        }
        
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
     * Cambiar contraseña
     */
    public function cambiarPassword($user_id, $nueva_password) {
        $query = "UPDATE " . $this->table_name . " 
                  SET password = :password 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $hashed_password = password_hash($nueva_password, PASSWORD_DEFAULT);

        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":id", $user_id);

        return $stmt->execute();
    }

    /**
     * Verificar si existe username
     */
    public function existeUsername($username, $excluir_id = null) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username";
        
        if ($excluir_id) {
            $query .= " AND id != :excluir_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        
        if ($excluir_id) {
            $stmt->bindParam(":excluir_id", $excluir_id);
        }

        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Verificar si existe email
     */
    public function existeEmail($email, $excluir_id = null) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        
        if ($excluir_id) {
            $query .= " AND id != :excluir_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        
        if ($excluir_id) {
            $stmt->bindParam(":excluir_id", $excluir_id);
        }

        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Obtener técnicos activos
     */
    public function obtenerTecnicos() {
        $query = "SELECT u.* 
                  FROM " . $this->table_name . " u
                  WHERE u.rol_id = :rol_id AND u.estado = 'activo'
                  ORDER BY u.nombre_completo";

        $stmt = $this->conn->prepare($query);
        $rol_tecnico = ROL_TECNICO;
        $stmt->bindParam(":rol_id", $rol_tecnico);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Contar usuarios por rol
     */
    public function contarPorRol() {
        $query = "SELECT r.nombre, COUNT(u.id) as total
                  FROM roles r
                  LEFT JOIN " . $this->table_name . " u ON r.id = u.rol_id AND u.estado = 'activo'";
        
        // Excluir SuperAdmin para roles que no son SuperAdmin
        if (!esSuperAdmin()) {
            $query .= " AND r.id != " . ROL_SUPERADMIN;
        }
        
        $query .= " GROUP BY r.id, r.nombre";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarPorRolPorSede($sede_id) {
        $query = "SELECT r.nombre, COUNT(u.id) as total
                  FROM roles r
                  LEFT JOIN " . $this->table_name . " u 
                    ON r.id = u.rol_id 
                   AND u.estado = 'activo'
                   AND u.sede_id = :sede_id";
        
        // Excluir SuperAdmin para roles que no son SuperAdmin
        if (!esSuperAdmin()) {
            $query .= " AND r.id != " . ROL_SUPERADMIN;
        }
        
        $query .= " GROUP BY r.id, r.nombre";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':sede_id', $sede_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Eliminar usuario
     */
    public function eliminar($id) {
        // Verificar si el usuario a eliminar es SUPERADMIN
        $query_check = "SELECT rol_id FROM " . $this->table_name . " WHERE id = :id";
        $stmt_check = $this->conn->prepare($query_check);
        $stmt_check->bindParam(":id", $id);
        $stmt_check->execute();
        $usuario_actual = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario_actual && $usuario_actual['rol_id'] == ROL_SUPERADMIN) {
            throw new Exception("No se puede eliminar un Superadministrador");
        }
        
        try {
            // Iniciar transacción
            $this->conn->beginTransaction();
            
            // 1. Actualizar movimientos_inventario donde este usuario es responsable
            $query = "UPDATE movimientos_inventario SET usuario_id = NULL WHERE usuario_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            
            // 2. Actualizar movimientos_inventario donde este usuario es técnico asignado
            $query = "UPDATE movimientos_inventario SET tecnico_asignado_id = NULL WHERE tecnico_asignado_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            
            // 3. Eliminar stock_tecnicos de este técnico
            $query = "DELETE FROM stock_tecnicos WHERE tecnico_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            
            // 4. Actualizar actas_técnicas
            $query = "UPDATE actas_tecnicas SET tecnico_id = NULL WHERE tecnico_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            
            // 5. Actualizar verificaciones_calidad
            $query = "UPDATE verificaciones_calidad SET usuario_id = NULL WHERE usuario_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            
            // 6. Actualizar alertas_sistema
            $query = "UPDATE alertas_sistema SET usuario_id = NULL WHERE usuario_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            
            // 7. Eliminar tokens de recuperación de contraseña
            $query = "DELETE FROM password_recovery_tokens WHERE user_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            
            // 8. Eliminar remember tokens
            $query = "DELETE FROM remember_tokens WHERE user_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            
            // 9. Eliminar el historial de actividades del usuario
            $query_historial = "DELETE FROM historial_actividades WHERE usuario_id = :id";
            $stmt_historial = $this->conn->prepare($query_historial);
            $stmt_historial->bindParam(":id", $id);
            $stmt_historial->execute();
            
            // 10. Finalmente, eliminar el usuario
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $result = $stmt->execute();
            
            // Confirmar transacción
            $this->conn->commit();
            
            return $result;
        } catch (PDOException $e) {
            // Revertir transacción en caso de error
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * Obtener usuarios por sede
     */
    public function obtenerPorSede($sede_id, $filtros = []) {
        $where = ["u.sede_id = :sede_id"];
        $params = [':sede_id' => $sede_id];

        if (!empty($filtros['rol'])) {
            $where[] = "u.rol_id = :rol";
            $params[':rol'] = $filtros['rol'];
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

        $query = "SELECT u.*, s.nombre as sede_nombre,
                         CASE 
                            WHEN u.rol_id = 5 THEN 'Superadministrador'
                            WHEN u.rol_id = 1 THEN 'Administrador'
                            WHEN u.rol_id = 2 THEN 'Jefe de Almacén'
                            WHEN u.rol_id = 3 THEN 'Asistente de Almacén'
                            WHEN u.rol_id = 4 THEN 'Técnico'
                            ELSE 'Sin rol'
                         END as rol_nombre
                  FROM " . $this->table_name . " u
                  LEFT JOIN sedes s ON u.sede_id = s.id
                  WHERE " . implode(' AND ', $where) . "
                  ORDER BY u.nombre_completo";

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener administradores disponibles para asignar a sedes
     */
    public function obtenerAdministradoresDisponibles() {
        $query = "SELECT u.*, s.nombre as sede_actual
                  FROM " . $this->table_name . " u
                  LEFT JOIN sedes s ON u.sede_id = s.id
                  WHERE u.rol_id = 1 AND u.estado = 'activo'
                  ORDER BY u.nombre_completo";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crear usuario con sede
     */
    public function crearConSede() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (username, nombre_completo, email, telefono, password, rol_id, sede_id, estado) 
                  VALUES (:usuario, :nombre_completo, :email, :telefono, :password, :rol, :sede_id, :estado)";

        $stmt = $this->conn->prepare($query);

        // Hash del password
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);

        $stmt->bindParam(":usuario", $this->username);
        $stmt->bindParam(":nombre_completo", $this->nombre_completo);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":telefono", $this->telefono);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":rol", $this->rol_id);
        $stmt->bindParam(":sede_id", $this->sede_id);
        $stmt->bindParam(":estado", $this->estado);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Verificar si usuario puede acceder a una sede específica
     */
    public function puedeAccederSede($usuario_id, $sede_id) {
        $query = "SELECT u.rol_id, u.sede_id 
                  FROM " . $this->table_name . " u
                  WHERE u.id = :usuario_id AND u.estado = 'activo'
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usuario_id", $usuario_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Superadmin puede acceder a todas las sedes
            if ($usuario['rol_id'] == 5) {
                return true;
            }
            
            // Otros usuarios solo pueden acceder a su sede asignada
            return $usuario['sede_id'] == $sede_id;
        }
        
        return false;
    }

    /**
     * Obtener estadísticas de usuarios por sede
     */
    public function obtenerEstadisticasPorSede($sede_id) {
        $query = "SELECT 
                    COUNT(*) as total_usuarios,
                    SUM(CASE WHEN rol_id = 1 THEN 1 ELSE 0 END) as administradores,
                    SUM(CASE WHEN rol_id = 2 THEN 1 ELSE 0 END) as jefes_almacen,
                    SUM(CASE WHEN rol_id = 3 THEN 1 ELSE 0 END) as asistentes,
                    SUM(CASE WHEN rol_id = 4 THEN 1 ELSE 0 END) as tecnicos,
                    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                    SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos
                  FROM " . $this->table_name . " 
                  WHERE sede_id = :sede_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":sede_id", $sede_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cambiar sede de usuario (solo para superadmin)
     */
    public function cambiarSede($usuario_id, $nueva_sede_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET sede_id = :nueva_sede_id 
                  WHERE id = :usuario_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nueva_sede_id", $nueva_sede_id);
        $stmt->bindParam(":usuario_id", $usuario_id);

        return $stmt->execute();
    }

    /**
     * Obtener todos los usuarios para Superadmin (global)
     */
    public function obtenerTodosGlobales($filtros = []) {
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
            $where[] = "(u.nombre_completo LIKE :buscar OR u.username LIKE :buscar OR u.email LIKE :buscar)";
            $params[':buscar'] = '%' . $filtros['buscar'] . '%';
        }

        $query = "SELECT u.*, r.nombre as rol_nombre, s.nombre as sede_nombre
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.rol_id = r.id
                  LEFT JOIN sedes s ON u.sede_id = s.id
                  WHERE " . implode(' AND ', $where) . "
                  ORDER BY u.created_at DESC";

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Subir imagen de perfil
     */
    public function subirImagenPerfil($usuario_id, $archivo) {
        // Validar archivo
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
        $tamaño_maximo = 5 * 1024 * 1024; // 5MB

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            return ['exito' => false, 'mensaje' => 'Error al subir el archivo'];
        }

        if ($archivo['size'] > $tamaño_maximo) {
            return ['exito' => false, 'mensaje' => 'El archivo excede el tamaño máximo de 5MB'];
        }

        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $extensiones_permitidas)) {
            return ['exito' => false, 'mensaje' => 'Formato de archivo no permitido. Use: JPG, PNG, GIF'];
        }

        // Crear directorio si no existe
        $directorio = dirname(__DIR__) . '/uploads/perfiles';
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        // Generar nombre único para el archivo
        $nombre_archivo = 'perfil_' . $usuario_id . '_' . time() . '.' . $extension;
        $ruta_archivo = $directorio . '/' . $nombre_archivo;

        // Mover archivo subido
        if (!move_uploaded_file($archivo['tmp_name'], $ruta_archivo)) {
            return ['exito' => false, 'mensaje' => 'Error al guardar el archivo'];
        }

        // Obtener imagen anterior para eliminarla
        $query_anterior = "SELECT imagen_perfil FROM " . $this->table_name . " WHERE id = :id";
        $stmt_anterior = $this->conn->prepare($query_anterior);
        $stmt_anterior->bindParam(":id", $usuario_id);
        $stmt_anterior->execute();
        $resultado = $stmt_anterior->fetch(PDO::FETCH_ASSOC);

        if ($resultado && !empty($resultado['imagen_perfil'])) {
            $ruta_anterior = $directorio . '/' . $resultado['imagen_perfil'];
            if (file_exists($ruta_anterior)) {
                unlink($ruta_anterior);
            }
        }

        // Actualizar base de datos
        $query = "UPDATE " . $this->table_name . " 
                  SET imagen_perfil = :imagen_perfil 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":imagen_perfil", $nombre_archivo);
        $stmt->bindParam(":id", $usuario_id);

        if ($stmt->execute()) {
            return ['exito' => true, 'mensaje' => 'Imagen de perfil actualizada correctamente', 'archivo' => $nombre_archivo];
        }

        return ['exito' => false, 'mensaje' => 'Error al actualizar la base de datos'];
    }

    /**
     * Obtener URL de imagen de perfil
     */
    public function obtenerUrlImagenPerfil($usuario_id) {
        $query = "SELECT imagen_perfil FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $usuario_id);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado && !empty($resultado['imagen_perfil'])) {
            return 'uploads/perfiles/' . $resultado['imagen_perfil'];
        }

        return null;
    }

    /**
     * Eliminar imagen de perfil
     */
    public function eliminarImagenPerfil($usuario_id) {
        // Obtener imagen actual
        $query = "SELECT imagen_perfil FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $usuario_id);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado && !empty($resultado['imagen_perfil'])) {
            $directorio = dirname(__DIR__) . '/uploads/perfiles';
            $ruta_archivo = $directorio . '/' . $resultado['imagen_perfil'];
            
            if (file_exists($ruta_archivo)) {
                unlink($ruta_archivo);
            }
        }

        // Actualizar base de datos
        $query_update = "UPDATE " . $this->table_name . " 
                        SET imagen_perfil = NULL 
                        WHERE id = :id";

        $stmt_update = $this->conn->prepare($query_update);
        $stmt_update->bindParam(":id", $usuario_id);

        return $stmt_update->execute();
    }

    /**
     * Obtener usuarios por rol
     */
    public function obtenerPorRol($rol_id, $estado = 'activo') {
        $query = "SELECT u.id, u.nombre_completo, u.email
                  FROM " . $this->table_name . " u
                  WHERE u.rol_id = :rol_id AND u.estado = :estado
                  ORDER BY u.nombre_completo";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":rol_id", $rol_id);
        $stmt->bindParam(":estado", $estado);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
