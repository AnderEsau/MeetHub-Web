-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 20-11-2026 a las 18:38:22
-- Versión del servidor: 11.8.9-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u845581198_MeetHub`
-- use u845581198_MeetHub

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblalumnos`
--

CREATE TABLE `tblalumnos` (
  `id_alumno` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido_paterno` varchar(100) NOT NULL,
  `apellido_materno` varchar(100) NOT NULL DEFAULT '',
  `correo_electronico` varchar(100) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `psw` varchar(255) NOT NULL,
  `pregunta_secreta` varchar(150) NOT NULL,
  `respuesta_secreta` varchar(150) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tblalumnos`
--

INSERT INTO `tblalumnos` (`id_alumno`, `nombre`, `apellido_paterno`, `apellido_materno`, `correo_electronico`, `telefono`, `psw`, `pregunta_secreta`, `respuesta_secreta`, `activo`) VALUES
(306090, 'Pauleet', 'Castellanos', 'Naranjo', 'pauleetcastellanos@gmail.com', '7711567219', 'calabaza12*', '¿Cual es tu color favorito?', 'rosa', 1),
(20241242, 'Sofía', 'Ramírez', 'Herrera', 'sofia.ramirez@uthh.edu.mx', '7898889900', '12345678', '¿Nombre de tu primera mascota?', 'Max', 1),
(20245581, 'Luis', 'Hernández', 'Díaz', 'luis.hernandez@uthh.edu.mx', '7892223344', '12345678', '¿Ciudad donde naciste?', 'Huejutla', 1),
(20250981, 'Ander Esau', 'Hernández', 'Jiménez', '20250981@uthh.edu.mx', '7891102233', '12345678', '¿Nombre de tu primera mascota?', 'Toby', 1),
(20251029, 'Leslie', 'Pelcastre', '', '20251029@uthh.edu.mx', '7711121655', 'leslie2007', '¿Nombre de tu primera mascota?', 'popeye', 1),
(20251041, 'Lary Baruc', 'Nolasco', 'Linarte', '20251041@uthh.edu.mx', '7711234567', 'nueva1234', '¿Nombre de tu primera mascota?', 'Firulais', 1),
(20251053, 'Samantha', 'Castellanos', 'Ruiz', '20251053@uthh.edu.mx', '7891145566', '12345678', '¿Color favorito?', 'Azul', 1),
(20251085, 'Leonel', 'Hernández', 'Gómez', '20251085@uthh.edu.mx', '7891178899', '12345678', '¿Nombre de tu escuela primaria?', 'Benito Juarez', 1),
(20259999, 'Prueba', 'Postman', 'Uno', '20259999@uthh.edu.mx', '7890000000', 'nueva1234', '¿Color favorito?', 'Rojo', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblbibliotecario`
--

CREATE TABLE `tblbibliotecario` (
  `id_bibliotecario` int(11) NOT NULL,
  `matricula` int(11) NOT NULL,
  `usuarios` varchar(50) NOT NULL,
  `psw` varchar(255) NOT NULL,
  `tipo` varchar(30) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tblbibliotecario`
--

INSERT INTO `tblbibliotecario` (`id_bibliotecario`, `matricula`, `usuarios`, `psw`, `tipo`, `activo`) VALUES
(1, 243, 'juan_biblio', '12345678', 'Bibliotecario', 1),
(2, 244, 'maria_admin', '12345678', 'Administrador', 1),
(3, 245, 'Ricardo', '12345678', 'Bibliotecario', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblcontacto`
--

CREATE TABLE `tblcontacto` (
  `id_contacto` int(11) NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `correo_electronico` varchar(100) NOT NULL,
  `asunto` varchar(150) NOT NULL,
  `mensaje` text NOT NULL,
  `fecha_envio` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` enum('Pendiente','Atendido') NOT NULL DEFAULT 'Pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblcontenido_publico`
--

CREATE TABLE `tblcontenido_publico` (
  `id_contenido` int(11) NOT NULL,
  `modulo` enum('FAQ','Politicas','Datos') NOT NULL,
  `apartado` varchar(100) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `contenido` text NOT NULL,
  `estado` enum('Activo','Baja') NOT NULL DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tblcontenido_publico`
--

INSERT INTO `tblcontenido_publico` (`id_contenido`, `modulo`, `apartado`, `titulo`, `contenido`, `estado`) VALUES
(1, 'FAQ', 'Anticipación mínima', '¿Con cuántos días de anticipación debo solicitar una sala?', 'Todas las solicitudes deben registrarse con al menos 3 días hábiles de anticipación (lunes a viernes) para permitir la validación del encargado.', 'Activo'),
(2, 'FAQ', 'Requisitos externos', '¿Cuáles son los requisitos para registrarme si soy Usuario Externo, Alumno o Docente?', 'Al crear tu cuenta debes elegir tu Tipo de Usuario: si eres Alumno o Docente ingresas tu Matrícula institucional; si eres Usuario Externo ingresas tu RFC, correo y teléfono de contacto.', 'Activo'),
(3, 'Politicas', 'Políticas de préstamo', 'Regla de aforo mínimo y credencial vigente', 'Cumplir con el aforo mínimo y máximo de la sala, presentar credencial vigente UTHH o identificación oficial y dar uso exclusivamente académico.', 'Activo'),
(4, 'Politicas', 'Políticas de cancelación', 'Tolerancia de 15 minutos y aviso previo', 'Cancelar desde Mis solicitudes con al menos 24 horas de anticipación. Tolerancia máxima de 15 minutos después de la hora de inicio.', 'Activo'),
(5, 'Politicas', 'Políticas de devolución', 'Entrega limpia y sin alimentos', 'Entregar la sala puntualmente, devolver controles y cables HDMI en buen estado y mantener el espacio libre de alimentos o bebidas.', 'Activo'),
(6, 'Datos', 'Dirección física', 'Edificio de Biblioteca Central UTHH', 'Carretera Huejutla - Chalahuiyapa S/N, Col. Tepoxteco, C.P. 43000, Huejutla de Reyes, Hidalgo, México.', 'Activo'),
(7, 'Datos', 'Horario y contacto', 'Atención Biblioteca', 'Lunes a Viernes de 08:00 a 16:30 hrs. Teléfono: (789) 896 2088 Ext. Biblioteca | Correo: biblioteca@uthh.edu.mx', 'Activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblequipamiento`
--

CREATE TABLE `tblequipamiento` (
  `id_equipamiento` int(11) NOT NULL,
  `id_sala` int(11) NOT NULL,
  `nombre_equipamiento` varchar(100) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `tipo_equipamiento` varchar(50) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `estado` enum('Activo','Baja') NOT NULL DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tblequipamiento`
--

INSERT INTO `tblequipamiento` (`id_equipamiento`, `id_sala`, `nombre_equipamiento`, `descripcion`, `tipo_equipamiento`, `cantidad`, `estado`) VALUES
(1, 1, 'Mesas de Lectura', 'Mesas amplias de madera con contactos eléctricos integrados', 'Mobiliario', 14, 'Activo'),
(2, 2, 'Computadoras Core i7', 'Equipos de escritorio con 16GB RAM y paquetería institucional', 'Cómputo', 30, 'Activo'),
(3, 3, 'Computadoras Core i5', 'Equipos de cómputo conectados a red cableada', 'Cómputo', 25, 'Activo'),
(4, 4, 'Proyector Epson HDMI', 'Proyector de alta definición con cable HDMI y control remoto', 'Equipamiento', 1, 'Activo'),
(5, 4, 'Pantalla de Proyección', 'Pantalla retráctil panorámica de 120 pulgadas', 'Equipamiento', 1, 'Activo'),
(6, 4, 'Sillas Ergonómicas', 'Sillas acolchadas con paleta abatible', 'Mobiliario', 40, 'Activo'),
(7, 5, 'Pantalla Smart TV 75\"', 'Pantalla interactiva para videoconferencias y presentaciones', 'Equipamiento', 1, 'Activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblexterno`
--

CREATE TABLE `tblexterno` (
  `id_externo` int(11) NOT NULL,
  `rfc` varchar(13) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido_paterno` varchar(100) NOT NULL,
  `apellido_materno` varchar(100) NOT NULL DEFAULT '',
  `correo_electronico` varchar(100) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `institucion` varchar(100) NOT NULL,
  `psw` varchar(255) NOT NULL,
  `pregunta_secreta` varchar(150) NOT NULL,
  `respuesta_secreta` varchar(150) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tblexterno`
--

INSERT INTO `tblexterno` (`id_externo`, `rfc`, `nombre`, `apellido_paterno`, `apellido_materno`, `correo_electronico`, `telefono`, `institucion`, `psw`, `pregunta_secreta`, `respuesta_secreta`, `activo`) VALUES
(1, 'MERC850412H10', 'Carlos', 'Mendoza', 'Ruiz', 'carlos.mendoza@email.com', '7891112233', 'Presidencia Municipal', '12345678', '¿Nombre de tu primera mascota?', 'Sol', 1),
(2, 'JICA900820M22', 'Ana', 'Jiménez', 'Castro', 'ana.jimenez@email.com', '7894445566', 'Cámara de Comercio', '12345678', '¿Ciudad donde naciste?', 'Tampico', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblhorarios`
--

CREATE TABLE `tblhorarios` (
  `id_horario` int(11) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `estado` enum('Activo','Baja') NOT NULL DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tblhorarios`
--

INSERT INTO `tblhorarios` (`id_horario`, `hora_inicio`, `hora_fin`, `estado`) VALUES
(1, '08:00:00', '10:00:00', 'Activo'),
(2, '10:00:00', '12:00:00', 'Activo'),
(3, '12:00:00', '14:00:00', 'Activo'),
(4, '14:00:00', '16:30:00', 'Activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblreserva`
--

CREATE TABLE `tblreserva` (
  `id_reserva` int(11) NOT NULL,
  `id_sala` int(11) NOT NULL,
  `id_alumno` int(11) DEFAULT NULL,
  `matricula` int(11) DEFAULT NULL,
  `id_externo` int(11) DEFAULT NULL,
  `id_bibliotecario_atiende` int(11) DEFAULT NULL,
  `fecha_solicitud` date NOT NULL,
  `fecha_reserva` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `tipo_evento` varchar(50) NOT NULL,
  `descripcion_evento` varchar(255) NOT NULL,
  `motivo_evento` varchar(150) NOT NULL,
  `total_asistentes` int(11) NOT NULL,
  `estado_reserva` enum('Pendiente','Aprobada','Rechazada','Cancelada','Realizada') NOT NULL DEFAULT 'Pendiente',
  `motivo_rechazo_cancelacion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tblreserva`
--

INSERT INTO `tblreserva` (`id_reserva`, `id_sala`, `id_alumno`, `matricula`, `id_externo`, `id_bibliotecario_atiende`, `fecha_solicitud`, `fecha_reserva`, `hora_inicio`, `hora_fin`, `tipo_evento`, `descripcion_evento`, `motivo_evento`, `total_asistentes`, `estado_reserva`, `motivo_rechazo_cancelacion`) VALUES
(80, 4, 20251041, NULL, NULL, 2, '2026-10-10', '2026-10-15', '14:00:00', '16:00:00', 'Práctica', 'Presentación de avances de proyecto móvil', 'Proyecto integrador', 12, 'Realizada', NULL),
(95, 1, 20251085, NULL, NULL, 2, '2026-11-01', '2026-11-08', '14:00:00', '16:00:00', 'Estudio', 'Sesión de repaso de bases de datos', 'Estudio grupal', 14, 'Cancelada', 'Cancelada por el usuario'),
(99, 5, 20250981, NULL, NULL, 2, '2026-10-28', '2026-11-02', '09:00:00', '11:00:00', 'Reunión', 'Revisión de arquitectura MVP', 'Asesoría de equipo', 2, 'Rechazada', 'Aforo insuficiente (mínimo 4 personas)'),
(101, 1, 20251085, NULL, NULL, 1, '2026-10-29', '2026-11-03', '12:00:00', '14:00:00', 'Académico', 'Círculo de lectura técnica', 'Actividad académica', 14, 'Realizada', NULL),
(102, 4, 20251041, NULL, NULL, 1, '2026-10-30', '2026-11-04', '10:00:00', '12:00:00', 'Presentación', 'Demostración de prototipo web', 'Evaluación parcial', 10, 'Realizada', NULL),
(105, 2, 20250981, NULL, NULL, 2, '2026-11-05', '2026-11-18', '09:00:00', '11:00:00', 'Práctica', 'Pruebas de API REST y Retrofit', 'Proyecto integrador', 8, 'Aprobada', NULL),
(106, 4, 20251053, NULL, NULL, 2, '2026-11-06', '2026-11-19', '10:00:00', '12:00:00', 'Taller', 'Taller de diseño UI/UX en Figma', 'Clase práctica', 12, 'Aprobada', NULL),
(107, 4, NULL, 247, NULL, 2, '2026-11-07', '2026-11-20', '12:00:00', '14:00:00', 'Conferencia', 'Evaluación de proyectos de 4to cuatrimestre', 'Evaluación docente', 30, 'Aprobada', NULL),
(108, 4, 20251041, NULL, NULL, NULL, '2026-11-14', '2026-11-23', '10:00:00', '12:00:00', 'Práctica', 'Pruebas de integración Android y PHP', 'Proyecto integrador', 12, 'Pendiente', NULL),
(109, 1, NULL, 246, NULL, NULL, '2026-11-14', '2026-11-24', '12:00:00', '14:00:00', 'Reunión', 'Academia de Tecnologías de la Información', 'Reunión departamental', 20, 'Pendiente', NULL),
(110, 4, 20251041, NULL, NULL, NULL, '2026-11-17', '2026-12-02', '10:00:00', '12:00:00', 'Académico', 'Prueba Postman', 'Prueba Postman', 12, 'Pendiente', NULL),
(111, 2, 20251041, NULL, NULL, NULL, '2026-11-19', '2026-12-02', '10:00:00', '12:00:00', 'Académico', 'Prueba Postman', 'Prueba Postman', 12, 'Pendiente', NULL),
(112, 5, 20250981, NULL, NULL, NULL, '2026-11-20', '2026-11-25', '08:00:00', '10:00:00', 'Académico', 'Prueba 1', 'Prueba 1', 6, 'Pendiente', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblsalas`
--

CREATE TABLE `tblsalas` (
  `id_sala` int(11) NOT NULL,
  `nombre_sala` varchar(100) NOT NULL,
  `id_tipo_sala` int(11) NOT NULL,
  `ubicacion` varchar(100) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `capacidad` int(11) NOT NULL,
  `capacidad_minima` int(11) NOT NULL DEFAULT 1,
  `hora_apertura` time NOT NULL DEFAULT '08:00:00',
  `hora_cierre` time NOT NULL DEFAULT '16:30:00',
  `imagen_url` varchar(255) DEFAULT NULL,
  `estado` enum('Activa','Baja') NOT NULL DEFAULT 'Activa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tblsalas`
--

INSERT INTO `tblsalas` (`id_sala`, `nombre_sala`, `id_tipo_sala`, `ubicacion`, `descripcion`, `capacidad`, `capacidad_minima`, `hora_apertura`, `hora_cierre`, `imagen_url`, `estado`) VALUES
(1, 'Sala de Lectura General', 1, 'Biblioteca UTHH - Área Central', 'Espacio amplio de estudio silencioso, lectura y consulta bibliográfica con mesas de trabajo.', 80, 10, '08:00:00', '16:30:00', 'https://images.unsplash.com/photo-1521587760476-6c12a4b040da?w=800', 'Activa'),
(2, 'Sala de Cómputo 1', 2, 'Biblioteca UTHH - Ala Norte', 'Laboratorio equipado con computadoras de escritorio, software académico e internet de alta velocidad.', 30, 6, '08:00:00', '16:30:00', 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=800', 'Activa'),
(3, 'Sala de Cómputo 2', 2, 'Biblioteca UTHH - Ala Norte', 'Sala informática para prácticas grupales, investigación digital y desarrollo de proyectos.', 25, 5, '08:00:00', '16:30:00', 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800', 'Activa'),
(4, 'Sala de Audio y Video 1', 3, 'Biblioteca UTHH - Ala Sur', 'Sala audiovisual equipada con proyector HDMI, pantalla panorámica y sistema de sonido para presentaciones.', 40, 8, '08:00:00', '16:30:00', 'https://images.unsplash.com/photo-1517502884422-41eaead166d4?w=800', 'Activa'),
(5, 'Sala de Audio y Video 2', 3, 'Biblioteca UTHH - Ala Sur', 'Cabina multimedia ideal para videoconferencias, proyección de material académico y reuniones.', 20, 4, '08:00:00', '16:30:00', 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=800', 'Activa');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblsugerencias`
--

CREATE TABLE `tblsugerencias` (
  `id_sugerencia` int(11) NOT NULL,
  `id_reserva` int(11) NOT NULL,
  `comentario` text NOT NULL,
  `fecha_envio` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tblsugerencias`
--

INSERT INTO `tblsugerencias` (`id_sugerencia`, `id_reserva`, `comentario`, `fecha_envio`) VALUES
(1, 80, 'El proyector funcionó bien, sugiero agregar multicontactos en las mesas.', '2026-10-15 16:15:00'),
(2, 101, 'La sala estaba limpia y lista a tiempo.', '2026-11-03 14:10:00'),
(3, 102, 'Prueba Postman', '2026-11-18 12:06:49'),
(4, 105, 'sala pichurrienta', '2026-11-20 09:04:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbltipo_sala`
--

CREATE TABLE `tbltipo_sala` (
  `id_tipo_sala` int(11) NOT NULL,
  `nombre_tipo` varchar(50) NOT NULL,
  `descripcion` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tbltipo_sala`
--

INSERT INTO `tbltipo_sala` (`id_tipo_sala`, `nombre_tipo`, `descripcion`) VALUES
(1, 'Lectura', 'Espacios para estudio silencioso y consulta bibliográfica'),
(2, 'Cómputo', 'Laboratorios equipados con equipos informáticos'),
(3, 'Audio y Video', 'Salas multimedia con proyección y sonido');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbltrabajadores`
--

CREATE TABLE `tbltrabajadores` (
  `matricula` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido_paterno` varchar(100) NOT NULL,
  `apellido_materno` varchar(100) NOT NULL DEFAULT '',
  `correo_electronico` varchar(100) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `psw` varchar(255) NOT NULL,
  `pregunta_secreta` varchar(150) NOT NULL,
  `respuesta_secreta` varchar(150) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tbltrabajadores`
--

INSERT INTO `tbltrabajadores` (`matricula`, `nombre`, `apellido_paterno`, `apellido_materno`, `correo_electronico`, `telefono`, `psw`, `pregunta_secreta`, `respuesta_secreta`, `activo`) VALUES
(243, 'Juan', 'Pérez', 'López', 'juan.perez@uthh.edu.mx', '7891234567', '12345678', '¿Nombre de tu primera mascota?', 'Rex', 1),
(244, 'María', 'Gómez', 'Reséndiz', 'maria.gomez@uthh.edu.mx', '7899876543', '12345678', '¿Ciudad donde naciste?', 'Pachuca', 1),
(245, 'Ricardo', 'Sánchez', 'Tovar', 'ricardo.sanchez@uthh.edu.mx', '7896543210', '12345678', '¿Color favorito?', 'Verde', 1),
(246, 'Carlos', 'Rodríguez', 'Méndez', 'carlos.rodriguez@uthh.edu.mx', '7895551122', '12345678', '¿Nombre de tu primera mascota?', 'Luna', 1),
(247, 'Gladys', 'Peralta', 'Vega', 'gladys.peralta@uthh.edu.mx', '7895553344', '12345678', '¿Nombre de tu primera mascota?', 'Nube', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `tblalumnos`
--
ALTER TABLE `tblalumnos`
  ADD PRIMARY KEY (`id_alumno`),
  ADD UNIQUE KEY `correo_electronico` (`correo_electronico`);

--
-- Indices de la tabla `tblbibliotecario`
--
ALTER TABLE `tblbibliotecario`
  ADD PRIMARY KEY (`id_bibliotecario`),
  ADD UNIQUE KEY `usuarios` (`usuarios`),
  ADD KEY `matricula` (`matricula`);

--
-- Indices de la tabla `tblcontacto`
--
ALTER TABLE `tblcontacto`
  ADD PRIMARY KEY (`id_contacto`);

--
-- Indices de la tabla `tblcontenido_publico`
--
ALTER TABLE `tblcontenido_publico`
  ADD PRIMARY KEY (`id_contenido`);

--
-- Indices de la tabla `tblequipamiento`
--
ALTER TABLE `tblequipamiento`
  ADD PRIMARY KEY (`id_equipamiento`),
  ADD KEY `id_sala` (`id_sala`);

--
-- Indices de la tabla `tblexterno`
--
ALTER TABLE `tblexterno`
  ADD PRIMARY KEY (`id_externo`),
  ADD UNIQUE KEY `rfc` (`rfc`),
  ADD UNIQUE KEY `correo_electronico` (`correo_electronico`);

--
-- Indices de la tabla `tblhorarios`
--
ALTER TABLE `tblhorarios`
  ADD PRIMARY KEY (`id_horario`),
  ADD UNIQUE KEY `horario` (`hora_inicio`,`hora_fin`);

--
-- Indices de la tabla `tblreserva`
--
ALTER TABLE `tblreserva`
  ADD PRIMARY KEY (`id_reserva`),
  ADD KEY `id_sala` (`id_sala`),
  ADD KEY `id_alumno` (`id_alumno`),
  ADD KEY `matricula` (`matricula`),
  ADD KEY `id_externo` (`id_externo`),
  ADD KEY `id_bibliotecario_atiende` (`id_bibliotecario_atiende`);

--
-- Indices de la tabla `tblsalas`
--
ALTER TABLE `tblsalas`
  ADD PRIMARY KEY (`id_sala`),
  ADD KEY `id_tipo_sala` (`id_tipo_sala`);

--
-- Indices de la tabla `tblsugerencias`
--
ALTER TABLE `tblsugerencias`
  ADD PRIMARY KEY (`id_sugerencia`),
  ADD KEY `id_reserva` (`id_reserva`);

--
-- Indices de la tabla `tbltipo_sala`
--
ALTER TABLE `tbltipo_sala`
  ADD PRIMARY KEY (`id_tipo_sala`),
  ADD UNIQUE KEY `nombre_tipo` (`nombre_tipo`);

--
-- Indices de la tabla `tbltrabajadores`
--
ALTER TABLE `tbltrabajadores`
  ADD PRIMARY KEY (`matricula`),
  ADD UNIQUE KEY `correo_electronico` (`correo_electronico`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `tblbibliotecario`
--
ALTER TABLE `tblbibliotecario`
  MODIFY `id_bibliotecario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tblcontacto`
--
ALTER TABLE `tblcontacto`
  MODIFY `id_contacto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tblcontenido_publico`
--
ALTER TABLE `tblcontenido_publico`
  MODIFY `id_contenido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `tblequipamiento`
--
ALTER TABLE `tblequipamiento`
  MODIFY `id_equipamiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `tblexterno`
--
ALTER TABLE `tblexterno`
  MODIFY `id_externo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `tblhorarios`
--
ALTER TABLE `tblhorarios`
  MODIFY `id_horario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `tblreserva`
--
ALTER TABLE `tblreserva`
  MODIFY `id_reserva` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT de la tabla `tblsalas`
--
ALTER TABLE `tblsalas`
  MODIFY `id_sala` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `tblsugerencias`
--
ALTER TABLE `tblsugerencias`
  MODIFY `id_sugerencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `tbltipo_sala`
--
ALTER TABLE `tbltipo_sala`
  MODIFY `id_tipo_sala` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `tblbibliotecario`
--
ALTER TABLE `tblbibliotecario`
  ADD CONSTRAINT `tblbibliotecario_ibfk_1` FOREIGN KEY (`matricula`) REFERENCES `tbltrabajadores` (`matricula`);

--
-- Filtros para la tabla `tblequipamiento`
--
ALTER TABLE `tblequipamiento`
  ADD CONSTRAINT `tblequipamiento_ibfk_1` FOREIGN KEY (`id_sala`) REFERENCES `tblsalas` (`id_sala`);

--
-- Filtros para la tabla `tblreserva`
--
ALTER TABLE `tblreserva`
  ADD CONSTRAINT `tblreserva_ibfk_1` FOREIGN KEY (`id_sala`) REFERENCES `tblsalas` (`id_sala`),
  ADD CONSTRAINT `tblreserva_ibfk_2` FOREIGN KEY (`id_alumno`) REFERENCES `tblalumnos` (`id_alumno`),
  ADD CONSTRAINT `tblreserva_ibfk_3` FOREIGN KEY (`matricula`) REFERENCES `tbltrabajadores` (`matricula`),
  ADD CONSTRAINT `tblreserva_ibfk_4` FOREIGN KEY (`id_externo`) REFERENCES `tblexterno` (`id_externo`),
  ADD CONSTRAINT `tblreserva_ibfk_5` FOREIGN KEY (`id_bibliotecario_atiende`) REFERENCES `tblbibliotecario` (`id_bibliotecario`);

--
-- Filtros para la tabla `tblsalas`
--
ALTER TABLE `tblsalas`
  ADD CONSTRAINT `tblsalas_ibfk_1` FOREIGN KEY (`id_tipo_sala`) REFERENCES `tbltipo_sala` (`id_tipo_sala`);

--
-- Filtros para la tabla `tblsugerencias`
--
ALTER TABLE `tblsugerencias`
  ADD CONSTRAINT `tblsugerencias_ibfk_1` FOREIGN KEY (`id_reserva`) REFERENCES `tblreserva` (`id_reserva`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
