<?php 

$is_logged_in = isset($_SESSION['user_id']);
$user_nombre = $_SESSION['user_nombre'] ?? 'Usuario'; 
$user_rol = $_SESSION['user_rol'] ?? 'alumno'; 

$dashboard_url = 'perfil.php'; 

if ($user_rol === 'administrador') {
    $dashboard_url = 'admin_dashboard.php'; 
} elseif ($user_rol === 'maestro') {
    $dashboard_url = 'maestro_dashboard.php'; 
} elseif ($user_rol === 'alumno') {
    $dashboard_url = 'alumno_dashboard.php'; 
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefEnCuna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header class="bg-[#69A64A] shadow-lg sticky top-0 z-40">
    <div class="flex flex-wrap lg:flex-nowrap justify-between items-center w-full max-w-[1600px] mx-auto py-4 px-4 md:px-8">
        
        <a href="index.php" class="text-white text-2xl font-bold whitespace-nowrap mb-2 lg:mb-0">
            ChefEnCuna 👨‍🍳
        </a>

        <form action="resultados_busqueda.php" method="get" class="order-3 lg:order-2 w-full max-w-lg lg:w-auto mx-0 lg:mx-8 flex items-center mb-2 lg:mb-0">
            <div class="relative w-full">
                <input type="search" name="q" placeholder="Buscar recetas o cursos..." class="w-full py-2 pl-4 pr-10 rounded-full text-gray-800 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#4ecdc4] focus:border-transparent transition duration-150 shadow-inner" aria-label="Buscar recetas">
                <button type="submit" class="absolute right-0 top-1/2 transform -translate-y-1/2 mr-3 text-gray-500 hover:text-[#69A64A] transition">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
        
        <nav class="flex items-center order-2 lg:order-3">
            <a href="index.php" class="text-white hover:text-[#4ecdc4] ml-6 font-medium whitespace-nowrap transition duration-150 hidden lg:inline">Inicio</a>
            <a href="recetas.php" class="text-white hover:text-[#4ecdc4] ml-6 font-medium whitespace-nowrap transition duration-150 hidden lg:inline">Recetas</a>
            <a href="cursos.php" class="text-white hover:text-[#4ecdc4] ml-6 font-medium whitespace-nowrap transition duration-150 hidden lg:inline">Cursos</a>
            <a href="foro_ayuda.php" class="text-white hover:text-[#4ecdc4] ml-6 font-medium whitespace-nowrap transition duration-150 hidden lg:inline">Foro</a>
            
            <?php if ($is_logged_in): ?>
                <div class="relative ml-4" x-data="{ open: false }" @click.away="open = false">
                    <button @click="open = !open" class="bg-[#4ecdc4] text-white rounded-full py-2 px-4 font-semibold flex items-center hover:bg-[#3aa69e] transition shadow-md">
                        <i class="fas fa-user mr-2"></i> <?= htmlspecialchars($user_nombre) ?>
                        <i class="fas fa-caret-down ml-2"></i>
                    </button>

                    <div x-show="open" x-transition class="absolute right-0 mt-2 w-48 rounded-lg shadow-xl py-1 bg-[#69A64A] text-white ring-1 ring-black ring-opacity-5 z-50">
                        <a href="<?= htmlspecialchars($dashboard_url) ?>" class="block px-4 py-2 text-sm text-white hover:bg-green-700 rounded-t-lg">
                            <i class="fas fa-gauge-high mr-2"></i> Mi perfil
                        </a>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-white hover:bg-red-700 border-t border-white/25 rounded-b-lg">
                            <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="relative ml-4" x-data="{ open: false }" @click.away="open = false">
                    <button @click="open = !open" class="bg-transparent border-2 border-white text-white rounded-full font-semibold py-2 px-4 flex items-center hover:bg-white hover:text-gray-700 transition shadow-md">
                        <i class="fas fa-user-circle mr-2"></i> Acceder
                        <i class="fas fa-caret-down ml-2"></i>
                    </button>

                    <div x-show="open" x-transition class="absolute right-0 mt-2 w-48 rounded-lg shadow-xl py-1 bg-[#69A64A] text-white ring-1 ring-black ring-opacity-5 z-50">
                        <a href="login.php" class="block px-4 py-2 text-sm text-white hover:bg-green-700 rounded-t-lg">
                            <i class="fas fa-sign-in-alt mr-2"></i> Entrar
                        </a>
                        <a href="registro.php" class="block px-4 py-2 text-sm text-white hover:bg-green-700 border-t border-white/25 rounded-b-lg">
                            <i class="fas fa-user-plus mr-2"></i> Registrarse
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </nav>
    </div>
</header>
</body>
</html>