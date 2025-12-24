<?php
session_start();
require 'config/bd.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? '') !== 'maestro') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$user_name_session = $_SESSION['user_name'] ?? 'Maestro'; 

function loadUserData($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT nombre, apellido, email, genero, foto_perfil FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$user = loadUserData($pdo, $user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $genero = $_POST['genero'] ?? null;
    $has_updated = false;

    if (!empty($nombre) && !empty($apellido)) {
        $update = $pdo->prepare("UPDATE usuarios 
                                 SET nombre = ?, apellido = ?, genero = ? 
                                 WHERE id = ?");
        $update->execute([$nombre, $apellido, $genero, $user_id]);
        $message .= " ✔ Datos personales actualizados.";
        $has_updated = true;
    }

    if (!empty($_POST['password'])) {
        if (strlen($_POST['password']) < 6) { 
             $message .= " ❌ La contraseña debe tener al menos 6 caracteres.";
        } else {
            $newPass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $passUpdate = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $passUpdate->execute([$newPass, $user_id]);
            $message .= " ✔ Contraseña actualizada.";
            $has_updated = true;
        }
    }
    if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === 0) {

        $carpeta = "img/perfiles/";
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0777, true);
        }

        $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $allowed_ext = ['jpg', 'jpeg', 'png'];
        
        if (!in_array(strtolower($extension), $allowed_ext)) {
            $message .= " ❌ Solo se permiten imágenes JPG, JPEG o PNG.";
        } else {
            $nombreArchivo = "perfil_" . $user_id . "." . $extension;
            $rutaCompleta = $carpeta . $nombreArchivo;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $rutaCompleta)) {

                $fotoUpdate = $pdo->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
                $fotoUpdate->execute([$rutaCompleta, $user_id]);

                $message .= " ✔ Foto de perfil actualizada.";
                $has_updated = true;
            } else {
                 $message .= " ❌ Error al subir la imagen.";
            }
        }
    }
    if ($has_updated) {
        $user = loadUserData($pdo, $user_id);
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
                        'secondary-red': '#ff6b6b', 
                        'text-base': '#4a4a4a', 
                    },
                    boxShadow: {
                        'input-focus': '0 0 0 3px rgba(78, 205, 196, 0.4)', 
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .input-focus:focus {
            border-color: #4ecdc4; 
            outline: none;
            box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.4);
        }
    </style>
</head>
<body class="bg-primary-light min-h-screen">
<header class="bg-white shadow-md p-4 sticky top-0 z-10">
    <div class="flex justify-between items-center max-w-7xl mx-auto">
        <a href="maestro_dashboard.php" 
            class="text-gray-500 hover:text-primary-dark transition duration-200 text-sm flex items-center">
            <i class="fas fa-arrow-left mr-1"></i> Volver al Panel
        </a>
    </div>
</header>

<main class="max-w-xl mx-auto p-6 mt-10">
    <div class="bg-white p-8 rounded-xl shadow-2xl border-t-4 border-secondary-red">
        <h1 class="text-3xl font-extrabold text-primary-dark text-center mb-8 flex items-center justify-center">
            <i class="fas fa-user-edit text-secondary-red mr-3"></i> Editar Datos de Cuenta
        </h1>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg font-medium flex items-center 
                <?php 
                    if (strpos($message, '❌') !== false) {
                        echo 'bg-secondary-red/10 text-secondary-red border border-secondary-red/50';
                    } elseif (strpos($message, '⚠️') !== false) {
                        echo 'bg-yellow-100 text-yellow-700 border border-yellow-300';
                    } else {
                        echo 'bg-accent/10 text-accent border border-accent/50';
                    }
                ?>">
                <i class="mr-3 fas 
                <?php 
                    if (strpos($message, '❌') !== false) {
                        echo 'fa-times-circle';
                    } elseif (strpos($message, '⚠️') !== false) {
                        echo 'fa-exclamation-triangle';
                    } else {
                        echo 'fa-check-circle';
                    }
                ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="flex flex-col items-center mb-6">
            <img src="<?php echo htmlspecialchars($user['foto_perfil'] ?: 'img/default.png'); ?>" 
                 alt="Foto de Perfil"
                 class="w-32 h-32 object-cover rounded-full border-4 border-gray-200 shadow-md">
            <p class="text-sm text-gray-500 mt-2 font-medium">Foto de perfil actual</p>
        </div>

        <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">

            <div class="bg-gray-50 p-4 rounded-lg border">
                <h3 class="font-bold text-primary-dark mb-3 flex items-center"><i class="fas fa-id-badge mr-2 text-secondary-red"></i> Información Personal</h3>
                
                <label class="block font-semibold mb-1 text-text-base">Nombre:</label>
                <input type="text" name="nombre" value="<?= htmlspecialchars($user['nombre']); ?>" required
                       class="w-full mb-3 p-3 border border-gray-300 rounded-lg input-focus bg-white">

                <label class="block font-semibold mb-1 text-text-base">Apellido:</label>
                <input type="text" name="apellido" value="<?= htmlspecialchars($user['apellido']); ?>" required
                       class="w-full mb-3 p-3 border border-gray-300 rounded-lg input-focus bg-white">

                <label class="block font-semibold mb-1 text-text-base">Género:</label>
                <select name="genero" class="w-full p-3 border border-gray-300 rounded-lg input-focus bg-white">
                    <option value="Masculino" <?= ($user['genero'] ?? '') === 'Masculino' ? 'selected' : '' ?>>Masculino</option>
                    <option value="Femenino" <?= ($user['genero'] ?? '') === 'Femenino' ? 'selected' : '' ?>>Femenino</option>
                    <option value="Otro" <?= ($user['genero'] ?? '') === 'Otro' ? 'selected' : '' ?>>Otro</option>
                    <option value="" <?= ($user['genero'] === null || $user['genero'] === '') ? 'selected' : '' ?>>No especificar</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold mb-1 text-text-base flex items-center"><i class="fas fa-lock mr-2 text-gray-500"></i> Correo Electrónico (No modificable):</label>
                <input type="text" disabled value="<?= htmlspecialchars($user['email']); ?>"
                       class="w-full p-3 bg-gray-200 border border-gray-300 rounded-lg cursor-not-allowed text-gray-600">
            </div>

            <div class="bg-gray-50 p-4 rounded-lg border">
                 <h3 class="font-bold text-primary-dark mb-3 flex items-center"><i class="fas fa-key mr-2 text-secondary-red"></i> Seguridad</h3>

                <label class="block font-semibold mb-1 text-text-base">Nueva Contraseña:</label>
                <input type="password" name="password" placeholder="Dejar vacío para mantener la actual"
                       class="w-full mb-1 p-3 border border-gray-300 rounded-lg input-focus bg-white">
                <p class="text-xs text-gray-500 mb-4">Mínimo 6 caracteres.</p>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg border">
                 <h3 class="font-bold text-primary-dark mb-3 flex items-center"><i class="fas fa-camera mr-2 text-secondary-red"></i> Actualizar Foto</h3>

                <input type="file" name="foto" accept="image/*"
                       class="w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-accent/80 file:text-white hover:file:bg-accent transition">
            </div>

            <button type="submit" class="w-full bg-accent text-white py-3 rounded-lg text-lg font-bold hover:bg-teal-600 transition shadow-lg transform hover:scale-[1.01] mt-6">
                <i class="fas fa-save mr-2"></i> Guardar Todos los Cambios
            </button>
        </form>
    </div>
</main>
</body>
</html>