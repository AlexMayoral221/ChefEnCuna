<?php
session_start();
require 'config/bd.php'; 

if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? '') !== 'maestro') {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['user_id'];
$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo = $_POST['titulo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $ingredientes = $_POST['ingredientes'] ?? '';
    $procedimiento = $_POST['procedimiento'] ?? '';
    $categoria = $_POST['categoria'] ?? ''; // <-- NUEVO CAMPO

    // Se agrega 'categoria' a la validación
    if ($titulo === "" || $ingredientes === "" || $procedimiento === "" || $categoria === "") {
        $mensaje = "❌ Todos los campos obligatorios (Título, Ingredientes, Procedimiento y Categoría) deben ser llenados.";
    } elseif (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== 0) {
        $mensaje = "❌ Debes subir una imagen.";
    } else {

        try {
            // Se agrega 'categoria' a la sentencia INSERT
            $stmt = $pdo->prepare("
                INSERT INTO recetas (usuario_id, titulo, descripcion, ingredientes, procedimiento, categoria)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            // Se agrega $categoria a la lista de parámetros
            if ($stmt->execute([$usuario_id, $titulo, $descripcion, $ingredientes, $procedimiento, $categoria])) {

                $id = $pdo->lastInsertId();

                // Lógica de subida de imagen
                $rutaDestino = "img/recetas/" . $id . ".jpg";
                
                // Antes de mover, verificar la extensión (aunque el cliente acepta solo JPG, es buena práctica)
                $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg'])) {
                    // Si el archivo no es JPG/JPEG, se borra el registro de la receta y se da un mensaje de error
                    $pdo->prepare("DELETE FROM recetas WHERE id = ?")->execute([$id]);
                    $mensaje = "❌ Solo se permiten imágenes JPG/JPEG. Receta no creada.";
                } else {
                    move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino);

                    $stmt2 = $pdo->prepare("UPDATE recetas SET imagen_ruta = ? WHERE id = ?");
                    $stmt2->execute([$rutaDestino, $id]);

                    // NOTA: Se evita el uso de alert() y se redirige directamente si tiene éxito
                    header('Location: maestro_recetas.php?message=Receta creada correctamente');
                    exit;
                }
            } else {
                $mensaje = "❌ Error al guardar la receta.";
            }
        } catch (PDOException $e) {
            $mensaje = "❌ Error: " . $e->getMessage();
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
<style>
    .input-focus:focus {
        border-color: #3b82f6; /* Tailwind blue-500 */
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.4);
    }
</style>

<script>
    function vistaPrevia(event) {
        let img = document.getElementById("previewImg");
        img.src = URL.createObjectURL(event.target.files[0]);
        img.style.display = "block";
    }
</script>
</head>
<body class="bg-gray-100 flex justify-center py-10">

<div class="bg-white w-full max-w-xl p-8 rounded-2xl shadow-xl">

    <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">Crear Nueva Receta</h2>

    <?php if (!empty($mensaje)): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-center border-l-4 border-red-500">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">

        <div>
            <label class="font-semibold block mb-1">Título:</label>
            <input type="text" name="titulo" required
                class="w-full mt-1 p-3 border rounded-lg bg-gray-50 input-focus" value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>">
        </div>
        
        <!-- CAMPO DE CATEGORÍA AGREGADO -->
        <div>
            <label class="font-semibold block mb-1">Categoría:</label>
            <input type="text" name="categoria" required placeholder="Ej: Cena, Desayuno, Vegetariana"
                class="w-full mt-1 p-3 border rounded-lg bg-gray-50 input-focus" value="<?= htmlspecialchars($_POST['categoria'] ?? '') ?>">
        </div>
        <!-- FIN CAMPO CATEGORÍA -->

        <div>
            <label class="font-semibold block mb-1">Descripción:</label>
            <textarea name="descripcion"
                class="w-full mt-1 p-3 border rounded-lg bg-gray-50 h-24 input-focus"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
        </div>

        <div>
            <label class="font-semibold block mb-1">Ingredientes (Separados por línea):</label>
            <textarea name="ingredientes" required
                class="w-full mt-1 p-3 border rounded-lg bg-gray-50 h-24 input-focus"><?= htmlspecialchars($_POST['ingredientes'] ?? '') ?></textarea>
        </div>

        <div>
            <label class="font-semibold block mb-1">Procedimiento (Paso a paso):</label>
            <textarea name="procedimiento" required
                class="w-full mt-1 p-3 border rounded-lg bg-gray-50 h-24 input-focus"><?= htmlspecialchars($_POST['procedimiento'] ?? '') ?></textarea>
        </div>

        <div class="border p-4 rounded-lg bg-gray-50 shadow-inner">
            <label class="font-semibold block mb-1">Imagen (Solo JPG):</label>
            <input type="file" name="imagen" accept="image/jpeg" onchange="vistaPrevia(event)" required
                class="w-full mt-1 p-2 border rounded-lg bg-white text-sm">

            <img id="previewImg" class="mt-4 w-40 rounded-lg hidden shadow-md border border-gray-300">
        </div>

        <button type="submit"
            class="w-full bg-green-600 text-white py-3 rounded-lg text-lg font-semibold hover:bg-green-700 transition shadow-lg">
            Crear Receta
        </button>

        <a href="maestro_recetas.php"
            class="block mt-3 text-center text-gray-600 hover:text-blue-600 transition">
            ← Volver a mis recetas
        </a>
    </form>
</div>
</body>
</html>