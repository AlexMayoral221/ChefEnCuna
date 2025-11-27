<?php
session_start();
require 'config/bd.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'maestro') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT nombre, apellido, email, genero, foto_perfil, fecha_registro FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
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

            <h1 class="text-2xl font-bold text-dark">ChefEnCuna • Mi Perfil</h1>

            <div class="flex items-center space-x-6">
            <div class="flex items-center gap-4">
                <a href="maestro_dashboard.php" class="text-gray-600 hover:text-gray-800">Panel</a>
            </div>
        </div>
    </nav>
<body class="bg-gray-100">

<div class="max-w-lg mx-auto bg-white mt-10 p-8 rounded-xl shadow-lg">
    <div class="flex justify-center mb-6">
        <img src="<?php echo $user['foto_perfil'] ?: 'img/default.png'; ?>"
             class="w-40 h-41 rounded-full border shadow">
    </div>

    <div class="space-y-4 text-lg">

        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($user['nombre']); ?> <?php echo htmlspecialchars($user['apellido']); ?></p>

        <p><strong>Correo:</strong> <?php echo htmlspecialchars($user['email']); ?></p>

        <p><strong>Género:</strong> 
            <?php echo $user['genero'] ?: 'No especificado'; ?>
        </p>
    </div>
</div>
</body>
</html>