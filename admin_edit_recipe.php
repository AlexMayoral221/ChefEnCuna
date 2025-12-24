<?php
session_start();
require 'config/bd.php'; 

error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$message_type = '';
$recipe_data = null;
$instructors = [];
$active_page = 'manage_recipes'; // Definida para el sidebar

// --- 1. Control de Acceso ---
if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? '') !== 'administrador') {
    header('Location: login.php');
    exit;
}

// --- 2. Validación de ID de Receta ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin_manage_recipes.php');
    exit;
}

$recipe_id = (int)$_GET['id'];
$fs_upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'recetas' . DIRECTORY_SEPARATOR; 
$db_upload_path_prefix = 'img/recetas/';

// --- 3. Carga Inicial de Datos (Instructores y Receta) ---
try {
    // Cargar Instructores
    $stmt_users = $pdo->prepare("SELECT id, nombre, apellido FROM usuarios WHERE rol IN ('maestro', 'administrador') ORDER BY nombre");
    $stmt_users->execute();
    $instructors = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

    // Cargar Datos de la Receta
    $stmt_recipe = $pdo->prepare("SELECT id, usuario_id, titulo, descripcion, ingredientes, procedimiento, categoria, imagen_ruta FROM recetas WHERE id = ?");
    $stmt_recipe->execute([$recipe_id]);
    $recipe_data = $stmt_recipe->fetch(PDO::FETCH_ASSOC);

    if (!$recipe_data) {
        $_SESSION['flash_message'] = "❌ Error: Receta con ID {$recipe_id} no encontrada.";
        $_SESSION['flash_type'] = 'error';
        header('Location: admin_manage_recipes.php');
        exit;
    }

} catch (PDOException $e) {
    $message = "❌ Error al cargar datos iniciales: " . $e->getMessage();
    $message_type = 'error';
}

