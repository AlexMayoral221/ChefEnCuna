<?php
session_start();
require 'config/bd.php'; 

$pdo = $pdo ?? null; 

if ($pdo && isset($_SESSION['user_id']) && !isset($_SESSION['user_nombre'])) {
    try {
        $stmt = $pdo->prepare("SELECT nombre, rol FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($usuario) {
            $_SESSION['user_nombre'] = $usuario['nombre'];
            $_SESSION['user_rol'] = $usuario['rol'];
        }
    } catch (PDOException $e) {
    }
}

$dashboard_url = "dashboard.php"; 
if (isset($_SESSION['user_rol'])) {
    switch ($_SESSION['user_rol']) {
        case 'maestro': $dashboard_url = "maestro_dashboard.php"; break;
        case 'alumno': $dashboard_url = "alumno_dashboard.php"; break;
        case 'administrador': $dashboard_url = "admin_dashboard.php"; break;
        default: $dashboard_url = "dashboard.php"; break;
    }
}
$ultimas_recetas = [];
$ultimos_cursos = []; 
$instructores = [];
$error_db = '';

$perfil = null;
$perfil_error_msg = ''; 
$cursos_impartidos = []; 

if (isset($_GET['profile_id']) && is_numeric($_GET['profile_id']) && $pdo) {
    $user_id = $_GET['profile_id'];
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
        $perfil_error_msg = "Error de base de datos al cargar el perfil y los cursos: " . $e->getMessage();
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
        case 'maestro': return 'bg-yellow-500 text-yellow-900';
        case 'alumno': return 'bg-blue-500 text-blue-900';
        case 'administrador': return 'bg-red-500 text-red-900';
        default: return 'bg-gray-500 text-gray-900';
    }
}
if ($pdo) {
    try {
        $stmtRecetas = $pdo->query("SELECT r.id, r.titulo, r.descripcion, r.imagen_ruta, u.nombre as autor FROM recetas r JOIN usuarios u ON r.usuario_id = u.id ORDER BY r.fecha_publicacion DESC LIMIT 3");
        $ultimas_recetas = $stmtRecetas->fetchAll(PDO::FETCH_ASSOC);
        $stmtCursos = $pdo->query("SELECT c.id, c.titulo, c.descripcion, c.nivel, u.nombre as instructor_nombre FROM cursos c LEFT JOIN usuarios u ON c.instructor_id = u.id ORDER BY c.fecha_creacion DESC LIMIT 3");
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
        $error_db = "Error al cargar contenido de la base de datos: " . $e->getMessage();
    }
} else {
    $error_db = "Error: Conexión PDO no inicializada correctamente.";
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
            --primary: #ffffff; /* Blanco */
            --secondary: #4ecdc4; /* Teal */
            --dark: #2d3436; /* Gris Oscuro */
            --light: #f7f1e3; /* Fondo del Cuerpo */
            --theme-green: #69A64A; /* Fondo del Header */
            --hero-bg: #2d3436; 
        }
        body { 
            font-family: 'Inter', sans-serif; 
            color: var(--dark); 
            background-color: var(--light); 
            min-height: 100vh;
            margin: 0;
            padding: 0;
            display: flex; 
            flex-direction: column; 
        }
        .app-header { 
            background: var(--theme-green);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
            position: sticky; 
            top: 0;
            z-index: 100;
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
            color: var(--primary);
            text-decoration: none; 
        }
        .app-nav a {
            margin-left: 1.5rem;
            text-decoration: none;
            color: var(--primary);
            font-weight: 500;
            transition: color 0.15s;
            white-space: nowrap; 
        }
        .app-nav a:hover { 
            color: var(--secondary);
        }
        .btn-access-style {
            background-color: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            border-radius: 9999px;
            font-weight: 600;
            padding: 0.5rem 1rem;
            display: inline-flex;
            align-items: center;
            transition: background-color 0.2s, color 0.2s;
        }
        .btn-access-style:hover {
            background-color: var(--primary);
            color: var(--theme-green);
        }
        .btn-profile {
            background-color: var(--secondary);
            color: white;
            transition: background-color 0.2s;
            border-radius: 9999px;
            font-weight: 600;
            padding: 0.5rem 1rem;
            display: inline-flex;
            align-items: center;
        }
        .btn-profile:hover {
            background-color: #3aa69e; 
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
        }
        .instructor-card:hover {
            background-color: #f0fff0;
        }
        .instructor-card img {
            width: 120px;
            height: 120px;
            border-radius: 10px; 
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
            .header-content {
                flex-direction: column;
                padding: 1rem; 
            }
            .app-nav {
                margin-top: 1rem;
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
            }
            .app-nav a {
                margin: 0.5rem 0.3rem;
                font-size: 0.9rem;
            }
            .btn-profile, .btn-access-style {
                margin-left: 0.3rem !important;
                margin-right: 0.3rem !important;
                padding: 0.4rem 0.8rem;
                font-size: 0.9rem;
            }
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
        footer { background: var(--dark); color: white; text-align: center; padding: 20px; margin-top: 50px; }
        footer a { color: var(--secondary); text-decoration: none; margin: 0 10px; }
        footer a:hover { text-decoration: underline; }        
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.75); /* Fondo oscuro semitransparente */
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
            max-width: 200%;
            width: 900px;
            position: relative;
            margin: auto; 
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
            max-width: 700px;
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
            .bio-box {
                max-width: none; 
                margin-top: 0;
            }
        }
    </style>
