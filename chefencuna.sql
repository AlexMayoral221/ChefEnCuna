-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 16-12-2025 a las 03:19:20
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
  `imagen_url` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`id`, `titulo`, `descripcion`, `nivel`, `requisitos`, `instructor_id`, `duracion`, `objetivos`, `imagen_url`, `fecha_creacion`) VALUES
(1, 'Repostería Avanzada', 'Curso intensivo para dominar técnicas de alta repostería. Incluye la elaboración de entremets, glaseados espejo, macarons, y el montaje de pasteles de boda y eventos, enfocándose en la estética y la perfección de acabados.', 'Avanzado', 'Experiencia comprobable en repostería básica e intermedia. Conocimiento de masas básicas, cremas madre y manejo de manga pastelera.', 5, '8 semanas, 40 horas', '1. Elaborar y perfeccionar 5 tipos de entremets y postres de vanguardia. \r\n2. Dominar el glaseado espejo y el montaje de pasteles de varios pisos. \r\n3. Crear rellenos y mousses con texturas y sabores complejos.', 'img/cursos/1764305239_69292957423f0.jpg', '2025-11-22 02:35:14'),
(2, 'Decoración de Pasteles', 'Curso práctico enfocado en el manejo avanzado de fondant y buttercream para crear diseños profesionales. Aprenderás a cubrir pasteles perfectamente, a trabajar con aerógrafo y a modelar figuras tridimensionales.', 'Intermedio', 'Manejo básico de manga pastelera y conocimiento de la consistencia de buttercream.', 4, '6 semanas, 30 horas', 'Cubrir pasteles cuadrados y redondos con fondant sin imperfecciones. Elaborar 5 técnicas de decoración con buttercream (rosetas, ruffles, etc.). Modelar figuras simples 3D para toppers.', 'img/cursos/1764305226_6929294a0b4bb.jpg', '2025-11-22 02:35:14'),
(3, 'Cocina Asiática', 'Explora los sabores clave de Asia con un enfoque en la técnica wok y el balance de sabores (dulce, salado, ácido y picante). Aprenderás a preparar Pad Thai, Pho vietnamita, sushi básico y las mejores sopas de ramen.', 'Intermedio', 'Manejo básico de cuchillo y wok. Se recomienda tener acceso a un mercado de ingredientes orientales.', 11, '8 semanas, 32 horas', 'Dominar el uso de salsa de pescado, tamarindo y dashi. Preparar y emplatar 8 platos tradicionales de la región. Entender y aplicar las técnicas básicas de cocción al vapor y salteado en wok.', 'img/cursos/1764305254_6929296602d86.jpg', '2025-11-22 02:35:14'),
(4, 'Cocina Saludable', 'Aprende a preparar platos completos, deliciosos y balanceados con alto valor nutricional. Cubriremos técnicas de cocción al vapor y al horno, sustitución de ingredientes (sin gluten, sin lácteos) y cómo organizar tu meal prep semanal.', 'Principiante', 'Ninguno.', 7, '4 semanas, 16 horas', 'Diseñar menús semanales balanceados (meal prep). Dominar las técnicas de cocción que conservan nutrientes. Preparar sustitutos saludables (aderezos, leches vegetales). Aprender a leer etiquetas nutricionales básicas.', 'img/cursos/1764305266_69292972b8e9b.jpg', '2025-11-22 02:35:14'),
(5, 'Curso Chef Junior', 'Curso diseñado para que los niños de 6 a 12 años aprendan a preparar recetas sencillas y creativas de forma segura. Fomentaremos hábitos saludables y la autonomía en la cocina a través de platos dulces y salados.', 'Principiante', 'Ninguno.', 2, '3 semanas, 9 horas', 'Identificar y aplicar 5 reglas básicas de seguridad e higiene. Preparar de manera autónoma 3 platos dulces y 3 salados. Familiarizarse con frutas y verduras y sus beneficios.', 'img/cursos/1764305281_6929298116da7.jpg', '2025-11-22 02:35:14'),
(6, 'Cocina Básica para Principiantes', 'Este curso intensivo está diseñado para quienes nunca han cocinado o tienen muy poca experiencia. Aprenderás desde cero las técnicas fundamentales (corte, cocción, preparación de salsas madre) y las recetas esenciales (arroces, pastas, caldos y postres sencillos). Al finalizar, tendrás la confianza para preparar comidas deliciosas y nutritivas en casa.', 'Principiante', 'Ninguno. Solo ganas de aprender y un buen cuchillo de chef.', 11, '4 semanas, 20 horas', 'Identificar y usar correctamente los utensilios básicos de cocina.\r\nDominar las técnicas de corte Julienne, Brunoise y Mirepoix.\r\nPreparar las 5 salsas madre de la cocina clásica (Béchamel, Velouté, Española, Holandesa y Tomate).\r\nElaborar al menos 8 platos completos (incluyendo una proteína, guarnición y un postre básico).\r\nImplementar prácticas de seguridad e higiene alimentaria en la cocina.', 'img/cursos/1764301261_692919cd55d21.webp', '2025-11-28 03:25:20'),
(7, 'Panadería Artesanal', 'Aprende la ciencia y la técnica detrás de panes rústicos. Cubriremos la creación y cuidado de la masa madre, amasado, fermentación y horneado de baguettes, panes integrales y pan de campo.', 'Intermedio', 'Experiencia básica en horneado.', 2, '6 semanas, 24 horas', 'Crear y mantener una masa madre viva y activa. Hornear 4 tipos de panes de corteza crujiente. Entender los tiempos de fermentación.', 'img/cursos/1764306614_69292eb611855.jpg', '2025-11-28 05:10:14'),
(8, 'Cocina Italiana', 'Sumérgete en la auténtica cocina italiana. Aprenderás a hacer la pasta desde cero (huevo y sémola), a preparar rellenos clásicos (ravioli, tortellini) y a cocinar las salsas más emblemáticas de Roma, Boloña y Nápoles.', 'Principiante', 'Ninguno.', NULL, '5 semanas, 20 horas', 'Preparar 3 tipos de masa de pasta fresca. Elaborar 5 salsas clásicas (Pesto, Ragú, Carbonara, Pomodoro). Cocinar platos completos como lasaña y tiramisú.', 'img/cursos/1764307062_69293076c6e53.jpg', '2025-11-28 05:11:28'),
(9, 'Cocina Mexicana y Colombiana', 'Domina las técnicas y los ingredientes esenciales de la gastronomía mexicana. Aprende a preparar tortillas caseras desde cero, salsas picantes, y platillos regionales como mole poblano y cochinita pibil.', 'Avanzado', 'Conocimientos de cocina básica y manejo de cuchillos.', NULL, '4 semanas, 18 horas', 'Ser capaz de preparar un menú completo con 5 platillos mexicanos tradicionales, diferenciar los tipos de chiles secos y húmedos, y elaborar salsas complejas.', 'img/cursos/1765080480_6934fda032ef9.webp', '2025-12-07 04:08:00');

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

