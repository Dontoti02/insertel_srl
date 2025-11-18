<?php
/**
 * Modelo de Alertas del Sistema
 */

class Alerta {
    private $conn;
    private $table_name = "alertas_sistema";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Crear nueva alerta
     */
    public function crear($tipo, $titulo, $mensaje, $usuario_id = null, $rol_id = null, $material_id = null, $solicitud_id = null) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (tipo, titulo, mensaje, usuario_id, rol_id, material_id, solicitud_id) 
                  VALUES (:tipo, :titulo, :mensaje, :usuario_id, :rol_id, :material_id, :solicitud_id)";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':tipo' => $tipo,
            ':titulo' => $titulo,
            ':mensaje' => $mensaje,
            ':usuario_id' => $usuario_id,
            ':rol_id' => $rol_id,
            ':material_id' => $material_id,
            ':solicitud_id' => $solicitud_id
        ]);
    }

    /**
     * Obtener alertas para un usuario
     */
    public function obtenerParaUsuario($usuario_id, $incluir_rol = true) {
        $where_conditions = ["(a.usuario_id = :usuario_id"];
        $params = [':usuario_id' => $usuario_id];

        if ($incluir_rol) {
            // Obtener rol del usuario
            $query_rol = "SELECT rol_id FROM usuarios WHERE id = :usuario_id";
            $stmt_rol = $this->conn->prepare($query_rol);
            $stmt_rol->execute([':usuario_id' => $usuario_id]);
            $rol = $stmt_rol->fetch(PDO::FETCH_ASSOC);

            if ($rol) {
                $where_conditions[0] .= " OR a.rol_id = :rol_id";
                $params[':rol_id'] = $rol['rol_id'];
            }
        }

        $where_conditions[0] .= ")";

        $query = "SELECT a.*, 
                         m.nombre as material_nombre,
                         s.codigo_solicitud
                  FROM " . $this->table_name . " a
                  LEFT JOIN materiales m ON a.material_id = m.id
                  LEFT JOIN solicitudes s ON a.solicitud_id = s.id
                  WHERE " . implode(' AND ', $where_conditions) . "
                  ORDER BY a.leida ASC, a.fecha_creacion DESC
                  LIMIT 50";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Marcar alerta como leída
     */
    public function marcarLeida($alerta_id, $usuario_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET leida = TRUE, fecha_leida = NOW() 
                  WHERE id = :id AND (usuario_id = :usuario_id OR rol_id IN (
                      SELECT rol_id FROM usuarios WHERE id = :usuario_id2
                  ))";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':id' => $alerta_id,
            ':usuario_id' => $usuario_id,
            ':usuario_id2' => $usuario_id
        ]);
    }

    /**
     * Marcar todas las alertas como leídas para un usuario
     */
    public function marcarTodasLeidas($usuario_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET leida = TRUE, fecha_leida = NOW() 
                  WHERE (usuario_id = :usuario_id OR rol_id IN (
                      SELECT rol_id FROM usuarios WHERE id = :usuario_id2
                  )) AND leida = FALSE";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':usuario_id' => $usuario_id,
            ':usuario_id2' => $usuario_id
        ]);
    }

    /**
     * Contar alertas no leídas para un usuario
     */
    public function contarNoLeidas($usuario_id) {
        $query = "SELECT COUNT(*) as total
                  FROM " . $this->table_name . " a
                  WHERE (a.usuario_id = :usuario_id OR a.rol_id IN (
                      SELECT rol_id FROM usuarios WHERE id = :usuario_id2
                  )) AND a.leida = FALSE";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':usuario_id' => $usuario_id,
            ':usuario_id2' => $usuario_id
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Generar alertas de stock mínimo
     */
    public function generarAlertasStockMinimo() {
        // Obtener materiales con stock bajo
        $query = "SELECT m.*, c.nombre as categoria_nombre
                  FROM materiales m
                  LEFT JOIN categorias_materiales c ON m.categoria_id = c.id
                  WHERE m.stock_actual <= m.stock_minimo 
                  AND m.estado = 'activo'
                  AND NOT EXISTS (
                      SELECT 1 FROM " . $this->table_name . " 
                      WHERE tipo = 'stock_minimo' 
                      AND material_id = m.id 
                      AND DATE(fecha_creacion) = CURDATE()
                  )";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $materiales_criticos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($materiales_criticos as $material) {
            $titulo = "Stock Mínimo: {$material['nombre']}";
            $mensaje = "El material {$material['codigo']} - {$material['nombre']} tiene stock crítico. Stock actual: {$material['stock_actual']}, Stock mínimo: {$material['stock_minimo']}";
            
            // Crear alerta para jefes de almacén y administradores
            $this->crear('stock_minimo', $titulo, $mensaje, null, ROL_JEFE_ALMACEN, $material['id']);
            $this->crear('stock_minimo', $titulo, $mensaje, null, ROL_ADMINISTRADOR, $material['id']);
        }

        return count($materiales_criticos);
    }

    /**
     * Generar alertas de vencimiento
     */
    public function generarAlertasVencimiento($dias_anticipacion = 30) {
        $query = "SELECT m.*, c.nombre as categoria_nombre
                  FROM materiales m
                  LEFT JOIN categorias_materiales c ON m.categoria_id = c.id
                  WHERE m.fecha_vencimiento IS NOT NULL
                  AND m.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL :dias DAY)
                  AND m.fecha_vencimiento >= CURDATE()
                  AND m.estado = 'activo'
                  AND NOT EXISTS (
                      SELECT 1 FROM " . $this->table_name . " 
                      WHERE tipo = 'vencimiento' 
                      AND material_id = m.id 
                      AND DATE(fecha_creacion) = CURDATE()
                  )";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':dias' => $dias_anticipacion]);
        $materiales_vencimiento = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($materiales_vencimiento as $material) {
            $dias_restantes = (strtotime($material['fecha_vencimiento']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
            $titulo = "Próximo a Vencer: {$material['nombre']}";
            $mensaje = "El material {$material['codigo']} - {$material['nombre']} vence en {$dias_restantes} días (Fecha: {$material['fecha_vencimiento']})";
            
            // Crear alerta para jefes de almacén y administradores
            $this->crear('vencimiento', $titulo, $mensaje, null, ROL_JEFE_ALMACEN, $material['id']);
            $this->crear('vencimiento', $titulo, $mensaje, null, ROL_ADMINISTRADOR, $material['id']);
        }

        return count($materiales_vencimiento);
    }

    /**
     * Generar alertas de solicitudes pendientes
     */
    public function generarAlertasSolicitudesPendientes($horas_limite = 24) {
        $query = "SELECT s.*, u.nombre_completo as tecnico_nombre
                  FROM solicitudes s
                  INNER JOIN usuarios u ON s.tecnico_id = u.id
                  WHERE s.estado = 'pendiente'
                  AND s.fecha_solicitud <= DATE_SUB(NOW(), INTERVAL :horas HOUR)
                  AND NOT EXISTS (
                      SELECT 1 FROM " . $this->table_name . " 
                      WHERE tipo = 'solicitud_pendiente' 
                      AND solicitud_id = s.id 
                      AND DATE(fecha_creacion) = CURDATE()
                  )";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':horas' => $horas_limite]);
        $solicitudes_pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($solicitudes_pendientes as $solicitud) {
            $titulo = "Solicitud Pendiente: {$solicitud['codigo_solicitud']}";
            $mensaje = "La solicitud {$solicitud['codigo_solicitud']} del técnico {$solicitud['tecnico_nombre']} lleva más de {$horas_limite} horas sin respuesta";
            
            // Crear alerta para jefes de almacén y administradores
            $this->crear('solicitud_pendiente', $titulo, $mensaje, null, ROL_JEFE_ALMACEN, null, $solicitud['id']);
            $this->crear('solicitud_pendiente', $titulo, $mensaje, null, ROL_ADMINISTRADOR, null, $solicitud['id']);
        }

        return count($solicitudes_pendientes);
    }

    /**
     * Limpiar alertas antiguas
     */
    public function limpiarAntiguas($dias = 30) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE fecha_creacion < DATE_SUB(NOW(), INTERVAL :dias DAY)";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':dias' => $dias]);
    }

    /**
     * Obtener estadísticas de alertas
     */
    public function obtenerEstadisticas() {
        $query = "SELECT 
                    tipo,
                    COUNT(*) as total,
                    SUM(CASE WHEN leida = FALSE THEN 1 ELSE 0 END) as no_leidas
                  FROM " . $this->table_name . "
                  WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                  GROUP BY tipo";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