</head>
<body>
    
<?php if ($perfil || $perfil_error_msg): ?>
<div class="modal-overlay">
    <div class="modal-content">
        <button class="close-button" onclick="window.history.back() || window.location.replace('index.php')">
            <i class="fas fa-times"></i>
        </button>

        <main class="py-10">
            <?php if ($perfil_error_msg): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative max-w-lg mx-auto" role="alert">
                    <strong class="font-bold">Error:</strong>
                    <span class="block sm:inline"><?= htmlspecialchars($perfil_error_msg) ?></span>
                </div>
            <?php elseif ($perfil): ?>

                <div class="profile-card max-w-4xl mx-auto">
                    
                    <div class="profile-card-header">
                        <?php $imgSrc = !empty($perfil['foto_perfil']) ? htmlspecialchars($perfil['foto_perfil']) : "https://placehold.co/150x150/4ecdc4/ffffff?text=" . strtoupper(substr(htmlspecialchars($perfil['nombre']), 0, 1) . substr(htmlspecialchars($perfil['apellido']), 0, 1));?>
                        <img src="<?= $imgSrc ?>" alt="Foto de perfil de <?= htmlspecialchars($perfil['nombre']) ?>" class="profile-img" onerror="this.onerror=null; this.src='https://placehold.co/150x150/4ecdc4/ffffff?text=<?= strtoupper(substr(htmlspecialchars($perfil['nombre']), 0, 1) . substr(htmlspecialchars($perfil['apellido']), 0, 1)) ?>'">

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
                                <h3 class="text-xl font-semibold mb-3 text-gray-700">Cursos Impartidos (<?= count($cursos_impartidos) ?>)</h3>
                                
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

                    <button @click="open = !open" class="btn-profile flex items-center">
                        <i class="fas fa-user mr-2"></i> <?= htmlspecialchars($_SESSION['user_nombre']) ?>
                        <i class="fas fa-caret-down ml-2"></i>
                    </button>

                    <div x-show="open" x-transition class="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-[var(--theme-green)] text-white ring-1 ring-black ring-opacity-5">
                        <a href="<?= htmlspecialchars($dashboard_url) ?>" class="block px-4 py-2 text-sm text-white hover:bg-green-700">
                            <i class="fas fa-gauge-high mr-2"></i> Mi perfil
                        </a>
                            
                        <a href="logout.php" class="block px-4 py-2 text-sm text-white hover:bg-red-700 border-t border-white/25">
                            <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
            <?php else: ?>
                
                <div class="relative ml-4" x-data="{ open: false }" @click.away="open = false">

                    <button @click="open = !open" class="btn-access-style flex items-center">
                        <i class="fas fa-user-circle mr-2"></i> Acceso
                        <i class="fas fa-caret-down ml-2"></i>
                    </button>
                    <div x-show="open" x-transition class="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-[var(--theme-green)] text-white ring-1 ring-black ring-opacity-5">
                        <a href="login.php" class="block px-4 py-2 text-sm text-white hover:bg-green-700">
                            <i class="fas fa-sign-in-alt mr-2"></i> Entrar
                        </a>
                        <a href="registro.php" class="block px-4 py-2 text-sm text-white hover:bg-green-700 border-t border-white/25">
                            <i class="fas fa-user-plus mr-2"></i> Registrarse
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </nav>
    </div>
</header>

