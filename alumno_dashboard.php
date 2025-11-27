<?php
session_start();

require 'config/bd.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'alumno') {
    header('Location: login.php');
    exit;
}

$alumno_id = $_SESSION['user_id'];
$nombre_alumno = htmlspecialchars($_SESSION['user_nombre'] ?? 'Estudiante');
$dashboard_url = "alumno_dashboard.php"; 

$cursos_inscritos = [];
$stats = [
    'completados' => 0,
    'en_progreso' => 0,
    'tiempo_estudio_horas' => 0,
    'ultimo_curso_titulo' => 'N/A',
];
$error_bd = null;
$error_bd_rec = null;

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

$recomendaciones = [];
try {
    if ($pdo instanceof PDO && $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
        $stmt_enrolled_ids = $pdo->prepare("SELECT curso_id FROM inscripciones WHERE alumno_id = ?");
        $stmt_enrolled_ids->execute([$alumno_id]);
        $enrolled_ids = $stmt_enrolled_ids->fetchAll(PDO::FETCH_COLUMN);

        $exclude_clause = '';
        $params = [];

        if (!empty($enrolled_ids)) {
            $placeholders = implode(',', array_fill(0, count($enrolled_ids), '?'));
            $exclude_clause = " AND id NOT IN ({$placeholders})";
            $params = $enrolled_ids;
        }

        $sql_recomendaciones = "
            SELECT id, titulo, nivel 
            FROM cursos 
            WHERE 1=1 {$exclude_clause} 
            ORDER BY id DESC LIMIT 3 
        ";
        
        $stmt_rec = $pdo->prepare($sql_recomendaciones);
        $stmt_rec->execute($params);
        $recomendaciones_db = $stmt_rec->fetchAll(PDO::FETCH_ASSOC);

        foreach ($recomendaciones_db as $curso) {
            $recomendaciones[] = [
                'id' => $curso['id'],
                'titulo' => htmlspecialchars($curso['titulo']), 
                'maestro' => 'Chef Invitado', 
                'nivel' => htmlspecialchars($curso['nivel']), 
                'imagen' => 'img/cursos/' . $curso['id'] . '.jpg'
            ];
        }
    } else {
        $recomendaciones = [
            ['id' => 101, 'titulo' => 'Cocina Mexicana Avanzada', 'maestro' => 'Chef Invitado', 'nivel' => 'Avanzado', 'imagen' => 'img/cursos/101.jpg'],
            ['id' => 102, 'titulo' => 'Postres Clásicos', 'maestro' => 'Chef Invitado', 'nivel' => 'Intermedio', 'imagen' => 'img/cursos/102.jpg'],
        ];
    }

} catch (PDOException $e) {
    $error_bd_rec = "No se pudieron cargar recomendaciones.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefEnCuna</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"> 
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script> 
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand-green': '#69A64A',   
                        'primary-accent': '#ff6b6b',  
                        'secondary': '#4ecdc4', 
                        'dark': '#2d3436',      
                        'light': '#f7f1e3',     
                        'white': '#ffffff',
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --primary: #69A64A;
            --secondary-color: #4ecdc4; 
            --dark: #2d3436;
            --light: #f7f1e3;
            --theme-green: #69A64A; 
        }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--light); 
        }
        .app-header { 
            background: var(--theme-green); 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
            position: sticky; 
            top: 0;
            z-index: 50;
        }
        .header-content {
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            width: 100%;
            max-width: 1600px; 
            margin: 0 auto;
            padding: 1rem 3rem; 
        }
        .logo { 
            font-size: 1.8rem; 
            font-weight: bold; 
            color: white; 
            text-decoration: none; 
        }
        .app-nav a {
            margin-left: 1.5rem;
            text-decoration: none;
            color: white; 
            font-weight: 500;
            transition: color 0.15s;
            white-space: nowrap; 
        }
        .app-nav a:hover { 
            color: var(--secondary-color); 
        }
        .btn-profile {
            background-color: var(--secondary-color); 
            color: white;
            transition: background-color 0.2s;
            border-radius: 9999px;
            font-weight: 600;
            padding: 0.5rem 1rem;
            display: inline-flex;
            align-items: center;
            margin-left: 1.5rem; 
        }
        .btn-profile:hover {
            background-color: #3aa69e; 
        }
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                padding: 1rem;
            }
            .app-nav {
                margin-top: 1rem;
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }
            .app-nav a {
                margin: 0;
            }
            .btn-profile {
                margin-left: 0.5rem !important;
                margin-right: 0.5rem !important;
            }
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
            background-color: #e2e8f0; /* Gris claro de Tailwind */
            border-radius: 9999px;
            height: 0.6rem;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        .progress-bar {
            height: 100%;
            background-color: var(--theme-green);
            border-radius: 9999px;
            transition: width 1s ease-in-out;
        }
    </style>
