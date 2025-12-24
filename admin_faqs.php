<?php
session_start();

// Asegúrate de que 'config/bd.php' establece la variable $pdo correctamente.
require 'config/bd.php'; 

// Definir la página activa para el sidebar
$active_page = 'manage_faqs'; 

// --- VALIDACIÓN DE LA CONEXIÓN (CRÍTICO) ---
if (!isset($pdo) || !($pdo instanceof PDO)) {
    // Es vital que el script termine si no hay conexión a la DB.
    die("❌ Error Crítico: No se pudo establecer la conexión a la base de datos (PDO). Verifique el archivo 'config/bd.php'.");
}
// ------------------------------------------

// 1. Verificación de Autenticación y Rol
if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? '') !== 'administrador') {
    header('Location: login.php');
    exit;
}

$nombre_admin = htmlspecialchars($_SESSION['user_nombre'] ?? 'Administrador');
$faqs = [];
$edit_faq = null;
$error_message = null;
$success_message = null;

/**
 * Función para sanitizar el output (prevención XSS).
 * PDO ya maneja la sanitización para la base de datos.
 */
function sanitize_output($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

try {
    // 2. Manejo de mensajes de estado (después de redirección PRG)
    if (isset($_GET['status'])) {
        $status_message = sanitize_output($_GET['status']);
        // Detectar si es un mensaje de éxito o error
        if (stripos($status_message, '✅') !== false || stripos($status_message, 'exitosa') !== false) {
             $success_message = $status_message;
        } else {
             $error_message = $status_message;
        }
    }
    
    // 3. Lógica para ELIMINAR
    if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
        $delete_id = (int)$_GET['delete_id'];
        $stmt = $pdo->prepare("DELETE FROM faqs WHERE id = ?");
        
        if ($stmt->execute([$delete_id])) {
            $success_message = "✅ FAQ eliminada exitosamente.";
        } else {
            $error_message = "❌ Error al eliminar la FAQ.";
        }
        // Redirigir PRG después de la eliminación
        header('Location: admin_faqs.php?status=' . urlencode($success_message ?? $error_message));
        exit;
    }
    
    // 4. Lógica para CARGAR FAQ para EDICIÓN
    if (isset($_GET['edit_id']) && is_numeric($_GET['edit_id'])) {
        $edit_id = (int)$_GET['edit_id'];
        $stmt = $pdo->prepare("SELECT id, pregunta, respuesta, orden FROM faqs WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_faq = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$edit_faq) {
            $error_message = "❌ FAQ no encontrada.";
            $edit_faq = null;
        }
    }

    // 5. Lógica para PROCESAR FORMULARIO (INSERTAR/ACTUALIZAR)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pregunta = trim($_POST['pregunta'] ?? '');
        $respuesta = trim($_POST['respuesta'] ?? '');
        // Usar null coalesce para manejar valores vacíos o no numéricos de forma segura
        $orden = filter_var($_POST['orden'] ?? 99, FILTER_VALIDATE_INT, ['options' => ['default' => 99]]);
        $faq_id = filter_var($_POST['faq_id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
        
        if (empty($pregunta) || empty($respuesta)) {
            $error_message = "❌ La pregunta y la respuesta no pueden estar vacías.";
        } else {
            if ($faq_id > 0) {
                // Actualizar
                $sql = "UPDATE faqs SET pregunta = ?, respuesta = ?, orden = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$pregunta, $respuesta, $orden, $faq_id])) {
                    $success_message = "✅ FAQ actualizada exitosamente.";
                } else {
                    $error_message = "❌ Error al actualizar la FAQ.";
                }
            } else {
                // Insertar
                $sql = "INSERT INTO faqs (pregunta, respuesta, orden, fecha_creacion) VALUES (?, ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$pregunta, $respuesta, $orden])) {
                    $success_message = "✅ FAQ agregada exitosamente.";
                } else {
                    $error_message = "❌ Error al agregar la FAQ.";
                }
            }
            
            // Redirigir PRG (Post/Redirect/Get) para limpiar el POST y mostrar el mensaje
            $redirect_status = urlencode($success_message ?? $error_message);
            header('Location: admin_faqs.php?status=' . $redirect_status);
            exit;
        }
    }

} catch (PDOException $e) {
    // Si ocurre un error en cualquiera de las operaciones anteriores (POST/DELETE)
    $error_message = ($error_message ?? "") . " ❌ Error de Base de Datos: " . $e->getMessage();
}

