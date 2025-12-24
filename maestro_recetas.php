<?php
session_start();
require 'config/bd.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? '') !== 'maestro') {
    header('Location: login.php');
    exit;
}

$user_name = $_SESSION['user_name'] ?? 'Maestro';
$maestro_id = $_SESSION['user_id'];
$active_page = 'recetas'; 

$message = '';
$recetas = [];

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
    error_log("Error contando cursos asignados: " . $e->getMessage());
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
    error_log("Error contando preguntas pendientes del foro: " . $e->getMessage());
    $preguntas_pendientes = 0; 
}

try {
    $stmt = $pdo->prepare("
        SELECT id, titulo, descripcion, fecha_publicacion
        FROM recetas
        WHERE usuario_id = ?
        ORDER BY fecha_publicacion DESC, id DESC
    ");
    $stmt->execute([$maestro_id]);
    $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error consultando recetas para maestro $maestro_id: " . $e->getMessage());
    $message = "❌ Error consultando recetas. Por favor, intente más tarde.";
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
            <a href="maestro_dashboard.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 <?php echo ($active_page === 'dashboard') ? 'active' : 'text-gray-600'; ?>">
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

            <a href="maestro_recetas.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 <?php echo ($active_page === 'recetas') ? 'active' : 'text-gray-600'; ?>">
                <i class="fas fa-utensils w-5 mr-3"></i>
                Mis Recetas
            </a>

             <a href="maestro_cursos.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 <?php echo ($active_page === 'cursos') ? 'active' : 'text-gray-600'; ?>">
                <i class="fas fa-book-open w-5 mr-3"></i>Mis Cursos 
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

            <a href="ayuda.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
                <i class="fas fa-question-circle w-5 mr-3"></i>
                Ayuda y Soporte
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
                        Mis Recetas
                    </h2>
                <a href="index.php" class="text-gray-500 hover:text-primary-dark transition duration-200 text-sm">
                    <i class="fas fa-home mr-1"></i> Ir a Inicio
                </a>
            </div>
        </header>

        <main class="p-6 md:p-10">
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-extrabold text-primary-dark mb-1">
                        <i class="fas fa-utensils text-accent mr-2"></i> Gestión de Recetas
                    </h1>
                    <p class="text-lg text-gray-500">
                        Administre el contenido que ha compartido con la comunidad.
                    </p>
                </div>
                
                <a href="crear_receta.php" class="bg-accent text-white font-semibold px-5 py-2.5 rounded-lg shadow-md hover:bg-teal-500 transition duration-200 flex items-center transform hover:scale-[1.03]">
                    <i class="fas fa-plus mr-2"></i> Crear Nueva Receta
                </a>
            </div>

            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg bg-red-100 text-secondary-red border border-secondary-red/50 font-medium flex items-center">
                    <i class="fas fa-exclamation-triangle mr-3"></i> <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($recetas)): ?>
                <div class="bg-white p-12 rounded-xl shadow-lg text-center text-gray-500 border-2 border-dashed border-gray-200">
                    <i class="fas fa-sad-tear text-5xl mb-4 text-gray-400"></i>
                    <p class="text-xl font-medium">Aún no has creado ninguna receta.</p>
                    <p class="mt-2 text-gray-600">¡Es el momento perfecto para compartir tu primera creación culinaria!</p>
                </div>
            <?php else: ?>
                <div class="grid gap-6">

                    <?php foreach ($recetas as $r): ?>
                        <?php
                            $imgPath = null;
                            foreach (["jpg","png","webp"] as $ext){
                                $path = "img/recetas/".$r['id'].".".$ext;
                                if (file_exists($path)){ $imgPath = $path; break; }
                            }
                        ?>

                        <article class="bg-white rounded-xl shadow-lg p-5 flex transition duration-300 hover:shadow-card-hover border-l-4 border-accent/70">
                            <div class="w-32 h-32 flex-shrink-0 mr-5">
                                <?php if ($imgPath): ?>
                                    <img src="<?= $imgPath ?>" alt="Imagen de <?= htmlspecialchars($r['titulo']) ?>" 
                                         class="w-full h-full object-cover rounded-lg border border-gray-100">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center bg-gray-50 rounded-lg text-gray-400 text-center text-sm p-2 border border-dashed border-gray-200">
                                        Sin<br>Imagen
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="flex-grow">
                                <h3 class="text-xl font-bold text-primary-dark mb-1">
                                    <?= htmlspecialchars($r['titulo']) ?>
                                </h3>

                                <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                                    <?= htmlspecialchars(mb_strlen($r['descripcion']) > 150 
                                        ? mb_substr($r['descripcion'], 0, 150) . "..." 
                                        : $r['descripcion']) ?>
                                </p>

                                <div class="flex items-center text-xs text-gray-400">
                                    <i class="fas fa-calendar-alt mr-1"></i>
                                    Publicado: <span class="ml-1 font-medium"><?= date('d/m/Y', strtotime(htmlspecialchars($r['fecha_publicacion']))) ?></span>
                                </div>
                            </div>

                            <div class="flex flex-col items-end justify-center gap-2 ml-4 flex-shrink-0 w-32">
                                
                                <a href="ver_receta.php?id=<?= $r['id'] ?>" class="w-full text-center text-sm font-semibold px-3 py-1 bg-accent/10 text-accent rounded hover:bg-accent/20 transition duration-150">
                                    <i class="fas fa-eye w-4 mr-1"></i> Ver
                                </a>

                                <a href="editar_receta.php?id=<?= $r['id'] ?>" class="w-full text-center text-sm font-semibold px-3 py-1 bg-yellow-100 text-yellow-700 rounded hover:bg-yellow-200 transition duration-150">
                                    <i class="fas fa-edit w-4 mr-1"></i> Editar
                                </a>

                                <form action="eliminar_receta.php" method="post" onsubmit="return confirm('¿Está seguro de eliminar la receta \"<?= addslashes($r['titulo']) ?>\"? Esta acción no se puede deshacer.');">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="w-full text-center text-sm font-semibold px-3 py-1 bg-secondary-red/10 text-secondary-red rounded hover:bg-secondary-red/20 transition duration-150">
                                        <i class="fas fa-trash-alt w-4 mr-1"></i> Eliminar
                                    </button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
</body>
</html>