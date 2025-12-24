<?php
session_start();
require 'config/bd.php'; 

$message = '';
$message_type = '';
$recipes = [];
$active_page = 'manage_recipes'; // Definida para el sidebar

// --- 1. Control de Acceso ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    header('Location: login.php');
    exit;
}

// --- 2. Procesamiento de Eliminación ---
if (isset($_GET['delete_id'])) {
    $recipe_id = (int)$_GET['delete_id'];

    if ($recipe_id > 0) {
        try {
            // Iniciar transacción (opcional, pero buena práctica si se eliminan datos en cascada)
            $pdo->beginTransaction();

            // 1. Eliminar datos asociados (ingredientes, pasos, etc. - asumiendo tablas relacionadas)
            // Aunque el esquema no se proporciona, es una buena práctica.
            // Si la FK en la BD usa ON DELETE CASCADE, esto no es necesario.
            // Ejemplo (descomentar si es necesario):
            // $stmt_ingredientes = $pdo->prepare("DELETE FROM receta_ingredientes WHERE receta_id = ?");
            // $stmt_ingredientes->execute([$recipe_id]);

            // 2. Eliminar la receta de la tabla principal
            $stmt_receta = $pdo->prepare("DELETE FROM recetas WHERE id = ?");
            $stmt_receta->execute([$recipe_id]);

            if ($stmt_receta->rowCount() > 0) {
                $pdo->commit();
                $_SESSION['flash_message'] = "✅ Receta ID: {$recipe_id} eliminada exitosamente.";
                $_SESSION['flash_type'] = 'success';
            } else {
                $pdo->rollBack();
                $_SESSION['flash_message'] = "⚠️ Receta con ID: {$recipe_id} no encontrada o ya eliminada.";
                $_SESSION['flash_type'] = 'warning';
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['flash_message'] = "❌ Error al eliminar la receta: " . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
        }
    }
    header('Location: admin_manage_recipes.php');
    exit;
}

// --- 3. Cargar Mensajes Flash (si existen) ---
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'];
    // Limpiar variables de sesión después de mostrar
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}


// --- 4. Cargar la Lista de Recetas ---
try {
    $sql = "SELECT 
                r.id, 
                r.titulo, 
                r.descripcion, 
                r.fecha_publicacion,
                u.nombre AS instructor_nombre,
                u.apellido AS instructor_apellido
            FROM recetas r
            LEFT JOIN usuarios u ON r.usuario_id = u.id 
            ORDER BY r.fecha_publicacion DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $message = "❌ Error al cargar las recetas: " . $e->getMessage();
    $message_type = 'error';
}

