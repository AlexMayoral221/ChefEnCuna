<?php
session_start();

require 'bd.php';
include 'templates/_header.php';

if (isset($_GET['query'])) {
    $searchQuery = $_GET['query'];

    // Verifica si el término de búsqueda es "cursos" o "recetas"
    if ($searchQuery === 'cursos') {
        try {
            // Obtiene todos los cursos
            $stmt = $pdo->prepare("SELECT id, titulo, descripcion, nivel FROM cursos");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    } elseif ($searchQuery === 'recetas') {
        try {
            // Obtiene todas las recetas
            $stmt = $pdo->prepare("SELECT id, titulo, descripcion FROM recetas");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    } else {
        echo "No se especificó un término de búsqueda válido.";
    }
} else {
    // Si no se especifica ningún término de búsqueda, se obtienen todas las recetas
    try {
        $stmt = $pdo->prepare("SELECT id, titulo, descripcion FROM recetas");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>ChefEnCuna</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-image: url('img/wallpaper.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Georgia', serif;
            color: #007bff;
        }
        .btn-ver-mas:hover {
            background-color: #4cae4c;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            text-decoration: none; 
        }
        .btn-ver-mas {
            background-color: #5cb85c;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            box-shadow: 2px 2px 5px rgba(0,0,0,0.2);
            border: none;
            transition: background-color 0.3s, box-shadow 0.3s; 
            margin-top: 50px; 
        } 
        .espacio-superior {
            margin-top: 40px;
        }
        .img-resize {
            width: 100%; 
            height: 200px; 
            object-fit: cover;
        }
        .card-img-top {
            width: 100%; /* Establece el ancho al 100% para que la imagen se ajuste al contenedor */
            height: 200px; /* Establece la altura deseada */
            object-fit: cover; /* Mantiene la relación de aspecto y recorta la imagen si es necesario */
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="row">
        <?php if (!empty($results)): ?>
            <?php foreach ($results as $result): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card">
                        <?php if (isset($result['nivel'])): // Si es un curso ?>
                            <img class="card-img-top" src="img/cursos/<?php echo htmlspecialchars($result['id']); ?>.jpg" alt="Imagen del Curso">
                        <?php else: // Si es una receta ?>
                            <img class="card-img-top" src="img/recetas/<?php echo htmlspecialchars($result['id']); ?>.jpg" alt="Imagen de la Receta">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($result['titulo']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($result['descripcion']); ?></p>
                            <?php if (isset($result['nivel'])): // Si es un curso ?>
                                <p class="card-text"><small class="text-muted">Nivel: <?php echo htmlspecialchars($result['nivel']); ?></small></p>
                                <a href="detalle_curso.php?id=<?php echo htmlspecialchars($result['id']); ?>" class="btn btn-primary">Más información</a>
                            <?php else: // Si es una receta ?>
                                <a href="detalle_receta.php?id=<?php echo htmlspecialchars($result['id']); ?>" class="btn btn-primary">Leer más</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No se encontraron resultados.</p>
        <?php endif; ?>
    </div>
</div><br>

<?php include 'templates/_footer.php'; ?>
</body>
</html>
