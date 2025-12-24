<?php
session_start();

require 'config/bd.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'alumno') {
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


$alumno_id = $_SESSION['user_id'];
$nombre_alumno = htmlspecialchars($_SESSION['user_nombre'] ?? 'Estudiante');
$dashboard_url = "alumno_dashboard.php"; 
$active_page = 'dashboard'; 

$cursos_inscritos = [];
$stats = [
    'completados' => 0,
    'en_progreso' => 0,
    'tiempo_estudio_horas' => 0,
    'ultimo_curso_titulo' => 'N/A',
];
$error_bd = null;

if (!isset($pdo)) {
    try {
        $pdo = new PDO('sqlite::memory:'); 
    } catch (PDOException $e) {
    }
}

try {
    if ($pdo instanceof PDO && $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
        $stmt = $pdo->prepare("
            SELECT 
                c.id, 
                c.titulo, 
                c.nivel,
                c.descripcion,
                u.nombre AS maestro_nombre
            FROM inscripciones i
            JOIN cursos c ON i.curso_id = c.id
            LEFT JOIN usuarios u ON c.instructor_id = u.id
            WHERE i.alumno_id = ?
            ORDER BY i.fecha_inscripcion DESC
        ");
        $stmt->execute([$alumno_id]);
        $cursos_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tiempo_total_estudio = 0;
        
        foreach ($cursos_db as $curso) {
            $progreso_simulado = rand(10, 100); 
            $tiempo_estudio_curso = rand(1, 10); 
            
            $tiempo_total_estudio += $tiempo_estudio_curso;

            if ($progreso_simulado === 100) {
                $stats['completados']++;
            } else {
                $stats['en_progreso']++;
            }
            
            $cursos_inscritos[] = [
                'id' => $curso['id'],
                'titulo' => $curso['titulo'],
                'maestro' => htmlspecialchars($curso['maestro_nombre'] ?? 'Instructor No Asignado'),
                'progreso' => $progreso_simulado,
                'nivel' => $curso['nivel'],
                'imagen' => 'img/cursos/' . $curso['id'] . '.jpg' 
            ];
        }

        $stats['tiempo_estudio_horas'] = $tiempo_total_estudio;

        if (!empty($cursos_inscritos)) {
            $stats['ultimo_curso_titulo'] = $cursos_inscritos[0]['titulo'];
        }
    } else {
        $error_bd = "Advertencia: La conexión a la base de datos no está disponible o es solo de prueba (SQLite en memoria).";
        $cursos_inscritos = [
            ['id' => 1, 'titulo' => 'Introducción a la Repostería', 'maestro' => 'Chef Eva', 'progreso' => 75, 'nivel' => 'Básico', 'imagen' => 'img/cursos/1.jpg'],
            ['id' => 2, 'titulo' => 'Secretos de la Pizza Artesanal', 'maestro' => 'Chef Nico', 'progreso' => 100, 'nivel' => 'Intermedio', 'imagen' => 'img/cursos/2.jpg'],
        ];
        if (!empty($cursos_inscritos)) {
            $stats['ultimo_curso_titulo'] = $cursos_inscritos[0]['titulo'];
        }
    }

} catch (PDOException $e) {
    $error_bd = "Error al cargar cursos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefEnCuna - Panel del Alumno</title>
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
                        'text-base': '#4a4a4a', 

                        'brand-green': '#69A64A',   
                        'primary-accent': '#ff6b6b',  
                        'secondary': '#4ecdc4', 
                        'dark': '#2d3436',      
                        'light': '#f7f1e3',     
                        'white': '#ffffff',
                    },
                     boxShadow: {
                        'sidebar': '5px 0 15px rgba(0, 0, 0, 0.05)',
                        'card-hover': '0 8px 16px rgba(0, 0, 0, 0.1)',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: var(--primary-light); }
        .nav-link.active {
            background-color: #4ecdc4; 
            color: white;
            border-radius: 0.5rem;
        }
        .nav-link:not(.active):hover {
            background-color: rgba(78, 205, 196, 0.1); 
            color: #4ecdc4;
            border-radius: 0.5rem;
        }
        .stat-card {
            background-color: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .card-course {
            background-color: white;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #eee;
        }
        .card-course:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .progress-bar-container {
            width: 100%;
            background-color: #e2e8f0; 
            border-radius: 9999px;
            height: 0.6rem;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        .progress-bar {
            height: 100%;
            background-color: var(--brand-green); 
            border-radius: 9999px;
            transition: width 1s ease-in-out;
        }
    </style>
</head>
<body class="bg-primary-light text-text-base">
<div class="flex h-screen overflow-hidden">
    <aside class="w-64 bg-white shadow-sidebar flex flex-col fixed h-full z-20">
        
        <div class="p-6 border-b border-gray-100">
            <h1 class="text-2xl font-extrabold text-primary-dark tracking-wide">ChefEnCuna<span class="text-accent">.</span></h1>
            <p class="text-sm text-gray-500 mt-1">Panel Alumno</p>
        </div>

        <nav class="flex-grow p-4 space-y-2">
            
            <a href="alumno_dashboard.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 
                      <?php echo ($active_page === 'dashboard') ? 'active' : 'text-gray-600'; ?>">
                <i class="fas fa-th-large w-5 mr-3"></i>
                Mi Dashboard
            </a>

            <a href="cursos.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
                <i class="fas fa-book-open w-5 mr-3"></i>
                Explorar Cursos
            </a>

            <a href="recetas_favoritas.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
                <i class="fas fa-heart w-5 mr-3"></i>
                Recetas Favoritas
            </a>
            
            <a href="mis_certificados.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
                <i class="fas fa-award w-5 mr-3"></i>
                Mis Certificados
            </a>

            <a href="foro_ayuda.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
                <i class="fas fa-comments w-5 mr-3"></i>
                Foro y Ayuda
            </a>

            <a href="perfil_ajustes.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
                <i class="fas fa-cog w-5 mr-3"></i>
                Ajustes
            </a>
        </nav>

        <div class="p-6 border-t border-gray-100 mt-auto">
            <a href="logout.php" class="flex items-center text-secondary-red font-medium hover:text-red-700 transition duration-200">
                <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
            </a>
        </div>
    </aside>

    <div class="flex-1 overflow-y-auto ml-64">
        <header class="bg-white shadow-md p-4 sticky top-0 z-10">
             <div class="flex justify-between items-center max-w-7xl mx-auto">
                <h2 class="text-xl font-bold text-text-base">
                    Bienvenido/a, <span class="text-accent"><?php echo htmlspecialchars($nombre_alumno); ?></span>
                </h2>
                <a href="index.php" class="text-gray-500 hover:text-primary-dark transition duration-200 text-sm">
                    <i class="fas fa-home mr-1"></i> Ir a Inicio
                </a>
            </div>
        </header>
        <main class="p-4 lg:p-10">
            
            <div class="mb-8">
                <h1 class="text-4xl font-extrabold text-dark mb-2">
                    ¡Hola, <?php echo $nombre_alumno; ?>! 👋
                </h1>
                <p class="text-xl text-gray-500">
                    Aquí tienes el resumen de tu viaje culinario.
                </p>
            </div>

            <?php if ($error_bd): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded-lg shadow" role="alert">
                    <p class="font-bold"><i class="fas fa-exclamation-triangle"></i> Atención:</p>
                    <?php if($error_bd) echo "<p class='text-sm'>$error_bd</p>"; ?>
                </div>
            <?php endif; ?>

            <section class="mb-10">
                <h2 class="text-2xl font-bold text-dark mb-4 border-b-2 border-brand-green inline-block pb-1">Tu Progreso</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    
                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <i class="fas fa-trophy text-3xl text-yellow-500"></i>
                            <span class="text-4xl font-extrabold text-dark"><?php echo $stats['completados']; ?></span>
                        </div>
                        <p class="text-sm text-gray-500 mt-2 font-semibold">Cursos Completados</p>
                    </div>

                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <i class="fas fa-book-open text-3xl text-secondary"></i>
                            <span class="text-4xl font-extrabold text-dark"><?php echo $stats['en_progreso']; ?></span>
                        </div>
                        <p class="text-sm text-gray-500 mt-2 font-semibold">Cursos en Progreso</p>
                    </div>

                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <i class="fas fa-clock text-3xl text-blue-500"></i>
                            <span class="text-4xl font-extrabold text-dark"><?php echo $stats['tiempo_estudio_horas']; ?>h</span>
                        </div>
                        <p class="text-sm text-gray-500 mt-2 font-semibold">Horas de Estudio</p>
                    </div>

                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <i class="fas fa-fire-alt text-3xl text-orange-500"></i>
                            <span class="text-4xl font-extrabold text-dark">5</span>
                        </div>
                        <p class="text-sm text-gray-500 mt-2 font-semibold">Racha de Días</p>
                    </div>
                </div>
            </section>

            <section class="mb-10">
                <div class="grid grid-cols-1 gap-8">
                    <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-brand-green">
                        <h2 class="text-2xl font-bold text-dark mb-4">Continuar Aprendiendo</h2>
                        <p class="text-gray-600 mb-4 text-lg">
                            Tu último curso activo fue: 
                            <span class="font-bold text-brand-green block text-xl mt-1">
                                <?php echo htmlspecialchars($stats['ultimo_curso_titulo']); ?>
                            </span>
                        </p>
                        <?php if ($stats['ultimo_curso_titulo'] !== 'N/A' && !empty($cursos_inscritos)): ?>
                            <a href="curso_detalle.php?id=<?php echo $cursos_inscritos[0]['id']; ?>" class="inline-flex items-center py-2 px-6 rounded-full bg-brand-green text-white font-bold hover:bg-green-700 transition duration-150 shadow-lg">
                                <i class="fas fa-play mr-2"></i> Retomar Curso
                            </a>
                        <?php else: ?>
                            <a href="cursos.php" class="inline-flex items-center py-2 px-6 rounded-full bg-secondary text-white font-bold hover:opacity-90 transition shadow-lg">
                                <i class="fas fa-search mr-2"></i> Buscar Cursos
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <h2 class="text-3xl font-bold text-dark mb-6 border-b-2 border-brand-green pb-2 inline-block">
                Mis Cursos (<?php echo count($cursos_inscritos); ?>)
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                
                <?php foreach ($cursos_inscritos as $curso): ?>
                <div class="card-course flex flex-col h-full">
                    <a href="curso_detalle.php?id=<?php echo $curso['id']; ?>" class="block overflow-hidden relative group">
                        <img src="<?php echo $curso['imagen']; ?>" alt="<?php echo htmlspecialchars($curso['titulo']); ?>" class="w-full h-48 object-cover transform group-hover:scale-105 transition duration-500" onerror="this.onerror=null;this.src='https://placehold.co/400x225/69A64A/ffffff?text=Curso+<?php echo htmlspecialchars($curso['id']); ?>';">
                        <div class="absolute top-2 right-2 bg-white bg-opacity-90 px-2 py-1 rounded text-xs font-bold text-dark shadow">
                            <?php echo htmlspecialchars($curso['nivel']); ?>
                        </div>
                    </a>
                    
                    <div class="p-5 flex flex-col flex-grow">
                        <h3 class="text-lg font-bold text-dark mb-1 leading-tight">
                            <a href="curso_detalle.php?id=<?php echo $curso['id']; ?>" class="hover:text-brand-green transition">
                                <?php echo htmlspecialchars($curso['titulo']); ?>
                            </a>
                        </h3>
                        <p class="text-xs text-gray-500 mb-4">
                            <i class="fas fa-chalkboard-teacher mr-1 text-secondary"></i> 
                            <?php echo htmlspecialchars($curso['maestro']); ?>
                        </p>

                        <div class="mt-auto">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs font-semibold text-gray-600">Progreso</span>
                                <span class="text-xs font-bold text-brand-green"><?php echo $curso['progreso']; ?>%</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: <?php echo $curso['progreso']; ?>%;"></div>
                            </div>

                            <?php if ($curso['progreso'] < 100): ?>
                                <a href="curso_detalle.php?id=<?php echo $curso['id']; ?>" class="mt-4 block w-full py-2 text-center text-sm font-bold text-brand-green border border-brand-green rounded-full hover:bg-brand-green hover:text-white transition">
                                    Continuar
                                </a>
                            <?php else: ?>
                                <div class="mt-4 block w-full py-2 text-center text-sm font-bold text-white bg-green-500 rounded-full cursor-default">
                                    <i class="fas fa-check-circle mr-1"></i> Completado
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

            </div>
            <?php if (empty($cursos_inscritos)): ?>
                <div class="text-center p-10 bg-white rounded-xl shadow-md mt-6">
                    <i class="fas fa-cookie-bite text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-2xl font-semibold mb-2 text-gray-700">Aún no estás inscrito en cursos.</h3>
                    <p class="text-gray-500 mb-6">Empieza tu aventura culinaria explorando nuestro catálogo.</p>
                    <a href="cursos.php" class="py-3 px-8 rounded-full bg-secondary text-white font-bold text-lg hover:opacity-90 transition duration-150 shadow-lg">
                        <i class="fas fa-search mr-2"></i> Explorar Cursos
                    </a>
                </div>
            <?php endif; ?>
        </main>
    </div>
    </div>
</body>
</html>