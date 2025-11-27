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
        $message = "Error al guardar biografía: " . $e->getMessage();
    }
}

$biografia_contenido = '';

try {
    $stmt = $pdo->prepare("SELECT bio FROM usuarios WHERE id = ? AND rol = 'maestro'");
    $stmt->execute([$maestro_id]);
    $biografia_contenido = $stmt->fetchColumn() ?: '';
} catch (PDOException $e) {
    $db_error = true;
    $message = "Error al cargar biografía: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ChefEnCuna</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 p-6">

    <div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow-xl">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h1 class="text-3xl font-bold text-gray-800">
                Mi Biografía 
            </h1>
            <a href="maestro_dashboard.php" class="text-indigo-600 hover:text-indigo-700 font-semibold transition">
                ← Volver al Dashboard
            </a>
        </div>

        <?php if ($message): ?>
            <div class="p-3 mb-4 rounded-lg text-white font-semibold <?= $db_error ? 'bg-red-500' : 'bg-green-500' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="maestro_biografia.php">
            <label for="contenido" class="block text-gray-700 font-bold mb-2">Tu Biografía:</label>

            <textarea id="contenido" name="contenido" rows="10" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"><?= htmlspecialchars($biografia_contenido) ?></textarea>

            <div class="flex justify-end mt-4">
                <button type="submit" class="px-6 py-3 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700">
                    Guardar Biografía
                </button>
            </div>
        </form>
    </div>

</body>
</html>
