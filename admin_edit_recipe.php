<?php
session_start();
require 'config/bd.php'; 

$message = '';
$message_type = '';
$recipe_data = null;
$instructors = [];

// 1. COMPROBACIÓN DE ACCESO Y ROL
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin_manage_recipes.php');
    exit;
}

$recipe_id = (int)$_GET['id'];

// 2. CARGAR DATOS INICIALES Y AUTORES
try {
    // Cargar la lista de posibles autores (administradores y maestros)
    $stmt_users = $pdo->prepare("SELECT id, nombre, apellido FROM usuarios WHERE rol IN ('maestro', 'administrador') ORDER BY nombre");
    $stmt_users->execute();
    $instructors = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

    // Cargar los datos de la receta, incluyendo la ruta de la imagen
    $stmt_recipe = $pdo->prepare("SELECT id, usuario_id, titulo, descripcion, ingredientes, procedimiento, categoria, imagen_ruta FROM recetas WHERE id = ?");
    $stmt_recipe->execute([$recipe_id]);
    $recipe_data = $stmt_recipe->fetch(PDO::FETCH_ASSOC);

    if (!$recipe_data) {
        $message = "❌ Error: Receta con ID {$recipe_id} no encontrada.";
        $message_type = 'error';
        $recipe_id = null; 
    }

} catch (PDOException $e) {
    $message = "❌ Error al cargar datos iniciales: " . $e->getMessage();
    $message_type = 'error';
}

