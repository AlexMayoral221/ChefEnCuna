<?php
session_start();
require 'config/bd.php'; 
require 'config/header.php'; 

$pdo = $pdo ?? null; 
$dashboard_url = "dashboard.php"; 
$ultimas_recetas = [];
$ultimos_cursos = []; 
$instructores = [];
$error_db = '';
$perfil = null;
$perfil_error_msg = ''; 
$cursos_impartidos = []; 

if ($pdo && isset($_SESSION['user_id']) && !isset($_SESSION['user_nombre'])) {
    try {
        $stmt = $pdo->prepare("SELECT nombre, apellido, rol FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($usuario) {
            $_SESSION['user_nombre'] = $usuario['nombre'];
            $_SESSION['user_rol'] = $usuario['rol'];
            $_SESSION['user_apellido'] = $usuario['apellido'];
        }
    } catch (PDOException $e) {
        error_log("Error al cargar datos del usuario: " . $e->getMessage());
    }
}

if (isset($_SESSION['user_rol'])) {
    switch ($_SESSION['user_rol']) {
        case 'maestro': $dashboard_url = "maestro_dashboard.php"; break;
        case 'alumno': $dashboard_url = "alumno_dashboard.php"; break;
        case 'administrador': $dashboard_url = "admin_dashboard.php"; break;
        default: $dashboard_url = "dashboard.php"; break;
    }
}

function getRolDisplay($rol) {
    switch ($rol) {
        case 'maestro': return 'Maestro / Instructor';
        case 'alumno': return 'Alumno / Estudiante';
        case 'administrador': return 'Administrador del Sitio';
        default: return 'Usuario Registrado';
    }
}

