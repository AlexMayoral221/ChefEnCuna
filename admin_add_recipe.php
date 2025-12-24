<?php
session_start();
// Asegúrate de que este archivo defina la variable $pdo con la conexión a la DB
require 'config/bd.php'; 

error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- VALIDACIÓN DE LA CONEXIÓN (CRÍTICO) ---
if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("❌ Error Crítico: No se pudo establecer la conexión a la base de datos (PDO). Verifique el archivo 'config/bd.php'.");
}
// ------------------------------------------

$message = '';
$message_type = '';
$active_page = 'manage_recipes'; // Definida para el sidebar

// Inicializar variables para que el formulario no arroje errores en el primer acceso (GET)
$titulo = '';
$descripcion = '';
$ingredientes = '';
$procedimiento = '';
$categoria = ''; 

// La variable que ahora coincide con la columna de la DB: imagen_ruta
$imagen_ruta = null; 

// 1. CONTROL DE ACCESO (solo administrador)
if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? '') !== 'administrador') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id']; // ID del administrador actual

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. SANITIZACIÓN DE ENTRADA DE TEXTO
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $ingredientes = trim($_POST['ingredientes'] ?? '');
    $procedimiento = trim($_POST['procedimiento'] ?? '');
    $categoria = trim($_POST['categoria'] ?? ''); 
    
    $upload_dir = 'img/recetas/';
    $upload_ok = true; 
    $message = "";

    // 3. VALIDACIÓN DE CAMPOS OBLIGATORIOS
    if (empty($titulo) || empty($descripcion) || empty($ingredientes) || empty($procedimiento) || empty($categoria)) {
        $message = "⚠️ Por favor, complete todos los campos obligatorios: Título, Categoría, Descripción, Ingredientes y Procedimiento.";
        $message_type = 'warning';
        $upload_ok = false;
    } 
    
    // --- 4. Lógica de Subida de Archivo ---
    if ($upload_ok && isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        
        if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            $error_code = $_FILES['imagen']['error'];
            $error_message = match ($error_code) {
                UPLOAD_ERR_INI_SIZE => 'El archivo excede el límite de tamaño (upload_max_filesize) de la configuración de PHP.',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede el límite de tamaño especificado en el formulario HTML (MAX_FILE_SIZE).',
                UPLOAD_ERR_PARTIAL => 'El archivo fue subido solo parcialmente.',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta una carpeta temporal en el servidor.',
                UPLOAD_ERR_CANT_WRITE => 'Fallo al escribir el archivo en el disco.',
                UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo.',
                default => "Código de error: {$error_code}. Intente con un archivo más pequeño o contacte al administrador."
            };
            $message = "❌ Error [UPLOAD_ERR]: " . $error_message;
            $message_type = 'error';
            $upload_ok = false;
        }

        if ($upload_ok) {
            $file_tmp = $_FILES['imagen']['tmp_name'];
            $file_name = basename($_FILES['imagen']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
            $max_file_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($file_ext, $allowed_extensions)) {
                $message = "⚠️ Error [EXT]: Solo se permiten archivos JPG, JPEG, PNG y WEBP.";
                $message_type = 'warning';
                $upload_ok = false;
            } elseif ($_FILES['imagen']['size'] > $max_file_size) {
                $message = "⚠️ Error [SIZE]: El archivo es demasiado grande (máximo 5MB).";
                $message_type = 'warning';
                $upload_ok = false;
            } else {
                // Generar un nombre de archivo único
                $new_file_name = uniqid('recipe_', true) . '.' . $file_ext;
                $upload_path_fs = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $upload_dir) . $new_file_name;
                $imagen_ruta_db = $upload_dir . $new_file_name;

                // Asegurar que el directorio de subida existe y tiene permisos de escritura
                if (!is_dir(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $upload_dir))) {
                    if (!mkdir(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $upload_dir), 0755, true)) {
                         $message = "❌ Error [1/3 - DIRECTORIO]: No se pudo crear el directorio de subida '{$upload_dir}'. Verifique permisos.";
                         $message_type = 'error';
                         $upload_ok = false;
                    }
                }
                
                // Solo intentar mover si todo va bien hasta ahora
                if ($upload_ok) { 
                    if (move_uploaded_file($file_tmp, $upload_path_fs)) {
                        $imagen_ruta = $imagen_ruta_db; 
                    } else {
                        $message = "❌ Error [3/3 - FUNCIÓN]: Falló move_uploaded_file. Verifique si el servidor tiene permisos de escritura en '{$upload_dir}'.";
                        $message_type = 'error';
                        $upload_ok = false;
                    }
                }
            }
        }
    } else {
        // Si no se sube imagen, se usa una ruta de placeholder predeterminada (opcional, pero buena práctica)
        $imagen_ruta = 'https://placehold.co/800x600/4ecdc4/1e272e?text=Receta+Nueva';
    }

    // 5. INSERCIÓN EN LA BASE DE DATOS
    if ($upload_ok && $message_type !== 'warning' && $message_type !== 'error') { 
        try {
            $sql = "INSERT INTO recetas (
                        titulo, descripcion, ingredientes,    
                        procedimiento, categoria, imagen_ruta,    
                        usuario_id, fecha_publicacion
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $pdo->prepare($sql);
            
            $success = $stmt->execute([
                $titulo, $descripcion, $ingredientes, 
                $procedimiento, $categoria, $imagen_ruta, 
                $user_id 
            ]);

            if ($success) {
                $new_recipe_id = $pdo->lastInsertId();
                $message = "✅ Receta '{$titulo}' (ID: {$new_recipe_id}) creada exitosamente. ¡A cocinar!";
                $message_type = 'success';
                
                // Limpiar variables para que el formulario se muestre vacío
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

// 6. PROTECCIÓN XSS
$titulo = htmlspecialchars($titulo);
$descripcion = htmlspecialchars($descripcion);
$ingredientes = htmlspecialchars($ingredientes);
$procedimiento = htmlspecialchars($procedimiento);
$categoria = htmlspecialchars($categoria); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Receta | ChefEnCuna Admin</title>
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
        .input-focus:focus { 
            border-color: #ff6b6b; 
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.4);
        }
        
        /* Estilo para el input file */
        .file-input-style::file-selector-button {
            margin-right: 0.5rem;
            padding: 0.5rem 1rem;
            border: 0;
            border-radius: 0.5rem; /* Menos redondeado para ser consistente */
            background-color: #4ecdc4; /* Color secundario para diferenciar */
            color: #1e272e;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .file-input-style::file-selector-button:hover {
            background-color: #3aa6a0;
        }

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
                    Gestión de Contenido / <span class="text-accent">Añadir Receta</span>
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
                        <i class="fas fa-plus-circle mr-3 text-primary-accent"></i> Crear Nueva Receta
                    </h1>
                    <p class="text-gray-500 mt-2">Introduce todos los detalles y el procedimiento completo de la nueva receta.</p>
                </header>

                <?php if ($message): ?>
                    <!-- Mensajes de feedback -->
                    <div role="alert" class="p-4 mb-6 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border-green-500' : ($message_type === 'warning' ? 'bg-yellow-100 text-yellow-700 border-yellow-500' : 'bg-red-100 text-red-700 border-red-500'); ?> border-l-4 font-mono text-sm" style="transition: opacity 0.5s ease-out;">
                        <p class="font-bold mb-1">Mensaje del sistema:</p>
                        <p><?php echo htmlspecialchars($message); ?></p>
                        
                        <?php if ($message_type === 'error' && strpos($message, '❌ Error [') !== false): ?>
                            <p class="mt-2 font-bold text-base text-red-700">SOLUCIÓN COMÚN (Si ve errores de subida de archivos):</p>
                            <ul class="list-disc list-inside text-xs mt-1 text-red-600">
                                <li>Si ve problemas de **DIRECTORIO** o **FUNCIÓN**, debe otorgar permisos de escritura a la carpeta `img/recetas/` en su servidor (p. ej., `chmod -R 777 img/`).</li>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <form action="admin_add_recipe.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        
                        <!-- COLUMNA IZQUIERDA: Imagen y Categoría -->
                        <div class="lg:col-span-1 space-y-6">
                            
                            <!-- Imagen de la Receta -->
                            <div class="p-4 rounded-xl bg-gray-50 shadow-inner">
                                <label for="imagen" class="block text-lg font-bold text-gray-800 mb-3 flex items-center border-b pb-2">
                                    <i class="fas fa-camera mr-2 text-primary-accent"></i> Imagen Principal
                                </label>
                                <input type="file" id="imagen" name="imagen" accept="image/jpeg, image/png, image/webp" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white file-input-style">
                                <p class="text-xs text-gray-500 mt-1">Formatos permitidos: JPG, PNG, WEBP. Máx. 5MB. Si no sube una, se usará un placeholder.</p>
                            </div>

                            <!-- Categoría -->
                            <div>
                                <label for="categoria" class="block text-sm font-medium text-gray-700 mb-2">Categoría <span class="text-red-500">*</span></label>
                                <input type="text" id="categoria" name="categoria" value="<?php echo $categoria; ?>" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg input-focus" placeholder="Ej: Postres, Cena, Vegetariano">
                            </div>
                            
                            <!-- Nota: El usuario_id se toma automáticamente de la sesión del administrador ($user_id) -->
                        </div>

                        <!-- COLUMNA DERECHA: Títulos y Contenido -->
                        <div class="lg:col-span-2 space-y-6">
                            
                            <!-- Título -->
                            <div>
                                <label for="titulo" class="block text-sm font-medium text-gray-700 mb-2">Título de la Receta <span class="text-red-500">*</span></label>
                                <input type="text" id="titulo" name="titulo" value="<?php echo $titulo; ?>" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg input-focus" placeholder="Ej: Pastel de Chocolate Clásico">
                            </div>

                            <!-- Descripción -->
                            <div>
                                <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-2">Descripción Breve <span class="text-red-500">*</span></label>
                                <textarea id="descripcion" name="descripcion" rows="3" required 
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg input-focus" placeholder="Una breve descripción de lo que trata la receta..."><?php echo $descripcion; ?></textarea>
                                <p class="text-xs text-gray-500 mt-1">Esta descripción es para el listado principal.</p>
                            </div>

                            <!-- Ingredientes -->
                            <div>
                                <label for="ingredientes" class="block text-sm font-medium text-gray-700 mb-2">Lista de Ingredientes (Separados por línea) <span class="text-red-500">*</span></label>
                                <textarea id="ingredientes" name="ingredientes" rows="6" required 
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg input-focus font-mono text-sm" placeholder="Ej:&#10;500 gramos de carne de res&#10;1 paquete de pasta de arroz&#10;2 dientes de ajo"><?php echo $ingredientes; ?></textarea>
                            </div>

                            <!-- Procedimiento -->
                            <div>
                                <label for="procedimiento" class="block text-sm font-medium text-gray-700 mb-2">Procedimiento Paso a Paso <span class="text-red-500">*</span></label>
                                <textarea id="procedimiento" name="procedimiento" rows="8" required 
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg input-focus font-mono text-sm" placeholder="Ej:&#10;1. Calentar el aceite en una sartén grande.&#10;2. Agregar la carne picada y cocinar hasta que esté dorada..."><?php echo $procedimiento; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Botón de Envío -->
                    <div class="flex justify-end pt-6 border-t border-gray-200 mt-8">
                        <button type="submit" class="btn-primary px-8 py-3 rounded-xl font-bold text-lg flex items-center shadow-xl hover:shadow-2xl">
                            <i class="fas fa-save mr-3"></i> Crear Receta
                        </button>
                    </div>
                </form>

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
                    messageDiv.style.transition = 'opacity 0.5s ease-out';
                    setTimeout(() => {
                        messageDiv.style.display = 'none';
                    }, 500);
                }, 7000);
            }
        });
    </script>

</body>
</html>