</head>
<body class="flex flex-col min-h-screen">
<header class="app-header">
    <div class="header-content">
        <a href="index.php" class="logo">ChefEnCuna 👨‍🍳</a>

        <nav class="app-nav flex items-center">
            <a href="index.php">Inicio</a>
            <a href="recetas.php">Recetas</a>
            <a href="cursos.php">Cursos</a>
            <a href="foro_ayuda.php">Foro</a>

            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="relative ml-4" x-data="{ open: false }" @click.away="open = false">
                    <button @click="open = !open" 
                        class="btn-profile flex items-center">
                        <i class="fas fa-user mr-2"></i> <?= htmlspecialchars($_SESSION['user_nombre']) ?>
                        <i class="fas fa-caret-down ml-2"></i>
                    </button>

                    <div x-show="open"
                        x-transition
                        class="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1
                               bg-[var(--theme-green)] text-white ring-1 ring-black ring-opacity-5">

                        <a href="<?= htmlspecialchars($dashboard_url) ?>"
                           class="block px-4 py-2 text-sm text-white hover:bg-green-700">
                            <i class="fas fa-gauge-high mr-2"></i> Mi perfil
                        </a>
                            
                        <a href="logout.php"
                           class="block px-4 py-2 text-sm text-white hover:bg-red-700 border-t border-white/25">
                            <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <div class="relative ml-4" x-data="{ open: false }" @click.away="open = false">
                    <button @click="open = !open" 
                        class="btn-access-style flex items-center">
                        <i class="fas fa-user-circle mr-2"></i> Acceso
                        <i class="fas fa-caret-down ml-2"></i>
                    </button>

                    <div x-show="open"
                        x-transition
                        class="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1
                               bg-[var(--theme-green)] text-white ring-1 ring-black ring-opacity-5">

                        <a href="login.php"
                           class="block px-4 py-2 text-sm text-white hover:bg-green-700">
                            <i class="fas fa-sign-in-alt mr-2"></i> Entrar
                        </a>
                            
                        <a href="registro.php"
                           class="block px-4 py-2 text-sm text-white hover:bg-green-700 border-t border-white/25">
                            <i class="fas fa-user-plus mr-2"></i> Registrarse
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </nav>
    </div>
