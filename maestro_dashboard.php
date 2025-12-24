<?php
session_start();
define('DB_HOST', 'localhost');
define('DB_NAME', 'chefencuna');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . htmlspecialchars($e->getMessage()));
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'maestro') {
    if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'administrador') {
        header('Location: admin_dashboard.php');
        exit;
    }
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['LAST_ACTIVITY'])) {
    $_SESSION['LAST_ACTIVITY'] = time();
} else if (time() - $_SESSION['LAST_ACTIVITY'] > 1800) { 
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

$user_name   = $_SESSION['user_name'] ?? 'Maestro';
$user_id     = (int) $_SESSION['user_id']; 
$active_page = 'dashboard';

function safeFetchCount(PDO $pdo, string $sql, array $params = []): int {
    try {
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $val) {
            if (is_int($val)) {
                $stmt->bindValue($key, $val, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $val, PDO::PARAM_STR);
            }
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("safeFetchCount error: " . $e->getMessage());
        return 0;
    }
}

$sql_recetas = "SELECT COUNT(*) FROM recetas WHERE usuario_id = :user_id";
$total_recetas = safeFetchCount($pdo, $sql_recetas, [':user_id' => $user_id]);
$sql_cursos = "SELECT COUNT(*) FROM cursos WHERE instructor_id = :user_id";
$cursos_asignados = safeFetchCount($pdo, $sql_cursos, [':user_id' => $user_id]);

try {
    $sql_unanswered = "
        SELECT COUNT(*) 
        FROM foro_temas t
        LEFT JOIN foro_respuestas r ON t.id = r.tema_id
        WHERE r.id IS NULL
    ";
    $preguntas_pendientes = safeFetchCount($pdo, $sql_unanswered);
} catch (Exception $e) {
    $preguntas_pendientes = 0;
}

$puntuacion_promedio = 4.8; 
try {
    $sql_avg = "
        SELECT AVG(v.puntuacion) as avg_score
        FROM valoraciones v
        INNER JOIN recetas r ON v.receta_id = r.id
        WHERE r.usuario_id = :user_id
    ";
    $stmt = $pdo->prepare($sql_avg);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $avg = $stmt->fetchColumn();
    if ($avg !== false && $avg !== null) {
        $puntuacion_promedio = round((float)$avg, 1);
    }
} catch (PDOException $e) {
    error_log("Avg rating error: " . $e->getMessage());
    $puntuacion_promedio = 4.8;
}
$actividad_reciente = [
    [
        'tipo' => 'reseña',
        'descripcion' => 'Nueva reseña de 5 estrellas en tu receta "Pizza Margarita".',
        'tiempo' => 'hace 5 minutos',
        'icono' => 'fa-star',
        'color' => 'text-yellow-500',
        'link' => 'maestro_ver_receta.php?id=10'
    ],
    [
        'tipo' => 'pregunta',
        'descripcion' => 'Tienes una pregunta pendiente sobre el curso "Cocina Asiática".',
        'tiempo' => 'hace 30 minutos',
        'icono' => 'fa-comment-dots',
        'color' => 'text-accent',
        'link' => 'foro_ayuda.php'
    ],
    [
        'tipo' => 'curso',
        'descripcion' => '¡2 nuevos alumnos se han inscrito en tu curso "Repostería Avanzada"!',
        'tiempo' => 'hace 1 día',
        'icono' => 'fa-users',
        'color' => 'text-blue-500',
        'link' => 'maestro_curso_alumnos.php?id=1'
    ],
];

$consejos_rendimiento = [
    [
        'icono' => 'fas fa-exclamation-triangle',
        'titulo' => 'Curso sin Módulos',
        'texto' => 'Tu curso de "Panadería Artesanal" no tiene módulos cargados. ¡Los alumnos no podrán empezar!',
        'bg_color' => 'bg-red-50',
        'border_color' => 'border-red-400',
        'link' => 'maestro_editar_curso.php?id=7'
    ],
    [
        'icono' => 'fas fa-chart-line',
        'titulo' => 'Receta Destacada',
        'texto' => 'Tu receta de "Cochinita Pibil" ha tenido un aumento del 15% en visualizaciones esta semana.',
        'bg_color' => 'bg-green-50',
        'border_color' => 'border-green-400',
        'link' => 'maestro_analiticas.php?receta_id=11'
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>ChefEnCuna</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-dark': '#1e272e',
                        'primary-light': '#f4f7f6',
                        'accent': '#4ecdc4',
                        'secondary-red': '#ff6b6b',
                        'text-base': '#4a4a4a'
                    }
                }
            }
        }
    </script>
    <style>
        body { 
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; 
        }
        .nav-link.active { 
            background-color: #4ecdc4; 
            color: white; 
            border-radius: .5rem; 
        }
        .nav-link:not(.active):hover { 
            background-color: rgba(78,205,196,0.1); 
            color: #4ecdc4; 
            border-radius: .5rem; 
        }
    </style>
