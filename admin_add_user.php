<?php
session_start();
require 'config/bd.php'; 

error_reporting(E_ALL);
ini_set('display_errors', 1);

$active_page = 'manage_users'; 

$message = '';
$message_type = '';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("❌ Error Crítico: No se pudo establecer la conexión a la base de datos (PDO). Verifique el archivo 'config/bd.php'.");
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? '') !== 'administrador') {
    header('Location: login.php');
    exit;
}

$nombre_val = '';
$email_val = '';
$rol_val = 'alumno';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? 'alumno'; 
    
    $nombre_val = $nombre;
    $email_val = $email;
    $rol_val = $rol;

    if (empty($nombre) || empty($email) || empty($password) || empty($rol)) {
        $message = "❌ Todos los campos son obligatorios.";
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ El formato del correo electrónico es inválido.";
        $message_type = 'error';
    } elseif (!in_array($rol, ['administrador', 'maestro', 'alumno'])) {
        $message = "❌ El rol seleccionado es inválido.";
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $message = "❌ Error: El correo electrónico ya está registrado.";
                $message_type = 'error';
            } else {
                $password_to_store = password_hash($password, PASSWORD_DEFAULT); 

                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nombre, $email, $password_to_store, $rol]);

                $message = "✅ Usuario '<strong>" . htmlspecialchars($nombre) . "</strong>' añadido exitosamente con el rol de <strong>" . htmlspecialchars($rol) . "</strong>. (ID: " . $pdo->lastInsertId() . ")";
                $message_type = 'success';

                $nombre_val = '';
                $email_val = '';
                $rol_val = 'alumno';
            }

        } catch (PDOException $e) {
            $message = "❌ Error al añadir el usuario: " . $e->getMessage();
            $message_type = 'error';
        }
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
                    Usuarios y Comunidad / <span class="text-primary-accent">Crear Nuevo Usuario</span>
                </h2>
            </div>
        </header>
        
        <main class="max-w-3xl mx-auto p-4 lg:p-10">

            <div class="bg-white rounded-xl shadow-2xl p-6 md:p-10">
                
                <header class="mb-8 border-b pb-4">
                    <h1 class="text-3xl font-extrabold text-primary-dark flex items-center">
                        <i class="fas fa-user-plus mr-3 text-primary-accent"></i> Registrar Nuevo Usuario
                    </h1>
                    <p class="text-gray-500 mt-2">Completa el formulario para registrar una nueva cuenta y asignar su rol dentro de la plataforma.</p>
                </header>

                <?php if ($message): ?>
                    <div id="alertMessage" class="p-4 mb-6 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border-green-500' : 'bg-red-100 text-red-700 border-red-500'; ?> border-l-4 font-mono text-sm" role="alert" style="transition: opacity 0.5s ease-out;">
                        <p class="font-bold mb-1">Mensaje del sistema:</p>
                        <p><?php echo $message; ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="admin_add_user.php" class="space-y-6">
                    <div>
                        <label for="nombre" class="block text-gray-700 font-semibold mb-2">Nombre Completo</label>
                        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre_val); ?>" class="input-field" placeholder="Ej: Juan Pérez" required>
                    </div>
                    <div>
                        <label for="email" class="block text-gray-700 font-semibold mb-2">Correo Electrónico</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email_val); ?>" class="input-field" placeholder="ejemplo@chefenuna.com" required>
                    </div>

                    <div>
                        <label for="password" class="block text-gray-700 font-semibold mb-2">Contraseña (Mínimo 6 caracteres)</label>
                        <input type="text" id="password" name="password" class="input-field" placeholder="Introduce una contraseña" required>
                        <p class="text-xs text-gray-500 mt-1 flex items-center">
                             <i class="fas fa-exclamation-triangle mr-1 text-primary-accent"></i>
                            Advertencia: Esta contraseña se guardará cifrada (`password_hash()`), pero el campo es de texto para la demostración.
                        </p>
                    </div>
                    <div>
                        <label for="rol" class="block text-gray-700 font-semibold mb-2">Rol del Usuario</label>
                        <select id="rol" name="rol" class="select-field" required>
                            <option value="alumno" <?php if ($rol_val === 'alumno') echo 'selected'; ?>>Alumno</option>
                            <option value="maestro" <?php if ($rol_val === 'maestro') echo 'selected'; ?>>Maestro</option>
                            <option value="administrador" <?php if ($rol_val === 'administrador') echo 'selected'; ?>>Administrador</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-primary w-full py-3 rounded-lg font-bold text-lg shadow-md hover:shadow-xl transition duration-200">
                        <i class="fas fa-user-plus mr-2"></i> Crear Usuario
                    </button>
                </form>

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