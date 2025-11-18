<?php
/**
 * Modelo de Asignación de Materiales a Técnicos
 */

class AsignacionTecnico {
    private $conn;
    private $table_name = "stock_tecnicos";
    
    public $id;
    public $tecnico_id;
    public $material_id;
    public $cantidad;
    public $motivo;
    public $usuario_asignador_id;
    public $fecha_asignacion;
    public $observaciones;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Crear asignación de materiales a técnico
     * Procesa múltiples materiales en una sola asignación
     */
    public function crear($tecnico_id, $usuario_id, $materiales, $comentario = '') {
        try {
            if (empty($tecnico_id) || empty($materiales)) {
                return "Faltan datos requeridos (técnico o materiales)";
            }
            
            // Validar que al menos un material tenga cantidad válida
            $tiene_material_valido = false;
            foreach ($materiales as $material) {
                if (!empty($material['id']) && !empty($material['cantidad']) && (int)$material['cantidad'] > 0) {
                    $tiene_material_valido = true;
                    break;
                }
            }
            
            if (!$tiene_material_valido) {
                return "Debe seleccionar al menos un material con cantidad válida";
            }
            
            $this->conn->beginTransaction();
            
            try {
                // Procesar cada material
                foreach ($materiales as $material) {
                    if (empty($material['id']) || empty($material['cantidad'])) {
                        continue;
                    }
                    
                    $material_id = (int)$material['id'];
                    $cantidad = (int)$material['cantidad'];
                    
                    if ($cantidad <= 0) {
                        continue;
                    }
                    
                    // Verificar stock disponible
                    $query_stock = "SELECT stock_actual, sede_id, nombre FROM materiales WHERE id = :material_id AND estado = 'activo'";
                    
                    if (!esSuperAdmin()) {
                        $sede_actual = obtenerSedeActual();
                        if ($sede_actual) {
                            $query_stock .= " AND sede_id = :sede_id";
                        }
                    }
                    
                    $stmt_stock = $this->conn->prepare($query_stock);
                    $stmt_stock->bindParam(":material_id", $material_id);
                    
                    if (!esSuperAdmin()) {
                        $sede_actual = obtenerSedeActual();
                        if ($sede_actual) {
                            $stmt_stock->bindParam(":sede_id", $sede_actual);
                        }
                    }
                    
                    $stmt_stock->execute();
                    $material_data = $stmt_stock->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$material_data) {
                        return "Material ID {$material_id} no encontrado o no está disponible";
                    }
                    
                    if ($material_data['stock_actual'] < $cantidad) {
                        return "Stock insuficiente para {$material_data['nombre']}. Disponible: {$material_data['stock_actual']}, Solicitado: {$cantidad}";
                    }
                    
                    $sede_id = $material_data['sede_id'];
                    
                    // Reducir stock del material
                    $query_update = "UPDATE materiales SET stock_actual = stock_actual - :cantidad WHERE id = :material_id";
                    
                    if (!esSuperAdmin()) {
                        $sede_actual = obtenerSedeActual();
                        if ($sede_actual) {
                            $query_update .= " AND sede_id = :sede_id";
                        }
                    }
                    
                    $stmt_update = $this->conn->prepare($query_update);
                    $stmt_update->bindParam(":material_id", $material_id);
                    $stmt_update->bindParam(":cantidad", $cantidad);
                    
                    if (!esSuperAdmin()) {
                        $sede_actual = obtenerSedeActual();
                        if ($sede_actual) {
                            $stmt_update->bindParam(":sede_id", $sede_actual);
                        }
                    }
                    
                    if (!$stmt_update->execute()) {
                        return "Error al actualizar stock del material {$material_id}";
                    }
                    
                    // Insertar o actualizar stock del técnico
                    $query_tech = "INSERT INTO " . $this->table_name . " (tecnico_id, material_id, cantidad, fecha_asignacion, sede_id)
                                   VALUES (:tecnico_id, :material_id, :cantidad, CURRENT_TIMESTAMP, :sede_id)
                                   ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad), fecha_asignacion = CURRENT_TIMESTAMP";
                    
                    $stmt_tech = $this->conn->prepare($query_tech);
                    $stmt_tech->bindParam(":tecnico_id", $tecnico_id);
                    $stmt_tech->bindParam(":material_id", $material_id);
                    $stmt_tech->bindParam(":cantidad", $cantidad);
                    $stmt_tech->bindParam(":sede_id", $sede_id);
                    
                    if (!$stmt_tech->execute()) {
                        return "Error al asignar material al técnico";
                    }
                    
                    // Registrar movimiento
                    $query_mov = "INSERT INTO movimientos_inventario 
                                  (material_id, tipo_movimiento, cantidad, motivo, usuario_id, tecnico_asignado_id, fecha_movimiento, sede_id)
                                  VALUES (:material_id, 'salida', :cantidad, :motivo, :usuario_id, :tecnico_id, CURRENT_TIMESTAMP, :sede_id)";
                    
                    $stmt_mov = $this->conn->prepare($query_mov);
                    $stmt_mov->bindParam(":material_id", $material_id);
                    $stmt_mov->bindParam(":cantidad", $cantidad);
                    $stmt_mov->bindParam(":motivo", $comentario);
                    $stmt_mov->bindParam(":usuario_id", $usuario_id);
                    $stmt_mov->bindParam(":tecnico_id", $tecnico_id);
                    $stmt_mov->bindParam(":sede_id", $sede_id);
                    
                    if (!$stmt_mov->execute()) {
                        return "Error al registrar movimiento del material";
                    }
                }
                
                $this->conn->commit();
                return true;
                
            } catch (Exception $e) {
                $this->conn->rollBack();
                return "Error en la transacción: " . $e->getMessage();
            }
            
        } catch (Exception $e) {
            error_log("Error al crear asignación: " . $e->getMessage());
            return "Error al crear asignación: " . $e->getMessage();
        }
    }
    
    /**
     * Obtener asignaciones por técnico
     */
    public function obtenerPorTecnico($tecnico_id) {
        $query = "SELECT st.*, m.codigo, m.nombre as material_nombre, m.unidad, m.costo_unitario,
                        u.nombre_completo as tecnico_nombre
                 FROM " . $this->table_name . " st
                 INNER JOIN materiales m ON st.material_id = m.id
                 INNER JOIN usuarios u ON st.tecnico_id = u.id
                 WHERE st.tecnico_id = :tecnico_id AND st.cantidad > 0
                 ORDER BY st.fecha_asignacion DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":tecnico_id", $tecnico_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener todos los técnicos con asignaciones
     */
    public function obtenerTecnicosConAsignaciones() {
        $query = "SELECT 
                        CONCAT('ASG-', u.id, '-', DATE_FORMAT(MAX(st.fecha_asignacion), '%Y%m%d')) as codigo_asignacion,
                        u.nombre_completo as tecnico_nombre,
                        MAX(st.fecha_asignacion) as fecha_asignacion,
                        COALESCE(uj.nombre_completo, 'N/A') as jefe_almacen_nombre,
                        COUNT(DISTINCT st.material_id) as total_materiales,
                        COALESCE(SUM(st.cantidad * COALESCE(m.costo_unitario, 0)), 0) as valor_total,
                        u.id as tecnico_id, u.email, u.sede_id
                 FROM " . $this->table_name . " st
                 INNER JOIN usuarios u ON st.tecnico_id = u.id
                 LEFT JOIN materiales m ON st.material_id = m.id
                 LEFT JOIN usuarios uj ON uj.rol_id = 2 AND uj.sede_id = u.sede_id
                 WHERE st.cantidad > 0";
        
        // Filtrar por sede si no es superadmin
        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $query .= " AND u.sede_id = :sede_id";
            }
        }
        
        $query .= " GROUP BY u.id, u.nombre_completo, u.email, u.sede_id
                   ORDER BY fecha_asignacion DESC";
        
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
    
    /**
     * Obtener materiales disponibles para asignación
     */
    public function obtenerMaterialesDisponibles() {
        $query = "SELECT m.*, c.nombre as categoria_nombre
                 FROM materiales m
                 LEFT JOIN categorias_materiales c ON m.categoria_id = c.id
                 WHERE m.estado = 'activo' AND m.stock_actual > 0";
        
        // Filtrar por sede si no es superadmin
        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $query .= " AND m.sede_id = :sede_id";
            }
        }
        
        $query .= " ORDER BY m.nombre";
        
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
    
    /**
     * Obtener técnicos disponibles para asignación
     */
    public function obtenerTecnicosDisponibles() {
        $query = "SELECT id, nombre_completo, email, rol_id, sede_id
                 FROM usuarios 
                 WHERE estado = 'activo' AND rol_id = 4";
        
        // Filtrar por sede si no es superadmin
        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $query .= " AND sede_id = :sede_id";
            }
        }
        
        $query .= " ORDER BY nombre_completo";
        
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
    
    /**
     * Devolver material al almacén
     */
    public function devolverMaterial($cantidad_devolver) {
        try {
            if ($cantidad_devolver <= 0) {
                throw new Exception("La cantidad debe ser mayor a cero");
            }
            
            // Verificar stock del técnico
            $query = "SELECT cantidad FROM " . $this->table_name . " 
                      WHERE tecnico_id = :tecnico_id AND material_id = :material_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":tecnico_id", $this->tecnico_id);
            $stmt->bindParam(":material_id", $this->material_id);
            $stmt->execute();
            
            $stock_actual = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$stock_actual || $stock_actual['cantidad'] < $cantidad_devolver) {
                throw new Exception("Stock insuficiente del técnico para devolver");
            }
            
            $this->conn->beginTransaction();
            
            try {
                // Devolver al almacén
                $query = "UPDATE materiales SET stock_actual = stock_actual + :cantidad WHERE id = :material_id";
                
                // Filtrar por sede si no es superadmin
                if (!esSuperAdmin()) {
                    $sede_actual = obtenerSedeActual();
                    if ($sede_actual) {
                        $query .= " AND sede_id = :sede_id";
                    }
                }
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":material_id", $this->material_id);
                $stmt->bindParam(":cantidad", $cantidad_devolver);
                
                if (!esSuperAdmin()) {
                    $sede_actual = obtenerSedeActual();
                    if ($sede_actual) {
                        $stmt->bindParam(":sede_id", $sede_actual);
                    }
                }
                
                $stmt->execute();
                
                // Reducir stock del técnico
                $nueva_cantidad = $stock_actual['cantidad'] - $cantidad_devolver;
                
                if ($nueva_cantidad > 0) {
                    $query = "UPDATE " . $this->table_name . " 
                              SET cantidad = :cantidad, fecha_asignacion = CURRENT_TIMESTAMP
                              WHERE tecnico_id = :tecnico_id AND material_id = :material_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(":tecnico_id", $this->tecnico_id);
                    $stmt->bindParam(":material_id", $this->material_id);
                    $stmt->bindParam(":cantidad", $nueva_cantidad);
                    $stmt->execute();
                } else {
                    // Eliminar registro si stock es cero
                    $query = "DELETE FROM " . $this->table_name . " 
                              WHERE tecnico_id = :tecnico_id AND material_id = :material_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(":tecnico_id", $this->tecnico_id);
                    $stmt->bindParam(":material_id", $this->material_id);
                    $stmt->execute();
                }
                
                // Registrar movimiento
                $motivo_registro = 'Devolución de técnico';
                if (!empty($this->observaciones)) {
                    $motivo_registro .= ': ' . $this->observaciones;
                }
                
                $query_mov = "INSERT INTO movimientos_inventario 
                              (material_id, tipo_movimiento, cantidad, motivo, usuario_id, tecnico_asignado_id, fecha_movimiento, sede_id)
                              VALUES (:material_id, 'entrada', :cantidad, :motivo, :usuario_id, :tecnico_id, CURRENT_TIMESTAMP, :sede_id)";
                
                $stmt_mov = $this->conn->prepare($query_mov);
                
                $sede_actual = obtenerSedeActual();

                $stmt_mov->bindParam(":material_id", $this->material_id);
                $stmt_mov->bindParam(":cantidad", $cantidad_devolver);
                $stmt_mov->bindParam(":motivo", $motivo_registro);
                $stmt_mov->bindParam(":usuario_id", $this->usuario_asignador_id);
                $stmt_mov->bindParam(":tecnico_id", $this->tecnico_id);
                $stmt_mov->bindParam(":sede_id", $sede_actual);
                
                $stmt_mov->execute();

                $this->conn->commit();
                return true;
                
            } catch (Exception $e) {
                $this->conn->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Error al devolver material: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar stock disponible
     */
    private function verificarStockDisponible() {
        $query = "SELECT stock_actual FROM materiales WHERE id = :material_id AND estado = 'activo'";
        
        // Filtrar por sede si no es superadmin
        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $query .= " AND sede_id = :sede_id";
            }
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":material_id", $this->material_id);
        
        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $stmt->bindParam(":sede_id", $sede_actual);
            }
        }
        
        $stmt->execute();
        
        $material = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$material) {
            return false;
        }
        
        return $material['stock_actual'] >= $this->cantidad;
    }
    
    /**
     * Registrar movimiento
     */
    private function registrarMovimiento($tipo = 'salida') {
        try {
            $query = "INSERT INTO movimientos_inventario 
                      (material_id, tipo_movimiento, cantidad, motivo, usuario_id, tecnico_asignado_id, observaciones, fecha_movimiento, sede_id)
                      VALUES (:material_id, :tipo_movimiento, :cantidad, :motivo, :usuario_id, :tecnico_id, :observaciones, CURRENT_TIMESTAMP, :sede_id)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":material_id", $this->material_id);
            $stmt->bindParam(":tipo_movimiento", $tipo);
            $stmt->bindParam(":cantidad", $this->cantidad);
            $stmt->bindParam(":motivo", $this->motivo);
            $stmt->bindParam(":usuario_id", $this->usuario_asignador_id);
            $stmt->bindParam(":tecnico_id", $this->tecnico_id);
            $stmt->bindParam(":observaciones", $this->observaciones);
            
            // Obtener y bind sede_id
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $stmt->bindParam(":sede_id", $sede_actual);
            } else {
                // Si es superadmin, obtener del material
                $query_material = "SELECT sede_id FROM materiales WHERE id = :material_id";
                $stmt_material = $this->conn->prepare($query_material);
                $stmt_material->bindParam(":material_id", $this->material_id);
                $stmt_material->execute();
                $material = $stmt_material->fetch(PDO::FETCH_ASSOC);
                $sede_id = $material ? $material['sede_id'] : null;
                $stmt->bindParam(":sede_id", $sede_id);
            }
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error al registrar movimiento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener resumen de asignaciones
     */
    public function obtenerResumen() {
        $query = "SELECT 
                    COUNT(DISTINCT st.tecnico_id) as total_tecnicos,
                    COUNT(DISTINCT st.material_id) as total_materiales_asignados,
                    SUM(st.cantidad) as total_items_asignados,
                    SUM(st.cantidad * m.costo_unitario) as valor_total_asignado
                  FROM " . $this->table_name . " st
                  INNER JOIN materiales m ON st.material_id = m.id
                  INNER JOIN usuarios u ON st.tecnico_id = u.id
                  WHERE st.cantidad > 0";
        
        // Filtrar por sede si no es superadmin
        if (!esSuperAdmin()) {
            $sede_actual = obtenerSedeActual();
            if ($sede_actual) {
                $query .= " AND u.sede_id = :sede_id";
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
}
