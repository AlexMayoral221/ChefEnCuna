<?php
session_start();
require 'config/bd.php'; 

$UPLOAD_DIR = 'img/cursos/'; 
$ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
$MAX_SIZE = 5 * 1024 * 1024; 

$message = '';
$message_type = '';
$instructors = [];
$imagen_url = NULL; 
$active_page = 'manage_courses';

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
                    $imagen_url = $target_path; 
                } else {
                    $message = "❌ Error al subir el archivo de imagen. Verifique permisos del directorio: " . $UPLOAD_DIR;
                    $message_type = 'error';
                }
            }
        }

        if ($message_type !== 'error') {
            try {
                $sql = "INSERT INTO cursos (titulo, descripcion, nivel, requisitos, instructor_id, duracion, objetivos, imagen_url) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $pdo->prepare($sql);
                $success = $stmt->execute([
                    $titulo, 
                    $descripcion, 
                    $nivel, 
                    $requisitos, 
                    $instructor_id, 
                    $duracion, 
                    $objetivos,
                    $imagen_url 
                ]);

                if ($success) {
                    $last_id = $pdo->lastInsertId();
                    
                    $_SESSION['flash_message'] = "✅ Curso '{$titulo}' creado exitosamente (ID: {$last_id}).";
                    $_SESSION['flash_type'] = 'success';
                    
                    header('Location: admin_manage_courses.php');
                    exit; 
                } else {
                    if ($imagen_url && file_exists($imagen_url)) {
                        unlink($imagen_url);
                    }
                    $message = "❌ Error desconocido al crear el curso.";
                    $message_type = 'error';
                }
            } catch (PDOException $e) {
                if ($imagen_url && file_exists($imagen_url)) {
                     unlink($imagen_url);
                }
                $message = "❌ Error al insertar el nuevo curso: " . $e->getMessage();
                $message_type = 'error';
            }
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
    <title>hefEnCuna</title>
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
    
    <div class="flex-1 overflow-y-auto ml-64">
        <header class="bg-white shadow-md p-4 sticky top-0 z-10">
             <div class="flex justify-between items-center max-w-7xl mx-auto">
                <h2 class="text-xl font-bold text-text-base">
                    Gestión de Contenido / <span class="text-accent">Agregar Nuevo Curso</span>
                </h2>
                <a href="admin_manage_courses.php" class="px-4 py-2 rounded-full bg-primary-accent text-white font-semibold hover:bg-red-700 transition duration-150 text-sm inline-flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Volver a Cursos
                </a>
            </div>
        </header>
        
        <main class="max-w-4xl mx-auto p-4 lg:p-10">
            <div class="bg-white rounded-xl shadow-2xl p-6 md:p-10">
                
                <header class="mb-8 border-b pb-4">
                    <h1 class="text-3xl font-extrabold text-primary-dark flex items-center">
                        <i class="fas fa-plus-circle mr-3 text-accent"></i> Agregar Nuevo Curso
                    </h1>
                    <p class="text-gray-500 mt-2">Ingrese la información completa para crear un nuevo curso disponible para los usuarios.</p>
                </header>

                <?php if ($message): ?>
                    <div class="p-4 mb-6 rounded-lg bg-red-100 text-red-700 border-red-500 border-l-4" role="alert">
                        <p class="font-bold"><?php echo $message; ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="admin_add_course.php" class="space-y-6" enctype="multipart/form-data">
                    <div>
                        <label for="titulo" class="block text-sm font-medium text-gray-700 mb-1">Título del Curso <span class="text-red-500">*</span></label>
                        <input type="text" name="titulo" id="titulo" class="input-style" value="<?php echo htmlspecialchars($default_titulo); ?>" required placeholder="Ej: Introducción a la Cocina para Bebés">
                    </div>
                    <div>
                        <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción Detallada <span class="text-red-500">*</span></label>
                        <textarea name="descripcion" id="descripcion" rows="4" class="input-style" required placeholder="Una descripción completa del contenido y beneficios del curso."><?php echo htmlspecialchars($default_descripcion); ?></textarea>
                    </div>
                    <div class="p-4 rounded-xl bg-gray-50 border-gray-200 border">
                        <label for="imagen" class="block text-sm font-medium text-gray-700 mb-1">Imagen del Curso (Opcional, Max 5MB)</label>
                        <input type="file" name="imagen" id="imagen" 
                               accept="image/jpeg,image/png,image/webp" 
                               class="block w-full text-sm text-gray-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-full file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-accent/10 file:text-accent
                                    hover:file:bg-accent/20 cursor-pointer p-0"
                        >
                        <p class="text-xs text-gray-400 mt-1">Formatos permitidos: JPG, PNG, WEBP.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="nivel" class="block text-sm font-medium text-gray-700 mb-1">Nivel del Curso <span class="text-red-500">*</span></label>
                            <select name="nivel" id="nivel" class="input-style" required>
                                <option value="">-- Seleccione Nivel --</option>
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
                            <input type="text" name="duracion" id="duracion" class="input-style" value="<?php echo htmlspecialchars($default_duracion); ?>" placeholder="Ej: 8 horas">
                        </div>
                    </div>
                    <div>
                        <label for="requisitos" class="block text-sm font-medium text-gray-700 mb-1">Requisitos Previos (Opcional)</label>
                        <textarea name="requisitos" id="requisitos" rows="2" class="input-style" placeholder="Ej: No se requiere experiencia previa."><?php echo htmlspecialchars($default_requisitos); ?></textarea>
                    </div>
                    <div>
                        <label for="objetivos" class="block text-sm font-medium text-gray-700 mb-1">Objetivos de Aprendizaje (Opcional)</label>
                        <textarea name="objetivos" id="objetivos" rows="3" class="input-style" placeholder="Ej: Al finalizar, el estudiante podrá elaborar 10 recetas esenciales."><?php echo htmlspecialchars($default_objetivos); ?></textarea>
                    </div>
                    <div>
                        <label for="instructor_id" class="block text-sm font-medium text-gray-700 mb-1">Instructor Asignado</label>
                        <select name="instructor_id" id="instructor_id" class="input-style">
                            <option value="" <?php echo ($default_instructor_id === '') ? 'selected' : ''; ?>>-- Sin Asignar --</option>
                            <?php foreach ($instructors as $instructor): ?>
                                <?php 
                                    $selected = ((int)$default_instructor_id === (int)$instructor['id']) ? 'selected' : '';
                                    $nombre_completo = htmlspecialchars($instructor['nombre'] . ' ' . $instructor['apellido']);
                                    echo "<option value=\"{$instructor['id']}\" $selected>$nombre_completo</option>";
                                ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex justify-end pt-4 border-t mt-6">
                        <button type="submit" class="flex items-center px-6 py-3 rounded-xl font-bold shadow-lg bg-accent text-primary-dark hover:bg-teal-500 transition duration-300">
                            <i class="fas fa-plus mr-2"></i> Crear Curso
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