--
-- Volcado de datos para la tabla `foro_respuestas`
--

INSERT INTO `foro_respuestas` (`id`, `tema_id`, `usuario_id`, `respuesta`, `fecha_respuesta`) VALUES
(3, 2, 1, 'proximamente habra mas recetas, no te preocupes', '2025-12-08 05:07:03');

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

--
-- Volcado de datos para la tabla `foro_temas`
--

INSERT INTO `foro_temas` (`id`, `alumno_id`, `titulo`, `contenido`, `etiqueta`, `fecha_creacion`) VALUES
(2, 8, 'Cocina Asiática', 'Que otra receta puedo hacer??', 'Receta', '2025-12-08 03:51:08');

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
(9, 2, 'Hamburguesas de pollo', 'Esta es una hamburguesa fresca, jugosa y llena de sabor, una excelente alternativa a la carne de res, ideal para una comida satisfactoria y reconfortante.', '300 g de pechuga de pollo deshuesada y sin piel (o muslo de pollo si prefieres más jugosidad).\r\n1/4 taza de pan molido (panko o pan rallado).\r\n1/4 taza de cebolla finamente picada.\r\n1 diente de ajo, machacado o muy finamente picado.\r\n1 cucharada de perejil fresco picado.\r\n1/2 cucharadita de sal.\r\n1/4 cucharadita de pimienta negra.\r\n1 cucharada de aceite de oliva o aceite vegetal.', 'Pica el pollo en trozos pequeños y luego muélelo en un procesador de alimentos hasta que tenga una consistencia de carne molida. Alternativamente, puedes pedirle a tu carnicero que te lo muela.\r\nEn un bol grande, combina la carne de pollo molida con el pan molido, la cebolla picada, el ajo, el perejil, la sal y la pimienta.\r\nMezcla bien con las manos hasta que todos los ingredientes estén integrados. No amases en exceso para evitar que la carne se endurezca.\r\nDivide la mezcla en porciones iguales (para el tamaño de tu pan) y forma discos de unos 1.5 cm de grosor. Haz una pequeña hendidura en el centro de cada disco con el pulgar para evitar que se abulten al cocinarse.\r\nEn un sartén grande (o parrilla), calienta el aceite de oliva a fuego medio.\r\nColoca las hamburguesas en el sartén caliente. Cocina durante 5-7 minutos por cada lado, o hasta que estén bien doradas y completamente cocidas en el centro (deben alcanzar una temperatura interna de 74 grados).\r\nDurante el último minuto de cocción, coloca una rebanada de queso sobre cada hamburguesa. Cubre el sartén brevemente para que el queso se derrita.\r\nCorta los panes de hamburguesa por la mitad. Tuesta ligeramente las caras internas en el mismo sartén o en un tostador.\r\nUnta tus salsas favoritas en ambas mitades del pan. \r\nColoca la lechuga en la base, seguida por la hamburguesa de pollo con queso.\r\nTermina con las rodajas de tomate y pepinillos (si los usas), y cubre con la parte superior del pan.', 'img/recetas/9.jpg', '2025-11-25 05:40:40', 'Pollos'),
(10, 2, 'Pizza Margarita', 'La receta original y sencilla de Nápoles. Una pizza con sabores frescos y colores que representan la bandera italiana: rojo del tomate, blanco de la mozzarella y verde de la albahaca. La base de toda gran pizza.', 'Aceite de oliva 40 gr.\r\nAgua 300 ml.\r\nHarina de fuerza o panificable 500 gr.\r\nSal 10 gr.\r\nAzúcar 5 gr. (para activar la levadura)\r\nLevadura fresca 12 gr. (o 4 gr. de levadura seca de panadería)\r\n\r\nSalsa de tomate natural triturado 200 gr. (o passata de tomate)\r\nMozzarella fresca (en bola) 250 gr. (bien escurrida)\r\nHojas de albahaca fresca\r\nAceite de oliva virgen extra (para terminar)', 'Disolver la levadura y el azúcar en el agua tibia. Dejar reposar durante 5-10 minutos hasta que comience a formar burbujas (esto indica que la levadura está activa).\r\n\r\nEn un bol grande, mezclar la harina y la sal. Hacer un hueco en el centro y añadir el agua con la levadura y el aceite de oliva.\r\n\r\nAmasar en una superficie enharinada durante 10-15 minutos hasta obtener una masa elástica y lisa. Debe despegarse de las manos.\r\n\r\nColocar la masa en un bol aceitado, cubrir con un paño húmedo y dejar levar en un lugar cálido durante 1 a 2 horas, o hasta que duplique su tamaño.\r\n\r\nPrecalentar el horno a la temperatura máxima (220-250°C) con la bandeja o piedra de pizza dentro si tienes.\r\n\r\nDividir la masa en 2-3 porciones (dependiendo del tamaño deseado) y estirarla con suavidad hasta obtener la forma de pizza deseada.\r\n\r\nMontar la pizza: Untar la salsa de tomate sobre la masa, dejando un borde. Añadir trozos de mozzarella fresca (bien escurrida) y un chorrito de aceite de oliva.\r\n\r\nHornear durante 8-15 minutos (el tiempo varía por horno) hasta que el borde esté dorado e hinchado y el queso burbujee.\r\n\r\nAl sacar del horno, decorar inmediatamente con hojas de albahaca fresca y un chorrito final de aceite de oliva virgen extra.', 'img/recetas/10.jpg', '2025-12-06 17:02:20', 'Italiana'),
(11, 2, 'Cochinita Pibil', 'La Cochinita Pibil es un platillo yucateco de carne de cerdo marinada en achiote, jugo de naranja agria y especias. Tradicionalmente cocida bajo tierra (pib), se logra una carne tierna y jugosa ideal para tacos. Esta versión adapta la cocción a horno o olla lenta.', '1 kg de carne de cerdo (pierna o paleta).\r\n100 g de pasta de achiote.\r\n1/2 taza de jugo de naranja agria (o 1/4 taza naranja + $/4 taza vinagre).\r\n1/2 taza de líquido (agua o caldo).\r\n4 dientes de ajo.\r\nEspecias: Comino, orégano, pimienta negra, clavo (una pizca de cada uno).\r\nSal al gusto.\r\nHojas de plátano (opcional).\r\n\r\n2 cebollas moradas, en juliana.\r\nVinagre y agua (partes iguales para encurtir).\r\nOrégano y Sal.\r\nChile habanero (opcional).', 'Disuelve la pasta de achiote en el jugo de naranja agria, agua/caldo, ajo y todas las especias/sal.\r\nCubre los trozos de carne de cerdo con la marinada. Refrigera por 8 horas o toda la noche.\r\nPrecalienta el horno a 160°C (320°F).\r\nForra un recipiente con hojas de plátano (si usas).\r\nColoca la carne y la marinada en el recipiente, cubre con las hojas y luego con papel de aluminio.\r\nHornea por 3 a 4 horas, hasta que la carne esté muy tierna.\r\nCebolla Encurtida\r\nMezcla vinagre, agua hirviendo, sal y orégano.\r\nAgrega la cebolla morada y el habanero. Deja reposar por 30 minutos.\r\nDeshebra la carne dentro de su jugo de cocción.\r\nSirve la cochinita caliente, acompañada de la cebolla encurtida.', 'img/recetas/11.jpg', '2025-12-07 04:50:40', 'Mexicana');

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
(3, 8, 8, '2025-11-26 18:42:46'),
(6, 9, 3, '2025-12-10 06:29:21');

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
(4, 'Lorenzo', 'Perez', 'lorenzoperez@uabc.edu.mx', 'lorenzop12', 'maestro', 'Masculino', 'img/perfiles/perfil_4.jpg', NULL, '2025-11-22 02:35:14'),
(5, 'Pepe', 'Ramos', 'Pepe12@uabc.edu.mx', 'pepito12', 'maestro', 'Masculino', 'img/perfiles/perfil_5.jpg', NULL, '2025-11-22 02:35:14'),
(7, 'Pedro', 'Hernandez', 'pedro123@uabc.edu.mx', 'pedrito123', 'maestro', 'Masculino', 'img/perfiles/perfil_7.jpg', NULL, '2025-11-22 02:35:14'),
(8, 'Barbara', 'Mayoral', 'barbara@uabc.edu.mx', 'barbara123', 'alumno', 'Femenino', 'img/perfiles/21d20f6aa4099af497b7c92249400538.jpg', NULL, '2025-11-22 02:35:14'),
(9, 'Carlos', 'Martinez', 'charlymartinez@uabc.edu.mx', 'carlitos12', 'alumno', 'Masculino', 'img/perfiles/00ddeae1dff18640dbcaed59830011ffa83e561cf877d35949cc26146c54665f.jpg', NULL, '2025-11-22 02:35:14'),
(10, 'Lorena Arely', 'Valenzuela', 'lorenavalenzuela@uabc.edu.mx', 'lorena123', 'alumno', 'Masculino', NULL, NULL, '2025-11-22 02:35:14'),
(11, 'Alicia', 'Perez', 'aliciap@gmail.com', 'aliciap12', 'maestro', 'Femenino', 'img/perfiles/perfil_11.jpg', NULL, '2025-11-25 18:21:35');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `foro_respuestas`
--
ALTER TABLE `foro_respuestas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `foro_temas`
--
ALTER TABLE `foro_temas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `recetas_favoritas`
--
ALTER TABLE `recetas_favoritas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