// --- 4. Procesamiento del Formulario POST (Actualización) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $recipe_id && $recipe_data) {
    
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $ingredientes = trim($_POST['ingredientes'] ?? '');
    $procedimiento = trim($_POST['procedimiento'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $usuario_id = (int)($_POST['usuario_id'] ?? 0);
    
    $new_imagen_ruta = $recipe_data['imagen_ruta']; 
    $image_updated = false;
    $has_upload_error = false;
    $upload_message = ""; 
    
    // Rellenar datos para el formulario si hay errores
    $recipe_data['titulo'] = $titulo;
    $recipe_data['descripcion'] = $descripcion;
    $recipe_data['ingredientes'] = $ingredientes;
    $recipe_data['procedimiento'] = $procedimiento;
    $recipe_data['categoria'] = $categoria;
    $recipe_data['usuario_id'] = $usuario_id;


    if (empty($titulo) || empty($descripcion) || empty($ingredientes) || empty($procedimiento) || empty($categoria) || $usuario_id <= 0) {
        $message = "⚠️ Por favor, complete todos los campos obligatorios (Título, Categoría, Descripción, Ingredientes, Procedimiento, e Autor).";
        $message_type = 'warning';
        
    } else {
        
        // --- 4.1. Manejo de Subida de Imagen ---
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
            
            if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
                $upload_message = "❌ Error de PHP [Código: " . $_FILES['imagen']['error'] . "]. Revise límites de tamaño en php.ini.";
                $has_upload_error = true;
            }

            if (!$has_upload_error) {
                
                $file_info = pathinfo($_FILES['imagen']['name']);
                $extension = strtolower($file_info['extension']);
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

                if (!in_array($extension, $allowed_extensions)) {
                    $upload_message = "❌ Formato de archivo no permitido. Solo se aceptan JPG, JPEG, PNG o WEBP.";
                    $has_upload_error = true;
                } else {
                    
                    $unique_filename = uniqid('receta_', true) . '.' . $extension;
                    $target_path_db = $db_upload_path_prefix . $unique_filename; 
                    $target_path_fs = $fs_upload_dir . $unique_filename; 

                    if (!is_dir($fs_upload_dir)) {
                        if (!mkdir($fs_upload_dir, 0755, true)) { 
                            $upload_message = "❌ Error [1/3 - DIRECTORIO]: No se pudo crear el directorio. Verifique los permisos en el directorio raíz. Ruta: " . htmlspecialchars($fs_upload_dir);
                            $has_upload_error = true;
                        }
                    }
                    
                    if (!$has_upload_error && !is_writable($fs_upload_dir)) {
                        $upload_message = "❌ Error [2/3 - PERMISOS]: El directorio de subida NO tiene permisos de escritura. Ejecute 'chmod 777 img/recetas/' (para depuración). Ruta: " . htmlspecialchars($fs_upload_dir);
                        $has_upload_error = true;
                    }

                    if (!$has_upload_error) {
                        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $target_path_fs)) {
                            
                            // Eliminar imagen antigua (si existe y no es placeholder)
                            if (!empty($recipe_data['imagen_ruta']) && strpos($recipe_data['imagen_ruta'], 'placehold') === false) {
                                $old_image_fs_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $recipe_data['imagen_ruta']); 
                                
                                if (file_exists($old_image_fs_path)) {
                                    @unlink($old_image_fs_path); 
                                }
                            }

                            $new_imagen_ruta = $target_path_db; 
                            $image_updated = true;
                            $upload_message = "✅ Imagen subida y lista para guardar.";
                            
                        } else {
                            $upload_message = "❌ Error [3/3 - FUNCIÓN]: Falló move_uploaded_file. Rutas: Temporal: " . htmlspecialchars($_FILES['imagen']['tmp_name']) . " | Destino ABSOLUTO: " . htmlspecialchars($target_path_fs);
                            $has_upload_error = true;
                        }
                    }
                }
            }
        }

        // --- 4.2. Intentar Guardar si no hay Errores de Imagen ---
        if ($has_upload_error) {
            $message = $upload_message;
            $message_type = 'error';
            
        } else {
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

                if ($image_updated) {
                    $sql_update_fields[] = 'imagen_ruta = :imagen_ruta';
                    $update_params[':imagen_ruta'] = $new_imagen_ruta;
                }
                
                $sql_update = "UPDATE recetas SET " . implode(', ', $sql_update_fields) . " WHERE id = :id";
                
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute($update_params);

                if ($image_updated) {
                    $message = "✅ La receta '{$titulo}' y la imagen han sido actualizadas exitosamente.";
                } elseif ($stmt_update->rowCount() > 0) {
                    $message = "✅ La receta '{$titulo}' ha sido actualizada exitosamente.";
                } else {
                    $message = "ℹ️ No se detectaron cambios en la receta.";
                    $message_type = 'info';
                }
                $message_type = $message_type ?: 'success';

                // Recargar datos después de la actualización exitosa
                $stmt_recipe->execute([$recipe_id]);
                $recipe_data = $stmt_recipe->fetch(PDO::FETCH_ASSOC);

            } catch (PDOException $e) {
                $message = "❌ Error al actualizar la receta en la base de datos: " . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Receta | ChefEnCuna Admin</title>
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"> 
    <!-- Tailwind CSS CDN -->
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
                    },
                     boxShadow: {
                        'sidebar': '5px 0 15px rgba(0, 0, 0, 0.05)',
                        'input-focus': '0 0 0 3px rgba(255, 107, 107, 0.4)',
                    }
                }
            }
        }
    </script>
    <style>
        /* Estilos base */
        body { font-family: 'Inter', sans-serif; background-color: #f4f7f6; } 
        
        /* Estilos de la Barra Lateral (Sidebar) */
        .nav-link { transition: background-color 0.2s, color 0.2s; }
        .nav-link.active { background-color: #ff6b6b; color: white; border-radius: 0.5rem; }
        .nav-link:not(.active):hover { background-color: rgba(255, 107, 107, 0.1); color: #ff6b6b; border-radius: 0.5rem; }
        
        /* Estilos de botones y formularios */
        .btn-primary { background-color: #ff6b6b; color: white; transition: background-color 0.2s; }
        .btn-primary:hover { background-color: #d84a4a; }
        .btn-secondary { background-color: #4ecdc4; color: #1e272e; transition: background-color 0.2s; }
        .btn-secondary:hover { background-color: #3aa6a0; }
        .input-focus:focus { 
            border-color: #ff6b6b; 
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.4);
        }
        .whitespace-pre-wrap { white-space: pre-wrap; } 
        
        /* Media query para hacer el sidebar fijo en desktop */
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
                    Gestión de Contenido / <span class="text-accent">Editar Receta</span>
                </h2>
                <a href="admin_manage_recipes.php" class="px-4 py-2 rounded-full bg-gray-200 text-primary-dark font-semibold hover:bg-gray-300 transition duration-150 text-sm inline-flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Volver al Listado
                </a>
            </div>
        </header>
        
        <main class="max-w-7xl mx-auto p-4 lg:p-10">

            <div class="bg-white rounded-xl shadow-2xl p-6 md:p-10">
                
                <header class="mb-8 border-b pb-4">
                    <h1 class="text-3xl font-extrabold text-primary-dark flex items-center">
                        <i class="fas fa-edit mr-3 text-primary-accent"></i> Edición de Receta
                    </h1>
                    <?php if ($recipe_data): ?>
                        <p class="text-gray-500 mt-2">ID #<?php echo htmlspecialchars($recipe_data['id']); ?>: <b><?php echo htmlspecialchars($recipe_data['titulo']); ?></b></p>
                    <?php endif; ?>
                </header>

                <?php if ($message): ?>
                    <!-- Mensajes de feedback -->
                    <div role="alert" class="p-4 mb-6 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border-green-500' : ($message_type === 'info' ? 'bg-blue-100 text-blue-700 border-blue-500' : 'bg-red-100 text-red-700 border-red-500'); ?> border-l-4 font-mono text-sm" style="transition: opacity 0.5s ease-out;">
                        <p class="font-bold mb-1">Mensaje del sistema:</p>
                        <p class="whitespace-pre-wrap"><?php echo htmlspecialchars($message); ?></p>
                        
                        <?php if ($message_type === 'error' && (strpos($message, '❌ Error [') !== false || strpos($message, 'Directorios') !== false)): ?>
                            <p class="mt-2 font-bold text-base text-red-700">SOLUCIÓN COMÚN (Si ve errores de subida de archivos):</p>
                            <ul class="list-disc list-inside text-xs mt-1 text-red-600">
                                <li>Si ve problemas de **DIRECTORIO** o **PERMISOS**, debe otorgar permisos de escritura a la carpeta `img/recetas/` en su servidor (p. ej., `chmod -R 777 img/`).</li>
                                <li>La ruta ABSOLUTA de subida es: <code><?php echo htmlspecialchars($fs_upload_dir ?? 'No definido'); ?></code></li>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($recipe_data): ?>
                    <form action="admin_edit_recipe.php?id=<?php echo $recipe_id; ?>" method="POST" enctype="multipart/form-data">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            
                            <!-- COLUMNA IZQUIERDA: Imagen y Autor -->
                            <div class="lg:col-span-1 space-y-6">
                                <div class="mb-6 border p-4 rounded-xl bg-gray-50 shadow-inner">
                                    <label class="block text-lg font-bold text-gray-800 mb-3 flex items-center border-b pb-2">
                                        <i class="fas fa-camera mr-2 text-primary-accent"></i> Imagen Principal
                                    </label>

                                    <div class="mb-4 text-center">
                                        <p class="text-sm text-gray-600 mb-2">Imagen actual:</p>
                                        <?php 
                                            $image_path = !empty($recipe_data['imagen_ruta']) ? htmlspecialchars($recipe_data['imagen_ruta']) : 'https://placehold.co/400x300/4ecdc4/1e272e?text=Sin+Imagen';
                                        ?>
                                        <img src="<?php echo $image_path; ?>" onerror="this.onerror=null; this.src='https://placehold.co/400x300/ff6b6b/ffffff?text=Error+al+cargar+imagen';" alt="Imagen actual de la receta" class="w-full h-auto object-cover rounded-lg shadow-md border-2 border-accent/50 mx-auto">
                                    </div>

                                    <label for="imagen" class="block text-sm font-medium text-gray-700 mb-2 mt-4">Cambiar Imagen (JPG, PNG, WEBP)</label>
                                    <input type="file" id="imagen" name="imagen" accept="image/jpeg, image/png, image/webp"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-accent file:text-white hover:file:bg-red-500">
                                    <p class="text-xs text-gray-500 mt-1">El archivo anterior será reemplazado.</p>
                                </div>
                                
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
                                
                                <div class="mb-6">
                                    <label for="categoria" class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                                    <input type="text" id="categoria" name="categoria" value="<?php echo htmlspecialchars($recipe_data['categoria'] ?? ''); ?>" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg input-focus" placeholder="Ej: Postres, Cena, Vegetariano" required>
                                    <p class="text-xs text-gray-500 mt-1">Define la categoría principal de la receta.</p>
                                </div>
                            </div>

                            <!-- COLUMNA DERECHA: Texto y Contenido -->
                            <div class="lg:col-span-2 space-y-6">
                                <div class="mb-6">
                                    <label for="titulo" class="block text-sm font-medium text-gray-700 mb-2">Título de la Receta</label>
                                    <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($recipe_data['titulo']); ?>" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg input-focus" required>
                                </div>

                                <div class="mb-6">
                                    <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-2">Descripción Breve</label>
                                    <textarea id="descripcion" name="descripcion" rows="4" 
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg input-focus" 
                                              required placeholder="Breve resumen de la receta para el listado..."><?php echo htmlspecialchars($recipe_data['descripcion']); ?></textarea>
                                    <p class="text-xs text-gray-500 mt-1">Aparece en el listado y la vista principal de la receta.</p>
                                </div>

                                <div class="mb-6">
                                    <label for="ingredientes" class="block text-sm font-medium text-gray-700 mb-2">Lista de Ingredientes (Separados por línea)</label>
                                    <textarea id="ingredientes" name="ingredientes" rows="8" 
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg input-focus font-mono text-sm" 
                                              required placeholder="Ej:&#10;500 gramos de carne de res&#10;1 paquete de pasta de arroz&#10;2 dientes de ajo"><?php echo htmlspecialchars($recipe_data['ingredientes'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-6">
                                    <label for="procedimiento" class="block text-sm font-medium text-gray-700 mb-2">Procedimiento Paso a Paso</label>
                                    <textarea id="procedimiento" name="procedimiento" rows="10" 
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg input-focus font-mono text-sm" 
                                              required placeholder="Ej:&#10;1. Calentar el aceite en una sartén grande.&#10;2. Agregar la carne picada y cocinar hasta que esté dorada..."><?php echo htmlspecialchars($recipe_data['procedimiento'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end pt-6 border-t border-gray-200 mt-8">
                            <button type="submit" class="btn-primary px-8 py-3 rounded-xl font-bold text-lg flex items-center shadow-xl hover:shadow-2xl">
                                <i class="fas fa-save mr-3"></i> Actualizar Receta
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </main>
    </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const messageDiv = document.querySelector('[role="alert"]');
            
            if (messageDiv) {
                setTimeout(() => {
                    messageDiv.style.opacity = '0';
                    setTimeout(() => {
                        messageDiv.style.display = 'none';
                    }, 500);
                }, 7000); 
            }
        });
    </script>
</body>
</html>