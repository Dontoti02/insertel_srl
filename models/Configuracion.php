<?php
/**
 * Modelo de Configuración del Sistema
 */

class Configuracion {
    private $conn;
    private $table_name = "configuracion_sistema";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Obtener valor de configuración
     */
    public function obtenerValor($clave, $valor_defecto = null) {
        $query = "SELECT valor FROM " . $this->table_name . " WHERE clave = :clave LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":clave", $clave);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['valor'] : $valor_defecto;
    }

    /**
     * Establecer valor de configuración
     */
    public function establecerValor($clave, $valor, $descripcion = '', $tipo = 'texto', $categoria = 'general') {
        $query = "INSERT INTO " . $this->table_name . " 
                  (clave, valor, descripcion, tipo, categoria) 
                  VALUES (:clave, :valor, :descripcion, :tipo, :categoria)
                  ON DUPLICATE KEY UPDATE 
                  valor = :valor2, descripcion = :descripcion2, tipo = :tipo2, categoria = :categoria2";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":clave", $clave);
        $stmt->bindParam(":valor", $valor);
        $stmt->bindParam(":descripcion", $descripcion);
        $stmt->bindParam(":tipo", $tipo);
        $stmt->bindParam(":categoria", $categoria);
        $stmt->bindParam(":valor2", $valor);
        $stmt->bindParam(":descripcion2", $descripcion);
        $stmt->bindParam(":tipo2", $tipo);
        $stmt->bindParam(":categoria2", $categoria);

        return $stmt->execute();
    }

    /**
     * Obtener todas las configuraciones por categoría
     */
    public function obtenerPorCategoria($categoria = null) {
        $where = $categoria ? "WHERE categoria = :categoria" : "";
        $query = "SELECT * FROM " . $this->table_name . " $where ORDER BY categoria, clave";
        
        $stmt = $this->conn->prepare($query);
        if ($categoria) {
            $stmt->bindParam(":categoria", $categoria);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener todas las categorías
     */
    public function obtenerCategorias() {
        $query = "SELECT DISTINCT categoria FROM " . $this->table_name . " ORDER BY categoria";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Actualizar múltiples configuraciones
     */
    public function actualizarMultiples($configuraciones) {
        try {
            $this->conn->beginTransaction();

            $query = "UPDATE " . $this->table_name . " SET valor = :valor WHERE clave = :clave";
            $stmt = $this->conn->prepare($query);

            foreach ($configuraciones as $clave => $valor) {
                $stmt->execute([':clave' => $clave, ':valor' => $valor]);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    /**
     * Eliminar configuración
     */
    public function eliminar($clave) {
        $query = "DELETE FROM " . $this->table_name . " WHERE clave = :clave";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":clave", $clave);
        
        return $stmt->execute();
    }

    /**
     * Obtener configuraciones de inventario
     */
    public function obtenerConfigInventario() {
        return [
            'stock_minimo_global' => $this->obtenerValor('stock_minimo_global', '10'),
            'dias_alerta_vencimiento' => $this->obtenerValor('dias_alerta_vencimiento', '30'),
            'moneda_sistema' => $this->obtenerValor('moneda_sistema', 'PEN')
        ];
    }

    /**
     * Obtener configuraciones de notificaciones
     */
    public function obtenerConfigNotificaciones() {
        return [
            'email_notificaciones' => $this->obtenerValor('email_notificaciones', 'admin@insertel.com'),
            'horas_respuesta_solicitud' => $this->obtenerValor('horas_respuesta_solicitud', '24')
        ];
    }

    /**
     * Obtener configuraciones de empresa
     */
    public function obtenerConfigEmpresa() {
        return [
            'empresa_nombre' => $this->obtenerValor('empresa_nombre', 'INSERTEL S.R.L.'),
            'empresa_ruc' => $this->obtenerValor('empresa_ruc', '20123456789')
        ];
    }
}
