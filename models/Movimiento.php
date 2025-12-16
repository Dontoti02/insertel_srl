<?php
class Movimiento
{
    private $conn;
    private $table_name = "movimientos_inventario";

    public $id;
    public $material_id;
    public $tipo_movimiento;
    public $cantidad;
    public $motivo;
    public $usuario_id;
    public $sede_id;
    public $tecnico_asignado_id;
    public $fecha_movimiento;
    public $documento_referencia;
    public $observaciones;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Obtener todos los movimientos
    public function obtenerTodos($limit = 50)
    {
        $query = "SELECT mi.*, m.nombre as material_nombre, u.nombre_completo as usuario_nombre,
                         ut.nombre_completo as tecnico_nombre
                  FROM " . $this->table_name . " mi
                  LEFT JOIN materiales m ON mi.material_id = m.id
                  LEFT JOIN usuarios u ON mi.usuario_id = u.id
                  LEFT JOIN usuarios ut ON mi.tecnico_asignado_id = ut.id";

        // Filtrar por sede si no es superadmin
        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $query .= " WHERE mi.sede_id = :sede_id";
            }
        }

        $query .= " ORDER BY mi.fecha_movimiento DESC LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);

        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $stmt->bindParam(":sede_id", $sede_actual);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener movimientos por mes (últimos 6 meses)
    public function obtenerMovimientosPorMes($meses = 6)
    {
        $query = "SELECT 
                    DATE_FORMAT(mi.fecha_movimiento, '%Y-%m') as mes,
                    COUNT(*) as total_movimientos,
                    SUM(CASE WHEN mi.tipo_movimiento = 'entrada' THEN 1 ELSE 0 END) as entradas,
                    SUM(CASE WHEN mi.tipo_movimiento = 'salida' THEN 1 ELSE 0 END) as salidas
                  FROM " . $this->table_name . " mi
                  WHERE mi.fecha_movimiento >= DATE_SUB(NOW(), INTERVAL :meses MONTH)";

        // Filtrar por sede si no es superadmin
        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $query .= " AND mi.sede_id = :sede_id";
            }
        }

        $query .= " GROUP BY DATE_FORMAT(mi.fecha_movimiento, '%Y-%m')
                   ORDER BY mes DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":meses", $meses, PDO::PARAM_INT);

        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $stmt->bindParam(":sede_id", $sede_actual);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener movimientos por material
    public function obtenerPorMaterial($material_id, $limit = 20)
    {
        $query = "SELECT mi.*, u.nombre_completo as usuario_nombre,
                         ut.nombre_completo as tecnico_nombre
                  FROM " . $this->table_name . " mi
                  LEFT JOIN usuarios u ON mi.usuario_id = u.id
                  LEFT JOIN usuarios ut ON mi.tecnico_asignado_id = ut.id
                  WHERE mi.material_id = :material_id";

        // Filtrar por sede si no es superadmin
        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $query .= " AND mi.sede_id = :sede_id";
            }
        }

        $query .= " ORDER BY mi.fecha_movimiento DESC LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":material_id", $material_id);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);

        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $stmt->bindParam(":sede_id", $sede_actual);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener movimientos por técnico
    public function obtenerPorTecnico($tecnico_id, $limit = 20)
    {
        $query = "SELECT mi.*, m.nombre as material_nombre, u.nombre_completo as usuario_nombre
                  FROM " . $this->table_name . " mi
                  LEFT JOIN materiales m ON mi.material_id = m.id
                  LEFT JOIN usuarios u ON mi.usuario_id = u.id
                  WHERE mi.tecnico_asignado_id = :tecnico_id";

        // Filtrar por sede si no es superadmin
        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $query .= " AND mi.sede_id = :sede_id";
            }
        }

        $query .= " ORDER BY mi.fecha_movimiento DESC LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":tecnico_id", $tecnico_id);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);

        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $stmt->bindParam(":sede_id", $sede_actual);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Crear nuevo movimiento
    public function crear()
    {
        // Obtener sede del material o del usuario
        if (!$this->sede_id) {
            $query_sede = "SELECT sede_id FROM materiales WHERE id = :material_id";
            $stmt_sede = $this->conn->prepare($query_sede);
            $stmt_sede->bindParam(":material_id", $this->material_id);
            $stmt_sede->execute();
            $material = $stmt_sede->fetch(PDO::FETCH_ASSOC);

            if ($material) {
                $this->sede_id = $material['sede_id'];
            }
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  (material_id, tipo_movimiento, cantidad, motivo, usuario_id, sede_id, 
                   tecnico_asignado_id, documento_referencia, observaciones)
                  VALUES (:material_id, :tipo_movimiento, :cantidad, :motivo, :usuario_id, 
                          :sede_id, :tecnico_asignado_id, :documento_referencia, :observaciones)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":material_id", $this->material_id);
        $stmt->bindParam(":tipo_movimiento", $this->tipo_movimiento);
        $stmt->bindParam(":cantidad", $this->cantidad);
        $stmt->bindParam(":motivo", $this->motivo);
        $stmt->bindParam(":usuario_id", $this->usuario_id);
        $stmt->bindParam(":sede_id", $this->sede_id);
        $stmt->bindParam(":tecnico_asignado_id", $this->tecnico_asignado_id);
        $stmt->bindParam(":documento_referencia", $this->documento_referencia);
        $stmt->bindParam(":observaciones", $this->observaciones);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();

            // Actualizar stock del material
            $this->actualizarStockMaterial();

            // Si es salida a técnico, actualizar stock del técnico
            if ($this->tipo_movimiento == 'salida' && $this->tecnico_asignado_id) {
                $this->actualizarStockTecnico();
            }

            return true;
        }

        return false;
    }

    // Actualizar stock del material
    private function actualizarStockMaterial()
    {
        $query = "UPDATE materiales 
                  SET stock_actual = ";

        if ($this->tipo_movimiento == 'entrada') {
            $query .= "stock_actual + :cantidad";
        } else {
            $query .= "stock_actual - :cantidad";
        }

        $query .= " WHERE id = :material_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":cantidad", $this->cantidad);
        $stmt->bindParam(":material_id", $this->material_id);

        return $stmt->execute();
    }

    // Actualizar stock del técnico
    private function actualizarStockTecnico()
    {
        $query = "INSERT INTO stock_tecnicos 
                  (tecnico_id, material_id, cantidad, sede_id)
                  VALUES (:tecnico_id, :material_id, :cantidad, :sede_id)
                  ON DUPLICATE KEY UPDATE 
                  cantidad = cantidad + :cantidad";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":tecnico_id", $this->tecnico_asignado_id);
        $stmt->bindParam(":material_id", $this->material_id);
        $stmt->bindParam(":cantidad", $this->cantidad);
        $stmt->bindParam(":sede_id", $this->sede_id);

        return $stmt->execute();
    }

    // Obtener estadísticas
    public function obtenerEstadisticas()
    {
        $query = "SELECT 
                    COUNT(*) as total_movimientos,
                    SUM(CASE WHEN tipo_movimiento = 'entrada' THEN 1 ELSE 0 END) as total_entradas,
                    SUM(CASE WHEN tipo_movimiento = 'salida' THEN 1 ELSE 0 END) as total_salidas,
                    SUM(CASE WHEN tipo_movimiento = 'ajuste' THEN 1 ELSE 0 END) as total_ajustes
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

    // Obtener movimientos recientes
    public function obtenerRecientes($limit = 10)
    {
        return $this->obtenerTodos($limit);
    }

    /**
     * Obtener historial de devoluciones por técnico
     */
    public function obtenerDevolucionesPorTecnico($tecnico_id)
    {
        $query = "SELECT 
                        mi.fecha_movimiento,
                        mi.cantidad,
                        mi.motivo,
                        m.nombre as material_nombre
                  FROM " . $this->table_name . " mi
                  JOIN materiales m ON mi.material_id = m.id
                  WHERE mi.tecnico_asignado_id = :tecnico_id 
                  AND mi.tipo_movimiento = 'entrada'
                  AND mi.motivo LIKE 'Devolución de técnico%'";

        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $query .= " AND mi.sede_id = :sede_id";
            }
        }

        $query .= " ORDER BY mi.fecha_movimiento DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":tecnico_id", $tecnico_id);

        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $stmt->bindParam(":sede_id", $sede_actual);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener movimiento por ID
    public function obtenerPorId($id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Eliminar movimiento
    public function eliminar($id)
    {
        // Primero revertir el stock
        $movimiento = $this->obtenerPorId($id);
        if ($movimiento) {
            // Lógica inversa para revertir stock
            $cantidad = $movimiento['cantidad'];
            $material_id = $movimiento['material_id'];
            $tipo = $movimiento['tipo_movimiento'];

            $query_stock = "UPDATE materiales SET stock_actual = ";
            if ($tipo == 'entrada') {
                $query_stock .= "stock_actual - :cantidad"; // Si fue entrada, restamos
            } else {
                $query_stock .= "stock_actual + :cantidad"; // Si fue salida, sumamos
            }
            $query_stock .= " WHERE id = :material_id";

            $stmt_stock = $this->conn->prepare($query_stock);
            $stmt_stock->bindParam(":cantidad", $cantidad);
            $stmt_stock->bindParam(":material_id", $material_id);
            $stmt_stock->execute();

            // Si hay técnico asignado y fue salida, revertir stock técnico
            if ($tipo == 'salida' && $movimiento['tecnico_asignado_id']) {
                $query_tec = "UPDATE stock_tecnicos SET cantidad = cantidad - :cantidad 
                              WHERE tecnico_id = :tecnico_id AND material_id = :material_id";
                $stmt_tec = $this->conn->prepare($query_tec);
                $stmt_tec->bindParam(":cantidad", $cantidad);
                $stmt_tec->bindParam(":tecnico_id", $movimiento['tecnico_asignado_id']);
                $stmt_tec->bindParam(":material_id", $material_id);
                $stmt_tec->execute();
            }
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    // Actualizar movimiento (solo campos descriptivos)
    public function actualizar($id, $motivo, $documento_referencia, $observaciones)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET motivo = :motivo,
                      documento_referencia = :documento_referencia,
                      observaciones = :observaciones
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":motivo", $motivo);
        $stmt->bindParam(":documento_referencia", $documento_referencia);
        $stmt->bindParam(":observaciones", $observaciones);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }
}
