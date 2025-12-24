<?php
session_start();
require 'config/bd.php'; 

if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? '') !== 'maestro') {
    header('Location: login.php');
    exit;
}

$user_name = $_SESSION['user_name'] ?? 'Maestro';
$maestro_id = $_SESSION['user_id'];
$active_page = 'cursos'; 
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

$mis_cursos = []; 

try {
    $stmt_mis_cursos = $pdo->prepare("
        SELECT
            c.id,
            c.titulo,
            c.fecha_creacion,
            COUNT(i.curso_id) AS total_alumnos
        FROM
            cursos c
        LEFT JOIN
            inscripciones i ON c.id = i.curso_id
        WHERE
            c.instructor_id = ?
        GROUP BY
            c.id, c.titulo, c.fecha_creacion
        ORDER BY
            c.fecha_creacion DESC
    ");
    $stmt_mis_cursos->execute([$maestro_id]);
    $mis_cursos = $stmt_mis_cursos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error consultando mis cursos: " . $e->getMessage());
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
            <a href="maestro_dashboard.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 text-gray-600">
                <i class="fas fa-th-large w-5 mr-3"></i>
                Dashboard
            </a>

            <div x-data="{ open: false }">
                <button @click="open = !open" 
                        class="nav-link w-full flex items-center justify-between p-3 font-semibold text-gray-600 transition duration-150">
                    <span class="flex items-center">
                        <i class="fas fa-user-cog w-5 mr-3"></i>
                        Gestión de Perfil
                    </span>
                    <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-xs transition-transform"></i>
                </button>
                <div x-show="open" class="ml-6 mt-1 space-y-1">
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

            <a href="maestro_cursos.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 <?php echo ($active_page === 'cursos') ? 'active' : 'text-gray-600'; ?>">
                <i class="fas fa-book-open w-5 mr-3"></i>Mis Cursos <?php if ($cursos_asignados > 0): ?>
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
                    Mis Cursos
                </h2>
                <a href="index.php" 
                   class="text-gray-500 hover:text-primary-dark transition duration-200 text-sm">
                    <i class="fas fa-home mr-1"></i> Ir a Inicio
                </a>
            </div>
        </header>

        <main class="p-6 md:p-10">
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-extrabold text-primary-dark mb-1">
                        <i class="fas fa-book-open text-accent mr-2"></i> Mis Cursos Asignados
                    </h1>
                    <p class="text-lg text-gray-500">
                        Actualmente gestiona <b><?php echo $cursos_asignados; ?></b> cursos en la plataforma.
                    </p>
                </div>
            </div>

            <section class="bg-white rounded-xl shadow-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Curso
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-32">
                                Alumnos Inscritos
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-32">
                                Fecha de Creación
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($mis_cursos as $curso): ?>
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-primary-dark">
                                    <?= htmlspecialchars($curso['titulo']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="font-bold text-primary-dark"><?= $curso['total_alumnos'] ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('d/m/Y', strtotime($curso['fecha_creacion'])) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
            
            <?php if (empty($mis_cursos)): ?>
                <div class="bg-white p-12 rounded-xl shadow-lg text-center text-gray-500 border-2 border-dashed border-gray-200 mt-6">
                    <i class="fas fa-clipboard-list text-5xl mb-4 text-gray-400"></i>
                    <p class="text-xl font-medium">Aún no tiene cursos asignados.</p>
                    <p class="mt-2 text-gray-600">Comience creando un nuevo curso para empezar a enseñar.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
</body>
</html>