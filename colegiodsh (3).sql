-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-05-2025 a las 20:14:04
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `colegiodsh`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `IntentarLogin` (IN `p_id_usuario` INT)   BEGIN
    DECLARE v_intentos INT DEFAULT 0;
    SELECT intentos INTO v_intentos FROM IntentosLogin WHERE id_usuario = p_id_usuario;

    SET v_intentos = v_intentos + 1;

    IF v_intentos >= 3 THEN
        UPDATE usuarios SET estado = 'inactivo' WHERE id_usuario = p_id_usuario;
        INSERT INTO usuariosbloqueados (id_usuario, fecha_bloqueo)
        VALUES (p_id_usuario, NOW());
        UPDATE IntentosLogin SET intentos = v_intentos, ultimo_intento = NOW()
        WHERE id_usuario = p_id_usuario;
    ELSE
        UPDATE IntentosLogin SET intentos = v_intentos, ultimo_intento = NOW()
        WHERE id_usuario = p_id_usuario;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `RegistrarAuditoria` (IN `p_usuario_afectado` INT, IN `p_operacion` ENUM('insert','update','delete'), IN `p_descripcion` TEXT, IN `p_usuario_realiza_operacion` INT)   BEGIN
    INSERT INTO Auditoria(usuario_afectado, operacion, descripcion, usuario_realiza_operacion)
    VALUES (p_usuario_afectado, p_operacion, p_descripcion, p_usuario_realiza_operacion);
END$$

--
-- Funciones
--
CREATE DEFINER=`root`@`localhost` FUNCTION `NombreCompletoUsuario` (`p_id_usuario` INT) RETURNS VARCHAR(255) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC BEGIN
    DECLARE nombreCompleto VARCHAR(255);
    SELECT CONCAT(nombre, ' ', apellido)
    INTO nombreCompleto
    FROM usuarios
    WHERE id_usuario = p_id_usuario;
    RETURN nombreCompleto;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `areasinstitucion`
--

