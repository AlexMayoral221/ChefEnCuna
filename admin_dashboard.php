<?php
session_start();

require 'config/bd.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    header('Location: login.php');
    exit;
}

$nombre_admin = htmlspecialchars($_SESSION['user_nombre'] ?? 'Administrador');
$active_page = 'dashboard'; 
$error_bd = null;

if (!isset($pdo)) {
    try {
        $pdo = new PDO('sqlite::memory:'); 
    } catch (PDOException $e) {
    }
}


try {
    if ($pdo instanceof PDO && $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
        $stmt = $pdo->query("SELECT COUNT(id) AS total_usuarios FROM usuarios");
        $total_usuarios = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(id) AS total_cursos FROM cursos");
        $total_cursos = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(id) AS total_recetas FROM recetas");
        $total_recetas = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(id) AS total_maestros FROM usuarios WHERE rol = 'maestro'");
        $total_maestros = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(id) AS total_temas FROM foro_temas");
        $total_temas = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(id) AS total_respuestas FROM foro_respuestas");
        $total_respuestas = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(id) AS total_faqs FROM faqs");
        $total_faqs = $stmt->fetchColumn();
    } else {
        $error_bd = "Advertencia: La conexión a la base de datos no está disponible o es solo de prueba (SQLite en memoria).";
        $total_usuarios = 120;
        $total_cursos = 15;
        $total_recetas = 450;
        $total_maestros = 8;
        $total_temas = 85;
        $total_respuestas = 520; 
        $total_faqs = 30;
    }


} catch (PDOException $e) {
    $error_bd = "No se pudieron cargar las estadísticas. Error: " . $e->getMessage();
    $total_usuarios = $total_cursos = $total_recetas = $total_maestros = "N/A";
    $total_temas = $total_respuestas = "N/A"; 
    $total_faqs = "N/A";
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
    <script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-dark': '#1e272e', 
                        'primary-light': '#f4f7f6', 
                        'accent': '#4ecdc4',       
                        'primary-accent': '#ff6b6b', 
                        'text-base': '#4a4a4a', 

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
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--primary-light); 
        }
        .nav-link.active {
            background-color: #ff6b6b; 
            color: white;
            border-radius: 0.5rem;
        }
        .nav-link:not(.active):hover {
            background-color: rgba(255, 107, 107, 0.1); 
            color: #ff6b6b;
            border-radius: 0.5rem;
        }
        .stat-card {
            background-color: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0,0,0,0.05);
            transition: transform 0.2s, border-color 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            border-color: #ff6b6b; 
        }
        .icon-bg { 
            width: 50px; 
            height: 50px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            border-radius: 50%; 
            font-size: 1.25rem; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        @media (min-width: 1024px) {
            .ml-64 { margin-left: 16rem; }
        }
    </style>
</head>
<body class="bg-primary-light text-text-base">

<div class="flex h-screen overflow-hidden">
    <aside class="w-64 bg-white shadow-sidebar flex flex-col fixed h-full z-20">
        <div class="p-6 border-b border-gray-100 flex-shrink-0">
            <h1 class="text-2xl font-extrabold text-primary-dark tracking-wide">ChefEnCuna<span class="text-primary-accent">.</span></h1>
        </div>

        <nav class="flex-grow p-4 space-y-2 overflow-y-auto">
            <a href="admin_dashboard.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 active">
                <i class="fas fa-chart-line w-5 mr-3"></i>
                Dashboard
            </a>
            
            <p class="text-xs text-gray-400 uppercase font-bold pt-4 pb-1 px-3">Gestión de Contenido</p>
            
            <a href="admin_manage_courses.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
                <i class="fas fa-book w-5 mr-3 text-accent"></i>
                Cursos
            </a>

            <a href="admin_manage_recipes.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
                <i class="fas fa-utensils w-5 mr-3 text-primary-accent"></i>
                Recetas
            </a>
            <p class="text-xs text-gray-400 uppercase font-bold pt-4 pb-1 px-3">Usuarios y Comunidad</p>

            <a href="admin_manage_users.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
                <i class="fas fa-users-cog w-5 mr-3 text-blue-500"></i>
                Cuentas de Usuarios
            </a>
            
            <a href="admin_manage_bios.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
                <i class="fas fa-address-card w-5 mr-3 text-purple-500"></i>
                Biografías Maestros
            </a>

            <a href="foro_ayuda.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
                <i class="fas fa-comments w-5 mr-3 text-green-500"></i>
                Moderar Foro
            </a>
            
            <a href="admin_faqs.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
                <i class="fas fa-question-circle w-5 mr-3 text-orange-500"></i>
                FAQs
            </a>
        </nav>

        <div class="p-6 border-t border-gray-100 flex-shrink-0">
            <a href="logout.php" class="flex items-center text-primary-accent font-medium hover:text-red-700 transition duration-200">
                <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
            </a>
        </div>
    </aside>
    
    <div class="flex-1 overflow-y-auto ml-64">
        
        <header class="bg-white shadow-md p-4 sticky top-0 z-10">
             <div class="flex justify-between items-center max-w-7xl mx-auto">
                <h2 class="text-xl font-bold text-text-base">
                    Panel de Control <span class="text-primary-accent">Administración</span>
                </h2>
                <a href="index.php" class="text-gray-500 hover:text-primary-dark transition duration-200 text-sm">
                    <i class="fas fa-home mr-1"></i> Ir a Inicio
                </a>
            </div>
        </header>
        
        <main class="p-4 lg:p-10">
            <h1 class="text-4xl font-extrabold text-dark mb-8 border-b-2 border-primary-accent pb-2">
                Resumen General del Sistema
            </h1>

            <?php if ($error_bd): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded-lg shadow" role="alert">
                    <p class="font-bold"><i class="fas fa-exclamation-triangle"></i> Error de Conexión:</p>
                    <p class="text-sm"><?php echo htmlspecialchars($error_bd); ?></p>
                </div>
            <?php endif; ?>

            <section class="mb-10">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-7 gap-6">
                    
                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div class="icon-bg bg-primary-accent text-white"><i class="fas fa-users"></i></div>
                            <span class="text-4xl font-extrabold text-dark"><?php echo $total_usuarios; ?></span>
                        </div>
                        <p class="text-sm text-gray-500 mt-2 font-semibold">Total de Usuarios</p>
                    </div>

                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div class="icon-bg bg-accent text-white"><i class="fas fa-book-open"></i></div>
                            <span class="text-4xl font-extrabold text-dark"><?php echo $total_cursos; ?></span>
                        </div>
                        <p class="text-sm text-gray-500 mt-2 font-semibold">Cursos Activos</p>
                    </div>

                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div class="icon-bg bg-primary-accent text-white"><i class="fas fa-utensils"></i></div>
                            <span class="text-4xl font-extrabold text-dark"><?php echo $total_recetas; ?></span>
                        </div>
                        <p class="text-sm text-gray-500 mt-2 font-semibold">Recetas Publicadas</p>
                    </div>

                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div class="icon-bg bg-blue-500 text-white"><i class="fas fa-chalkboard-teacher"></i></div>
                            <span class="text-4xl font-extrabold text-dark"><?php echo $total_maestros; ?></span>
                        </div>
                        <p class="text-sm text-gray-500 mt-2 font-semibold">Maestros Registrados</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div class="icon-bg bg-purple-500 text-white"><i class="fas fa-question"></i></div>
                            <span class="text-4xl font-extrabold text-dark"><?php echo $total_temas; ?></span>
                        </div>
                        <p class="text-sm text-gray-500 mt-2 font-semibold">Temas en el Foro</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div class="icon-bg bg-green-500 text-white"><i class="fas fa-reply-all"></i></div>
                            <span class="text-4xl font-extrabold text-dark"><?php echo $total_respuestas; ?></span>
                        </div>
                        <p class="text-sm text-gray-500 mt-2 font-semibold">Respuestas Generadas</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div class="icon-bg bg-orange-500 text-white"><i class="fas fa-question-circle"></i></div>
                            <span class="text-4xl font-extrabold text-dark"><?php echo $total_faqs; ?></span>
                        </div>
                        <p class="text-sm text-gray-500 mt-2 font-semibold">FAQs Registradas</p>
                    </div>
                </div>
            </section>
        </main>
    </div>
    </div>
</body>
</html>