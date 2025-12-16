-- =====================================================
-- MIGRACIÓN: Sistema Seguro de Recuperación de Contraseña
-- Versión: 1.0
-- Fecha: 2025-11-20
-- Descripción: Implementación de sistema robusto contra ataques
-- =====================================================

-- --------------------------------------------------------
-- Tabla: password_recovery_tokens
-- Tokens de recuperación con hash seguro
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_recovery_tokens` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL COMMENT 'SHA256 hash del token',
  `selector` VARCHAR(32) NOT NULL COMMENT 'Selector público para búsqueda',
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) DEFAULT 0,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_selector` (`selector`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires_at` (`expires_at`),
  FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: remember_tokens
-- Tokens para "Recordar sesión" con seguridad mejorada
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL COMMENT 'SHA256 hash del token',
  `selector` VARCHAR(32) NOT NULL COMMENT 'Selector público para búsqueda',
  `expires_at` DATETIME NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `last_used` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_selector` (`selector`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires_at` (`expires_at`),
  FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: security_rate_limit
-- Control de intentos para prevenir fuerza bruta
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `security_rate_limit` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `identifier` VARCHAR(255) NOT NULL COMMENT 'IP, email o username',
  `action_type` ENUM('login','password_recovery','api_request') NOT NULL,
  `attempts` INT(11) DEFAULT 0,
  `last_attempt` DATETIME DEFAULT NULL,
  `blocked_until` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_identifier_action` (`identifier`, `action_type`),
  KEY `idx_blocked_until` (`blocked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: security_audit_log
-- Log completo de eventos de seguridad
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `security_audit_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `event_type` VARCHAR(50) NOT NULL COMMENT 'Tipo de evento de seguridad',
  `severity` ENUM('low','medium','high','critical') DEFAULT 'low',
  `user_id` INT(11) DEFAULT NULL,
  `username` VARCHAR(100) DEFAULT NULL COMMENT 'Username intentado (aunque no exista)',
  `email` VARCHAR(150) DEFAULT NULL COMMENT 'Email intentado (aunque no exista)',
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `success` TINYINT(1) DEFAULT 0,
  `error_message` TEXT DEFAULT NULL,
  `metadata` JSON DEFAULT NULL COMMENT 'Datos adicionales en formato JSON',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_severity` (`severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Índices adicionales para optimización
-- --------------------------------------------------------
CREATE INDEX IF NOT EXISTS `idx_historial_usuario_fecha` ON `historial_actividades`(`usuario_id`, `fecha`);
CREATE INDEX IF NOT EXISTS `idx_historial_accion` ON `historial_actividades`(`accion`);

-- --------------------------------------------------------
-- Configuraciones de seguridad
-- --------------------------------------------------------
INSERT INTO `configuracion_sistema` (`clave`, `valor`, `descripcion`, `tipo`, `categoria`) VALUES
('max_login_attempts', '5', 'Máximo de intentos de login antes de bloqueo', 'numero', 'seguridad'),
('login_lockout_minutes', '15', 'Minutos de bloqueo tras exceder intentos', 'numero', 'seguridad'),
('max_recovery_attempts', '3', 'Máximo de intentos de recuperación por hora', 'numero', 'seguridad'),
('password_min_length', '8', 'Longitud mínima de contraseña', 'numero', 'seguridad'),
('password_require_special', '1', 'Requerir caracteres especiales en contraseña', 'boolean', 'seguridad'),
('password_require_numbers', '1', 'Requerir números en contraseña', 'boolean', 'seguridad'),
('password_require_uppercase', '1', 'Requerir mayúsculas en contraseña', 'boolean', 'seguridad'),
('recovery_token_validity_hours', '1', 'Horas de validez del token de recuperación', 'numero', 'seguridad'),
('enable_email_notifications', '1', 'Activar notificaciones por email', 'boolean', 'seguridad')
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`);

-- --------------------------------------------------------
-- Comentarios y documentación
-- --------------------------------------------------------
ALTER TABLE `password_recovery_tokens` COMMENT = 'Tokens de recuperación de contraseña con hash seguro (SHA256)';
ALTER TABLE `remember_tokens` COMMENT = 'Tokens de sesión persistente con hash seguro (SHA256)';
ALTER TABLE `security_rate_limit` COMMENT = 'Control de intentos para prevenir ataques de fuerza bruta';
ALTER TABLE `security_audit_log` COMMENT = 'Registro completo de eventos de seguridad del sistema';