// 6. CARGAR LISTA DE FAQS (Para mostrar en la tabla)
try {
    $stmt = $pdo->query("SELECT id, pregunta, SUBSTRING(respuesta, 1, 150) as respuesta_corta, orden, DATE_FORMAT(fecha_creacion, '%d/%m/%Y') as fecha_formato FROM faqs ORDER BY orden ASC, fecha_creacion DESC");
    $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = ($error_message ?? "") . " ❌ Error al cargar la lista de FAQs: " . $e->getMessage();
}

// *** IMPORTANTE: Se ha eliminado el bloque de código que realizaba una segunda
// *** redirección incondicional si $_GET['status'] estaba presente. El patrón
// *** PRG (Redirección después de POST/DELETE) ya maneja la limpieza del POST.
// *** Dejar el GET['status'] activo después del redirect permite mostrar el mensaje.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de FAQs | ChefEnCuna Admin</title>
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
        /* Uso dinámico de la clase active por PHP */
        .nav-link.active { background-color: #ff6b6b; color: white; border-radius: 0.5rem; }
        .nav-link:not(.active):hover { background-color: rgba(255, 107, 107, 0.1); color: #ff6b6b; border-radius: 0.5rem; }
        
        /* Estilos de botones y formularios */
        .btn-primary { background-color: #ff6b6b; color: white; transition: background-color 0.2s; }
        .btn-primary:hover { background-color: #d84a4a; }
        .btn-accent { background-color: #4ecdc4; color: #1e272e; transition: background-color 0.2s; }
        .btn-accent:hover { background-color: #3aa6a0; }
        
        .input-field, .textarea-field { 
            border: 1px solid #ccc; 
            padding: 0.75rem; 
            border-radius: 0.5rem; 
            width: 100%; 
            box-sizing: border-box; 
            transition: border-color 0.2s, box-shadow 0.2s; 
        }
        .input-field:focus, .textarea-field:focus { 
            border-color: #4ecdc4; /* Color acento */
            outline: none; 
            box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.3); 
        }

        /* Media query para hacer el sidebar fijo en desktop */
        @media (min-width: 1024px) {
            .ml-64 { margin-left: 16rem; }
        }
        
        /* Estilo para ocultar mensaje */
        /* Nota: La transición se maneja en JS */
    </style>
</head>
<body class="bg-primary-light text-text-base">

<div class="flex h-screen overflow-hidden">
    <aside class="w-64 bg-white shadow-sidebar flex flex-col fixed h-full z-20">
        <div class="p-6 border-b border-gray-100 flex-shrink-0">
            <h1 class="text-2xl font-extrabold text-primary-dark tracking-wide">ChefEnCuna<span class="text-primary-accent">.</span></h1>
        </div>

        <nav class="flex-grow p-4 space-y-2 overflow-y-auto">
            
            <?php 
            // Función auxiliar para aplicar la clase 'active' dinámicamente
            $is_active = fn($page) => $active_page === $page ? ' active' : ' text-gray-600'; 
            ?>
            
            <a href="admin_dashboard.php" class="nav-link flex items-center p-3 font-semibold transition duration-150<?php echo $is_active('dashboard'); ?>">
                <i class="fas fa-chart-line w-5 mr-3"></i>
                Dashboard
            </a>
            
            <p class="text-xs text-gray-400 uppercase font-bold pt-4 pb-1 px-3">Gestión de Contenido</p>
            
            <a href="admin_manage_courses.php" class="nav-link flex items-center p-3 font-semibold transition duration-150<?php echo $is_active('manage_courses'); ?>">
                <i class="fas fa-book w-5 mr-3 text-accent"></i>
                Cursos
            </a>

            <a href="admin_manage_recipes.php" class="nav-link flex items-center p-3 font-semibold transition duration-150<?php echo $is_active('manage_recipes'); ?>">
                <i class="fas fa-utensils w-5 mr-3 text-primary-accent"></i>
                Recetas
            </a>
            <p class="text-xs text-gray-400 uppercase font-bold pt-4 pb-1 px-3">Usuarios y Comunidad</p>

            <a href="admin_manage_users.php" class="nav-link flex items-center p-3 font-semibold transition duration-150<?php echo $is_active('manage_users'); ?>">
                <i class="fas fa-users-cog w-5 mr-3 text-blue-500"></i>
                Cuentas de Usuarios
            </a>
            
            <a href="admin_manage_bios.php" class="nav-link flex items-center p-3 font-semibold transition duration-150<?php echo $is_active('manage_bios'); ?>">
                <i class="fas fa-address-card w-5 mr-3 text-purple-500"></i>
                Biografías Maestros
            </a>

            <a href="foro_ayuda.php" class="nav-link flex items-center p-3 font-semibold transition duration-150<?php echo $is_active('foro_ayuda'); ?>">
                <i class="fas fa-comments w-5 mr-3 text-green-500"></i>
                Moderar Foro
            </a>
            
            <!-- Link de FAQs - Ahora aplica la clase 'active' correctamente -->
            <a href="admin_faqs.php" class="nav-link flex items-center p-3 font-semibold transition duration-150<?php echo $is_active('manage_faqs'); ?>">
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
    <div class="flex-1 overflow-y-auto lg:ml-64">
        
        <header class="bg-white shadow-md p-4 sticky top-0 z-10">
             <div class="flex justify-between items-center max-w-7xl mx-auto">
                <h2 class="text-xl font-bold text-text-base">
                    Usuarios y Comunidad / <span class="text-primary-accent">Gestión de FAQs</span>
                </h2>
            </div>
        </header>
        
        <main class="max-w-7xl mx-auto p-4 lg:p-10">

            <header class="mb-8 border-b pb-4">
                <h1 class="text-3xl font-extrabold text-primary-dark flex items-center">
                    <i class="fas fa-headset mr-3 text-primary-accent"></i> Administrar Preguntas Frecuentes
                </h1>
                <p class="text-gray-500 mt-2">Crea, edita y organiza las preguntas frecuentes que se muestran a los usuarios.</p>
            </header>

            <!-- Mensajes de feedback -->
            <?php if ($error_message): ?>
                <div id="alertMessage" class="p-4 mb-6 rounded-lg bg-red-100 text-red-700 border-red-500 border-l-4 font-mono text-sm" role="alert">
                    <p class="font-bold mb-1">Error:</p>
                    <p><?php echo $error_message; ?></p>
                </div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div id="alertMessage" class="p-4 mb-6 rounded-lg bg-green-100 text-green-700 border-green-500 border-l-4 font-mono text-sm" role="alert">
                    <p class="font-bold mb-1">Éxito:</p>
                    <p><?php echo $success_message; ?></p>
                </div>
            <?php endif; ?>
            <!-- Fin Mensajes de feedback -->

            <div class="bg-white p-6 md:p-8 rounded-xl shadow-2xl mb-10 border-t-8 border-primary-accent">
                <h2 class="text-2xl font-bold text-primary-dark mb-6 flex items-center border-b pb-3">
                    <i class="fas fa-plus-circle mr-3 text-accent"></i>
                    <?php echo $edit_faq ? 'Editar FAQ Existente' : 'Añadir Nueva FAQ'; ?>
                </h2>
                <form method="POST" action="admin_faqs.php" class="space-y-6">
                    
                    <?php if ($edit_faq): ?>
                        <input type="hidden" name="faq_id" value="<?php echo sanitize_output($edit_faq['id']); ?>">
                    <?php endif; ?>

                    <div class="grid md:grid-cols-4 md:gap-6">
                        <div class="md:col-span-3">
                            <label for="pregunta" class="block text-gray-700 font-semibold mb-2">
                                Pregunta
                            </label>
                            <input type="text" id="pregunta" name="pregunta" required class="input-field" placeholder="Ej: ¿Cuál es el horario de soporte?" value="<?php echo sanitize_output($edit_faq['pregunta'] ?? ''); ?>">
                        </div>
                        <div class="md:col-span-1 mt-4 md:mt-0">
                            <label for="orden" class="block text-gray-700 font-semibold mb-2">
                                Orden de Prioridad
                            </label>
                            <input type="number" id="orden" name="orden" class="input-field" min="1" max="999" value="<?php echo sanitize_output($edit_faq['orden'] ?? '99'); ?>">
                            <p class="text-xs text-gray-500 mt-1">Número bajo = se muestra primero.</p>
                        </div>
                    </div>

                    <div>
                        <label for="respuesta" class="block text-gray-700 font-semibold mb-2">
                            Respuesta
                        </label>
                        <textarea id="respuesta" name="respuesta" rows="6" required class="textarea-field" placeholder="Escribe la respuesta detallada a la pregunta frecuente..."><?php echo sanitize_output($edit_faq['respuesta'] ?? ''); ?></textarea>
                    </div>

                    <div class="flex justify-between items-center pt-4">
                        <button type="submit" class="btn-primary py-3 px-6 font-bold rounded-lg shadow-md hover:shadow-xl">
                            <i class="fas fa-save mr-2"></i>
                            <?php echo $edit_faq ? 'Actualizar FAQ' : 'Guardar Nueva FAQ'; ?>
                        </button>
                        
                        <?php if ($edit_faq): ?>
                            <!-- Botón para cancelar edición -->
                            <a href="admin_faqs.php" class="text-sm font-medium text-gray-500 hover:text-primary-dark transition duration-150 p-2 rounded-lg border border-gray-300 hover:bg-gray-100">
                                <i class="fas fa-undo mr-1"></i> Cancelar Edición
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <h2 class="text-3xl font-bold text-primary-dark mb-6 mt-12 flex items-center">
                <i class="fas fa-table mr-2 text-primary-accent"></i>
                Lista de FAQs Registradas 
                <span class="ml-3 text-base font-medium bg-accent text-primary-dark px-3 py-1 rounded-full shadow-inner">
                    <?php echo count($faqs); ?> FAQs
                </span>
            </h2>
            
            <?php if (empty($faqs)): ?>
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-lg shadow-md" role="alert">
                    <p><i class="fas fa-info-circle mr-2"></i> No hay preguntas frecuentes registradas aún. Utiliza el formulario superior para empezar.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto bg-white rounded-xl shadow-lg border-t-2 border-accent">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider w-16">Orden</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pregunta</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider hidden md:table-cell">Respuesta (Resumen)</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider w-24 hidden lg:table-cell">Creado</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider w-20">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php foreach ($faqs as $faq): ?>
                            <tr class="hover:bg-primary-accent/5 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-primary-accent font-extrabold"><?php echo sanitize_output($faq['orden']); ?></td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 max-w-xs overflow-hidden text-ellipsis"><?php echo sanitize_output($faq['pregunta']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600 max-w-md overflow-hidden text-ellipsis hidden md:table-cell">
                                    <span class="block truncate max-w-sm"><?php echo sanitize_output($faq['respuesta_corta']); ?>...</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center hidden lg:table-cell"><?php echo sanitize_output($faq['fecha_formato']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-base font-medium space-x-3">
                                    <a href="admin_faqs.php?edit_id=<?php echo $faq['id']; ?>" class="text-accent hover:text-teal-700 transition duration-150 p-1.5 rounded-full hover:bg-accent/20" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <!-- La eliminación usa GET, pero está protegida por la confirmación de JS -->
                                    <a href="admin_faqs.php?delete_id=<?php echo $faq['id']; ?>" onclick="return confirm('¿Estás seguro de que quieres eliminar la FAQ: <?php echo addslashes($faq['pregunta']); ?>?');" class="text-red-500 hover:text-red-700 transition duration-150 p-1.5 rounded-full hover:bg-red-100" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

    <script>
        // Script para ocultar automáticamente el mensaje de alerta
        document.addEventListener('DOMContentLoaded', () => {
            const messageDiv = document.getElementById('alertMessage');
            if (messageDiv) {
                // Configurar la transición antes de cambiar la opacidad
                messageDiv.style.transition = 'opacity 0.5s ease-out';
                
                setTimeout(() => {
                    messageDiv.style.opacity = '0';
                    // Después de que la transición termine, ocultar el elemento por completo
                    setTimeout(() => {
                        messageDiv.style.display = 'none';
                    }, 500);
                }, 7000); // 7 segundos antes de empezar a desvanecer
            }
        });
    </script>
</body>
</html>