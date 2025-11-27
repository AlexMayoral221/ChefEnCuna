<?php
session_start();
require 'config/bd.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? '') !== 'maestro') {
    header('Location: login.php');
    exit;
}

$maestro_id = $_SESSION['user_id'];
$message = '';
$recetas = [];

try {
    $stmt = $pdo->prepare("
        SELECT id, titulo, descripcion, fecha_publicacion
        FROM recetas
        WHERE usuario_id = ?
        ORDER BY fecha_publicacion DESC, id DESC
    ");
    $stmt->execute([$maestro_id]);
    $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $message = "❌ Error consultando recetas: " . $e->getMessage();
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

            <h1 class="text-2xl font-bold text-dark">ChefEnCuna • 🍽️ Mis Recetas</h1>

            <div class="flex items-center space-x-6">
            <div class="flex items-center gap-4">
                <a href="maestro_dashboard.php" class="text-gray-600 hover:text-gray-800">Panel</a>
            </div>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto p-6">
        <header class="flex items-center justify-between mb-6">
            <a href="crear_receta.php" class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700">
                + Crear nueva receta
            </a>
        </header>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded bg-red-50 text-red-700 border border-red-100">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($recetas)): ?>
            <div class="bg-white p-6 rounded shadow text-center text-gray-600">
                No has creado ninguna receta todavía.
            </div>
        <?php else: ?>
            <div class="grid gap-6">

                <?php foreach ($recetas as $r): ?>
                    <?php
                        $imgPath = null;
                        foreach (["jpg","png","webp"] as $ext){
                            $path = "img/recetas/".$r['id'].".".$ext;
                            if (file_exists($path)){ $imgPath = $path; break; }
                        }
                    ?>

                    <article class="bg-white rounded-lg shadow p-5 grid grid-cols-1 md:grid-cols-6 gap-4 items-center">

                        <div class="col-span-1 md:col-span-1">
                            <?php if ($imgPath): ?>
                                <img src="<?= $imgPath ?>" class="w-full h-28 object-cover rounded">
                            <?php else: ?>
                                <div class="w-full h-28 flex items-center justify-center bg-gray-100 rounded text-gray-400">
                                    Sin imagen
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-span-1 md:col-span-4">
                            <h3 class="text-xl font-semibold text-gray-800 mb-1">
                                <?= htmlspecialchars($r['titulo']) ?>
                            </h3>

                            <p class="text-gray-600 text-sm mb-2">
                                <?= htmlspecialchars(mb_strlen($r['descripcion']) > 160 
                                    ? mb_substr($r['descripcion'], 0, 160) . "..." 
                                    : $r['descripcion']) ?>
                            </p>

                            <p class="text-xs text-gray-400">
                                Publicado: <?= htmlspecialchars($r['fecha_publicacion']) ?>
                            </p>
                        </div>

                        <div class="col-span-1 md:col-span-1 text-right">
                            <div class="flex flex-col items-end gap-2">

                                <a href="ver_receta.php?id=<?= $r['id'] ?>" 
                                   class="px-3 py-1 bg-blue-50 text-blue-700 rounded hover:bg-blue-100">
                                    Ver
                                </a>

                                <a href="editar_receta.php?id=<?= $r['id'] ?>" 
                                   class="px-3 py-1 bg-yellow-50 text-yellow-700 rounded hover:bg-yellow-100">
                                    Editar
                                </a>

                                <form action="eliminar_receta.php" method="post"
                                      onsubmit="return confirm('¿Eliminar receta \"<?= addslashes($r['titulo']) ?>\" ?');">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="w-full px-3 py-1 bg-red-50 text-red-700 rounded hover:bg-red-100">
                                        Eliminar
                                    </button>
                                </form>

                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
