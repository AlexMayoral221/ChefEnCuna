<?php
session_start();
require 'config/bd.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? '') !== 'maestro') {
    header('Location: login.php');
    exit;
}

$maestro_id = $_SESSION['user_id'];
$receta_id = $_GET['id'] ?? null;
$message = '';

if (!$receta_id || !is_numeric($receta_id)) {
    die("ID de receta inválido.");
}

try {
    // Asegurarse de seleccionar la columna 'categoria'
    $stmt = $pdo->prepare("SELECT * FROM recetas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$receta_id, $maestro_id]);
    $receta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receta) {
        die("No tienes permiso para editar esta receta.");
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $ingredientes = trim($_POST['ingredientes'] ?? '');
    $procedimiento = trim($_POST['procedimiento'] ?? '');
    $categoria = trim($_POST['categoria'] ?? ''); // <-- Nuevo campo

    // Agregar 'categoria' a la validación
    if ($titulo === "" || $ingredientes === "" || $procedimiento === "" || $categoria === "") {
        $message = "⚠️ Todos los campos obligatorios (incluyendo la Categoría) deben ser completados.";
        
        // Mantener los datos posteados en $receta en caso de error de validación
        $receta['titulo'] = $titulo;
        $receta['descripcion'] = $descripcion;
        $receta['ingredientes'] = $ingredientes;
        $receta['procedimiento'] = $procedimiento;
        $receta['categoria'] = $categoria;
        
    } else {
        try {
            // Actualizar la consulta SQL para incluir el nuevo campo 'categoria'
            $stmt = $pdo->prepare("
                UPDATE recetas
                SET titulo = ?, descripcion = ?, ingredientes = ?, procedimiento = ?, categoria = ?
                WHERE id = ? AND usuario_id = ?
            ");
            $stmt->execute([$titulo, $descripcion, $ingredientes, $procedimiento, $categoria, $receta_id, $maestro_id]);

            // Lógica de subida de imagen (se mantiene igual)
            if (!empty($_FILES['imagen']['name'])) {

                $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg'])) {
                    $message = "❌ Solo se permiten imágenes JPG.";
                } else {
                    $ruta_imagen = "img/recetas/" . $receta_id . ".jpg";
                    move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_imagen);

                    $stmt = $pdo->prepare("UPDATE recetas SET imagen_ruta = ? WHERE id = ?");
                    $stmt->execute([$ruta_imagen, $receta_id]);
                }
            }

            $message = "✅ Receta actualizada correctamente.";

            // Volver a cargar los datos actualizados de la DB
            $stmt = $pdo->prepare("SELECT * FROM recetas WHERE id = ?");
            $stmt->execute([$receta_id]);
            $receta = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $message = "❌ Error actualizando receta: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ChefEnCuna</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <main class="max-w-3xl mx-auto p-6 mt-10 bg-white shadow rounded-xl">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">✏️ Editar Receta</h1>
        <?php if ($message): ?>
            <div class="mb-4 p-4 
                <?php echo strpos($message, '❌') !== false ? 'bg-red-100 text-red-800' : (strpos($message, '⚠️') !== false ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?> 
                rounded">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block mb-1 font-semibold">Título:</label>
                <input type="text" name="titulo" value="<?= htmlspecialchars($receta['titulo']) ?>" 
                class="w-full p-3 border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            
            <!-- NUEVO CAMPO: CATEGORÍA -->
            <div>
                <label class="block mb-1 font-semibold">Categoría:</label>
                <input type="text" name="categoria" value="<?= htmlspecialchars($receta['categoria'] ?? '') ?>" 
                class="w-full p-3 border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500" required placeholder="Ej: Cena, Desayuno, Vegetariana">
            </div>

            <div>
                <label class="block mb-1 font-semibold">Descripción:</label>
                <textarea name="descripcion" rows="4" 
                class="w-full p-3 border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($receta['descripcion']) ?></textarea>
            </div>
            <div>
                <label class="block mb-1 font-semibold">Ingredientes (Separados por línea):</label>
                <textarea name="ingredientes" rows="5" 
                class="w-full p-3 border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500" required><?= htmlspecialchars($receta['ingredientes']) ?></textarea>
            </div>
            <div>
                <label class="block mb-1 font-semibold">Procedimiento (Paso a paso):</label>
                <textarea name="procedimiento" rows="5" 
                class="w-full p-3 border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500" required><?= htmlspecialchars($receta['procedimiento']) ?></textarea>
            </div>
            
            <div class="border p-4 rounded-lg bg-gray-50">
                <label class="block font-semibold mb-2 text-lg">Imagen de la Receta</label>

                <p class="text-sm text-gray-500 mb-2">Imagen actual:</p>
                <?php if (!empty($receta['imagen_ruta']) && file_exists($receta['imagen_ruta'])): ?>
                    <img src="<?= htmlspecialchars($receta['imagen_ruta']) ?>" class="w-48 h-auto rounded mb-4 shadow-md border">
                <?php else: ?>
                    <p class="text-gray-500 mb-4">No hay imagen actualmente.</p>
                <?php endif; ?>

                <label class="block mb-1 font-semibold">Subir nueva imagen (Solo JPG, opcional):</label>
                <input type="file" name="imagen" accept="image/jpeg" class="border p-2 rounded w-full bg-white text-sm">
            </div>

            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded shadow hover:bg-blue-700 transition duration-150 font-bold">
                <i class="fas fa-save mr-2"></i> Guardar cambios
            </button>

        </form>

        <div class="mt-6">
            <a href="maestro_recetas.php" class="text-gray-700 hover:text-blue-600 transition duration-150 flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Volver a Mis Recetas
            </a>
        </div>
    </main>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>