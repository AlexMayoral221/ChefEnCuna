<?php
session_start();
require 'config/bd.php'; 

$message = '';
$message_type = '';
$courses = [];

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_course_id'])) {
    $delete_id = (int)$_POST['delete_course_id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM cursos WHERE id = ?");
        if ($stmt->execute([$delete_id]) && $stmt->rowCount() > 0) {
            $message = "✅ Curso (ID: $delete_id) eliminado exitosamente.";
            $message_type = 'success';
        } else {
            $message = "❌ Error: No se encontró el curso para eliminar o la eliminación falló.";
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        $message = "❌ Error al eliminar el curso: " . $e->getMessage();
        $message_type = 'error';
    }
}

try {
    $sql = "SELECT 
                c.id, 
                c.titulo AS nombre_curso, 
                c.descripcion, 
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
    $message = "❌ Error al obtener la lista de cursos: " . $e->getMessage();
    $message_type = 'error';
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
                <a href="admin_add_course.php" class="btn-primary px-4 py-2 rounded-lg font-semibold hover:opacity-90 transition duration-150">
                    <i class="fas fa-plus-circle mr-2"></i> Añadir Nuevo Curso
                </a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        <div class="bg-white rounded-xl shadow-2xl p-6 md:p-10">
            
            <header class="mb-8">
                <h2 class="text-3xl font-extrabold text-dark flex items-center">
                    <i class="fas fa-graduation-cap mr-3 text-secondary"></i> Gestión de Cursos
                </h2>
                <p class="text-gray-500 mt-2">Administra los cursos, asigna maestros y define el contenido de la plataforma.</p>
            </header>

            <?php if ($message): ?>
                <div class="p-4 mb-6 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border-green-400' : 'bg-red-100 text-red-700 border-red-400'; ?> border-l-4" role="alert">
                    <p class="font-bold"><?php echo $message; ?></p>
                </div>
            <?php endif; ?>

            <div class="overflow-x-auto shadow-lg rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="table-header">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Nombre del Curso</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider hidden sm:table-cell">Descripción</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Maestro</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider rounded-tr-lg">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($courses)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                    No hay cursos registrados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($courses as $course): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-900 font-semibold"><?php echo htmlspecialchars($course['nombre_curso']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500 hidden sm:table-cell max-w-xs overflow-hidden text-ellipsis">
                                        <?php echo htmlspecialchars(substr($course['descripcion'], 0, 50)) . (strlen($course['descripcion']) > 50 ? '...' : ''); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600">
                                        <?php echo htmlspecialchars($course['nombre_maestro'] ?? 'Sin Asignar'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <a href="admin_edit_course.php?id=<?php echo $course['id']; ?>" class="text-secondary hover:text-teal-700 mr-3 inline-flex items-center" title="Editar curso">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <form method="POST" action="admin_manage_courses.php" class="inline-block" onsubmit="return confirm('¿Estás seguro de que quieres eliminar el curso: <?php echo addslashes(htmlspecialchars($course['nombre_curso'])); ?>? Esta acción es irreversible.');">
                                            <input type="hidden" name="delete_course_id" value="<?php echo $course['id']; ?>">
                                            <button type="submit" class="text-red-500 hover:text-red-700 p-1" title="Eliminar curso">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const messageDiv = document.querySelector('[role="alert"]');
            if (messageDiv) {
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 5000);
            }
        });
    </script>
</body>
</html>