// 3. PROCESAR LA ACTUALIZACIÓN DEL FORMULARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $recipe_id) {
    // Recoger y sanear todos los campos, incluidos los nuevos
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $ingredientes = trim($_POST['ingredientes'] ?? '');
    $procedimiento = trim($_POST['procedimiento'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $usuario_id = (int)($_POST['usuario_id'] ?? 0);
    
    // Inicializar variables para la gestión de la imagen
    $new_imagen_ruta = $recipe_data['imagen_ruta']; 
    $image_updated = false;
    $has_upload_error = false;

    // Validación de todos los campos obligatorios
    if (empty($titulo) || empty($descripcion) || empty($ingredientes) || empty($procedimiento) || empty($categoria) || $usuario_id <= 0) {
        $message = "⚠️ Por favor, complete todos los campos obligatorios (Título, Categoría, Descripción, Ingredientes, Procedimiento, e Autor).";
        $message_type = 'warning';
        
        // Si hay un error, actualizar $recipe_data con los valores POST para no perderlos
        $recipe_data['titulo'] = $titulo;
        $recipe_data['descripcion'] = $descripcion;
        $recipe_data['ingredientes'] = $ingredientes;
        $recipe_data['procedimiento'] = $procedimiento;
        $recipe_data['categoria'] = $categoria;
        $recipe_data['usuario_id'] = $usuario_id;
        
    } else {
        
        // --- GESTIÓN DE SUBIDA DE IMAGEN ---
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/recetas/'; // Directorio donde se guardarán las imágenes
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_info = pathinfo($_FILES['imagen']['name']);
            $extension = strtolower($file_info['extension']);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($extension, $allowed_extensions)) {
                $unique_filename = uniqid('receta_', true) . '.' . $extension;
                $target_path = $upload_dir . $unique_filename;

                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $target_path)) {
                    // Eliminación opcional de la imagen antigua
                    if (!empty($recipe_data['imagen_ruta']) && file_exists($recipe_data['imagen_ruta'])) {
                        // Verifica si la imagen actual no es una ruta por defecto antes de borrar
                        if (strpos($recipe_data['imagen_ruta'], 'placehold') === false) {
                            unlink($recipe_data['imagen_ruta']);
                        }
                    }

                    $new_imagen_ruta = $target_path;
                    $image_updated = true;
                } else {
                    $message = "❌ Error al subir el archivo de imagen.";
                    $message_type = 'error';
                    $has_upload_error = true;
                }
            } else {
                $message = "❌ Formato de archivo no permitido. Solo se aceptan JPG, JPEG, PNG o WEBP.";
                $message_type = 'error';
                $has_upload_error = true;
            }
        }
        // --- FIN GESTIÓN DE SUBIDA DE IMAGEN ---


        if (!$has_upload_error) {
            try {
                $sql_update_fields = [
                    'titulo = :titulo', 
                    'descripcion = :descripcion', 
                    'ingredientes = :ingredientes', 
                    'procedimiento = :procedimiento',
                    'categoria = :categoria', 
                    'usuario_id = :usuario_id'
                ];

                $update_params = [
                    ':titulo' => $titulo,
                    ':descripcion' => $descripcion,
                    ':ingredientes' => $ingredientes,
                    ':procedimiento' => $procedimiento,
                    ':categoria' => $categoria,
                    ':usuario_id' => $usuario_id,
                    ':id' => $recipe_id
                ];

                // Añadir el campo de imagen a la consulta si se subió una nueva
                if ($image_updated) {
                    $sql_update_fields[] = 'imagen_ruta = :imagen_ruta';
                    $update_params[':imagen_ruta'] = $new_imagen_ruta;
                }
                
                $sql_update = "UPDATE recetas SET " . implode(', ', $sql_update_fields) . " WHERE id = :id";
                
                $stmt_update = $pdo->prepare($sql_update);
                
                $stmt_update->execute($update_params);

                // Si se actualizaron filas o si se subió una imagen (que no cambia rowCount si solo eso cambió)
                if ($stmt_update->rowCount() > 0 || $image_updated) { 
                    $message = "✅ La receta '{$titulo}' ha sido actualizada exitosamente.";
                    $message_type = 'success';
                    
                    // Volver a cargar los datos frescos de la DB
                    $stmt_recipe->execute([$recipe_id]);
                    $recipe_data = $stmt_recipe->fetch(PDO::FETCH_ASSOC);
                } else {
                    $message = "ℹ️ No se realizaron cambios en los campos de texto o los datos eran idénticos.";
                    $message_type = 'info';
                }

            } catch (PDOException $e) {
                $message = "❌ Error al actualizar la receta: " . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// Si la solicitud no es POST y hubo un error de carga, $recipe_data puede ser null. 
// Si la solicitud fue POST y hubo un error, $recipe_data ya fue actualizado con los valores POST.

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefEnCuna - Editar Receta</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"> 
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#ff6b6b', // Rojo vibrante
                        'dark': '#2d3436', // Gris oscuro
                        'light': '#f7f1e3', // Crema muy claro
                    },
                    boxShadow: {
                        'card': '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
                    }
                }
            }
        }
    </script>
    <style>
        .input-focus:focus { 
            border-color: #ff6b6b; /* var(--primary) */
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.4);
        }
        .btn-primary { background-color: #ff6b6b; color: white; transition: background-color 0.2s; }
        .btn-primary:hover { background-color: #d84a4a; }
        .btn-secondary-link { background-color: #4ecdc4; color: #2d3436; transition: background-color 0.2s; }
        .btn-secondary-link:hover { background-color: #3aa6a0; }
    </style>
</head>
<body class="font-sans bg-light text-dark">

    <nav class="bg-dark p-4 shadow-lg">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold text-white">ChefEnCuna Admin</h1>
            <div class="flex space-x-4">
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8">
        <a href="admin_manage_recipes.php" class="mb-4 inline-flex items-center text-dark hover:text-primary transition duration-150">
            <i class="fas fa-arrow-left mr-2"></i> Volver a Recetas
        </a>
        
        <div class="bg-white rounded-xl card-shadow p-6 md:p-10 mt-4">
            
            <header class="mb-8 border-b pb-4">
                <h2 class="text-3xl font-extrabold text-dark flex items-center">
                    <i class="fas fa-edit mr-3 text-primary"></i> Editar Receta
                </h2>
                <?php if ($recipe_data): ?>
                    <p class="text-gray-500 mt-1">Título actual: <b><?php echo htmlspecialchars($recipe_data['titulo']); ?></b></p>
                <?php endif; ?>
            </header>

            <?php if ($message): ?>
                <div class="p-4 mb-6 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border-green-400' : ($message_type === 'info' ? 'bg-blue-100 text-blue-700 border-blue-400' : 'bg-red-100 text-red-700 border-red-400'); ?> border-l-4" role="alert">
                    <p class="font-bold"><?php echo $message; ?></p>
                </div>
            <?php endif; ?>

            <?php if ($recipe_data): ?>
                <!-- Formulario completo de edición: Se añade enctype para permitir la subida de archivos -->
                <form action="admin_edit_recipe.php?id=<?php echo $recipe_id; ?>" method="POST" enctype="multipart/form-data">
                    
                    <!-- IMAGEN DE LA RECETA (NUEVO) -->
                    <div class="mb-6 border p-4 rounded-xl bg-gray-50 shadow-inner">
                        <label class="block text-lg font-bold text-gray-800 mb-3 flex items-center border-b pb-2">
                            <i class="fas fa-camera mr-2 text-primary"></i> Imagen de la Receta
                        </label>

                        <!-- Vista previa de la imagen actual -->
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 mb-2">Imagen actual:</p>
                            <?php 
                                $image_path = !empty($recipe_data['imagen_ruta']) ? htmlspecialchars($recipe_data['imagen_ruta']) : 'https://placehold.co/400x300/ff6b6b/ffffff?text=Sin+Imagen';
                            ?>
                            <!-- Se utiliza max-w-sm para que la imagen se vea bien en pantallas pequeñas -->
                            <img src="<?php echo $image_path; ?>" onerror="this.onerror=null; this.src='https://placehold.co/400x300/ff6b6b/ffffff?text=Error+al+cargar+imagen';" alt="Imagen actual de la receta" class="w-full max-w-sm h-auto object-cover rounded-lg shadow-md border-2 border-primary/50">
                        </div>

                        <label for="imagen" class="block text-sm font-medium text-gray-700 mb-2 mt-4">Cambiar Imagen (JPG, PNG, WEBP)</label>
                        <input type="file" id="imagen" name="imagen" accept="image/jpeg, image/png, image/webp"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-red-500">
                        <p class="text-xs text-gray-500 mt-1">Sube un nuevo archivo para reemplazar la imagen actual. (Máx. 2MB recomendado)</p>
                    </div>

                    <!-- TÍTULO -->
                    <div class="mb-6">
                        <label for="titulo" class="block text-sm font-medium text-gray-700 mb-2">Título de la Receta</label>
                        <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($recipe_data['titulo']); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg input-focus" required>
                    </div>

                    <!-- CATEGORÍA -->
                    <div class="mb-6">
                        <label for="categoria" class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                        <input type="text" id="categoria" name="categoria" value="<?php echo htmlspecialchars($recipe_data['categoria'] ?? ''); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg input-focus" placeholder="Ej: Repostería, Cena, Vegetariano" required>
                        <p class="text-xs text-gray-500 mt-1">Define la categoría principal de la receta.</p>
                    </div>

                    <!-- DESCRIPCIÓN -->
                    <div class="mb-6">
                        <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-2">Descripción Breve</label>
                        <textarea id="descripcion" name="descripcion" rows="4" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg input-focus" 
                                  required><?php echo htmlspecialchars($recipe_data['descripcion']); ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Esta es la descripción que aparece en el listado y la vista principal de la receta.</p>
                    </div>

                    <!-- INGREDIENTES -->
                    <div class="mb-6">
                        <label for="ingredientes" class="block text-sm font-medium text-gray-700 mb-2">Lista de Ingredientes (Separados por línea)</label>
                        <textarea id="ingredientes" name="ingredientes" rows="8" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg input-focus" 
                                  required placeholder="Ej:&#10;500 gramos de carne&#10;1 paquete de pasta"><?php echo htmlspecialchars($recipe_data['ingredientes'] ?? ''); ?></textarea>
                    </div>

                    <!-- PROCEDIMIENTO -->
                    <div class="mb-6">
                        <label for="procedimiento" class="block text-sm font-medium text-gray-700 mb-2">Procedimiento Paso a Paso</label>
                        <textarea id="procedimiento" name="procedimiento" rows="10" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg input-focus" 
                                  required placeholder="Ej:&#10;1. Calienta el aceite.&#10;2. Fríe la carne..."><?php echo htmlspecialchars($recipe_data['procedimiento'] ?? ''); ?></textarea>
                    </div>

                    <!-- AUTOR -->
                    <div class="mb-6">
                        <label for="usuario_id" class="block text-sm font-medium text-gray-700 mb-2">Instructor/Autor de la Receta</label>
                        <select id="usuario_id" name="usuario_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg input-focus bg-white" required>
                            <option value="">-- Seleccionar Instructor --</option>
                            <?php foreach ($instructors as $instructor): ?>
                                <option value="<?php echo $instructor['id']; ?>" 
                                        <?php echo $instructor['id'] == $recipe_data['usuario_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($instructor['nombre'] . ' ' . $instructor['apellido']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Asigna el usuario responsable de esta receta.</p>
                    </div>
                    
                    <div class="flex justify-end pt-4 border-t border-gray-200 mt-6">
                        <button type="submit" class="btn-primary px-6 py-3 rounded-lg font-semibold flex items-center shadow-md">
                            <i class="fas fa-save mr-2"></i> Guardar Todos los Cambios
                        </button>
                    </div>

                </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>