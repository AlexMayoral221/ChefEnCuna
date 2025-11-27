<?php
session_start();
require 'config/bd.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? '') !== 'maestro') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

$stmt = $pdo->prepare("SELECT nombre, apellido, email, genero, foto_perfil FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $genero = $_POST['genero'] ?? null;

    if (!empty($nombre) && !empty($apellido)) {

        $update = $pdo->prepare("UPDATE usuarios 
                                 SET nombre = ?, apellido = ?, genero = ? 
                                 WHERE id = ?");
        $update->execute([$nombre, $apellido, $genero, $user_id]);

        $message = "✔ Datos actualizados correctamente.";
    }

    if (!empty($_POST['password'])) {
        $newPass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $passUpdate = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $passUpdate->execute([$newPass, $user_id]);
        $message .= " ✔ Contraseña actualizada.";
    }

    if (!empty($_FILES['foto']['name'])) {

        $carpeta = "img/perfiles/";
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0777, true);
        }

        $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nombreArchivo = "perfil_" . $user_id . "." . $extension;
        $rutaCompleta = $carpeta . $nombreArchivo;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $rutaCompleta)) {

            $fotoUpdate = $pdo->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
            $fotoUpdate->execute([$rutaCompleta, $user_id]);

            $message .= " ✔ Foto de perfil actualizada.";
        }
    }

    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
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
        .header-bg { background-color: var(--secondary); }
        .card-shadow { 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 
                        0 4px 6px -2px rgba(0, 0, 0, 0.05); 
        }
        .text-primary-custom { color: var(--primary); }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="header-bg p-4 shadow-lg">
        <div class="max-w-7xl mx-auto flex justify-between items-center">

            <h1 class="text-2xl font-bold text-dark">ChefEnCuna • Editar Perfil</h1>

            <div class="flex items-center space-x-6">
            <div class="flex items-center gap-4">
                <a href="maestro_dashboard.php" class="text-gray-600 hover:text-gray-800">Panel</a>
            </div>
        </div>
    </nav>

<body class="bg-gray-100">
    
    <div class="max-w-xl mx-auto mt-10 bg-white p-8 rounded-xl shadow-lg">

        <?php if ($message): ?>
        <p class="mb-4 p-3 bg-green-100 text-green-800 rounded"><?php echo $message; ?></p>
        <?php endif; ?>

        <div class="flex justify-center mb-6">
            <img src="<?php echo $user['foto_perfil'] ?: 'img/default.png'; ?>" 
                 class="w-40 h-41 rounded-full border shadow">
        </div>

        <form action="" method="POST" enctype="multipart/form-data">

            <label class="block font-semibold mb-1">Nombre:</label>
            <input type="text" name="nombre" value="<?= $user['nombre']; ?>" 
                   class="w-full mb-4 p-3 border rounded">

            <label class="block font-semibold mb-1">Apellido:</label>
            <input type="text" name="apellido" value="<?= $user['apellido']; ?>" 
                   class="w-full mb-4 p-3 border rounded">

            <label class="block font-semibold mb-1">Género:</label>
            <select name="genero" class="w-full mb-4 p-3 border rounded">
                <option value="Masculino" <?= $user['genero'] === 'Masculino' ? 'selected' : '' ?>>Masculino</option>
                <option value="Femenino" <?= $user['genero'] === 'Femenino' ? 'selected' : '' ?>>Femenino</option>
                <option value="Otro" <?= $user['genero'] === 'Otro' ? 'selected' : '' ?>>Otro</option>
                <option value="" <?= $user['genero'] === null ? 'selected' : '' ?>>No especificar</option>
            </select>

            <label class="block font-semibold mb-1">Correo:</label>
            <input type="text" disabled value="<?= $user['email']; ?>"
                   class="w-full mb-4 p-3 bg-gray-200 border rounded cursor-not-allowed">

            <label class="block font-semibold mb-1">Nueva contraseña:</label>
            <input type="password" name="password" placeholder="********"
                   class="w-full mb-6 p-3 border rounded">

            <label class="block font-semibold mb-1">Foto de perfil:</label>
            <input type="file" name="foto" accept="image/*"
                   class="w-full mb-6 p-3 border rounded">

            <button class="w-full bg-blue-600 hover:bg-blue-700 text-white p-3 rounded font-semibold">
                Guardar cambios
            </button>
        </form>
    </div>

</body>
</html>