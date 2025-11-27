<?php
session_start();
require 'config/bd.php'; 

$message = '';
$message_type = '';

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id']; // ID del administrador actual

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $ingredientes = trim($_POST['ingredientes'] ?? '');
    $procedimiento = trim($_POST['procedimiento'] ?? '');
    $categoria = trim($_POST['categoria'] ?? ''); 
    
    $upload_dir = 'uploads/recipes/';
    $imagen_url = null; // Ruta a guardar en la DB
    $upload_ok = true; // Indicador para continuar con la DB

    if (empty($titulo) || empty($descripcion) || empty($ingredientes) || empty($procedimiento) || empty($categoria)) {
        $message = "⚠️ Por favor, complete todos los campos obligatorios: Título, Categoría, Descripción, Ingredientes y Procedimiento.";
        $message_type = 'warning';
        $upload_ok = false; // Detener si falta texto obligatorio
    } 
    
    // --- Lógica de Subida de Archivo ---
    if ($upload_ok && isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['imagen']['tmp_name'];
        $file_name = basename($_FILES['imagen']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
        $max_file_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file_ext, $allowed_extensions)) {
            $message = "⚠️ Error: Solo se permiten archivos JPG, JPEG, PNG y WEBP.";
            $message_type = 'warning';
            $upload_ok = false;
        } elseif ($_FILES['imagen']['size'] > $max_file_size) {
            $message = "⚠️ Error: El archivo es demasiado grande (máximo 5MB).";
            $message_type = 'warning';
            $upload_ok = false;
        } else {
            // Generar un nombre de archivo único
            $new_file_name = uniqid('recipe_', true) . '.' . $file_ext;
            $upload_path = $upload_dir . $new_file_name;

            // Asegurar que el directorio de subida existe
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (move_uploaded_file($file_tmp, $upload_path)) {
                $imagen_url = $upload_path; // Guardar la ruta relativa para la DB
            } else {
                $message = "❌ Error al subir el archivo de imagen. Verifique permisos de carpeta.";
                $message_type = 'error';
                $upload_ok = false;
            }
        }
    } elseif (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        $message = "❌ Error al subir la imagen. Código de error: " . $_FILES['imagen']['error'] . ". Intente con un archivo más pequeño.";
        $message_type = 'error';
        $upload_ok = false;
    }

    if ($upload_ok && empty($message)) { 
        try {
            $sql = "INSERT INTO recetas (
                        titulo, 
                        descripcion, 
                        ingredientes,    
                        procedimiento,
                        categoria,
                        imagen_url,    
                        usuario_id, 
                        fecha_publicacion
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $pdo->prepare($sql);
            
            $success = $stmt->execute([
                $titulo, 
                $descripcion, 
                $ingredientes, 
                $procedimiento, 
                $categoria,
                $imagen_url, // NUEVO VALOR
                $user_id 
            ]);

            if ($success) {
                $new_recipe_id = $pdo->lastInsertId();
                $message = "✅ Receta '{$titulo}' (ID: {$new_recipe_id}) creada exitosamente. Los datos de Categoría y la imagen fueron guardados.";
                $message_type = 'success';
                
                $titulo = $descripcion = $ingredientes = $procedimiento = $categoria = '';
                
            } else {
                $message = "❌ Error desconocido al guardar la receta en la base de datos.";
                $message_type = 'error';
            }

        } catch (PDOException $e) {
            $message = "❌ Error de base de datos: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}
$titulo = htmlspecialchars($titulo ?? '');
$descripcion = htmlspecialchars($descripcion ?? '');
$ingredientes = htmlspecialchars($ingredientes ?? '');
$procedimiento = htmlspecialchars($procedimiento ?? '');
$categoria = htmlspecialchars($categoria ?? ''); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefEnCuna - Añadir Receta</title>    
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
        .card-shadow { box-shadow: 0 10px 15px -3px rgba(153, 94, 94, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); }
        .input-style { border: 1px solid #0065fcff; border-radius: 0.5rem; padding: 0.75rem; width: 100%; transition: border-color 0.2s; }
        .input-style:focus { border-color: var(--primary); outline: none; }
    </style>
</head>
<body>

    <nav class="header-bg p-4 shadow-lg">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold text-white">ChefEnCuna Admin</h1>
            <div class="flex space-x-4">
                <a href="admin_manage_recipes.php" class="btn-secondary px-4 py-2 rounded-lg font-semibold hover:opacity-90 transition duration-150">
                    <i class="fas fa-arrow-left mr-2"></i> Volver a Recetas
                </a>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8">
        <div class="bg-white rounded-xl card-shadow p-6 md:p-10">
            
            <header class="mb-8 border-b pb-4">
                <h2 class="text-3xl font-extrabold text-dark flex items-center">
                    <i class="fas fa-plus-circle mr-3 text-primary"></i> Crear Nueva Receta
                </h2>
                <p class="text-gray-500 mt-2">Introduce todos los detalles de la receta, incluyendo ingredientes, la categoría, la imagen y el procedimiento completo.</p>
            </header>

            <?php if ($message): ?>
                <div class="p-4 mb-6 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border-green-400' : ($message_type === 'warning' ? 'bg-yellow-100 text-yellow-700 border-yellow-400' : 'bg-red-100 text-red-700 border-red-400'); ?> border-l-4" role="alert">
                    <p class="font-bold"><?php echo $message; ?></p>
                </div>
            <?php endif; ?>
            
            <form action="admin_add_recipe.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                
                <!-- Título -->
                <div>
                    <label for="titulo" class="block text-sm font-medium text-gray-700 mb-1">Título de la Receta <span class="text-red-500">*</span></label>
                    <input type="text" id="titulo" name="titulo" value="<?php echo $titulo; ?>" required class="input-style" placeholder="Ej: Pastel de Chocolate Clásico">
                </div>

                <!-- Categoría -->
                <div>
                    <label for="categoria" class="block text-sm font-medium text-gray-700 mb-1">Categoría <span class="text-red-500">*</span></label>
                    <input type="text" id="categoria" name="categoria" value="<?php echo $categoria; ?>" required class="input-style" placeholder="Ej: Repostería, Cena, Vegetariano">
                </div>
                
                <!-- Imagen de la Receta (NUEVO CAMPO) -->
                <div>
                    <label for="imagen" class="block text-sm font-medium text-black-700 mb-1">Imagen de la Receta</label>
                    <input type="file" id="imagen" name="imagen" accept="image/jpeg, image/png, image/webp" class="input-style file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-dark file:text-dark hover:file:bg-gray-700">
                    <p class="text-xs text-gray-400 mt-1">Formatos permitidos: JPG, PNG, WEBP. Máximo 5MB. Asegúrese de tener la columna 'imagen_url' y la carpeta 'uploads/recipes/' creadas.</p>
                </div>

                <!-- Descripción -->
                <div>
                    <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción<span class="text-red-500">*</span></label>
                    <textarea id="descripcion" name="descripcion" rows="3" required class="input-style" placeholder="Una breve descripción de lo que trata la receta..."><?php echo $descripcion; ?></textarea>
                </div>

                <!-- Ingredientes -->
                <div>
                    <label for="ingredientes" class="block text-sm font-medium text-gray-700 mb-1">Lista de Ingredientes <span class="text-red-500">*</span></label>
                    <textarea id="ingredientes" name="ingredientes" rows="8" required class="input-style" placeholder="Ej:&#10;500 gramos de carne molida&#10;1 paquete de spaghetti&#10;3 tazas de salsa de tomate"><?php echo $ingredientes; ?></textarea>
                </div>

                <!-- Procedimiento -->
                <div>
                    <label for="procedimiento" class="block text-sm font-medium text-gray-700 mb-1">Procedimiento Paso a Paso <span class="text-red-500">*</span></label>
                    <textarea id="procedimiento" name="procedimiento" rows="10" required class="input-style" placeholder="Ej:&#10;1. Hierve agua y cocina el spaghetti.&#10;2. Mezcla la carne y forma albóndigas..."><?php echo $procedimiento; ?></textarea>
                </div>

                <!-- Botón de Envío -->
                <div class="pt-4 border-t mt-6 flex justify-end">
                    <button type="submit" class="btn-primary px-6 py-3 rounded-lg font-bold text-lg flex items-center">
                        <i class="fas fa-save mr-2"></i> Crear Receta
                    </button>
                </div>
            </form>

        </div>
    </main>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const messageDiv = document.querySelector('[role="alert"]');
            if (messageDiv) {
                setTimeout(() => {
                    messageDiv.style.opacity = '0';
                    messageDiv.style.transition = 'opacity 0.5s ease-out';
                    setTimeout(() => {
                        messageDiv.style.display = 'none';
                    }, 500);
                }, 5000);
            }
        });
    </script>

</body>
</html>