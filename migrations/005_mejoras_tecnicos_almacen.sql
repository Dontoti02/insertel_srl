-- Migración: Mejoras para Técnicos y Jefe de Almacén
-- Fecha: 2025-12-01
-- Descripción: Agrega funcionalidades de liquidación de materiales, alertas y estadísticas

-- 1. Agregar campo foto_acta a la tabla actas_tecnicas (si no existe)
SET @dbname = DATABASE();
SET @tablename = "actas_tecnicas";
SET @columnname = "foto_acta";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " VARCHAR(255) NULL AFTER observaciones")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Agregar campo estado_liquidacion (si no existe)
SET @columnname = "estado_liquidacion";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " ENUM('pendiente', 'liquidada') DEFAULT 'pendiente' AFTER estado")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 2. Crear tabla de liquidaciones de materiales
CREATE TABLE IF NOT EXISTS `liquidaciones_materiales` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `acta_id` INT(11) NOT NULL,
  `tecnico_id` INT(11) NOT NULL,
  `material_id` INT(11) NOT NULL,
  `cantidad` DECIMAL(10,2) NOT NULL,
  `fecha_liquidacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sede_id` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_acta` (`acta_id`),
  KEY `idx_tecnico` (`tecnico_id`),
  KEY `idx_material` (`material_id`),
  KEY `idx_sede` (`sede_id`),
  KEY `idx_fecha` (`fecha_liquidacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Agregar campo updated_at a stock_tecnicos si no existe
SET @tablename = "stock_tecnicos";
SET @columnname = "updated_at";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER fecha_asignacion")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 4. Crear índices adicionales para mejorar rendimiento de consultas (solo si no existen)
SET @tablename = "stock_tecnicos";

-- Índice para fecha_asignacion
SET @indexname = "idx_stock_tecnicos_fecha";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (index_name = @indexname)
  ) > 0,
  "SELECT 1",
  CONCAT("CREATE INDEX ", @indexname, " ON ", @tablename, " (fecha_asignacion)")
));
PREPARE createIndexIfNotExists FROM @preparedStatement;
EXECUTE createIndexIfNotExists;
DEALLOCATE PREPARE createIndexIfNotExists;

-- Índice para updated_at
SET @indexname = "idx_stock_tecnicos_updated";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (index_name = @indexname)
  ) > 0,
  "SELECT 1",
  CONCAT("CREATE INDEX ", @indexname, " ON ", @tablename, " (updated_at)")
));
PREPARE createIndexIfNotExists FROM @preparedStatement;
EXECUTE createIndexIfNotExists;
DEALLOCATE PREPARE createIndexIfNotExists;

-- Índices para actas_tecnicas
SET @tablename = "actas_tecnicas";

SET @indexname = "idx_actas_tipo_servicio";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (index_name = @indexname)
  ) > 0,
  "SELECT 1",
  CONCAT("CREATE INDEX ", @indexname, " ON ", @tablename, " (tipo_servicio)")
));
PREPARE createIndexIfNotExists FROM @preparedStatement;
EXECUTE createIndexIfNotExists;
DEALLOCATE PREPARE createIndexIfNotExists;

SET @indexname = "idx_actas_estado_liquidacion";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (index_name = @indexname)
  ) > 0,
  "SELECT 1",
  CONCAT("CREATE INDEX ", @indexname, " ON ", @tablename, " (estado_liquidacion)")
));
PREPARE createIndexIfNotExists FROM @preparedStatement;
EXECUTE createIndexIfNotExists;
DEALLOCATE PREPARE createIndexIfNotExists;

-- Verificación de la migración
SELECT 'Migración completada exitosamente' as status;

