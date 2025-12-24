<?php
session_start();
require 'config/bd.php'; 

$UPLOAD_DIR = 'img/cursos/'; 
$ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
$MAX_SIZE = 5 * 1024 * 1024;

$message = '';
$message_type = '';
$course_data = [];
$instructors = [];
$active_page = 'manage_courses'; // Definida para el sidebar

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    header('Location: login.php');
    exit;
}

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0);

try {
    // Obtener lista de instructores
    $stmt = $pdo->prepare("SELECT id, nombre, apellido FROM usuarios WHERE rol = 'maestro' ORDER BY nombre");
    $stmt->execute();
    $instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "❌ Error al cargar instructores: " . $e->getMessage();
    $message_type = 'error';
}

// 1. Cargar Datos del Curso para Edición
if ($course_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM cursos WHERE id = ?");
        $stmt->execute([$course_id]);
        $course_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$course_data) {
            $message = "❌ Curso no encontrado. Redireccionando...";
            $message_type = 'error';
            // Mensaje de redirección para evitar un error brusco
            echo "<!DOCTYPE html><html><head><script src='https://cdn.tailwindcss.com'></script><title>Error</title></head><body class='bg-light flex items-center justify-center h-screen'><div class='p-6 rounded-xl bg-red-100 text-red-700 border-red-400 border-l-4 shadow-lg'><p class='font-bold'>$message</p><p class='text-sm mt-2'>Será redirigido automáticamente.</p></div></body></html>";
            header('refresh:3; url=admin_manage_courses.php');
            exit;
        }

    } catch (PDOException $e) {
        $message = "❌ Error al cargar los datos: " . $e->getMessage();
        $message_type = 'error';
    }
} else {
    $message = "❌ ID de curso no proporcionado. Redireccionando...";
    $message_type = 'error';
    // Mensaje de redirección para evitar un error brusco
    echo "<!DOCTYPE html><html><head><script src='https://cdn.tailwindcss.com'></script><title>Error</title></head><body class='bg-light flex items-center justify-center h-screen'><div class='p-6 rounded-xl bg-red-100 text-red-700 border-red-400 border-l-4 shadow-lg'><p class='font-bold'>$message</p><p class='text-sm mt-2'>Será redirigido automáticamente.</p></div></body></html>";
    header('refresh:3; url=admin_manage_courses.php');
    exit;
}

$data = $course_data; 
$old_imagen_url = $data['imagen_url'] ?? NULL;
$new_imagen_url = $old_imagen_url;

