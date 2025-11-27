<?php
session_start();
require 'config/bd.php'; 

$message = '';
$message_type = '';
$recipes = [];

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    header('Location: login.php');
    exit;
}
if (isset($_GET['delete_id'])) {
    $recipe_id = (int)$_GET['delete_id'];

    if ($recipe_id > 0) {
        try {
            $stmt_receta = $pdo->prepare("DELETE FROM recetas WHERE id = ?");
            $stmt_receta->execute([$recipe_id]);

            if ($stmt_receta->rowCount() > 0) {
                $message = "✅ Receta ID: {$recipe_id} eliminada exitosamente.";
                $message_type = 'success';
            } else {
                $message = "⚠️ Receta con ID: {$recipe_id} no encontrada o ya eliminada.";
                $message_type = 'warning';
            }
        } catch (PDOException $e) {
            $message = "❌ Error al eliminar la receta: " . $e->getMessage();
            $message_type = 'error';
        }
    }
    header('Location: admin_manage_recipes.php');
    exit;
}
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

function truncateText($text, $maxLength = 80) {
    if (strlen($text) > $maxLength) {
        return substr($text, 0, $maxLength) . '...';
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
    <style>
        :root { --primary: #ff6b6b; --secondary: #4ecdc4; --dark: #2d3436; --light: #f7f1e3; }
        body { font-family: 'Inter', sans-serif; background-color: var(--light); }
        .header-bg { background-color: var(--dark); }
        .btn-primary { background-color: var(--primary); color: white; transition: background-color 0.2s; }
        .btn-primary:hover { background-color: #d84a4a; }
        .btn-success { background-color: #4CAF50; color: white; transition: background-color 0.2s; }
        .btn-success:hover { background-color: #45a049; }
        .btn-warning { background-color: #ff9800; color: white; transition: background-color 0.2s; }
        .btn-warning:hover { background-color: #e68a00; }
        .btn-info { background-color: var(--secondary); color: var(--dark); transition: background-color 0.2s; }
        .btn-info:hover { background-color: #3aa6a0; }
        .table-header-bg { background-color: #e2e8f0; } /* Tailwind: gray-200 */
        .card-shadow { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); }
        :root { --primary: #ff6b6b; --secondary: #4ecdc4; --dark: #2d3436; --light: #f7f1e3; }
        body { font-family: 'Inter', sans-serif; background-color: var(--light); }
        .header-bg { background-color: var(--dark); }
        .btn-primary { background-color: var(--primary); color: white; transition: background-color 0.2s; }
        .btn-primary:hover { background-color: #d84a4a; }
        .btn-secondary { background-color: var(--secondary); color: var(--dark); transition: background-color 0.2s; }
        .btn-secondary:hover { background-color: #3aa6a0; }
        .table-header { background-color: var(--dark); color: white; }
    </style>
</head>
<body>

    <nav class="header-bg p-4 shadow-lg">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold text-white">ChefEnCuna Admin</h1>
            <div class="flex space-x-4">
                <a href="admin_dashboard.php" class="btn-secondary px-4 py-2 rounded-lg font-semibold hover:opacity-90 transition duration-150">
                    <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                </a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        <div class="bg-white rounded-xl card-shadow p-6 md:p-10">
            
            <header class="mb-8 border-b pb-4 flex justify-between items-center">
                <h2 class="text-3xl font-extrabold text-dark flex items-center">
                    <i class="fas fa-utensils mr-3 text-primary"></i> Gestión de Recetas
                </h2>
                <a href="admin_add_recipe.php" class="btn-primary px-4 py-2 rounded-lg font-semibold hover:opacity-90 transition duration-150 flex items-center">
                    <i class="fas fa-plus mr-2"></i> Nueva Receta
                </a>
            </header>

            <?php if ($message): ?>
                <div class="p-4 mb-6 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border-green-400' : ($message_type === 'warning' ? 'bg-yellow-100 text-yellow-700 border-yellow-400' : 'bg-red-100 text-red-700 border-red-400'); ?> border-l-4" role="alert">
                    <p class="font-bold"><?php echo $message; ?></p>
                </div>
            <?php endif; ?>
            
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="table-header-bg">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Título</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Descripción</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Instructor</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Fecha Publicación</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($recipes)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay recetas disponibles. ¡Crea la primera!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recipes as $recipe): ?>
                                <tr class="hover:bg-gray-50 transition duration-100">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold"><?php echo htmlspecialchars($recipe['titulo']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs">
                                        <?php echo htmlspecialchars(truncateText($recipe['descripcion'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?php if ($recipe['instructor_nombre']): ?>
                                            <?php echo htmlspecialchars($recipe['instructor_nombre'] . ' ' . $recipe['instructor_apellido']); ?>
                                        <?php else: ?>
                                            <span class="text-red-500 font-medium">No Asignado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-xs text-gray-500">
                                        <?php echo date('Y-m-d', strtotime($recipe['fecha_publicacion'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <div class="flex justify-center space-x-2">
                                            <a href="admin_edit_recipe.php?id=<?php echo $recipe['id']; ?>" class="btn-warning p-2 rounded-full leading-none" title="Editar Metadatos">
                                                <i class="fas fa-edit text-lg"></i>
                                            </a>
                                            <button 
                                                class="btn-primary p-2 rounded-full leading-none delete-btn" 
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

    <div id="delete-modal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
            <h3 class="text-xl font-bold mb-4 text-red-600 flex items-center"><i class="fas fa-exclamation-triangle mr-2"></i> Confirmar Eliminación</h3>
            <p id="modal-text" class="mb-6 text-gray-700">¿Está seguro de que desea eliminar permanentemente la receta X?</p>
            <div class="flex justify-end space-x-3">
                <button id="cancel-delete" class="px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-300 transition duration-150">Cancelar</button>
                <a href="#" id="confirm-delete" class="px-4 py-2 btn-primary font-semibold rounded-lg">Eliminar</a>
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

            deleteButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    const recipeId = e.currentTarget.getAttribute('data-id');
                    const recipeTitle = e.currentTarget.getAttribute('data-title');
                    
                    modalText.innerHTML = `¿Está seguro de que desea eliminar permanentemente la receta **${recipeTitle}** (ID: ${recipeId})? <br><span class="text-sm text-red-500 font-medium">Esta acción también eliminará todos los comentarios y registros de favoritos asociados.</span>`;
                    confirmDeleteLink.href = `admin_manage_recipes.php?delete_id=${recipeId}`;
                    modal.classList.remove('hidden');
                });
            });

            cancelDeleteButton.addEventListener('click', () => {
                modal.classList.add('hidden');
            });
            
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });

            const messageDiv = document.querySelector('[role="alert"]');
            if (messageDiv) {
                setTimeout(() => {
                    messageDiv.style.opacity = '0';
                    setTimeout(() => {
                        messageDiv.style.display = 'none';
                    }, 500);
                }, 5000);
            }
        });
    </script>

</body>
</html>