</header>
    
    <main class="container mx-auto p-4 lg:p-10 flex-grow">
        
        <div class="mb-8">
            <h1 class="text-4xl font-extrabold text-dark mb-2">
                ¡Hola, <?php echo $nombre_alumno; ?>! 👋
            </h1>
            <p class="text-xl text-gray-500">
                Aquí tienes el resumen de tu viaje culinario.
            </p>
        </div>

        <?php if ($error_bd || $error_bd_rec): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded-lg shadow" role="alert">
                <p class="font-bold"><i class="fas fa-exclamation-triangle"></i> Atención:</p>
                <?php if($error_bd) echo "<p class='text-sm'>$error_bd</p>"; ?>
                <?php if($error_bd_rec) echo "<p class='text-sm'>$error_bd_rec</p>"; ?>
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
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-md border-l-4 border-brand-green">
                    <h2 class="text-2xl font-bold text-dark mb-4">Continuar Aprendiendo</h2>
                    <p class="text-gray-600 mb-4 text-lg">
                        Tu último curso activo fue: 
                        <span class="font-bold text-brand-green block text-xl mt-1">
                            <?php echo htmlspecialchars($stats['ultimo_curso_titulo']); ?>
                        </span>
                    </p>
                    <?php if ($stats['ultimo_curso_titulo'] !== 'N/A' && !empty($cursos_inscritos)): ?>
                        <a href="curso_detalle.php?id=<?php echo $cursos_inscritos[0]['id']; ?>" 
                           class="inline-flex items-center py-2 px-6 rounded-full bg-brand-green text-white font-bold hover:bg-green-700 transition duration-150 shadow-lg">
                            <i class="fas fa-play mr-2"></i> Retomar Curso
                        </a>
                    <?php else: ?>
                         <a href="cursos.php" class="inline-flex items-center py-2 px-6 rounded-full bg-secondary text-white font-bold hover:opacity-90 transition shadow-lg">
                            <i class="fas fa-search mr-2"></i> Buscar Cursos
                        </a>
                    <?php endif; ?>
                </div>

                <div class="lg:col-span-1 p-6 bg-white rounded-xl shadow-md">
                    <h2 class="text-xl font-bold text-dark mb-4">Acciones Rápidas</h2>
                    <div class="space-y-3">
                        
                        <a href="recetas_favoritas.php" class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition group">
                            <i class="fas fa-heart text-xl text-primary-accent group-hover:scale-110 transition-transform"></i>
                            <span class="font-medium text-gray-700">Mis Recetas Favoritas</span>
                        </a>

                        <a href="mis_certificados.php" class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition group">
                            <i class="fas fa-award text-xl text-yellow-500 group-hover:scale-110 transition-transform"></i>
                            <span class="font-medium text-gray-700">Mis Certificados</span>
                        </a>
                        <a href="foro_ayuda.php" class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition group">
                            <i class="fas fa-comments text-xl text-secondary group-hover:scale-110 transition-transform"></i>
                            <span class="font-medium text-gray-700">Foro y Ayuda</span>
                        </a>
                        <a href="perfil_ajustes.php" class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition group">
                            <i class="fas fa-cog text-xl text-gray-500 group-hover:scale-110 transition-transform"></i>
                            <span class="font-medium text-gray-700">Ajustes de Cuenta</span>
                        </a>
                    </div>
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
                    <img src="<?php echo $curso['imagen']; ?>" 
                         alt="<?php echo htmlspecialchars($curso['titulo']); ?>" 
                         class="w-full h-48 object-cover transform group-hover:scale-105 transition duration-500"
                         onerror="this.onerror=null;this.src='https://placehold.co/400x225/69A64A/ffffff?text=Curso+<?php echo htmlspecialchars($curso['id']); ?>';">
                    
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

        <?php if (!empty($recomendaciones)): ?>
        <section class="mt-16 pt-8 border-t border-gray-300">
            <h2 class="text-2xl font-bold text-dark mb-6">
                Te podría interesar <i class="fas fa-sparkles text-yellow-500 ml-2"></i>
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($recomendaciones as $curso): ?>
                <a href="ver_curso.php?id=<?php echo $curso['id']; ?>" class="card-course flex items-center p-4 hover:bg-gray-50 transition"> 
                    <img src="<?php echo $curso['imagen']; ?>" 
                         alt="<?php echo htmlspecialchars($curso['titulo']); ?>" 
                         class="w-24 h-24 object-cover rounded-lg shadow-sm"
                         onerror="this.onerror=null;this.src='https://placehold.co/96x96/4ecdc4/ffffff?text=Curso+<?php echo htmlspecialchars($curso['id']); ?>';">
                    
                    <div class="ml-4 flex-grow">
                        <span class="text-xs font-bold text-secondary uppercase tracking-wide"><?php echo htmlspecialchars($curso['nivel']); ?></span>
                        <h3 class="text-md font-bold text-dark leading-tight mb-1"><?php echo htmlspecialchars($curso['titulo']); ?></h3>
                        <p class="text-xs text-gray-500">Instructor Invitado</p>
                    </div>
                    <div class="text-brand-green">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </main>
</body>
</html>