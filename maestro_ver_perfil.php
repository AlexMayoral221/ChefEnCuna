<?php
session_start();
require 'config/bd.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'maestro') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name_session = $_SESSION['user_name'] ?? 'Maestro'; 

$stmt = $pdo->prepare("SELECT nombre, apellido, email, genero, foto_perfil, fecha_registro FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Error: Usuario no encontrado.");
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
                        'card-hover': '0 8px 16px rgba(0, 0, 0, 0.1)',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
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
    <div class="bg-white p-8 rounded-xl shadow-2xl border-t-4 border-accent">
        <h1 class="text-3xl font-extrabold text-primary-dark text-center mb-8 flex items-center justify-center">
            <i class="fas fa-id-card-alt text-accent mr-3"></i> Mi Perfil Público
        </h1>
        
        <div class="flex justify-center mb-8">
            <img src="<?php echo htmlspecialchars($user['foto_perfil'] ?: 'img/default.png'); ?>"
                 alt="Foto de Perfil"
                 class="w-40 h-40 object-cover rounded-full border-4 border-accent shadow-xl transform transition duration-300 hover:scale-[1.05]">
        </div>

        <div class="space-y-4 text-text-base">
            <div class="border-b border-gray-100 pb-3 flex items-center">
                <i class="fas fa-user-circle w-6 text-accent mr-3"></i>
                <p>
                    <span class="font-semibold text-primary-dark">Nombre Completo:</span> 
                    <?php echo htmlspecialchars($user['nombre']); ?> <?php echo htmlspecialchars($user['apellido']); ?>
                </p>
            </div>

            <div class="border-b border-gray-100 pb-3 flex items-center">
                <i class="fas fa-envelope w-6 text-accent mr-3"></i>
                <p>
                    <span class="font-semibold text-primary-dark">Correo Electrónico:</span> 
                    <?php echo htmlspecialchars($user['email']); ?>
                </p>
            </div>

            <div class="border-b border-gray-100 pb-3 flex items-center">
                <i class="fas fa-venus-mars w-6 text-accent mr-3"></i>
                <p>
                    <span class="font-semibold text-primary-dark">Género:</span> 
                    <?php echo htmlspecialchars($user['genero']) ?: '<span class="italic text-gray-500">No especificado</span>'; ?>
                </p>
            </div>

            <div class="flex items-center pt-3">
                <i class="fas fa-calendar-alt w-6 text-accent mr-3"></i>
                <p>
                    <span class="font-semibold text-primary-dark">Miembro desde:</span> 
                    <?php 
                        echo date('d/m/Y', strtotime(htmlspecialchars($user['fecha_registro']))); 
                    ?>
                </p>
            </div>
        </div>
        
        <div class="mt-8 text-center">
            <a href="maestro_perfil.php"
               class="inline-flex items-center px-4 py-2 bg-secondary-red text-white font-semibold rounded-lg shadow-md hover:bg-red-500 transition duration-200 transform hover:scale-[1.02]">
                <i class="fas fa-user-edit mr-2"></i> Editar mi Cuenta
            </a>
        </div>
    </div>
</main>
</body>
</html>