<?php
session_start();
require 'config/bd.php'; 

error_reporting(E_ALL);
ini_set('display_errors', 1);

$active_page = 'manage_users'; 

$message = '';
$message_type = '';
$user_data = null; 

$user_id_to_edit = (int)($_GET['id'] ?? ($_POST['user_id'] ?? 0));

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("❌ Error Crítico: No se pudo establecer la conexión a la base de datos (PDO). Verifique el archivo 'config/bd.php'.");
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? '') !== 'administrador') {
    header('Location: login.php');
    exit;
}

if ($user_id_to_edit > 0) {
    try {
        $stmt = $pdo->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id_to_edit]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user_data) {
            $message = "❌ Error: Usuario no encontrado.";
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        $message = "❌ Error al cargar los datos del usuario: " . $e->getMessage();
        $message_type = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id_to_edit > 0 && $user_data) {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rol = $_POST['rol'] ?? $user_data['rol'];
    $new_password = $_POST['new_password'] ?? '';

    if ($user_id_to_edit == $_SESSION['user_id'] && $rol !== 'administrador') {
        $message = "🚫 Auto-bloqueo prevenido: No puedes cambiar tu propio rol de 'administrador'.";
        $message_type = 'error';
        $rol = $user_data['rol']; 
    }
    
    if ($message_type !== 'error') {
        if (empty($nombre) || empty($email) || empty($rol)) {
            $message = "❌ Los campos Nombre, Email y Rol son obligatorios.";
            $message_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "❌ El formato del correo electrónico es inválido.";
            $message_type = 'error';
        } elseif (!in_array($rol, ['administrador', 'maestro', 'alumno'])) {
            $message = "❌ El rol seleccionado es inválido.";
            $message_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ? AND id != ?");
                $stmt->execute([$email, $user_id_to_edit]);
                if ($stmt->fetchColumn() > 0) {
                    $message = "❌ Error: El correo electrónico ya está registrado por otro usuario.";
                    $message_type = 'error';
                } else {
                    $sql = "UPDATE usuarios SET nombre = ?, email = ?, rol = ? WHERE id = ?";
                    $params = [$nombre, $email, $rol, $user_id_to_edit];
                    $password_change_msg = "";

                    if (!empty($new_password)) {
                        $password_to_store = password_hash($new_password, PASSWORD_DEFAULT);

                        $sql = "UPDATE usuarios SET nombre = ?, email = ?, rol = ?, password = ? WHERE id = ?";
                        $params = [$nombre, $email, $rol, $password_to_store, $user_id_to_edit];
                        $password_change_msg = " y la contraseña ha sido actualizada";
                    }

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);

                    $stmt = $pdo->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE id = ?");
                    $stmt->execute([$user_id_to_edit]);
                    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

                    $message = "✅ Usuario '<strong>" . htmlspecialchars($user_data['nombre']) . "</strong>' actualizado exitosamente" . $password_change_msg . ".";
                    $message_type = 'success';
                }
            } catch (PDOException $e) {
                $message = "❌ Error al actualizar el usuario: " . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

if (!$user_data) {
    if ($message_type !== 'error') {
         header('Location: admin_manage_users.php');
         exit;
    }
}
$is_self_edit = ($user_id_to_edit == $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefEnCuna Admin</title>
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
                    },
                     boxShadow: {
                        'sidebar': '5px 0 15px rgba(0, 0, 0, 0.05)',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f7f6; } 
        .nav-link { transition: background-color 0.2s, color 0.2s; }
        .nav-link.active { background-color: #ff6b6b; color: white; border-radius: 0.5rem; }
        .nav-link:not(.active):hover { background-color: rgba(255, 107, 107, 0.1); color: #ff6b6b; border-radius: 0.5rem; }
        .btn-primary { background-color: #ff6b6b; color: white; transition: background-color 0.2s; }
        .btn-primary:hover { background-color: #d84a4a; }
        .btn-secondary { background-color: #4ecdc4; color: #1e272e; transition: background-color 0.2s; }
        .btn-secondary:hover { background-color: #3aa6a0; }
        .table-header { background-color: #1e272e; color: white; }
        .input-field { 
            border: 1px solid #ccc; 
            padding: 0.75rem; 
            border-radius: 0.5rem; 
            width: 100%; 
            box-sizing: border-box; 
            transition: border-color 0.2s, box-shadow 0.2s; 
        }
        .input-field:focus { 
            border-color: #4ecdc4;
            outline: none; 
            box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.3); 
        }
        .select-field { 
            border: 1px solid #ccc; 
            padding: 0.75rem; 
            border-radius: 0.5rem; 
            width: 100%; 
            box-sizing: border-box;
            appearance: none; 
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none' stroke='%234ecdc4' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1.5em 1.5em;
        }
        @media (min-width: 1024px) {
            .ml-64 { margin-left: 16rem; }
        }
        .fade-out {
            opacity: 0;
            transition: opacity 0.5s ease-out;
            display: none; 
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
                    Usuarios y Comunidad / <span class="text-primary-accent">Editar Usuario</span>
                </h2>
            </div>
        </header>
        
        <main class="max-w-3xl mx-auto p-4 lg:p-10">

            <div class="bg-white rounded-xl shadow-2xl p-6 md:p-10">
                
                <header class="mb-8 border-b pb-4">
                    <h1 class="text-3xl font-extrabold text-primary-dark flex items-center">
                        <i class="fas fa-user-edit mr-3 text-primary-accent"></i> Editar Usuario
                    </h1>
                    <?php if ($user_data): ?>
                        <p class="text-gray-500 mt-2">Modificando información para: <strong><?php echo htmlspecialchars($user_data['nombre']); ?> (ID: <?php echo $user_id_to_edit; ?>)</strong>.</p>
                    <?php else: ?>
                        <p class="text-gray-500 mt-2">Usuario no encontrado o error de carga.</p>
                    <?php endif; ?>
                </header>

                <?php if ($message): ?>
                    <div id="alertMessage" class="p-4 mb-6 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border-green-500' : 'bg-red-100 text-red-700 border-red-500'; ?> border-l-4 font-mono text-sm" role="alert" style="transition: opacity 0.5s ease-out;">
                        <p class="font-bold mb-1">Mensaje del sistema:</p>
                        <p><?php echo $message; ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($user_data): ?>
                <form method="POST" action="admin_edit_user.php?id=<?php echo $user_id_to_edit; ?>" class="space-y-6">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id_to_edit); ?>">
                    <div>
                        <label for="nombre" class="block text-gray-700 font-semibold mb-2">Nombre Completo</label>
                        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($user_data['nombre'] ?? ''); ?>" class="input-field" placeholder="Nombre completo" required>
                    </div>
                    <div>
                        <label for="email" class="block text-gray-700 font-semibold mb-2">Correo Electrónico</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" class="input-field" placeholder="ejemplo@chefenuna.com" required>
                    </div>
                    <div>
                        <label for="rol" class="block text-gray-700 font-semibold mb-2">Rol del Usuario</label>
                        <select id="rol" name="rol" class="select-field" required 
                            <?php echo $is_self_edit ? 'disabled' : ''; ?>>
                            
                            <?php if ($is_self_edit): ?>
                                <option value="administrador" selected>Administrador (No editable)</option>
                            <?php else: ?>
                                <option value="alumno" <?php if (($user_data['rol'] ?? 'alumno') === 'alumno') echo 'selected'; ?>>Alumno</option>
                                <option value="maestro" <?php if (($user_data['rol'] ?? 'alumno') === 'maestro') echo 'selected'; ?>>Maestro</option>
                                <option value="administrador" <?php if (($user_data['rol'] ?? 'alumno') === 'administrador') echo 'selected'; ?>>Administrador</option>
                            <?php endif; ?>
                        </select>
                        <?php if ($is_self_edit): ?>
                            <input type="hidden" name="rol" value="<?php echo htmlspecialchars($user_data['rol']); ?>">
                            <p class="text-xs text-primary-accent mt-1 flex items-center">
                                <i class="fas fa-lock mr-1"></i> No puedes cambiar tu propio rol por seguridad.
                            </p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="new_password" class="block text-gray-700 font-semibold mb-2">Nueva Contraseña (Opcional)</label>
                        <input type="text" id="new_password" name="new_password" class="input-field" placeholder="Dejar vacío para mantener la contraseña actual">
                        <p class="text-xs text-gray-500 mt-1 flex items-center">
                            <i class="fas fa-exclamation-triangle mr-1 text-primary-accent"></i>
                            Si introduces algo, la contraseña del usuario será reemplazada (cifrada).
                        </p>
                    </div>
                    <button type="submit" class="btn-primary w-full py-3 rounded-lg font-bold text-lg shadow-md hover:shadow-xl transition duration-200">
                        <i class="fas fa-sync-alt mr-2"></i> Actualizar Usuario
                    </button>
                </form>
                <?php endif; ?>

            </div>
        </main>
    </div>
</div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const messageDiv = document.getElementById('alertMessage');
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