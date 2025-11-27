<?php
session_start();

require 'config/bd.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    header('Location: login.php');
    exit;
}

$nombre_admin = htmlspecialchars($_SESSION['user_nombre'] ?? 'Administrador');
$error_bd = null;

try {
    // Nota: Aquí se asume que $pdo está configurado correctamente en 'config/bd.php'
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

    // Consulta para obtener el total de FAQs
    $stmt = $pdo->query("SELECT COUNT(id) AS total_faqs FROM faqs");
    $total_faqs = $stmt->fetchColumn();

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
    <script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root { --primary: #ff6b6b; --secondary: #4ecdc4; --dark: #2d3436; --light: #f7f1e3; }
        body { font-family: 'Inter', sans-serif; margin: 0; padding: 0; background-color: var(--light); color: var(--dark); }
        header { background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        .logo { font-size: 1.5rem; font-weight: bold; color: var(--primary); text-decoration: none; }
        .admin-card { background-color: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: transform 0.2s, box-shadow 0.2s; text-align: center; }
        .admin-card-stats:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .icon-bg { background-color: var(--primary); color: white; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 50%; margin: 0 auto 1rem; font-size: 1.5rem; }
        .text-primary { color: var(--primary); }
    </style>
</head>
<body>

    <header>
        <div class="logo">Panel de Control del Administrador</div>
        <nav class="flex items-center space-x-6">
            <span class="text-sm font-medium text-gray-700">
                ¡Hola, <?php echo $nombre_admin; ?>!
            </span>
            <a href="logout.php" class="py-2 px-4 rounded-full bg-red-500 text-white font-semibold hover:bg-red-600 transition duration-150">
                <i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión
            </a>
        </nav>
    </header>

    <div class="container mx-auto p-8 lg:p-12">
        <h1 class="text-4xl font-extrabold text-gray-800 mb-8 border-b-2 border-primary pb-2">
            Resumen General del Sistema
        </h1>

        <?php if ($error_bd): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-lg" role="alert">
                <p><?php echo htmlspecialchars($error_bd); ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            <div class="admin-card admin-card-stats border-2 border-transparent">
                <div class="icon-bg bg-secondary"><i class="fas fa-users"></i></div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $total_usuarios; ?></p>
                <p class="text-gray-500 mt-1">Total de Usuarios</p>
            </div>
            <div class="admin-card admin-card-stats border-2 border-transparent">
                <div class="icon-bg"><i class="fas fa-book-open"></i></div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $total_cursos; ?></p>
                <p class="text-gray-500 mt-1">Cursos Activos</p>
            </div>
            <div class="admin-card admin-card-stats border-2 border-transparent">
                <div class="icon-bg bg-secondary"><i class="fas fa-utensils"></i></div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $total_recetas; ?></p>
                <p class="text-gray-500 mt-1">Recetas Publicadas</p>
            </div>
            <div class="admin-card admin-card-stats border-2 border-transparent">
                <div class="icon-bg"><i class="fas fa-chalkboard-teacher"></i></div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $total_maestros; ?></p>
                <p class="text-gray-500 mt-1">Maestros Registrados</p>
            </div>
            <div class="admin-card admin-card-stats border-2 border-transparent">
                <div class="icon-bg bg-purple-500"><i class="fas fa-question"></i></div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $total_temas; ?></p>
                <p class="text-gray-500 mt-1">Temas en el Foro</p>
            </div>
            <div class="admin-card admin-card-stats border-2 border-transparent">
                <div class="icon-bg bg-green-500"><i class="fas fa-reply-all"></i></div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $total_respuestas; ?></p>
                <p class="text-gray-500 mt-1">Respuestas Generadas</p>
            </div>
             <div class="admin-card admin-card-stats border-2 border-transparent">
                <div class="icon-bg bg-blue-500"><i class="fas fa-question-circle"></i></div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $total_faqs; ?></p>
                <p class="text-gray-500 mt-1">FAQs Registradas</p>
            </div>
        </div>

        <h2 class="text-3xl font-extrabold text-gray-800 mb-6">Módulos de Gestión</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div x-data="{ open: false }" class="relative admin-card border-2 border-gray-100 transition hover:border-primary">

                <div @click="open = !open" class="p-4 text-center cursor-pointer transition duration-300 rounded-xl" :class="{ 'rounded-b-none': open }">
                    <div class="icon-bg"><i class="fas fa-users-cog"></i></div>
                    <h3 class="text-xl font-semibold mb-2">Gestión de Cuentas y Perfiles</h3>
                    <p class="text-gray-500 text-sm flex items-center justify-center">
                        Cuentas, roles y biografías 
                        <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'" class="ml-2 text-sm transition-all duration-300"></i>
                    </p>
                    <span class="mt-4 inline-block text-sm text-primary font-medium">Click para ver opciones &rarr;</span>
                </div>
                <div x-show="open" 
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 transform scale-y-0"
                        x-transition:enter-end="opacity-100 transform scale-y-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 transform scale-y-100"
                        x-transition:leave-end="opacity-0 transform scale-y-0"
                        class="border-t border-gray-100 p-2 space-y-2 bg-gray-50 rounded-b-xl absolute w-full left-0 z-10 origin-top">
                    
                    <a href="admin_manage_users.php" 
                        class="block p-3 rounded-lg text-left text-gray-700 hover:bg-white hover:shadow-md transition duration-150">
                        <i class="fas fa-user-shield w-5 mr-3 text-primary"></i> Gestionar Cuentas de Usuarios
                    </a>
                    <a href="admin_manage_bios.php" 
                        class="block p-3 rounded-lg text-left text-gray-700 hover:bg-white hover:shadow-md transition duration-150">
                        <i class="fas fa-address-card w-5 mr-3 text-blue-500"></i> Gestionar Biografías de Maestros
                    </a>
                </div>
            </div>
            
            <a href="admin_manage_courses.php" class="admin-card border-2 border-gray-100 block hover:border-secondary">
                <div class="icon-bg bg-secondary"><i class="fas fa-book"></i></div>
                <h3 class="text-xl font-semibold mb-2">Gestión de Cursos</h3>
                <p class="text-gray-500 text-sm">Administrar módulos, lecciones y disponibilidad de todos los cursos.</p>
                <span class="mt-4 inline-block text-sm text-primary font-medium">Ir al Módulo &rarr;</span>
            </a>

            <a href="admin_manage_recipes.php" class="admin-card border-2 border-gray-100 block hover:border-primary">
                <div class="icon-bg"><i class="fas fa-pizza-slice"></i></div>
                <h3 class="text-xl font-semibold mb-2">Gestión de Recetas</h3>
                <p class="text-gray-500 text-sm">Revisar, aprobar y editar recetas enviadas por usuarios o maestros.</p>
                <span class="mt-4 inline-block text-sm text-primary font-medium">Ir al Módulo &rarr;</span>
            </a>
            
            <!-- NUEVA TARJETA PARA LA GESTIÓN DE FAQS -->
            <a href="admin_faqs.php" class="admin-card border-2 border-gray-100 block hover:border-secondary">
                <div class="icon-bg bg-blue-500"><i class="fas fa-question-circle"></i></div>
                <h3 class="text-xl font-semibold mb-2">Gestión de FAQs</h3>
                <p class="text-gray-500 text-sm">Crear, editar y organizar las preguntas frecuentes para la vista pública.</p>
                <span class="mt-4 inline-block text-sm text-primary font-medium">Ir al Módulo &rarr;</span>
            </a>
            
            <a href="foro_ayuda.php" class="admin-card border-2 border-gray-100 block hover:border-secondary">
                <div class="icon-bg bg-secondary"><i class="fas fa-comments"></i></div>
                <h3 class="text-xl font-semibold mb-2">Moderar Foro</h3>
                <p class="text-gray-500 text-sm">Ver, responder y gestionar los temas y dudas de la comunidad.</p>
                <span class="mt-4 inline-block text-sm text-primary font-medium">Ir al Foro &rarr;</span>
            </a>
        </div>
    </div>
</body>
</html>