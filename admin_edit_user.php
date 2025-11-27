<?php
session_start();
require 'config/bd.php'; 

$message = '';
$message_type = '';
$user_data = null; 
$user_id_to_edit = (int)($_GET['id'] ?? ($_POST['user_id'] ?? 0));

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
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
            header('Location: admin_manage_users.php');
            exit;
        }
    } catch (PDOException $e) {
        $message = "❌ Error al cargar los datos del usuario: " . $e->getMessage();
        $message_type = 'error';
    }
} else {
    header('Location: admin_manage_users.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id_to_edit > 0) {
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

                    if (!empty($new_password)) {
                        $password_to_store = $new_password;

                        $sql = "UPDATE usuarios SET nombre = ?, email = ?, rol = ?, password = ? WHERE id = ?";
                        $params = [$nombre, $email, $rol, $password_to_store, $user_id_to_edit];
                        $password_change_msg = " y la contraseña ha sido actualizada";
                    } else {
                        $password_change_msg = "";
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
                    <i class="fas fa-user-edit mr-3 text-primary"></i> Editar Usuario
                </h2>
                <p class="text-gray-500 mt-2">Modifica la información del usuario <?php echo htmlspecialchars($user_data['nombre'] ?? ''); ?>.</p>
            </header>

            <?php if ($message): ?>
                <div class="p-4 mb-6 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border-green-400' : 'bg-red-100 text-red-700 border-red-400'; ?> border-l-4" role="alert">
                    <p class="font-bold"><?php echo $message; ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="admin_edit_user.php?id=<?php echo $user_id_to_edit; ?>" class="space-y-6">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id_to_edit); ?>">
                <div>
                    <label for="nombre" class="block text-gray-700 font-semibold mb-2">Nombre Completo</label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($user_data['nombre'] ?? ''); ?>" class="input-field" required>
                </div>
                <div>
                    <label for="email" class="block text-gray-700 font-semibold mb-2">Correo Electrónico</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" class="input-field" required>
                </div>
                <div>
                    <label for="rol" class="block text-gray-700 font-semibold mb-2">Rol del Usuario</label>
                    <select id="rol" name="rol" class="select-field" required 
                        <?php echo ($user_id_to_edit == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                        
                        <option value="alumno" <?php if (($user_data['rol'] ?? 'alumno') === 'alumno') echo 'selected'; ?>>Alumno</option>
                        <option value="maestro" <?php if (($user_data['rol'] ?? 'alumno') === 'maestro') echo 'selected'; ?>>Maestro</option>
                    </select>
                    <?php if ($user_id_to_edit == $_SESSION['user_id']): ?>
                        <input type="hidden" name="rol" value="<?php echo htmlspecialchars($user_data['rol']); ?>">
                        <p class="text-xs text-red-500 mt-1">No puedes cambiar tu propio rol por seguridad.</p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="new_password" class="block text-gray-700 font-semibold mb-2">Nueva Contraseña (Opcional)</label>
                    <input type="text" id="new_password" name="new_password" class="input-field" placeholder="Dejar vacío para no cambiar la contraseña">
                    <p class="text-xs text-gray-500 mt-1">
                        Si introduces algo aquí, se actualizará la contraseña del usuario.
                    </p>
                </div>
                <button type="submit" class="btn-primary w-full py-3 rounded-lg font-bold shadow-md hover:shadow-lg">
                    <i class="fas fa-sync-alt mr-2"></i> Actualizar Usuario
                </button>
            </form>
            <div class="mt-8 pt-4 border-t border-gray-200 text-center">
                 <a href="admin_manage_users.php" class="text-secondary hover:text-green-600 transition duration-150 text-sm">
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