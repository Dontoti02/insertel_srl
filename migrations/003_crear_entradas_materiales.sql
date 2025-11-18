-- Crear tabla de entradas de materiales
CREATE TABLE IF NOT EXISTS `entradas_materiales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `movimiento_id` int(11) NOT NULL,
  `tipo_entrada` enum('proveedor','devolucion','ajuste') NOT NULL DEFAULT 'proveedor',
  `proveedor_id` int(11) DEFAULT NULL,
  `numero_lote` varchar(100) DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `fecha_entrada` date NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `movimiento_id` (`movimiento_id`),
  KEY `proveedor_id` (`proveedor_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `fecha_entrada` (`fecha_entrada`),
  KEY `tipo_entrada` (`tipo_entrada`),
  CONSTRAINT `entradas_materiales_ibfk_1` FOREIGN KEY (`movimiento_id`) REFERENCES `movimientos_inventario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `entradas_materiales_ibfk_2` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `entradas_materiales_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de salidas de materiales
CREATE TABLE IF NOT EXISTS `salidas_materiales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `movimiento_id` int(11) NOT NULL,
  `tipo_salida` enum('proyecto','tecnico','devolucion_proveedor','ajuste') NOT NULL DEFAULT 'proyecto',
  `proyecto_id` int(11) DEFAULT NULL,
  `tecnico_id` int(11) DEFAULT NULL,
  `numero_orden` varchar(100) DEFAULT NULL,
  `fecha_salida` date NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `movimiento_id` (`movimiento_id`),
  KEY `tecnico_id` (`tecnico_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `fecha_salida` (`fecha_salida`),
  KEY `tipo_salida` (`tipo_salida`),
  CONSTRAINT `salidas_materiales_ibfk_1` FOREIGN KEY (`movimiento_id`) REFERENCES `movimientos_inventario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `salidas_materiales_ibfk_2` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `salidas_materiales_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de devoluciones de materiales
CREATE TABLE IF NOT EXISTS `devoluciones_materiales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `movimiento_id` int(11) NOT NULL,
  `tecnico_id` int(11) NOT NULL,
  `motivo_devolucion` varchar(255) NOT NULL,
  `estado_material` enum('nuevo','usado','dañado') NOT NULL DEFAULT 'usado',
  `cantidad_devuelta` int(11) NOT NULL,
  `cantidad_rechazada` int(11) DEFAULT 0,
  `razon_rechazo` text DEFAULT NULL,
  `fecha_devolucion` date NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `movimiento_id` (`movimiento_id`),
  KEY `tecnico_id` (`tecnico_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `fecha_devolucion` (`fecha_devolucion`),
  CONSTRAINT `devoluciones_materiales_ibfk_1` FOREIGN KEY (`movimiento_id`) REFERENCES `movimientos_inventario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `devoluciones_materiales_ibfk_2` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `devoluciones_materiales_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de verificación de calidad de proveedores
CREATE TABLE IF NOT EXISTS `verificacion_calidad_proveedor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entrada_id` int(11) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `cantidad_recibida` int(11) NOT NULL,
  `cantidad_conforme` int(11) NOT NULL,
  `cantidad_no_conforme` int(11) DEFAULT 0,
  `defectos_encontrados` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `estado_verificacion` enum('pendiente','conforme','no_conforme','parcial') NOT NULL DEFAULT 'pendiente',
  `fecha_verificacion` datetime DEFAULT NULL,
  `usuario_verificador_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `entrada_id` (`entrada_id`),
  KEY `proveedor_id` (`proveedor_id`),
  KEY `material_id` (`material_id`),
  KEY `usuario_verificador_id` (`usuario_verificador_id`),
  KEY `estado_verificacion` (`estado_verificacion`),
  CONSTRAINT `verificacion_calidad_ibfk_1` FOREIGN KEY (`entrada_id`) REFERENCES `entradas_materiales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `verificacion_calidad_ibfk_2` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `verificacion_calidad_ibfk_3` FOREIGN KEY (`material_id`) REFERENCES `materiales` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `verificacion_calidad_ibfk_4` FOREIGN KEY (`usuario_verificador_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de alertas de vencimiento
CREATE TABLE IF NOT EXISTS `alertas_vencimiento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `material_id` int(11) NOT NULL,
  `entrada_id` int(11) NOT NULL,
  `numero_lote` varchar(100) DEFAULT NULL,
  `fecha_vencimiento` date NOT NULL,
  `cantidad_disponible` int(11) NOT NULL,
  `dias_para_vencer` int(11) NOT NULL,
  `estado_alerta` enum('activa','resuelta','vencida') NOT NULL DEFAULT 'activa',
  `fecha_creacion_alerta` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_resolucion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `material_id` (`material_id`),
  KEY `entrada_id` (`entrada_id`),
  KEY `fecha_vencimiento` (`fecha_vencimiento`),
  KEY `estado_alerta` (`estado_alerta`),
  CONSTRAINT `alertas_vencimiento_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `materiales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `alertas_vencimiento_ibfk_2` FOREIGN KEY (`entrada_id`) REFERENCES `entradas_materiales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