// 2. Lógica de Actualización POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $nivel = trim($_POST['nivel']);
    $requisitos = trim($_POST['requisitos']);
    // Usar NULL para instructor_id si el campo viene vacío
    $instructor_id = $_POST['instructor_id'] === '' ? NULL : (int)$_POST['instructor_id'];
    $duracion = trim($_POST['duracion']);
    $objetivos = trim($_POST['objetivos']);
    $remove_imagen = isset($_POST['remove_imagen']) ? (int)$_POST['remove_imagen'] : 0;

    if (empty($titulo) || empty($descripcion) || empty($nivel)) {
        $message = "❌ Por favor, complete los campos Título, Descripción y Nivel.";
        $message_type = 'error';
    } else {
        $image_was_changed = false;

        // Manejo de la subida de nueva imagen
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['imagen'];

            if (!in_array($file['type'], $ALLOWED_TYPES)) {
                $message = "❌ Tipo de archivo no permitido. Solo se aceptan JPG, PNG y WEBP.";
                $message_type = 'error';
            } elseif ($file['size'] > $MAX_SIZE) {
                $message = "❌ El archivo es demasiado grande. Máximo 5MB.";
                $message_type = 'error';
            } else {
                if (!is_dir($UPLOAD_DIR)) {
                    mkdir($UPLOAD_DIR, 0777, true);
                }
                
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $unique_name = time() . '_' . uniqid() . '.' . $extension;
                $target_path = $UPLOAD_DIR . $unique_name;
                
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $new_imagen_url = $target_path; 
                    $image_was_changed = true;
                } else {
                    $message = "❌ Error al subir el nuevo archivo de imagen. Verifique permisos.";
                    $message_type = 'error';
                }
            }
        } 
        
        // Manejo de la eliminación de imagen existente
        elseif ($remove_imagen === 1) {
            if ($old_imagen_url) {
                $new_imagen_url = NULL;
                $image_was_changed = true;
            }
        }

        // Si no hay errores, proceder con la actualización de la BD
        if ($message_type !== 'error') {
            try {
                $sql = "UPDATE cursos SET 
                            titulo = ?, 
                            descripcion = ?, 
                            nivel = ?, 
                            requisitos = ?, 
                            instructor_id = ?, 
                            duracion = ?, 
                            objetivos = ?,
                            imagen_url = ? 
                        WHERE id = ?";
                
                $stmt = $pdo->prepare($sql);
                $success = $stmt->execute([
                    $titulo, 
                    $descripcion, 
                    $nivel, 
                    $requisitos, 
                    $instructor_id, 
                    $duracion, 
                    $objetivos, 
                    $new_imagen_url, 
                    $course_id
                ]);

                if ($success) {
                    // Borrar la imagen antigua si se subió una nueva o se marcó para eliminar
                    if ($image_was_changed && $old_imagen_url && $old_imagen_url !== $new_imagen_url && file_exists($old_imagen_url)) {
                        unlink($old_imagen_url); 
                    }

                    // Actualizar los datos en la variable $data para reflejar los cambios en el formulario
                    $data['titulo'] = $titulo;
                    $data['descripcion'] = $descripcion;
                    $data['nivel'] = $nivel;
                    $data['requisitos'] = $requisitos;
                    $data['instructor_id'] = $instructor_id;
                    $data['duracion'] = $duracion;
                    $data['objetivos'] = $objetivos;
                    $data['imagen_url'] = $new_imagen_url; 
                    
                    $message = "✅ Curso '{$titulo}' actualizado exitosamente.";
                    $message_type = 'success';
                } else {
                    // Si la actualización falla, revertir la subida de la nueva imagen
                    if ($image_was_changed && $new_imagen_url !== NULL && $new_imagen_url !== $old_imagen_url && file_exists($new_imagen_url)) {
                        unlink($new_imagen_url); 
                    }
                    $message = "⚠️ No se realizaron cambios en el curso o la actualización falló.";
                    $message_type = 'warning';
                }
            } catch (PDOException $e) {
                // Si hay un error de DB, revertir la subida de la nueva imagen
                if ($image_was_changed && $new_imagen_url !== NULL && $new_imagen_url !== $old_imagen_url && file_exists($new_imagen_url)) {
                    unlink($new_imagen_url); 
                }
                $message = "❌ Error al actualizar el curso: " . $e->getMessage();
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
    <title>Editar Curso | ChefEnCuna Admin</title>
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

                        'dark': '#2d3436',      
                        'light': '#f7f1e3',     
                        'white': '#ffffff',
                    },
                     boxShadow: {
                        'sidebar': '5px 0 15px rgba(0, 0, 0, 0.05)',
                    }
                }
            }
        }
    </script>
    <style>
        /* Estilos base */
        body { font-family: 'Inter', sans-serif; background-color: #f4f7f6; } /* primary-light */
        
        /* Estilos de la Barra Lateral (Sidebar) */
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
        
        /* Estilos de inputs */
        .input-style { 
            border: 1px solid #ccc; 
            padding: 10px; 
            border-radius: 8px; 
            width: 100%; 
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.06); 
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input-style:focus {
            border-color: #ff6b6b;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.2);
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
                    Editando Curso: <span class="text-primary-accent"><?php echo htmlspecialchars($data['titulo'] ?? ''); ?></span>
                </h2>
                <a href="admin_manage_courses.php" class="px-4 py-2 rounded-full bg-accent text-primary-dark font-semibold hover:bg-teal-400 transition duration-150 text-sm inline-flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Volver a Cursos
                </a>
            </div>
        </header>
        
        <main class="max-w-4xl mx-auto p-4 lg:p-10">
            <div class="bg-white rounded-xl shadow-2xl p-6 md:p-10">
                
                <header class="mb-8 border-b pb-4">
                    <h1 class="text-3xl font-extrabold text-primary-dark flex items-center">
                        <i class="fas fa-edit mr-3 text-primary-accent"></i> Modificar Contenido del Curso
                    </h1>
                </header>

                <?php if ($message): ?>
                    <div class="p-4 mb-6 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border-green-500' : ($message_type === 'warning' ? 'bg-yellow-100 text-yellow-700 border-yellow-500' : 'bg-red-100 text-red-700 border-red-500'); ?> border-l-4" role="alert">
                        <p class="font-bold"><?php echo $message; ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="admin_edit_course.php" class="space-y-6" enctype="multipart/form-data">
                    <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>">
                    
                    <!-- Campo Título -->
                    <div>
                        <label for="titulo" class="block text-sm font-medium text-gray-700 mb-1">Título del Curso <span class="text-red-500">*</span></label>
                        <input type="text" name="titulo" id="titulo" class="input-style" value="<?php echo htmlspecialchars($data['titulo'] ?? ''); ?>" required>
                    </div>
                    
                    <!-- Campo Descripción -->
                    <div>
                        <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción Detallada <span class="text-red-500">*</span></label>
                        <textarea name="descripcion" id="descripcion" rows="4" class="input-style" required><?php echo htmlspecialchars($data['descripcion'] ?? ''); ?></textarea>
                    </div>

                    <!-- Gestión de Imagen -->
                    <div class="border p-4 rounded-xl bg-gray-50 border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                            <i class="fas fa-image mr-2 text-accent"></i> Imagen del Curso
                        </h3>

                        <?php if (!empty($data['imagen_url'])): ?>
                            <div class="mb-4 p-3 bg-white rounded-lg border">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Vista Previa Actual:</label>
                                <img src="<?php echo htmlspecialchars($data['imagen_url']); ?>" alt="Imagen actual del curso" class="max-w-full h-40 object-cover rounded-lg shadow-inner">
                                <div class="mt-3 flex items-center">
                                    <input type="checkbox" name="remove_imagen" id="remove_imagen" value="1" class="h-4 w-4 text-primary-accent border-gray-300 rounded focus:ring-primary-accent">
                                    <label for="remove_imagen" class="ml-2 block text-sm text-red-600 font-medium">Eliminar imagen actual</label>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-sm text-gray-500 mb-4">No hay imagen asignada a este curso.</p>
                        <?php endif; ?>

                        <div>
                            <label for="imagen" class="block text-sm font-medium text-gray-700 mb-1">Subir Nueva Imagen</label>
                                <input type="file" name="imagen" id="imagen" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-accent/10 file:text-primary-accent hover:file:bg-primary-accent/20 cursor-pointer p-0">
                            <p class="text-xs text-gray-400 mt-1">Formatos permitidos: JPG, PNG, WEBP. Máximo 5MB.</p>
                        </div>
                    </div>

                    <!-- Nivel y Duración -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="nivel" class="block text-sm font-medium text-gray-700 mb-1">Nivel del Curso <span class="text-red-500">*</span></label>
                            <select name="nivel" id="nivel" class="input-style" required>
                                <option value="">-- Seleccione Nivel --</option>
                                <?php 
                                    $niveles = ['Principiante', 'Intermedio', 'Avanzado'];
                                    foreach ($niveles as $nivel_op) {
                                        $selected = ($data['nivel'] === $nivel_op) ? 'selected' : '';
                                        echo "<option value=\"$nivel_op\" $selected>$nivel_op</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label for="duracion" class="block text-sm font-medium text-gray-700 mb-1">Duración (Ej: 6 semanas, 10 horas)</label>
                            <input type="text" name="duracion" id="duracion" class="input-style" value="<?php echo htmlspecialchars($data['duracion'] ?? ''); ?>" placeholder="Ej: 8 horas">
                        </div>
                    </div>
                    
                    <!-- Requisitos y Objetivos -->
                    <div>
                        <label for="requisitos" class="block text-sm font-medium text-gray-700 mb-1">Requisitos Previos (Opcional)</label>
                        <textarea name="requisitos" id="requisitos" rows="2" class="input-style" placeholder="Ej: No se requiere experiencia previa en cocina."><?php echo htmlspecialchars($data['requisitos'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label for="objetivos" class="block text-sm font-medium text-gray-700 mb-1">Objetivos de Aprendizaje (Opcional)</label>
                        <textarea name="objetivos" id="objetivos" rows="3" class="input-style" placeholder="Ej: Aprender a preparar 10 recetas esenciales."><?php echo htmlspecialchars($data['objetivos'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- Instructor -->
                    <div>
                        <label for="instructor_id" class="block text-sm font-medium text-gray-700 mb-1">Instructor Asignado</label>
                        <select name="instructor_id" id="instructor_id" class="input-style">
                            <option value="" <?php echo ($data['instructor_id'] === NULL || $data['instructor_id'] === '') ? 'selected' : ''; ?>>-- Sin Asignar --</option>
                            <?php foreach ($instructors as $instructor): ?>
                                <?php 
                                    $isSelected = ((int)($data['instructor_id'] ?? 0) === (int)$instructor['id']);
                                    echo "<option value=\"{$instructor['id']}\" " . ($isSelected ? 'selected' : '') . ">" . htmlspecialchars($instructor['nombre'] . ' ' . $instructor['apellido']) . "</option>"; 
                                ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Botón de Guardar -->
                    <div class="flex justify-end pt-4 border-t mt-6">
                        <button type="submit" class="flex items-center px-6 py-3 rounded-xl font-bold shadow-lg bg-primary-accent text-white hover:bg-red-700 transition duration-300">
                            <i class="fas fa-save mr-2"></i> Guardar Cambios
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
            if (messageDiv && messageDiv.classList.contains('bg-green-100')) {
                setTimeout(() => {
                    messageDiv.style.opacity = 0;
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