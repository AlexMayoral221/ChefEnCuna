<?php
session_start();
require 'config/bd.php'; 

error_reporting(E_ALL);
ini_set('display_errors', 1);

$active_page = 'manage_bios'; 

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("❌ Error Crítico: No se pudo establecer la conexión a la base de datos (PDO). Verifique el archivo 'config/bd.php'.");
}
if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? '') !== 'administrador') {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_bio') {

    $maestro_id = filter_var($_POST['maestro_id'], FILTER_VALIDATE_INT);
    $contenido = trim($_POST['contenido'] ?? '');

    if ($maestro_id === false || $maestro_id <= 0) {
        $_SESSION['flash_message'] = "❌ Error: ID de maestro inválido o no proporcionado.";
        $_SESSION['flash_type'] = 'error';
    } else {
        try {
            // Se usa PDO::PARAM_STR para el contenido de la biografía
            $stmt = $pdo->prepare("UPDATE usuarios SET bio = ? WHERE id = ? AND rol = 'maestro'");
            $stmt->bindParam(1, $contenido, PDO::PARAM_STR);
            $stmt->bindParam(2, $maestro_id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                 $_SESSION['flash_message'] = "✅ Biografía del Maestro (ID: {$maestro_id}) actualizada exitosamente.";
                 $_SESSION['flash_type'] = 'success';
            } else {
                 // Esto puede ser porque el contenido es idéntico o el ID no es maestro.
                 $_SESSION['flash_message'] = "⚠️ Advertencia: La biografía no cambió o el usuario (ID: {$maestro_id}) no es un maestro válido.";
                 $_SESSION['flash_type'] = 'warning';
            }
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = "❌ Error en la base de datos al actualizar: " . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
        }
    }
    // Implementación del patrón Post-Redirect-Get (PRG)
    header('Location: admin_manage_bios.php');
    exit;
}

// 3. CARGAR MENSAJES FLASH
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'];
    // Limpiar variables de sesión después de mostrar
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

