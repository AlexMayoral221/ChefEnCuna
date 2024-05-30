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
<title>Editar Curso - ChefEnCuna</title>
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
    </style>
</head>
<body>
<div class="container mt-5">
    <h2>Editar Curso</h2>
    <?php if ($curso): ?>
        <form method="post" action="actualizar_curso.php">
            <input type="hidden" name="curso_id" value="<?php echo $curso['id']; ?>">
            <div class="form-group">
                <label for="titulo">Título:</label>
                <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo htmlspecialchars($curso['titulo']); ?>">
            </div>
            <div class="form-group">
                <label for="nivel">Nivel:</label>
                <input type="text" class="form-control" id="nivel" name="nivel" value="<?php echo htmlspecialchars($curso['nivel']); ?>">
            </div>
            <div class="form-group">
                <label for="requisitos">Requisitos:</label>
                <textarea class="form-control" id="requisitos" name="requisitos" rows="3"><?php echo htmlspecialchars($curso['requisitos']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="duracion">Duración:</label>
                <input type="text" class="form-control" id="duracion" name="duracion" value="<?php echo htmlspecialchars($curso['duracion']); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Actualizar Curso</button>
        </form>
    <?php else: ?>
        <p>Curso no encontrado.</p>
    <?php endif; ?>
</div><br>

<?php include 'templates/_footer.php'; ?>
</body>
</html>
