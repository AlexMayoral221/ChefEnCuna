<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require 'config/bd.php';
require 'config/header.php';

function rutaImagenReceta($ruta_db) {
    $placeholder = 'https://via.placeholder.com/400x300/69A64A/ffffff?text=Receta+ChefEnCuna';

    if (empty($ruta_db)) {
        return $placeholder;
    }

    if (filter_var($ruta_db, FILTER_VALIDATE_URL)) {
        return $ruta_db;
    }

    $ruta = 'img/recetas' . basename($ruta_db);

    if (!file_exists($ruta)) {
        return $placeholder;
    }

    return $ruta;
}

$curso_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$curso_detalle = null;

$placeholder_curso_url = 'https://via.placeholder.com/800x400/1e3a8a/ffffff?text=Curso+ChefEnCuna';

if ($curso_id > 0) {
    try {
        $sql_detalle = "SELECT * FROM cursos WHERE id = :id";
        $stmt_detalle = $pdo->prepare($sql_detalle);
        $stmt_detalle->bindParam(':id', $curso_id, PDO::PARAM_INT);
        $stmt_detalle->execute();
        $curso_detalle = $stmt_detalle->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al buscar curso por ID: " . $e->getMessage());
        $curso_detalle = false;
    }
}

$termino = isset($_GET['q']) ? trim($_GET['q']) : '';
$recetas = [];
$cursos = [];
$es_busqueda = false;

