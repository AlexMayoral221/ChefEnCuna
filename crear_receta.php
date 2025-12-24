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
    $categoria = $_POST['categoria'] ?? ''; 

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

                    // Redirigir al dashboard de recetas al finalizar
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
    <title>ChefEnCuna - Crear Receta</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"> 
<script src="https://cdn.tailwindcss.com"></script>

<script>
    // Configuración de Tailwind para coincidir con el resto del panel
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'primary-dark': '#1e272e', // Gris muy oscuro
                    'primary-light': '#f4f7f6', // Fondo muy claro
                    'accent': '#4ecdc4', // Verde azulado (Accecnto, usado para el botón)
                    'secondary-red': '#ff6b6b', // Rojo (Acción/Alerta)
                    'text-base': '#4a4a4a', // Color de texto principal
                },
                boxShadow: {
                    'input-focus': '0 0 0 3px rgba(78, 205, 196, 0.4)', // Sombra de enfoque con color accent
                }
            }
        }
    }
</script>

<style>
    body { font-family: 'Inter', sans-serif; }
    .input-focus:focus {
        border-color: #4ecdc4; /* Color accent */
        outline: none;
        box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.4);
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
<body class="bg-primary-light flex justify-center py-10 min-h-screen">

<div class="bg-white w-full max-w-2xl p-8 rounded-xl shadow-2xl border-t-4 border-accent">

    <div class="flex justify-between items-start mb-6 border-b pb-4">
        <h2 class="text-3xl font-extrabold text-primary-dark flex items-center">
            <i class="fas fa-plus-circle text-accent mr-3"></i> Crear Nueva Receta
        </h2>
        <a href="maestro_recetas.php"
            class="text-sm text-gray-500 hover:text-primary-dark transition flex items-center font-medium">
            <i class="fas fa-arrow-left mr-2"></i> Volver a mis recetas
        </a>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="mb-6 p-4 rounded-lg bg-secondary-red/10 text-secondary-red border border-secondary-red/50 font-medium flex items-center">
            <i class="fas fa-exclamation-triangle mr-3"></i> <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="font-semibold block mb-1 text-text-base">Título:</label>
                <input type="text" name="titulo" required placeholder="Ej: Pastel de plátano y nueces"
                    class="w-full mt-1 p-3 border border-gray-300 rounded-lg bg-gray-50 input-focus" value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>">
            </div>
            
            <div>
                <label class="font-semibold block mb-1 text-text-base">Categoría:</label>
                <input type="text" name="categoria" required placeholder="Ej: Cena, Desayuno, Vegetariana"
                    class="w-full mt-1 p-3 border border-gray-300 rounded-lg bg-gray-50 input-focus" value="<?= htmlspecialchars($_POST['categoria'] ?? '') ?>">
            </div>
        </div>

        <div>
            <label class="font-semibold block mb-1 text-text-base">Descripción Corta:</label>
            <textarea name="descripcion" placeholder="Una breve descripción de tu receta"
                class="w-full mt-1 p-3 border border-gray-300 rounded-lg bg-gray-50 h-20 resize-none input-focus"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
        </div>

        <div>
            <label class="font-semibold block mb-1 text-text-base">Ingredientes (Separados por línea):</label>
            <textarea name="ingredientes" required placeholder="1 taza de harina&#10;2 huevos&#10;..."
                class="w-full mt-1 p-3 border border-gray-300 rounded-lg bg-gray-50 h-32 resize-none input-focus"><?= htmlspecialchars($_POST['ingredientes'] ?? '') ?></textarea>
        </div>

        <div>
            <label class="font-semibold block mb-1 text-text-base">Procedimiento (Paso a paso):</label>
            <textarea name="procedimiento" required placeholder="1. Mezclar ingredientes secos&#10;2. Añadir líquidos&#10;..."
                class="w-full mt-1 p-3 border border-gray-300 rounded-lg bg-gray-50 h-40 resize-none input-focus"><?= htmlspecialchars($_POST['procedimiento'] ?? '') ?></textarea>
        </div>

        <div class="p-5 rounded-xl bg-gray-50 shadow-inner border border-gray-200">
            <label class="font-semibold block mb-3 text-text-base flex items-center">
                <i class="fas fa-image mr-2"></i> Imagen Principal (Solo JPG):
            </label>
            <input type="file" name="imagen" accept="image/jpeg" onchange="vistaPrevia(event)" required
                class="w-full mt-1 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-accent file:text-white hover:file:bg-teal-500">

            <div class="mt-4">
                <img id="previewImg" class="w-48 h-32 object-cover rounded-lg hidden shadow-md border border-gray-300">
            </div>
        </div>

        <button type="submit"
            class="w-full bg-accent text-white py-3 rounded-lg text-lg font-bold hover:bg-teal-600 transition shadow-lg transform hover:scale-[1.01]">
            <i class="fas fa-paper-plane mr-2"></i> Publicar Receta
        </button>
    </form>
</div>
</body>
</html>