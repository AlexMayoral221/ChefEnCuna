-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 29-05-2024 a las 23:24:03
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
-- Base de datos: `chefencuna`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administradores`
--

CREATE TABLE `administradores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `contraseña` varchar(255) DEFAULT NULL,
  `correo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `administradores`
--

INSERT INTO `administradores` (`id`, `nombre`, `contraseña`, `correo`) VALUES
(1, 'Alejandro', 'admin123', 'admin@admin.com');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `nivel` varchar(50) DEFAULT NULL,
  `fecha_creacion` date DEFAULT NULL,
  `requisitos` text DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `duracion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`id`, `titulo`, `descripcion`, `nivel`, `fecha_creacion`, `requisitos`, `instructor_id`, `duracion`) VALUES
(2, 'Repostería y Panadería', 'Descubre el arte de hacer pasteles, panes, galletas, y otros postres deliciosos.', 'Intermedio', '2024-02-01', 'Interés en la repostería y la panadería: Demostrar interés en aprender sobre técnicas de repostería y panadería.\r\nHabilidades básicas de cocina: No se requiere experiencia previa en repostería, pero es útil tener habilidades básicas en cocina.\r\nDisponibilidad de tiempo: Compromiso para asistir a clases teóricas y prácticas de manera regular durante el horario establecido del curso.\r\nEquipo y materiales: Se proporcionará una lista de equipo y materiales necesarios para el curso. Los estudiantes deben tener acceso a estos elementos durante las clases prácticas.\r\nCapacidad para seguir instrucciones: Habilidad para seguir instrucciones detalladas y ejecutar recetas con precisión.\r\nHabilidad para trabajar en equipo: Capacidad para trabajar en equipo durante actividades prácticas en la cocina.', 1, '6 semanas'),
(3, 'Cocina Internacional', 'Explora distintas cocinas del mundo, como italiana, mexicana, japonesa, y más.', 'Avanzado', '2024-03-01', NULL, NULL, '10 semanas'),
(4, 'Cocina Vegetariana y Vegana', 'Aprende a preparar deliciosas recetas sin ingredientes de origen animal, llenas de sabor y nutrientes.', 'Principiante', '2024-04-01', NULL, NULL, '6 semanas'),
(5, 'Cocina Saludable', 'Descubre cómo preparar comidas equilibradas y nutritivas que benefician tu salud y bienestar.', 'Intermedio', '2024-05-01', NULL, NULL, '8 semanas'),
(6, 'Técnicas Avanzadas de Cocina', 'Eleva tus habilidades culinarias al siguiente nivel con técnicas avanzadas y platos complejos.', 'Avanzado', '2024-06-01', NULL, NULL, '12 semanas'),
(7, 'Cocina para Niños', 'Inicia a los más pequeños en el mundo de la cocina con recetas divertidas y seguras para ellos.', 'Principiante', '2024-05-26', NULL, NULL, '4 semanas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `instructores`
--

CREATE TABLE `instructores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `apellido` varchar(255) DEFAULT NULL,
  `correo` varchar(255) NOT NULL,
  `genero` varchar(255) DEFAULT NULL,
  `contraseña` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `instructores`
--

INSERT INTO `instructores` (`id`, `nombre`, `apellido`, `correo`, `genero`, `contraseña`) VALUES
(1, 'Alejandro', 'Mayoral', 'a359717@uabc.edu.mx', 'Masculino', 'alex123'),
(2, 'Carlos', 'Martinez', 'Charly21@uabc.edu.mx', 'Masculino', 'carlos21'),
(3, 'Sergio', 'Perez', 'checoperez@uabc.edu.mx', 'Masculino', 'checoperez2'),
(4, 'Pepe', 'Ramos', 'Pepe12@uabc.edu.mx', 'Masculino', 'pepillo1'),
(5, 'Lorena', 'García', 'Lorena21@uabc.edu.mx', 'Femenino', 'Lore1234'),
(6, 'Pedro', 'Hernandez', 'pedro123@uabc.edu.mx', 'Masculino', 'pedropedro');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recetas`
--

CREATE TABLE `recetas` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `ingredientes` text NOT NULL,
  `procedimiento` text NOT NULL,
  `fecha_publicacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `recetas`
--

INSERT INTO `recetas` (`id`, `titulo`, `descripcion`, `ingredientes`, `procedimiento`, `fecha_publicacion`) VALUES
(1, 'Spaghetti Con Albóndigas Al Horno', 'Una receta clásica que combina la textura perfecta del spaghetti con jugosas albóndigas de res, horneadas hasta alcanzar la perfección y bañadas en una rica salsa de tomate. Coronado con queso gratinado, este plato promete ser un festín para los sentidos, ideal para cualquier día de la semana.', 'suficiente de agua\r\nsuficiente de sal\r\n1 paquete de spaghetti\r\n500 gramos de carne molida\r\n1/4 tazas de albahaca, finamente picada\r\n1/4 tazas de queso parmesano, rallado\r\n1 cucharada de ajo en polvo\r\nal gusto de sal\r\nal gusto de pimienta\r\n1 huevo\r\nsuficiente de aceite de oliva\r\n1/4 tazas de cebolla\r\n2 cucharadas de ajo, finamente picado\r\n3 tazas de salsa de tomate\r\n6 albahaca\r\n1 taza de caldo de res\r\n1/4 tazas de queso Gouda, rallado\r\n1/4 tazas de queso parmesano, rallado\r\nsuficiente de hoja de albahaca\r\nal gusto de baguette', 'Hierve suficiente agua en una olla, agrega sal y deja hervir nuevamente. Posteriormente, agrega el Spaghetti y cocina durante 8 minutos. Escurre y reserva.\r\nMezcla la carne molida, la albahaca, el queso parmesano, el ajo en polvo, la sal, la pimienta y el huevo en un bowl hasta que todo esté bien integrado. Forma albóndigas medianas y reserva.\r\nCalienta un poco de aceite de oliva en una sartén de hierro. Cocina las albóndigas por 3 minutos de cada lado para sellarlas. Retira de la sartén y reserva.\r\nVierte un poco más de aceite de oliva en la misma sartén y sofríe la cebolla y el ajo durante 3 minutos. Incorpora la salsa de tomate, la albahaca y el caldo de res, cocina durante 5 minutos más.\r\nAgrega el Spaghetti hervido y las albóndigas, espolvorea el queso gouda y el queso parmesano y hornea durante 10 minutos.\r\nSirve la pasta en la sartén, decora con hojas de albahaca y acompaña con rebanadas de pan.', '2024-05-29 10:29:31'),
(2, 'Enchiladas Con Pollo', 'Las enchiladas con pollo son un plato mexicano delicioso y reconfortante, donde se combinan tortillas de maíz rellenas de pollo deshebrado, bañadas en una salsa cremosa de tomate y chiles verdes, todo cubierto con queso gratinado hasta lograr una capa dorada y burbujeante. ', '1 kilo de tomate verde, de fresilla\r\n1 cebolla\r\n4 suficiente de cuadrito de caldo de pollo concentrado\r\n1/2 litros de leche de vaca\r\nmanojos de cilantro fresco\r\n30 tortillas de maíz, delgadas\r\n2 pechugas de pollo, cocidas y en tiritas\r\n10 rebanadas de queso manchego, o chihuahua\r\nfríjoles refritos', 'En una olla cocer el tomatillo pero tener cuidado porque ya que hierve el agua les tienes que apagar para que no se amarguen.\r\nDorar la cebolla en un sartén con aceite hasta que este traslucida. Sacar la cebolla, y en una licuadora licuarla junto con el cilantro, y tomatillo.\r\nEn el mismo sartén de la cebolla, vaciar todo de la licuadora e incorporar la leche hasta que se haga una salsa espesa.\r\nEnvuelves pollo en tiritas en las tortillas (las puedes pasar por aceite primero) y formas un taquito. Las colocas en un refractario engrasado.\r\nRepites el paso anterior con el resto de las tortillas, las bañas en la salsa verde y les pones rebanadas de queso manchego encima.\r\nLas metes al horno a 180 grados por 15 minutos a gratinar y calentar.', '2024-03-25 21:31:13'),
(3, 'Brochetas De Carne Con Papas', 'Las brochetas de carne con papas son una comida sencilla pero sumamente sabrosa, perfecta para asados al aire libre o cenas familiares. ', '1 taza de salsa de soya, para la marinada\n1 cucharada de hojuelas de chile, para la marinada\n1 cucharada de ralladura de limón amarillo, para la marinada\n500 gramos de filete de res\n2 calabazas, en láminas delgadas y verticales\nsuficiente de aceite vegetal\nsuficiente de aceite de oliva\n2 dientes de ajo, en láminas\n2 tazas de papa, en rebanadas de ½ cm, con cáscara\nal gusto de ensalada de arúgula, para acompañar\nal gusto de salsa de soya, para acompañar', 'Mezcla salsa de soya, las hojuelas de chile y la ralladura de limón en un tazón.\nAgrega el filete de res a la marinada y deja reposar por una hora.\nArma las brochetas intercalando la carne y las láminas de calabaza en forma de “S”.\nCalienta una sartén o parrilla a temperatura media, barniza las brochetas con un poco de aceite y cocina por aproximadamente 10 minutos, asegurándote de sellar cada lado.\nPara las papas: Calienta abundante aceite de oliva en una sartén a temperatura baja, fríe el ajo por 2 minutos, retira y agrega las papas, cocínalas por 10 minutos o hasta que estén completamente cocidas y suaves, sin romperse. Retira del aceite y pásalas por papel absorbente.\nPara armar el plato: Coloca las brochetas de carne sobre una cama de papas y ensalada de arúgula para decorar acompaña con Salsa de soya.', '2024-03-25 21:31:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recetas_favoritas`
--

CREATE TABLE `recetas_favoritas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `receta_id` int(11) NOT NULL,
  `fecha_agregado` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `apellido` varchar(255) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `contraseña` varchar(255) NOT NULL,
  `genero` enum('masculino','femenino','otro','prefiero_no_decir') NOT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `correo`, `contraseña`, `genero`, `fecha_registro`) VALUES
(2, 'Charly', 'Lopez', 'charlylopez@uabc.edu.mx', '$2y$10$69tLtKGVYHVz.HEuJc1gwONmNrEVXta0CN5jeZZng0/2EEeYOsaDa', 'masculino', '2024-05-26 21:51:02');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `instructores`
--
ALTER TABLE `instructores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `recetas`
--
ALTER TABLE `recetas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `recetas_favoritas`
--
ALTER TABLE `recetas_favoritas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `receta_id` (`receta_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `instructores`
--
ALTER TABLE `instructores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `recetas`
--
ALTER TABLE `recetas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `recetas_favoritas`
--
ALTER TABLE `recetas_favoritas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `recetas_favoritas`
--
ALTER TABLE `recetas_favoritas`
  ADD CONSTRAINT `recetas_favoritas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `recetas_favoritas_ibfk_2` FOREIGN KEY (`receta_id`) REFERENCES `recetas` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