if ($curso_detalle === null && $termino !== '') {
    $es_busqueda = true;
    $palabras = explode(' ', $termino);

    $palabras_clave = array_filter($palabras, fn($p) => strlen($p) >= 3);

    if (!empty($palabras_clave)) {
        $consulta_c = [];
        $param_c = [];
        $i = 0;

        foreach ($palabras_clave as $p) {
            $t = ":c{$i}_t";
            $d = ":c{$i}_d";

            $consulta_c[] = "(titulo LIKE $t OR descripcion LIKE $d)";
            $param_c[$t] = $param_c[$d] = "%$p%";
            $i++;
        }

        if ($consulta_c) {
            $sql = "SELECT * FROM cursos WHERE " . implode(' AND ', $consulta_c);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($param_c);
            $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $consulta_r = [];
        $param_r = [];
        $i = 0;

        foreach ($palabras_clave as $p) {
            $t = ":r{$i}_t";
            $d = ":r{$i}_d";
            $ing = ":r{$i}_i";
            $proc = ":r{$i}_p";

            $consulta_r[] = "(titulo LIKE $t OR descripcion LIKE $d OR ingredientes LIKE $ing OR procedimiento LIKE $proc)";

            $param_r[$t] = $param_r[$d] =
            $param_r[$ing] = $param_r[$proc] = "%$p%";
            $i++;
        }
        if ($consulta_r) {
            $sql = "SELECT * FROM recetas WHERE " . implode(' AND ', $consulta_r);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($param_r);
            $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de Búsqueda | ChefEnCuna</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f7f1e3; 
        }
        .card-magazine { 
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.06);
        }
        .card-magazine:hover { 
            transform: translateY(-8px); 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); 
        }
        .badge-curso {
            background-color: #10b981; 
            color: white;
            padding: 4px 10px;
            border-bottom-left-radius: 8px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
        .badge-receta {
            background-color: #84cc16; /* Lima */
            color: #1f2937;
            padding: 4px 10px;
            border-bottom-left-radius: 8px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
    </style>
</head>
<body class="text-gray-900">
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 pb-20">

<?php if ($curso_detalle): ?>
    <a href="javascript:history.back()" class="text-lime-600 hover:text-lime-800 font-bold mb-6 inline-flex items-center transition duration-200">
        <i class="fas fa-chevron-left mr-2 text-sm"></i> Volver a los resultados
    </a>

    <div class="bg-white rounded-2xl shadow-2xl overflow-hidden p-6 sm:p-10 border border-gray-100">
        <header class="mb-8">
            <h1 class="text-5xl font-extrabold text-gray-900 leading-tight">
                <?= htmlspecialchars($curso_detalle['titulo']) ?>
            </h1>
            <p class="text-xl text-gray-500 mt-2 font-light">
                Un camino completo de aprendizaje culinario.
            </p>
        </header>

        <?php $img = $curso_detalle['imagen_url'] ?: $placeholder_curso_url; ?>
        <img src="<?= htmlspecialchars($img) ?>" 
             class="w-full h-96 object-cover rounded-xl mb-8 shadow-lg" 
             onerror="this.onerror=null; this.src='<?= $placeholder_curso_url ?>';">

        <div class="lg:grid lg:grid-cols-3 lg:gap-12">
            <div class="lg:col-span-1 space-y-4 mb-8 lg:mb-0 p-4 bg-gray-50 rounded-lg">
                <h2 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">Detalles del Curso</h2>
                
                <div class="flex items-center space-x-3">
                    <i class="fas fa-user-tie text-lime-600"></i>
                    <span class="font-medium">Profesor:</span>
                    <span class="text-gray-600"><?= htmlspecialchars($curso_detalle['profesor'] ?? 'No especificado') ?></span>
                </div>

                <div class="flex items-center space-x-3">
                    <i class="fas fa-signal text-lime-600"></i>
                    <span class="font-medium">Nivel:</span>
                    <span class="bg-lime-100 text-lime-800 px-3 py-1 text-xs rounded-full font-bold">
                        <?= htmlspecialchars($curso_detalle['nivel']) ?>
                    </span>
                </div>

                <div class="flex items-center space-x-3">
                    <i class="fas fa-clock text-lime-600"></i>
                    <span class="font-medium">Duración:</span>
                    <span class="text-gray-600"><?= htmlspecialchars($curso_detalle['duracion']) ?></span>
                </div>
            </div>

            <div class="lg:col-span-2">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Acerca de este Curso</h2>
                <div class="prose max-w-none text-gray-700">
                    <p class="whitespace-pre-wrap">
                        <?= htmlspecialchars($curso_detalle['descripcion']) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($es_busqueda): ?>
    
    <?php if (count($cursos) + count($recetas) === 0): ?>
        <div class="bg-white p-10 rounded-xl shadow-lg text-center border-l-4 border-red-500 mt-8">
            <i class="fas fa-sad-cry text-5xl text-red-500 mb-4"></i>
            <p class="text-gray-700 text-xl font-semibold">¡Lo sentimos! No encontramos resultados.</p>
            <p class="text-gray-500 mt-2">Intenta con términos más generales o diferentes.</p>
        </div>
    <?php endif; ?>

    <?php if ($cursos): ?>
        <h2 class="text-3xl font-bold mt-12 mb-6 text-gray-900 border-b pb-2">
            <i class="fas fa-graduation-cap text-lime-600 mr-2"></i> Cursos (<?= count($cursos) ?>)
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($cursos as $curso): ?>
                <div class="bg-white rounded-xl overflow-hidden card-magazine relative">
                    
                    <div class="absolute top-0 right-0 badge-curso z-10">CURSO</div>
                    <?php $img = $curso['imagen_url'] ?: 'https://via.placeholder.com/400x300/1e3a8a/ffffff?text=Curso+ChefEnCuna'; ?>

                    <img src="<?= htmlspecialchars($img) ?>" class="w-full h-48 object-cover" onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/1e3a8a/ffffff?text=Curso+ChefEnCuna';">

                    <div class="p-6 flex flex-col">
                        <h3 class="text-xl font-bold mb-3 text-gray-800 leading-snug"><?= htmlspecialchars($curso['titulo']) ?></h3>

                        <div class="flex flex-wrap gap-2 text-xs mb-3">
                            <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full font-medium">
                                <?= htmlspecialchars($curso['nivel']) ?>
                            </span>
                            <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full font-medium">
                                <i class="far fa-clock mr-1"></i> <?= htmlspecialchars($curso['duracion']) ?>
                            </span>
                        </div>

                        <p class="text-gray-500 text-sm mb-4 line-clamp-3">
                            <?= htmlspecialchars(mb_substr($curso['descripcion'], 0, 100)) ?>...
                        </p>

                        <a href="ver_curso.php?id=<?= $curso['id'] ?>" class="mt-auto bg-lime-500 text-white px-4 py-2 rounded-lg text-center font-semibold hover:bg-lime-600 transition duration-300 shadow-md"> 
                            Explorar Curso <i class="fas fa-arrow-right ml-1 text-sm"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($recetas): ?>
        <h2 class="text-3xl font-bold mt-12 mb-6 text-gray-900 border-b pb-2">
            <i class="fas fa-utensils text-lime-600 mr-2"></i> Recetas (<?= count($recetas) ?>)
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($recetas as $receta): ?>

                <?php $imagePath = !empty($receta['imagen_ruta']) ? htmlspecialchars($receta['imagen_ruta']) : 'img/recetas/' . htmlspecialchars($receta['id']) . '.jpg'; ?>
                <div class="bg-white rounded-xl overflow-hidden card-magazine relative">

                    <div class="absolute top-0 right-0 badge-receta z-10">RECETA</div>
                    <img src="<?= $imagePath ?>" class="w-full h-48 object-cover" onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/69A64A/ffffff?text=Receta+ChefEnCuna';">

                    <div class="p-6 flex flex-col">
                        <h3 class="text-xl font-bold mb-3 text-gray-800 leading-snug"><?= htmlspecialchars($receta['titulo']) ?></h3>

                        <span class="text-xs font-bold bg-green-100 text-green-700 px-3 py-1 rounded-full w-max mb-3">
                            <i class="fas fa-tag mr-1"></i> Categoría: <?= htmlspecialchars($receta['categoria']) ?>
                        </span>

                        <p class="text-gray-500 text-sm mb-4 line-clamp-3">
                            <?= htmlspecialchars(mb_substr($receta['descripcion'], 0, 100)) ?>...
                        </p>

                        <a href="ver_receta.php?id=<?= $receta['id'] ?>" class="mt-auto bg-lime-500 text-white px-4 py-2 rounded-lg text-center font-semibold hover:bg-lime-600 transition duration-300 shadow-md">
                            Ver Receta <i class="fas fa-arrow-right ml-1 text-sm"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php elseif ($curso_id > 0 && !$curso_detalle): ?>

    <div class="bg-white p-10 rounded-xl shadow-lg text-center border-t-4 border-red-600 mt-12">
        <i class="fas fa-exclamation-triangle text-6xl text-red-600 mb-4"></i>
        <p class="text-gray-800 text-2xl font-bold">Error 404: Contenido no Encontrado</p>
        <p class="text-gray-500 mt-2">El curso con ID **<?= $curso_id ?>** que estás buscando no está disponible o ha sido eliminado.</p>
        <a href="javascript:history.back()" class="mt-6 text-lime-600 font-bold inline-block hover:text-lime-800 transition">&larr; Volver a la página anterior</a>
    </div>

<?php endif; ?>
<div class="h-20"></div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
</body>
</html>