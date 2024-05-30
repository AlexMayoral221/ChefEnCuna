<?php
session_start();

require 'bd.php';
include 'templates/_header.php';
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
    .container {
        max-width: 1500px;
        margin: auto;
        background-color: #fff;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .recipe-info {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }
    .recipe-image {
        max-width: 80%;
        height: auto;
        border-radius: 8px;
        margin-right: 0px; 
    }
    .recipe-text {
        flex-grow: 1;
    }
    .recipe-title {
        color: #2980B9;
        margin-bottom: 15px;
    }
    .recipe-description {
        font-size: 16px;
        margin-bottom: 20px;
        line-height: 1.6;
    }
    ul.recipe-ingredients, ol.recipe-method {
        padding-left: 20px;
    }
    ul.recipe-ingredients li, ol.recipe-method li {
        margin-bottom: 10px;
    }
    h3 {
        color: #27AE60;
        margin-top: 30px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    table, th, td {
        border: 1px solid #ddd;
    }
    th, td {
        padding: 15px;
        text-align: left;
    }
    th {
        background-color: #f2f2f2;
    }
</style>
</head>
<body>

<div class="container">
    <?php
    if(isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id_receta = $_GET['id'];
        
        $query = "SELECT * FROM recetas WHERE id = :id";
        $statement = $pdo->prepare($query);
        $statement->execute(['id' => $id_receta]);
        $receta = $statement->fetch(PDO::FETCH_ASSOC);

        if($receta) {
            echo "<div class='recipe-info'>";
            echo "<div class='recipe-image'>";
            echo "<img src='img/recetas/" . htmlspecialchars($receta['id']) . ".jpg' class='recipe-image'>";
            echo "</div>";
            echo "<div class='recipe-text'>";
            echo "<h2 class='recipe-title'>" . htmlspecialchars($receta['titulo']) . "</h2>";
            echo "<div class='recipe-description'>" . nl2br(htmlspecialchars($receta['descripcion'])) . "</div>";
            echo "<form action='guardar_receta.php' method='post'>";
            echo "<input type='hidden' name='id_receta' value='" . htmlspecialchars($receta['id']) . "'>";
            echo "<button type='submit' class='btn btn-success'>Guardar receta</button>";
            echo "</form>";
            echo "</div>"; 
            echo "</div>"; 
            echo "<div class='recipe-details'>";
            echo "<table>";
            echo "<tr><th>Ingredientes</th><th>Procedimiento</th></tr>";
            echo "<tr>";

            echo "<td><ul class='recipe-ingredients'>";
            $ingredientes = explode("\n", htmlspecialchars($receta['ingredientes']));
            foreach ($ingredientes as $ingrediente) {
                echo "<li>" . $ingrediente . "</li>";
            }
            echo "</ul></td>";

            echo "<td><ol class='recipe-method'>";
            $procedimientos = explode("\n", htmlspecialchars($receta['procedimiento']));
            foreach ($procedimientos as $paso) {
                echo "<li>" . $paso . "</li>";
            }
            echo "</ol></td>";

            echo "</tr>";
            echo "</table>";
            echo "</div>"; 
        } else {
            echo "<p>Receta no encontrada.</p>";
        }
    } else {
        echo "<p>ID de receta inválido.</p>";
    }
    ?>
</div><br>

<?php include 'templates/_footer.php'; ?>
</body>
</html>