<?php
/**
 * Modelo de Material
 */

class Material {
    private $conn;
    private $table_name = "materiales";

    public $id;
    public $codigo;
    public $nombre;
    public $descripcion;
    public $categoria_id;
    public $unidad;
    public $proveedor_id;
    public $costo_unitario;
    public $stock_actual;
    public $stock_minimo;
    public $stock_maximo;
    public $ubicacion;
    public $estado;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Crear material
     */
    public function crear() {
        $sede_id = obtenerSedeActual();
        
        $query = "INSERT INTO " . $this->table_name . " 
                  (codigo, nombre, descripcion, categoria_id, unidad, proveedor_id, 
                   costo_unitario, stock_actual, stock_minimo, stock_maximo, ubicacion, estado, sede_id) 
                  VALUES (:codigo, :nombre, :descripcion, :categoria_id, :unidad, :proveedor_id,
                          :costo_unitario, :stock_actual, :stock_minimo, :stock_maximo, :ubicacion, :estado, :sede_id)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":codigo", $this->codigo);
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":categoria_id", $this->categoria_id);
        $stmt->bindParam(":unidad", $this->unidad);
        $stmt->bindParam(":proveedor_id", $this->proveedor_id);
        $stmt->bindParam(":costo_unitario", $this->costo_unitario);
        $stmt->bindParam(":stock_actual", $this->stock_actual);
        $stmt->bindParam(":stock_minimo", $this->stock_minimo);
        $stmt->bindParam(":stock_maximo", $this->stock_maximo);
        $stmt->bindParam(":ubicacion", $this->ubicacion);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":sede_id", $sede_id);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Obtener todos los materiales
     */
    public function obtenerTodos($filtros = []) {
        $where = ["1=1"]; // Cambiar condición base para permitir filtrar por estado
        $params = [];

        // Filtro de estado
        if (isset($filtros['estado']) && $filtros['estado'] !== '') {
            $where[] = "m.estado = :estado";
            $params['estado'] = $filtros['estado'];
        } else if (!isset($filtros['estado'])) {
            // Comportamiento por defecto si 'estado' no está en los filtros
            $where[] = "m.estado = 'activo'";
        }

        if (!empty($filtros['categoria_id'])) {
            $where[] = "m.categoria_id = :categoria_id";
            $params['categoria_id'] = $filtros['categoria_id'];
        }

        if (!empty($filtros['buscar'])) {
            $where[] = "(m.nombre LIKE :buscar1 OR m.codigo LIKE :buscar2 OR m.descripcion LIKE :buscar3)";
            $params['buscar1'] = '%' . $filtros['buscar'] . '%';
            $params['buscar2'] = '%' . $filtros['buscar'] . '%';
            $params['buscar3'] = '%' . $filtros['buscar'] . '%';
        }

        if (isset($filtros['stock_bajo']) && $filtros['stock_bajo']) {
            $where[] = "m.stock_actual <= m.stock_minimo";
        }

        if (isset($filtros['stockMayorQue']) && is_numeric($filtros['stockMayorQue'])) {
            $where[] = "m.stock_actual > :stockMayorQue";
            $params['stockMayorQue'] = $filtros['stockMayorQue'];
        }

        if (!esSuperAdmin() && empty($filtros['ignorar_sede']) && obtenerSedeActual()) {
            $where[] = "m.sede_id = :sede_id";
            $params['sede_id'] = obtenerSedeActual();
        }

        $query = "SELECT m.*, c.nombre as categoria_nombre, p.nombre as proveedor_nombre FROM " . $this->table_name . " m LEFT JOIN categorias_materiales c ON m.categoria_id = c.id LEFT JOIN proveedores p ON m.proveedor_id = p.id WHERE " . implode(' AND ', $where) . " ORDER BY m.nombre";

        // Agregar paginación si se especifica
        if (isset($filtros['limit']) && isset($filtros['offset'])) {
            $query .= " LIMIT :limit OFFSET :offset";
        }

        $stmt = $this->conn->prepare($query);
        
        // Bind de parámetros de filtros
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        // Bind de parámetros de paginación si existen
        if (isset($filtros['limit']) && isset($filtros['offset'])) {
            $stmt->bindValue(':limit', (int)$filtros['limit'], PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$filtros['offset'], PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Contar total de materiales con filtros
     */
    public function contarTodos($filtros = []) {
        $where = ["1=1"]; // Cambiar condición base para permitir filtrar por estado
        $params = [];

        // Filtro de estado
        if (isset($filtros['estado']) && $filtros['estado'] !== '') {
            $where[] = "m.estado = :estado";
            $params['estado'] = $filtros['estado'];
        } else if (!isset($filtros['estado'])) {
            // Comportamiento por defecto si 'estado' no está en los filtros
            $where[] = "m.estado = 'activo'";
        }

        if (!empty($filtros['categoria_id'])) {
            $where[] = "m.categoria_id = :categoria_id";
            $params['categoria_id'] = $filtros['categoria_id'];
        }

        if (!empty($filtros['buscar'])) {
            $where[] = "(m.nombre LIKE :buscar1 OR m.codigo LIKE :buscar2 OR m.descripcion LIKE :buscar3)";
            $params['buscar1'] = '%' . $filtros['buscar'] . '%';
            $params['buscar2'] = '%' . $filtros['buscar'] . '%';
            $params['buscar3'] = '%' . $filtros['buscar'] . '%';
        }

        if (isset($filtros['stock_bajo']) && $filtros['stock_bajo']) {
            $where[] = "m.stock_actual <= m.stock_minimo";
        }

        if (isset($filtros['stockMayorQue']) && is_numeric($filtros['stockMayorQue'])) {
            $where[] = "m.stock_actual > :stockMayorQue";
            $params['stockMayorQue'] = $filtros['stockMayorQue'];
        }

        if (!esSuperAdmin() && empty($filtros['ignorar_sede']) && obtenerSedeActual()) {
            $where[] = "m.sede_id = :sede_id";
            $params['sede_id'] = obtenerSedeActual();
        }

        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " m LEFT JOIN categorias_materiales c ON m.categoria_id = c.id LEFT JOIN proveedores p ON m.proveedor_id = p.id WHERE " . implode(' AND ', $where);

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Obtener material por ID
     */
    public function obtenerPorId($id) {
        $query = "SELECT m.*, c.nombre as categoria_nombre, p.nombre as proveedor_nombre 
                  FROM " . $this->table_name . " m
                  LEFT JOIN categorias_materiales c ON m.categoria_id = c.id
                  LEFT JOIN proveedores p ON m.proveedor_id = p.id
                  WHERE m.id = :id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar material
     */
    public function actualizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET codigo = :codigo,
                      nombre = :nombre,
                      descripcion = :descripcion,
                      categoria_id = :categoria_id,
                      unidad = :unidad,
                      proveedor_id = :proveedor_id,
                      costo_unitario = :costo_unitario,
                      stock_actual = :stock_actual,
                      stock_minimo = :stock_minimo,
                      stock_maximo = :stock_maximo,
                      ubicacion = :ubicacion,
                      estado = :estado,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":codigo", $this->codigo);
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":categoria_id", $this->categoria_id);
        $stmt->bindParam(":unidad", $this->unidad);
        $stmt->bindParam(":proveedor_id", $this->proveedor_id);
        $stmt->bindParam(":costo_unitario", $this->costo_unitario);
        $stmt->bindParam(":stock_actual", $this->stock_actual);
        $stmt->bindParam(":stock_minimo", $this->stock_minimo);
        $stmt->bindParam(":stock_maximo", $this->stock_maximo);
        $stmt->bindParam(":ubicacion", $this->ubicacion);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    /**
     * Actualizar stock
     */
    public function actualizarStock($material_id, $cantidad, $operacion = 'sumar') {
        if ($operacion == 'sumar') {
            $query = "UPDATE " . $this->table_name . " 
                      SET stock_actual = stock_actual + :cantidad 
                      WHERE id = :id";
        } else {
            $query = "UPDATE " . $this->table_name . " 
                      SET stock_actual = stock_actual - :cantidad 
                      WHERE id = :id AND stock_actual >= :cantidad";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":cantidad", $cantidad);
        $stmt->bindParam(":id", $material_id);

        return $stmt->execute();
    }

    /**
     * Verificar si existe un código de material
     */
    public function existeCodigo($codigo, $excluir_id = null) {
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
     * Obtener materiales con stock bajo
     */
    public function obtenerStockBajo() {
        $where = ["m.stock_actual <= m.stock_minimo", "m.estado = 'activo'"];
        $params = [];
        if (!esSuperAdmin() && obtenerSedeActual()) {
            $where[] = "m.sede_id = :sede_id";
            $params['sede_id'] = obtenerSedeActual();
        }
        $query = "SELECT m.*, c.nombre as categoria_nombre 
                  FROM " . $this->table_name . " m
                  LEFT JOIN categorias_materiales c ON m.categoria_id = c.id
                  WHERE " . implode(' AND ', $where) . "
                  ORDER BY m.stock_actual ASC";

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener estadísticas de materiales
     */
    public function obtenerEstadisticas() {
        $where = ["estado = 'activo'"];
        $params = [];
        if (!esSuperAdmin() && obtenerSedeActual()) {
            $where[] = "sede_id = :sede_id";
            $params['sede_id'] = obtenerSedeActual();
        }
        $query = "SELECT 
                    COUNT(*) as total_materiales,
                    SUM(stock_actual) as total_stock,
                    SUM(stock_actual * costo_unitario) as valor_inventario,
                    COUNT(CASE WHEN stock_actual <= stock_minimo THEN 1 END) as stock_bajo
                  FROM " . $this->table_name . "
                  WHERE " . implode(' AND ', $where);

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Verificar si un material tiene referencias en otras tablas
     */
    public function tieneReferencias($material_id) {
        $referencias = [];
        
        // Verificar movimientos de inventario
        $query_movimientos = "SELECT COUNT(*) as total FROM movimientos_inventario WHERE material_id = :material_id";
        $stmt = $this->conn->prepare($query_movimientos);
        $stmt->bindParam(':material_id', $material_id);
        $stmt->execute();
        $movimientos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($movimientos['total'] > 0) {
            $referencias[] = "movimientos_inventario ({$movimientos['total']} registros)";
        }
        
        // Verificar solicitudes detalle
        $query_solicitudes = "SELECT COUNT(*) as total FROM solicitudes_detalle WHERE material_id = :material_id";
        $stmt = $this->conn->prepare($query_solicitudes);
        $stmt->bindParam(':material_id', $material_id);
        $stmt->execute();
        $solicitudes = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($solicitudes['total'] > 0) {
            $referencias[] = "solicitudes_detalle ({$solicitudes['total']} registros)";
        }
        
        // Verificar compras detalle (si existe la tabla)
        try {
            $query_compras = "SELECT COUNT(*) as total FROM compras_detalle WHERE material_id = :material_id";
            $stmt = $this->conn->prepare($query_compras);
            $stmt->bindParam(':material_id', $material_id);
            $stmt->execute();
            $compras = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($compras['total'] > 0) {
                $referencias[] = "compras_detalle ({$compras['total']} registros)";
            }
        } catch (Exception $e) {
            // Tabla no existe, continuar
        }
        
        return $referencias;
    }
    
    /**
     * Eliminar o desactivar material de forma segura
     */
    public function eliminarSeguro($material_id) {
        $referencias = $this->tieneReferencias($material_id);
        
        if (empty($referencias)) {
            // No tiene referencias, eliminar completamente
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $material_id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'action' => 'eliminado', 'message' => 'Material eliminado completamente'];
            } else {
                return ['success' => false, 'message' => 'Error al eliminar el material'];
            }
        } else {
            // Tiene referencias, cambiar estado a inactivo
            $query = "UPDATE " . $this->table_name . " SET estado = 'inactivo', updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $material_id);
            
            if ($stmt->execute()) {
                return [
                    'success' => true, 
                    'action' => 'desactivado', 
                    'message' => 'Material desactivado (tiene referencias en: ' . implode(', ', $referencias) . ')'
                ];
            } else {
                return ['success' => false, 'message' => 'Error al desactivar el material'];
            }
        }
    }
}
