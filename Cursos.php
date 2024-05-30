<?php
session_start();

require 'bd.php';
include 'templates/_header.php';

if (isset($_GET['id'])) {
    $cursoId = $_GET['id'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM cursos WHERE id = ?");
        $stmt->execute([$cursoId]);
        $curso = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(Exception $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    try {
        $stmt = $pdo->prepare("SELECT id, titulo, descripcion, nivel, fecha_creacion FROM cursos");
        $stmt->execute();
        $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        .card-img-top {
            width: 100%; 
            height: 200px; 
            object-fit: cover; 
        }
        .col-md-4 {
            margin-bottom: 20px; 
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <h2>Nuestros Cursos</h2>
    <div class="row">
        <?php if (isset($curso)): ?>
            <div class="col-md-12">
                <div class="card">
                    <img class="card-img-top" src="img/cursos/<?php echo htmlspecialchars($curso['id']); ?>.jpg" alt="Imagen del Curso">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($curso['titulo']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($curso['descripcion']); ?></p>
                        <p class="card-text"><small class="text-muted">Nivel: <?php echo htmlspecialchars($curso['nivel']); ?></small></p>
                    </div>
                </div>
            </div>
        <?php elseif (!empty($cursos)): foreach ($cursos as $curso): ?>
            <div class="col-md-4">
                <div class="card">
                    <img class="card-img-top" src="img/cursos/<?php echo htmlspecialchars($curso['id']); ?>.jpg" alt="Imagen del Curso">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($curso['titulo']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($curso['descripcion']); ?></p>
                        <p class="card-text"><small class="text-muted">Nivel: <?php echo htmlspecialchars($curso['nivel']); ?></small></p>
                        <a href="detalle_curso.php?id=<?php echo htmlspecialchars($curso['id']); ?>" class="btn btn-primary">Más información</a>
                    </div>
                </div>
            </div>
        <?php endforeach; else: ?>
            <p>No se encontraron cursos.</p>
        <?php endif; ?>
    </div>
</div><br>
<?php include 'templates/_footer.php'; ?>
</body>
</html>