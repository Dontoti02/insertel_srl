-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 19-11-2025 a las 00:35:30
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `insertel_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actas_tecnicas`
--

CREATE TABLE `actas_tecnicas` (
  `id` int(11) NOT NULL,
  `codigo_acta` varchar(50) DEFAULT NULL,
  `tecnico_id` int(11) NOT NULL,
  `usuario_reporta_id` int(11) DEFAULT NULL,
  `fecha_servicio` date NOT NULL,
  `cliente` varchar(150) DEFAULT NULL,
  `direccion_servicio` varchar(255) DEFAULT NULL,
  `tipo_servicio` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `descripcion_trabajo` text NOT NULL,
  `materiales_utilizados` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `archivo_path` varchar(255) DEFAULT NULL,
  `estado` enum('borrador','finalizada') DEFAULT 'borrador',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `actas_tecnicas`
--

INSERT INTO `actas_tecnicas` (`id`, `codigo_acta`, `tecnico_id`, `usuario_reporta_id`, `fecha_servicio`, `cliente`, `direccion_servicio`, `tipo_servicio`, `descripcion`, `descripcion_trabajo`, `materiales_utilizados`, `observaciones`, `archivo_path`, `estado`, `created_at`, `updated_at`) VALUES
(2, 'ACT-20251118-3368DD', 24, NULL, '2025-11-18', 'Juan maldonado Sosa', 'veracruz av sullana', 'Instalación', NULL, 'se instalo 3 m3tros de cable', 'COAXIAL, CONECTORES', 'LO MAS TOP', NULL, 'finalizada', '2025-11-18 13:46:11', '2025-11-18 13:46:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alertas_sistema`
--

CREATE TABLE `alertas_sistema` (
  `id` int(11) NOT NULL,
  `tipo` enum('stock_minimo','vencimiento','solicitud_pendiente','sistema') NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `mensaje` text NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `rol_id` int(11) DEFAULT NULL,
  `material_id` int(11) DEFAULT NULL,
  `solicitud_id` int(11) DEFAULT NULL,
  `leida` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_leida` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alertas_vencimiento`
--

CREATE TABLE `alertas_vencimiento` (
  `id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `entrada_id` int(11) NOT NULL,
  `numero_lote` varchar(100) DEFAULT NULL,
  `fecha_vencimiento` date NOT NULL,
  `cantidad_disponible` int(11) NOT NULL,
  `dias_para_vencer` int(11) NOT NULL,
  `estado_alerta` enum('activa','resuelta','vencida') DEFAULT 'activa',
  `fecha_creacion_alerta` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_resolucion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria_sede`
--

CREATE TABLE `auditoria_sede` (
  `id` int(11) NOT NULL,
  `sede_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `datos_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_anteriores`)),
  `datos_nuevos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_nuevos`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `fecha_accion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_materiales`
--

CREATE TABLE `categorias_materiales` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias_materiales`
--

INSERT INTO `categorias_materiales` (`id`, `nombre`, `descripcion`, `created_at`) VALUES
(1, 'Cables', 'Cables de diferentes tipos y calibres', '2025-11-17 22:08:19'),
(2, 'Conectores', 'Conectores y terminales', '2025-11-17 22:08:19'),
(3, 'Routers', 'Routers y equipos de red inalámbrica', '2025-11-17 22:08:19'),
(4, 'Repetidores', 'Repetidores y amplificadores de señal', '2025-11-17 22:08:19'),
(5, 'Herramientas Tecnicas', 'Herramientas técnicas y de diagnóstico', '2025-11-17 22:08:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuraciones_sede`
--

CREATE TABLE `configuraciones_sede` (
  `id` int(11) NOT NULL,
  `sede_id` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `valor` text DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuraciones_sede`
--

INSERT INTO `configuraciones_sede` (`id`, `sede_id`, `clave`, `valor`, `descripcion`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 1, 'stock_minimo_alerta', '10', 'Cantidad m??nima de stock para generar alertas', '2025-11-15 17:38:12', '2025-11-15 17:38:12'),
(5, 1, 'limite_valor_auto_aprobacion', '1000', 'Valor l??mite para auto-aprobaci??n de solicitudes', '2025-11-15 17:38:12', '2025-11-15 17:38:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_sistema`
--

CREATE TABLE `configuracion_sistema` (
  `id` int(11) NOT NULL,
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `tipo` enum('texto','numero','boolean','json') DEFAULT 'texto',
  `categoria` varchar(50) DEFAULT 'general',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuracion_sistema`
--

INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `descripcion`, `tipo`, `categoria`, `updated_at`) VALUES
(1, 'stock_minimo_global', '10', 'Stock mínimo por defecto para nuevos materiales', 'numero', 'inventario', '2025-11-13 15:01:45'),
(2, 'dias_alerta_vencimiento', '30', 'Días antes del vencimiento para generar alerta', 'numero', 'inventario', '2025-11-13 15:01:45'),
(3, 'email_notificaciones', 'admin@insertel.com', 'Email para notificaciones del sistema', 'texto', 'notificaciones', '2025-11-13 15:01:45'),
(4, 'horas_respuesta_solicitud', '24', 'Horas máximas para responder una solicitud', 'numero', 'solicitudes', '2025-11-13 15:01:45'),
(5, 'backup_automatico', '1', 'Activar backup automático diario', 'boolean', 'sistema', '2025-11-16 19:21:46'),
(6, 'moneda_sistema', 'PEN', 'Moneda del sistema (PEN, USD, EUR)', 'texto', 'general', '2025-11-13 15:01:45'),
(7, 'empresa_nombre', 'INSERTEL S.R.L', 'Nombre de la empresa', 'texto', 'general', '2025-11-17 22:26:58'),
(8, 'empresa_ruc', '20123456789', 'RUC de la empresa', 'texto', 'general', '2025-11-13 15:01:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `devoluciones_materiales`
--

CREATE TABLE `devoluciones_materiales` (
  `id` int(11) NOT NULL,
  `movimiento_id` int(11) NOT NULL,
  `tecnico_id` int(11) NOT NULL,
  `motivo_devolucion` varchar(255) NOT NULL,
  `estado_devolucion` enum('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
  `estado_material` enum('nuevo','usado','dañado') DEFAULT 'usado',
  `cantidad_devuelta` int(11) NOT NULL,
  `cantidad_rechazada` int(11) DEFAULT 0,
  `razon_rechazo` text DEFAULT NULL,
  `fecha_devolucion` date NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `sede_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entradas_materiales`
--

CREATE TABLE `entradas_materiales` (
  `id` int(11) NOT NULL,
  `movimiento_id` int(11) NOT NULL,
  `tipo_entrada` enum('proveedor','devolucion','ajuste') DEFAULT 'proveedor',
  `proveedor_id` int(11) DEFAULT NULL,
  `numero_lote` varchar(100) DEFAULT NULL,
  `fecha_entrada` date NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `entradas_materiales`
--

INSERT INTO `entradas_materiales` (`id`, `movimiento_id`, `tipo_entrada`, `proveedor_id`, `numero_lote`, `fecha_entrada`, `usuario_id`, `created_at`, `updated_at`) VALUES
(1, 25, 'proveedor', 204, 'MAT-01', '2025-11-18', 25, '2025-11-18 18:15:37', '2025-11-18 18:15:37'),
(2, 27, 'proveedor', 197, 'MAT-01', '2025-11-18', 26, '2025-11-18 21:36:08', '2025-11-18 21:36:08'),
(3, 28, 'ajuste', 204, 'MAT-04', '2025-11-18', 26, '2025-11-18 21:36:33', '2025-11-18 21:36:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_actividades`
--

CREATE TABLE `historial_actividades` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `accion` varchar(100) NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `historial_actividades`
--

INSERT INTO `historial_actividades` (`id`, `usuario_id`, `accion`, `modulo`, `descripcion`, `ip_address`, `fecha`) VALUES
(2, 6, 'registro', 'autenticacion', 'Nuevo administrador registrado', '::1', '2025-11-12 09:27:36'),
(3, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-12 09:27:51'),
(4, 6, 'eliminar', 'usuarios', 'Usuario eliminado: admin', '::1', '2025-11-12 09:29:50'),
(5, 6, 'eliminar', 'usuarios', 'Usuario eliminado: jefe_almacen', '::1', '2025-11-12 09:29:52'),
(6, 6, 'eliminar', 'usuarios', 'Usuario eliminado: asistente1', '::1', '2025-11-12 09:29:54'),
(7, 6, 'eliminar', 'usuarios', 'Usuario eliminado: tecnico1', '::1', '2025-11-12 09:29:56'),
(8, 6, 'eliminar', 'usuarios', 'Usuario eliminado: tecnico2', '::1', '2025-11-12 09:29:58'),
(9, 6, 'editar', 'usuarios', 'Usuario actualizado ID: 6', '::1', '2025-11-12 09:30:09'),
(10, 6, 'editar', 'usuarios', 'Usuario actualizado ID: 6', '::1', '2025-11-12 09:41:10'),
(11, 6, 'importar', 'materiales', 'Importados: 200, Actualizados: 0', '::1', '2025-11-12 10:07:04'),
(12, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-12 11:27:21'),
(13, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-12 11:30:23'),
(14, 6, 'crear', 'usuarios', 'Usuario creado: jefealm1', '::1', '2025-11-12 11:38:22'),
(15, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-12 11:38:32'),
(20, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-12 11:44:57'),
(21, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-12 12:08:03'),
(22, 6, 'crear', 'usuarios', 'Usuario creado: asistalm1', '::1', '2025-11-12 12:35:25'),
(23, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-12 12:35:30'),
(26, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-12 12:36:26'),
(27, 6, 'crear', 'usuarios', 'Usuario creado: tecnico1', '::1', '2025-11-12 12:39:01'),
(28, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-12 12:39:06'),
(31, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-12 16:36:19'),
(32, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-12 16:36:52'),
(42, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-12 16:43:05'),
(44, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-12 16:45:02'),
(47, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-12 16:45:43'),
(48, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-12 16:45:58'),
(53, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-12 17:22:44'),
(54, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-12 17:23:33'),
(55, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-12 17:31:45'),
(56, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-12 17:33:16'),
(57, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-13 09:55:20'),
(58, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-13 11:33:52'),
(59, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-13 11:37:58'),
(60, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-13 19:04:54'),
(61, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-13 19:23:01'),
(62, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-13 19:33:51'),
(63, 6, 'editar', 'sedes', 'Sede actualizada ID: 1', '::1', '2025-11-13 19:41:20'),
(64, 6, 'editar', 'sedes', 'Sede actualizada ID: 1', '::1', '2025-11-13 19:41:24'),
(65, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-14 15:50:55'),
(66, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-14 15:52:23'),
(67, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-14 16:03:35'),
(68, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-14 16:04:10'),
(69, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-14 16:04:26'),
(70, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-14 16:05:32'),
(71, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-14 16:06:48'),
(72, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-14 16:09:51'),
(73, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-14 16:10:55'),
(74, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-14 16:10:57'),
(75, 6, 'solicitud_recuperacion', 'autenticacion', 'Solicitud de recuperación de contraseña', '::1', '2025-11-14 16:21:25'),
(76, 6, 'cambio_password', 'autenticacion', 'Contraseña cambiada mediante recuperación', '::1', '2025-11-14 16:21:55'),
(77, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso (recordar sesión activado)', '::1', '2025-11-14 16:22:12'),
(78, 6, 'eliminar_masa', 'materiales', 'Eliminados: 19 materiales', '::1', '2025-11-14 16:31:18'),
(79, 6, 'eliminar_masa', 'materiales', 'Eliminados: 19 materiales', '::1', '2025-11-14 16:31:34'),
(80, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 16:33:41'),
(81, 6, 'eliminar', 'sedes', 'Sede eliminada: Sede Este', '::1', '2025-11-14 16:35:20'),
(82, 6, 'eliminar', 'sedes', 'Sede eliminada: Sede Norte', '::1', '2025-11-14 16:35:24'),
(83, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-14 16:37:27'),
(88, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-14 16:38:12'),
(89, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 16:46:21'),
(90, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 16:46:28'),
(91, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 16:46:36'),
(92, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 16:46:44'),
(93, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 16:46:47'),
(94, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 16:46:50'),
(95, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 16:47:05'),
(96, 6, 'eliminar_masa', 'materiales', 'Eliminados: 2 materiales', '::1', '2025-11-14 16:47:08'),
(97, 6, 'importar', 'materiales', 'Importados: 0, Actualizados: 0', '::1', '2025-11-14 17:03:40'),
(98, 6, 'importar', 'materiales', 'Importados: 0, Actualizados: 0', '::1', '2025-11-14 17:04:31'),
(99, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-14 17:06:15'),
(100, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-14 17:06:27'),
(101, 6, 'importar', 'materiales', 'Importados: 0, Actualizados: 0', '::1', '2025-11-14 17:15:19'),
(102, 6, 'importar', 'materiales', 'Importados: 0, Actualizados: 0', '::1', '2025-11-14 17:15:39'),
(103, 6, 'importar', 'materiales', 'Importados: 0, Actualizados: 0', '::1', '2025-11-14 17:21:40'),
(104, 6, 'importar', 'materiales', 'Importados: 500, Actualizados: 0', '::1', '2025-11-14 17:25:33'),
(105, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 118', '::1', '2025-11-14 17:26:20'),
(106, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 118', '::1', '2025-11-14 17:26:30'),
(107, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 295', '::1', '2025-11-14 17:26:50'),
(108, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 118', '::1', '2025-11-14 17:30:38'),
(109, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 118', '::1', '2025-11-14 17:30:57'),
(110, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 266', '::1', '2025-11-14 17:31:16'),
(111, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 425', '::1', '2025-11-14 17:31:41'),
(112, 6, 'editar', 'sedes', 'Sede actualizada ID: 3', '::1', '2025-11-14 17:32:11'),
(113, 6, 'editar', 'sedes', 'Sede actualizada ID: 3', '::1', '2025-11-14 17:32:15'),
(114, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 706', '::1', '2025-11-14 17:32:32'),
(115, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 118', '::1', '2025-11-14 17:32:58'),
(116, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 118', '::1', '2025-11-14 17:33:16'),
(117, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 118', '::1', '2025-11-14 17:35:35'),
(118, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 118', '::1', '2025-11-14 17:39:34'),
(119, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 295', '::1', '2025-11-14 17:39:55'),
(120, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 723', '::1', '2025-11-14 17:40:19'),
(121, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 118', '::1', '2025-11-14 17:41:19'),
(122, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 140', '::1', '2025-11-14 17:43:58'),
(123, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 889', '::1', '2025-11-14 17:44:31'),
(124, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 266', '::1', '2025-11-14 17:44:52'),
(125, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 288', '::1', '2025-11-14 17:45:06'),
(126, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 288', '::1', '2025-11-14 17:45:17'),
(127, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 295', '::1', '2025-11-14 17:57:23'),
(128, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 295', '::1', '2025-11-14 17:57:41'),
(129, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 295', '::1', '2025-11-14 17:57:53'),
(130, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 295', '::1', '2025-11-14 17:58:02'),
(131, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 295', '::1', '2025-11-14 17:58:12'),
(132, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 295', '::1', '2025-11-14 17:58:25'),
(133, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 295', '::1', '2025-11-14 17:59:18'),
(134, 6, 'actualizar', 'materiales', 'Material actualizado: Routers 509', '::1', '2025-11-14 18:00:38'),
(135, 6, 'registrar', 'movimientos', 'Movimiento de entrada', '::1', '2025-11-14 18:01:36'),
(136, 6, 'crear', 'sedes', 'Sede creada: SEDE TALARA', '::1', '2025-11-14 18:06:09'),
(137, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-14 18:23:17'),
(138, 6, 'solicitud_recuperacion', 'autenticacion', 'Solicitud de recuperación de contraseña', '::1', '2025-11-14 18:25:35'),
(140, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-14 18:28:59'),
(141, 6, 'actualizar', 'configuracion', 'Configuraciones del sistema actualizadas', '::1', '2025-11-14 18:30:03'),
(142, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-14 18:31:32'),
(143, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso (recordar sesión activado)', '::1', '2025-11-14 18:32:08'),
(144, 6, 'login_remember', 'autenticacion', 'Inicio de sesión automático (recordar sesión)', '::1', '2025-11-14 20:44:59'),
(145, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-14 20:45:02'),
(146, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-14 20:47:55'),
(147, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:53:13'),
(148, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:53:16'),
(149, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:53:19'),
(150, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:53:22'),
(151, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:53:25'),
(152, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:53:28'),
(153, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:53:41'),
(154, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:53:44'),
(155, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:53:47'),
(156, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:53:49'),
(157, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:53:52'),
(158, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:53:55'),
(159, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:53:58'),
(160, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:54:01'),
(161, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:54:04'),
(162, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:54:07'),
(163, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:54:09'),
(164, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:54:13'),
(165, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:54:15'),
(166, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:54:21'),
(167, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:54:23'),
(168, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:54:27'),
(169, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:54:30'),
(170, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales', '::1', '2025-11-14 20:54:33'),
(171, 6, 'eliminar_masa', 'materiales', 'Eliminados: 16 materiales', '::1', '2025-11-14 20:54:36'),
(172, 6, 'importar', 'materiales', 'Importados: 495, Actualizados: 5', '::1', '2025-11-14 20:55:54'),
(173, 6, 'importar', 'materiales', 'Importados: 0, Actualizados: 500', '::1', '2025-11-14 20:56:26'),
(174, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 118', '::1', '2025-11-14 20:57:04'),
(175, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 118', '::1', '2025-11-14 20:57:20'),
(176, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-14 20:58:19'),
(181, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-14 21:23:43'),
(182, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-15 12:41:43'),
(183, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-15 12:41:53'),
(184, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-15 12:44:10'),
(186, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-15 12:48:05'),
(187, 16, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-15 12:49:54'),
(188, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-15 12:50:03'),
(189, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-15 12:50:09'),
(190, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-15 12:52:58'),
(191, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-16 12:37:07'),
(192, 16, 'eliminar_sede', 'sedes', 'Sede eliminada ID: 5', '::1', '2025-11-16 12:43:44'),
(193, 16, 'eliminar_sede', 'sedes', 'Sede eliminada ID: 5', '::1', '2025-11-16 12:47:56'),
(194, 16, 'eliminar_sede', 'sedes', 'Sede eliminada ID: 5', '::1', '2025-11-16 12:47:57'),
(195, 16, 'eliminar_sede', 'sedes', 'Sede eliminada ID: 5', '::1', '2025-11-16 12:47:58'),
(196, 16, 'eliminar_sede', 'sedes', 'Sede eliminada ID: 5', '::1', '2025-11-16 12:47:58'),
(197, 16, 'eliminar_global', 'usuarios', 'Usuario eliminado: superadmin', '::1', '2025-11-16 12:55:07'),
(198, 16, 'editar_global', 'usuarios', 'Usuario actualizado ID: 16', '::1', '2025-11-16 12:55:21'),
(199, 16, 'cambiar_vista_sede', 'superadmin', 'Vista de sede cambiada a: Sede Central', '::1', '2025-11-16 13:32:21'),
(200, 16, 'cambiar_vista_sede', 'superadmin', 'Vista de sede cambiada a: Global', '::1', '2025-11-16 13:32:36'),
(201, 16, 'cambiar_vista_sede', 'superadmin', 'Vista de sede cambiada a: Sede Central', '::1', '2025-11-16 13:32:51'),
(202, 16, 'cambiar_vista_sede', 'superadmin', 'Vista de sede cambiada a: Global', '::1', '2025-11-16 13:33:05'),
(203, 16, 'eliminar', 'respaldos', 'Respaldo de BD eliminado: backup_20251116_133315.sql', '::1', '2025-11-16 13:33:22'),
(204, 16, 'actualizar_sede', 'sedes', 'Sede actualizada: Sede Central (SC01)', '::1', '2025-11-16 13:50:08'),
(205, 16, 'subir_imagen', 'perfil', 'Imagen de perfil actualizada', '::1', '2025-11-16 14:02:29'),
(206, 16, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-16 14:05:05'),
(207, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-16 14:06:49'),
(208, 16, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-16 14:07:20'),
(209, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-16 14:17:18'),
(210, 16, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-16 14:18:20'),
(211, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-16 14:18:58'),
(212, 16, 'actualizar', 'configuracion', 'Actualización de configuración del sistema', '::1', '2025-11-16 14:21:46'),
(213, 16, 'actualizar', 'configuracion', 'Actualización de configuración del sistema', '::1', '2025-11-16 14:21:56'),
(214, 16, 'optimizar', 'base_datos', 'Base de datos optimizada: 5 tablas', '::1', '2025-11-16 14:23:40'),
(215, 16, 'cambiar_vista_sede', 'superadmin', 'Vista de sede cambiada a: Sede Central', '::1', '2025-11-16 14:25:44'),
(216, 16, 'cambiar_vista_sede', 'superadmin', 'Vista de sede cambiada a: Global', '::1', '2025-11-16 14:25:54'),
(217, 16, 'cambiar_vista_sede', 'superadmin', 'Vista de sede cambiada a: Global', '::1', '2025-11-16 14:25:56'),
(218, 16, 'cambiar_vista_sede', 'superadmin', 'Vista de sede cambiada a: Global', '::1', '2025-11-16 14:25:57'),
(219, 16, 'cambiar_vista_sede', 'superadmin', 'Vista de sede cambiada a: Global', '::1', '2025-11-16 14:26:00'),
(220, 16, 'cambiar_vista_sede', 'superadmin', 'Vista de sede cambiada a: Global', '::1', '2025-11-16 14:26:01'),
(221, 16, 'cambiar_vista_sede', 'superadmin', 'Vista de sede cambiada a: Sede Central', '::1', '2025-11-16 14:26:03'),
(222, 16, 'cambiar_vista_sede', 'superadmin', 'Vista de sede cambiada a: Global', '::1', '2025-11-16 14:26:05'),
(223, 16, 'cambiar_vista_sede', 'superadmin', 'Vista de sede cambiada a: Sede Central', '::1', '2025-11-16 14:26:33'),
(224, 16, 'subir_imagen', 'perfil', 'Imagen de perfil actualizada', '::1', '2025-11-16 14:37:59'),
(225, 16, 'cambiar_vista_sede', 'superadmin', 'Vista de sede cambiada a: Sede Central', '::1', '2025-11-16 14:39:19'),
(226, 16, 'cambiar_vista_sede', 'superadmin', 'Vista de sede cambiada a: Sede Central', '::1', '2025-11-16 14:39:21'),
(227, 16, 'actualizar_sede', 'sedes', 'Sede actualizada: Sede Sur (SS03)', '::1', '2025-11-16 14:40:44'),
(228, 16, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-16 14:40:48'),
(229, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-16 14:40:55'),
(230, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-16 14:41:17'),
(231, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-16 14:41:32'),
(232, 16, 'crear_administrador', 'usuarios', 'Administrador creado: JUAN VELARDE (admin2) - Sede ID: 3', '::1', '2025-11-16 14:42:59'),
(233, 16, 'actualizar_sede', 'sedes', 'Sede actualizada: Sede Sur (SS03)', '::1', '2025-11-16 14:43:17'),
(234, 16, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-16 14:43:23'),
(236, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-17 09:03:26'),
(237, 16, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-17 09:03:37'),
(238, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-17 09:24:54'),
(239, 6, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-17 09:25:05'),
(240, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-17 09:25:45'),
(241, 16, 'crear_administrador', 'usuarios', 'Administrador creado: Juan Alvarado (adminTalara) [sede:1]', '::1', '2025-11-17 09:42:35'),
(242, 16, 'crear_sede', 'sedes', 'Sede creada: Talara (C03) [sede:1]', '::1', '2025-11-17 09:43:23'),
(243, 16, 'actualizar_sede', 'sedes', 'Sede actualizada: Talara (C03) [sede:1]', '::1', '2025-11-17 09:43:48'),
(244, 16, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 09:44:17'),
(245, 18, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-17 09:44:26'),
(246, 18, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-17 09:50:58'),
(247, 18, 'login', 'autenticacion', 'Inicio de sesión exitoso', '::1', '2025-11-17 09:51:18'),
(248, 18, 'logout', 'autenticacion', 'Cierre de sesión', '::1', '2025-11-17 10:21:13'),
(249, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 10:21:22'),
(250, 16, 'eliminar_seleccionados', 'migraciones', 'Registros eliminados: 1 migraciones [sede:1]', '::1', '2025-11-17 10:27:08'),
(251, 16, 'editar_global', 'usuarios', 'Usuario actualizado ID: 18 [sede:1]', '::1', '2025-11-17 10:27:45'),
(252, 16, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 10:27:54'),
(253, 18, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:6]', '::1', '2025-11-17 10:28:08'),
(254, 18, 'editar', 'sedes', 'Sede actualizada ID: 6 [sede:6]', '::1', '2025-11-17 10:30:57'),
(255, 18, 'logout', 'autenticacion', 'Cierre de sesión [sede:6]', '::1', '2025-11-17 10:31:22'),
(256, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 10:31:30'),
(257, 16, 'crear_administrador', 'usuarios', 'Administrador creado: Daniel Campos Tavara (adminPiura) [sede:1]', '::1', '2025-11-17 10:35:15'),
(258, 16, 'crear_sede', 'sedes', 'Sede creada: SEDE PIURA (CO4) [sede:1]', '::1', '2025-11-17 10:36:04'),
(259, 16, 'editar_global', 'usuarios', 'Usuario actualizado ID: 19 [sede:1]', '::1', '2025-11-17 10:36:19'),
(260, 16, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 10:38:42'),
(261, 19, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:7]', '::1', '2025-11-17 10:38:49'),
(262, 19, 'crear', 'usuarios', 'Usuario creado: jefealmp [sede:7]', '::1', '2025-11-17 10:40:30'),
(263, 19, 'crear', 'usuarios', 'Usuario creado: jefealm2 [sede:7]', '::1', '2025-11-17 10:46:53'),
(264, 19, 'logout', 'autenticacion', 'Cierre de sesión [sede:7]', '::1', '2025-11-17 10:52:22'),
(267, 19, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:7]', '::1', '2025-11-17 10:53:19'),
(268, 19, 'crear', 'usuarios', 'Usuario creado: jefealm3 [sede:7]', '::1', '2025-11-17 10:54:28'),
(269, 19, 'logout', 'autenticacion', 'Cierre de sesión [sede:7]', '::1', '2025-11-17 10:54:38'),
(272, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 11:00:30'),
(273, 16, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 11:01:32'),
(274, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 11:01:57'),
(275, 16, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 11:02:17'),
(276, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 11:02:23'),
(277, 6, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 11:12:53'),
(278, 19, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:7]', '::1', '2025-11-17 11:13:05'),
(279, 19, 'importar', 'materiales', 'Importados: 0, Actualizados: 500 [sede:7]', '::1', '2025-11-17 11:13:20'),
(280, 19, 'logout', 'autenticacion', 'Cierre de sesión [sede:7]', '::1', '2025-11-17 11:13:42'),
(281, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 11:13:56'),
(282, 16, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 11:14:36'),
(283, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 16:16:28'),
(284, 16, 'eliminar_global', 'usuarios', 'Usuario eliminado: admin2 [sede:1]', '::1', '2025-11-17 16:24:57'),
(285, 16, 'eliminar_global', 'usuarios', 'Usuario eliminado: jefealm3 [sede:1]', '::1', '2025-11-17 16:25:19'),
(286, 16, 'eliminar_global', 'usuarios', 'Usuario eliminado: jefealm2 [sede:1]', '::1', '2025-11-17 16:25:24'),
(287, 16, 'eliminar_global', 'usuarios', 'Usuario eliminado: jefealmp [sede:1]', '::1', '2025-11-17 16:25:32'),
(288, 16, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 16:25:48'),
(289, 19, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:7]', '::1', '2025-11-17 16:25:56'),
(290, 19, 'logout', 'autenticacion', 'Cierre de sesión [sede:7]', '::1', '2025-11-17 16:28:20'),
(291, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 16:28:27'),
(292, 16, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 16:28:44'),
(293, 19, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:7]', '::1', '2025-11-17 16:28:58'),
(294, 19, 'importar', 'materiales', 'Importados: 500, Actualizados: 0 [sede:7]', '::1', '2025-11-17 16:30:11'),
(295, 19, 'logout', 'autenticacion', 'Cierre de sesión [sede:7]', '::1', '2025-11-17 16:30:27'),
(296, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 16:30:34'),
(297, 16, 'cambiar_vista_sede', 'superadmin', 'Vista de sede cambiada a: SEDE PIURA [sede:1]', '::1', '2025-11-17 16:31:34'),
(298, 16, 'cambiar_vista_sede', 'superadmin', 'Vista de sede cambiada a: Global [sede:1]', '::1', '2025-11-17 16:32:41'),
(299, 16, 'generar', 'respaldos', 'Respaldo de BD generado: backup_20251117_163629.sql [sede:1]', '::1', '2025-11-17 16:36:30'),
(300, 16, 'eliminar', 'respaldos', 'Respaldo de BD eliminado: backup_20251117_163629.sql [sede:1]', '::1', '2025-11-17 16:36:47'),
(301, 16, 'generar', 'respaldos', 'Respaldo de BD generado: backup_20251117_163948.sql [sede:1]', '::1', '2025-11-17 16:39:48'),
(302, 16, 'cambiar_vista_sede', 'superadmin', 'Vista de sede cambiada a: Talara [sede:1]', '::1', '2025-11-17 16:40:40'),
(303, 16, 'cambiar_vista_sede', 'superadmin', 'Vista de sede cambiada a: Talara [sede:1]', '::1', '2025-11-17 16:40:46'),
(304, 16, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 16:49:31'),
(305, 18, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:6]', '::1', '2025-11-17 16:49:40'),
(306, 18, 'logout', 'autenticacion', 'Cierre de sesión [sede:6]', '::1', '2025-11-17 16:54:26'),
(307, 19, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:7]', '::1', '2025-11-17 16:54:34'),
(308, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:54:45'),
(309, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:54:49'),
(310, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:54:52'),
(311, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:54:54'),
(312, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:54:57'),
(313, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:54:59'),
(314, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:55:02'),
(315, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:55:04'),
(316, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:55:07'),
(317, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:55:09'),
(318, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:55:12'),
(319, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:55:15'),
(320, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:55:17'),
(321, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:55:19'),
(322, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:55:22'),
(323, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:55:26'),
(324, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:55:29'),
(325, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:55:32'),
(326, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:55:35'),
(327, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:55:37'),
(328, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:55:39'),
(329, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:55:42'),
(330, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:55:45'),
(331, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:55:47'),
(332, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 16:55:50'),
(333, 19, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:7]', '::1', '2025-11-17 17:08:38'),
(334, 19, 'importar', 'materiales', 'Importados: 100, Actualizados: 0 [sede:7]', '::1', '2025-11-17 17:11:14'),
(335, 19, 'logout', 'autenticacion', 'Cierre de sesión [sede:7]', '::1', '2025-11-17 17:12:16'),
(336, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 17:12:27'),
(337, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:13:21'),
(338, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:13:24'),
(339, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:13:27'),
(340, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:13:30'),
(341, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:13:32'),
(342, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:13:36'),
(343, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:13:38'),
(344, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:13:41'),
(345, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:13:43'),
(346, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:13:46'),
(347, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:13:48'),
(348, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:13:51'),
(349, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:13:53'),
(350, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:13:55'),
(351, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:13:59'),
(352, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:14:02'),
(353, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:14:05'),
(354, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:14:08'),
(355, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:14:10'),
(356, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:14:14'),
(357, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:14:16'),
(358, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:14:19'),
(359, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:14:21'),
(360, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:14:24'),
(361, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 17:14:28'),
(362, 6, 'importar', 'materiales', 'Importados: 200, Actualizados: 0 [sede:1]', '::1', '2025-11-17 17:14:40'),
(363, 6, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 17:15:25'),
(364, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 17:15:36'),
(365, 16, 'actualizar', 'configuracion', 'Actualización de configuración del sistema [sede:1]', '::1', '2025-11-17 17:17:15'),
(366, 16, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 17:17:20'),
(367, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 17:17:33'),
(368, 16, 'actualizar', 'configuracion', 'Actualización de configuración del sistema [sede:1]', '::1', '2025-11-17 17:17:46'),
(369, 16, 'optimizar', 'base_datos', 'Base de datos optimizada: 5 tablas [sede:1]', '::1', '2025-11-17 17:18:21'),
(370, 16, 'actualizar', 'configuracion', 'Actualización de configuración del sistema [sede:1]', '::1', '2025-11-17 17:24:08'),
(371, 16, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 17:24:13'),
(372, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 17:24:36'),
(373, 16, 'actualizar', 'configuracion', 'Actualización de configuración del sistema [sede:1]', '::1', '2025-11-17 17:26:58'),
(374, 16, 'actualizar_sede', 'sedes', 'Sede actualizada: SEDE TALARA (C03) [sede:1]', '::1', '2025-11-17 18:06:36'),
(375, 16, 'actualizar_sede', 'sedes', 'Sede actualizada: SEDE CENTRAL LIMA (SC01) [sede:1]', '::1', '2025-11-17 18:06:47'),
(376, 16, 'actualizar_sede', 'sedes', 'Sede actualizada: SEDE CENTRAL LIMA (SC01) [sede:1]', '::1', '2025-11-17 18:07:16'),
(377, 16, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 18:07:37'),
(378, 19, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:7]', '::1', '2025-11-17 18:07:47'),
(379, 19, 'logout', 'autenticacion', 'Cierre de sesión [sede:7]', '::1', '2025-11-17 18:08:02'),
(380, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 18:08:10'),
(381, 16, 'editar_global', 'usuarios', 'Usuario actualizado ID: 9 [sede:1]', '::1', '2025-11-17 18:08:48'),
(382, 16, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 18:08:59'),
(386, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 18:11:22'),
(388, 6, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 18:11:36'),
(391, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 18:15:58'),
(392, 6, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 18:16:38'),
(397, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 18:31:05'),
(398, 6, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 18:31:12'),
(405, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 18:46:07'),
(406, 6, 'actualizar', 'materiales', 'Material actualizado: Cable de Fibra Óptica Monomodo [sede:1]', '::1', '2025-11-17 18:48:55'),
(407, 6, 'actualizar', 'materiales', 'Material actualizado: Cable de Fibra Óptica Monomodo [sede:1]', '::1', '2025-11-17 18:51:02'),
(408, 6, 'actualizar', 'materiales', 'Material actualizado: Kit de Herramientas para Fibra Óptica [sede:1]', '::1', '2025-11-17 18:53:12'),
(409, 6, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 18:57:37'),
(412, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 18:58:47'),
(413, 6, 'eliminar', 'usuarios', 'Usuario eliminado: tecnico1 [sede:1]', '::1', '2025-11-17 19:03:30'),
(414, 6, 'eliminar', 'usuarios', 'Usuario eliminado: jefealm1 [sede:1]', '::1', '2025-11-17 19:10:11'),
(415, 6, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 19:14:02'),
(416, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 19:14:35'),
(417, 6, 'crear', 'usuarios', 'Usuario creado: jefealm1 [sede:1]', '::1', '2025-11-17 19:15:13'),
(418, 6, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 19:15:40'),
(419, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 19:15:49'),
(420, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 19:16:11'),
(421, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 19:16:18'),
(422, 6, 'crear', 'usuarios', 'Usuario creado: tecnico1 [sede:1]', '::1', '2025-11-17 19:16:53'),
(423, 6, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 19:17:07'),
(424, 24, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 19:17:13'),
(425, 24, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 19:17:16'),
(426, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 19:17:30'),
(427, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 19:28:09'),
(428, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 19:28:16'),
(429, 6, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 19:35:02'),
(430, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 19:35:09'),
(431, 16, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 19:36:08'),
(432, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 19:36:19'),
(433, 6, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 19:37:05'),
(434, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 19:37:19'),
(435, 6, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 19:37:34'),
(436, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 19:37:42'),
(437, 23, 'crear', 'asignacion_tecnicos', 'Nueva asignación para técnico ID: 24 [sede:1]', '::1', '2025-11-17 20:41:14'),
(438, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 20:43:15'),
(439, 24, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 20:43:22'),
(440, 24, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 20:44:41'),
(441, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 20:44:53'),
(442, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 21:03:46'),
(443, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 21:03:55'),
(444, 16, 'generar', 'respaldos', 'Respaldo de BD generado: backup_20251117_210828.sql [sede:1]', '::1', '2025-11-17 21:08:28'),
(445, 16, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 21:09:43'),
(446, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 21:09:53'),
(447, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 21:14:18'),
(450, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 21:15:35'),
(451, 6, 'eliminar', 'usuarios', 'Usuario eliminado: asistalm1 [sede:1]', '::1', '2025-11-17 21:15:48'),
(452, 6, 'crear', 'usuarios', 'Usuario creado: asistalm1 [sede:1]', '::1', '2025-11-17 21:16:37'),
(453, 6, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 21:16:42'),
(454, 25, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 21:16:48'),
(455, 25, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-17 21:20:34'),
(456, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-17 21:20:46'),
(457, 6, 'eliminar_masa', 'materiales', 'Eliminados: 1 materiales [sede:1]', '::1', '2025-11-17 21:20:59'),
(458, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 706 [sede:1]', '::1', '2025-11-17 21:21:09'),
(459, 6, 'eliminar_masa', 'materiales', 'Eliminados: 1 materiales [sede:1]', '::1', '2025-11-17 21:21:22'),
(460, 6, 'crear', 'materiales', 'Material creado: RJ45 [sede:1]', '::1', '2025-11-17 21:22:28'),
(461, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 21:32:41'),
(462, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 21:32:43'),
(463, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 21:32:46'),
(464, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 21:32:49'),
(465, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 21:32:52'),
(466, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 21:32:55'),
(467, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 21:32:58'),
(468, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 21:33:00'),
(469, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 21:33:03'),
(470, 6, 'eliminar_masa', 'materiales', 'Eliminados: 20 materiales [sede:1]', '::1', '2025-11-17 21:33:06'),
(471, 6, 'importar', 'materiales', 'Importados: 0, Actualizados: 200 [sede:1]', '::1', '2025-11-17 21:38:13'),
(472, 6, 'crear', 'materiales', 'Material creado: Cable UTP [sede:1]', '::1', '2025-11-17 21:44:34'),
(473, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 706 [sede:1]', '::1', '2025-11-17 21:53:59'),
(474, 6, 'eliminar_masa', 'materiales', 'Eliminados: 1 materiales [sede:1]', '::1', '2025-11-17 21:54:32'),
(475, 6, 'actualizar', 'materiales', 'Material actualizado: Antenas 706 [sede:1]', '::1', '2025-11-17 21:54:45'),
(476, 6, 'eliminar_masa', 'materiales', 'Eliminados: 1 materiales [sede:1]', '::1', '2025-11-17 21:54:55'),
(477, 6, 'eliminar_masa', 'materiales', 'Eliminados: 1 materiales [sede:1]', '::1', '2025-11-17 22:01:30'),
(478, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 08:03:51'),
(479, 6, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 08:07:20'),
(480, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 08:07:27'),
(481, 23, 'crear', 'asignacion_tecnicos', 'Nueva asignación para técnico ID: 24 [sede:1]', '::1', '2025-11-18 08:28:03'),
(482, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 08:28:16'),
(483, 24, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 08:28:22'),
(484, 24, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 08:42:17'),
(485, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 08:42:24'),
(486, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 08:42:32'),
(487, 25, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 08:42:37'),
(488, 25, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 08:42:42'),
(489, 24, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 08:42:48'),
(490, 24, 'crear', 'actas', 'Acta creada: ACT-20251118-3368DD [sede:1]', '::1', '2025-11-18 08:46:11'),
(491, 24, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 08:46:13'),
(492, 25, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 08:46:25'),
(493, 25, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 08:59:32'),
(494, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 08:59:41'),
(495, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 09:11:26'),
(496, 24, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 09:11:33'),
(497, 24, 'crear', 'actas', 'Acta creada: ACT-20251118-724615 [sede:1]', '::1', '2025-11-18 09:12:23'),
(498, 24, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 09:12:24'),
(499, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 09:12:57'),
(500, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 09:19:12'),
(501, 24, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 09:19:18'),
(502, 24, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 09:19:44'),
(503, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 09:19:51'),
(504, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 09:19:56'),
(505, 25, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 09:20:03'),
(506, 25, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 11:32:42'),
(507, 24, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 11:32:51'),
(508, 24, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 11:33:22'),
(509, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 11:33:35'),
(510, 6, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 11:33:41'),
(511, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 11:33:49'),
(512, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 11:34:01'),
(513, 24, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 11:34:07'),
(514, 24, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 11:34:46'),
(515, 25, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 11:34:54'),
(516, 25, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 12:07:17'),
(517, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 12:07:23'),
(518, 6, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 12:07:39'),
(519, 24, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 12:07:46'),
(520, 24, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 12:10:25'),
(521, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 12:10:35'),
(522, 16, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 12:12:25'),
(523, 24, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 12:12:32'),
(524, 24, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 12:12:59'),
(525, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 12:13:14'),
(526, 6, 'eliminar', 'unidades_medida', 'Unidad eliminada ID: 8 [sede:1]', '::1', '2025-11-18 12:18:03'),
(527, 6, 'eliminar', 'unidades_medida', 'Unidad eliminada ID: 7 [sede:1]', '::1', '2025-11-18 12:18:06'),
(528, 6, 'eliminar', 'unidades_medida', 'Unidad eliminada ID: 4 [sede:1]', '::1', '2025-11-18 12:18:10'),
(529, 6, 'eliminar', 'unidades_medida', 'Unidad eliminada ID: 9 [sede:1]', '::1', '2025-11-18 12:18:20'),
(530, 6, 'eliminar', 'unidades_medida', 'Unidad eliminada ID: 5 [sede:1]', '::1', '2025-11-18 12:18:22'),
(531, 6, 'eliminar', 'unidades_medida', 'Unidad eliminada ID: 10 [sede:1]', '::1', '2025-11-18 12:18:23'),
(532, 6, 'eliminar', 'unidades_medida', 'Unidad eliminada ID: 1 [sede:1]', '::1', '2025-11-18 12:18:25'),
(533, 6, 'eliminar', 'unidades_medida', 'Unidad eliminada ID: 2 [sede:1]', '::1', '2025-11-18 12:18:27'),
(534, 6, 'eliminar', 'unidades_medida', 'Unidad eliminada ID: 6 [sede:1]', '::1', '2025-11-18 12:18:28'),
(535, 6, 'crear', 'unidades_medida', 'Unidad creada: Unidad [sede:1]', '::1', '2025-11-18 12:18:46'),
(536, 6, 'crear', 'unidades_medida', 'Unidad creada: Kit [sede:1]', '::1', '2025-11-18 12:19:46'),
(537, 6, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 12:24:47'),
(538, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 12:24:59'),
(539, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 12:26:23'),
(540, 25, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 12:26:37'),
(541, 25, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 12:29:06'),
(542, 24, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 12:29:13'),
(543, 24, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 12:29:23'),
(544, 25, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 12:29:31'),
(545, 25, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 12:36:57'),
(546, 24, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 12:37:04'),
(547, 24, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 12:43:39'),
(548, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 12:44:33'),
(549, 23, 'crear', 'asignacion_tecnicos', 'Nueva asignación para técnico ID: 24 [sede:1]', '::1', '2025-11-18 12:45:57'),
(550, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 12:46:06'),
(551, 24, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 12:46:15'),
(552, 24, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 12:46:57'),
(553, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 12:47:05'),
(554, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 12:53:06'),
(555, 24, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 12:53:21');
INSERT INTO `historial_actividades` (`id`, `usuario_id`, `accion`, `modulo`, `descripcion`, `ip_address`, `fecha`) VALUES
(556, 24, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 13:06:17'),
(557, 25, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 13:06:26'),
(558, 25, 'crear', 'entradas_materiales', 'Entrada registrada: Material ID 2614, Cantidad: 3 [sede:1]', '::1', '2025-11-18 13:15:37'),
(559, 19, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:7]', '::1', '2025-11-18 16:34:11'),
(560, 19, 'crear', 'usuarios', 'Usuario creado: jefealmpiura [sede:7]', '::1', '2025-11-18 16:35:18'),
(561, 19, 'logout', 'autenticacion', 'Cierre de sesión [sede:7]', '::1', '2025-11-18 16:35:26'),
(562, 26, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:7]', '::1', '2025-11-18 16:35:31'),
(563, 26, 'crear', 'entradas_materiales', 'Entrada registrada: Material ID 2225, Cantidad: 1 [sede:7]', '::1', '2025-11-18 16:36:08'),
(564, 26, 'crear', 'entradas_materiales', 'Entrada registrada: Material ID 2225, Cantidad: 5 [sede:7]', '::1', '2025-11-18 16:36:33'),
(565, 26, 'crear', 'salidas_materiales', 'Salida registrada: Material ID 2205, Cantidad: 2 [sede:7]', '::1', '2025-11-18 16:40:31'),
(566, 26, 'logout', 'autenticacion', 'Cierre de sesión [sede:7]', '::1', '2025-11-18 16:46:47'),
(567, 19, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:7]', '::1', '2025-11-18 16:47:04'),
(568, 19, 'editar', 'usuarios', 'Usuario actualizado ID: 26 [sede:7]', '::1', '2025-11-18 16:47:31'),
(569, 19, 'editar', 'usuarios', 'Usuario actualizado ID: 26 [sede:7]', '::1', '2025-11-18 16:47:36'),
(570, 19, 'logout', 'autenticacion', 'Cierre de sesión [sede:7]', '::1', '2025-11-18 16:47:38'),
(571, 26, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:7]', '::1', '2025-11-18 16:47:48'),
(572, 26, 'logout', 'autenticacion', 'Cierre de sesión [sede:7]', '::1', '2025-11-18 17:04:54'),
(573, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 17:05:05'),
(574, 23, 'crear', 'salidas_materiales', 'Salida registrada: Alicate de Crimpado - Cantidad: 2 [sede:1]', '::1', '2025-11-18 17:24:35'),
(575, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 17:33:53'),
(576, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 17:34:02'),
(577, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 17:34:56'),
(578, 16, 'crear_sede', 'sedes', 'Sede creada: SEDE PAITA (COS04) [sede:1]', '::1', '2025-11-18 17:35:53'),
(579, 16, 'generar', 'respaldos', 'Respaldo de BD generado: backup_20251118_173626.sql [sede:1]', '::1', '2025-11-18 17:36:27'),
(580, 16, 'optimizar', 'base_datos', 'Base de datos optimizada: 5 tablas [sede:1]', '::1', '2025-11-18 17:37:38'),
(581, 16, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 17:38:07'),
(582, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 17:38:16'),
(583, 6, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 17:38:55'),
(584, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 17:39:03'),
(585, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 17:39:23'),
(586, 26, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:7]', '::1', '2025-11-18 17:39:34'),
(587, 26, 'logout', 'autenticacion', 'Cierre de sesión [sede:7]', '::1', '2025-11-18 17:40:11'),
(588, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 17:40:18'),
(589, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 17:40:27'),
(590, 24, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 17:40:33'),
(591, 24, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 17:40:51'),
(592, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 17:40:58'),
(593, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 17:41:03'),
(594, 24, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 17:41:10'),
(595, 24, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 17:43:09'),
(596, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 17:43:15'),
(597, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 17:43:19'),
(598, 25, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 17:43:28'),
(599, 25, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 17:43:53'),
(600, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 17:44:01'),
(601, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 17:44:31'),
(602, 25, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 17:44:37'),
(603, 25, 'crear', 'salidas_materiales', 'Salida registrada: Material ID 2614, Cantidad: 1 [sede:1]', '::1', '2025-11-18 17:54:48'),
(604, 25, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 17:58:57'),
(605, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 17:59:03'),
(606, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 17:59:22'),
(607, 24, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 17:59:35'),
(608, 24, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 17:59:43'),
(609, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 17:59:58'),
(610, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 18:03:38'),
(611, 24, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 18:03:45'),
(612, 24, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 18:12:00'),
(613, 23, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 18:12:07'),
(614, 23, 'eliminar', 'actas_tecnicas', 'Acta eliminada: ID 3 [sede:1]', '::1', '2025-11-18 18:20:12'),
(615, 23, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 18:20:14'),
(616, 24, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 18:20:20'),
(617, 24, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 18:22:08'),
(618, 16, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 18:22:19'),
(619, 16, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 18:25:18'),
(620, 19, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:7]', '::1', '2025-11-18 18:25:25'),
(621, 19, 'logout', 'autenticacion', 'Cierre de sesión [sede:7]', '::1', '2025-11-18 18:26:10'),
(622, 6, 'solicitud_recuperacion', 'autenticacion', 'Solicitud de recuperación de contraseña', '::1', '2025-11-18 18:26:25'),
(623, 6, 'cambio_password', 'autenticacion', 'Contraseña cambiada mediante recuperación', '::1', '2025-11-18 18:26:44'),
(624, 6, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 18:29:18'),
(625, 6, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 18:29:21'),
(626, 25, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 18:32:31'),
(627, 25, 'cambiar_password', 'perfil', 'Contraseña cambiada [sede:1]', '::1', '2025-11-18 18:33:06'),
(628, 25, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 18:33:09'),
(629, 25, 'login', 'autenticacion', 'Inicio de sesión exitoso [sede:1]', '::1', '2025-11-18 18:33:21'),
(630, 25, 'cambiar_password', 'perfil', 'Contraseña cambiada [sede:1]', '::1', '2025-11-18 18:33:36'),
(631, 25, 'logout', 'autenticacion', 'Cierre de sesión [sede:1]', '::1', '2025-11-18 18:34:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiales`
--

CREATE TABLE `materiales` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `sede_id` int(11) DEFAULT NULL,
  `unidad` varchar(20) NOT NULL,
  `proveedor_id` int(11) DEFAULT NULL,
  `costo_unitario` decimal(10,2) DEFAULT 0.00,
  `stock_actual` int(11) DEFAULT 0,
  `stock_minimo` int(11) DEFAULT 0,
  `stock_maximo` int(11) DEFAULT 0,
  `ubicacion` varchar(100) DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `lote` varchar(100) DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `materiales`
--

INSERT INTO `materiales` (`id`, `codigo`, `nombre`, `descripcion`, `categoria_id`, `sede_id`, `unidad`, `proveedor_id`, `costo_unitario`, `stock_actual`, `stock_minimo`, `stock_maximo`, `ubicacion`, `fecha_vencimiento`, `lote`, `estado`, `created_at`, `updated_at`) VALUES
(284, 'MAT-0084', 'Antenas 706', 'Antenas 706 utilizado en instalación y mantenimiento de red de telecomunicaciones.', NULL, 1, 'unidad', 199, 1128.32, 387, 15, 0, 'Sucursal Norte', NULL, NULL, 'inactivo', '2025-11-14 22:25:32', '2025-11-18 03:01:30'),
(2196, 'INS-001', 'Router Mesh', 'Router Mesh de alta calidad para uso en telecomunicaciones.', 3, 7, 'Unidad', 202, 181.86, 116, 7, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2197, 'INS-002', 'Crimpadora', 'Crimpadora de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 203, 411.67, 97, 10, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2198, 'INS-003', 'Cable de Fibra Óptica Multimodo', 'Cable de Fibra Óptica Multimodo de alta calidad para uso en telecomunicaciones.', 1, 7, 'Unidad', 204, 249.84, 187, 17, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2199, 'INS-004', 'Router Mesh', 'Router Mesh de alta calidad para uso en telecomunicaciones.', 3, 7, 'Unidad', 205, 418.29, 107, 6, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2200, 'INS-005', 'Repetidor WiFi para Larga Distancia', 'Repetidor WiFi para Larga Distancia de alta calidad para uso en telecomunicaciones.', 4, 7, 'Unidad', 206, 51.59, 194, 12, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2201, 'INS-006', 'Repetidor WiFi Mesh', 'Repetidor WiFi Mesh de alta calidad para uso en telecomunicaciones.', 4, 7, 'Unidad', 207, 42.63, 83, 9, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2202, 'INS-007', 'Router Gigabit', 'Router Gigabit de alta calidad para uso en telecomunicaciones.', 3, 7, 'Unidad', 208, 169.88, 175, 9, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2203, 'INS-008', 'Pelacables', 'Pelacables de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 202, 259.71, 70, 13, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2204, 'INS-009', 'Cable USB', 'Cable USB de alta calidad para uso en telecomunicaciones.', 1, 7, 'Unidad', 206, 123.86, 178, 9, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2205, 'INS-010', 'Cable Coaxial RG59', 'Cable Coaxial RG59 de alta calidad para uso en telecomunicaciones.', 1, 7, 'Unidad', 209, 18.86, 163, 6, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-18 21:40:31'),
(2206, 'INS-011', 'Conector N', 'Conector N de alta calidad para uso en telecomunicaciones.', 2, 7, 'Unidad', 210, 234.07, 47, 5, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2207, 'INS-012', 'Cable UTP Cat6', 'Cable UTP Cat6 de alta calidad para uso en telecomunicaciones.', 1, 7, 'Unidad', 209, 441.81, 59, 11, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2208, 'INS-013', 'Repetidor WiFi Dual Band', 'Repetidor WiFi Dual Band de alta calidad para uso en telecomunicaciones.', 4, 7, 'Unidad', 211, 473.02, 42, 11, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2209, 'INS-014', 'Router para Hogar', 'Router para Hogar de alta calidad para uso en telecomunicaciones.', 3, 7, 'Unidad', 209, 237.01, 96, 7, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2210, 'INS-015', 'Repetidor WiFi para Larga Distancia', 'Repetidor WiFi para Larga Distancia de alta calidad para uso en telecomunicaciones.', 4, 7, 'Unidad', 209, 453.06, 191, 14, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2211, 'INS-016', 'Cable de Red Externo', 'Cable de Red Externo de alta calidad para uso en telecomunicaciones.', 1, 7, 'Unidad', 206, 323.37, 181, 16, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2212, 'INS-017', 'Repetidor WiFi Dual Band', 'Repetidor WiFi Dual Band de alta calidad para uso en telecomunicaciones.', 4, 7, 'Unidad', 211, 270.86, 101, 20, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2213, 'INS-018', 'Multímetro Digital', 'Multímetro Digital de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 210, 392.73, 170, 9, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2214, 'INS-019', 'Repetidor WiFi Dual Band', 'Repetidor WiFi Dual Band de alta calidad para uso en telecomunicaciones.', 4, 7, 'Unidad', 203, 202.79, 169, 11, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2215, 'INS-020', 'Cable HDMI', 'Cable HDMI de alta calidad para uso en telecomunicaciones.', 1, 7, 'Unidad', 210, 278.62, 113, 19, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2216, 'INS-021', 'Alicate de Crimpado', 'Alicate de Crimpado de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 207, 194.66, 79, 8, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2217, 'INS-022', 'Crimpadora', 'Crimpadora de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 207, 490.84, 181, 9, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2218, 'INS-023', 'Conector ST', 'Conector ST de alta calidad para uso en telecomunicaciones.', 2, 7, 'Unidad', 207, 86.48, 101, 15, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2219, 'INS-024', 'Repetidor WiFi con Antena', 'Repetidor WiFi con Antena de alta calidad para uso en telecomunicaciones.', 4, 7, 'Unidad', 206, 165.88, 79, 14, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2220, 'INS-025', 'Conector ST', 'Conector ST de alta calidad para uso en telecomunicaciones.', 2, 7, 'Unidad', 211, 145.99, 45, 15, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2221, 'INS-026', 'Repetidor WiFi Dual Band', 'Repetidor WiFi Dual Band de alta calidad para uso en telecomunicaciones.', 4, 7, 'Unidad', 206, 485.07, 98, 19, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2222, 'INS-027', 'Kit de Herramientas para Redes', 'Kit de Herramientas para Redes de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 211, 126.40, 71, 11, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2223, 'INS-028', 'Conector SC', 'Conector SC de alta calidad para uso en telecomunicaciones.', 2, 7, 'Unidad', 203, 228.36, 92, 18, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2224, 'INS-029', 'Cable Coaxial RG6', 'Cable Coaxial RG6 de alta calidad para uso en telecomunicaciones.', 1, 7, 'Unidad', 203, 386.32, 63, 11, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2225, 'INS-030', 'Cable de Red Externo', 'Cable de Red Externo de alta calidad para uso en telecomunicaciones.', 1, 7, 'Unidad', 210, 471.41, 48, 6, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-18 21:36:33'),
(2226, 'INS-031', 'Router Inalámbrico Dual Band', 'Router Inalámbrico Dual Band de alta calidad para uso en telecomunicaciones.', 3, 7, 'Unidad', 203, 283.81, 39, 13, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2227, 'INS-032', 'Conector F', 'Conector F de alta calidad para uso en telecomunicaciones.', 2, 7, 'Unidad', 202, 45.87, 121, 12, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2228, 'INS-033', 'Conector SMA', 'Conector SMA de alta calidad para uso en telecomunicaciones.', 2, 7, 'Unidad', 202, 344.79, 17, 17, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2229, 'INS-034', 'Conector ST', 'Conector ST de alta calidad para uso en telecomunicaciones.', 2, 7, 'Unidad', 203, 422.60, 136, 11, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2230, 'INS-035', 'Conector N', 'Conector N de alta calidad para uso en telecomunicaciones.', 2, 7, 'Unidad', 206, 159.77, 47, 17, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2231, 'INS-036', 'Conector FC', 'Conector FC de alta calidad para uso en telecomunicaciones.', 2, 7, 'Unidad', 206, 212.68, 19, 10, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2232, 'INS-037', 'Router Mesh', 'Router Mesh de alta calidad para uso en telecomunicaciones.', 3, 7, 'Unidad', 206, 497.09, 100, 19, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2233, 'INS-038', 'Router Gigabit', 'Router Gigabit de alta calidad para uso en telecomunicaciones.', 3, 7, 'Unidad', 209, 323.89, 84, 9, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2234, 'INS-039', 'Router VPN', 'Router VPN de alta calidad para uso en telecomunicaciones.', 3, 7, 'Unidad', 211, 34.17, 91, 13, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2235, 'INS-040', 'Conector F', 'Conector F de alta calidad para uso en telecomunicaciones.', 2, 7, 'Unidad', 207, 405.32, 116, 6, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2236, 'INS-041', 'Cortador de Cables', 'Cortador de Cables de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 209, 308.98, 43, 15, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2237, 'INS-042', 'Repetidor WiFi Dual Band', 'Repetidor WiFi Dual Band de alta calidad para uso en telecomunicaciones.', 4, 7, 'Unidad', 203, 374.63, 156, 8, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2238, 'INS-043', 'Kit de Herramientas para Fibra Óptica', 'Kit de Herramientas para Fibra Óptica de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 206, 227.75, 25, 13, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2239, 'INS-044', 'Cable UTP Cat5e', 'Cable UTP Cat5e de alta calidad para uso en telecomunicaciones.', 1, 7, 'Unidad', 202, 352.58, 43, 9, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2240, 'INS-045', 'Pelacables', 'Pelacables de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 206, 253.73, 70, 13, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2241, 'INS-046', 'Kit de Limpieza para Conectores', 'Kit de Limpieza para Conectores de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 204, 421.46, 184, 9, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2242, 'INS-047', 'Probador de Redes', 'Probador de Redes de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 202, 419.48, 155, 6, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2243, 'INS-048', 'Router 4G LTE', 'Router 4G LTE de alta calidad para uso en telecomunicaciones.', 3, 7, 'Unidad', 203, 86.17, 87, 10, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2244, 'INS-049', 'Cable UTP Cat6', 'Cable UTP Cat6 de alta calidad para uso en telecomunicaciones.', 1, 7, 'Unidad', 211, 382.95, 32, 11, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2245, 'INS-050', 'Conector LC', 'Conector LC de alta calidad para uso en telecomunicaciones.', 2, 7, 'Unidad', 204, 173.80, 140, 8, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2246, 'INS-051', 'Multímetro Digital', 'Multímetro Digital de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 209, 124.35, 108, 19, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2247, 'INS-052', 'Cable Coaxial RG6', 'Cable Coaxial RG6 de alta calidad para uso en telecomunicaciones.', 1, 7, 'Unidad', 203, 371.55, 118, 18, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2248, 'INS-053', 'Repetidor WiFi Dual Band', 'Repetidor WiFi Dual Band de alta calidad para uso en telecomunicaciones.', 4, 7, 'Unidad', 202, 400.47, 108, 15, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2249, 'INS-054', 'Cortador de Cables', 'Cortador de Cables de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 203, 148.44, 167, 12, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2250, 'INS-055', 'Repetidor WiFi con Antena', 'Repetidor WiFi con Antena de alta calidad para uso en telecomunicaciones.', 4, 7, 'Unidad', 208, 282.37, 180, 19, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2251, 'INS-056', 'Crimpadora', 'Crimpadora de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 203, 439.91, 126, 7, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2252, 'INS-057', 'Cable Coaxial RG6', 'Cable Coaxial RG6 de alta calidad para uso en telecomunicaciones.', 1, 7, 'Unidad', 209, 197.61, 81, 9, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2253, 'INS-058', 'Router Inalámbrico Dual Band', 'Router Inalámbrico Dual Band de alta calidad para uso en telecomunicaciones.', 3, 7, 'Unidad', 211, 455.21, 154, 12, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2254, 'INS-059', 'Router Industrial', 'Router Industrial de alta calidad para uso en telecomunicaciones.', 3, 7, 'Unidad', 204, 133.79, 175, 17, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2255, 'INS-060', 'Router Industrial', 'Router Industrial de alta calidad para uso en telecomunicaciones.', 3, 7, 'Unidad', 209, 278.36, 24, 18, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2256, 'INS-061', 'Cable de Red Externo', 'Cable de Red Externo de alta calidad para uso en telecomunicaciones.', 1, 7, 'Unidad', 209, 90.89, 13, 7, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2257, 'INS-062', 'Cortador de Cables', 'Cortador de Cables de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 203, 73.97, 30, 6, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2258, 'INS-063', 'Repetidor WiFi con Antena', 'Repetidor WiFi con Antena de alta calidad para uso en telecomunicaciones.', 4, 7, 'Unidad', 209, 415.13, 42, 17, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2259, 'INS-064', 'Probador de Redes', 'Probador de Redes de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 202, 210.75, 121, 19, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2260, 'INS-065', 'Cortador de Cables', 'Cortador de Cables de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 208, 343.31, 90, 5, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2261, 'INS-066', 'Router para Empresas', 'Router para Empresas de alta calidad para uso en telecomunicaciones.', 3, 7, 'Unidad', 208, 309.66, 50, 5, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2262, 'INS-067', 'Router Inalámbrico Dual Band', 'Router Inalámbrico Dual Band de alta calidad para uso en telecomunicaciones.', 3, 7, 'Unidad', 208, 254.19, 57, 15, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2263, 'INS-068', 'Router Inalámbrico Dual Band', 'Router Inalámbrico Dual Band de alta calidad para uso en telecomunicaciones.', 3, 7, 'Unidad', 211, 134.63, 76, 15, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2264, 'INS-069', 'Router con Firewall', 'Router con Firewall de alta calidad para uso en telecomunicaciones.', 3, 7, 'Unidad', 206, 323.15, 132, 16, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2265, 'INS-070', 'Repetidor WiFi Exterior', 'Repetidor WiFi Exterior de alta calidad para uso en telecomunicaciones.', 4, 7, 'Unidad', 205, 164.95, 176, 8, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2266, 'INS-071', 'Kit de Herramientas para Fibra Óptica', 'Kit de Herramientas para Fibra Óptica de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 207, 43.04, 158, 9, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2267, 'INS-072', 'Repetidor WiFi para Oficina', 'Repetidor WiFi para Oficina de alta calidad para uso en telecomunicaciones.', 4, 7, 'Unidad', 202, 197.04, 52, 18, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2268, 'INS-073', 'Kit de Herramientas para Fibra Óptica', 'Kit de Herramientas para Fibra Óptica de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 207, 427.65, 119, 6, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2269, 'INS-074', 'Repetidor WiFi para Oficina', 'Repetidor WiFi para Oficina de alta calidad para uso en telecomunicaciones.', 4, 7, 'Unidad', 202, 227.46, 151, 9, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2270, 'INS-075', 'Cable de Fibra Óptica Multimodo', 'Cable de Fibra Óptica Multimodo de alta calidad para uso en telecomunicaciones.', 1, 7, 'Unidad', 203, 484.85, 118, 14, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2271, 'INS-076', 'Conector T', 'Conector T de alta calidad para uso en telecomunicaciones.', 2, 7, 'Unidad', 211, 418.71, 76, 7, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2272, 'INS-077', 'Conector BNC', 'Conector BNC de alta calidad para uso en telecomunicaciones.', 2, 7, 'Unidad', 211, 266.83, 22, 13, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2273, 'INS-078', 'Router Mesh', 'Router Mesh de alta calidad para uso en telecomunicaciones.', 3, 7, 'Unidad', 204, 404.36, 190, 18, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2274, 'INS-079', 'Kit de Herramientas para Fibra Óptica', 'Kit de Herramientas para Fibra Óptica de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 211, 89.32, 160, 10, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2275, 'INS-080', 'Cable Coaxial RG6', 'Cable Coaxial RG6 de alta calidad para uso en telecomunicaciones.', 1, 7, 'Unidad', 203, 153.98, 120, 17, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2276, 'INS-081', 'Repetidor WiFi con Enchufe', 'Repetidor WiFi con Enchufe de alta calidad para uso en telecomunicaciones.', 4, 7, 'Unidad', 202, 328.93, 187, 17, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2277, 'INS-082', 'Cable HDMI', 'Cable HDMI de alta calidad para uso en telecomunicaciones.', 1, 7, 'Unidad', 203, 325.36, 197, 19, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2278, 'INS-083', 'Kit de Herramientas para Fibra Óptica', 'Kit de Herramientas para Fibra Óptica de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 211, 451.58, 75, 10, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2279, 'INS-084', 'Pelacables', 'Pelacables de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 204, 192.16, 38, 17, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2280, 'INS-085', 'Alicate de Crimpado', 'Alicate de Crimpado de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 203, 499.07, 27, 17, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2281, 'INS-086', 'Kit de Limpieza para Conectores', 'Kit de Limpieza para Conectores de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 203, 144.51, 16, 14, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2282, 'INS-087', 'Router 4G LTE', 'Router 4G LTE de alta calidad para uso en telecomunicaciones.', 3, 7, 'Unidad', 210, 34.26, 142, 16, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2283, 'INS-088', 'Cable Coaxial RG59', 'Cable Coaxial RG59 de alta calidad para uso en telecomunicaciones.', 1, 7, 'Unidad', 204, 318.34, 33, 14, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2284, 'INS-089', 'Router para Hogar', 'Router para Hogar de alta calidad para uso en telecomunicaciones.', 3, 7, 'Unidad', 209, 274.27, 135, 11, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2285, 'INS-090', 'Repetidor WiFi 1200Mbps', 'Repetidor WiFi 1200Mbps de alta calidad para uso en telecomunicaciones.', 4, 7, 'Unidad', 203, 121.52, 189, 17, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2286, 'INS-091', 'Kit de Herramientas para Fibra Óptica', 'Kit de Herramientas para Fibra Óptica de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 208, 472.59, 48, 17, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2287, 'INS-092', 'Cable UTP Cat6', 'Cable UTP Cat6 de alta calidad para uso en telecomunicaciones.', 1, 7, 'Unidad', 203, 457.06, 99, 11, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2288, 'INS-093', 'Cable USB', 'Cable USB de alta calidad para uso en telecomunicaciones.', 1, 7, 'Unidad', 210, 183.59, 190, 8, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2289, 'INS-094', 'Conector N', 'Conector N de alta calidad para uso en telecomunicaciones.', 2, 7, 'Unidad', 203, 492.18, 67, 17, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2290, 'INS-095', 'Cable de Fibra Óptica Monomodo', 'Cable de Fibra Óptica Monomodo de alta calidad para uso en telecomunicaciones.', 1, 7, 'Unidad', 202, 309.19, 111, 6, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2291, 'INS-096', 'Multímetro Digital', 'Multímetro Digital de alta calidad para uso en telecomunicaciones.', 5, 7, 'Unidad', 208, 19.01, 63, 8, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2292, 'INS-097', 'Router Inalámbrico Dual Band', 'Router Inalámbrico Dual Band de alta calidad para uso en telecomunicaciones.', 3, 7, 'Unidad', 203, 90.10, 85, 19, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2293, 'INS-098', 'Cable UTP Cat6', 'Cable UTP Cat6 de alta calidad para uso en telecomunicaciones.', 1, 7, 'Unidad', 208, 195.01, 134, 9, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2294, 'INS-099', 'Repetidor WiFi Mesh', 'Repetidor WiFi Mesh de alta calidad para uso en telecomunicaciones.', 4, 7, 'Unidad', 207, 440.28, 126, 10, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2295, 'INS-100', 'Conector SMA', 'Conector SMA de alta calidad para uso en telecomunicaciones.', 2, 7, 'Unidad', 205, 112.87, 197, 16, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-17 22:11:14', '2025-11-17 22:11:14'),
(2329, 'INS-134', 'Multímetro Digital', 'Multímetro Digital de alta calidad para uso en telecomunicaciones, modelo 510.', 5, 1, 'Unidad', 204, 17.37, 70, 13, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-17 22:14:39', '2025-11-18 16:34:27'),
(2350, 'INS-155', 'Conector N', 'Conector N de alta calidad para uso en telecomunicaciones, modelo 889.', 2, 1, 'Unidad', 204, 486.72, 18, 12, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-17 22:14:40', '2025-11-18 02:38:13'),
(2402, 'INS-207', 'Alicate de Crimpado', 'Alicate de Crimpado de alta calidad para uso en telecomunicaciones, modelo 112.', 5, 1, 'Unidad', 204, 431.72, 84, 13, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-17 22:14:40', '2025-11-18 02:38:13'),
(2496, 'MAT-0151', 'RJ45', 'NUEVOS', 2, NULL, 'unidad', 192, 5.00, 12, 10, 20, '', NULL, NULL, 'activo', '2025-11-18 02:22:28', '2025-11-18 02:22:28'),
(2497, 'INS-101', 'Probador de Redes', 'Probador de Redes de alta calidad para uso en telecomunicaciones, modelo 868.', 5, 1, 'Unidad', 206, 395.58, 16, 13, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:12'),
(2498, 'INS-102', 'Cable de Fibra Óptica Multimodo', 'Cable de Fibra Óptica Multimodo de alta calidad para uso en telecomunicaciones, modelo 405.', 1, 1, 'Unidad', 203, 295.20, 21, 6, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:12'),
(2499, 'INS-103', 'Repetidor WiFi Dual Band', 'Repetidor WiFi Dual Band de alta calidad para uso en telecomunicaciones, modelo 449.', 4, 1, 'Unidad', 209, 163.33, 35, 6, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:12'),
(2500, 'INS-104', 'Kit de Herramientas para Fibra Óptica', 'Kit de Herramientas para Fibra Óptica de alta calidad para uso en telecomunicaciones, modelo 750.', 5, 1, 'Unidad', 204, 48.97, 196, 6, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:12'),
(2501, 'INS-105', 'Cable Coaxial RG6', 'Cable Coaxial RG6 de alta calidad para uso en telecomunicaciones, modelo 482.', 1, 1, 'Unidad', 208, 73.31, 118, 6, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:12'),
(2502, 'INS-106', 'Conector ST', 'Conector ST de alta calidad para uso en telecomunicaciones, modelo 456.', 2, 1, 'Unidad', 210, 284.66, 139, 9, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:12'),
(2503, 'INS-107', 'Repetidor WiFi 1200Mbps', 'Repetidor WiFi 1200Mbps de alta calidad para uso en telecomunicaciones, modelo 837.', 4, 1, 'Unidad', 210, 399.72, 53, 10, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:12'),
(2504, 'INS-108', 'Repetidor WiFi 1200Mbps', 'Repetidor WiFi 1200Mbps de alta calidad para uso en telecomunicaciones, modelo 309.', 4, 1, 'Unidad', 204, 229.34, 170, 20, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2505, 'INS-109', 'Cable UTP Cat5e', 'Cable UTP Cat5e de alta calidad para uso en telecomunicaciones, modelo 262.', 1, 1, 'Unidad', 206, 217.02, 183, 14, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2506, 'INS-110', 'Repetidor WiFi para Oficina', 'Repetidor WiFi para Oficina de alta calidad para uso en telecomunicaciones, modelo 689.', 4, 1, 'Unidad', 211, 172.10, 196, 19, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2507, 'INS-111', 'Cable Coaxial RG6', 'Cable Coaxial RG6 de alta calidad para uso en telecomunicaciones, modelo 178.', 1, 1, 'Unidad', 202, 266.64, 31, 18, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2508, 'INS-112', 'Repetidor WiFi Dual Band', 'Repetidor WiFi Dual Band de alta calidad para uso en telecomunicaciones, modelo 452.', 4, 1, 'Unidad', 209, 113.71, 60, 5, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2509, 'INS-113', 'Cable Coaxial RG59', 'Cable Coaxial RG59 de alta calidad para uso en telecomunicaciones, modelo 524.', 1, 1, 'Unidad', 205, 206.56, 147, 7, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2510, 'INS-114', 'Cable de Fibra Óptica Monomodo', 'Cable de Fibra Óptica Monomodo de alta calidad para uso en telecomunicaciones, modelo 149.', 1, 1, 'Unidad', 205, 378.99, 54, 8, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2511, 'INS-115', 'Conector SC', 'Conector SC de alta calidad para uso en telecomunicaciones, modelo 684.', 2, 1, 'Unidad', 210, 228.89, 82, 13, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2512, 'INS-116', 'Repetidor WiFi para Larga Distancia', 'Repetidor WiFi para Larga Distancia de alta calidad para uso en telecomunicaciones, modelo 656.', 4, 1, 'Unidad', 205, 392.83, 58, 6, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2513, 'INS-117', 'Repetidor WiFi para Oficina', 'Repetidor WiFi para Oficina de alta calidad para uso en telecomunicaciones, modelo 739.', 4, 1, 'Unidad', 210, 234.71, 68, 15, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2514, 'INS-118', 'Conector T', 'Conector T de alta calidad para uso en telecomunicaciones, modelo 359.', 2, 1, 'Unidad', 204, 159.56, 160, 20, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2515, 'INS-119', 'Router para Hogar', 'Router para Hogar de alta calidad para uso en telecomunicaciones, modelo 636.', 3, 1, 'Unidad', 203, 223.53, 22, 12, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2516, 'INS-120', 'Kit de Limpieza para Conectores', 'Kit de Limpieza para Conectores de alta calidad para uso en telecomunicaciones, modelo 126.', 5, 1, 'Unidad', 211, 251.33, 41, 15, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2517, 'INS-121', 'Conector SMA', 'Conector SMA de alta calidad para uso en telecomunicaciones, modelo 153.', 2, 1, 'Unidad', 209, 461.14, 30, 16, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2518, 'INS-122', 'Cable de Fibra Óptica Multimodo', 'Cable de Fibra Óptica Multimodo de alta calidad para uso en telecomunicaciones, modelo 295.', 1, 1, 'Unidad', 204, 178.39, 48, 12, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2519, 'INS-123', 'Router Mesh', 'Router Mesh de alta calidad para uso en telecomunicaciones, modelo 415.', 3, 1, 'Unidad', 209, 436.97, 17, 13, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2520, 'INS-124', 'Repetidor WiFi Mesh', 'Repetidor WiFi Mesh de alta calidad para uso en telecomunicaciones, modelo 132.', 4, 1, 'Unidad', 210, 436.07, 200, 12, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2521, 'INS-125', 'Kit de Limpieza para Conectores', 'Kit de Limpieza para Conectores de alta calidad para uso en telecomunicaciones, modelo 676.', 5, 1, 'Unidad', 202, 177.33, 63, 9, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2522, 'INS-126', 'Router Inalámbrico Dual Band', 'Router Inalámbrico Dual Band de alta calidad para uso en telecomunicaciones, modelo 139.', 3, 1, 'Unidad', 210, 275.46, 193, 18, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2523, 'INS-127', 'Kit de Herramientas para Fibra Óptica', 'Kit de Herramientas para Fibra Óptica de alta calidad para uso en telecomunicaciones, modelo 675.', 5, 1, 'Unidad', 206, 242.30, 69, 7, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2524, 'INS-128', 'Repetidor WiFi para Larga Distancia', 'Repetidor WiFi para Larga Distancia de alta calidad para uso en telecomunicaciones, modelo 489.', 4, 1, 'Unidad', 203, 43.38, 21, 19, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2525, 'INS-129', 'Repetidor WiFi Dual Band', 'Repetidor WiFi Dual Band de alta calidad para uso en telecomunicaciones, modelo 516.', 4, 1, 'Unidad', 203, 42.04, 60, 19, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2526, 'INS-130', 'Conector F', 'Conector F de alta calidad para uso en telecomunicaciones, modelo 607.', 2, 1, 'Unidad', 207, 432.73, 66, 14, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2527, 'INS-131', 'Router 4G LTE', 'Router 4G LTE de alta calidad para uso en telecomunicaciones, modelo 115.', 3, 1, 'Unidad', 205, 375.08, 41, 7, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2528, 'INS-132', 'Router Mesh', 'Router Mesh de alta calidad para uso en telecomunicaciones, modelo 273.', 3, 1, 'Unidad', 205, 398.96, 138, 20, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2529, 'INS-133', 'Repetidor WiFi Mesh', 'Repetidor WiFi Mesh de alta calidad para uso en telecomunicaciones, modelo 144.', 4, 1, 'Unidad', 207, 138.42, 179, 11, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2530, 'INS-135', 'Conector SMA', 'Conector SMA de alta calidad para uso en telecomunicaciones, modelo 560.', 2, 1, 'Unidad', 210, 237.40, 124, 14, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2531, 'INS-136', 'Router Inalámbrico Dual Band', 'Router Inalámbrico Dual Band de alta calidad para uso en telecomunicaciones, modelo 400.', 3, 1, 'Unidad', 210, 378.10, 125, 17, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2532, 'INS-137', 'Router para Hogar', 'Router para Hogar de alta calidad para uso en telecomunicaciones, modelo 131.', 3, 1, 'Unidad', 209, 268.39, 31, 10, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2533, 'INS-138', 'Router Inalámbrico Dual Band', 'Router Inalámbrico Dual Band de alta calidad para uso en telecomunicaciones, modelo 790.', 3, 1, 'Unidad', 203, 281.27, 39, 18, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2534, 'INS-139', 'Pelacables', 'Pelacables de alta calidad para uso en telecomunicaciones, modelo 975.', 5, 1, 'Unidad', 207, 139.28, 150, 14, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2535, 'INS-140', 'Cable Coaxial RG6', 'Cable Coaxial RG6 de alta calidad para uso en telecomunicaciones, modelo 533.', 1, 1, 'Unidad', 208, 342.28, 189, 12, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2536, 'INS-141', 'Repetidor WiFi 1200Mbps', 'Repetidor WiFi 1200Mbps de alta calidad para uso en telecomunicaciones, modelo 498.', 4, 1, 'Unidad', 203, 342.54, 34, 5, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2537, 'INS-142', 'Repetidor WiFi para Oficina', 'Repetidor WiFi para Oficina de alta calidad para uso en telecomunicaciones, modelo 560.', 4, 1, 'Unidad', 202, 250.94, 184, 7, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2538, 'INS-143', 'Multímetro Digital', 'Multímetro Digital de alta calidad para uso en telecomunicaciones, modelo 944.', 5, 1, 'Unidad', 208, 384.80, 181, 13, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2539, 'INS-144', 'Router con Firewall', 'Router con Firewall de alta calidad para uso en telecomunicaciones, modelo 105.', 3, 1, 'Unidad', 210, 56.35, 45, 12, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2540, 'INS-145', 'Conector N', 'Conector N de alta calidad para uso en telecomunicaciones, modelo 134.', 2, 1, 'Unidad', 208, 162.88, 30, 9, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2541, 'INS-146', 'Conector F', 'Conector F de alta calidad para uso en telecomunicaciones, modelo 987.', 2, 1, 'Unidad', 211, 53.72, 81, 11, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2542, 'INS-147', 'Conector BNC', 'Conector BNC de alta calidad para uso en telecomunicaciones, modelo 962.', 2, 1, 'Unidad', 204, 423.81, 84, 10, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2543, 'INS-148', 'Conector SC', 'Conector SC de alta calidad para uso en telecomunicaciones, modelo 109.', 2, 1, 'Unidad', 207, 317.45, 109, 12, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2544, 'INS-149', 'Conector F', 'Conector F de alta calidad para uso en telecomunicaciones, modelo 578.', 2, 1, 'Unidad', 202, 424.65, 189, 15, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2545, 'INS-150', 'Repetidor WiFi con Enchufe', 'Repetidor WiFi con Enchufe de alta calidad para uso en telecomunicaciones, modelo 950.', 4, 1, 'Unidad', 205, 250.55, 52, 10, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2546, 'INS-151', 'Repetidor WiFi con Enchufe', 'Repetidor WiFi con Enchufe de alta calidad para uso en telecomunicaciones, modelo 705.', 4, 1, 'Unidad', 205, 460.16, 69, 14, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2547, 'INS-152', 'Router para Empresas', 'Router para Empresas de alta calidad para uso en telecomunicaciones, modelo 977.', 3, 1, 'Unidad', 203, 282.87, 34, 9, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2548, 'INS-153', 'Repetidor WiFi 1200Mbps', 'Repetidor WiFi 1200Mbps de alta calidad para uso en telecomunicaciones, modelo 823.', 4, 1, 'Unidad', 209, 386.54, 23, 8, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2549, 'INS-154', 'Cable Coaxial RG59', 'Cable Coaxial RG59 de alta calidad para uso en telecomunicaciones, modelo 624.', 1, 1, 'Unidad', 211, 21.99, 108, 6, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 13:41:46'),
(2550, 'INS-156', 'Conector ST', 'Conector ST de alta calidad para uso en telecomunicaciones, modelo 136.', 2, 1, 'Unidad', 207, 433.70, 67, 14, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2551, 'INS-157', 'Kit de Herramientas para Redes', 'Kit de Herramientas para Redes de alta calidad para uso en telecomunicaciones, modelo 418.', 5, 1, 'Unidad', 210, 499.70, 156, 19, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2552, 'INS-158', 'Repetidor WiFi Dual Band', 'Repetidor WiFi Dual Band de alta calidad para uso en telecomunicaciones, modelo 110.', 4, 1, 'Unidad', 206, 88.90, 158, 6, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2553, 'INS-159', 'Repetidor WiFi con Enchufe', 'Repetidor WiFi con Enchufe de alta calidad para uso en telecomunicaciones, modelo 196.', 4, 1, 'Unidad', 207, 213.16, 27, 14, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2554, 'INS-160', 'Router Portátil', 'Router Portátil de alta calidad para uso en telecomunicaciones, modelo 434.', 3, 1, 'Unidad', 208, 292.34, 74, 18, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 17:45:57'),
(2555, 'INS-161', 'Cortador de Cables', 'Cortador de Cables de alta calidad para uso en telecomunicaciones, modelo 171.', 5, 1, 'Unidad', 205, 395.51, 59, 18, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2556, 'INS-162', 'Multímetro Digital', 'Multímetro Digital de alta calidad para uso en telecomunicaciones, modelo 183.', 5, 1, 'Unidad', 203, 42.69, 156, 14, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2557, 'INS-163', 'Cortador de Cables', 'Cortador de Cables de alta calidad para uso en telecomunicaciones, modelo 396.', 5, 1, 'Unidad', 205, 499.31, 163, 8, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2558, 'INS-164', 'Cable de Par Trenzado', 'Cable de Par Trenzado de alta calidad para uso en telecomunicaciones, modelo 600.', 1, 1, 'Unidad', 205, 475.93, 86, 12, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2559, 'INS-165', 'Router con Firewall', 'Router con Firewall de alta calidad para uso en telecomunicaciones, modelo 222.', 3, 1, 'Unidad', 206, 480.47, 31, 8, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2560, 'INS-166', 'Kit de Herramientas para Fibra Óptica', 'Kit de Herramientas para Fibra Óptica de alta calidad para uso en telecomunicaciones, modelo 393.', 5, 1, 'Unidad', 209, 488.61, 178, 20, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2561, 'INS-167', 'Multímetro Digital', 'Multímetro Digital de alta calidad para uso en telecomunicaciones, modelo 949.', 5, 1, 'Unidad', 209, 71.91, 133, 8, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2562, 'INS-168', 'Conector F', 'Conector F de alta calidad para uso en telecomunicaciones, modelo 145.', 2, 1, 'Unidad', 209, 418.33, 103, 8, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2563, 'INS-169', 'Pelacables', 'Pelacables de alta calidad para uso en telecomunicaciones, modelo 875.', 5, 1, 'Unidad', 207, 248.22, 108, 11, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2564, 'INS-170', 'Probador de Redes', 'Probador de Redes de alta calidad para uso en telecomunicaciones, modelo 961.', 5, 1, 'Unidad', 205, 246.52, 61, 8, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2565, 'INS-171', 'Cable de Fibra Óptica Multimodo', 'Cable de Fibra Óptica Multimodo de alta calidad para uso en telecomunicaciones, modelo 676.', 1, 1, 'Unidad', 208, 465.80, 139, 7, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2566, 'INS-172', 'Router Portátil', 'Router Portátil de alta calidad para uso en telecomunicaciones, modelo 801.', 3, 1, 'Unidad', 205, 433.98, 106, 16, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2567, 'INS-173', 'Conector N', 'Conector N de alta calidad para uso en telecomunicaciones, modelo 172.', 2, 1, 'Unidad', 208, 14.10, 117, 8, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2568, 'INS-174', 'Conector LC', 'Conector LC de alta calidad para uso en telecomunicaciones, modelo 796.', 2, 1, 'Unidad', 209, 178.52, 192, 20, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2569, 'INS-175', 'Conector FC', 'Conector FC de alta calidad para uso en telecomunicaciones, modelo 486.', 2, 1, 'Unidad', 203, 68.58, 88, 14, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2570, 'INS-176', 'Conector SC', 'Conector SC de alta calidad para uso en telecomunicaciones, modelo 276.', 2, 1, 'Unidad', 208, 153.03, 156, 7, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2571, 'INS-177', 'Router 4G LTE', 'Router 4G LTE de alta calidad para uso en telecomunicaciones, modelo 718.', 3, 1, 'Unidad', 208, 301.55, 135, 13, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2572, 'INS-178', 'Conector FC', 'Conector FC de alta calidad para uso en telecomunicaciones, modelo 298.', 2, 1, 'Unidad', 211, 197.66, 141, 6, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2573, 'INS-179', 'Router Mesh', 'Router Mesh de alta calidad para uso en telecomunicaciones, modelo 222.', 3, 1, 'Unidad', 208, 170.17, 78, 13, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2574, 'INS-180', 'Router 4G LTE', 'Router 4G LTE de alta calidad para uso en telecomunicaciones, modelo 367.', 3, 1, 'Unidad', 209, 402.79, 167, 18, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2575, 'INS-181', 'Router VPN', 'Router VPN de alta calidad para uso en telecomunicaciones, modelo 508.', 3, 1, 'Unidad', 207, 112.81, 80, 16, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2576, 'INS-182', 'Repetidor WiFi 300Mbps', 'Repetidor WiFi 300Mbps de alta calidad para uso en telecomunicaciones, modelo 717.', 4, 1, 'Unidad', 206, 481.42, 182, 17, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2577, 'INS-183', 'Cable de Fibra Óptica Monomodo', 'Cable de Fibra Óptica Monomodo de alta calidad para uso en telecomunicaciones, modelo 706.', 1, 1, 'Unidad', 210, 420.57, 13, 18, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2578, 'INS-184', 'Cable de Fibra Óptica Monomodo', 'Cable de Fibra Óptica Monomodo de alta calidad para uso en telecomunicaciones, modelo 368.', 1, 1, 'Unidad', 211, 28.20, 94, 6, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2579, 'INS-185', 'Conector SC', 'Conector SC de alta calidad para uso en telecomunicaciones, modelo 134.', 2, 1, 'Unidad', 203, 328.21, 137, 8, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2580, 'INS-186', 'Conector F', 'Conector F de alta calidad para uso en telecomunicaciones, modelo 796.', 2, 1, 'Unidad', 206, 109.99, 48, 13, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2581, 'INS-187', 'Router Industrial', 'Router Industrial de alta calidad para uso en telecomunicaciones, modelo 935.', 3, 1, 'Unidad', 206, 71.37, 17, 14, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2582, 'INS-188', 'Conector SC', 'Conector SC de alta calidad para uso en telecomunicaciones, modelo 988.', 2, 1, 'Unidad', 206, 486.58, 118, 14, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2583, 'INS-189', 'Router Industrial', 'Router Industrial de alta calidad para uso en telecomunicaciones, modelo 628.', 3, 1, 'Unidad', 203, 368.19, 130, 18, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2584, 'INS-190', 'Conector ST', 'Conector ST de alta calidad para uso en telecomunicaciones, modelo 719.', 2, 1, 'Unidad', 211, 408.87, 72, 15, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2585, 'INS-191', 'Cable de Red Externo', 'Cable de Red Externo de alta calidad para uso en telecomunicaciones, modelo 855.', 1, 1, 'Unidad', 207, 325.93, 104, 5, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2586, 'INS-192', 'Kit de Herramientas para Redes', 'Kit de Herramientas para Redes de alta calidad para uso en telecomunicaciones, modelo 881.', 5, 1, 'Unidad', 208, 469.39, 143, 14, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2587, 'INS-193', 'Repetidor WiFi con Enchufe', 'Repetidor WiFi con Enchufe de alta calidad para uso en telecomunicaciones, modelo 485.', 4, 1, 'Unidad', 205, 457.06, 55, 5, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2588, 'INS-194', 'Router Mesh', 'Router Mesh de alta calidad para uso en telecomunicaciones, modelo 509.', 3, 1, 'Unidad', 207, 111.17, 90, 17, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2589, 'INS-195', 'Router Gigabit', 'Router Gigabit de alta calidad para uso en telecomunicaciones, modelo 639.', 3, 1, 'Unidad', 202, 199.58, 186, 14, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2590, 'INS-196', 'Router Gigabit', 'Router Gigabit de alta calidad para uso en telecomunicaciones, modelo 896.', 3, 1, 'Unidad', 211, 306.04, 54, 9, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2591, 'INS-197', 'Multímetro Digital', 'Multímetro Digital de alta calidad para uso en telecomunicaciones, modelo 766.', 5, 1, 'Unidad', 205, 119.76, 96, 13, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2592, 'INS-198', 'Conector FC', 'Conector FC de alta calidad para uso en telecomunicaciones, modelo 201.', 2, 1, 'Unidad', 208, 476.99, 141, 16, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2593, 'INS-199', 'Repetidor WiFi 300Mbps', 'Repetidor WiFi 300Mbps de alta calidad para uso en telecomunicaciones, modelo 107.', 4, 1, 'Unidad', 211, 380.76, 64, 7, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2594, 'INS-200', 'Conector T', 'Conector T de alta calidad para uso en telecomunicaciones, modelo 203.', 2, 1, 'Unidad', 202, 214.24, 122, 20, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2595, 'INS-201', 'Conector F', 'Conector F de alta calidad para uso en telecomunicaciones, modelo 233.', 2, 1, 'Unidad', 207, 291.53, 196, 11, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2596, 'INS-202', 'Pelacables', 'Pelacables de alta calidad para uso en telecomunicaciones, modelo 849.', 5, 1, 'Unidad', 208, 50.46, 17, 10, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13');
INSERT INTO `materiales` (`id`, `codigo`, `nombre`, `descripcion`, `categoria_id`, `sede_id`, `unidad`, `proveedor_id`, `costo_unitario`, `stock_actual`, `stock_minimo`, `stock_maximo`, `ubicacion`, `fecha_vencimiento`, `lote`, `estado`, `created_at`, `updated_at`) VALUES
(2597, 'INS-203', 'Cable de Par Trenzado', 'Cable de Par Trenzado de alta calidad para uso en telecomunicaciones, modelo 816.', 1, 1, 'Unidad', 203, 180.95, 88, 17, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2598, 'INS-204', 'Router Portátil', 'Router Portátil de alta calidad para uso en telecomunicaciones, modelo 216.', 3, 1, 'Unidad', 205, 354.72, 61, 19, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2599, 'INS-205', 'Repetidor WiFi Portátil', 'Repetidor WiFi Portátil de alta calidad para uso en telecomunicaciones, modelo 173.', 4, 1, 'Unidad', 209, 439.15, 103, 19, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2600, 'INS-206', 'Cable de Fibra Óptica Multimodo', 'Cable de Fibra Óptica Multimodo de alta calidad para uso en telecomunicaciones, modelo 796.', 1, 1, 'Unidad', 202, 261.86, 12, 11, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2601, 'INS-208', 'Router para Empresas', 'Router para Empresas de alta calidad para uso en telecomunicaciones, modelo 325.', 3, 1, 'Unidad', 209, 183.31, 70, 18, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2602, 'INS-209', 'Repetidor WiFi 1200Mbps', 'Repetidor WiFi 1200Mbps de alta calidad para uso en telecomunicaciones, modelo 611.', 4, 1, 'Unidad', 206, 80.89, 103, 18, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2603, 'INS-210', 'Cable de Red Externo', 'Cable de Red Externo de alta calidad para uso en telecomunicaciones, modelo 111.', 1, 1, 'Unidad', 206, 234.55, 108, 7, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2604, 'INS-211', 'Repetidor WiFi 300Mbps', 'Repetidor WiFi 300Mbps de alta calidad para uso en telecomunicaciones, modelo 246.', 4, 1, 'Unidad', 202, 453.58, 127, 8, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2605, 'INS-212', 'Repetidor WiFi Dual Band', 'Repetidor WiFi Dual Band de alta calidad para uso en telecomunicaciones, modelo 520.', 4, 1, 'Unidad', 206, 256.75, 23, 17, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2606, 'INS-213', 'Cable de Red Externo', 'Cable de Red Externo de alta calidad para uso en telecomunicaciones, modelo 373.', 1, 1, 'Unidad', 202, 149.44, 27, 5, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2607, 'INS-214', 'Router Gigabit', 'Router Gigabit de alta calidad para uso en telecomunicaciones, modelo 376.', 3, 1, 'Unidad', 208, 235.80, 99, 7, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2608, 'INS-215', 'Cable de Red Externo', 'Cable de Red Externo de alta calidad para uso en telecomunicaciones, modelo 977.', 1, 1, 'Unidad', 205, 347.39, 102, 13, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2609, 'INS-216', 'Router Inalámbrico Dual Band', 'Router Inalámbrico Dual Band de alta calidad para uso en telecomunicaciones, modelo 882.', 3, 1, 'Unidad', 211, 297.87, 146, 13, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2610, 'INS-217', 'Cortador de Cables', 'Cortador de Cables de alta calidad para uso en telecomunicaciones, modelo 398.', 5, 1, 'Unidad', 206, 414.46, 64, 10, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2611, 'INS-218', 'Router Gigabit', 'Router Gigabit de alta calidad para uso en telecomunicaciones, modelo 984.', 3, 1, 'Unidad', 205, 93.16, 197, 7, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2612, 'INS-219', 'Router Gigabit', 'Router Gigabit de alta calidad para uso en telecomunicaciones, modelo 145.', 3, 1, 'Unidad', 205, 204.39, 159, 5, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2613, 'INS-220', 'Conector SMA', 'Conector SMA de alta calidad para uso en telecomunicaciones, modelo 471.', 2, 1, 'Unidad', 206, 312.17, 180, 11, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2614, 'INS-221', 'Alicate de Crimpado', 'Alicate de Crimpado de alta calidad para uso en telecomunicaciones, modelo 813.', 5, 1, 'Unidad', 208, 71.07, 87, 6, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 22:54:48'),
(2615, 'INS-222', 'Cable de Fibra Óptica Multimodo', 'Cable de Fibra Óptica Multimodo de alta calidad para uso en telecomunicaciones, modelo 996.', 1, 1, 'Unidad', 210, 365.49, 51, 20, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2616, 'INS-223', 'Cable de Fibra Óptica Monomodo', 'Cable de Fibra Óptica Monomodo de alta calidad para uso en telecomunicaciones, modelo 650.', 1, 1, 'Unidad', 202, 120.80, 97, 8, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2617, 'INS-224', 'Conector FC', 'Conector FC de alta calidad para uso en telecomunicaciones, modelo 659.', 2, 1, 'Unidad', 211, 279.94, 27, 6, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2618, 'INS-225', 'Conector F', 'Conector F de alta calidad para uso en telecomunicaciones, modelo 714.', 2, 1, 'Unidad', 204, 45.86, 20, 13, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2619, 'INS-226', 'Router Industrial', 'Router Industrial de alta calidad para uso en telecomunicaciones, modelo 220.', 3, 1, 'Unidad', 202, 290.61, 11, 8, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2620, 'INS-227', 'Router Inalámbrico Dual Band', 'Router Inalámbrico Dual Band de alta calidad para uso en telecomunicaciones, modelo 277.', 3, 1, 'Unidad', 210, 360.22, 91, 19, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2621, 'INS-228', 'Repetidor WiFi para Larga Distancia', 'Repetidor WiFi para Larga Distancia de alta calidad para uso en telecomunicaciones, modelo 473.', 4, 1, 'Unidad', 211, 333.71, 185, 7, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2622, 'INS-229', 'Alicate de Crimpado', 'Alicate de Crimpado de alta calidad para uso en telecomunicaciones, modelo 707.', 5, 1, 'Unidad', 209, 132.12, 62, 12, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2623, 'INS-230', 'Repetidor WiFi con Enchufe', 'Repetidor WiFi con Enchufe de alta calidad para uso en telecomunicaciones, modelo 660.', 4, 1, 'Unidad', 210, 100.87, 145, 5, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2624, 'INS-231', 'Kit de Herramientas para Fibra Óptica', 'Kit de Herramientas para Fibra Óptica de alta calidad para uso en telecomunicaciones, modelo 739.', 5, 1, 'Unidad', 208, 213.32, 23, 12, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2625, 'INS-232', 'Cable UTP Cat5e', 'Cable UTP Cat5e de alta calidad para uso en telecomunicaciones, modelo 244.', 1, 1, 'Unidad', 205, 457.66, 50, 9, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2626, 'INS-233', 'Router Portátil', 'Router Portátil de alta calidad para uso en telecomunicaciones, modelo 934.', 3, 1, 'Unidad', 210, 108.79, 146, 14, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2627, 'INS-234', 'Repetidor WiFi con Enchufe', 'Repetidor WiFi con Enchufe de alta calidad para uso en telecomunicaciones, modelo 306.', 4, 1, 'Unidad', 209, 155.47, 86, 18, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2628, 'INS-235', 'Conector SC', 'Conector SC de alta calidad para uso en telecomunicaciones, modelo 483.', 2, 1, 'Unidad', 207, 382.40, 57, 12, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2629, 'INS-236', 'Conector LC', 'Conector LC de alta calidad para uso en telecomunicaciones, modelo 299.', 2, 1, 'Unidad', 208, 310.15, 182, 14, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2630, 'INS-237', 'Repetidor WiFi para Oficina', 'Repetidor WiFi para Oficina de alta calidad para uso en telecomunicaciones, modelo 192.', 4, 1, 'Unidad', 208, 235.82, 93, 14, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2631, 'INS-238', 'Crimpadora', 'Crimpadora de alta calidad para uso en telecomunicaciones, modelo 231.', 5, 1, 'Unidad', 202, 330.53, 146, 15, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2632, 'INS-239', 'Cable de Fibra Óptica Multimodo', 'Cable de Fibra Óptica Multimodo de alta calidad para uso en telecomunicaciones, modelo 342.', 1, 1, 'Unidad', 205, 71.63, 84, 7, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 22:40:49'),
(2633, 'INS-240', 'Repetidor WiFi Dual Band', 'Repetidor WiFi Dual Band de alta calidad para uso en telecomunicaciones, modelo 524.', 4, 1, 'Unidad', 208, 332.37, 125, 18, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2634, 'INS-241', 'Conector SMA', 'Conector SMA de alta calidad para uso en telecomunicaciones, modelo 780.', 2, 1, 'Unidad', 203, 133.21, 180, 7, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2635, 'INS-242', 'Repetidor WiFi Portátil', 'Repetidor WiFi Portátil de alta calidad para uso en telecomunicaciones, modelo 265.', 4, 1, 'Unidad', 209, 451.34, 62, 14, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2636, 'INS-243', 'Repetidor WiFi Portátil', 'Repetidor WiFi Portátil de alta calidad para uso en telecomunicaciones, modelo 586.', 4, 1, 'Unidad', 202, 97.83, 98, 9, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2637, 'INS-244', 'Repetidor WiFi Dual Band', 'Repetidor WiFi Dual Band de alta calidad para uso en telecomunicaciones, modelo 723.', 4, 1, 'Unidad', 207, 126.31, 11, 16, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2638, 'INS-245', 'Cable Coaxial RG59', 'Cable Coaxial RG59 de alta calidad para uso en telecomunicaciones, modelo 614.', 1, 1, 'Unidad', 205, 224.57, 152, 19, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2639, 'INS-246', 'Cable de Red Externo', 'Cable de Red Externo de alta calidad para uso en telecomunicaciones, modelo 887.', 1, 1, 'Unidad', 206, 198.69, 116, 18, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2640, 'INS-247', 'Cortador de Cables', 'Cortador de Cables de alta calidad para uso en telecomunicaciones, modelo 365.', 5, 1, 'Unidad', 203, 110.55, 194, 14, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2641, 'INS-248', 'Router con Firewall', 'Router con Firewall de alta calidad para uso en telecomunicaciones, modelo 881.', 3, 1, 'Unidad', 208, 214.13, 168, 13, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2642, 'INS-249', 'Cable UTP Cat5e', 'Cable UTP Cat5e de alta calidad para uso en telecomunicaciones, modelo 640.', 1, 1, 'Unidad', 205, 392.70, 19, 12, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2643, 'INS-250', 'Repetidor WiFi Mesh', 'Repetidor WiFi Mesh de alta calidad para uso en telecomunicaciones, modelo 268.', 4, 1, 'Unidad', 208, 391.59, 91, 8, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2644, 'INS-251', 'Conector SC', 'Conector SC de alta calidad para uso en telecomunicaciones, modelo 956.', 2, 1, 'Unidad', 204, 351.24, 47, 8, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2645, 'INS-252', 'Router con Firewall', 'Router con Firewall de alta calidad para uso en telecomunicaciones, modelo 226.', 3, 1, 'Unidad', 206, 53.80, 190, 13, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2646, 'INS-253', 'Conector FC', 'Conector FC de alta calidad para uso en telecomunicaciones, modelo 865.', 2, 1, 'Unidad', 209, 144.98, 91, 12, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2647, 'INS-254', 'Cable HDMI', 'Cable HDMI de alta calidad para uso en telecomunicaciones, modelo 383.', 1, 1, 'Unidad', 206, 226.49, 27, 10, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2648, 'INS-255', 'Repetidor WiFi para Oficina', 'Repetidor WiFi para Oficina de alta calidad para uso en telecomunicaciones, modelo 518.', 4, 1, 'Unidad', 202, 338.91, 40, 17, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2649, 'INS-256', 'Router VPN', 'Router VPN de alta calidad para uso en telecomunicaciones, modelo 472.', 3, 1, 'Unidad', 210, 351.95, 145, 15, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2650, 'INS-257', 'Router 4G LTE', 'Router 4G LTE de alta calidad para uso en telecomunicaciones, modelo 402.', 3, 1, 'Unidad', 205, 296.22, 43, 6, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2651, 'INS-258', 'Cable Coaxial RG6', 'Cable Coaxial RG6 de alta calidad para uso en telecomunicaciones, modelo 685.', 1, 1, 'Unidad', 211, 297.98, 155, 13, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2652, 'INS-259', 'Router para Hogar', 'Router para Hogar de alta calidad para uso en telecomunicaciones, modelo 731.', 3, 1, 'Unidad', 208, 382.75, 141, 18, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2653, 'INS-260', 'Conector N', 'Conector N de alta calidad para uso en telecomunicaciones, modelo 569.', 2, 1, 'Unidad', 207, 278.91, 108, 6, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2654, 'INS-261', 'Router Gigabit', 'Router Gigabit de alta calidad para uso en telecomunicaciones, modelo 142.', 3, 1, 'Unidad', 208, 242.93, 143, 18, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2655, 'INS-262', 'Conector RJ45', 'Conector RJ45 de alta calidad para uso en telecomunicaciones, modelo 674.', 2, 1, 'Unidad', 206, 311.87, 184, 7, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2656, 'INS-263', 'Conector BNC', 'Conector BNC de alta calidad para uso en telecomunicaciones, modelo 844.', 2, 1, 'Unidad', 204, 242.01, 93, 10, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2657, 'INS-264', 'Conector ST', 'Conector ST de alta calidad para uso en telecomunicaciones, modelo 193.', 2, 1, 'Unidad', 204, 61.12, 178, 18, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2658, 'INS-265', 'Router para Hogar', 'Router para Hogar de alta calidad para uso en telecomunicaciones, modelo 903.', 3, 1, 'Unidad', 208, 207.35, 113, 17, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2659, 'INS-266', 'Cortador de Cables', 'Cortador de Cables de alta calidad para uso en telecomunicaciones, modelo 295.', 5, 1, 'Unidad', 206, 440.84, 167, 12, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2660, 'INS-267', 'Router para Hogar', 'Router para Hogar de alta calidad para uso en telecomunicaciones, modelo 415.', 3, 1, 'Unidad', 203, 144.28, 160, 14, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2661, 'INS-268', 'Conector T', 'Conector T de alta calidad para uso en telecomunicaciones, modelo 224.', 2, 1, 'Unidad', 210, 27.58, 180, 17, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2662, 'INS-269', 'Multímetro Digital', 'Multímetro Digital de alta calidad para uso en telecomunicaciones, modelo 811.', 5, 1, 'Unidad', 207, 430.50, 195, 6, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2663, 'INS-270', 'Cable Coaxial RG6', 'Cable Coaxial RG6 de alta calidad para uso en telecomunicaciones, modelo 447.', 1, 1, 'Unidad', 209, 308.61, 144, 17, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2664, 'INS-271', 'Conector T', 'Conector T de alta calidad para uso en telecomunicaciones, modelo 653.', 2, 1, 'Unidad', 204, 375.40, 188, 13, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2665, 'INS-272', 'Conector N', 'Conector N de alta calidad para uso en telecomunicaciones, modelo 841.', 2, 1, 'Unidad', 202, 221.55, 101, 6, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2666, 'INS-273', 'Multímetro Digital', 'Multímetro Digital de alta calidad para uso en telecomunicaciones, modelo 818.', 5, 1, 'Unidad', 204, 321.32, 78, 20, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2667, 'INS-274', 'Pelacables', 'Pelacables de alta calidad para uso en telecomunicaciones, modelo 623.', 5, 1, 'Unidad', 208, 21.49, 87, 13, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2668, 'INS-275', 'Conector SMA', 'Conector SMA de alta calidad para uso en telecomunicaciones, modelo 887.', 2, 1, 'Unidad', 208, 176.45, 87, 11, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2669, 'INS-276', 'Repetidor WiFi Portátil', 'Repetidor WiFi Portátil de alta calidad para uso en telecomunicaciones, modelo 302.', 4, 1, 'Unidad', 204, 237.19, 150, 6, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2670, 'INS-277', 'Repetidor WiFi Portátil', 'Repetidor WiFi Portátil de alta calidad para uso en telecomunicaciones, modelo 773.', 4, 1, 'Unidad', 206, 90.98, 99, 20, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2671, 'INS-278', 'Conector SMA', 'Conector SMA de alta calidad para uso en telecomunicaciones, modelo 261.', 2, 1, 'Unidad', 203, 29.05, 75, 17, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2672, 'INS-279', 'Conector BNC', 'Conector BNC de alta calidad para uso en telecomunicaciones, modelo 339.', 2, 1, 'Unidad', 211, 354.99, 146, 17, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2673, 'INS-280', 'Kit de Limpieza para Conectores', 'Kit de Limpieza para Conectores de alta calidad para uso en telecomunicaciones, modelo 249.', 5, 1, 'Unidad', 205, 462.11, 195, 11, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2674, 'INS-281', 'Conector ST', 'Conector ST de alta calidad para uso en telecomunicaciones, modelo 696.', 2, 1, 'Unidad', 211, 101.16, 55, 11, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2675, 'INS-282', 'Conector SC', 'Conector SC de alta calidad para uso en telecomunicaciones, modelo 134.', 2, 1, 'Unidad', 210, 409.61, 24, 17, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2676, 'INS-283', 'Probador de Redes', 'Probador de Redes de alta calidad para uso en telecomunicaciones, modelo 814.', 5, 1, 'Unidad', 205, 198.14, 19, 20, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2677, 'INS-284', 'Cable USB', 'Cable USB de alta calidad para uso en telecomunicaciones, modelo 261.', 1, 1, 'Unidad', 208, 292.86, 34, 20, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2678, 'INS-285', 'Conector ST', 'Conector ST de alta calidad para uso en telecomunicaciones, modelo 219.', 2, 1, 'Unidad', 207, 114.64, 173, 11, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2679, 'INS-286', 'Router Industrial', 'Router Industrial de alta calidad para uso en telecomunicaciones, modelo 783.', 3, 1, 'Unidad', 211, 365.13, 193, 6, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2680, 'INS-287', 'Probador de Redes', 'Probador de Redes de alta calidad para uso en telecomunicaciones, modelo 182.', 5, 1, 'Unidad', 204, 404.67, 106, 11, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2681, 'INS-288', 'Router VPN', 'Router VPN de alta calidad para uso en telecomunicaciones, modelo 150.', 3, 1, 'Unidad', 202, 29.95, 169, 12, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2682, 'INS-289', 'Conector FC', 'Conector FC de alta calidad para uso en telecomunicaciones, modelo 200.', 2, 1, 'Unidad', 207, 382.68, 38, 5, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2683, 'INS-290', 'Conector RJ45', 'Conector RJ45 de alta calidad para uso en telecomunicaciones, modelo 496.', 2, 1, 'Unidad', 204, 237.31, 149, 12, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2684, 'INS-291', 'Router Gigabit', 'Router Gigabit de alta calidad para uso en telecomunicaciones, modelo 293.', 3, 1, 'Unidad', 208, 140.02, 128, 7, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2685, 'INS-292', 'Repetidor WiFi Mesh', 'Repetidor WiFi Mesh de alta calidad para uso en telecomunicaciones, modelo 691.', 4, 1, 'Unidad', 209, 243.11, 121, 5, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2686, 'INS-293', 'Cable de Par Trenzado', 'Cable de Par Trenzado de alta calidad para uso en telecomunicaciones, modelo 635.', 1, 1, 'Unidad', 202, 187.73, 153, 9, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2687, 'INS-294', 'Router Portátil', 'Router Portátil de alta calidad para uso en telecomunicaciones, modelo 504.', 3, 1, 'Unidad', 209, 117.74, 11, 13, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2688, 'INS-295', 'Repetidor WiFi para Larga Distancia', 'Repetidor WiFi para Larga Distancia de alta calidad para uso en telecomunicaciones, modelo 686.', 4, 1, 'Unidad', 205, 185.96, 111, 16, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2689, 'INS-296', 'Cable UTP Cat6', 'Cable UTP Cat6 de alta calidad para uso en telecomunicaciones, modelo 771.', 1, 1, 'Unidad', 210, 143.50, 183, 17, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2690, 'INS-297', 'Router para Empresas', 'Router para Empresas de alta calidad para uso en telecomunicaciones, modelo 674.', 3, 1, 'Unidad', 202, 41.68, 197, 9, 0, 'Almacén Sur', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2691, 'INS-298', 'Conector FC', 'Conector FC de alta calidad para uso en telecomunicaciones, modelo 779.', 2, 1, 'Unidad', 205, 13.47, 66, 8, 0, 'Almacén Central', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2692, 'INS-299', 'Router Portátil', 'Router Portátil de alta calidad para uso en telecomunicaciones, modelo 523.', 3, 1, 'Unidad', 211, 343.17, 106, 14, 0, 'Almacén Este', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2693, 'INS-300', 'Router para Empresas', 'Router para Empresas de alta calidad para uso en telecomunicaciones, modelo 599.', 3, 1, 'Unidad', 209, 64.41, 29, 11, 0, 'Almacén Norte', NULL, NULL, 'activo', '2025-11-18 02:33:19', '2025-11-18 02:38:13'),
(2694, 'MAT-0155', 'Cable UTP', 'estado bueno', 1, 1, 'metro', 189, 5.00, 200, 50, 500, 'estante 098N', NULL, NULL, 'activo', '2025-11-18 02:44:34', '2025-11-18 02:44:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_inventario`
--

CREATE TABLE `movimientos_inventario` (
  `id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `tipo_movimiento` enum('entrada','salida','ajuste') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `sede_id` int(11) DEFAULT NULL,
  `tecnico_asignado_id` int(11) DEFAULT NULL,
  `fecha_movimiento` datetime DEFAULT current_timestamp(),
  `documento_referencia` varchar(100) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `movimientos_inventario`
--

INSERT INTO `movimientos_inventario` (`id`, `material_id`, `tipo_movimiento`, `cantidad`, `motivo`, `usuario_id`, `sede_id`, `tecnico_asignado_id`, `fecha_movimiento`, `documento_referencia`, `observaciones`, `created_at`) VALUES
(4, 284, 'entrada', 1, 'se requieren mas de estos', 6, 1, NULL, '2025-11-14 18:01:36', 'bereve', 'jrjfhjrjhrjfn', '2025-11-14 23:01:36'),
(14, 2402, 'salida', 1, 'Prueba de asignación', 23, 1, 24, '2025-11-17 19:46:01', NULL, 'Prueba de asignación con filtro de sede', '2025-11-18 00:46:01'),
(16, 2350, 'salida', 3, '', 23, 1, 24, '2025-11-17 20:41:14', NULL, NULL, '2025-11-18 01:41:14'),
(17, 2329, 'salida', 4, '', 23, 1, 24, '2025-11-17 20:41:14', NULL, NULL, '2025-11-18 01:41:14'),
(18, 2549, 'salida', 3, '', 23, 1, 24, '2025-11-18 08:28:02', NULL, NULL, '2025-11-18 13:28:02'),
(19, 2549, 'entrada', 1, 'Devolución de técnico: no necesito', 24, 1, 24, '2025-11-18 08:41:46', NULL, NULL, '2025-11-18 13:41:46'),
(20, 2329, 'entrada', 2, 'Devolución de técnico', 24, 1, 24, '2025-11-18 11:34:27', NULL, NULL, '2025-11-18 16:34:27'),
(21, 2614, 'salida', 2, '', 23, 1, 24, '2025-11-18 12:45:57', NULL, NULL, '2025-11-18 17:45:57'),
(22, 2632, 'salida', 20, '', 23, 1, 24, '2025-11-18 12:45:57', NULL, NULL, '2025-11-18 17:45:57'),
(23, 2554, 'salida', 2, '', 23, 1, 24, '2025-11-18 12:45:57', NULL, NULL, '2025-11-18 17:45:57'),
(24, 2632, 'entrada', 18, 'Devolución de técnico', 24, 1, 24, '2025-11-18 13:05:00', NULL, NULL, '2025-11-18 18:05:00'),
(25, 2614, 'entrada', 3, 'proveedor', 25, 1, NULL, '2025-11-18 13:15:37', 'FACTURA', 'NUEVOS', '2025-11-18 18:15:37'),
(27, 2225, 'entrada', 1, 'proveedor', 26, 7, NULL, '2025-11-18 16:36:08', 'FACTURA', '', '2025-11-18 21:36:08'),
(28, 2225, 'entrada', 5, 'ajuste', 26, 7, NULL, '2025-11-18 16:36:33', 'FACTURA', 'tyyty', '2025-11-18 21:36:33'),
(31, 2205, 'salida', 2, 'tecnico', 26, 7, NULL, '2025-11-18 16:40:31', NULL, NULL, '2025-11-18 21:40:31'),
(34, 2614, 'salida', 2, 'Salida para ajuste', 23, 1, NULL, '2025-11-18 17:24:35', 'e45354', '345345454', '2025-11-18 22:24:35'),
(35, 2632, 'entrada', 1, 'Devolución de técnico: no se ncesita', 24, 1, 24, '2025-11-18 17:40:49', NULL, NULL, '2025-11-18 22:40:49'),
(36, 2614, 'salida', 1, 'tecnico', 25, 1, NULL, '2025-11-18 17:54:48', NULL, NULL, '2025-11-18 22:54:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_recovery_tokens`
--

CREATE TABLE `password_recovery_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `password_recovery_tokens`
--

INSERT INTO `password_recovery_tokens` (`id`, `user_id`, `token`, `expires_at`, `used`, `created_at`) VALUES
(4, 6, '7bda6016e3d354db1c9d48c9c0d093d01d7f632c5d661fa485333138590ed6ec', '2025-11-18 19:26:25', 1, '2025-11-18 23:26:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `ruc` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id`, `nombre`, `ruc`, `direccion`, `telefono`, `email`, `contacto`, `estado`, `created_at`) VALUES
(1, 'Proveedor ZLR', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(2, 'Proveedor PEI', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(3, 'Proveedor APM', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(4, 'Proveedor JJE', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(5, 'Proveedor CYP', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(6, 'Proveedor VYE', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(7, 'Proveedor GXE', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(8, 'Proveedor BUV', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(9, 'Proveedor UZE', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(10, 'Proveedor ZPM', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(11, 'Proveedor UBV', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(12, 'Proveedor SAS', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(13, 'Proveedor VZG', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(14, 'Proveedor OBT', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(15, 'Proveedor VEC', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(16, 'Proveedor BWJ', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(17, 'Proveedor XGJ', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(18, 'Proveedor YZV', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(19, 'Proveedor FFK', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(20, 'Proveedor UWU', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(21, 'Proveedor LLA', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(22, 'Proveedor FXX', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(23, 'Proveedor AZM', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(24, 'Proveedor GQE', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(25, 'Proveedor FNA', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(26, 'Proveedor ZVC', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(27, 'Proveedor FQW', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(28, 'Proveedor EKT', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(29, 'Proveedor MBS', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(30, 'Proveedor EEW', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(31, 'Proveedor WQS', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(32, 'Proveedor SRQ', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(33, 'Proveedor EDN', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(34, 'Proveedor KHW', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(35, 'Proveedor MOH', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(36, 'Proveedor OHM', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(37, 'Proveedor JXX', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(38, 'Proveedor ZFT', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(39, 'Proveedor YCX', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(40, 'Proveedor JPF', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(41, 'Proveedor OBY', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(42, 'Proveedor FLG', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(43, 'Proveedor YSE', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(44, 'Proveedor NJS', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(45, 'Proveedor LEF', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(46, 'Proveedor PUY', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(47, 'Proveedor SOI', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(48, 'Proveedor UDK', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(49, 'Proveedor JJL', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(50, 'Proveedor NWB', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(51, 'Proveedor BAG', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(52, 'Proveedor WXN', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(53, 'Proveedor BZJ', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(54, 'Proveedor VWB', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(55, 'Proveedor YZN', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(56, 'Proveedor RZV', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(57, 'Proveedor DML', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(58, 'Proveedor AQS', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(59, 'Proveedor VOO', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(60, 'Proveedor GRL', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(61, 'Proveedor RLZ', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(62, 'Proveedor YGQ', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(63, 'Proveedor TEO', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(64, 'Proveedor VVB', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(65, 'Proveedor DAP', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(66, 'Proveedor QAB', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(67, 'Proveedor YFX', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(68, 'Proveedor XUJ', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(69, 'Proveedor JBB', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(70, 'Proveedor JOA', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(71, 'Proveedor BTO', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(72, 'Proveedor PHY', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(73, 'Proveedor XFF', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(74, 'Proveedor GBX', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(75, 'Proveedor RSY', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(76, 'Proveedor CZX', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(77, 'Proveedor GTA', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(78, 'Proveedor QMZ', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(79, 'Proveedor LQT', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(80, 'Proveedor CFN', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(81, 'Proveedor RTH', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(82, 'Proveedor SUP', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(83, 'Proveedor NHN', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(84, 'Proveedor HXP', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(85, 'Proveedor HKE', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(86, 'Proveedor XQI', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(87, 'Proveedor ZOU', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(88, 'Proveedor TMW', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(89, 'Proveedor RZL', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(90, 'Proveedor YMM', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(91, 'Proveedor OCM', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(92, 'Proveedor JHU', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(93, 'Proveedor FYQ', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(94, 'Proveedor VGI', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(95, 'Proveedor WQY', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(96, 'Proveedor DOA', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(97, 'Proveedor KMA', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(98, 'Proveedor BVD', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(99, 'Proveedor JIJ', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(100, 'Proveedor DSM', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(101, 'Proveedor IOR', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(102, 'Proveedor YGA', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(103, 'Proveedor WAE', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(104, 'Proveedor VMI', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(105, 'Proveedor RFD', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(106, 'Proveedor JRH', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(107, 'Proveedor DOO', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(108, 'Proveedor JWT', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(109, 'Proveedor YUG', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(110, 'Proveedor HSF', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(111, 'Proveedor QWP', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(112, 'Proveedor HIG', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(113, 'Proveedor GAS', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(114, 'Proveedor JXS', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(115, 'Proveedor VCZ', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(116, 'Proveedor LYR', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(117, 'Proveedor WUR', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(118, 'Proveedor RBM', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(119, 'Proveedor QJP', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(120, 'Proveedor QDY', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(121, 'Proveedor PHB', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(122, 'Proveedor HGD', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(123, 'Proveedor LTF', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(124, 'Proveedor AMW', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(125, 'Proveedor LMJ', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(126, 'Proveedor CJU', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(127, 'Proveedor ITQ', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(128, 'Proveedor FTJ', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(129, 'Proveedor LEL', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(130, 'Proveedor MIV', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(131, 'Proveedor TVY', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(132, 'Proveedor POD', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(133, 'Proveedor VHF', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(134, 'Proveedor VEI', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(135, 'Proveedor RXQ', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(136, 'Proveedor LFR', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(137, 'Proveedor NJX', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(138, 'Proveedor XAU', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(139, 'Proveedor PXO', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(140, 'Proveedor LMK', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(141, 'Proveedor JMJ', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(142, 'Proveedor LNS', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(143, 'Proveedor KDQ', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(144, 'Proveedor YYR', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(145, 'Proveedor WGN', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(146, 'Proveedor EXA', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(147, 'Proveedor MLA', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(148, 'Proveedor LXB', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(149, 'Proveedor VAX', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(150, 'Proveedor JID', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(151, 'Proveedor YZG', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(152, 'Proveedor QAX', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(153, 'Proveedor VHN', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(154, 'Proveedor AJC', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(155, 'Proveedor DQB', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(156, 'Proveedor VVI', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(157, 'Proveedor JGF', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(158, 'Proveedor XLX', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(159, 'Proveedor LHP', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(160, 'Proveedor NYR', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(161, 'Proveedor FPX', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(162, 'Proveedor TOP', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(163, 'Proveedor TYB', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(164, 'Proveedor DSO', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(165, 'Proveedor OPT', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(166, 'Proveedor TRS', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(167, 'Proveedor ORQ', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(168, 'Proveedor NSS', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(169, 'Proveedor VKD', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(170, 'Proveedor ZOG', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(171, 'Proveedor JTM', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(172, 'Proveedor EUQ', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(173, 'Proveedor HJC', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(174, 'Proveedor VLB', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(175, 'Proveedor CCO', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(176, 'Proveedor KPL', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(177, 'Proveedor LHU', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(178, 'Proveedor KSG', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(179, 'Proveedor IXU', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(180, 'Proveedor INZ', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(181, 'Proveedor XNX', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(182, 'Proveedor FED', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(183, 'Proveedor CEY', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(184, 'Proveedor IXH', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(185, 'Proveedor NLQ', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(186, 'Proveedor TUB', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(187, 'Proveedor LHJ', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(188, 'Proveedor TIS', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(189, 'Proveedor AZD', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(190, 'Proveedor IIL', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(191, 'Proveedor VCT', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(192, 'Proveedor ALE', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(193, 'Proveedor KUN', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(194, 'Proveedor HXB', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:01'),
(195, 'Proveedor ZMW', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:02'),
(196, 'Proveedor HTI', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 14:50:02'),
(197, 'ElectroPeru S.A.', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 15:00:48'),
(198, 'Claro Proveedores', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 15:00:48'),
(199, 'SolucionesEléctricas SAC', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 15:00:48'),
(200, 'RedesGlobal SRL', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 15:00:48'),
(201, 'TecnoLink SAC', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-12 15:00:48'),
(202, 'Proveedor 10', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-17 22:11:14'),
(203, 'Proveedor 7', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-17 22:11:14'),
(204, 'Proveedor 1', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-17 22:11:14'),
(205, 'Proveedor 6', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-17 22:11:14'),
(206, 'Proveedor 9', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-17 22:11:14'),
(207, 'Proveedor 5', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-17 22:11:14'),
(208, 'Proveedor 2', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-17 22:11:14'),
(209, 'Proveedor 4', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-17 22:11:14'),
(210, 'Proveedor 8', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-17 22:11:14'),
(211, 'Proveedor 3', NULL, NULL, '', '', 'Contacto automático', 'activo', '2025-11-17 22:11:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_used` timestamp NULL DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `descripcion`, `created_at`) VALUES
(1, 'Administrador', 'Acceso completo al sistema', '2025-11-12 14:18:53'),
(2, 'Jefe de Almacen', 'Gestion completa de materiales e inventario', '2025-11-12 14:18:53'),
(3, 'Asistente de Almacen', 'Apoyo en gestion de materiales y solicitudes', '2025-11-12 14:18:53'),
(4, 'Tecnico', 'Solicitud de materiales y gestion de actas tecnicas', '2025-11-12 14:18:53'),
(5, 'Superadministrador', 'Control total del sistema multi-sede', '2025-11-15 17:38:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `salidas_materiales`
--

CREATE TABLE `salidas_materiales` (
  `id` int(11) NOT NULL,
  `movimiento_id` int(11) NOT NULL,
  `tipo_salida` enum('proyecto','tecnico','devolucion_proveedor','ajuste') DEFAULT 'proyecto',
  `proyecto_id` int(11) DEFAULT NULL,
  `tecnico_id` int(11) DEFAULT NULL,
  `numero_orden` varchar(100) DEFAULT NULL,
  `fecha_salida` date NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `salidas_materiales`
--

INSERT INTO `salidas_materiales` (`id`, `movimiento_id`, `tipo_salida`, `proyecto_id`, `tecnico_id`, `numero_orden`, `fecha_salida`, `usuario_id`, `created_at`, `updated_at`) VALUES
(1, 31, 'tecnico', NULL, 24, '2', '2025-11-18', 26, '2025-11-18 21:40:31', '2025-11-18 21:40:31'),
(2, 34, 'ajuste', NULL, NULL, 'e45354', '2025-11-18', 23, '2025-11-18 22:24:35', '2025-11-18 22:24:35'),
(3, 36, 'tecnico', NULL, 24, 'e23423', '2025-11-18', 25, '2025-11-18 22:54:48', '2025-11-18 22:54:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sedes`
--

CREATE TABLE `sedes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `responsable_id` int(11) DEFAULT NULL,
  `estado` enum('activa','inactiva') DEFAULT 'activa',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sedes`
--

INSERT INTO `sedes` (`id`, `nombre`, `codigo`, `direccion`, `telefono`, `email`, `responsable_id`, `estado`, `created_at`, `updated_at`) VALUES
(1, 'SEDE CENTRAL LIMA', 'SC01', 'Av. Principal 123, Lima', '01-234-5678', 'central.lima@gmail.com', 6, 'activa', '2025-11-13 15:01:45', '2025-11-17 23:07:16'),
(6, 'SEDE TALARA', 'C03', 'Calle avelardo lote 12 mz34', '942308899', 'talarasede@gmail.com', 18, 'activa', '2025-11-17 14:43:23', '2025-11-17 23:06:36'),
(7, 'SEDE PIURA', 'CO4', 'Av. Sanchez cerro', '942305512', 'sedepiura@gmail.com', 19, 'activa', '2025-11-17 15:36:04', '2025-11-17 15:36:04'),
(8, 'SEDE PAITA', 'COS04', 'paita flow mz23', '942308899', 'sedepaita@gmail.com', NULL, 'activa', '2025-11-18 22:35:53', '2025-11-18 22:35:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes`
--

CREATE TABLE `solicitudes` (
  `id` int(11) NOT NULL,
  `codigo_solicitud` varchar(50) DEFAULT NULL,
  `tecnico_id` int(11) NOT NULL,
  `sede_id` int(11) DEFAULT NULL,
  `fecha_solicitud` datetime DEFAULT current_timestamp(),
  `estado` enum('pendiente','aprobada','rechazada','completada') DEFAULT 'pendiente',
  `motivo` text NOT NULL,
  `justificacion` text DEFAULT NULL,
  `fecha_respuesta` datetime DEFAULT NULL,
  `usuario_respuesta_id` int(11) DEFAULT NULL,
  `comentario_respuesta` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_detalle`
--

CREATE TABLE `solicitudes_detalle` (
  `id` int(11) NOT NULL,
  `solicitud_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `cantidad_solicitada` int(11) NOT NULL,
  `cantidad_aprobada` int(11) DEFAULT 0,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stock_tecnicos`
--

CREATE TABLE `stock_tecnicos` (
  `id` int(11) NOT NULL,
  `tecnico_id` int(11) NOT NULL,
  `sede_id` int(11) DEFAULT NULL,
  `material_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 0,
  `fecha_asignacion` datetime DEFAULT current_timestamp(),
  `movimiento_id` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `stock_tecnicos`
--

INSERT INTO `stock_tecnicos` (`id`, `tecnico_id`, `sede_id`, `material_id`, `cantidad`, `fecha_asignacion`, `movimiento_id`, `updated_at`) VALUES
(1, 24, 1, 2402, 2, '2025-11-17 19:46:05', NULL, '2025-11-18 00:46:05'),
(6, 24, 1, 2350, 3, '2025-11-17 20:41:14', NULL, '2025-11-18 01:41:14'),
(7, 24, 1, 2329, 2, '2025-11-18 11:34:27', NULL, '2025-11-18 16:34:27'),
(8, 24, 1, 2549, 2, '2025-11-18 08:41:46', NULL, '2025-11-18 13:41:46'),
(9, 24, 1, 2614, 2, '2025-11-18 12:45:57', NULL, '2025-11-18 17:45:57'),
(10, 24, 1, 2632, 1, '2025-11-18 17:40:49', NULL, '2025-11-18 22:40:49'),
(11, 24, 1, 2554, 2, '2025-11-18 12:45:57', NULL, '2025-11-18 17:45:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidades_medida`
--

CREATE TABLE `unidades_medida` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `simbolo` varchar(10) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `unidades_medida`
--

INSERT INTO `unidades_medida` (`id`, `nombre`, `simbolo`, `descripcion`, `created_at`, `updated_at`) VALUES
(3, 'Metro', 'm', 'Unidad de longitud', '2025-11-18 17:16:56', '2025-11-18 17:16:56'),
(11, 'Unidad', 'U', '', '2025-11-18 17:18:46', '2025-11-18 17:18:46'),
(14, 'Kit', 'KIT', '', '2025-11-18 17:19:46', '2025-11-18 17:19:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `imagen_perfil` varchar(255) DEFAULT NULL,
  `rol_id` int(11) NOT NULL,
  `sede_id` int(11) DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `ultimo_acceso` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `nombre_completo`, `email`, `telefono`, `imagen_perfil`, `rol_id`, `sede_id`, `estado`, `ultimo_acceso`, `created_at`, `updated_at`) VALUES
(6, 'insertel25', '$2y$10$uOoSlXZuHyFqYqML/4V1tOP4555B2jAmopp/kIaWrvqWisKsJF5pe', 'INSERTEL ADMIN', 'insertel25@gmail.com', '987654321', NULL, 1, 1, 'activo', '2025-11-18 18:29:18', '2025-11-12 14:27:36', '2025-11-18 23:29:18'),
(16, 'superadmin1', '$2y$10$/POGwy84NdPL8dHPTkp9KuURMljaNOa/3Pyi7VMiWwwavlP53MqXe', 'INSERTEL CEO', 'insertelceo25@gmail.com', '987654321', 'perfil_16_1763321879.png', 5, 1, 'activo', '2025-11-18 18:22:19', '2025-11-15 17:47:51', '2025-11-18 23:22:19'),
(18, 'adminTalara', '$2y$10$V3sJlcUaQGNY.0iBxMsJzu06x7.tGAgekSKNnDXzqdC9nLZdFY5Xu', 'Juan Alvarado', 'juan25@gmail.com', '942398812', NULL, 1, 6, 'activo', '2025-11-17 16:49:40', '2025-11-17 14:42:35', '2025-11-17 21:49:40'),
(19, 'adminPiura', '$2y$10$xvFfd1WnsCFzTDqcsSu5MOYjhwTAmuW2WDYBVbwkltY5iTDfJlaOO', 'Daniel Campos Tavara', 'tavaradanny25@gmail.com', '942387716', NULL, 1, 7, 'activo', '2025-11-18 18:25:25', '2025-11-17 15:35:15', '2025-11-18 23:25:25'),
(23, 'jefealm1', '$2y$10$3.KBoLZ2cgujoH4ScLsicu7V9e0eSIqw47mU9dOmqcLPKFuZJepeq', 'Pablo Chinguel Aponte', 'aponte45@gmail.com', '988308812', NULL, 2, 1, 'activo', '2025-11-18 18:12:07', '2025-11-18 00:15:13', '2025-11-18 23:12:07'),
(24, 'tecnico1', '$2y$10$pFLKuMWVhmu5kTPYNUd0KucVN1cC4whaKEEz2FWArVaDdG5HBxXlK', 'Daniel Campos Tavara', 'campos25@gmail.com', '942308800', NULL, 4, 1, 'activo', '2025-11-18 18:20:20', '2025-11-18 00:16:53', '2025-11-18 23:20:20'),
(25, 'asistalm1', '$2y$10$/1YueHz9E6fC9zO8zaWWcu5G3.ERvVT9BsGNqSbJd6mgA1RfN5WaS', 'Martin Panigua Colorado', 'colorado234@gmail.com', '942308890', NULL, 3, 1, 'activo', '2025-11-18 18:33:21', '2025-11-18 02:16:37', '2025-11-18 23:33:36'),
(26, 'jefealmpiura', '$2y$10$j9IzqIHivkDYU/t7nYSq..ad/zp6sf0y3pY4zbPfjjLG6dxtmJv66', 'Gerardo Huamani', 'huamani25@gmail.com', '942308812', NULL, 2, 7, 'activo', '2025-11-18 17:39:34', '2025-11-18 21:35:18', '2025-11-18 22:39:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `verificaciones_calidad`
--

CREATE TABLE `verificaciones_calidad` (
  `id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `movimiento_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_verificacion` datetime DEFAULT current_timestamp(),
  `estado_calidad` enum('conforme','no_conforme','observaciones') NOT NULL,
  `observaciones` text DEFAULT NULL,
  `acciones_correctivas` text DEFAULT NULL,
  `proveedor_id` int(11) DEFAULT NULL,
  `lote_proveedor` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `verificacion_calidad_proveedor`
--

CREATE TABLE `verificacion_calidad_proveedor` (
  `id` int(11) NOT NULL,
  `entrada_id` int(11) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `cantidad_recibida` int(11) NOT NULL,
  `cantidad_conforme` int(11) NOT NULL,
  `cantidad_no_conforme` int(11) DEFAULT 0,
  `defectos_encontrados` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `estado_verificacion` enum('pendiente','conforme','no_conforme','parcial') DEFAULT 'pendiente',
  `fecha_verificacion` datetime DEFAULT NULL,
  `usuario_verificador_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `actas_tecnicas`
--
ALTER TABLE `actas_tecnicas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_acta` (`codigo_acta`),
  ADD KEY `idx_tecnico` (`tecnico_id`),
  ADD KEY `idx_fecha` (`fecha_servicio`);

--
-- Indices de la tabla `alertas_sistema`
--
ALTER TABLE `alertas_sistema`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rol_id` (`rol_id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `solicitud_id` (`solicitud_id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_leida` (`leida`);

--
-- Indices de la tabla `alertas_vencimiento`
--
ALTER TABLE `alertas_vencimiento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `entrada_id` (`entrada_id`),
  ADD KEY `idx_fecha` (`fecha_vencimiento`),
  ADD KEY `idx_estado` (`estado_alerta`);

--
-- Indices de la tabla `auditoria_sede`
--
ALTER TABLE `auditoria_sede`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_auditoria_sede` (`sede_id`),
  ADD KEY `fk_auditoria_usuario` (`usuario_id`),
  ADD KEY `idx_fecha_accion` (`fecha_accion`),
  ADD KEY `idx_accion` (`accion`);

--
-- Indices de la tabla `categorias_materiales`
--
ALTER TABLE `categorias_materiales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `configuraciones_sede`
--
ALTER TABLE `configuraciones_sede`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sede_clave_UNIQUE` (`sede_id`,`clave`),
  ADD KEY `fk_config_sede` (`sede_id`);

--
-- Indices de la tabla `configuracion_sistema`
--
ALTER TABLE `configuracion_sistema`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`),
  ADD KEY `idx_categoria` (`categoria`);

--
-- Indices de la tabla `devoluciones_materiales`
--
ALTER TABLE `devoluciones_materiales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movimiento_id` (`movimiento_id`),
  ADD KEY `tecnico_id` (`tecnico_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `entradas_materiales`
--
ALTER TABLE `entradas_materiales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movimiento_id` (`movimiento_id`),
  ADD KEY `proveedor_id` (`proveedor_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_fecha` (`fecha_entrada`),
  ADD KEY `idx_tipo` (`tipo_entrada`);

--
-- Indices de la tabla `historial_actividades`
--
ALTER TABLE `historial_actividades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_modulo` (`modulo`);

--
-- Indices de la tabla `materiales`
--
ALTER TABLE `materiales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_sede` (`codigo`,`sede_id`),
  ADD KEY `proveedor_id` (`proveedor_id`),
  ADD KEY `idx_codigo` (`codigo`),
  ADD KEY `idx_categoria` (`categoria_id`),
  ADD KEY `idx_stock` (`stock_actual`),
  ADD KEY `fk_material_sede` (`sede_id`),
  ADD KEY `idx_materiales_sede_estado` (`sede_id`,`estado`);

--
-- Indices de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `tecnico_asignado_id` (`tecnico_asignado_id`),
  ADD KEY `idx_material` (`material_id`),
  ADD KEY `idx_fecha` (`fecha_movimiento`),
  ADD KEY `idx_tipo` (`tipo_movimiento`),
  ADD KEY `fk_movimiento_sede` (`sede_id`),
  ADD KEY `idx_movimientos_sede_fecha` (`sede_id`,`fecha_movimiento`);

--
-- Indices de la tabla `password_recovery_tokens`
--
ALTER TABLE `password_recovery_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `salidas_materiales`
--
ALTER TABLE `salidas_materiales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movimiento_id` (`movimiento_id`),
  ADD KEY `tecnico_id` (`tecnico_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_fecha` (`fecha_salida`),
  ADD KEY `idx_tipo` (`tipo_salida`);

--
-- Indices de la tabla `sedes`
--
ALTER TABLE `sedes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_codigo` (`codigo`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tecnico_id` (`tecnico_id`),
  ADD KEY `sede_id` (`sede_id`),
  ADD KEY `usuario_respuesta_id` (`usuario_respuesta_id`),
  ADD KEY `estado` (`estado`),
  ADD KEY `fecha_solicitud` (`fecha_solicitud`);

--
-- Indices de la tabla `solicitudes_detalle`
--
ALTER TABLE `solicitudes_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `solicitud_id` (`solicitud_id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indices de la tabla `stock_tecnicos`
--
ALTER TABLE `stock_tecnicos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tecnico_material` (`tecnico_id`,`material_id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `movimiento_id` (`movimiento_id`),
  ADD KEY `idx_tecnico` (`tecnico_id`),
  ADD KEY `fk_stock_tecnico_sede` (`sede_id`);

--
-- Indices de la tabla `unidades_medida`
--
ALTER TABLE `unidades_medida`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD UNIQUE KEY `simbolo` (`simbolo`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_rol` (`rol_id`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `fk_usuario_sede` (`sede_id`),
  ADD KEY `idx_usuarios_sede_rol` (`sede_id`,`rol_id`,`estado`),
  ADD KEY `idx_usuarios_imagen` (`imagen_perfil`);

--
-- Indices de la tabla `verificaciones_calidad`
--
ALTER TABLE `verificaciones_calidad`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movimiento_id` (`movimiento_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_material` (`material_id`),
  ADD KEY `idx_fecha` (`fecha_verificacion`);

--
-- Indices de la tabla `verificacion_calidad_proveedor`
--
ALTER TABLE `verificacion_calidad_proveedor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `entrada_id` (`entrada_id`),
  ADD KEY `proveedor_id` (`proveedor_id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `usuario_verificador_id` (`usuario_verificador_id`),
  ADD KEY `idx_estado` (`estado_verificacion`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `actas_tecnicas`
--
ALTER TABLE `actas_tecnicas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `alertas_sistema`
--
ALTER TABLE `alertas_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `alertas_vencimiento`
--
ALTER TABLE `alertas_vencimiento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `auditoria_sede`
--
ALTER TABLE `auditoria_sede`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias_materiales`
--
ALTER TABLE `categorias_materiales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `configuraciones_sede`
--
ALTER TABLE `configuraciones_sede`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `configuracion_sistema`
--
ALTER TABLE `configuracion_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `devoluciones_materiales`
--
ALTER TABLE `devoluciones_materiales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `entradas_materiales`
--
ALTER TABLE `entradas_materiales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `historial_actividades`
--
ALTER TABLE `historial_actividades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=632;

--
-- AUTO_INCREMENT de la tabla `materiales`
--
ALTER TABLE `materiales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2695;

--
-- AUTO_INCREMENT de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de la tabla `password_recovery_tokens`
--
ALTER TABLE `password_recovery_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=212;

--
-- AUTO_INCREMENT de la tabla `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `salidas_materiales`
--
ALTER TABLE `salidas_materiales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `sedes`
--
ALTER TABLE `sedes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitudes_detalle`
--
ALTER TABLE `solicitudes_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `stock_tecnicos`
--
ALTER TABLE `stock_tecnicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `unidades_medida`
--
ALTER TABLE `unidades_medida`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `verificaciones_calidad`
--
ALTER TABLE `verificaciones_calidad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `verificacion_calidad_proveedor`
--
ALTER TABLE `verificacion_calidad_proveedor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `actas_tecnicas`
--
ALTER TABLE `actas_tecnicas`
  ADD CONSTRAINT `actas_tecnicas_ibfk_1` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `alertas_sistema`
--
ALTER TABLE `alertas_sistema`
  ADD CONSTRAINT `alertas_sistema_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `alertas_sistema_ibfk_2` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `alertas_sistema_ibfk_3` FOREIGN KEY (`material_id`) REFERENCES `materiales` (`id`),
  ADD CONSTRAINT `alertas_sistema_ibfk_4` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitudes` (`id`);

--
-- Filtros para la tabla `alertas_vencimiento`
--
ALTER TABLE `alertas_vencimiento`
  ADD CONSTRAINT `alertas_vencimiento_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `materiales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alertas_vencimiento_ibfk_2` FOREIGN KEY (`entrada_id`) REFERENCES `entradas_materiales` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `devoluciones_materiales`
--
ALTER TABLE `devoluciones_materiales`
  ADD CONSTRAINT `devoluciones_materiales_ibfk_1` FOREIGN KEY (`movimiento_id`) REFERENCES `movimientos_inventario` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `devoluciones_materiales_ibfk_2` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `devoluciones_materiales_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `entradas_materiales`
--
ALTER TABLE `entradas_materiales`
  ADD CONSTRAINT `entradas_materiales_ibfk_1` FOREIGN KEY (`movimiento_id`) REFERENCES `movimientos_inventario` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `entradas_materiales_ibfk_2` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `entradas_materiales_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `historial_actividades`
--
ALTER TABLE `historial_actividades`
  ADD CONSTRAINT `historial_actividades_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `materiales`
--
ALTER TABLE `materiales`
  ADD CONSTRAINT `materiales_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_materiales` (`id`),
  ADD CONSTRAINT `materiales_ibfk_2` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`);

--
-- Filtros para la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD CONSTRAINT `movimientos_inventario_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `materiales` (`id`),
  ADD CONSTRAINT `movimientos_inventario_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `movimientos_inventario_ibfk_3` FOREIGN KEY (`tecnico_asignado_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `password_recovery_tokens`
--
ALTER TABLE `password_recovery_tokens`
  ADD CONSTRAINT `password_recovery_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `salidas_materiales`
--
ALTER TABLE `salidas_materiales`
  ADD CONSTRAINT `salidas_materiales_ibfk_1` FOREIGN KEY (`movimiento_id`) REFERENCES `movimientos_inventario` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `salidas_materiales_ibfk_2` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `salidas_materiales_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  ADD CONSTRAINT `fk_solicitudes_responsable` FOREIGN KEY (`usuario_respuesta_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_solicitudes_sede` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_solicitudes_tecnico` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `solicitudes_detalle`
--
ALTER TABLE `solicitudes_detalle`
  ADD CONSTRAINT `fk_solicitudes_detalle_material` FOREIGN KEY (`material_id`) REFERENCES `materiales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_solicitudes_detalle_solicitud` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitudes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `stock_tecnicos`
--
ALTER TABLE `stock_tecnicos`
  ADD CONSTRAINT `stock_tecnicos_ibfk_1` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `stock_tecnicos_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `materiales` (`id`),
  ADD CONSTRAINT `stock_tecnicos_ibfk_3` FOREIGN KEY (`movimiento_id`) REFERENCES `movimientos_inventario` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);

--
-- Filtros para la tabla `verificaciones_calidad`
--
ALTER TABLE `verificaciones_calidad`
  ADD CONSTRAINT `verificaciones_calidad_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `materiales` (`id`),
  ADD CONSTRAINT `verificaciones_calidad_ibfk_2` FOREIGN KEY (`movimiento_id`) REFERENCES `movimientos_inventario` (`id`),
  ADD CONSTRAINT `verificaciones_calidad_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `verificacion_calidad_proveedor`
--
ALTER TABLE `verificacion_calidad_proveedor`
  ADD CONSTRAINT `verificacion_calidad_proveedor_ibfk_1` FOREIGN KEY (`entrada_id`) REFERENCES `entradas_materiales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `verificacion_calidad_proveedor_ibfk_2` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`),
  ADD CONSTRAINT `verificacion_calidad_proveedor_ibfk_3` FOREIGN KEY (`material_id`) REFERENCES `materiales` (`id`),
  ADD CONSTRAINT `verificacion_calidad_proveedor_ibfk_4` FOREIGN KEY (`usuario_verificador_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
