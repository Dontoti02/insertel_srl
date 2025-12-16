<?php

/**
 * Modelo de Sede
 */

class Sede
{
    private $conn;
    private $table_name = "sedes";

    public $id;
    public $nombre;
    public $codigo;
    public $direccion;
    public $telefono;
    public $email;
    public $responsable_id;
    public $estado;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Crear sede
     */
    public function crear()
    {
        $query = "INSERT INTO " . $this->table_name . " 
                  (nombre, codigo, direccion, telefono, email, responsable_id, estado) 
                  VALUES (:nombre, :codigo, :direccion, :telefono, :email, :responsable_id, :estado)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":codigo", $this->codigo);
        $stmt->bindParam(":direccion", $this->direccion);
        $stmt->bindParam(":telefono", $this->telefono);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":responsable_id", $this->responsable_id);
        $stmt->bindParam(":estado", $this->estado);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Obtener todas las sedes
     */
    public function obtenerTodas($filtros = [])
    {
        $where = ["1=1"];
        $params = [];

        if (!empty($filtros['estado'])) {
            $where[] = "s.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        if (!empty($filtros['buscar'])) {
            $where[] = "(s.nombre LIKE :buscar OR s.codigo LIKE :buscar2)";
            $params[':buscar'] = '%' . $filtros['buscar'] . '%';
            $params[':buscar2'] = '%' . $filtros['buscar'] . '%';
        }

        $query = "SELECT s.*, 
                         u.nombre_completo as responsable_nombre,
                         (SELECT COUNT(*) FROM usuarios WHERE sede_id = s.id AND estado = 'activo') as total_usuarios
                  FROM " . $this->table_name . " s
                  LEFT JOIN usuarios u ON s.responsable_id = u.id
                  WHERE " . implode(' AND ', $where) . "
                  ORDER BY s.nombre";

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener sede por ID
     */
    public function obtenerPorId($id)
    {
        $query = "SELECT s.*, 
                         u.nombre_completo as responsable_nombre,
                         (SELECT COUNT(*) FROM usuarios WHERE sede_id = s.id AND estado = 'activo') as total_usuarios
                  FROM " . $this->table_name . " s
                  LEFT JOIN usuarios u ON s.responsable_id = u.id
                  WHERE s.id = :id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar sede
     */
    public function actualizar()
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET nombre = :nombre,
                      codigo = :codigo,
                      direccion = :direccion,
                      telefono = :telefono,
                      email = :email,
                      responsable_id = :responsable_id,
                      estado = :estado
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":codigo", $this->codigo);
        $stmt->bindParam(":direccion", $this->direccion);
        $stmt->bindParam(":telefono", $this->telefono);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":responsable_id", $this->responsable_id);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    /**
     * Verificar si existe código de sede
     */
    public function existeCodigo($codigo, $excluir_id = null)
    {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE codigo = :codigo";

        if ($excluir_id) {
            $query .= " AND id != :excluir_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":codigo", $codigo);

        if ($excluir_id) {
            $stmt->bindParam(":excluir_id", $excluir_id);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)$result['total'] > 0;
    }

    /**
     * Obtener sedes activas para select
     */
    public function obtenerActivas()
    {
        $query = "SELECT id, nombre, codigo FROM " . $this->table_name . " 
                  WHERE estado = 'activa' 
                  ORDER BY nombre";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener estadísticas de la sede
     */
    public function obtenerEstadisticas($sede_id)
    {
        $query = "SELECT 
                    (SELECT COUNT(*) FROM usuarios WHERE sede_id = :sede_id AND estado = 'activo') as total_usuarios,
                    (SELECT COUNT(*) FROM solicitudes WHERE sede_id = :sede_id2) as total_solicitudes,
                    (SELECT COUNT(*) FROM solicitudes WHERE sede_id = :sede_id3 AND estado = 'pendiente') as solicitudes_pendientes,
                    (SELECT COUNT(*) FROM actas_tecnicas at 
                     INNER JOIN usuarios u ON at.tecnico_id = u.id 
                     WHERE u.sede_id = :sede_id4) as total_actas";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":sede_id", $sede_id);
        $stmt->bindParam(":sede_id2", $sede_id);
        $stmt->bindParam(":sede_id3", $sede_id);
        $stmt->bindParam(":sede_id4", $sede_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener información de qué se eliminará al borrar una sede
     */
    public function obtenerDatosAEliminar($id)
    {
        $datos = [
            'usuarios' => 0,
            'materiales' => 0,
            'movimientos' => 0,
            'solicitudes' => 0,
            'asignaciones' => 0
        ];

        try {
            // Contar usuarios
            $query = "SELECT COUNT(*) as total FROM usuarios WHERE sede_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            $datos['usuarios'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Contar materiales
            $query = "SELECT COUNT(*) as total FROM materiales WHERE sede_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            $datos['materiales'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Contar movimientos
            $query = "SELECT COUNT(*) as total FROM movimientos_inventario WHERE sede_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            $datos['movimientos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Contar solicitudes
            $query = "SELECT COUNT(*) as total FROM solicitudes WHERE sede_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            $datos['solicitudes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Contar asignaciones a técnicos
            $query = "SELECT COUNT(*) as total FROM asignacion_tecnicos WHERE sede_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            $datos['asignaciones'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            // Silenciosamente fallar si hay error
        }

        return $datos;
    }

    /**
     * Eliminar sede
     * Los usuarios de la sede quedan sin asignar (sede_id = NULL)
     */
    public function eliminar($id)
    {
        try {
            // Verificar que la sede existe
            $sede = $this->obtenerPorId($id);
            if (!$sede) {
                error_log("Intento de eliminar sede inexistente - ID: $id");
                return false;
            }

            error_log("Iniciando eliminación de sede - ID: $id, Nombre: {$sede['nombre']}");

            $this->conn->beginTransaction();

            // 1. Desasignar usuarios de la sede (ponerlos sin sede)
            $query = "UPDATE usuarios SET sede_id = NULL WHERE sede_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            $usuarios_desasignados = $stmt->rowCount();
            error_log("Usuarios desasignados de la sede: $usuarios_desasignados");

            // 2. Eliminar la sede
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                error_log("Error: No se pudo eliminar la sede - ID: $id");
                throw new Exception("No se pudo eliminar la sede");
            }

            // Confirmar transacción
            $this->conn->commit();

            error_log("Sede eliminada exitosamente - ID: $id, Usuarios desasignados: $usuarios_desasignados");

            return true;
        } catch (Exception $e) {
            error_log("Error al eliminar sede - ID: $id, Error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            // Revertir transacción
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
                error_log("Transacción revertida");
            }

            return false;
        }
    }
}
