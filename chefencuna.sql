-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 27-11-2025 a las 03:11:10
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
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `nivel` varchar(50) DEFAULT 'Principiante',
  `requisitos` text DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `duracion` varchar(100) DEFAULT NULL,
  `objetivos` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`id`, `titulo`, `descripcion`, `nivel`, `requisitos`, `instructor_id`, `duracion`, `objetivos`, `fecha_creacion`) VALUES
(1, 'Repostería Avanzada', 'Aprende técnicas avanzadas de repostería.', 'Avanzado', 'Experiencia previa.', 5, '6 semanas', 'Dominar técnicas avanzadas.', '2025-11-22 02:35:14'),
(2, 'Decoración de Pasteles', 'Técnicas de decoración con fondant.', 'Intermedio', 'Conocimientos básicos.', 4, '4 semanas', 'Dominar decoración.', '2025-11-22 02:35:14'),
(3, 'Cocina Asiática', 'Platos tradicionales asiáticos.', 'Intermedio', 'Interés en cocina.', 11, '8 semanas', 'Uso de ingredientes auténticos.', '2025-11-22 02:35:14'),
(4, 'Cocina saludable', 'Ensaladas frescas y nutritivas.', 'Principiante', 'Ninguno.', 7, '2 semanas', 'Aprender a seleccionar vegetales.', '2025-11-22 02:35:14'),
(5, 'Curso de Cocina para Niños', 'Platos simples y divertidos.', 'Principiante', 'Ninguno.', 2, '1 semana', 'Cocina segura.', '2025-11-22 02:35:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `faqs`
--

CREATE TABLE `faqs` (
  `id` int(11) NOT NULL,
  `pregunta` varchar(500) NOT NULL,
  `respuesta` text NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `orden` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `faqs`
--

INSERT INTO `faqs` (`id`, `pregunta`, `respuesta`, `fecha_creacion`, `orden`) VALUES
(1, '¿Cómo puedo inscribirme en un curso de cocina?', 'Es muy sencillo. Solo navega a la sección &quot;Cursos&quot;, selecciona el curso que te interese y haz clic en &quot;Inscribirme&quot;. Si no tienes una cuenta, se te pedirá que te registres primero.', '2025-11-24 04:34:25', 1),
(2, '¿Necesito ser un chef profesional para tomar un curso?', '¡De ninguna manera! Nuestros cursos están diseñados para todos los niveles, desde principiantes (\"ChefEnCuna\") hasta cocineros avanzados. Cada curso especifica su nivel de dificultad.', '2025-11-24 04:34:25', 2),
(3, '¿Qué son los \"Foros de Ayuda\" y cómo los utilizo?', 'Los foros son espacios de la comunidad donde puedes hacer preguntas, compartir consejos y resolver dudas con otros estudiantes y, en ocasiones, con los instructores. Simplemente busca el foro relevante o crea un nuevo hilo.', '2025-11-24 04:34:25', 3),
(4, '¿Las recetas tienen información nutricional?', 'Sí, la mayoría de nuestras recetas incluyen una sección con información nutricional estimada, incluyendo calorías, proteínas y grasas, aunque siempre recomendamos consultar a un especialista en nutrición.', '2025-11-24 04:34:25', 4),
(5, '¿Los cursos incluyen material descargable o guías de estudio?', 'Sí, la mayoría de los cursos de cocina incluyen recetarios en PDF, listas de ingredientes, guías de utensilios y otros recursos que puedes descargar para usar offline.', '2025-11-27 01:53:18', 5),
(6, '¿Necesito tener ingredientes o equipos de cocina especiales para los cursos?', 'Depende del nivel y del tema. Los cursos básicos utilizan equipo estándar. Los cursos avanzados o de especialización (como pastelería o cocina molecular) podrían requerir equipos o ingredientes más específicos, lo cual suele indicarse claramente al inicio.', '2025-11-27 01:53:41', 6),
(7, '¿Cómo puedo contactar al instructor si tengo dudas específicas sobre una receta o lección?', 'Habitualmente se hace a través de un foro o sección de comentarios dentro de la plataforma. Algunos programas también ofrecen sesiones de preguntas y respuestas en vivo o la opción de enviar un correo electrónico directo.', '2025-11-27 01:54:33', 7);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `foro_respuestas`
--

CREATE TABLE `foro_respuestas` (
  `id` int(11) NOT NULL,
  `tema_id` int(11) NOT NULL COMMENT 'ID del tema al que pertenece la respuesta',
  `usuario_id` int(11) NOT NULL COMMENT 'ID del usuario que responde',
  `respuesta` text NOT NULL,
  `fecha_respuesta` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `foro_temas`
--

CREATE TABLE `foro_temas` (
  `id` int(11) NOT NULL,
  `alumno_id` int(11) NOT NULL COMMENT 'ID del usuario que crea el tema',
  `titulo` varchar(255) NOT NULL,
  `contenido` text NOT NULL,
  `etiqueta` varchar(50) DEFAULT 'General' COMMENT 'Ej: Tecnico, Receta, Sugerencia',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripciones`
--

CREATE TABLE `inscripciones` (
  `id` int(11) NOT NULL,
  `alumno_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `fecha_inscripcion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulos_curso`
--

CREATE TABLE `modulos_curso` (
  `id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `modulos_curso`
--

INSERT INTO `modulos_curso` (`id`, `curso_id`, `titulo`) VALUES
(1, 1, 'Introducción a la Repostería y Panadería'),
(2, 1, 'Avanzando en la Repostería y Panadería');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recetas`
--

CREATE TABLE `recetas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `ingredientes` text NOT NULL,
  `procedimiento` text NOT NULL,
  `imagen_ruta` varchar(255) DEFAULT NULL,
  `fecha_publicacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `categoria` varchar(100) NOT NULL DEFAULT 'General'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `recetas`
--

INSERT INTO `recetas` (`id`, `usuario_id`, `titulo`, `descripcion`, `ingredientes`, `procedimiento`, `imagen_ruta`, `fecha_publicacion`, `categoria`) VALUES
(1, 5, 'Spaghetti Con Albóndigas Al Horno', 'Una receta clásica que combina la textura perfecta del spaghetti con jugosas albóndigas de res, horneadas hasta alcanzar la perfección y bañadas en una rica salsa de tomate. Coronado con queso gratinado, este plato promete ser un festín para los sentidos, ideal para cualquier día de la semana.', 'Suficiente de agua\r\nSuficiente de sal\r\n1 paquete de spaghetti\r\n500 gramos de carne molida\r\n1/4 tazas de albahaca, finamente picada\r\n1/4 tazas de queso parmesano, rallado\r\n1 cucharada de ajo en polvo\r\nAl gusto de sal\r\nAl gusto de pimienta\r\n1 huevo\r\nSuficiente de aceite de oliva\r\n1/4 tazas de cebolla\r\n2 cucharadas de ajo, finamente picado\r\n3 tazas de salsa de tomate\r\n6 albahaca\r\n1 taza de caldo de res\r\n1/4 tazas de queso Gouda, rallado\r\n1/4 tazas de queso parmesano, rallado\r\nSuficiente de hoja de albahaca\r\nAl gusto de baguette', 'Hierve suficiente agua en una olla, agrega sal y deja hervir nuevamente. Posteriormente, agrega el Spaghetti y cocina durante 8 minutos. Escurre y reserva.\r\nMezcla la carne molida, la albahaca, el queso parmesano, el ajo en polvo, la sal, la pimienta y el huevo en un bowl hasta que todo esté bien integrado. Forma albóndigas medianas y reserva.\r\nCalienta un poco de aceite de oliva en una sartén de hierro. Cocina las albóndigas por 3 minutos de cada lado para sellarlas. Retira de la sartén y reserva.\r\nVierte un poco más de aceite de oliva en la misma sartén y sofríe la cebolla y el ajo durante 3 minutos. Incorpora la salsa de tomate, la albahaca y el caldo de res, cocina durante 5 minutos más.\r\nAgrega el Spaghetti hervido y las albóndigas, espolvorea el queso gouda y el queso parmesano y hornea durante 10 minutos.\r\nSirve la pasta en la sartén, decora con hojas de albahaca y acompaña con rebanadas de pan.', NULL, '2024-05-29 17:29:31', 'Pasta'),
(3, 2, 'Brochetas De Carne con Papa', 'Las brochetas de carne con papas son una comida sencilla pero sumamente sabrosa, perfecta para asados al aire libre o cenas familiares. ', '1 taza de salsa de soya, para la marinada\r\n1 cucharada de hojuelas de chile, para la marinada\r\n1 cucharada de ralladura de limón amarillo, para la marinada\r\n500 gramos de filete de res\r\n2 calabazas, en láminas delgadas y verticales\r\nSuficiente de aceite vegetal\r\nSuficiente de aceite de oliva\r\n2 dientes de ajo, en láminas\r\n2 tazas de papa, en rebanadas de ½ cm, con cáscara\r\nAl gusto de ensalada de arúgula, para acompañar\r\nAl gusto de salsa de soya, para acompañar', 'Mezcla salsa de soya, las hojuelas de chile y la ralladura de limón en un tazón.\r\nAgrega el filete de res a la marinada y deja reposar por una hora.\r\nArma las brochetas intercalando la carne y las láminas de calabaza en forma de “S”.\r\nCalienta una sartén o parrilla a temperatura media, barniza las brochetas con un poco de aceite y cocina por aproximadamente 10 minutos, asegurándote de sellar cada lado.\r\nPara las papas: Calienta abundante aceite de oliva en una sartén a temperatura baja, fríe el ajo por 2 minutos, retira y agrega las papas, cocínalas por 10 minutos o hasta que estén completamente cocidas y suaves, sin romperse. Retira del aceite y pásalas por papel absorbente.\r\nPara armar el plato: Coloca las brochetas de carne sobre una cama de papas y ensalada de arúgula para decorar acompaña con Salsa de soya.', NULL, '2024-03-26 04:31:13', 'Parrilladas'),
(4, 5, 'Chimigangas de Pollo BBQ', '¿Quieres probar algo nuevo en la cocina? Estas chimichangas de pollo BBQ son una excelente opción para cualquier hora. Y lo mejor de todo es que puedes tenerlas listas en solo 40 minutos. ¡Anímate y disfruta de su increíble sabor!', '2 tazas de Salsa de Tomate para Aderezar\r\n1 taza de azúcar mascabado\r\n2 cucharadas de jugo sazonador\r\n1 cucharada de salsa inglesa\r\n1/4 tazas de vinagre de manzana\r\n1 cucharadita de paprika\r\n1 cucharadita de cebolla en polvo\r\n1 cucharadita de pimienta\r\n2 cucharadas de mantequilla, en cubos pequeños\r\n2 tazas de pechuga de pollo, cocida y desmenuzada\r\nsuficiente de tortilla de harina burrera\r\n1 taza de queso americano, rallado\r\nMantequilla al gusto\r\nPapas fritas al gusto', 'En una olla, combina la Salsa de Tomate para Aderezar, el azúcar, el jugo sazonador, la salsa inglesa y el vinagre de manzana y cocina a fuego bajo por 10 minutos. Posteriormente, agrega la paprika, la cebolla y la pimienta y mezcla. Cocina por 5 minutos más. Reserva.\r\nDerrite la mantequilla en una sartén a fuego medio, añade el pollo y dora por 5 minutos. Vierte la salsa y cocina a fuego bajo por 5 minutos.\r\nRellena las tortillas burreras con un poco pollo BBQ y queso. Dobla dos de los lados y enrolla. Repite el mismo proceso hasta terminar con todo el relleno.\r\nDerrite mantequilla en un comal y dora las chimichangas a fuego bajo por 5 minutos.\r\nSirve las chimichangas y acompaña con papas fritas.', NULL, '2024-07-11 19:32:56', 'Mexicana'),
(5, 7, 'Sushi De Pepino Con Chipotle', '¿Tienes ganas de algo ligero y sabroso? Entonces debes probar este sushi de pepino con chipotle. Es una opción refrescante y deliciosa, perfecta para cualquier ocasión, ya sea un almuerzo rápido o una reunión con amigos. ¿Te animas a darle una oportunidad?', '1 taza de atún en lata, drenado\r\n1/2 tazas de Chipotle \r\n1/2 tazas de pepino, en cubos pequeños\r\n1 pepino\r\n90 gramos de queso crema, en rebanadas delgadas\r\n1 aguacate, en rebanadas delgadas\r\nAjonjolí negro al gusto, para decorar\r\nSalsa de soya al gusto\r\nChipotle al gusto', 'En un bowl, combina el atún, el Chipotle y el pepino. Mezcla y reserva.\r\nRebana el pepino de forma horizontal, usando un pelador.\r\nColoca plástico antiadherente sobre un tapete de bambú y añade una capa de pepino, una de queso crema, una de aguacate y una de atún.\r\nCierra el rollo de forma horizontal, refrigera por 30 minutos y corta en rollos de 2 cm.\r\nSirve el sushi de pepino en una tabla y decora con ajonjolí. Acompaña con salsa de soya y Chipotle.', NULL, '2024-07-11 23:44:46', 'Asiática'),
(7, 4, 'Enchiladas Con Pollo', 'Las enchiladas con pollo son un plato mexicano delicioso y reconfortante, donde se combinan tortillas de maíz rellenas de pollo deshebrado, bañadas en una salsa cremosa de tomate y chiles verdes, todo cubierto con queso gratinado hasta lograr una capa dorada y burbujeante.', '1 kilo de tomate verde, de fresilla\r\n1 cebolla\r\n4 suficiente de cuadrito de caldo de pollo concentrado\r\n1/2 litros de leche de vaca\r\nManojos de cilantro fresco\r\n30 tortillas de maíz, delgadas\r\n2 pechugas de pollo, cocidas y en tiritas\r\n10 rebanadas de queso manchego, o chihuahua\r\nFríjoles refritos', 'En una olla cocer el tomatillo pero tener cuidado porque ya que hierve el agua les tienes que apagar para que no se amarguen.\r\nDorar la cebolla en un sartén con aceite hasta que este traslucida. Sacar la cebolla, y en una licuadora licuarla junto con el cilantro, y tomatillo.\r\nEn el mismo sartén de la cebolla, vaciar todo de la licuadora e incorporar la leche hasta que se haga una salsa espesa.\r\nEnvuelves pollo en tiritas en las tortillas (las puedes pasar por aceite primero) y formas un taquito. Las colocas en un refractario engrasado.\r\nRepites el paso anterior con el resto de las tortillas, las bañas en la salsa verde y les pones rebanadas de queso manchego encima.\r\nLas metes al horno a 180 grados por 15 minutos a gratinar y calentar.', 'img/recetas/7.jpg', '2025-11-22 18:46:18', 'Mexicana'),
(8, 11, 'Cerdo con salsa agridulce', 'Platillo de cerdo agridulce está compuesto por trozos de cerdo crujientes, salteados con pimientos verdes y rojos, cebolla morada y piña fresca. Todo está cubierto con una brillante y apetitosa salsa agridulce. El platillo se sirve sobre una cama de arroz blanco y esponjoso, lo que lo convierte en una comida completa y deliciosa. Los colores vibrantes de los ingredientes y la salsa le dan un aspecto muy atractivo.', '1/2 kg. de lomo de cerdo\r\n1 huevo\r\nSalsa de soja ligera\r\nVino chino de arroz\r\nKetchup\r\nVinagre de arroz\r\nHarina de trigo\r\nHarina fina de maíz\r\nPimienta blanca\r\nSemillas de sésamo tostadas\r\nAgua\r\nAzúcar\r\nSal\r\nAceite de oliva virgen extra', 'Cortamos el lomo de cerdo en tiras o porciones pequeñas.\r\nLo colocamos en un bol o un tupper que podamos cerrar.\r\nBatimos el huevo y lo añadimos al cerdo.\r\nAñadimos también 1 cucharada de salsa de soja ligera, 1 de vino de arroz chino, una pizca de sal y de pimienta blanca y removemos todo muy bien.\r\nTapamos el tupper y dejamos marinar como mínimo 30 minutos.\r\nEn un cuenco, mezclamos 50 g. de harina de trigo y 50 g. de harina fina de maíz.\r\nEnharinamos los trozos de cerdo con la mezcla de harinas y los freímos en una sartén con un chorro generoso de aceite de oliva virgen extra. Nos llevará 4 o 5 minutos como máximo, y tendremos cuidado de que no se pase para que no quede duro.\r\nRetiramos el cerdo a un plato o una fuente cubierta de papel absorbente para que suelte el exceso de aceite.\r\nEn una sartén puesta al fuego (medio o alto) añadimos una cucharada de aceite de oliva virgen extra y otra de ajo picado.\r\nCuando ha empezado a dorarse incorporamos medio vaso de agua, 3 cucharadas de ketchup, 2 de salsa de soja ligera, 2 de vinagre de arroz, 2 de azúcar y una pizca de sal. Removemos bien.\r\nDiluimos un par de cucharadas de harina fina de maíz en agua tibia y vamos añadiendo poco a poco a la salsa, sin dejar de remover, hasta que se vuelve ligeramente espesa.\r\nEn ese momento añadimos el cerdo a la sartén y removemos con cuidado hasta que se cubre por completo con la salsa.\r\nLo servimos espolvoreando semillas de sésamo sobre la carne.', 'img/recetas/8.jpg', '2025-11-25 04:25:38', 'Asiática'),
(9, 2, 'Hamburguesas de pollo', 'Esta es una hamburguesa fresca, jugosa y llena de sabor, una excelente alternativa a la carne de res, ideal para una comida satisfactoria y reconfortante.', '300 g de pechuga de pollo deshuesada y sin piel (o muslo de pollo si prefieres más jugosidad).\r\n1/4 taza de pan molido (panko o pan rallado).\r\n1/4 taza de cebolla finamente picada.\r\n1 diente de ajo, machacado o muy finamente picado.\r\n1 cucharada de perejil fresco picado.\r\n1/2 cucharadita de sal.\r\n1/4 cucharadita de pimienta negra.\r\n1 cucharada de aceite de oliva o aceite vegetal.', 'Pica el pollo en trozos pequeños y luego muélelo en un procesador de alimentos hasta que tenga una consistencia de carne molida. Alternativamente, puedes pedirle a tu carnicero que te lo muela.\r\nEn un bol grande, combina la carne de pollo molida con el pan molido, la cebolla picada, el ajo, el perejil, la sal y la pimienta.\r\nMezcla bien con las manos hasta que todos los ingredientes estén integrados. No amases en exceso para evitar que la carne se endurezca.\r\nDivide la mezcla en porciones iguales (para el tamaño de tu pan) y forma discos de unos 1.5 cm de grosor. Haz una pequeña hendidura en el centro de cada disco con el pulgar para evitar que se abulten al cocinarse.\r\nEn un sartén grande (o parrilla), calienta el aceite de oliva a fuego medio.\r\nColoca las hamburguesas en el sartén caliente. Cocina durante 5-7 minutos por cada lado, o hasta que estén bien doradas y completamente cocidas en el centro (deben alcanzar una temperatura interna de 74 grados).\r\nDurante el último minuto de cocción, coloca una rebanada de queso sobre cada hamburguesa. Cubre el sartén brevemente para que el queso se derrita.\r\nCorta los panes de hamburguesa por la mitad. Tuesta ligeramente las caras internas en el mismo sartén o en un tostador.\r\nUnta tus salsas favoritas en ambas mitades del pan. \r\nColoca la lechuga en la base, seguida por la hamburguesa de pollo con queso.\r\nTermina con las rodajas de tomate y pepinillos (si los usas), y cubre con la parte superior del pan.', 'img/recetas/9.jpg', '2025-11-25 05:40:40', 'Pollos');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recetas_favoritas`
--

CREATE TABLE `recetas_favoritas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `receta_id` int(11) NOT NULL,
  `fecha_agregado` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `recetas_favoritas`
--

INSERT INTO `recetas_favoritas` (`id`, `usuario_id`, `receta_id`, `fecha_agregado`) VALUES
(1, 8, 5, '2025-11-26 18:33:17'),
(3, 8, 8, '2025-11-26 18:42:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('alumno','maestro','administrador') NOT NULL DEFAULT 'alumno',
  `genero` enum('Masculino','Femenino','Otro') DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `email`, `password`, `rol`, `genero`, `foto_perfil`, `bio`, `fecha_registro`) VALUES
(1, 'Administrador', '', 'admin@admin.com', 'admin123', 'administrador', NULL, NULL, NULL, '2025-11-22 02:35:14'),
(2, 'Alejandro', 'Mayoral', 'a359717@uabc.edu.mx', '$2y$10$UQYuhcUXW0BB9RvFTn0Y2OIAmxvGqECr8p3vCWFoD3gswFqk2xU7m', 'maestro', 'Masculino', 'img/perfiles/perfil_2.jpg', 'Soy Alejandro Mayoral, originario de un pequeño pueblo de Baja California. Me caracterizo por mis raíces humildes, mi dedicación y el orgullo que siento por mi comunidad.', '2025-11-22 02:35:14'),
(4, 'Lorenzo', 'Perez', 'lorenzoperez@uabc.edu.mx', 'lorenzop12', 'maestro', 'Masculino', NULL, NULL, '2025-11-22 02:35:14'),
(5, 'Pepe', 'Ramos', 'Pepe12@uabc.edu.mx', 'pepillo1', 'maestro', 'Masculino', NULL, NULL, '2025-11-22 02:35:14'),
(7, 'Pedro', 'Hernandez', 'pedro123@uabc.edu.mx', 'pedropedro', 'maestro', 'Masculino', NULL, NULL, '2025-11-22 02:35:14'),
(8, 'Barbara', 'Mayoral', 'barbara@uabc.edu.mx', 'barbara123', 'alumno', 'Femenino', 'img/perfiles/21d20f6aa4099af497b7c92249400538.jpg', NULL, '2025-11-22 02:35:14'),
(9, 'Carlos', 'Martinez', 'charlymartinez@uabc.edu.mx', 'juan123', 'alumno', 'Masculino', NULL, NULL, '2025-11-22 02:35:14'),
(10, 'Lorena Arely', 'Valenzuela', 'lorenavalenzuela@uabc.edu.mx', 'lorena123', 'alumno', 'Masculino', NULL, NULL, '2025-11-22 02:35:14'),
(11, 'Juan', 'Perez', 'juanp@gmail.com', 'junap12', 'maestro', 'Masculino', NULL, NULL, '2025-11-25 18:21:35');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indices de la tabla `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `foro_respuestas`
--
ALTER TABLE `foro_respuestas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tema_id` (`tema_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `foro_temas`
--
ALTER TABLE `foro_temas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `alumno_id` (`alumno_id`);

--
-- Indices de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `alumno_id` (`alumno_id`,`curso_id`),
  ADD KEY `curso_id` (`curso_id`);

--
-- Indices de la tabla `modulos_curso`
--
ALTER TABLE `modulos_curso`
  ADD PRIMARY KEY (`id`),
  ADD KEY `curso_id` (`curso_id`);

--
-- Indices de la tabla `recetas`
--
ALTER TABLE `recetas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `recetas_favoritas`
--
ALTER TABLE `recetas_favoritas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`,`receta_id`),
  ADD KEY `receta_id` (`receta_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `foro_respuestas`
--
ALTER TABLE `foro_respuestas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `foro_temas`
--
ALTER TABLE `foro_temas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modulos_curso`
--
ALTER TABLE `modulos_curso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `recetas`
--
ALTER TABLE `recetas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `recetas_favoritas`
--
ALTER TABLE `recetas_favoritas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `foro_respuestas`
--
ALTER TABLE `foro_respuestas`
  ADD CONSTRAINT `foro_respuestas_ibfk_1` FOREIGN KEY (`tema_id`) REFERENCES `foro_temas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD CONSTRAINT `inscripciones_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `modulos_curso`
--
ALTER TABLE `modulos_curso`
  ADD CONSTRAINT `modulos_curso_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `recetas_favoritas`
--
ALTER TABLE `recetas_favoritas`
  ADD CONSTRAINT `recetas_favoritas_ibfk_2` FOREIGN KEY (`receta_id`) REFERENCES `recetas` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