function getRolColor($rol) {
    switch ($rol) {
        case 'maestro': return 'bg-yellow-100 text-yellow-800';
        case 'alumno': return 'bg-blue-100 text-blue-800';
        case 'administrador': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getProfileImage($perfil) {
    $nombre = htmlspecialchars($perfil['nombre'] ?? '');
    $apellido = htmlspecialchars($perfil['apellido'] ?? '');
    
    $initials = strtoupper(
        substr($nombre, 0, 1) . 
        (isset($apellido[0]) ? substr($apellido, 0, 1) : '')
    );
    
    $placeholder_text = urlencode($initials ?: 'NN'); 

    $src = !empty($perfil['foto_perfil']) ? htmlspecialchars($perfil['foto_perfil']) : "https://placehold.co/150x150/4ecdc4/ffffff?text={$placeholder_text}";
    
    $fallback_src = "https://placehold.co/150x150/4ecdc4/ffffff?text={$placeholder_text}";

    return [
        'src' => $src,
        'fallback_src' => $fallback_src,
        'alt' => "Foto de perfil de {$nombre} {$apellido}"
    ];
}

if ($pdo && isset($_GET['profile_id']) && is_numeric($_GET['profile_id'])) {
    $user_id = (int)$_GET['profile_id']; 

    try {
        $stmt = $pdo->prepare("SELECT id, nombre, apellido, email, rol, bio, foto_perfil, 
                                DATE_FORMAT(fecha_registro, '%d/%m/%Y') as registro 
                                FROM usuarios 
                                WHERE id = ?");
        $stmt->execute([$user_id]);
        $perfil = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($perfil) {
            if ($perfil['rol'] === 'maestro') {
                $stmtCursos = $pdo->prepare("SELECT id, titulo, nivel FROM cursos WHERE instructor_id = ? ORDER BY fecha_creacion DESC LIMIT 5"); 
                $stmtCursos->execute([$user_id]);
                $cursos_impartidos = $stmtCursos->fetchAll(PDO::FETCH_ASSOC);
            }
        } else {
            $perfil_error_msg = "Usuario no encontrado.";
        }
        
    } catch (PDOException $e) {
        $perfil_error_msg = "Error de base de datos al cargar el perfil: " . $e->getMessage();
        error_log("Error de DB en Perfil: " . $e->getMessage());
    }
}

if ($pdo) {
    try {
        $stmtRecetas = $pdo->query("SELECT r.id, r.titulo, r.descripcion, r.imagen_ruta, u.nombre as autor 
                                     FROM recetas r 
                                     JOIN usuarios u ON r.usuario_id = u.id 
                                     ORDER BY r.fecha_publicacion DESC LIMIT 3");
        $ultimas_recetas = $stmtRecetas->fetchAll(PDO::FETCH_ASSOC);

        
        $stmtCursos = $pdo->query("SELECT c.id, c.titulo, c.descripcion, c.nivel, c.imagen_url, u.nombre as instructor_nombre 
                                    FROM cursos c 
                                    LEFT JOIN usuarios u ON c.instructor_id = u.id 
                                    ORDER BY c.fecha_creacion DESC LIMIT 3");
        $ultimos_cursos = $stmtCursos->fetchAll(PDO::FETCH_ASSOC);

        $stmtInstructores = $pdo->query("SELECT id, nombre, apellido, foto_perfil FROM usuarios WHERE rol='maestro' ORDER BY RAND() LIMIT 3");
        $instructores = $stmtInstructores->fetchAll(PDO::FETCH_ASSOC);

        while (count($instructores) < 3) {
            $instructores[] = [
                'id' => 0,
                'nombre' => 'Instructor',
                'apellido' => 'Próximamente',
                'foto_perfil' => '' 
            ];
        }

    } catch (PDOException $e) {
        $error_db = "Error al cargar contenido de la base de datos. Por favor, revisa la conexión.";
        error_log("Error de DB en Contenido Principal: " . $e->getMessage());
    }
} else {
    $error_db = "Error: Conexión a la base de datos no inicializada correctamente. Revisa 'config/bd.php'.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefEnCuna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="//unpkg.com/alpinejs" defer></script> 
    <style>
        :root {
            --primary: #ffffff;
            --secondary: #4ecdc4;
            --dark: #2d3436;
            --light: #f7f1e3;
            --theme-green: #69A64A;
            --hero-bg: #2d3436; 
        }
        body { 
            font-family: 'Inter', sans-serif; 
            color: var(--dark); 
            background-color: var(--light); 
            min-height: 100vh;
            display: flex; 
            flex-direction: column; 
        }
        .hero-section {
            background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('img/fondo.jpg');
            background-size: cover; 
            background-position: center; 
            background-repeat: no-repeat;
            min-height: 450px; 
            color: var(--primary);
            text-align: center; 
            padding: 60px 20px;
            box-shadow: inset 0 -5px 10px rgba(0, 0, 0, 0.5); 
        }
        .hero-section h1, .hero-section p {
            color: var(--primary);
        }
        .cta-button {
            background-color: var(--secondary); 
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 9999px;
            font-size: 1.125rem;
            text-decoration: none;
            transition: background-color 0.2s, transform 0.1s;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .cta-button:hover {
            background-color: #3aa69e;
            transform: translateY(-2px);
        }
        .container {
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 0 20px; 
            flex-grow: 1; 
            padding-top: 40px;
        }
        h2.section-title { 
            text-align: center; 
            margin-top: 0; 
            margin-bottom: 40px; 
            color: var(--dark);
            position: relative; 
        }
        h2.section-title::after { 
            content: ''; 
            display: block; 
            width: 50px; 
            height: 3px; 
            background: var(--theme-green);
            margin: 10px auto; 
        }
            .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; }
            .card { border: 1px solid #eee; border-radius: 10px; overflow: hidden; transition: transform 0.3s ease, box-shadow 0.3s; box-shadow: 0 5px 15px rgba(0,0,0,0.05); background-color: white; display: flex; flex-direction: column;}
            .card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
            .card-img { height: 200px; object-fit: cover; width: 100%; background: #f0f0f0; } 
            .card-body { padding: 20px; flex-grow: 1; display: flex; flex-direction: column;}
            .card-title { margin: 0 0 10px 0; font-size: 1.25rem; color: var(--dark); }
            .card-text { font-size: 0.95rem; color: #666; line-height: 1.5; }
            .card-footer { padding: 15px 20px; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        
        .course-meta span {
            display: block;
            margin-bottom: 5px;
            color: var(--dark);
            font-size: 0.9rem;
        }
        .course-meta i {
            color: var(--theme-green);
            margin-right: 5px;
        }
        .course-level-tag {
            font-weight: bold;
        }
        .img-resize {
            width: 100%;
            height: 150px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        .instructor-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            background-color: white;
            transition: background-color 0.2s;
            height: 100%;
            justify-content: space-between;
        }
        .instructor-card:hover {
            background-color: #f0fff0;
        }
        .instructor-card img {
            width: 120px;
            height: 120px;
            border-radius: 50%; 
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid var(--secondary);
        }
        .well {
            background-color: #fcfcfc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            text-align: center;
        }
        .btn-social-icon {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: white;
            font-size: 1.2rem;
            transition: opacity 0.2s, transform 0.1s;
        }
        .btn-social-icon:hover {
            opacity: 0.8;
            transform: scale(1.05);
        }
        .btn-facebook { background-color: #3b5998; }
        .btn-instagram { background: radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%); }
        .btn-youtube { background-color: #ff0000; }
        @media (max-width: 768px) {
            .hero-section {
                padding: 40px 10px;
                min-height: 350px; 
            }
            .hero-section h1 {
                font-size: 2rem !important;
            }
            .hero-section p {
                font-size: 1.2rem !important;
            }
        }
        footer { 
            background: var(--dark); 
            color: white; 
            text-align: center; 
            padding: 20px; 
            margin-top: 50px; 
        }
        footer a { color: var(--secondary); text-decoration: none; margin: 0 10px; }
        footer a:hover { text-decoration: underline; }        
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.75);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            overflow-y: auto; 
            padding: 20px;
        }
        .modal-content {
            background-color: var(--primary);
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            max-width: 900px;
            width: 100%;
            position: relative;
            margin: auto; 
            max-height: 90vh; /* NUEVO: Limita la altura máxima al 90% del viewport */
            overflow-y: auto; /* NUEVO: Habilita el desplazamiento vertical si es necesario */
        }
        .profile-card {
            padding: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--secondary);
            margin-bottom: 20px;
        }
        .tag-rol {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 0.85rem;
            margin-top: 10px;
            opacity: 0.9;
        }
        .bio-box {
            background-color: var(--light);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            text-align: left;
            width: 100%;
            max-width: 450px; 
        }
        .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            transition: color 0.15s;
            padding: 5px;
        }
        .close-button:hover {
            color: #ff4757;
        }
        @media (min-width: 768px) {
            .profile-card {
                flex-direction: row;
                text-align: left;
                align-items: flex-start;
                padding: 40px;
            }
            .profile-card-header {
                flex-shrink: 0;
                margin-right: 30px;
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .profile-img {
                margin-bottom: 0;
            }
            .bio-box {
                margin-top: 0;
            }
        }
    </style>
</head>
<body>
<?php if ($perfil || $perfil_error_msg): ?>
<div class="modal-overlay" x-data="{ open: true }">
    <div class="modal-content" @click.outside="window.history.back() || window.location.replace('index.php')">
        <main class="py-10">
            <?php if ($perfil_error_msg): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative max-w-lg mx-auto" role="alert">
                    <strong class="font-bold">Error al cargar perfil:</strong>
                    <span class="block sm:inline"><?= htmlspecialchars($perfil_error_msg) ?></span>
                </div>
            <?php elseif ($perfil): 
                $imgData = getProfileImage($perfil);?>
                <div class="profile-card max-w-4xl mx-auto">
                    <div class="profile-card-header">
                        <img src="<?= $imgData['src'] ?>" alt="<?= $imgData['alt'] ?>" class="profile-img" onerror="this.onerror=null; this.src='<?= $imgData['fallback_src'] ?>';" >
                        <h1 class="text-3xl font-bold mt-2 text-gray-800">
                            <?= htmlspecialchars($perfil['nombre'] . ' ' . $perfil['apellido']) ?>
                        </h1>
                        
                        <span class="tag-rol <?= getRolColor($perfil['rol']) ?>">
                            <i class="fas fa-tag mr-1"></i> <?= getRolDisplay($perfil['rol']) ?>
                        </span>
                        
                    </div>
                    <div class="mt-5 md:mt-0 w-full">
                        <h2 class="text-2xl font-semibold mb-3 border-b pb-2 text-gray-700">Acerca de Mí</h2>
                        <div class="bio-box">
                            <p class="text-gray-600 leading-relaxed">
                                <?= !empty($perfil['bio']) ? nl2br(htmlspecialchars($perfil['bio'])) : (getRolDisplay($perfil['rol']) . ' sin biografía aún.') ?>
                            </p>
                        </div>

                        <div class="mt-5 text-gray-600 text-sm md:text-base md:text-left">
                            <p class="mb-2">
                                <i class="fas fa-envelope mr-2 text-[var(--theme-green)]"></i>
                                <span class="font-semibold">Email:</span> <?= htmlspecialchars($perfil['email']) ?>
                            </p>
                            <p>
                                <i class="fas fa-calendar-alt mr-2 text-[var(--theme-green)]"></i>
                                <span class="font-semibold">Miembro desde:</span> <?= htmlspecialchars($perfil['registro']) ?>
                            </p>
                        </div>

                        <?php if ($perfil['rol'] === 'maestro'): ?>
                            <div class="mt-8">
                                <h3 class="text-xl font-semibold mb-3 text-gray-700">Cursos Impartidos</h3>
                                
                                <?php if (!empty($cursos_impartidos)): ?>
                                    <ul class="space-y-3 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                                        <?php foreach ($cursos_impartidos as $curso): ?>
                                            <li class="flex items-center justify-between p-3 bg-white rounded-md shadow-sm">
                                                <span class="text-gray-700 font-medium">
                                                    <i class="fas fa-book-open text-[var(--theme-green)] mr-2"></i>
                                                    <?= htmlspecialchars($curso['titulo']) ?>
                                                    <span class="ml-3 text-xs font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800"><?= htmlspecialchars($curso['nivel']) ?></span>
                                                </span>
                                                <a href="ver_curso.php?id=<?= htmlspecialchars($curso['id']) ?>" class="text-sm font-semibold text-[var(--secondary)] hover:text-[#3aa69e] transition-colors">
                                                    Ver <i class="fas fa-chevron-right text-xs ml-1"></i>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-400 text-sm text-gray-600">
                                        <p><i class="fas fa-info-circle mr-2 text-yellow-500"></i>Este instructor aún no tiene cursos públicos listados.</p>
                                    </div>
                                <?php endif; ?>
                                
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php endif; ?>
        
<section class="hero-section">
    <div style="max-width: 800px; margin: 0 auto;">
        
        <img src="img/logo.png" alt="Logo ChefEnCuna" style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; margin: 0 auto 20px; display: block; filter: drop-shadow(0 5px 15px rgba(0,0,0,0.3));" onerror="this.onerror=null; this.src='https://placehold.co/150x150/4ecdc4/ffffff?text=LOGO';">
        <h1 style="font-size: 3rem; margin-bottom: 10px;">Tu hogar digital para la cocina.</h1>
        <p class="text-xl mb-6" style="font-size: 1.5rem; margin-bottom: 30px;">Únete a la comunidad culinaria más grande.</p>

        <?php if(!isset($_SESSION['user_id'])): ?>
            <a href="registro.php" class="cta-button">
                Comienza a Cocinar Hoy <i class="fas fa-arrow-right ml-2"></i>
            </a>
        <?php endif; ?>
    </div>
</section>

<div class="container">
    <?php if ($error_db): ?>
        <p class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative text-center mb-8 font-semibold"><?= htmlspecialchars($error_db) ?></p>
    <?php endif; ?>

    <h2 class="section-title">Recetas Frescas</h2>
    <div class="grid">
        <?php if (empty($ultimas_recetas)): ?>
            <p class="col-span-full text-center text-gray-500">No hay recetas disponibles en este momento.</p>
        <?php else: ?>
        <?php foreach ($ultimas_recetas as $receta): 
            $recipe_image_path = !empty($receta['imagen_ruta']) ? 
                                htmlspecialchars($receta['imagen_ruta']) : 
                                "img/recetas/" . htmlspecialchars($receta['id']) . ".jpg";?>

            <div class="card">
                <img src="<?= $recipe_image_path ?>" class="card-img" alt="Imagen de la receta: <?= htmlspecialchars($receta['titulo']) ?>" onerror="this.onerror=null; this.src='https://placehold.co/400x200/ff6b6b/ffffff?text=Receta+<?= htmlspecialchars($receta['id']) ?>'">
                <div class="card-body">
                    <h3 class="card-title"><?= htmlspecialchars($receta['titulo']) ?></h3>
                    
                    <p class="card-text mb-4"><?= substr(htmlspecialchars($receta['descripcion']), 0, 100) ?>...</p>

                    <div class="course-meta mt-auto">                        
                        <span>
                            <i class="fas fa-user"></i> Autor:
                            <b><?= htmlspecialchars($receta['autor']) ?></b>
                        </span>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="ver_receta.php?id=<?= htmlspecialchars($receta['id']) ?>" class="py-2 px-4 rounded font-semibold text-white transition-colors" style="background-color:var(--theme-green); color: white;">
                        Ver Receta
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <h2 class="section-title" style="margin-top:60px;">¿Por Qué Elegir ChefEnCuna?</h2>
    <div class="grid mb-10 md:grid-cols-3 gap-8">
        
        <div class="p-6 bg-white border border-gray-100 rounded-xl shadow-lg text-center transition-transform hover:scale-[1.02]">
            <i class="fas fa-utensils text-5xl text-[var(--theme-green)] mb-4"></i>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Miles de Recetas</h3>
            <p class="text-gray-600">Explora recetas de todos los niveles, desde platos rápidos hasta cocina de alta escuela. Filtra por dificultad, tiempo o ingredientes.</p>
            <a href="recetas.php" class="inline-block mt-4 text-sm font-semibold text-[var(--secondary)] hover:underline">
                Explorar Recetas <i class="fas fa-arrow-right text-xs ml-1"></i>
            </a>
        </div>
        
        <div class="p-6 bg-white border border-gray-100 rounded-xl shadow-lg text-center transition-transform hover:scale-[1.02]">
            <i class="fas fa-graduation-cap text-5xl text-[var(--secondary)] mb-4"></i>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Aprende de Maestros</h3>
            <p class="text-gray-600">Únete a nuestros cursos guiados por chefs profesionales. Mejora tus técnicas, domina nuevos estilos y obtén tu certificación.</p>
            <a href="cursos.php" class="inline-block mt-4 text-sm font-semibold text-[var(--secondary)] hover:underline">
                Ver Cursos <i class="fas fa-arrow-right text-xs ml-1"></i>
            </a>
        </div>

        <div class="p-6 bg-white border border-gray-100 rounded-xl shadow-lg text-center transition-transform hover:scale-[1.02]">
            <i class="fas fa-users text-5xl text-red-500 mb-4"></i>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Comunidad y Soporte</h3>
            <p class="text-gray-600">Conéctate con otros entusiastas. Comparte tus creaciones, haz preguntas y recibe consejos de una comunidad apasionada.</p>
            <a href="foro_ayuda.php" class="inline-block mt-4 text-sm font-semibold text-[var(--secondary)] hover:underline">
                Únete Ahora <i class="fas fa-arrow-right text-xs ml-1"></i>
            </a>
        </div>
    </div>

    <h2 class="section-title" style="margin-top:60px;">Nuestros Últimos Cursos</h2>
    <div class="grid">
        <?php if (empty($ultimos_cursos)): ?>
            <p class="col-span-full text-center text-gray-500">No hay cursos disponibles en este momento.</p>
        <?php endif; ?>
        <?php foreach($ultimos_cursos as $curso): 
            $course_title_encoded = urlencode(htmlspecialchars($curso['titulo']));
            $course_image_url_db = htmlspecialchars($curso['imagen_url'] ?? ''); 
            $final_image_source = !empty($course_image_url_db) ? $course_image_url_db : "img/cursos/{$curso['id']}.jpg";
            $fallback_placeholder = "https://placehold.co/400x200/4ecdc4/1f2937?text=Curso%20{$course_title_encoded}";?>
            <div class="card">
                <img src="<?= $final_image_source ?>" class="card-img" alt="Imagen del Curso" onerror="this.onerror=null; this.src='<?= $fallback_placeholder ?>'">

                <div class="card-body">
                    <h3 class="card-title"><?= htmlspecialchars($curso['titulo']) ?></h3>
                    <p class="card-text mb-4"><?= substr(htmlspecialchars($curso['descripcion']), 0, 100) ?>...</p>
                    
                    <div class="course-meta mt-auto">
                        <span>
                            <i class="fas fa-medal"></i>Nivel: 
                            <span class="course-level-tag"><?= htmlspecialchars($curso['nivel']) ?></span>
                        </span><br>
                        <span>
                            <i class="fas fa-chalkboard-teacher"></i> Instructor:
                            <b><?= htmlspecialchars($curso['instructor_nombre'] ?? 'Sin asignar') ?></b>
                        </span>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="ver_curso.php?id=<?= htmlspecialchars($curso['id']) ?>" class="py-2 px-4 rounded font-semibold text-white transition-colors" style="background-color:var(--secondary);">
                        Ver Detalles
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <h2 class="section-title" style="margin-top:60px;">Descubre el mundo de la cocina</h2>
    <div class="text-center">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">
            
            <div class="p-6 border border-gray-100 rounded-lg shadow-sm flex flex-col justify-between h-full bg-white">
                <img src="img/Cocinero1.jpg" class="img-resize" alt="Explora nuestros cursos" onerror="this.onerror=null; this.src='https://placehold.co/100x150/f7f1e3/4ecdc4?text=Cursos';">
                <p class="text-lg font-medium mt-auto">Explora nuestros cursos</p>
            </div>
            
            <div class="p-6 border border-gray-100 rounded-lg shadow-sm flex flex-col justify-between h-full bg-white">
                <img src="img/Cocinero2.jpg" class="img-resize" alt="Aprende nuevas recetas" onerror="this.onerror=null; this.src='https://placehold.co/100x150/f7f1e3/4ecdc4?text=Recetas';">
                <p class="text-lg font-medium mt-auto">Aprende nuevas recetas</p>
            </div>
            
            <div class="p-4 flex flex-col justify-between items-center bg-gray-50 rounded-lg shadow-sm h-full">
                
                <div class="well w-full">
                    <p class="text-gray-700">Únete a nuestra comunidad de entusiastas de la cocina.</p>
                </div>
                
                <div class="well w-full flex-grow">
                    <p class="text-gray-700">Amplía tus habilidades culinarias a tu propio ritmo.</p>
                </div>
                
                <div class="mt-auto w-full">
                    <div class="well flex justify-center space-x-3">
                        <a href="#" target="_blank" class="btn-social-icon btn-facebook" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" target="_blank" class="btn-social-icon btn-instagram" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" target="_blank" class="btn-social-icon btn-youtube" aria-label="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h2 class="section-title" style="margin-top:60px;">Conoce a Nuestros Maestros</h2>
    <div class="grid mb-10">
        <?php foreach ($instructores as $instructor): $imgData = getProfileImage($instructor); $isPlaceholder = $instructor['id'] == 0; ?>
            <a href="index.php?profile_id=<?= htmlspecialchars($instructor['id']) ?>" class="instructor-card group <?= $isPlaceholder ? 'opacity-70 pointer-events-none' : '' ?>" aria-label="Ver perfil de <?= htmlspecialchars($instructor['nombre']) ?>">
                <div>
                    <img src="<?= $imgData['src'] ?>" alt="<?= $imgData['alt'] ?>" onerror="this.onerror=null; this.src='<?= $imgData['fallback_src'] ?>';" class="transition-transform duration-300 group-hover:scale-105 mx-auto block">
                    
                    <h3 class="text-xl font-bold text-gray-800 mt-2">
                        <?= htmlspecialchars($instructor['nombre'] . ' ' . $instructor['apellido']) ?>
                    </h3>
                    <p class="text-sm text-gray-500 mb-4">Maestro Culinario</p>
                </div>
                
                <?php if (!$isPlaceholder): ?><button class="mt-auto text-sm font-semibold py-2 px-6 rounded-full text-white transition-colors" style="background-color: var(--theme-green); box-shadow: 0 4px 10px rgba(0, 150, 0, 0.2);" onmouseover="this.style.backgroundColor='#4c8234'" onmouseout="this.style.backgroundColor='var(--theme-green)'">
                        Ver Perfil
                    </button>
                <?php else: ?>
                    <button class="mt-auto text-sm font-semibold py-2 px-6 rounded-full text-gray-500 bg-gray-200 cursor-default">
                        Próximamente
                    </button>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div> 
    <footer>
        <p>
            &copy; <?php echo date('Y'); ?> ChefEnCuna — Todos los derechos reservados.
            <br>
            <a href="sobre_nosotros.php">Sobre Nosotros</a>
        </p>
    </footer>
</body>
</html>