// 4. CARGAR LISTA DE MAESTROS (GET)
$maestros = [];
try {
    $query = "
        SELECT 
            id AS user_id, 
            nombre, 
            bio
        FROM usuarios
        WHERE rol = 'maestro'
        ORDER BY nombre ASC
    ";
    $stmt = $pdo->query($query);
    $maestros = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si ya hay un mensaje flash, lo mantenemos y solo agregamos el error.
    $errorMessage = "❌ Error al cargar la lista de maestros: " . $e->getMessage();
    if (empty($message)) {
        $message = $errorMessage;
        $message_type = 'error';
    } else {
        $message .= "<br>" . $errorMessage;
        $message_type = $message_type === 'success' ? 'warning' : 'error';
    }
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
            
            <!-- ENLACE DE BIOGRAFÍAS ACTIVO -->
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
             <div class="flex justify-between items-center max-w-6xl mx-auto">
                <h2 class="text-xl font-bold text-text-base">
                    Usuarios y Comunidad / <span class="text-primary-accent">Gestión de Biografías</span>
                </h2>
            </div>
        </header>
        
        <main class="max-w-6xl mx-auto p-4 lg:p-10">
            <div class="bg-white rounded-xl shadow-2xl p-6 md:p-10">
                <header class="mb-8 border-b pb-4">
                    <h1 class="text-3xl font-extrabold text-primary-dark flex items-center">
                        <i class="fas fa-address-card mr-3 text-primary-accent"></i> Biografías de Maestros
                    </h1>
                    <p class="text-gray-500 mt-2">Administra y edita las biografías que se muestran en el perfil público de cada maestro.</p>
                </header>

                <?php if ($message): ?>
                    <?php
                        $bg_class = 'bg-gray-100 text-gray-700 border-gray-500';
                        if ($message_type === 'success') {
                            $bg_class = 'bg-green-100 text-green-700 border-green-500';
                        } elseif ($message_type === 'error') {
                            $bg_class = 'bg-red-100 text-red-700 border-red-500';
                        } elseif ($message_type === 'warning') {
                            $bg_class = 'bg-yellow-100 text-yellow-700 border-yellow-500';
                        }
                    ?>
                    <div id="alertMessage" class="p-4 mb-6 rounded-lg <?php echo $bg_class; ?> border-l-4 font-sans text-base shadow-lg" role="alert" style="transition: opacity 0.5s ease-out;">
                        <p class="font-bold mb-1">Mensaje del sistema:</p>
                        <p><?php echo $message; ?></p>
                    </div>
                <?php endif; ?>

                <div class="space-y-6">

                    <?php if (empty($maestros)): ?>
                        <div class="text-center text-gray-500 p-10 border border-dashed border-gray-300 rounded-xl bg-gray-50">
                            <i class="fas fa-chalkboard-teacher text-4xl mb-3 text-gray-400"></i>
                            <p class="font-semibold">No se encontraron maestros activos.</p>
                            <p class="text-sm mt-1">Asegúrate de haber registrado usuarios con el rol 'maestro' en el panel de Gestión de Usuarios.</p>
                        </div>
                    <?php else: ?>

                        <?php foreach ($maestros as $maestro): ?>
                            <?php
                                $biografia = $maestro['bio'] ?? ''; // Usar un valor por defecto si es null
                                $maestro_id = $maestro['user_id'];
                                $nombre_completo = htmlspecialchars($maestro['nombre'] ?? 'Maestro Desconocido');
                                $has_bio = !empty($biografia);
                            ?>

                            <div class="p-5 rounded-xl shadow-lg transition hover:shadow-xl border border-gray-200 bg-gray-50">
                                <div class="flex justify-between items-center mb-3">
                                    <h2 class="text-xl font-bold text-primary-dark">
                                        <i class="fas fa-user-circle mr-2 text-accent"></i> <?= $nombre_completo ?> 
                                    </h2>
                                </div>
                                
                                <div class="bg-white p-4 rounded-lg border mb-4 <?php echo $has_bio ? 'border-accent/30' : 'border-red-300 bg-red-50'; ?>">
                                    <h3 class="font-semibold text-sm mb-2 text-gray-600 flex items-center">
                                        <i class="fas fa-pencil-alt mr-2"></i> Biografía Actual:
                                    </h3>
                                    <div class="text-gray-700 whitespace-pre-wrap text-sm italic min-h-6">
                                        <?= $has_bio ? nl2br(htmlspecialchars($biografia)) : '— 🚨 Maestro sin biografía registrada. Debe añadir una descripción. —' ?>
                                    </div>
                                </div>
                                
                                <button type="button" onclick="toggleEdit(<?= $maestro_id ?>)"
                                    class="px-4 py-2 bg-accent text-primary-dark font-semibold rounded-lg hover:bg-teal-600 hover:text-white transition duration-200 shadow-md">
                                    <i class="fas fa-edit mr-1"></i> <?= $has_bio ? 'Editar' : 'Añadir' ?> Biografía
                                </button>

                                <form id="edit-form-<?= $maestro_id ?>" method="POST" class="mt-4 p-4 rounded-xl bg-gray-100 border border-gray-200 hidden shadow-inner">
                                    <h3 class="text-lg font-bold mb-3 text-primary-dark border-b pb-2">Formulario de Edición para <?= $nombre_completo ?></h3>

                                    <label for="contenido-<?= $maestro_id ?>" class="block text-gray-700 font-semibold mb-2">Contenido de la Biografía</label>
                                    <textarea id="contenido-<?= $maestro_id ?>" name="contenido" rows="5" class="textarea-field w-full p-3" placeholder="Escribe una breve biografía sobre la experiencia y especialidad del maestro."><?= htmlspecialchars($biografia) ?></textarea>
                                    
                                    <input type="hidden" name="maestro_id" value="<?= $maestro_id ?>">
                                    <input type="hidden" name="action" value="update_bio">
                                    
                                    <button type="submit" 
                                        class="mt-4 px-6 py-2 btn-primary text-white font-bold rounded-lg shadow-md hover:shadow-lg">
                                        <i class="fas fa-save mr-1"></i> Guardar Biografía
                                    </button>
                                    <button type="button" onclick="toggleEdit(<?= $maestro_id ?>)"
                                        class="mt-4 ml-2 px-6 py-2 bg-gray-400 text-white font-bold rounded-lg hover:bg-gray-500 transition duration-200">
                                        Cancelar
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

    <script>
        function toggleEdit(id) {
            document.getElementById("edit-form-" + id).classList.toggle("hidden");
        }

        document.addEventListener('DOMContentLoaded', () => {
            const messageDiv = document.getElementById('alertMessage');
            if (messageDiv) {
                const hideMessage = () => {
                    messageDiv.style.opacity = '0';
                    messageDiv.style.transition = 'opacity 0.5s ease-out';
                    setTimeout(() => {
                        messageDiv.style.display = 'none';
                    }, 500); 
                };
                
                setTimeout(hideMessage, 7000); 
            }
        });
    </script>
</body>
</html>