<section class="hero-section">
    <div style="max-width: 800px; margin: 0 auto;">
        
        <img src="img/logo.png" alt="Logo ChefEnCuna" style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; margin: 0 auto 20px; display: block; filter: drop-shadow(0 5px 15px rgba(0,0,0,0.3));" onerror="this.onerror=null; this.src='https://placehold.co/150x150/4ecdc4/ffffff?text=Logo';">
        <h1 style="font-size: 3rem; margin-bottom: 10px;">Aprende. Cocina. Comparte.</h1>
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
        <p style="color:red;text-align:center;padding: 20px; border: 1px solid red; background: #fee; border-radius: 8px; margin-bottom: 30px;"><?= htmlspecialchars($error_db) ?></p>
    <?php endif; ?>

    <h2 class="section-title">Recetas Frescas</h2>
    <div class="grid">
        <?php if (empty($ultimas_recetas)): ?>
            <p class="col-span-full text-center text-gray-500">No hay recetas disponibles en este momento.</p>
        <?php endif; ?>
        <?php foreach($ultimas_recetas as $receta): ?>
            <div class="card">
                <img src="img/recetas/<?= htmlspecialchars($receta['id']) ?>.jpg" class="card-img" alt="Imagen de la receta: <?= htmlspecialchars($receta['titulo']) ?>" onerror="this.src='https://placehold.co/400x200/ff6b6b/ffffff?text=Receta+<?= htmlspecialchars($receta['id']) ?>'">

                <div class="card-body">
                    <h3 class="card-title"><?= htmlspecialchars($receta['titulo']) ?></h3>
                </div>

                <div class="card-footer">
                    <a href="ver_receta.php?id=<?= htmlspecialchars($receta['id']) ?>" class="btn-sm" style="background-color:var(--theme-green); color: white;">
                        Ver Receta
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <h2 class="section-title" style="margin-top:60px;">Nuestros Últimos Cursos</h2>
    <div class="grid">
        <?php if (empty($ultimos_cursos)): ?>
            <p class="col-span-full text-center text-gray-500">No hay cursos disponibles en este momento.</p>
        <?php endif; ?>
        <?php foreach($ultimos_cursos as $curso): ?>
            <div class="card">
                <img src="img/cursos/<?= htmlspecialchars($curso['id']) ?>.jpg" class="card-img" alt="Imagen del Curso" onerror="this.src='https://placehold.co/400x200/4ecdc4/ffffff?text=Curso+<?= htmlspecialchars($curso['id']) ?>'">

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
                    <a href="ver_curso.php?id=<?= htmlspecialchars($curso['id']) ?>" class="btn-sm">
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
                <img src="img/Cocinero1.jpg" class="img-resize" alt="Explora nuestros cursos">
                <p class="text-lg font-medium mt-auto">Explora nuestros cursos</p>
            </div>
            
            <div class="p-6 border border-gray-100 rounded-lg shadow-sm flex flex-col justify-between h-full bg-white">
                <img src="img/Cocinero2.jpg" class="img-resize" alt="Aprende nuevas recetas">
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
                        <a href="#" target="_blank" class="btn-social-icon btn-facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" target="_blank" class="btn-social-icon btn-instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" target="_blank" class="btn-social-icon btn-youtube">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div style="height: 40px;"></div> 

    <h2 class="section-title" style="margin-top:60px;">Nuestros Cocineros Destacados</h2>

    <div class="grid">
        <?php foreach($instructores as $inst): ?>
        <div class="instructor-card">
            <?php $imgSrc = !empty($inst['foto_perfil']) ? htmlspecialchars($inst['foto_perfil']) : "https://placehold.co/120x120/4ecdc4/ffffff?text=" . substr(htmlspecialchars($inst['nombre']), 0, 1);?>
            <img src="<?= $imgSrc ?>" alt="Foto de <?= htmlspecialchars($inst['nombre']) ?>"onerror="this.onerror=null; this.src='https://placehold.co/120x120/4ecdc4/ffffff?text=<?= substr(htmlspecialchars($inst['nombre']), 0, 1) ?>'">

            <h3 class="text-xl font-semibold mb-1"><?= htmlspecialchars($inst['nombre']." ".$inst['apellido']) ?></h3>
            <p class="text-sm text-gray-500">Chef Certificado</p>
            
            <a href="index.php?profile_id=<?= htmlspecialchars($inst['id']) ?>" class="mt-2 text-sm font-medium text-[var(--secondary)] hover:underline flex items-center"> Ver Perfil <i class="fas fa-arrow-right text-xs ml-1"></i></a>
        </div>
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