CREATE TABLE `areasinstitucion` (
  `id_area` int(11) NOT NULL,
  `nombre_area` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `id_auditoria` int(11) NOT NULL,
  `usuario_afectado` int(11) NOT NULL,
  `operacion` enum('insert','update','delete') NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_operacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_realiza_operacion` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `auditoria`
--

INSERT INTO `auditoria` (`id_auditoria`, `usuario_afectado`, `operacion`, `descripcion`, `fecha_operacion`, `usuario_realiza_operacion`) VALUES
(1, 2, 'insert', 'El usuario (2, tipo docente) inició sesión correctamente', '2025-05-26 02:46:24', 2),
(2, 2, 'insert', 'Docente (2) creó el programa \'IA\'', '2025-05-26 14:05:39', 2),
(3, 1, 'insert', 'El usuario (1, tipo estudiante) inició sesión correctamente', '2025-05-26 14:30:35', 1),
(4, 3, 'insert', 'Se registró un nuevo usuario (ID 3, email delascar@admin.com, tipo docente)', '2025-05-26 14:34:52', NULL),
(5, 3, 'update', 'Actualización de usuario: Luis Delascar', '2025-05-26 14:35:14', NULL),
(6, 3, 'insert', 'El usuario (3, tipo admin) inició sesión correctamente', '2025-05-26 14:35:36', 3),
(7, 3, 'insert', 'El usuario (3, tipo admin) inició sesión correctamente', '2025-05-26 14:36:35', 3),
(8, 1, 'update', 'Actualización de usuario: Ronald David Sanchez Caicedo', '2025-05-26 14:38:58', NULL),
(9, 1, 'update', 'Administrador cambió el estado del usuario 1 a \'inactivo\'', '2025-05-26 14:38:58', 3),
(10, 1, 'update', 'Actualización de usuario: Ronald David Sanchez Caicedo', '2025-05-26 14:39:40', NULL),
(11, 1, 'update', 'Administrador cambió el estado del usuario 1 a \'activo\'', '2025-05-26 14:39:40', 3),
(12, 1, 'update', 'Actualización de usuario: Ronald David Sanchez Caicedo', '2025-05-26 14:42:47', NULL),
(13, 1, 'update', 'Administrador cambió el estado del usuario 1 a \'activo\'', '2025-05-26 14:42:47', 3),
(14, 3, 'insert', 'El usuario (3, tipo admin) inició sesión correctamente', '2025-05-26 14:45:00', 3),
(15, 3, 'insert', 'El usuario (3, tipo admin) inició sesión correctamente', '2025-05-26 14:55:03', 3),
(16, 3, 'insert', 'El usuario (3, tipo admin) inició sesión correctamente', '2025-05-26 14:57:09', 3),
(17, 1, 'update', 'Actualización de usuario: Ronald David Sanchez Caicedo', '2025-05-26 15:19:46', NULL),
(18, 1, 'update', 'Administrador cambió el estado del usuario 1 a \'inactivo\'', '2025-05-26 15:19:46', 3),
(19, 1, 'update', 'Actualización de usuario: Ronald David Sanchez Caicedo', '2025-05-26 15:20:37', NULL),
(20, 1, 'update', 'Administrador cambió el estado del usuario 1 a \'activo\'', '2025-05-26 15:20:37', 3),
(21, 1, 'insert', 'Intento fallido de inicio de sesión para el email: ronal@estudiante.com', '2025-05-26 15:25:54', NULL),
(22, 1, 'insert', 'Intento fallido de inicio de sesión para el email: ronal@estudiante.com', '2025-05-26 15:26:19', NULL),
(23, 1, 'insert', 'Intento fallido de inicio de sesión para el email: ronal@estudiante.com', '2025-05-26 15:26:23', NULL),
(24, 1, 'update', 'Actualización de usuario: Ronald David Sanchez Caicedo', '2025-05-26 15:26:29', NULL),
(25, 1, 'insert', 'Intento fallido de inicio de sesión para el email: ronal@estudiante.com', '2025-05-26 15:26:29', NULL),
(26, 1, 'update', 'Actualización de usuario: Ronald David Sanchez Caicedo', '2025-05-26 15:29:35', NULL),
(27, 1, 'insert', 'Intento fallido de inicio de sesión para el email: ronal@estudiante.com', '2025-05-26 15:29:35', NULL),
(28, 3, 'insert', 'El usuario (3, tipo admin) inició sesión correctamente', '2025-05-26 15:30:20', 3),
(29, 3, 'insert', 'El usuario (3, tipo admin) inició sesión correctamente', '2025-05-26 15:32:10', 3),
(30, 3, 'insert', 'El usuario (3, tipo admin) inició sesión correctamente', '2025-05-26 15:47:05', 3),
(31, 1, 'update', 'Actualización de usuario: Ronald David Sanchez Caicedo', '2025-05-26 15:51:16', NULL),
(32, 1, 'update', 'Administrador actualizó datos del usuario 1', '2025-05-26 15:51:16', 3),
(33, 3, 'insert', 'El usuario (3, tipo admin) inició sesión correctamente', '2025-05-26 18:47:30', 3),
(34, 3, 'insert', 'El usuario (3, tipo admin) inició sesión correctamente', '2025-05-27 16:18:27', 3),
(35, 2, 'update', 'Actualización de usuario: Yamal Lamine', '2025-05-27 16:19:07', NULL),
(36, 2, 'update', 'Administrador cambió el estado del usuario 2 a \'inactivo\'', '2025-05-27 16:19:07', 3),
(37, 2, 'update', 'Actualización de usuario: Yamal Lamine', '2025-05-27 16:19:09', NULL),
(38, 2, 'update', 'Administrador cambió el estado del usuario 2 a \'activo\'', '2025-05-27 16:19:09', 3),
(39, 3, 'insert', 'El usuario (3, tipo admin) inició sesión correctamente', '2025-05-27 17:00:44', 3),
(40, 3, 'insert', 'El usuario (3, tipo admin) inició sesión correctamente', '2025-05-27 17:02:31', 3),
(41, 2, 'insert', 'El usuario (2, tipo docente) inició sesión correctamente', '2025-05-27 17:06:13', 2),
(42, 3, 'insert', 'El usuario (3, tipo admin) inició sesión correctamente', '2025-05-27 20:25:39', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id_curso` int(11) NOT NULL,
  `nombre_curso` varchar(100) NOT NULL,
  `id_programa` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `docentecurso`
--

CREATE TABLE `docentecurso` (
  `id_docente_curso` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipostecnologicos`
--

CREATE TABLE `equipostecnologicos` (
  `id_equipo` int(11) NOT NULL,
  `nombre_equipo` varchar(100) NOT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `numero_serie` varchar(100) DEFAULT NULL,
  `id_area` int(11) NOT NULL,
  `estado` enum('activo','inactivo') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos`
--

CREATE TABLE `eventos` (
  `id_evento` int(11) NOT NULL,
  `titulo_evento` varchar(255) NOT NULL,
  `descripcion_evento` text DEFAULT NULL,
  `fecha_evento` date NOT NULL,
  `lugar_evento` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Disparadores `eventos`
--
DELIMITER $$
CREATE TRIGGER `AfterEventoDelete` AFTER DELETE ON `eventos` FOR EACH ROW BEGIN
    CALL RegistrarAuditoria(
        1, -- Debes pasar el id_usuario real que eliminó el evento
        'delete',
        CONCAT('Evento eliminado: ', OLD.titulo_evento),
        1
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `AfterEventoInsert` AFTER INSERT ON `eventos` FOR EACH ROW BEGIN
    CALL RegistrarAuditoria(
        1, -- Debes pasar el id_usuario real que creó el evento
        'insert',
        CONCAT('Evento creado: ', NEW.titulo_evento),
        1
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripciones`
--

CREATE TABLE `inscripciones` (
  `id_inscripcion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `fecha_inscripcion` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `intentoslogin`
--

CREATE TABLE `intentoslogin` (
  `id_intento` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha_intento` timestamp NOT NULL DEFAULT current_timestamp(),
  `intentos` int(11) DEFAULT 0,
  `ultimo_intento` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `intentoslogin`
--

INSERT INTO `intentoslogin` (`id_intento`, `id_usuario`, `fecha_intento`, `intentos`, `ultimo_intento`) VALUES
(1, 1, '2025-05-26 00:00:41', 0, '2025-05-26 15:26:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materias`
--

CREATE TABLE `materias` (
  `id_materia` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `id_docente` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notas`
--

CREATE TABLE `notas` (
  `id_nota` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `nota` decimal(5,2) DEFAULT NULL,
  `periodo` enum('primer','segundo') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personaladministrativo`
--

CREATE TABLE `personaladministrativo` (
  `id_personal` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `dependencia` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `programas`
--

CREATE TABLE `programas` (
  `id_programa` int(11) NOT NULL,
  `nombre_programa` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `nivel` enum('bajo','medio','alto') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `programas`
--

INSERT INTO `programas` (`id_programa`, `nombre_programa`, `descripcion`, `nivel`) VALUES
(1, 'IA', 'Inteligencia Artificial', 'alto');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recuperacioncontraseña`
--

CREATE TABLE `recuperacioncontraseña` (
  `id_token` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `token` varchar(100) NOT NULL,
  `fecha_expiracion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `recuperacioncontraseña`
--
DELIMITER $$
CREATE TRIGGER `set_expiration_time` BEFORE INSERT ON `recuperacioncontraseña` FOR EACH ROW BEGIN
    SET NEW.fecha_expiracion = CURRENT_TIMESTAMP + INTERVAL 1 HOUR;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `documento_identidad` varchar(50) NOT NULL,
  `tipo_usuario` enum('estudiante','docente','admin') NOT NULL,
  `email` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `apellido`, `documento_identidad`, `tipo_usuario`, `email`, `telefono`, `direccion`, `estado`, `fecha_registro`, `password`) VALUES
(1, 'Ronald David', 'Sanchez Caicedo', '1077424709', 'estudiante', 'ronal@estudiante.com', '3150537662', NULL, 'activo', '2025-05-25 23:28:00', '$2y$10$j1jfBWg.Z.EG0MKDRlCu6.2cCCd4d9x4nkkctdxKlhdStwWvOD3Ha'),
(2, 'Yamal', 'Lamine', '12349334', 'docente', 'yamal@docente.com', '31312231', NULL, 'activo', '2025-05-25 23:44:51', '$2y$10$sxWpTqvv2.GFzEqh4FndNOwcjyq9BBDul9cvs/qwwszBa.Q5lXZ16'),
(3, 'Luis', 'Delascar', '1231421', 'admin', 'delascar@admin.com', '31322424', NULL, 'activo', '2025-05-26 14:34:52', '$2y$10$Gqwf0iFqe/jcqEuOU7Y7GuvsPUqi9Mf4lK6dfv.ab94gbD7oniU7u');

--
-- Disparadores `usuarios`
--
DELIMITER $$
CREATE TRIGGER `AfterUsuariosUpdate` AFTER UPDATE ON `usuarios` FOR EACH ROW BEGIN
    CALL RegistrarAuditoria(
        OLD.id_usuario,
        'update',
        CONCAT('Actualización de usuario: ', OLD.nombre, ' ', OLD.apellido),
        NULL
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuariosbloqueados`
--

CREATE TABLE `usuariosbloqueados` (
  `id_bloqueo` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `razon_bloqueo` text DEFAULT NULL,
  `fecha_bloqueo` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_desbloqueo` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `usuariosbloqueados`
--

INSERT INTO `usuariosbloqueados` (`id_bloqueo`, `id_usuario`, `razon_bloqueo`, `fecha_bloqueo`, `fecha_desbloqueo`) VALUES
(1, 1, NULL, '2025-05-26 15:26:29', NULL);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vistaadmins`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vistaadmins` (
`id_usuario` int(11)
,`nombre` varchar(100)
,`apellido` varchar(100)
,`email` varchar(255)
,`estado` enum('activo','inactivo')
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vistaauditoria`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vistaauditoria` (
`id_auditoria` int(11)
,`usuario_afectado` varchar(255)
,`operacion` enum('insert','update','delete')
,`descripcion` text
,`fecha_operacion` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vistaauditoriacompleta`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vistaauditoriacompleta` (
`id_auditoria` int(11)
,`usuario_afectado` varchar(201)
,`operacion` enum('insert','update','delete')
,`descripcion` text
,`fecha_operacion` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vistadocentes`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vistadocentes` (
`id_usuario` int(11)
,`nombre` varchar(100)
,`apellido` varchar(100)
,`email` varchar(255)
,`estado` enum('activo','inactivo')
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vistaequiposarea`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vistaequiposarea` (
`nombre_area` varchar(100)
,`nombre_equipo` varchar(100)
,`marca` varchar(50)
,`modelo` varchar(50)
,`estado` enum('activo','inactivo')
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vistaequiposporarea`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vistaequiposporarea` (
`nombre_area` varchar(100)
,`nombre_equipo` varchar(100)
,`marca` varchar(50)
,`modelo` varchar(50)
,`estado` enum('activo','inactivo')
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vistaestudiantes`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vistaestudiantes` (
`id_usuario` int(11)
,`nombre` varchar(100)
,`apellido` varchar(100)
,`email` varchar(255)
,`estado` enum('activo','inactivo')
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vistaestudiantesactivos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vistaestudiantesactivos` (
`id_usuario` int(11)
,`nombre` varchar(100)
,`apellido` varchar(100)
,`email` varchar(255)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vistaeventosproximos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vistaeventosproximos` (
`id_evento` int(11)
,`titulo_evento` varchar(255)
,`descripcion_evento` text
,`fecha_evento` date
,`lugar_evento` varchar(255)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vistainscripciones`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vistainscripciones` (
`id_inscripcion` int(11)
,`nombre` varchar(100)
,`apellido` varchar(100)
,`nombre_curso` varchar(100)
,`fecha_inscripcion` date
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vistainscripcionesdetalladas`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vistainscripcionesdetalladas` (
`id_inscripcion` int(11)
,`nombre_estudiante` varchar(100)
,`apellido_estudiante` varchar(100)
,`nombre_curso` varchar(100)
,`nombre_programa` varchar(100)
,`fecha_inscripcion` date
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vistarendimiento`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vistarendimiento` (
`id_nota` int(11)
,`nombre_estudiante` varchar(100)
,`apellido_estudiante` varchar(100)
,`nombre_curso` varchar(100)
,`nota` decimal(5,2)
,`periodo` enum('primer','segundo')
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vistausuariosactivos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vistausuariosactivos` (
`id_usuario` int(11)
,`nombre` varchar(100)
,`apellido` varchar(100)
,`email` varchar(255)
,`tipo_usuario` enum('estudiante','docente','admin')
,`estado` enum('activo','inactivo')
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vistausuariosbloqueados`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vistausuariosbloqueados` (
`id_bloqueo` int(11)
,`nombre` varchar(100)
,`apellido` varchar(100)
,`razon_bloqueo` text
,`fecha_bloqueo` timestamp
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vistaadmins`
--
DROP TABLE IF EXISTS `vistaadmins`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vistaadmins`  AS SELECT `usuarios`.`id_usuario` AS `id_usuario`, `usuarios`.`nombre` AS `nombre`, `usuarios`.`apellido` AS `apellido`, `usuarios`.`email` AS `email`, `usuarios`.`estado` AS `estado` FROM `usuarios` WHERE `usuarios`.`tipo_usuario` = 'admin' ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vistaauditoria`
--
DROP TABLE IF EXISTS `vistaauditoria`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vistaauditoria`  AS SELECT `a`.`id_auditoria` AS `id_auditoria`, `NombreCompletoUsuario`(`a`.`usuario_afectado`) AS `usuario_afectado`, `a`.`operacion` AS `operacion`, `a`.`descripcion` AS `descripcion`, `a`.`fecha_operacion` AS `fecha_operacion` FROM `auditoria` AS `a` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vistaauditoriacompleta`
--
DROP TABLE IF EXISTS `vistaauditoriacompleta`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vistaauditoriacompleta`  AS SELECT `a`.`id_auditoria` AS `id_auditoria`, concat(`u`.`nombre`,' ',`u`.`apellido`) AS `usuario_afectado`, `a`.`operacion` AS `operacion`, `a`.`descripcion` AS `descripcion`, `a`.`fecha_operacion` AS `fecha_operacion` FROM (`auditoria` `a` join `usuarios` `u` on(`a`.`usuario_afectado` = `u`.`id_usuario`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vistadocentes`
--
DROP TABLE IF EXISTS `vistadocentes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vistadocentes`  AS SELECT `usuarios`.`id_usuario` AS `id_usuario`, `usuarios`.`nombre` AS `nombre`, `usuarios`.`apellido` AS `apellido`, `usuarios`.`email` AS `email`, `usuarios`.`estado` AS `estado` FROM `usuarios` WHERE `usuarios`.`tipo_usuario` = 'docente' ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vistaequiposarea`
--
DROP TABLE IF EXISTS `vistaequiposarea`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vistaequiposarea`  AS SELECT `a`.`nombre_area` AS `nombre_area`, `e`.`nombre_equipo` AS `nombre_equipo`, `e`.`marca` AS `marca`, `e`.`modelo` AS `modelo`, `e`.`estado` AS `estado` FROM (`equipostecnologicos` `e` join `areasinstitucion` `a` on(`e`.`id_area` = `a`.`id_area`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vistaequiposporarea`
--
DROP TABLE IF EXISTS `vistaequiposporarea`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vistaequiposporarea`  AS SELECT `a`.`nombre_area` AS `nombre_area`, `e`.`nombre_equipo` AS `nombre_equipo`, `e`.`marca` AS `marca`, `e`.`modelo` AS `modelo`, `e`.`estado` AS `estado` FROM (`equipostecnologicos` `e` join `areasinstitucion` `a` on(`e`.`id_area` = `a`.`id_area`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vistaestudiantes`
--
DROP TABLE IF EXISTS `vistaestudiantes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vistaestudiantes`  AS SELECT `usuarios`.`id_usuario` AS `id_usuario`, `usuarios`.`nombre` AS `nombre`, `usuarios`.`apellido` AS `apellido`, `usuarios`.`email` AS `email`, `usuarios`.`estado` AS `estado` FROM `usuarios` WHERE `usuarios`.`tipo_usuario` = 'estudiante' ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vistaestudiantesactivos`
--
DROP TABLE IF EXISTS `vistaestudiantesactivos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vistaestudiantesactivos`  AS SELECT `usuarios`.`id_usuario` AS `id_usuario`, `usuarios`.`nombre` AS `nombre`, `usuarios`.`apellido` AS `apellido`, `usuarios`.`email` AS `email` FROM `usuarios` WHERE `usuarios`.`tipo_usuario` = 'estudiante' AND `usuarios`.`estado` = 'activo' ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vistaeventosproximos`
--
DROP TABLE IF EXISTS `vistaeventosproximos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vistaeventosproximos`  AS SELECT `eventos`.`id_evento` AS `id_evento`, `eventos`.`titulo_evento` AS `titulo_evento`, `eventos`.`descripcion_evento` AS `descripcion_evento`, `eventos`.`fecha_evento` AS `fecha_evento`, `eventos`.`lugar_evento` AS `lugar_evento` FROM `eventos` WHERE `eventos`.`fecha_evento` >= curdate() ORDER BY `eventos`.`fecha_evento` ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vistainscripciones`
--
DROP TABLE IF EXISTS `vistainscripciones`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vistainscripciones`  AS SELECT `i`.`id_inscripcion` AS `id_inscripcion`, `u`.`nombre` AS `nombre`, `u`.`apellido` AS `apellido`, `c`.`nombre_curso` AS `nombre_curso`, `i`.`fecha_inscripcion` AS `fecha_inscripcion` FROM ((`inscripciones` `i` join `usuarios` `u` on(`i`.`id_usuario` = `u`.`id_usuario`)) join `cursos` `c` on(`i`.`id_curso` = `c`.`id_curso`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vistainscripcionesdetalladas`
--
DROP TABLE IF EXISTS `vistainscripcionesdetalladas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vistainscripcionesdetalladas`  AS SELECT `i`.`id_inscripcion` AS `id_inscripcion`, `u`.`nombre` AS `nombre_estudiante`, `u`.`apellido` AS `apellido_estudiante`, `c`.`nombre_curso` AS `nombre_curso`, `p`.`nombre_programa` AS `nombre_programa`, `i`.`fecha_inscripcion` AS `fecha_inscripcion` FROM (((`inscripciones` `i` join `usuarios` `u` on(`i`.`id_usuario` = `u`.`id_usuario`)) join `cursos` `c` on(`i`.`id_curso` = `c`.`id_curso`)) join `programas` `p` on(`c`.`id_programa` = `p`.`id_programa`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vistarendimiento`
--
DROP TABLE IF EXISTS `vistarendimiento`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vistarendimiento`  AS SELECT `n`.`id_nota` AS `id_nota`, `u`.`nombre` AS `nombre_estudiante`, `u`.`apellido` AS `apellido_estudiante`, `c`.`nombre_curso` AS `nombre_curso`, `n`.`nota` AS `nota`, `n`.`periodo` AS `periodo` FROM ((`notas` `n` join `usuarios` `u` on(`n`.`id_usuario` = `u`.`id_usuario`)) join `cursos` `c` on(`n`.`id_curso` = `c`.`id_curso`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vistausuariosactivos`
--
DROP TABLE IF EXISTS `vistausuariosactivos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vistausuariosactivos`  AS SELECT `usuarios`.`id_usuario` AS `id_usuario`, `usuarios`.`nombre` AS `nombre`, `usuarios`.`apellido` AS `apellido`, `usuarios`.`email` AS `email`, `usuarios`.`tipo_usuario` AS `tipo_usuario`, `usuarios`.`estado` AS `estado` FROM `usuarios` WHERE `usuarios`.`estado` = 'activo' ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vistausuariosbloqueados`
--
DROP TABLE IF EXISTS `vistausuariosbloqueados`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vistausuariosbloqueados`  AS SELECT `ub`.`id_bloqueo` AS `id_bloqueo`, `u`.`nombre` AS `nombre`, `u`.`apellido` AS `apellido`, `ub`.`razon_bloqueo` AS `razon_bloqueo`, `ub`.`fecha_bloqueo` AS `fecha_bloqueo` FROM (`usuariosbloqueados` `ub` join `usuarios` `u` on(`ub`.`id_usuario` = `u`.`id_usuario`)) ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `areasinstitucion`
--
ALTER TABLE `areasinstitucion`
  ADD PRIMARY KEY (`id_area`);

--
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id_auditoria`),
  ADD KEY `usuario_afectado` (`usuario_afectado`),
  ADD KEY `usuario_realiza_operacion` (`usuario_realiza_operacion`);

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id_curso`),
  ADD KEY `id_programa` (`id_programa`);

--
-- Indices de la tabla `docentecurso`
--
ALTER TABLE `docentecurso`
  ADD PRIMARY KEY (`id_docente_curso`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_curso` (`id_curso`);

--
-- Indices de la tabla `equipostecnologicos`
--
ALTER TABLE `equipostecnologicos`
  ADD PRIMARY KEY (`id_equipo`),
  ADD KEY `id_area` (`id_area`),
  ADD KEY `idx_estado_equipo` (`estado`);

--
-- Indices de la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id_evento`),
  ADD KEY `idx_fecha_evento` (`fecha_evento`);

--
-- Indices de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD PRIMARY KEY (`id_inscripcion`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_curso` (`id_curso`),
  ADD KEY `idx_fecha_inscripcion` (`fecha_inscripcion`);

--
-- Indices de la tabla `intentoslogin`
--
ALTER TABLE `intentoslogin`
  ADD PRIMARY KEY (`id_intento`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `materias`
--
ALTER TABLE `materias`
  ADD PRIMARY KEY (`id_materia`),
  ADD KEY `id_curso` (`id_curso`),
  ADD KEY `id_docente` (`id_docente`);

--
-- Indices de la tabla `notas`
--
ALTER TABLE `notas`
  ADD PRIMARY KEY (`id_nota`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_curso` (`id_curso`);

--
-- Indices de la tabla `personaladministrativo`
--
ALTER TABLE `personaladministrativo`
  ADD PRIMARY KEY (`id_personal`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `programas`
--
ALTER TABLE `programas`
  ADD PRIMARY KEY (`id_programa`);

--
-- Indices de la tabla `recuperacioncontraseña`
--
ALTER TABLE `recuperacioncontraseña`
  ADD PRIMARY KEY (`id_token`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `uc_email` (`email`),
  ADD KEY `idx_estado_usuario` (`estado`);

--
-- Indices de la tabla `usuariosbloqueados`
--
ALTER TABLE `usuariosbloqueados`
  ADD PRIMARY KEY (`id_bloqueo`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `areasinstitucion`
--
ALTER TABLE `areasinstitucion`
  MODIFY `id_area` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id_auditoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id_curso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `docentecurso`
--
ALTER TABLE `docentecurso`
  MODIFY `id_docente_curso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `equipostecnologicos`
--
ALTER TABLE `equipostecnologicos`
  MODIFY `id_equipo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id_evento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  MODIFY `id_inscripcion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `intentoslogin`
--
ALTER TABLE `intentoslogin`
  MODIFY `id_intento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `materias`
--
ALTER TABLE `materias`
  MODIFY `id_materia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notas`
--
ALTER TABLE `notas`
  MODIFY `id_nota` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `personaladministrativo`
--
ALTER TABLE `personaladministrativo`
  MODIFY `id_personal` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `programas`
--
ALTER TABLE `programas`
  MODIFY `id_programa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `recuperacioncontraseña`
--
ALTER TABLE `recuperacioncontraseña`
  MODIFY `id_token` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuariosbloqueados`
--
ALTER TABLE `usuariosbloqueados`
  MODIFY `id_bloqueo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD CONSTRAINT `auditoria_ibfk_1` FOREIGN KEY (`usuario_afectado`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `auditoria_ibfk_2` FOREIGN KEY (`usuario_realiza_operacion`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD CONSTRAINT `cursos_ibfk_1` FOREIGN KEY (`id_programa`) REFERENCES `programas` (`id_programa`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `docentecurso`
--
ALTER TABLE `docentecurso`
  ADD CONSTRAINT `docentecurso_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `docentecurso_ibfk_2` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `equipostecnologicos`
--
ALTER TABLE `equipostecnologicos`
  ADD CONSTRAINT `equipostecnologicos_ibfk_1` FOREIGN KEY (`id_area`) REFERENCES `areasinstitucion` (`id_area`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD CONSTRAINT `inscripciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `inscripciones_ibfk_2` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `intentoslogin`
--
ALTER TABLE `intentoslogin`
  ADD CONSTRAINT `intentoslogin_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `materias`
--
ALTER TABLE `materias`
  ADD CONSTRAINT `materias_ibfk_1` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`),
  ADD CONSTRAINT `materias_ibfk_2` FOREIGN KEY (`id_docente`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `notas`
--
ALTER TABLE `notas`
  ADD CONSTRAINT `notas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notas_ibfk_2` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `personaladministrativo`
--
ALTER TABLE `personaladministrativo`
  ADD CONSTRAINT `personaladministrativo_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `recuperacioncontraseña`
--
ALTER TABLE `recuperacioncontraseña`
  ADD CONSTRAINT `recuperacioncontraseña_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `usuariosbloqueados`
--
ALTER TABLE `usuariosbloqueados`
  ADD CONSTRAINT `usuariosbloqueados_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
