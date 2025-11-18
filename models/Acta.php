<?php
/**
 * Modelo de Acta Técnica
 */
class Acta {
    private $conn;
    private $table_name = "actas_tecnicas";

    public $id;
    public $codigo_acta;
    public $tecnico_id; // ID del técnico que realizó el servicio
    public $usuario_reporta_id; // ID del asistente que reporta el acta
    public $cliente;
    public $fecha_servicio;
    public $tipo_servicio;
    public $descripcion;
    public $estado; // Ej: 'pendiente', 'aprobada', 'rechazada'
    public $observaciones_admin; // Comentarios del administrador
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Generar un código de acta único
     */
    public function generarCodigoActa() {
        $prefix = "ACT-";
        $date_part = date("Ymd");
        $random_part = strtoupper(substr(uniqid(), -4)); // Últimos 4 caracteres de un ID único
        return $prefix . $date_part . "-" . $random_part;
    }

    /**
     * Crear una nueva acta
     */
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET
                    codigo_acta = :codigo_acta,
                    tecnico_id = :tecnico_id,
                    usuario_reporta_id = :usuario_reporta_id,
                    cliente = :cliente,
                    fecha_servicio = :fecha_servicio,
                    tipo_servicio = :tipo_servicio,
                    descripcion = :descripcion,
                    estado = :estado";

        $stmt = $this->conn->prepare($query);

        // Limpiar y bindear valores
        $this->codigo_acta = htmlspecialchars(strip_tags($this->codigo_acta));
        $this->tecnico_id = htmlspecialchars(strip_tags($this->tecnico_id));
        $this->usuario_reporta_id = htmlspecialchars(strip_tags($this->usuario_reporta_id));
        $this->cliente = htmlspecialchars(strip_tags($this->cliente));
        $this->fecha_servicio = htmlspecialchars(strip_tags($this->fecha_servicio));
        $this->tipo_servicio = htmlspecialchars(strip_tags($this->tipo_servicio));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->estado = htmlspecialchars(strip_tags($this->estado));

        $stmt->bindParam(":codigo_acta", $this->codigo_acta);
        $stmt->bindParam(":tecnico_id", $this->tecnico_id);
        $stmt->bindParam(":usuario_reporta_id", $this->usuario_reporta_id);
        $stmt->bindParam(":cliente", $this->cliente);
        $stmt->bindParam(":fecha_servicio", $this->fecha_servicio);
        $stmt->bindParam(":tipo_servicio", $this->tipo_servicio);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":estado", $this->estado);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Obtener todas las actas (para administrador)
     */
    public function obtenerTodas($estado = null) {
        $query = "SELECT a.*, u_tec.nombre_completo as tecnico_nombre, u_rep.nombre_completo as reporta_nombre,
                         u_rep.sede_id as sede_reporta_id
                  FROM " . $this->table_name . " a
                  LEFT JOIN usuarios u_tec ON a.tecnico_id = u_tec.id
                  LEFT JOIN usuarios u_rep ON a.usuario_reporta_id = u_rep.id";
        
        $conditions = [];
        $params = [];

        // Filtrar por sede si no es superadmin
        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $conditions[] = "u_rep.sede_id = :sede_actual_id";
                $params[':sede_actual_id'] = $sede_actual;
            }
        }

        if ($estado !== null) {
            if (is_array($estado)) {
                $placeholders = [];
                foreach ($estado as $index => $s) {
                    $placeholder = ":estado" . $index;
                    $placeholders[] = $placeholder;
                    $params[$placeholder] = $s;
                }
                $conditions[] = "a.estado IN (" . implode(", ", $placeholders) . ")";
            } else {
                $conditions[] = "a.estado = :estado";
                $params[':estado'] = $estado;
            }
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY a.created_at DESC";

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener actas reportadas por un asistente
     */
    public function obtenerPorAsistente($asistente_id) {
        $query = "SELECT a.*, u_tec.nombre_completo as tecnico_nombre
                  FROM " . $this->table_name . " a
                  LEFT JOIN usuarios u_tec ON a.tecnico_id = u_tec.id
                  WHERE a.usuario_reporta_id = :asistente_id
                  ORDER BY a.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":asistente_id", $asistente_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar el estado de un acta
     */
    public function actualizarEstado($id, $estado, $observaciones_admin = null) {
        $query = "UPDATE " . $this->table_name . "
                  SET estado = :estado, updated_at = CURRENT_TIMESTAMP";
        
        if ($observaciones_admin !== null) {
            $query .= ", observaciones_admin = :observaciones_admin";
        }
        
        $query .= " WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":estado", $estado);
        $stmt->bindParam(":id", $id);
        if ($observaciones_admin !== null) {
            $stmt->bindParam(":observaciones_admin", $observaciones_admin);
        }

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>
