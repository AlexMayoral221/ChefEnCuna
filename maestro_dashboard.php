<?php
session_start();

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

$user_name = $_SESSION['user_name'] ?? 'Maestro';
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
        body { font-family: 'Inter', sans-serif; background-color: var(--light); }
        .header-bg { background-color: var(--secondary); }
        .card-shadow { 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 
                        0 4px 6px -2px rgba(0, 0, 0, 0.05); 
        }
        .text-primary-custom { color: var(--primary); }
        .card-bio-color { color: #3b82f6; /* Tailwind blue-500 */ }
    </style>
</head>
<body>
    <nav class="header-bg p-4 shadow-lg">
        <div class="max-w-7xl mx-auto flex justify-between items-center">

            <h1 class="text-2xl font-bold text-dark">ChefEnCuna • Panel del Maestro</h1>
            <div class="flex items-center space-x-6">
                <a href="index.php" 
                class="font-semibold flex items-center hover:text-white transition duration-200">
                    <i class="fas fa-home mr-2"></i> Inicio
                </a>
                <a href="logout.php" 
                class="font-semibold flex items-center hover:text-white transition duration-200">
                    <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto p-6">

        <header class="text-center py-10 bg-white rounded-xl card-shadow mb-10">
            <h2 class="text-4xl font-extrabold text-dark mb-2">
                ¡Hola, <?php echo htmlspecialchars($user_name); ?>! 👋
            </h2>
            <p class="text-xl text-gray-500">Aquí puedes gestionar tus actividades y recetas.</p>
        </header>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">

            <!-- TARJETA DESPLEGABLE DE GESTIÓN DE PERFIL (MOVIMIENTO: A la primera posición) -->
            <div x-data="{ open: false }" class="relative bg-white rounded-xl card-shadow transition">
                
                <!-- Encabezado de la Tarjeta (Clickable) -->
                <div @click="open = !open" class="p-6 text-center cursor-pointer hover:shadow-xl transition duration-300 rounded-xl" :class="{ 'rounded-b-none': open }">
                    <i class="fas fa-user-cog text-5xl card-bio-color mb-4 transition" :class="{ 'rotate-12 scale-110': open }"></i>
                    <h3 class="text-2xl font-semibold text-dark mb-2">Gestión de Perfil</h3>
                    <p class="text-gray-600 flex items-center justify-center">
                        Opciones de visualización y edición 
                        <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'" class="ml-2 text-sm transition-all duration-300"></i>
                    </p>
                </div>

                <!-- Menú Desplegable -->
                <div x-show="open" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 transform translate-y-0"
                     x-transition:leave-end="opacity-0 transform -translate-y-2"
                     class="border-t border-gray-100 p-2 space-y-2 bg-gray-50 rounded-b-xl">
                    
                    <a href="maestro_ver_perfil.php" class="block p-3 rounded-lg text-left text-gray-700 hover:bg-white hover:shadow-md transition duration-150">
                        <i class="fas fa-id-card w-5 mr-3 text-secondary"></i> Ver Mi Perfil
                    </a>

                    <a href="maestro_perfil.php" class="block p-3 rounded-lg text-left text-gray-700 hover:bg-white hover:shadow-md transition duration-150">
                        <i class="fas fa-user-edit w-5 mr-3 text-primary-custom"></i> Editar Datos Personales
                    </a>

                    <a href="maestro_biografia.php" class="block p-3 rounded-lg text-left text-gray-700 hover:bg-white hover:shadow-md transition duration-150">
                        <i class="fas fa-address-card w-5 mr-3 text-card-bio-color"></i> Gestionar Biografía
                    </a>
                </div>
            </div>
            <!-- FIN TARJETA DESPLEGABLE -->

            <a href="maestro_recetas.php" class="bg-white rounded-xl p-6 card-shadow text-center hover:shadow-2xl hover:scale-105 transition">
                <i class="fas fa-utensils text-5xl text-primary-custom mb-4"></i>
                <h3 class="text-2xl font-semibold text-dark mb-2">Mis Recetas</h3>
                <p class="text-gray-600">Ver y administrar recetas creadas.</p>
            </a>

            <a href="foro_ayuda.php" class="bg-white rounded-xl p-6 card-shadow text-center hover:shadow-2xl hover:scale-105 transition">
                <i class="fas fa-comments text-5xl text-primary-custom mb-4"></i> 
                <h3 class="text-2xl font-semibold text-dark mb-2">Foro de Dudas</h3>
                <p class="text-gray-600">Ver y responder las dudas de los alumnos.</p>
            </a>
            
             <a href="ayuda.php" class="bg-white rounded-xl p-6 card-shadow text-center hover:shadow-2xl hover:scale-105 transition">
                <i class="fas fa-question-circle text-5xl text-primary-custom mb-4"></i>
                <h3 class="text-2xl font-semibold text-dark mb-2">Ayuda y Soporte</h3>
                <p class="text-gray-600">Tutoriales y asistencia técnica.</p>
            </a>

        </div>
    </main>
</body>
</html>