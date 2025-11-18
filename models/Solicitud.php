<?php
class Solicitud {
    private $conn;
    private $table_name = "solicitudes";
    
    public $id;
    public $codigo_solicitud;
    public $tecnico_id;
    public $sede_id;
    public $fecha_solicitud;
    public $estado;
    public $motivo;
    public $justificacion;
    public $fecha_respuesta;
    public $usuario_respuesta_id;
    public $comentario_respuesta;
    public $created_at;
    public $updated_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Obtener todas las solicitudes
    public function obtenerTodas() {
        $query = "SELECT s.*, u.nombre_completo as tecnico_nombre, u.email as tecnico_email,
                         ur.nombre_completo as responsable_nombre
                  FROM " . $this->table_name . " s
                  LEFT JOIN usuarios u ON s.tecnico_id = u.id
                  LEFT JOIN usuarios ur ON s.usuario_respuesta_id = ur.id";
        
        // Filtrar por sede si no es superadmin
        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $query .= " WHERE s.sede_id = :sede_id";
            }
        }
        
        $query .= " ORDER BY s.fecha_solicitud DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $stmt->bindParam(":sede_id", $sede_actual);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener solicitudes pendientes
    public function obtenerPendientes() {
        $query = "SELECT s.*, u.nombre_completo as tecnico_nombre, u.email as tecnico_email
                  FROM " . $this->table_name . " s
                  LEFT JOIN usuarios u ON s.tecnico_id = u.id
                  WHERE s.estado = 'pendiente'";
        
        // Filtrar por sede si no es superadmin
        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $query .= " AND s.sede_id = :sede_id";
            }
        }
        
        $query .= " ORDER BY s.fecha_solicitud ASC";
        
        $stmt = $this->conn->prepare($query);
        
        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $stmt->bindParam(":sede_id", $sede_actual);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener por ID
    public function obtenerPorId($id) {
        $query = "SELECT s.*, u.nombre_completo as tecnico_nombre, u.email as tecnico_email,
                         ur.nombre_completo as responsable_nombre
                  FROM " . $this->table_name . " s
                  LEFT JOIN usuarios u ON s.tecnico_id = u.id
                  LEFT JOIN usuarios ur ON s.usuario_respuesta_id = ur.id
                  WHERE s.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Obtener solicitudes de un técnico
    public function obtenerPorTecnico($tecnico_id) {
        $query = "SELECT s.*, u.nombre_completo as responsable_nombre
                  FROM " . $this->table_name . " s
                  LEFT JOIN usuarios u ON s.usuario_respuesta_id = u.id
                  WHERE s.tecnico_id = :tecnico_id
                  ORDER BY s.fecha_solicitud DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":tecnico_id", $tecnico_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Crear nueva solicitud
    public function crear() {
        // Generar código único
        $this->codigo_solicitud = 'SOL-' . date('Ymd') . '-' . strtoupper(uniqid());
        
        // Obtener sede del técnico
        $query_sede = "SELECT sede_id FROM usuarios WHERE id = :tecnico_id";
        $stmt_sede = $this->conn->prepare($query_sede);
        $stmt_sede->bindParam(":tecnico_id", $this->tecnico_id);
        $stmt_sede->execute();
        $tecnico = $stmt_sede->fetch(PDO::FETCH_ASSOC);
        
        if ($tecnico) {
            $this->sede_id = $tecnico['sede_id'];
        }
        
        $query = "INSERT INTO " . $this->table_name . " 
                  (codigo_solicitud, tecnico_id, sede_id, motivo, justificacion)
                  VALUES (:codigo_solicitud, :tecnico_id, :sede_id, :motivo, :justificacion)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":codigo_solicitud", $this->codigo_solicitud);
        $stmt->bindParam(":tecnico_id", $this->tecnico_id);
        $stmt->bindParam(":sede_id", $this->sede_id);
        $stmt->bindParam(":motivo", $this->motivo);
        $stmt->bindParam(":justificacion", $this->justificacion);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    // Actualizar estado (aprobar/rechazar)
    public function actualizarEstado($estado, $usuario_respuesta_id, $comentario = '') {
        $query = "UPDATE " . $this->table_name . " 
                  SET estado = :estado, 
                      fecha_respuesta = NOW(), 
                      usuario_respuesta_id = :usuario_respuesta_id,
                      comentario_respuesta = :comentario_respuesta
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":estado", $estado);
        $stmt->bindParam(":usuario_respuesta_id", $usuario_respuesta_id);
        $stmt->bindParam(":comentario_respuesta", $comentario);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
    
    // Obtener estadísticas
    public function obtenerEstadisticas() {
        $query = "SELECT 
                    COUNT(*) as total_solicitudes,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'aprobada' THEN 1 ELSE 0 END) as aprobadas,
                    SUM(CASE WHEN estado = 'rechazada' THEN 1 ELSE 0 END) as rechazadas,
                    SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas
                  FROM " . $this->table_name;
        
        // Filtrar por sede si no es superadmin
        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $query .= " WHERE sede_id = :sede_id";
            }
        }
        
        $stmt = $this->conn->prepare($query);
        
        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $stmt->bindParam(":sede_id", $sede_actual);
            }
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Obtener solicitudes recientes (últimos 7 días)
    public function obtenerRecientes($dias = 7) {
        $query = "SELECT s.*, u.nombre_completo as tecnico_nombre
                  FROM " . $this->table_name . " s
                  LEFT JOIN usuarios u ON s.tecnico_id = u.id
                  WHERE s.fecha_solicitud >= DATE_SUB(NOW(), INTERVAL :dias DAY)";
        
        // Filtrar por sede si no es superadmin
        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $query .= " AND s.sede_id = :sede_id";
            }
        }
        
        $query .= " ORDER BY s.fecha_solicitud DESC LIMIT 10";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":dias", $dias);
        
        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $stmt->bindParam(":sede_id", $sede_actual);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