// --- Función de Utilidad ---
function truncateText($text, $maxLength = 80) {
    if (strlen($text) > $maxLength) {
        // Aseguramos que el corte no rompa una palabra
        $truncated = substr($text, 0, $maxLength);
        if (strrpos($truncated, ' ') !== false) {
             $truncated = substr($truncated, 0, strrpos($truncated, ' '));
        }
        return $truncated . '...';
    }
    return $text;
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
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-dark': '#1e272e', 
                        'primary-light': '#f4f7f6', 
                        'accent': '#4ecdc4',        // Color Secundario (teal)
                        'primary-accent': '#ff6b6b', // Color Principal (rojo/salmon)
                        'text-base': '#4a4a4a', 
                        'warning-btn': '#ff9800',   // Naranja para Editar
                    },
                     boxShadow: {
                        'sidebar': '5px 0 15px rgba(0, 0, 0, 0.05)',
                    }
                }
            }
        }
    </script>
    <style>
        body { 
        font-family: 'Inter', sans-serif; 
        background-color: #f4f7f6; 
        } 
        .nav-link {
            transition: background-color 0.2s, color 0.2s;
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
        .btn-primary { background-color: #ff6b6b; color: white; transition: background-color 0.2s; }
        .btn-primary:hover { background-color: #d84a4a; }
        .btn-warning { background-color: #ff9800; color: white; transition: background-color 0.2s; }
        .btn-warning:hover { background-color: #e68a00; }
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
            <!-- HE APLICADO LA LÓGICA DE CLASE 'ACTIVE' A TODOS LOS ENLACES -->
            <a href="admin_dashboard.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 <?php echo ($active_page === 'dashboard' ? 'active' : 'text-gray-600'); ?>">
                <i class="fas fa-chart-line w-5 mr-3"></i>
                Dashboard
            </a>
            
            <p class="text-xs text-gray-400 uppercase font-bold pt-4 pb-1 px-3">Gestión de Contenido</p>
            
            <a href="admin_manage_courses.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 <?php echo ($active_page === 'manage_courses' ? 'active' : 'text-gray-600'); ?>">
                <i class="fas fa-book w-5 mr-3 text-accent"></i>
                Cursos
            </a>

            <!-- ESTE ES EL ENLACE DE RECETAS - APLICACIÓN DE CLASE ACTIVE -->
            <a href="admin_manage_recipes.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 <?php echo ($active_page === 'manage_recipes' ? 'active' : 'text-gray-600'); ?>">
                <i class="fas fa-utensils w-5 mr-3 text-primary-accent"></i>
                Recetas
            </a>
            
            <p class="text-xs text-gray-400 uppercase font-bold pt-4 pb-1 px-3">Usuarios y Comunidad</p>

            <a href="admin_manage_users.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 <?php echo ($active_page === 'manage_users' ? 'active' : 'text-gray-600'); ?>">
                <i class="fas fa-users-cog w-5 mr-3 text-blue-500"></i>
                Cuentas de Usuarios
            </a>
            
            <a href="admin_manage_bios.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 <?php echo ($active_page === 'manage_bios' ? 'active' : 'text-gray-600'); ?>">
                <i class="fas fa-address-card w-5 mr-3 text-purple-500"></i>
                Biografías Maestros
            </a>

            <a href="foro_ayuda.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 <?php echo ($active_page === 'foro_ayuda' ? 'active' : 'text-gray-600'); ?>">
                <i class="fas fa-comments w-5 mr-3 text-green-500"></i>
                Moderar Foro
            </a>
            
            <a href="admin_faqs.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 <?php echo ($active_page === 'admin_faqs' ? 'active' : 'text-gray-600'); ?>">
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
    
    <!-- CONTENIDO PRINCIPAL -->
    <div class="flex-1 overflow-y-auto ml-64">
        
        <header class="bg-white shadow-md p-4 sticky top-0 z-10">
             <div class="flex justify-between items-center max-w-7xl mx-auto">
                <h2 class="text-xl font-bold text-text-base">
                    Gestión de Contenido / <span class="text-accent">Recetas</span>
                </h2>
                <a href="admin_add_recipe.php" class="px-4 py-2 rounded-full bg-accent text-primary-dark font-semibold hover:bg-teal-500 transition duration-150 text-sm inline-flex items-center">
                    <i class="fas fa-plus mr-2"></i> Nueva Receta
                </a>
            </div>
        </header>
        
        <main class="max-w-7xl mx-auto p-4 lg:p-10">
            <div class="bg-white rounded-xl shadow-2xl p-6 md:p-10">
                
                <header class="mb-8 border-b pb-4">
                    <h1 class="text-3xl font-extrabold text-primary-dark flex items-center">
                        <i class="fas fa-utensils mr-3 text-primary-accent"></i> Recetas Publicadas
                    </h1>
                    <p class="text-gray-500 mt-2">Administre las recetas que están visibles para los usuarios del sitio.</p>
                </header>

                <?php if ($message): ?>
                    <!-- Mensajes de feedback -->
                    <div class="p-4 mb-6 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border-green-500' : ($message_type === 'warning' ? 'bg-yellow-100 text-yellow-700 border-yellow-500' : 'bg-red-100 text-red-700 border-red-500'); ?> border-l-4" role="alert" style="transition: opacity 0.5s ease-out;">
                        <p class="font-bold"><?php echo $message; ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-primary-dark">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Título</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Descripción</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Autor/a</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">Publicación</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php if (empty($recipes)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-6 text-center text-sm text-gray-500 bg-gray-50">
                                        <i class="fas fa-box-open mr-2"></i> No hay recetas disponibles en la base de datos.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recipes as $recipe): ?>
                                    <tr class="hover:bg-gray-50 transition duration-100">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold"><?php echo htmlspecialchars($recipe['titulo']); ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-600 max-w-xs">
                                            <?php echo htmlspecialchars(truncateText($recipe['descripcion'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                            <?php if ($recipe['instructor_nombre']): ?>
                                                <?php echo htmlspecialchars($recipe['instructor_nombre'] . ' ' . $recipe['instructor_apellido']); ?>
                                            <?php else: ?>
                                                <span class="text-gray-500 italic">Administrador</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-xs text-gray-500">
                                            <?php echo date('Y-m-d', strtotime($recipe['fecha_publicacion'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                            <div class="flex justify-center space-x-2">
                                                <a href="admin_edit_recipe.php?id=<?php echo $recipe['id']; ?>" class="btn-warning p-2 rounded-full leading-none shadow-md hover:shadow-lg" title="Editar Metadatos">
                                                    <i class="fas fa-edit text-lg"></i>
                                                </a>
                                                <button 
                                                    class="btn-primary p-2 rounded-full leading-none shadow-md hover:shadow-lg delete-btn" 
                                                    data-id="<?php echo $recipe['id']; ?>" 
                                                    data-title="<?php echo htmlspecialchars($recipe['titulo']); ?>"
                                                    title="Eliminar Receta">
                                                    <i class="fas fa-trash-alt text-lg"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    </div>

    <!-- Modal de Confirmación de Eliminación -->
    <div id="delete-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center p-4 z-50 transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-8 transform transition-transform duration-300 scale-95">
            <h3 class="text-2xl font-bold mb-4 text-primary-accent flex items-center border-b pb-2"><i class="fas fa-exclamation-triangle mr-3"></i> Confirmar Eliminación</h3>
            <p id="modal-text" class="mb-6 text-gray-700 leading-relaxed"></p>
            <div class="flex justify-end space-x-3">
                <button id="cancel-delete" class="px-5 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-300 transition duration-150">
                    Cancelar
                </button>
                <a href="#" id="confirm-delete" class="px-5 py-2 btn-primary font-semibold rounded-lg inline-flex items-center">
                    <i class="fas fa-trash-alt mr-2"></i> Eliminar Permanentemente
                </a>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const deleteButtons = document.querySelectorAll('.delete-btn');
            const modal = document.getElementById('delete-modal');
            const modalText = document.getElementById('modal-text');
            const confirmDeleteLink = document.getElementById('confirm-delete');
            const cancelDeleteButton = document.getElementById('cancel-delete');
            const messageDiv = document.querySelector('[role="alert"]');

            // --- 1. Gestión de Modal de Eliminación ---
            deleteButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    const recipeId = e.currentTarget.getAttribute('data-id');
                    const recipeTitle = e.currentTarget.getAttribute('data-title');
                    
                    // Mejorado el mensaje del modal
                    modalText.innerHTML = `¿Está seguro de que desea eliminar permanentemente la receta **"${recipeTitle}"** (ID: ${recipeId})? <br><br><span class="text-sm text-red-600 font-medium flex items-center"><i class="fas fa-skull-crossbones mr-1"></i> Esta acción es irreversible y eliminará todos los datos asociados.</span>`;
                    confirmDeleteLink.href = `admin_manage_recipes.php?delete_id=${recipeId}`;
                    modal.classList.remove('hidden');
                });
            });

            cancelDeleteButton.addEventListener('click', () => {
                modal.classList.add('hidden');
            });
            
            // Cerrar modal al hacer clic fuera del contenido
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });

            // --- 2. Gestión de Mensajes Flash ---
            if (messageDiv) {
                // Función para ocultar el mensaje de forma gradual
                const hideMessage = () => {
                    messageDiv.style.opacity = '0';
                    setTimeout(() => {
                        messageDiv.remove();
                    }, 500); // Coincide con la duración de la transición CSS
                };
                
                // Ocultar después de 5 segundos
                setTimeout(hideMessage, 5000);
            }
        });
    </script>

</body>
</html>