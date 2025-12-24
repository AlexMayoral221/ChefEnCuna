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
    // Obtener la receta para el usuario actual
    $stmt = $pdo->prepare("SELECT * FROM recetas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$receta_id, $maestro_id]);
    $receta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receta) {
        die("No tienes permiso para editar esta receta o no existe.");
    }
} catch (PDOException $e) {
    die("Error al obtener la receta: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $ingredientes = trim($_POST['ingredientes'] ?? '');
    $procedimiento = trim($_POST['procedimiento'] ?? '');
    $categoria = trim($_POST['categoria'] ?? ''); // <-- Campo Categoría

    // Agregar 'categoria' a la validación
    if ($titulo === "" || $ingredientes === "" || $procedimiento === "" || $categoria === "") {
        $message = "⚠️ Todos los campos obligatorios (Título, Ingredientes, Procedimiento y Categoría) deben ser completados.";
        
        // Mantener los datos posteados en $receta en caso de error de validación
        $receta['titulo'] = $titulo;
        $receta['descripcion'] = $descripcion;
        $receta['ingredientes'] = $ingredientes;
        $receta['procedimiento'] = $procedimiento;
        $receta['categoria'] = $categoria;
        
    } else {
        try {
            // Actualizar la consulta SQL para incluir 'categoria'
            $stmt = $pdo->prepare("
                UPDATE recetas
                SET titulo = ?, descripcion = ?, ingredientes = ?, procedimiento = ?, categoria = ?
                WHERE id = ? AND usuario_id = ?
            ");
            $stmt->execute([$titulo, $descripcion, $ingredientes, $procedimiento, $categoria, $receta_id, $maestro_id]);

            // Lógica de subida de imagen (opcional)
            if (!empty($_FILES['imagen']['name']) && $_FILES['imagen']['error'] === 0) {

                $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg'])) {
                    $message = "❌ Solo se permiten imágenes JPG.";
                } else {
                    $ruta_imagen = "img/recetas/" . $receta_id . ".jpg";
                    move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_imagen);

                    $stmt_img = $pdo->prepare("UPDATE recetas SET imagen_ruta = ? WHERE id = ?");
                    $stmt_img->execute([$ruta_imagen, $receta_id]);
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
    <title>ChefEnCuna - Editar Receta</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Configuración de Tailwind para coincidir con el resto del panel
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
            border-color: #4ecdc4; /* Color accent */
            outline: none;
            box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.4);
        }
    </style>
</head>

<body class="bg-primary-light flex justify-center py-10 min-h-screen">
    <main class="w-full max-w-2xl p-8 bg-white rounded-xl shadow-2xl border-t-4 border-accent">
        
        <div class="flex justify-between items-start mb-6 border-b pb-4">
            <h1 class="text-3xl font-extrabold text-primary-dark flex items-center">
                <i class="fas fa-edit text-accent mr-3"></i> Editar Receta
            </h1>
            <a href="maestro_recetas.php"
                class="text-sm text-gray-500 hover:text-primary-dark transition flex items-center font-medium">
                <i class="fas fa-arrow-left mr-2"></i> Volver a Mis Recetas
            </a>
        </div>

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

        <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="font-semibold block mb-1 text-text-base">Título:</label>
                    <input type="text" name="titulo" value="<?= htmlspecialchars($receta['titulo']) ?>" 
                    class="w-full mt-1 p-3 border border-gray-300 rounded-lg bg-gray-50 input-focus" required>
                </div>
                
                <div>
                    <label class="font-semibold block mb-1 text-text-base">Categoría:</label>
                    <input type="text" name="categoria" value="<?= htmlspecialchars($receta['categoria'] ?? '') ?>" 
                    class="w-full mt-1 p-3 border border-gray-300 rounded-lg bg-gray-50 input-focus" required placeholder="Ej: Cena, Desayuno, Vegetariana">
                </div>
            </div>

            <div>
                <label class="font-semibold block mb-1 text-text-base">Descripción:</label>
                <textarea name="descripcion" rows="4" placeholder="Una breve descripción de tu receta"
                class="w-full mt-1 p-3 border border-gray-300 rounded-lg bg-gray-50 resize-none input-focus"><?= htmlspecialchars($receta['descripcion']) ?></textarea>
            </div>
            
            <div>
                <label class="font-semibold block mb-1 text-text-base">Ingredientes (Separados por línea):</label>
                <textarea name="ingredientes" rows="5" placeholder="1 taza de harina&#10;2 huevos&#10;..."
                class="w-full mt-1 p-3 border border-gray-300 rounded-lg bg-gray-50 resize-none input-focus" required><?= htmlspecialchars($receta['ingredientes']) ?></textarea>
            </div>
            
            <div>
                <label class="font-semibold block mb-1 text-text-base">Procedimiento (Paso a paso):</label>
                <textarea name="procedimiento" rows="5" placeholder="1. Mezclar ingredientes secos&#10;2. Añadir líquidos&#10;..."
                class="w-full mt-1 p-3 border border-gray-300 rounded-lg bg-gray-50 resize-none input-focus" required><?= htmlspecialchars($receta['procedimiento']) ?></textarea>
            </div>
            
            <div class="p-5 rounded-xl bg-gray-50 shadow-inner border border-gray-200">
                <label class="block font-bold mb-3 text-text-base flex items-center text-lg">
                    <i class="fas fa-image mr-2"></i> Imagen Principal
                </label>

                <p class="text-sm text-gray-600 mb-3">Imagen actual:</p>
                <?php 
                $has_image = !empty($receta['imagen_ruta']) && file_exists($receta['imagen_ruta']);
                ?>
                <div class="mb-4 <?= $has_image ? 'block' : 'hidden' ?>" id="currentImageContainer">
                    <img src="<?= htmlspecialchars($receta['imagen_ruta'] ?? '') ?>?t=<?= time() ?>" 
                         class="w-48 h-32 object-cover rounded-lg shadow-md border border-gray-300" id="currentImage">
                </div>
                <?php if (!$has_image): ?>
                    <p class="text-gray-500 mb-4 bg-white p-3 rounded-lg border border-dashed border-gray-300 w-48 text-center text-sm">No hay imagen actualmente.</p>
                <?php endif; ?>

                <label class="block mb-2 font-semibold text-text-base">Subir nueva imagen (Solo JPG, opcional):</label>
                <input type="file" name="imagen" accept="image/jpeg" 
                    class="w-full mt-1 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-secondary-red/80 file:text-white hover:file:bg-secondary-red transition">
            </div>

            <button type="submit" class="w-full bg-accent text-white py-3 rounded-lg text-lg font-bold hover:bg-teal-600 transition shadow-lg transform hover:scale-[1.01]">
                <i class="fas fa-save mr-2"></i> Guardar Cambios
            </button>
        </form>

    </main>
</body>
</html>