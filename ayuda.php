<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'config/bd.php'; 

$user_name = $_SESSION['user_name'] ?? 'Maestro';
$maestro_id = $_SESSION['user_id'];
$active_page = 'ayuda'; 

$cursos_asignados = 0; 
$preguntas_pendientes = 0; 

try {
    $stmt_cursos = $pdo->prepare("
        SELECT COUNT(id) 
        FROM cursos
        WHERE instructor_id = ?
    ");
    $stmt_cursos->execute([$maestro_id]);
    $cursos_asignados = (int)$stmt_cursos->fetchColumn();

} catch (PDOException $e) {
    error_log("Error contando cursos asignados en ayuda.php: " . $e->getMessage());
    $cursos_asignados = 0; 
}

try {
    $stmt_pendientes = $pdo->query("
        SELECT COUNT(t.id) 
        FROM foro_temas t
        LEFT JOIN foro_respuestas r ON t.id = r.tema_id
        WHERE r.tema_id IS NULL
    ");
    $preguntas_pendientes = (int)$stmt_pendientes->fetchColumn();

} catch (PDOException $e) {
    error_log("Error contando preguntas pendientes del foro en ayuda.php: " . $e->getMessage());
    $preguntas_pendientes = 0; 
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
                        'secondary-red': '#ff6b6b', 
                        'text-base': '#4a4a4a',
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
        }
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
    </style>
</head>
<body class="bg-primary-light text-text-base">
<div class="flex h-screen overflow-hidden">
    <aside class="w-64 bg-white shadow-sidebar flex flex-col fixed h-full z-20">
        
        <div class="p-6 border-b border-gray-100">
            <h1 class="text-2xl font-extrabold text-primary-dark tracking-wide">ChefEnCuna<span class="text-accent">.</span></h1>
            <p class="text-sm text-gray-500 mt-1">Panel Maestro</p>
        </div>

        <nav class="flex-grow p-4 space-y-2">
            <a href="maestro_dashboard.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
                <i class="fas fa-th-large w-5 mr-3"></i>
                Dashboard
            </a>

            <div x-data="{ open: false }">
                <button @click="open = !open" class="nav-link w-full flex items-center justify-between p-3 font-semibold text-gray-600 transition duration-150">
                    <span class="flex items-center">
                        <i class="fas fa-user-cog w-5 mr-3"></i>
                        Gestión de Perfil
                    </span>
                    <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-xs transition-transform"></i>
                </button>
                <div x-show="open" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="ml-6 mt-1 space-y-1">
                    
                    <a href="maestro_ver_perfil.php" class="block p-2 text-sm text-gray-600 hover:bg-gray-50 rounded transition duration-150">
                        <i class="fas fa-id-card w-4 mr-2"></i> Ver Perfil Público
                    </a>

                    <a href="maestro_perfil.php" class="block p-2 text-sm text-gray-600 hover:bg-gray-50 rounded transition duration-150">
                        <i class="fas fa-user-edit w-4 mr-2"></i> Editar Datos
                    </a>

                    <a href="maestro_biografia.php" class="block p-2 text-sm text-gray-600 hover:bg-gray-50 rounded transition duration-150">
                        <i class="fas fa-address-card w-4 mr-2"></i> Gestionar Biografía
                    </a>
                </div>
            </div>
            
            <a href="maestro_recetas.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
                <i class="fas fa-utensils w-5 mr-3"></i>
                Mis Recetas
            </a>

            <a href="maestro_cursos.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
                <i class="fas fa-book-open w-5 mr-3"></i>
                Mis Cursos
                <?php if ($cursos_asignados > 0): ?>
                    <span class="ml-auto bg-blue-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                        <?php echo $cursos_asignados; ?>
                    </span>
                <?php endif; ?>
            </a>
            
            <a href="foro_ayuda.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
                <i class="fas fa-comments w-5 mr-3"></i>
                Foro de Dudas
                <?php if ($preguntas_pendientes > 0): ?>
                    <span class="ml-auto bg-secondary-red text-white text-xs font-bold px-2 py-0.5 rounded-full">
                        <?php echo $preguntas_pendientes; ?>
                    </span>
                <?php endif; ?>
            </a>

            <a href="ayuda.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 
                      <?php echo ($active_page === 'ayuda') ? 'active' : 'text-gray-600'; ?>">
                <i class="fas fa-question-circle w-5 mr-3"></i>
                Ayuda y Soporte
            </a>
        </nav>

        <div class="p-6 border-t border-gray-100 mt-auto">
            <a href="logout.php" 
               class="flex items-center text-secondary-red font-medium hover:text-red-700 transition duration-200">
                <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
            </a>
        </div>
    </aside>

    <div class="flex-1 overflow-y-auto ml-64">
        <header class="bg-white shadow-md p-4 sticky top-0 z-10">
             <div class="flex justify-between items-center max-w-7xl mx-auto">
                <h2 class="text-xl font-bold text-text-base">
                    Centro de Ayuda
                </h2>
                <a href="index.php" 
                   class="text-gray-500 hover:text-primary-dark transition duration-200 text-sm">
                    <i class="fas fa-home mr-1"></i> Ir a Inicio
                </a>
            </div>
        </header>

        <main class="p-6 md:p-10">
            <div class="mb-8">
                <h1 class="text-3xl font-extrabold text-primary-dark mb-1">
                    <i class="fas fa-question-circle text-gray-500 mr-2"></i> Centro de Ayuda
                </h1>
                <p class="text-lg text-gray-500">
                    Tutoriales y asistencia para el uso de las herramientas de ChefEnCuna.
                </p>
            </div>
            
            <div class="max-w-4xl mx-auto">
                <p class="text-gray-600 mb-8 p-4 bg-white rounded-lg shadow-sm border-l-4 border-accent">
                    Encuentra información útil sobre cómo usar ChefEnCuna y las funciones disponibles para <b>maestros</b>.
                </p>
                
                <div class="space-y-4">

                    <div x-data="{ open: false }" class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                        <button @click="open = !open" class="w-full text-left p-5 font-semibold text-primary-dark flex justify-between items-center hover:bg-gray-50 transition duration-150">
                            <span class="text-xl"><i class="fas fa-book-open text-accent mr-3"></i> 📘 Cómo crear recetas</span>
                            <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-gray-400 transition-transform duration-300 text-sm"></i>
                        </button>
                        <div x-show="open" x-collapse.duration.500ms class="p-5 pt-0 border-t border-gray-100 text-gray-700">
                            <p>
                                Dirígete a la sección <b>"Mis Recetas"</b> (en el menú lateral) y haz clic en
                                el botón <b>"Crear Nueva Receta"</b>. Allí podrás agregar título, descripción,
                                ingredientes, procedimiento e imagen.
                            </p>
                        </div>
                    </div>

                    <div x-data="{ open: false }" class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                        <button @click="open = !open" class="w-full text-left p-5 font-semibold text-primary-dark flex justify-between items-center hover:bg-gray-50 transition duration-150">
                            <span class="text-xl"><i class="fas fa-pencil-alt text-secondary-red mr-3"></i> ✏️ Cómo editar o eliminar una receta</span>
                            <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-gray-400 transition-transform duration-300 text-sm"></i>
                        </button>
                        <div x-show="open" x-collapse.duration.500ms class="p-5 pt-0 border-t border-gray-100 text-gray-700">
                            <p class="mb-3">
                                Una vez en la lista de <b>"Mis Recetas"</b>, encontrarás los botones de acción junto a cada entrada:
                            </p>
                            <ul class="list-disc ml-6 space-y-1">
                                <li><b>Ver</b> — para revisar el plato completo.</li>
                                <li><b>Editar</b> — para modificar cualquier detalle de la receta.</li>
                                <li><b>Eliminar</b> — para borrar la receta de forma permanente (se pedirá confirmación).</li>
                            </ul>
                        </div>
                    </div>

                    <div x-data="{ open: false }" class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                        <button @click="open = !open" class="w-full text-left p-5 font-semibold text-primary-dark flex justify-between items-center hover:bg-gray-50 transition duration-150">
                            <span class="text-xl"><i class="fas fa-eye text-blue-500 mr-3"></i> 👀 Gestión y Visualización de Perfil</span>
                            <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-gray-400 transition-transform duration-300 text-sm"></i>
                        </button>
                        <div x-show="open" x-collapse.duration.500ms class="p-5 pt-0 border-t border-gray-100 text-gray-700">
                            <ul class="list-disc ml-6 space-y-1">
                                <li><b>Ver Mi Perfil Público:</b> Visualiza tu información (nombre, biografía, foto) tal como la verán los usuarios.</li>
                                <li><b>Editar Datos de Cuenta:</b> Actualiza tu nombre, apellido y contraseña. <b>El correo electrónico no puede ser modificado.</b></li>
                                <li><b>Gestionar Biografía:</b> Agrega o modifica tu biografía y experiencia como maestro de ChefEnCuna.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="p-6 bg-primary-dark rounded-xl shadow-2xl text-white border-b-4 border-accent">
                        <h2 class="text-2xl font-bold mb-2 flex items-center">
                            <i class="fas fa-tools mr-3 text-accent"></i> Contacto de Soporte Técnico
                        </h2>
                        <p class="text-gray-300">
                            Si necesitas asistencia técnica, tienes problemas con tu cuenta, o requieres ayuda con errores de la plataforma, por favor escríbenos a:
                        </p>
                        <div class="mt-3 text-xl font-extrabold tracking-wider text-accent">
                            soporte@chefcuna.com
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>