</head>
<body class="bg-primary-light text-text-base">
<div class="flex h-screen overflow-hidden">
    <aside class="w-64 bg-white shadow p-4 fixed h-full z-20">
        <div class="mb-6">
            <h1 class="text-2xl font-extrabold text-primary-dark">ChefEnCuna<span class="text-accent">.</span></h1>
            <p class="text-sm text-gray-500">Panel Maestro</p>
        </div>

        <nav class="space-y-2">
            <a href="maestro_dashboard.php" class="nav-link flex items-center p-3 font-semibold <?php echo ($active_page==='dashboard') ? 'active' : 'text-gray-600'; ?>">
                <i class="fas fa-th-large w-5 mr-3"></i> Dashboard
            </a>
            <div x-data="{open:false}">
                <button @click="open = !open" class="w-full flex items-center justify-between p-3 font-semibold text-gray-600">
                    <span class="flex items-center"><i class="fas fa-user-cog w-5 mr-3"></i> Gestión de Perfil</span>
                    <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
                <div x-show="open" class="ml-6 mt-2 space-y-1">
                    <a href="maestro_ver_perfil.php" class="block p-2 text-sm text-gray-600 hover:bg-gray-50 rounded">Ver Perfil Público</a>
                    <a href="maestro_perfil.php" class="block p-2 text-sm text-gray-600 hover:bg-gray-50 rounded">Editar Datos</a>
                    <a href="maestro_biografia.php" class="block p-2 text-sm text-gray-600 hover:bg-gray-50 rounded">Gestionar Biografía</a>
                </div>
            </div>

            <a href="maestro_recetas.php" class="flex items-center p-3 font-semibold text-gray-600"><i class="fas fa-utensils w-5 mr-3"></i> Mis Recetas</a>
            <a href="maestro_cursos.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 <?php echo ($active_page === 'cursos') ? 'active' : 'text-gray-600'; ?>">
                <i class="fas fa-book-open w-5 mr-3"></i>Mis Cursos <?php if ($cursos_asignados > 0): ?>
                    <span class="ml-auto bg-blue-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                        <?php echo $cursos_asignados; ?>
                    </span>
                <?php endif; ?>
            </a>            
            <a href="foro_ayuda.php" class="flex items-center p-3 font-semibold text-gray-600"><i class="fas fa-comments w-5 mr-3"></i> Foro de Dudas</a>
            <a href="ayuda.php" class="flex items-center p-3 font-semibold text-gray-600"><i class="fas fa-question-circle w-5 mr-3"></i> Ayuda y Soporte</a>
        </nav>

        <div class="mt-auto pt-6">
            <a href="logout.php" class="flex items-center text-secondary-red font-medium hover:text-red-700">
                <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
            </a>
        </div>
    </aside>

    <div class="flex-1 ml-64 overflow-y-auto">
        <header class="bg-white shadow p-4 sticky top-0 z-10">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <h2 class="text-xl font-bold text-text-base">Bienvenido/a, <span class="text-accent"><?php echo htmlspecialchars($user_name); ?></span></h2>
                <a href="index.php" class="text-gray-500 hover:text-primary-dark"><i class="fas fa-home mr-1"></i> Ir a Inicio</a>
            </div>
        </header>

        <main class="p-6 md:p-10 max-w-7xl mx-auto">
            <div class="mb-8">
                <h1 class="text-4xl font-extrabold text-primary-dark mb-1">Panel del Maestro</h1>
                <p class="text-lg text-gray-500">Gestione su contenido y sus interacciones en la plataforma.</p>
            </div>

            <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <div class="bg-white p-6 rounded-xl shadow border-b-4 border-accent">
                    <div class="flex items-center justify-between">
                        <i class="fas fa-utensils text-4xl text-accent opacity-75"></i>
                        <span class="text-3xl font-extrabold text-primary-dark"><?php echo (int)$total_recetas; ?></span>
                    </div>
                    <p class="mt-3 text-sm text-gray-500 uppercase tracking-wider">Recetas Publicadas</p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow border-b-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <i class="fas fa-book-open text-4xl text-blue-500 opacity-75"></i>
                        <span class="text-3xl font-extrabold text-primary-dark"><?php echo (int)$cursos_asignados; ?></span>
                    </div>
                    <p class="mt-3 text-sm text-gray-500 uppercase tracking-wider">Cursos Asignados</p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow border-b-4 border-secondary-red">
                    <div class="flex items-center justify-between">
                        <i class="fas fa-comments text-4xl text-secondary-red opacity-75"></i>
                        <span class="text-3xl font-extrabold text-primary-dark"><?php echo (int)$preguntas_pendientes; ?></span>
                    </div>
                    <p class="mt-3 text-sm text-gray-500 uppercase tracking-wider">Preguntas Pendientes</p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow border-b-4 border-yellow-500">
                    <div class="flex items-center justify-between">
                        <i class="fas fa-star text-4xl text-yellow-500 opacity-75"></i>
                        <span class="text-3xl font-extrabold text-primary-dark"><?php echo number_format((float)$puntuacion_promedio, 1); ?></span>
                    </div>
                    <p class="mt-3 text-sm text-gray-500 uppercase tracking-wider">Puntuación Promedio</p>
                </div>
            </section>

            <section class="mb-12">
                <h2 class="text-2xl font-bold mb-4">Acciones Rápidas</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <a href="crear_receta.php" class="bg-white p-6 rounded-xl shadow text-center border hover:border-accent">
                        <div class="text-accent mb-3"><i class="fas fa-plus-circle text-4xl"></i></div>
                        <h3 class="text-lg font-semibold">Publicar Nueva Receta</h3>
                        <p class="text-sm text-gray-500 mt-1">Empiece a compartir su próximo plato.</p>
                    </a>

                    <a href="foro_ayuda.php" class="bg-white p-6 rounded-xl shadow text-center border hover:border-secondary-red">
                        <div class="text-secondary-red mb-3"><i class="fas fa-question-circle text-4xl"></i></div>
                        <h3 class="text-lg font-semibold">Responder Foro (<?php echo (int)$preguntas_pendientes; ?>)</h3>
                        <p class="text-sm text-gray-500 mt-1">Interaccione con su audiencia.</p>
                    </a>
                </div>
            </section>

            <section class="lg:flex lg:space-x-8">
                <div class="lg:w-1/2 bg-white p-6 rounded-xl shadow mb-6 lg:mb-0">
                    <h2 class="text-2xl font-bold mb-4">Actividad Reciente</h2>
                    <ul class="space-y-4">
                        <?php foreach ($actividad_reciente as $actividad): ?>
                        <li class="flex items-start p-3 hover:bg-gray-50 rounded-lg">
                            <div class="w-10 h-10 flex items-center justify-center rounded-full mr-4 bg-gray-100">
                                <i class="fas <?php echo $actividad['icono']; ?> <?php echo $actividad['color']; ?>"></i>
                            </div>
                            <div>
                                <p class="font-semibold"><?php echo htmlspecialchars($actividad['tipo']); ?>: <?php echo htmlspecialchars($actividad['descripcion']); ?></p>
                                <p class="text-xs text-gray-400"><?php echo htmlspecialchars($actividad['tiempo']); ?></p>
                            </div>
                        </li>
                        <?php endforeach; ?>
                        <li class="pt-4 text-center border-t mt-4">
                            <a href="maestro_notificaciones.php" class="text-accent font-medium">Ver todas las notificaciones &rarr;</a>
                        </li>
                    </ul>
                </div>

                <div class="lg:w-1/2 bg-white p-6 rounded-xl shadow">
                    <h2 class="text-2xl font-bold mb-4">Consejos y Rendimiento</h2>
                    <div class="space-y-4">
                        <?php foreach ($consejos_rendimiento as $consejo): ?>
                        <a href="<?php echo htmlspecialchars($consejo['link']); ?>" class="block">
                            <div class="p-4 rounded-lg <?php echo $consejo['bg_color']; ?> border-l-4 <?php echo $consejo['border_color']; ?>">
                                <p class="font-semibold"><?php echo htmlspecialchars($consejo['titulo']); ?></p>
                                <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($consejo['texto']); ?></p>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>
</body>
</html>