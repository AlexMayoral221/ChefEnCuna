<?php
session_start();
require 'config/bd.php'; 

$message = '';
$message_type = '';

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    header('Location: login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? 'alumno'; 

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
                $password_to_store = $password; 

                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nombre, $email, $password_to_store, $rol]);

                $message = "✅ Usuario '<strong>" . htmlspecialchars($nombre) . "</strong>' añadido exitosamente con el rol de <strong>" . htmlspecialchars($rol) . "</strong>.";
                $message_type = 'success';

                $_POST = []; 
            }

        } catch (PDOException $e) {
            $message = "❌ Error al añadir el usuario: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

$nombre_val = $_POST['nombre'] ?? '';
$email_val = $_POST['email'] ?? '';
$rol_val = $_POST['rol'] ?? 'alumno';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefEnCuna</title>
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
        .input-field { border: 1px solid #ccc; padding: 0.75rem; border-radius: 8px; width: 100%; box-sizing: border-box; transition: border-color 0.2s; }
        .input-field:focus { border-color: var(--secondary); outline: none; box-shadow: 0 0 0 2px rgba(78, 205, 196, 0.3); }
        .select-field { border: 1px solid #ccc; padding: 0.75rem; border-radius: 8px; width: 100%; box-sizing: border-box; }
    </style>
</head>
<body>

    <nav class="header-bg p-4 shadow-lg">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold text-white">ChefEnCuna Admin</h1>
            <a href="admin_manage_users.php" class="text-white hover:text-gray-300 transition duration-150">
                <i class="fas fa-arrow-left mr-1"></i> Volver a Usuarios
            </a>
        </div>
    </nav>

    <main class="max-w-3xl mx-auto p-4 sm:p-6 lg:p-8">
        <div class="bg-white rounded-xl shadow-2xl p-6 md:p-10">
            
            <header class="mb-8">
                <h2 class="text-3xl font-extrabold text-dark flex items-center">
                    <i class="fas fa-user-plus mr-3 text-primary"></i> Crear Nuevo Usuario
                </h2>
                <p class="text-gray-500 mt-2">Completa el formulario para registrar un nuevo usuario en el sistema.</p>
            </header>

            <?php if ($message): ?>
                <div class="p-4 mb-6 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border-green-400' : 'bg-red-100 text-red-700 border-red-400'; ?> border-l-4" role="alert">
                    <p class="font-bold"><?php echo $message; ?></p>
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
                    <label for="password" class="block text-gray-700 font-semibold mb-2">Contraseña (Texto Plano para demo)</label>
                    <input type="text" id="password" name="password" class="input-field" placeholder="Mínimo 6 caracteres" required>
                    <p class="text-xs text-gray-500 mt-1">
                        Para producción, esta contraseña DEBE ser cifrada con `password_hash()`.
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

                <button type="submit" class="btn-secondary w-full py-3 rounded-lg font-bold shadow-md hover:shadow-lg">
                    <i class="fas fa-save mr-2"></i> Crear Usuario
                </button>
            </form>

            <div class="mt-8 pt-4 border-t border-gray-200 text-center">
                 <a href="admin_manage_users.php" class="text-primary hover:text-red-700 transition duration-150 text-sm">
                    <i class="fas fa-undo-alt mr-1"></i> Cancelar y volver
                </a>
            </div>

        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const messageDiv = document.querySelector('[role="alert"]');
            if (messageDiv) {
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 5000);
            }
        });
    </script>
</body>
</html>