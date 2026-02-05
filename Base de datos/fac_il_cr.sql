-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 03-02-2026 a las 03:18:13
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
-- Base de datos: `fac_il_cr`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `id` bigint(20) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `modulo` varchar(60) NOT NULL,
  `accion` varchar(40) NOT NULL,
  `tabla_nombre` varchar(80) DEFAULT NULL,
  `registro_id` bigint(20) DEFAULT NULL,
  `antes_json` longtext DEFAULT NULL,
  `despues_json` longtext DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `auditoria`
--

INSERT INTO `auditoria` (`id`, `empresa_id`, `usuario_id`, `modulo`, `accion`, `tabla_nombre`, `registro_id`, `antes_json`, `despues_json`, `ip`, `user_agent`, `created_at`) VALUES
(1, 1, NULL, 'DB', 'UPDATE', 'productos', 1, '{\"codigo\": \"1\", \"descripcion\": \"Vaso\", \"precio\": 200.00, \"costo\": 100.00}', '{\"codigo\": \"1\", \"descripcion\": \"Vaso1\", \"precio\": 200.00, \"costo\": 100.00}', NULL, 'TRIGGER', '2026-01-31 21:26:45'),
(2, 1, NULL, 'DB', 'UPDATE', 'clientes', 1, '{\"nombre\": \"Marco Prueba\", \"estado\": \"ACTIVO\", \"limite_credito\": 0.00}', '{\"nombre\": \"Marco Prueba\", \"estado\": \"ACTIVO\", \"limite_credito\": 0.00}', NULL, 'TRIGGER', '2026-02-01 03:23:38'),
(3, 1, NULL, 'DB', 'UPDATE', 'clientes', 1, '{\"nombre\": \"Marco Prueba\", \"estado\": \"ACTIVO\", \"limite_credito\": 0.00}', '{\"nombre\": \"Marco Prueba\", \"estado\": \"\", \"limite_credito\": 0.00}', NULL, 'TRIGGER', '2026-02-01 03:23:43'),
(4, 1, NULL, 'DB', 'UPDATE', 'clientes', 1, '{\"nombre\": \"Marco Prueba\", \"estado\": \"\", \"limite_credito\": 0.00}', '{\"nombre\": \"Marco Prueba\", \"estado\": \"ACTIVO\", \"limite_credito\": 0.00}', NULL, 'TRIGGER', '2026-02-01 03:23:53'),
(5, 1, NULL, 'DB', 'UPDATE', 'usuarios', 1, '{\"nombre\": \"Admin\", \"email\": \"admin@facilcr.local\", \"estado\": 1}', '{\"nombre\": \"Admin\", \"email\": \"admin@facilcr.local\", \"estado\": 1}', NULL, 'TRIGGER', '2026-02-02 19:04:29'),
(6, 1, NULL, 'DB', 'UPDATE', 'usuarios', 4, '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', NULL, 'TRIGGER', '2026-02-02 20:02:15'),
(7, 1, NULL, 'DB', 'UPDATE', 'usuarios', 4, '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', NULL, 'TRIGGER', '2026-02-02 20:40:47'),
(8, 1, NULL, 'DB', 'UPDATE', 'usuarios', 4, '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', NULL, 'TRIGGER', '2026-02-02 20:44:13'),
(9, 1, NULL, 'DB', 'UPDATE', 'usuarios', 4, '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', NULL, 'TRIGGER', '2026-02-02 20:49:43'),
(10, 1, NULL, 'DB', 'UPDATE', 'usuarios', 4, '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', NULL, 'TRIGGER', '2026-02-02 20:49:43'),
(11, 1, NULL, 'DB', 'UPDATE', 'usuarios', 4, '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', NULL, 'TRIGGER', '2026-02-02 21:01:51'),
(12, 1, NULL, 'DB', 'UPDATE', 'usuarios', 4, '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', NULL, 'TRIGGER', '2026-02-02 21:14:35'),
(13, 1, NULL, 'DB', 'UPDATE', 'usuarios', 4, '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', NULL, 'TRIGGER', '2026-02-02 21:19:07'),
(14, 1, NULL, 'DB', 'UPDATE', 'usuarios', 4, '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', NULL, 'TRIGGER', '2026-02-02 21:23:49'),
(15, 1, NULL, 'DB', 'UPDATE', 'usuarios', 4, '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', NULL, 'TRIGGER', '2026-02-02 21:34:25'),
(16, 1, NULL, 'DB', 'UPDATE', 'usuarios', 4, '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', NULL, 'TRIGGER', '2026-02-02 21:40:36'),
(17, 1, NULL, 'DB', 'UPDATE', 'usuarios', 4, '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', NULL, 'TRIGGER', '2026-02-02 21:47:54'),
(18, 1, NULL, 'DB', 'UPDATE', 'usuarios', 4, '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', NULL, 'TRIGGER', '2026-02-02 21:53:11'),
(19, 1, NULL, 'DB', 'UPDATE', 'usuarios', 4, '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', NULL, 'TRIGGER', '2026-02-02 21:58:20'),
(20, 1, NULL, 'DB', 'UPDATE', 'usuarios', 4, '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', '{\"nombre\": \"Marco Jimenez\", \"email\": \"sistemas08il@outlook.com\", \"estado\": 1}', NULL, 'TRIGGER', '2026-02-02 22:07:16'),
(21, 1, NULL, 'DB', 'UPDATE', 'inventario_existencias', 7, '{\"existencia\": 87.000, \"stock_minimo\": 5.000}', '{\"existencia\": 86.000, \"stock_minimo\": 5.000}', NULL, 'TRIGGER', '2026-02-03 00:50:30'),
(22, 1, NULL, 'DB', 'INSERT', 'inventario_movimientos', 5, NULL, '{\"producto_id\": 2, \"tipo\": \"SALIDA\", \"cantidad\": 1.000, \"motivo\": \"VENTA #46\"}', NULL, 'TRIGGER', '2026-02-03 00:50:30'),
(25, 1, NULL, 'DB', 'UPDATE', 'inventario_existencias', 7, '{\"existencia\": 86.000, \"stock_minimo\": 5.000}', '{\"existencia\": 85.000, \"stock_minimo\": 5.000}', NULL, 'TRIGGER', '2026-02-03 01:13:42'),
(26, 1, NULL, 'DB', 'INSERT', 'inventario_movimientos', 7, NULL, '{\"producto_id\": 2, \"tipo\": \"SALIDA\", \"cantidad\": 1.000, \"motivo\": \"VENTA #48\"}', NULL, 'TRIGGER', '2026-02-03 01:13:42'),
(27, 1, NULL, 'DB', 'INSERT', 'cxc_abonos', 1, NULL, '{\"monto\": 10.00, \"metodo\": \"EFECTIVO\", \"venta_id\": 48}', NULL, 'TRIGGER', '2026-02-03 01:21:47'),
(28, 1, NULL, 'DB', 'UPDATE', 'cxc_documentos', 1, '{\"saldo\": 570.65, \"estado\": \"PENDIENTE\"}', '{\"saldo\": 560.65, \"estado\": \"PENDIENTE\"}', NULL, 'TRIGGER', '2026-02-03 01:21:47'),
(29, 1, NULL, 'DB', 'UPDATE', 'cxc_documentos', 1, '{\"saldo\": 560.65, \"estado\": \"PENDIENTE\"}', '{\"saldo\": 560.65, \"estado\": \"PENDIENTE\"}', NULL, 'TRIGGER', '2026-02-03 01:21:47'),
(30, 1, 4, 'CXC', 'ABONO', 'cxc_abonos', 0, NULL, '{\"cxc_documento_id\":1,\"monto\":10,\"metodo\":\"EFECTIVO\",\"referencia\":\"pago abono\",\"saldo_antes\":{\"saldo\":\"570.65\",\"estado\":\"PENDIENTE\"},\"saldo_despues\":{\"saldo\":\"560.65\",\"estado\":\"PENDIENTE\"}}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-03 01:21:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bodegas`
--

CREATE TABLE `bodegas` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `sucursal_id` int(11) DEFAULT NULL,
  `nombre` varchar(120) NOT NULL,
  `estado` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `bodegas`
--

INSERT INTO `bodegas` (`id`, `empresa_id`, `sucursal_id`, `nombre`, `estado`, `created_at`) VALUES
(1, 1, 1, 'Bodega Principal', 1, '2026-01-31 19:15:52'),
(2, 1, 1, 'Sala / Tienda', 0, '2026-01-31 19:15:52'),
(3, 1, 1, 'Prueba', 0, '2026-01-31 19:52:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `nombre` varchar(160) NOT NULL,
  `identificacion` varchar(25) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `tipo` enum('CONTADO','CREDITO') NOT NULL DEFAULT 'CONTADO',
  `limite_credito` decimal(18,2) NOT NULL DEFAULT 0.00,
  `plazo_dias` int(11) NOT NULL DEFAULT 0,
  `estado` enum('ACTIVO','MOROSO','BLOQUEADO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `empresa_id`, `nombre`, `identificacion`, `email`, `telefono`, `direccion`, `tipo`, `limite_credito`, `plazo_dias`, `estado`, `created_at`, `updated_at`) VALUES
(1, 1, 'Marco Prueba', NULL, NULL, NULL, NULL, 'CONTADO', 0.00, 0, 'ACTIVO', '2026-01-30 05:48:26', '2026-02-01 03:23:53');

--
-- Disparadores `clientes`
--
DELIMITER $$
CREATE TRIGGER `trg_aud_clientes_upd` AFTER UPDATE ON `clientes` FOR EACH ROW BEGIN
  INSERT INTO auditoria
  VALUES
  (NULL,NEW.empresa_id,NULL,'DB','UPDATE','clientes',NEW.id,
   JSON_OBJECT('nombre',OLD.nombre,'estado',OLD.estado,'limite_credito',OLD.limite_credito),
   JSON_OBJECT('nombre',NEW.nombre,'estado',NEW.estado,'limite_credito',NEW.limite_credito),
   NULL,'TRIGGER',NOW());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comisiones_calculadas`
--

CREATE TABLE `comisiones_calculadas` (
  `id` bigint(20) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `venta_id` bigint(20) DEFAULT NULL,
  `pago_id` bigint(20) DEFAULT NULL,
  `porcentaje` decimal(6,3) NOT NULL,
  `monto` decimal(18,2) NOT NULL,
  `estado` enum('PENDIENTE','PAGADA','ANULADA') NOT NULL DEFAULT 'PENDIENTE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comisiones_reglas`
--

CREATE TABLE `comisiones_reglas` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `porcentaje` decimal(6,3) NOT NULL DEFAULT 0.000,
  `tipo` enum('POR_VENTA','POR_COBRO') NOT NULL DEFAULT 'POR_VENTA',
  `estado` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compras`
--

CREATE TABLE `compras` (
  `id` bigint(20) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `subtotal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `impuesto_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `estado` enum('ABIERTA','CONTABILIZADA','ANULADA') NOT NULL DEFAULT 'ABIERTA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compras_detalle`
--

CREATE TABLE `compras_detalle` (
  `id` bigint(20) NOT NULL,
  `compra_id` bigint(20) NOT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `descripcion` varchar(220) NOT NULL,
  `cantidad` decimal(18,3) NOT NULL,
  `costo_unitario` decimal(18,2) NOT NULL,
  `impuesto_monto` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_linea` decimal(18,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cont_asientos`
--

CREATE TABLE `cont_asientos` (
  `id` bigint(20) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `periodo_id` int(11) DEFAULT NULL,
  `referencia` varchar(80) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `origen` varchar(40) DEFAULT NULL,
  `origen_id` bigint(20) DEFAULT NULL,
  `anulado` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cont_asientos_detalle`
--

CREATE TABLE `cont_asientos_detalle` (
  `id` bigint(20) NOT NULL,
  `asiento_id` bigint(20) NOT NULL,
  `cuenta_id` int(11) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `debito` decimal(18,2) NOT NULL DEFAULT 0.00,
  `credito` decimal(18,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cont_cuentas`
--

CREATE TABLE `cont_cuentas` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(140) NOT NULL,
  `tipo` enum('ACTIVO','PASIVO','PATRIMONIO','INGRESO','GASTO') NOT NULL,
  `permite_mov` tinyint(4) NOT NULL DEFAULT 1,
  `estado` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cont_periodos`
--

CREATE TABLE `cont_periodos` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `anio` int(11) NOT NULL,
  `mes` int(11) NOT NULL,
  `estado` enum('ABIERTO','CERRADO') NOT NULL DEFAULT 'ABIERTO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cxc_abonos`
--

CREATE TABLE `cxc_abonos` (
  `id` bigint(20) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `cxc_documento_id` bigint(20) NOT NULL,
  `venta_id` bigint(20) DEFAULT NULL,
  `cliente_id` int(11) NOT NULL,
  `fecha_abono` timestamp NOT NULL DEFAULT current_timestamp(),
  `monto_abono` decimal(18,2) NOT NULL,
  `metodo_pago` enum('EFECTIVO','TARJETA','TRANSFERENCIA','SINPE','CHEQUE','OTRO') NOT NULL DEFAULT 'EFECTIVO',
  `referencia_pago` varchar(100) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `anulado` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cxc_abonos`
--

INSERT INTO `cxc_abonos` (`id`, `empresa_id`, `cxc_documento_id`, `venta_id`, `cliente_id`, `fecha_abono`, `monto_abono`, `metodo_pago`, `referencia_pago`, `usuario_id`, `observaciones`, `anulado`, `created_at`) VALUES
(1, 1, 1, 48, 1, '2026-02-03 01:21:47', 10.00, 'EFECTIVO', 'pago abono', 4, 'pago', 0, '2026-02-03 01:21:47');

--
-- Disparadores `cxc_abonos`
--
DELIMITER $$
CREATE TRIGGER `trg_aud_cxc_abonos_ins` AFTER INSERT ON `cxc_abonos` FOR EACH ROW BEGIN
  INSERT INTO auditoria
  VALUES
  (NULL,NEW.empresa_id,NULL,'DB','INSERT','cxc_abonos',NEW.id,
   NULL,
   JSON_OBJECT('monto',NEW.monto_abono,'metodo',NEW.metodo_pago,'venta_id',NEW.venta_id),
   NULL,'TRIGGER',NOW());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cxc_documentos`
--

CREATE TABLE `cxc_documentos` (
  `id` bigint(20) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `venta_id` bigint(20) DEFAULT NULL,
  `fe_id` bigint(20) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `vencimiento` date DEFAULT NULL,
  `vence` date DEFAULT NULL,
  `total` decimal(18,2) NOT NULL,
  `saldo` decimal(18,2) NOT NULL,
  `estado` enum('PENDIENTE','PAGADO','VENCIDO') NOT NULL DEFAULT 'PENDIENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cxc_documentos`
--

INSERT INTO `cxc_documentos` (`id`, `empresa_id`, `cliente_id`, `venta_id`, `fe_id`, `fecha`, `vencimiento`, `vence`, `total`, `saldo`, `estado`) VALUES
(1, 1, 1, 48, NULL, '2026-02-03 01:13:42', '2026-03-05', '2026-03-05', 570.65, 560.65, 'PENDIENTE');

--
-- Disparadores `cxc_documentos`
--
DELIMITER $$
CREATE TRIGGER `trg_aud_cxc_upd` AFTER UPDATE ON `cxc_documentos` FOR EACH ROW BEGIN
  INSERT INTO auditoria
  VALUES
  (NULL,NEW.empresa_id,NULL,'DB','UPDATE','cxc_documentos',NEW.id,
   JSON_OBJECT('saldo',OLD.saldo,'estado',OLD.estado),
   JSON_OBJECT('saldo',NEW.saldo,'estado',NEW.estado),
   NULL,'TRIGGER',NOW());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cxc_pagos`
--

CREATE TABLE `cxc_pagos` (
  `id` bigint(20) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `metodo` varchar(40) DEFAULT NULL,
  `monto` decimal(18,2) NOT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `anulado` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cxc_pagos_aplicaciones`
--

CREATE TABLE `cxc_pagos_aplicaciones` (
  `id` bigint(20) NOT NULL,
  `pago_id` bigint(20) NOT NULL,
  `cxc_id` bigint(20) NOT NULL,
  `monto_aplicado` decimal(18,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cxp_documentos`
--

CREATE TABLE `cxp_documentos` (
  `id` bigint(20) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  `compra_id` bigint(20) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `vencimiento` date DEFAULT NULL,
  `vence` date DEFAULT NULL,
  `total` decimal(18,2) NOT NULL,
  `saldo` decimal(18,2) NOT NULL,
  `estado` enum('PENDIENTE','PAGADO','VENCIDO') NOT NULL DEFAULT 'PENDIENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cxp_pagos`
--

CREATE TABLE `cxp_pagos` (
  `id` bigint(20) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `metodo` varchar(40) DEFAULT NULL,
  `monto` decimal(18,2) NOT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `anulado` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresas`
--

CREATE TABLE `empresas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `cedula_juridica` varchar(20) NOT NULL,
  `nombre_comercial` varchar(150) DEFAULT NULL,
  `actividad_economica` varchar(10) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `logo_blob` longblob DEFAULT NULL,
  `logo_mime` varchar(60) DEFAULT NULL,
  `moneda_base` char(3) NOT NULL DEFAULT 'CRC',
  `estado` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `tipo_identificacion` enum('FISICA','JURIDICA','DIMEX','NITE') NOT NULL DEFAULT 'JURIDICA',
  `identificacion` varchar(20) NOT NULL,
  `email_fe` varchar(120) NOT NULL,
  `provincia` varchar(2) NOT NULL,
  `canton` varchar(2) NOT NULL,
  `distrito` varchar(2) NOT NULL,
  `barrio` varchar(2) NOT NULL,
  `otras_senas` varchar(255) NOT NULL,
  `atv_usuario` varchar(100) DEFAULT NULL,
  `atv_password` varbinary(255) DEFAULT NULL,
  `fe_ambiente` enum('SANDBOX','PRODUCCION') NOT NULL DEFAULT 'SANDBOX',
  `cert_ruta` varchar(255) DEFAULT NULL,
  `cert_password` varbinary(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empresas`
--

INSERT INTO `empresas` (`id`, `nombre`, `cedula_juridica`, `nombre_comercial`, `actividad_economica`, `email`, `telefono`, `direccion`, `logo_blob`, `logo_mime`, `moneda_base`, `estado`, `created_at`, `updated_at`, `tipo_identificacion`, `identificacion`, `email_fe`, `provincia`, `canton`, `distrito`, `barrio`, `otras_senas`, `atv_usuario`, `atv_password`, `fe_ambiente`, `cert_ruta`, `cert_password`) VALUES
(1, 'Empresa Demo FAC-IL-CR', '000000000', 'FAC-IL-CR DEMO', '0000', 'demo@facilcr.local', '0000-0000', NULL, NULL, NULL, 'CRC', 1, '2026-01-30 04:38:55', '2026-02-02 22:09:07', 'JURIDICA', '35011001100111', 'sistemas08il@outlook.com', 'Gu', 'a', 'a', 'a', 'a', NULL, NULL, 'SANDBOX', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fe_documentos`
--

CREATE TABLE `fe_documentos` (
  `id` bigint(20) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `venta_id` bigint(20) DEFAULT NULL,
  `tipo` enum('FACTURA','TIQUETE','NC','ND') NOT NULL DEFAULT 'FACTURA',
  `clave` varchar(50) DEFAULT NULL,
  `consecutivo` varchar(20) DEFAULT NULL,
  `estado` enum('PENDIENTE','ACEPTADA','RECHAZADA') NOT NULL DEFAULT 'PENDIENTE',
  `xml_enviado` longtext DEFAULT NULL,
  `xml_firmado` longtext DEFAULT NULL,
  `respuesta_hacienda` longtext DEFAULT NULL,
  `mensaje_hacienda` varchar(255) DEFAULT NULL,
  `fecha_emision` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `impuestos`
--

CREATE TABLE `impuestos` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `porcentaje` decimal(6,3) NOT NULL DEFAULT 0.000,
  `estado` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_existencias`
--

CREATE TABLE `inventario_existencias` (
  `id` bigint(20) NOT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `bodega_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `existencia` decimal(18,3) NOT NULL DEFAULT 0.000,
  `stock_minimo` decimal(18,3) NOT NULL DEFAULT 0.000,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inventario_existencias`
--

INSERT INTO `inventario_existencias` (`id`, `empresa_id`, `bodega_id`, `producto_id`, `existencia`, `stock_minimo`, `updated_at`) VALUES
(1, 1, 1, 1, 100.000, 2.000, NULL),
(2, 1, 2, 1, 2.000, 1.000, NULL),
(7, 1, 1, 2, 85.000, 5.000, '2026-02-03 01:13:42');

--
-- Disparadores `inventario_existencias`
--
DELIMITER $$
CREATE TRIGGER `trg_aud_inv_exist_upd` AFTER UPDATE ON `inventario_existencias` FOR EACH ROW BEGIN
  INSERT INTO auditoria
  VALUES
  (NULL,NEW.empresa_id,NULL,'DB','UPDATE','inventario_existencias',NEW.id,
   JSON_OBJECT('existencia',OLD.existencia,'stock_minimo',OLD.stock_minimo),
   JSON_OBJECT('existencia',NEW.existencia,'stock_minimo',NEW.stock_minimo),
   NULL,'TRIGGER',NOW());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_movimientos`
--

CREATE TABLE `inventario_movimientos` (
  `id` bigint(20) NOT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `bodega_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `tipo` enum('ENTRADA','SALIDA','AJUSTE','REVERSO') NOT NULL,
  `cantidad` decimal(18,3) NOT NULL,
  `costo_unitario` decimal(18,2) NOT NULL DEFAULT 0.00,
  `referencia_tipo` varchar(40) DEFAULT NULL,
  `referencia_id` bigint(20) DEFAULT NULL,
  `motivo` varchar(150) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `anulado` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inventario_movimientos`
--

INSERT INTO `inventario_movimientos` (`id`, `empresa_id`, `bodega_id`, `producto_id`, `tipo`, `cantidad`, `costo_unitario`, `referencia_tipo`, `referencia_id`, `motivo`, `usuario_id`, `anulado`, `created_at`) VALUES
(2, 1, 1, 2, 'ENTRADA', 100.000, 0.00, 'MANUAL', NULL, 'Compra', 1, 0, '2026-01-31 19:56:48'),
(3, 1, 1, 2, 'SALIDA', 2.000, 0.00, 'VENTA', 5, 'VENTA #5', 1, 0, '2026-01-31 20:15:22'),
(4, 1, 1, 2, 'SALIDA', 11.000, 0.00, 'VENTA', 6, 'VENTA #6', 1, 0, '2026-01-31 20:16:13'),
(5, 1, 1, 2, 'SALIDA', 1.000, 0.00, 'VENTA', 46, 'VENTA #46', 4, 0, '2026-02-03 00:50:30'),
(7, 1, 1, 2, 'SALIDA', 1.000, 0.00, 'VENTA', 48, 'VENTA #48', 4, 0, '2026-02-03 01:13:42');

--
-- Disparadores `inventario_movimientos`
--
DELIMITER $$
CREATE TRIGGER `trg_aud_inv_mov_ins` AFTER INSERT ON `inventario_movimientos` FOR EACH ROW BEGIN
  INSERT INTO auditoria
  VALUES
  (NULL,NEW.empresa_id,NULL,'DB','INSERT','inventario_movimientos',NEW.id,
   NULL,
   JSON_OBJECT('producto_id',NEW.producto_id,'tipo',NEW.tipo,'cantidad',NEW.cantidad,'motivo',NEW.motivo),
   NULL,'TRIGGER',NOW());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `rol_id` int(11) NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `puede_ver` tinyint(4) NOT NULL DEFAULT 1,
  `puede_crear` tinyint(4) NOT NULL DEFAULT 0,
  `puede_editar` tinyint(4) NOT NULL DEFAULT 0,
  `puede_eliminar` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `permisos`
--

INSERT INTO `permisos` (`id`, `empresa_id`, `rol_id`, `modulo`, `puede_ver`, `puede_crear`, `puede_editar`, `puede_eliminar`, `created_at`) VALUES
(1, 1, 1, 'dashboard', 1, 1, 1, 1, '2026-01-30 04:38:55'),
(2, 1, 1, 'reportes', 1, 1, 1, 1, '2026-01-30 04:38:55'),
(3, 1, 1, 'empresas', 1, 1, 1, 1, '2026-01-30 04:38:55'),
(4, 1, 1, 'sucursales', 1, 1, 1, 1, '2026-01-30 04:38:55'),
(5, 1, 1, 'usuarios', 1, 1, 1, 1, '2026-01-30 04:38:55'),
(6, 1, 1, 'roles', 1, 1, 1, 1, '2026-01-30 04:38:55'),
(7, 1, 1, 'productos', 1, 1, 1, 1, '2026-01-30 04:38:55'),
(8, 1, 1, 'inventario', 1, 1, 1, 1, '2026-01-30 04:38:55'),
(9, 1, 1, 'clientes', 1, 1, 1, 1, '2026-01-30 04:38:55'),
(10, 1, 1, 'ventas', 1, 1, 1, 1, '2026-01-30 04:38:55'),
(11, 1, 1, 'facturacion', 1, 1, 1, 1, '2026-01-30 04:38:55'),
(12, 1, 1, 'cxc', 1, 1, 1, 1, '2026-01-30 04:38:55'),
(13, 1, 1, 'proveedores', 1, 1, 1, 1, '2026-01-30 04:38:55'),
(14, 1, 1, 'cxp', 1, 1, 1, 1, '2026-01-30 04:38:55'),
(15, 1, 1, 'contabilidad', 1, 1, 1, 1, '2026-01-30 04:38:55'),
(16, 1, 1, 'comisiones', 1, 1, 1, 1, '2026-01-30 04:38:55'),
(17, 1, 1, 'auditoria', 1, 0, 0, 0, '2026-01-30 04:38:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `codigo_barras` varchar(60) DEFAULT NULL,
  `descripcion` varchar(220) NOT NULL,
  `categoria` varchar(80) DEFAULT NULL,
  `cabys` varchar(20) DEFAULT NULL,
  `unidad` varchar(10) DEFAULT NULL,
  `costo` decimal(18,2) NOT NULL DEFAULT 0.00,
  `margen` decimal(6,3) NOT NULL DEFAULT 0.000,
  `margen_minimo` decimal(6,3) NOT NULL DEFAULT 0.000,
  `precio` decimal(18,2) NOT NULL DEFAULT 0.00,
  `impuesto_id` int(11) DEFAULT NULL,
  `estado` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `stock_minimo` decimal(18,3) NOT NULL DEFAULT 0.000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `empresa_id`, `codigo`, `codigo_barras`, `descripcion`, `categoria`, `cabys`, `unidad`, `costo`, `margen`, `margen_minimo`, `precio`, `impuesto_id`, `estado`, `created_at`, `updated_at`, `stock_minimo`) VALUES
(1, 1, '1', NULL, 'Vaso1', NULL, NULL, NULL, 100.00, 100.000, 50.000, 200.00, NULL, 1, '2026-01-30 05:31:16', '2026-01-31 21:26:45', 100.000),
(2, 1, '3', '123123123', 'Aire', 'Electronico', NULL, 'Unid', 500.00, 10.000, 5.000, 505.00, NULL, 1, '2026-01-31 19:47:41', NULL, 0.000);

--
-- Disparadores `productos`
--
DELIMITER $$
CREATE TRIGGER `trg_aud_productos_del` AFTER DELETE ON `productos` FOR EACH ROW BEGIN
  INSERT INTO auditoria
  VALUES
  (NULL,OLD.empresa_id,NULL,'DB','DELETE','productos',OLD.id,
   JSON_OBJECT('codigo',OLD.codigo,'descripcion',OLD.descripcion,'precio',OLD.precio,'costo',OLD.costo),
   NULL,NULL,'TRIGGER',NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_aud_productos_ins` AFTER INSERT ON `productos` FOR EACH ROW BEGIN
  INSERT INTO auditoria
  (empresa_id,usuario_id,modulo,accion,tabla_nombre,registro_id,antes_json,despues_json,ip,user_agent)
  VALUES
  (NEW.empresa_id,NULL,'DB','INSERT','productos',NEW.id,NULL,
   JSON_OBJECT('codigo',NEW.codigo,'descripcion',NEW.descripcion,'precio',NEW.precio,'costo',NEW.costo),
   NULL,'TRIGGER');
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_aud_productos_upd` AFTER UPDATE ON `productos` FOR EACH ROW BEGIN
  INSERT INTO auditoria
  VALUES
  (NULL,NEW.empresa_id,NULL,'DB','UPDATE','productos',NEW.id,
   JSON_OBJECT('codigo',OLD.codigo,'descripcion',OLD.descripcion,'precio',OLD.precio,'costo',OLD.costo),
   JSON_OBJECT('codigo',NEW.codigo,'descripcion',NEW.descripcion,'precio',NEW.precio,'costo',NEW.costo),
   NULL,'TRIGGER',NOW());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `nombre` varchar(160) NOT NULL,
  `identificacion` varchar(25) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `estado` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id`, `empresa_id`, `nombre`, `identificacion`, `email`, `telefono`, `direccion`, `estado`, `created_at`) VALUES
(1, 1, 'Pruebas', '112490762', 'pruebas@gmail.com', '64520450', 'santa cruz', 1, '2026-02-01 01:18:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `estado` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `empresa_id`, `nombre`, `estado`, `created_at`) VALUES
(1, 1, 'Administrador', 1, '2026-01-30 04:38:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sucursales`
--

CREATE TABLE `sucursales` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `codigo` varchar(10) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `estado` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sucursales`
--

INSERT INTO `sucursales` (`id`, `empresa_id`, `nombre`, `codigo`, `direccion`, `telefono`, `estado`, `created_at`, `updated_at`) VALUES
(1, 1, 'Sucursal Principal', '001', NULL, NULL, 1, '2026-01-30 04:38:55', NULL),
(2, 1, 'Sucursal Principal - Santa Cruz', 'SCZ-001', 'Santa Cruz, Guanacaste', '8888-8888', 1, '2026-02-02 20:21:23', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_cambio`
--

CREATE TABLE `tipos_cambio` (
  `id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `moneda` varchar(3) NOT NULL DEFAULT 'USD',
  `compra` decimal(18,5) DEFAULT NULL,
  `venta` decimal(18,5) NOT NULL,
  `fuente` varchar(50) NOT NULL DEFAULT 'BCCR',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_cambio`
--

INSERT INTO `tipos_cambio` (`id`, `fecha`, `moneda`, `compra`, `venta`, `fuente`, `created_at`) VALUES
(1, '2026-01-31', 'USD', NULL, 1.00000, 'MANUAL', '2026-01-31 18:31:03'),
(2, '2026-02-01', 'USD', NULL, 1.00000, 'MANUAL', '2026-02-01 05:33:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `sucursal_id` int(11) DEFAULT NULL,
  `rol_id` int(11) DEFAULT NULL,
  `nombre` varchar(120) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `estado` tinyint(4) NOT NULL DEFAULT 1,
  `intentos_fallidos` int(11) NOT NULL DEFAULT 0,
  `ultimo_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `empresa_id`, `sucursal_id`, `rol_id`, `nombre`, `email`, `password`, `estado`, `intentos_fallidos`, `ultimo_login`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 'Admin', 'admin@facilcr.local', '$2y$10$rXu4mr/4d/fyGvKZiAfleeF5K6jaXmD9tNX.oMXSQSkBh4B.4M.V.', 1, 0, '2026-02-02 19:04:29', '2026-01-30 04:38:55', '2026-02-02 19:04:29'),
(3, 1, 1, 1, 'Admin', 'admin@sistemas.local', '$2y$10$0T6RZJ0wqjVdRj0VwM8bcuuQ2e8JQ0x9V7qO0M8W0bE0x9YQ0', 1, 2, NULL, '2026-01-30 04:53:02', '2026-01-30 04:56:57'),
(4, 1, 1, 1, 'Marco Jimenez', 'sistemas08il@outlook.com', '$2y$10$qzKbeq/.LQF35zNQnlzz0uKb5nRSDzxzihFgRnJZ7dbw4EgtKj5ee', 1, 0, '2026-02-02 22:07:16', '2026-02-02 20:01:56', '2026-02-02 22:07:16');

--
-- Disparadores `usuarios`
--
DELIMITER $$
CREATE TRIGGER `trg_aud_usuarios_upd` AFTER UPDATE ON `usuarios` FOR EACH ROW BEGIN
  INSERT INTO auditoria
  VALUES
  (NULL,NEW.empresa_id,NULL,'DB','UPDATE','usuarios',NEW.id,
   JSON_OBJECT('nombre',OLD.nombre,'email',OLD.email,'estado',OLD.estado),
   JSON_OBJECT('nombre',NEW.nombre,'email',NEW.email,'estado',NEW.estado),
   NULL,'TRIGGER',NOW());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` bigint(20) NOT NULL,
  `empresa_id` int(11) NOT NULL DEFAULT 1,
  `sucursal_id` int(11) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `tipo` enum('COTIZACION','PEDIDO','VENTA') NOT NULL DEFAULT 'VENTA',
  `moneda` varchar(3) NOT NULL DEFAULT 'CRC',
  `tipo_cambio` decimal(18,5) NOT NULL DEFAULT 1.00000,
  `condicion_venta` enum('CONTADO','CREDITO') NOT NULL DEFAULT 'CONTADO',
  `plazo_credito` int(11) DEFAULT NULL,
  `medio_pago` enum('EFECTIVO','TARJETA','TRANSFERENCIA','SINPE','OTRO') NOT NULL DEFAULT 'EFECTIVO',
  `referencia_pago` varchar(60) DEFAULT NULL,
  `fe_documento_id` bigint(20) DEFAULT NULL,
  `facturada_at` timestamp NULL DEFAULT NULL,
  `estado` enum('ABIERTA','FACTURADA','ANULADA') NOT NULL DEFAULT 'ABIERTA',
  `subtotal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `descuento` decimal(18,2) NOT NULL DEFAULT 0.00,
  `impuesto_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `observaciones` varchar(255) DEFAULT NULL,
  `anulado` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id`, `empresa_id`, `sucursal_id`, `cliente_id`, `usuario_id`, `tipo`, `moneda`, `tipo_cambio`, `condicion_venta`, `plazo_credito`, `medio_pago`, `referencia_pago`, `fe_documento_id`, `facturada_at`, `estado`, `subtotal`, `descuento`, `impuesto_total`, `total`, `observaciones`, `anulado`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 'VENTA', 'CRC', 1.00000, 'CONTADO', NULL, 'EFECTIVO', NULL, NULL, NULL, 'ABIERTA', 300.00, 0.00, 0.00, 300.00, 'prueba', 0, '2026-01-30 05:49:31', NULL),
(5, 1, 1, 1, 1, 'VENTA', 'CRC', 1.00000, 'CONTADO', 30, 'EFECTIVO', '', NULL, NULL, 'ABIERTA', 1010.00, 0.00, 131.30, 1141.30, '', 0, '2026-01-31 20:15:22', NULL),
(6, 1, 1, 1, 1, 'VENTA', 'CRC', 1.00000, 'CONTADO', 30, 'EFECTIVO', '', NULL, NULL, 'ABIERTA', 5555.00, 0.00, 722.15, 6277.15, '', 0, '2026-01-31 20:16:13', NULL),
(46, 1, 1, 1, 4, 'VENTA', 'CRC', 1.00000, 'CONTADO', 30, 'EFECTIVO', 'REC:1000.00|VUE:429.35', NULL, NULL, 'ABIERTA', 505.00, 0.00, 65.65, 570.65, '', 0, '2026-02-03 00:50:30', NULL),
(48, 1, 1, 1, 4, 'VENTA', 'CRC', 1.00000, 'CREDITO', 30, 'OTRO', 'CREDITO', NULL, NULL, 'ABIERTA', 505.00, 0.00, 65.65, 570.65, '', 0, '2026-02-03 01:13:42', NULL);

--
-- Disparadores `ventas`
--
DELIMITER $$
CREATE TRIGGER `trg_aud_ventas_del` AFTER DELETE ON `ventas` FOR EACH ROW BEGIN
  INSERT INTO auditoria
  VALUES
  (NULL,OLD.empresa_id,NULL,'DB','DELETE','ventas',OLD.id,
   JSON_OBJECT('total',OLD.total,'estado',OLD.estado),
   NULL,NULL,'TRIGGER',NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_aud_ventas_upd` AFTER UPDATE ON `ventas` FOR EACH ROW BEGIN
  INSERT INTO auditoria
  VALUES
  (NULL,NEW.empresa_id,NULL,'DB','UPDATE','ventas',NEW.id,
   JSON_OBJECT('estado',OLD.estado,'total',OLD.total),
   JSON_OBJECT('estado',NEW.estado,'total',NEW.total),
   NULL,'TRIGGER',NOW());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas_detalle`
--

CREATE TABLE `ventas_detalle` (
  `id` bigint(20) NOT NULL,
  `venta_id` bigint(20) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `descripcion` varchar(220) NOT NULL,
  `cabys` varchar(20) DEFAULT NULL,
  `cantidad` decimal(18,3) NOT NULL,
  `precio_unitario` decimal(18,2) NOT NULL,
  `descuento` decimal(18,2) NOT NULL DEFAULT 0.00,
  `impuesto_monto` decimal(18,2) NOT NULL DEFAULT 0.00,
  `impuesto_pct` decimal(6,2) NOT NULL DEFAULT 0.00,
  `total_linea` decimal(18,2) NOT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `sucursal_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas_detalle`
--

INSERT INTO `ventas_detalle` (`id`, `venta_id`, `producto_id`, `descripcion`, `cabys`, `cantidad`, `precio_unitario`, `descuento`, `impuesto_monto`, `impuesto_pct`, `total_linea`, `empresa_id`, `sucursal_id`) VALUES
(1, 1, 1, 'Vaso', NULL, 1.000, 300.00, 0.00, 0.00, 0.00, 200.00, 1, 1),
(2, 5, 2, 'Aire', NULL, 2.000, 505.00, 0.00, 131.30, 13.00, 1141.30, 1, 1),
(3, 6, 2, 'Aire', NULL, 11.000, 505.00, 0.00, 722.15, 13.00, 6277.15, 1, 1),
(39, 46, 2, 'Aire', NULL, 1.000, 505.00, 0.00, 65.65, 13.00, 570.65, 1, 0),
(41, 48, 2, 'Aire', NULL, 1.000, 505.00, 0.00, 65.65, 13.00, 570.65, 1, 0);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_aud_user` (`usuario_id`),
  ADD KEY `idx_aud_empresa_fecha` (`empresa_id`,`created_at`),
  ADD KEY `idx_aud_modulo` (`modulo`);

--
-- Indices de la tabla `bodegas`
--
ALTER TABLE `bodegas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bod_empresa` (`empresa_id`),
  ADD KEY `fk_bod_suc` (`sucursal_id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cli_empresa_estado` (`empresa_id`,`estado`);

--
-- Indices de la tabla `comisiones_calculadas`
--
ALTER TABLE `comisiones_calculadas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ccal_empresa` (`empresa_id`),
  ADD KEY `fk_ccal_user` (`usuario_id`),
  ADD KEY `fk_ccal_venta` (`venta_id`),
  ADD KEY `fk_ccal_pago` (`pago_id`);

--
-- Indices de la tabla `comisiones_reglas`
--
ALTER TABLE `comisiones_reglas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cr_empresa` (`empresa_id`),
  ADD KEY `fk_cr_user` (`usuario_id`),
  ADD KEY `fk_cr_prod` (`producto_id`);

--
-- Indices de la tabla `compras`
--
ALTER TABLE `compras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_com_empresa` (`empresa_id`),
  ADD KEY `fk_com_prov` (`proveedor_id`);

--
-- Indices de la tabla `compras_detalle`
--
ALTER TABLE `compras_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cd_compra` (`compra_id`),
  ADD KEY `fk_cd_prod` (`producto_id`);

--
-- Indices de la tabla `cont_asientos`
--
ALTER TABLE `cont_asientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ca_empresa` (`empresa_id`),
  ADD KEY `fk_ca_periodo` (`periodo_id`);

--
-- Indices de la tabla `cont_asientos_detalle`
--
ALTER TABLE `cont_asientos_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cad_asiento` (`asiento_id`),
  ADD KEY `fk_cad_cuenta` (`cuenta_id`);

--
-- Indices de la tabla `cont_cuentas`
--
ALTER TABLE `cont_cuentas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cuenta` (`empresa_id`,`codigo`);

--
-- Indices de la tabla `cont_periodos`
--
ALTER TABLE `cont_periodos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_periodo` (`empresa_id`,`anio`,`mes`);

--
-- Indices de la tabla `cxc_abonos`
--
ALTER TABLE `cxc_abonos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_empresa` (`empresa_id`),
  ADD KEY `idx_cxc` (`cxc_documento_id`),
  ADD KEY `idx_venta` (`venta_id`),
  ADD KEY `idx_cliente` (`cliente_id`),
  ADD KEY `fk_abono_usuario` (`usuario_id`);

--
-- Indices de la tabla `cxc_documentos`
--
ALTER TABLE `cxc_documentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cxc_cli` (`cliente_id`),
  ADD KEY `fk_cxc_venta` (`venta_id`),
  ADD KEY `fk_cxc_fe` (`fe_id`),
  ADD KEY `idx_cxc_empresa_estado_vence` (`empresa_id`,`estado`,`vence`),
  ADD KEY `idx_cxc_empresa_cliente_saldo` (`empresa_id`,`cliente_id`,`saldo`);

--
-- Indices de la tabla `cxc_pagos`
--
ALTER TABLE `cxc_pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cxcp_empresa` (`empresa_id`),
  ADD KEY `fk_cxcp_cli` (`cliente_id`),
  ADD KEY `fk_cxcp_user` (`usuario_id`);

--
-- Indices de la tabla `cxc_pagos_aplicaciones`
--
ALTER TABLE `cxc_pagos_aplicaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_apl_pago` (`pago_id`),
  ADD KEY `fk_apl_cxc` (`cxc_id`);

--
-- Indices de la tabla `cxp_documentos`
--
ALTER TABLE `cxp_documentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cxp_prov` (`proveedor_id`),
  ADD KEY `fk_cxp_compra` (`compra_id`),
  ADD KEY `idx_cxp_empresa_estado_vence` (`empresa_id`,`estado`,`vence`),
  ADD KEY `idx_cxp_empresa_proveedor_saldo` (`empresa_id`,`proveedor_id`,`saldo`);

--
-- Indices de la tabla `cxp_pagos`
--
ALTER TABLE `cxp_pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cxpp_empresa` (`empresa_id`),
  ADD KEY `fk_cxpp_prov` (`proveedor_id`),
  ADD KEY `fk_cxpp_user` (`usuario_id`);

--
-- Indices de la tabla `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `fe_documentos`
--
ALTER TABLE `fe_documentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_fe_clave` (`clave`),
  ADD KEY `fk_fe_empresa` (`empresa_id`),
  ADD KEY `fk_fe_venta` (`venta_id`);

--
-- Indices de la tabla `impuestos`
--
ALTER TABLE `impuestos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_imp_empresa` (`empresa_id`);

--
-- Indices de la tabla `inventario_existencias`
--
ALTER TABLE `inventario_existencias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_exist` (`empresa_id`,`bodega_id`,`producto_id`),
  ADD KEY `fk_exi_bodega` (`bodega_id`),
  ADD KEY `fk_exi_prod` (`producto_id`),
  ADD KEY `idx_inv_exist` (`empresa_id`,`bodega_id`,`producto_id`),
  ADD KEY `idx_existencias_stockmin` (`empresa_id`,`bodega_id`,`stock_minimo`);

--
-- Indices de la tabla `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mov_bodega` (`bodega_id`),
  ADD KEY `fk_mov_user` (`usuario_id`),
  ADD KEY `idx_mov_empresa_fecha` (`empresa_id`,`created_at`),
  ADD KEY `idx_mov_prod_fecha` (`producto_id`,`created_at`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_perm_rol_mod` (`rol_id`,`modulo`),
  ADD KEY `idx_perm_empresa` (`empresa_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_prod_empresa_codigo` (`empresa_id`,`codigo`),
  ADD KEY `fk_prod_impuesto` (`impuesto_id`),
  ADD KEY `idx_prod_empresa_estado` (`empresa_id`,`estado`),
  ADD KEY `idx_productos_busqueda` (`empresa_id`,`codigo`,`descripcion`),
  ADD KEY `idx_productos_categoria` (`empresa_id`,`categoria`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prov_empresa` (`empresa_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_roles_empresa` (`empresa_id`);

--
-- Indices de la tabla `sucursales`
--
ALTER TABLE `sucursales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_suc_empresa` (`empresa_id`);

--
-- Indices de la tabla `tipos_cambio`
--
ALTER TABLE `tipos_cambio`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tc` (`fecha`,`moneda`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_usuario_email` (`email`),
  ADD KEY `fk_user_empresa` (`empresa_id`),
  ADD KEY `fk_user_sucursal` (`sucursal_id`),
  ADD KEY `fk_user_rol` (`rol_id`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ven_suc` (`sucursal_id`),
  ADD KEY `fk_ven_cli` (`cliente_id`),
  ADD KEY `fk_ven_user` (`usuario_id`),
  ADD KEY `idx_ven_empresa_fecha` (`empresa_id`,`created_at`),
  ADD KEY `idx_ven_estado` (`estado`),
  ADD KEY `idx_ventas_fe_doc` (`fe_documento_id`),
  ADD KEY `idx_ventas_busq` (`empresa_id`,`created_at`),
  ADD KEY `idx_ventas_estado` (`empresa_id`,`estado`);

--
-- Indices de la tabla `ventas_detalle`
--
ALTER TABLE `ventas_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vd_prod` (`producto_id`),
  ADD KEY `idx_vd_venta` (`venta_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `bodegas`
--
ALTER TABLE `bodegas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `comisiones_calculadas`
--
ALTER TABLE `comisiones_calculadas`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `comisiones_reglas`
--
ALTER TABLE `comisiones_reglas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `compras`
--
ALTER TABLE `compras`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `compras_detalle`
--
ALTER TABLE `compras_detalle`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cont_asientos`
--
ALTER TABLE `cont_asientos`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cont_asientos_detalle`
--
ALTER TABLE `cont_asientos_detalle`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cont_cuentas`
--
ALTER TABLE `cont_cuentas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cont_periodos`
--
ALTER TABLE `cont_periodos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cxc_abonos`
--
ALTER TABLE `cxc_abonos`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `cxc_documentos`
--
ALTER TABLE `cxc_documentos`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `cxc_pagos`
--
ALTER TABLE `cxc_pagos`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cxc_pagos_aplicaciones`
--
ALTER TABLE `cxc_pagos_aplicaciones`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cxp_documentos`
--
ALTER TABLE `cxp_documentos`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cxp_pagos`
--
ALTER TABLE `cxp_pagos`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `fe_documentos`
--
ALTER TABLE `fe_documentos`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `impuestos`
--
ALTER TABLE `impuestos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_existencias`
--
ALTER TABLE `inventario_existencias`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `sucursales`
--
ALTER TABLE `sucursales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `tipos_cambio`
--
ALTER TABLE `tipos_cambio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT de la tabla `ventas_detalle`
--
ALTER TABLE `ventas_detalle`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD CONSTRAINT `fk_aud_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_aud_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `bodegas`
--
ALTER TABLE `bodegas`
  ADD CONSTRAINT `fk_bod_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_bod_suc` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`);

--
-- Filtros para la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `fk_cli_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Filtros para la tabla `comisiones_calculadas`
--
ALTER TABLE `comisiones_calculadas`
  ADD CONSTRAINT `fk_ccal_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_ccal_pago` FOREIGN KEY (`pago_id`) REFERENCES `cxc_pagos` (`id`),
  ADD CONSTRAINT `fk_ccal_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_ccal_venta` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`);

--
-- Filtros para la tabla `comisiones_reglas`
--
ALTER TABLE `comisiones_reglas`
  ADD CONSTRAINT `fk_cr_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_cr_prod` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`),
  ADD CONSTRAINT `fk_cr_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `compras`
--
ALTER TABLE `compras`
  ADD CONSTRAINT `fk_com_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_com_prov` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`);

--
-- Filtros para la tabla `compras_detalle`
--
ALTER TABLE `compras_detalle`
  ADD CONSTRAINT `fk_cd_compra` FOREIGN KEY (`compra_id`) REFERENCES `compras` (`id`),
  ADD CONSTRAINT `fk_cd_prod` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `cont_asientos`
--
ALTER TABLE `cont_asientos`
  ADD CONSTRAINT `fk_ca_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_ca_periodo` FOREIGN KEY (`periodo_id`) REFERENCES `cont_periodos` (`id`);

--
-- Filtros para la tabla `cont_asientos_detalle`
--
ALTER TABLE `cont_asientos_detalle`
  ADD CONSTRAINT `fk_cad_asiento` FOREIGN KEY (`asiento_id`) REFERENCES `cont_asientos` (`id`),
  ADD CONSTRAINT `fk_cad_cuenta` FOREIGN KEY (`cuenta_id`) REFERENCES `cont_cuentas` (`id`);

--
-- Filtros para la tabla `cont_cuentas`
--
ALTER TABLE `cont_cuentas`
  ADD CONSTRAINT `fk_cc_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Filtros para la tabla `cont_periodos`
--
ALTER TABLE `cont_periodos`
  ADD CONSTRAINT `fk_cp_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Filtros para la tabla `cxc_abonos`
--
ALTER TABLE `cxc_abonos`
  ADD CONSTRAINT `fk_abono_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `fk_abono_cxc_doc` FOREIGN KEY (`cxc_documento_id`) REFERENCES `cxc_documentos` (`id`),
  ADD CONSTRAINT `fk_abono_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_abono_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_abono_venta` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`);

--
-- Filtros para la tabla `cxc_documentos`
--
ALTER TABLE `cxc_documentos`
  ADD CONSTRAINT `fk_cxc_cli` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `fk_cxc_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_cxc_fe` FOREIGN KEY (`fe_id`) REFERENCES `fe_documentos` (`id`),
  ADD CONSTRAINT `fk_cxc_venta` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`);

--
-- Filtros para la tabla `cxc_pagos`
--
ALTER TABLE `cxc_pagos`
  ADD CONSTRAINT `fk_cxcp_cli` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `fk_cxcp_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_cxcp_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `cxc_pagos_aplicaciones`
--
ALTER TABLE `cxc_pagos_aplicaciones`
  ADD CONSTRAINT `fk_apl_cxc` FOREIGN KEY (`cxc_id`) REFERENCES `cxc_documentos` (`id`),
  ADD CONSTRAINT `fk_apl_pago` FOREIGN KEY (`pago_id`) REFERENCES `cxc_pagos` (`id`);

--
-- Filtros para la tabla `cxp_documentos`
--
ALTER TABLE `cxp_documentos`
  ADD CONSTRAINT `fk_cxp_compra` FOREIGN KEY (`compra_id`) REFERENCES `compras` (`id`),
  ADD CONSTRAINT `fk_cxp_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_cxp_prov` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`);

--
-- Filtros para la tabla `cxp_pagos`
--
ALTER TABLE `cxp_pagos`
  ADD CONSTRAINT `fk_cxpp_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_cxpp_prov` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`),
  ADD CONSTRAINT `fk_cxpp_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `fe_documentos`
--
ALTER TABLE `fe_documentos`
  ADD CONSTRAINT `fk_fe_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_fe_venta` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`);

--
-- Filtros para la tabla `impuestos`
--
ALTER TABLE `impuestos`
  ADD CONSTRAINT `fk_imp_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Filtros para la tabla `inventario_existencias`
--
ALTER TABLE `inventario_existencias`
  ADD CONSTRAINT `fk_exi_bodega` FOREIGN KEY (`bodega_id`) REFERENCES `bodegas` (`id`),
  ADD CONSTRAINT `fk_exi_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_exi_prod` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  ADD CONSTRAINT `fk_mov_bodega` FOREIGN KEY (`bodega_id`) REFERENCES `bodegas` (`id`),
  ADD CONSTRAINT `fk_mov_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_mov_prod` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`),
  ADD CONSTRAINT `fk_mov_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD CONSTRAINT `fk_perm_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_perm_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_prod_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_prod_impuesto` FOREIGN KEY (`impuesto_id`) REFERENCES `impuestos` (`id`);

--
-- Filtros para la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD CONSTRAINT `fk_prov_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Filtros para la tabla `roles`
--
ALTER TABLE `roles`
  ADD CONSTRAINT `fk_roles_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Filtros para la tabla `sucursales`
--
ALTER TABLE `sucursales`
  ADD CONSTRAINT `fk_suc_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_user_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_user_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `fk_user_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`);

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `fk_ven_cli` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `fk_ven_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_ven_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_ventas_fe_documento` FOREIGN KEY (`fe_documento_id`) REFERENCES `fe_documentos` (`id`);

--
-- Filtros para la tabla `ventas_detalle`
--
ALTER TABLE `ventas_detalle`
  ADD CONSTRAINT `fk_vd_prod` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`),
  ADD CONSTRAINT `fk_vd_venta` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
