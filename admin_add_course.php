<?php
session_start();
require 'config/bd.php'; 

$message = '';
$message_type = '';
$instructors = [];

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    header('Location: login.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, nombre, apellido FROM usuarios WHERE rol = 'maestro' ORDER BY nombre");
    $stmt->execute();
    $instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "❌ Error al cargar instructores: " . $e->getMessage();
    $message_type = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $nivel = trim($_POST['nivel']);
    $requisitos = trim($_POST['requisitos'] ?? NULL);
    $instructor_id = $_POST['instructor_id'] === '' ? NULL : (int)$_POST['instructor_id'];
    $duracion = trim($_POST['duracion'] ?? NULL);
    $objetivos = trim($_POST['objetivos'] ?? NULL);

    if (empty($titulo) || empty($descripcion) || empty($nivel)) {
        $message = "❌ Por favor, complete los campos Título, Descripción y Nivel.";
        $message_type = 'error';
    } else {
        try {
            $sql = "INSERT INTO cursos (titulo, descripcion, nivel, requisitos, instructor_id, duracion, objetivos) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                $titulo, 
                $descripcion, 
                $nivel, 
                $requisitos, 
                $instructor_id, 
                $duracion, 
                $objetivos
            ]);

            if ($success) {
                $last_id = $pdo->lastInsertId();
                $message = "✅ Curso '{$titulo}' creado exitosamente (ID: {$last_id}).";
                $message_type = 'success';
                $_POST = []; 
            } else {
                $message = "❌ Error desconocido al crear el curso.";
                $message_type = 'error';
            }
        } catch (PDOException $e) {
            $message = "❌ Error al insertar el nuevo curso: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

$default_titulo = $_POST['titulo'] ?? '';
$default_descripcion = $_POST['descripcion'] ?? '';
$default_nivel = $_POST['nivel'] ?? 'Principiante';
$default_requisitos = $_POST['requisitos'] ?? '';
$default_instructor_id = $_POST['instructor_id'] ?? '';
$default_duracion = $_POST['duracion'] ?? '';
$default_objetivos = $_POST['objetivos'] ?? '';
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
        .input-style { border: 1px solid #ccc; padding: 10px; border-radius: 8px; width: 100%; box-shadow: inset 0 1px 3px rgba(0,0,0,0.06); }
    </style>
</head>
<body>

    <nav class="header-bg p-4 shadow-lg">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold text-white">ChefEnCuna Admin</h1>
            <a href="admin_manage_courses.php" class="btn-secondary px-4 py-2 rounded-lg font-semibold hover:opacity-90 transition duration-150">
                <i class="fas fa-arrow-left mr-2"></i> Volver a Cursos
            </a>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8">
        <div class="bg-white rounded-xl shadow-2xl p-6 md:p-10">
            <header class="mb-8 border-b pb-4">
                <h2 class="text-3xl font-extrabold text-dark flex items-center">
                    <i class="fas fa-plus-circle mr-3 text-secondary"></i> Agregar Nuevo Curso
                </h2>
                <p class="text-gray-500 mt-2">Ingrese la información completa para crear un nuevo curso.</p>
            </header>

            <?php if ($message): ?>
                <div class="p-4 mb-6 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border-green-400' : 'bg-red-100 text-red-700 border-red-400'; ?> border-l-4" role="alert">
                    <p class="font-bold"><?php echo $message; ?></p>
                    <?php if ($message_type === 'success'): ?>
                        <p class="text-sm mt-1">Será redirigido a la lista de cursos en 3 segundos.</p>
                        <?php header('refresh:3; url=admin_manage_courses.php'); exit; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="admin_add_course.php" class="space-y-6">
                <div>
                    <label for="titulo" class="block text-sm font-medium text-gray-700 mb-1">Título del Curso <span class="text-red-500">*</span></label>
                    <input type="text" name="titulo" id="titulo" class="input-style" value="<?php echo htmlspecialchars($default_titulo); ?>" required>
                </div>
                <div>
                    <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción Detallada <span class="text-red-500">*</span></label>
                    <textarea name="descripcion" id="descripcion" rows="4" class="input-style" required><?php echo htmlspecialchars($default_descripcion); ?></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="nivel" class="block text-sm font-medium text-gray-700 mb-1">Nivel del Curso <span class="text-red-500">*</span></label>
                        <select name="nivel" id="nivel" class="input-style" required>
                            <?php 
                                $niveles = ['Principiante', 'Intermedio', 'Avanzado'];
                                foreach ($niveles as $nivel_op) {
                                    $selected = ($default_nivel === $nivel_op) ? 'selected' : '';
                                    echo "<option value=\"$nivel_op\" $selected>$nivel_op</option>";
                                }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label for="duracion" class="block text-sm font-medium text-gray-700 mb-1">Duración (Ej: 6 semanas, 10 horas)</label>
                        <input type="text" name="duracion" id="duracion" class="input-style" value="<?php echo htmlspecialchars($default_duracion); ?>">
                    </div>
                </div>
                <div>
                    <label for="requisitos" class="block text-sm font-medium text-gray-700 mb-1">Requisitos Previos (Opcional)</label>
                    <textarea name="requisitos" id="requisitos" rows="2" class="input-style"><?php echo htmlspecialchars($default_requisitos); ?></textarea>
                </div>
                <div>
                    <label for="objetivos" class="block text-sm font-medium text-gray-700 mb-1">Objetivos de Aprendizaje (Opcional)</label>
                    <textarea name="objetivos" id="objetivos" rows="3" class="input-style"><?php echo htmlspecialchars($default_objetivos); ?></textarea>
                </div>
                <div>
                    <label for="instructor_id" class="block text-sm font-medium text-gray-700 mb-1">Instructor Asignado</label>
                    <select name="instructor_id" id="instructor_id" class="input-style">
                        <option value="" <?php echo ($default_instructor_id === '') ? 'selected' : ''; ?>>-- Sin Asignar --</option>
                        <?php foreach ($instructors as $instructor): ?>
                            <?php 
                                $selected = ((int)$default_instructor_id === (int)$instructor['id']) ? 'selected' : '';
                                $nombre_completo = htmlspecialchars($instructor['nombre'] . ' ' . $instructor['apellido']);
                                echo "<option value=\"{$instructor['id']}\" $selected>$nombre_completo (ID: {$instructor['id']})</option>";
                            ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex justify-end pt-4 border-t mt-6">
                    <button type="submit" class="btn-primary flex items-center px-6 py-3 rounded-xl font-bold shadow-md hover:shadow-lg">
                        <i class="fas fa-plus mr-2"></i> Crear Curso
                    </button>
                </div>
            </form>

        </div>
    </main>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const messageDiv = document.querySelector('[role="alert"]');
            if (messageDiv && messageDiv.classList.contains('bg-red-100')) {
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 5000);
            }
        });
    </script>
</body>
</html>