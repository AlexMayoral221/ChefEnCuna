<?php
require 'config/bd.php'; 
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    header('Location: login.php');
    exit;
}

$message = '';
$db_error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $maestro_id = filter_var($_POST['maestro_id'], FILTER_VALIDATE_INT);

    if ($maestro_id === false) {
        $message = "Error: ID de maestro inválido.";
        $db_error = true;
    } else {
        try {
            if ($_POST['action'] === 'update_bio' && isset($_POST['contenido'])) {
                $contenido = trim($_POST['contenido']);

                $stmt = $pdo->prepare("UPDATE usuarios SET bio = ? WHERE id = ? AND rol = 'maestro'");
                $stmt->execute([$contenido, $maestro_id]);

                $message = "Biografía del Maestro actualizada.";
            }
        } catch (PDOException $e) {
            $db_error = true;
            $message = "Error en la base de datos al actualizar: " . $e->getMessage();
        }
    }
}

$maestros = [];
try {
    $query = "
        SELECT 
            id AS user_id, 
            nombre, 
            apellido,
            bio
        FROM usuarios
        WHERE rol = 'maestro'
        ORDER BY nombre ASC
    ";
    $stmt = $pdo->query($query);
    $maestros = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $db_error = true;
    $message = "Error al cargar la lista de maestros: " . $e->getMessage();
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
</head>
<body class="bg-gray-50 p-4">
    <div class="max-w-6xl mx-auto bg-white p-8 rounded-xl shadow-2xl">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-address-card mr-2 text-green-500"></i> Gestión de Biografías de Maestros
            </h1>
            <a href="admin_dashboard.php" class="text-green-600 hover:text-green-700 font-semibold transition">
                <i class="fas fa-arrow-left mr-1"></i> Volver al Dashboard
            </a>
        </div>

        <?php if ($message): ?>
            <div class="p-4 mb-4 rounded-lg text-white font-semibold <?= $db_error ? 'bg-red-500' : 'bg-green-500' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="space-y-6">

            <?php if (empty($maestros)): ?>
                <p class="text-center text-gray-500 p-10 border rounded-lg">No se encontraron maestros.</p>
            <?php else: ?>

                <?php foreach ($maestros as $maestro): ?>
                    <?php
                        $biografia = $maestro['bio'];
                        $maestro_id = $maestro['user_id'];
                        $nombre_completo = htmlspecialchars($maestro['nombre'] . ' ' . $maestro['apellido']);
                    ?>

                    <div class="p-5 rounded-lg shadow-md transition hover:shadow-xl bg-gray-100">
                        <div class="flex justify-between items-center mb-3">
                            <h2 class="text-xl font-bold text-gray-700">
                                Maestro: <?= $nombre_completo ?>
                            </h2>
                        </div>
                        
                        <div class="bg-white p-4 rounded-lg border border-gray-200 mb-4">
                            <p class="text-gray-600 whitespace-pre-wrap text-sm italic">
                                <?= !empty($biografia) ? htmlspecialchars($biografia) : '— Maestro sin biografía registrada. —' ?>
                            </p>
                        </div>
                        
                        <button onclick="toggleEdit(<?= $maestro_id ?>)"
                            class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-lg hover:bg-blue-600 transition">
                            <i class="fas fa-edit mr-1"></i> Editar Biografía
                        </button>

                        <form id="edit-form-<?= $maestro_id ?>" method="POST" class="mt-4 p-4 bg-gray-200 rounded-lg hidden">
                            <h3 class="text-lg font-semibold mb-2 text-gray-700">Editar Biografía</h3>

                            <textarea name="contenido" rows="5" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($biografia) ?></textarea>
                            
                            <input type="hidden" name="maestro_id" value="<?= $maestro_id ?>">
                            <input type="hidden" name="action" value="update_bio">
                            
                            <button type="submit" 
                                class="mt-2 px-4 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition">
                                Guardar
                            </button>
                            <button type="button" onclick="toggleEdit(<?= $maestro_id ?>)"
                                class="mt-2 ml-2 px-4 py-2 bg-gray-500 text-white font-semibold rounded-lg hover:bg-gray-600 transition">
                                Cancelar
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleEdit(id) {
            document.getElementById("edit-form-" + id).classList.toggle("hidden");
        }
    </script>
</body>
</html>
