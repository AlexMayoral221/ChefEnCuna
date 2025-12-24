<?php
session_start();
require 'config/bd.php'; 

$message = '';
$message_type = '';
$courses = [];
$active_page = 'manage_courses'; // Definida para el sidebar

// --- 1. Control de Acceso ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    header('Location: login.php');
    exit;
}

// --- 2. Procesamiento de Eliminación (Usando PRG y Transacciones) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_course_id'])) {
    $delete_id = (int)$_POST['delete_course_id'];

    if ($delete_id > 0) {
        try {
            $pdo->beginTransaction();

            // Aquí se debería añadir la lógica de eliminación en cascada si no la maneja la BD
            // Ej: DELETE FROM lecciones WHERE curso_id = ?

            // Eliminar el curso principal
            $stmt = $pdo->prepare("DELETE FROM cursos WHERE id = ?");
            $stmt->execute([$delete_id]);

            if ($stmt->rowCount() > 0) {
                $pdo->commit();
                $_SESSION['flash_message'] = "✅ Curso (ID: {$delete_id}) eliminado exitosamente.";
                $_SESSION['flash_type'] = 'success';
            } else {
                $pdo->rollBack();
                $_SESSION['flash_message'] = "⚠️ Curso con ID: {$delete_id} no encontrado o ya eliminado.";
                $_SESSION['flash_type'] = 'warning';
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['flash_message'] = "❌ Error al eliminar el curso: " . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
        }
    }
    // Redirigir para evitar re-envío del formulario (PRG pattern)
    header('Location: admin_manage_courses.php');
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

// --- 4. Lógica para Obtener Cursos ---
try {
    $sql = "SELECT 
                c.id, 
                c.titulo AS nombre_curso, 
                c.descripcion, 
                c.nivel,
                u.nombre AS nombre_maestro 
            FROM 
                cursos c
            LEFT JOIN 
                usuarios u ON c.instructor_id = u.id
            ORDER BY 
                c.id DESC";
    
    $stmt = $pdo->query($sql);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si la carga de cursos falla, solo mostramos el error sin interrumpir el flujo del HTML
    $message = ($message ? $message . '<br>' : '') . "❌ Error al obtener la lista de cursos: " . $e->getMessage();
    $message_type = $message_type === 'success' ? 'warning' : 'error'; // Priorizar error
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
                        'accent': '#4ecdc4',        
                        'primary-accent': '#ff6b6b', 
                        'text-base': '#4a4a4a', 
                        'dark': '#2d3436',      
                        'light': '#f7f1e3',     
                    },
                     boxShadow: {
                        'sidebar': '5px 0 15px rgba(0, 0, 0, 0.05)',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f7f6; } 
        
        /* Estilos de la Barra Lateral (Sidebar) */
        .nav-link {
            transition: background-color 0.2s, color 0.2s;
        }
        .nav-link.active {
            background-color: #ff6b6b; /* Color Principal */
            color: white;
            border-radius: 0.5rem;
        }
        .nav-link:not(.active):hover {
            background-color: rgba(255, 107, 107, 0.1); 
            color: #ff6b6b;
            border-radius: 0.5rem;
        }
        
        .table-header { background-color: #2d3436; color: white; } 
        
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
            <a href="admin_dashboard.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 <?php echo ($active_page === 'dashboard' ? 'active' : 'text-gray-600'); ?>">
                <i class="fas fa-chart-line w-5 mr-3"></i>
                Dashboard
            </a>
            
            <p class="text-xs text-gray-400 uppercase font-bold pt-4 pb-1 px-3">Gestión de Contenido</p>
            
            <!-- ENLACE DE CURSOS ACTIVO -->
            <a href="admin_manage_courses.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 <?php echo ($active_page === 'manage_courses' ? 'active' : 'text-gray-600'); ?>">
                <i class="fas fa-book w-5 mr-3 text-accent"></i>
                Cursos
            </a>

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
                    Gestión de <span class="text-accent">Cursos</span>
                </h2>
            </div>
        </header>
        
        <main class="p-4 lg:p-10">
            <div class="bg-white rounded-xl shadow-2xl p-6 md:p-10">
                
                <header class="mb-8 flex justify-between items-start flex-wrap">
                    <div>
                        <h1 class="text-4xl font-extrabold text-dark flex items-center mb-2">
                            <i class="fas fa-graduation-cap mr-3 text-accent"></i> Catálogo de Cursos
                        </h1>
                        <p class="text-gray-500 mt-2 text-lg">Administra los cursos, asigna maestros y define el contenido de la plataforma.</p>
                    </div>
                    <a href="admin_add_course.php" 
                       class="mt-4 sm:mt-0 px-6 py-2 rounded-full bg-primary-accent text-white font-semibold hover:opacity-90 transition duration-150 shadow-lg inline-flex items-center">
                        <i class="fas fa-plus-circle mr-2"></i> Nuevo Curso
                    </a>
                </header>

                <?php if ($message): ?>
                    <div class="p-4 mb-6 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border-green-500' : ($message_type === 'warning' ? 'bg-yellow-100 text-yellow-700 border-yellow-500' : 'bg-red-100 text-red-700 border-red-500'); ?> border-l-4" role="alert" style="transition: opacity 0.5s ease-out;">
                        <p class="font-bold"><?php echo $message; ?></p>
                    </div>
                <?php endif; ?>

                <!-- Tabla de Cursos -->
                <div class="overflow-x-auto shadow-lg rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="table-header">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider rounded-tl-lg">Curso</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider hidden md:table-cell">Maestro</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider hidden sm:table-cell">Nivel</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider hidden lg:table-cell">Descripción</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider rounded-tr-lg">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($courses)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-8 whitespace-nowrap text-center text-lg text-gray-500 font-semibold">
                                        <i class="fas fa-folder-open mr-2 text-4xl text-gray-300 block mb-2"></i>
                                        No hay cursos registrados en el sistema.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($courses as $course): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm text-gray-900 font-semibold">
                                            <?php echo htmlspecialchars($course['nombre_curso']); ?>
                                            <span class="block text-xs text-gray-400 mt-1 sm:hidden">
                                                Maestro: <?php echo htmlspecialchars($course['nombre_maestro'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 hidden md:table-cell">
                                            <?php echo htmlspecialchars($course['nombre_maestro'] ?? 'Sin Asignar'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium hidden sm:table-cell">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php 
                                                    $level = htmlspecialchars($course['nivel']);
                                                    if ($level === 'Básico') echo 'bg-green-100 text-green-800';
                                                    else if ($level === 'Intermedio') echo 'bg-yellow-100 text-yellow-800';
                                                    else if ($level === 'Avanzado') echo 'bg-red-100 text-red-800';
                                                    else echo 'bg-gray-100 text-gray-800';
                                                ?>">
                                                <?php echo $level; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 hidden lg:table-cell max-w-sm overflow-hidden text-ellipsis">
                                            <?php echo htmlspecialchars(substr($course['descripcion'], 0, 70)) . (strlen($course['descripcion']) > 70 ? '...' : ''); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                            <!-- Botón Editar -->
                                            <a href="admin_edit_course.php?id=<?php echo $course['id']; ?>" class="text-accent hover:text-teal-700 mr-3 inline-flex items-center p-2 rounded-full transition duration-150 hover:bg-gray-100" title="Editar curso">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <!-- Botón Eliminar - Ahora abre el modal -->
                                            <button 
                                                class="text-primary-accent hover:text-red-700 p-2 rounded-full transition duration-150 hover:bg-gray-100 delete-btn" 
                                                data-id="<?php echo $course['id']; ?>" 
                                                data-title="<?php echo htmlspecialchars($course['nombre_curso']); ?>"
                                                title="Eliminar curso">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
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
            
            <form id="delete-form" method="POST" action="admin_manage_courses.php" class="flex justify-end space-x-3">
                <input type="hidden" name="delete_course_id" id="modal-delete-id" value="">

                <button type="button" id="cancel-delete" class="px-5 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-300 transition duration-150">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2 bg-primary-accent text-white font-semibold rounded-lg hover:bg-red-700 transition duration-150 inline-flex items-center">
                    <i class="fas fa-trash-alt mr-2"></i> Eliminar Permanentemente
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const deleteButtons = document.querySelectorAll('.delete-btn');
            const modal = document.getElementById('delete-modal');
            const modalText = document.getElementById('modal-text');
            const modalDeleteIdInput = document.getElementById('modal-delete-id');
            const cancelDeleteButton = document.getElementById('cancel-delete');
            const messageDiv = document.querySelector('[role="alert"]');

            // --- 1. Gestión de Modal de Eliminación ---
            deleteButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    const courseId = e.currentTarget.getAttribute('data-id');
                    const courseTitle = e.currentTarget.getAttribute('data-title');
                    
                    modalText.innerHTML = `¿Está seguro de que desea eliminar permanentemente el curso **"${courseTitle}"** (ID: ${courseId})? <br><br><span class="text-sm text-red-600 font-medium flex items-center"><i class="fas fa-skull-crossbones mr-1"></i> Esta acción es irreversible y eliminará todo el contenido asociado (lecciones, etc.).</span>`;
                    
                    // Asignar el ID al input hidden del formulario del modal
                    modalDeleteIdInput.value = courseId;
                    
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