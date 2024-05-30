<?php
session_start();

require 'bd.php';
include 'templates/_header.php';

$cursoId = isset($_GET['id']) ? $_GET['id'] : 0;

try {
    $stmt = $pdo->prepare("SELECT c.id, c.titulo, c.nivel, c.requisitos, c.duracion, CONCAT(i.nombre, ' ', i.apellido) AS instructor_nombre FROM cursos c LEFT JOIN instructores i ON c.instructor_id = i.id WHERE c.id = :id");
    $stmt->execute(['id' => $cursoId]);

    $curso = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}

$sessionActive = isset($_SESSION['user_id']); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Detalle del Curso - ChefEnCuna</title>
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
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <?php if ($curso): ?>
        <h2><?php echo htmlspecialchars($curso['titulo']); ?></h2>        
        <div class="card">
            <div class="card-body">

            </div>
        </div>
        
        <div class="modal fade" id="cursoModal" tabindex="-1" role="dialog" aria-labelledby="cursoModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title" id="cursoModalLabel">Detalles del Curso</h3>
                            <a href="inscripcion.php?curso_id=<?php echo $curso['id']; ?>" class="btn btn-success">Inscríbete ahora</a>
                        </div>
                        <div class="modal-body">
                            <h4>Instructor: <?php echo htmlspecialchars($curso['instructor_nombre']); ?></h4>
                            <p><strong>Duración:</strong> <?php echo htmlspecialchars($curso['duracion']); ?></p>
                            <p class="card-text"><small class="text-muted">Nivel: <?php echo htmlspecialchars($curso['nivel']); ?></small></p>
                            <p class="card-text"><strong>Requisitos:</strong></p>
                            <ul>
                                <?php
                                // Separar los requisitos por saltos de línea y convertirlos en elementos de lista
                                $requisitos = explode("\n", $curso['requisitos']);
                                foreach ($requisitos as $requisito) {
                                    echo "<li>" . htmlspecialchars(trim($requisito)) . "</li>";
                                }
                                ?>
                            </ul>
                            <video width="100%" controls>
                                <source src="path_to_video_file" type="video/mp4">
                                Tu navegador no soporta vídeos HTML5.
                            </video>
                        </div>
                    </div>
                </div>
            </div>
    
        <?php else: ?>
            <p>Curso no encontrado.</p>
        <?php endif; ?>
    </div>

<script>
    $(document).ready(function() {
        if (!<?php echo json_encode($sessionActive); ?>) {
            $('#cursoModal').modal('show');
        }
    });
</script>

<?php include 'templates/_footer.php'; ?>
</body>
</html>
