<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config/bd.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'maestro') {
    header('Location: login.php');
    exit;
}

$maestro_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Maestro';
$message = '';
$db_error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contenido'])) {

    $contenido = trim($_POST['contenido']);

    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET bio = ? WHERE id = ? AND rol = 'maestro'");
        $stmt->execute([$contenido, $maestro_id]);

        $message = "Biografía guardada correctamente.";
    } catch (PDOException $e) {
        $db_error = true;
        $message = "❌ Error al guardar biografía: " . $e->getMessage();
    }
}

$biografia_contenido = '';

try {
    $stmt = $pdo->prepare("SELECT bio FROM usuarios WHERE id = ? AND rol = 'maestro'");
    $stmt->execute([$maestro_id]);
    $biografia_contenido = $stmt->fetchColumn() ?: '';
} catch (PDOException $e) {
    $db_error = true;
    $message = "❌ Error al cargar biografía: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ChefEnCuna - Editar Biografía</title>
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

    <div class="max-w-4xl mx-auto p-6 mt-10">
        <div class="bg-white p-8 rounded-xl shadow-2xl border-t-4 border-accent">
            
            <h1 class="text-3xl font-extrabold text-primary-dark mb-6 flex items-center">
                <i class="fas fa-address-card text-accent mr-3"></i> Gestión de Biografía
            </h1>
            
            <?php if ($message): ?>
                <div class="p-4 mb-6 rounded-lg font-medium flex items-center 
                    <?php 
                        if ($db_error || strpos($message, '❌') !== false) {
                            echo 'bg-secondary-red/10 text-secondary-red border border-secondary-red/50';
                        } else {
                            echo 'bg-accent/10 text-accent border border-accent/50';
                        }
                    ?>">
                    <i class="mr-3 fas <?= $db_error || strpos($message, '❌') !== false ? 'fa-times-circle' : 'fa-check-circle' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <p class="text-gray-600 mb-6">
                Escribe una breve biografía sobre tu experiencia y pasión culinaria. Este texto será visible en tu perfil público.
            </p>

            <form method="POST" action="maestro_biografia.php">
                <label for="contenido" class="block text-text-base font-semibold mb-2 flex items-center">
                    <i class="fas fa-feather-alt mr-2 text-secondary-red"></i> Tu Biografía/Presentación:
                </label>

                <textarea id="contenido" name="contenido" rows="10" 
                          placeholder="Ej: Soy un chef con 15 años de experiencia, especializado en cocina vegetariana e infantil. Me encanta enseñar a cocinar platos sanos y divertidos..."
                          class="w-full p-4 border border-gray-300 rounded-lg focus:border-accent input-focus bg-gray-50 resize-none"><?= htmlspecialchars($biografia_contenido) ?></textarea>

                <div class="flex justify-end mt-6">
                    <button type="submit" class="px-6 py-3 bg-accent text-white font-bold rounded-lg shadow-lg hover:bg-teal-600 transition transform hover:scale-[1.01] flex items-center">
                        <i class="fas fa-save mr-2"></i> Guardar Biografía
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>