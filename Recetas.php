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
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Georgia', serif;
            color: #007bff;
        }
        .img-responsive {
            height: 200px;
            width: auto;
        }
    </style>
</head>
<body>

<div class="container text-center">
    <h2>Nuestras Recetas</h2>
    <div class="row">
        <?php
        $query = "SELECT * FROM recetas ORDER BY id ASC";
        $result = $pdo->query($query);

        if ($result && $result->rowCount() > 0) {
            foreach ($result as $row) {
                echo '<div class="col-md-4">';
                echo '<img src="img/recetas/' . htmlspecialchars($row['id']) . '.jpg" alt="Imagen de ' . htmlspecialchars($row['titulo']) . '" class="img-responsive">';
                echo '<h3>' . htmlspecialchars($row['titulo']) . '</h3>';
                echo '<a href="detalle_receta.php?id=' . htmlspecialchars($row['id']) . '" class="btn btn-primary">Leer más</a>';
                echo '</div>';
            }
        } else {
            echo "<p>No se encontraron recetas.</p>";
        }
        ?>
    </div>
</div><br>

<?php include 'templates/_footer.php'; ?>
</body>
</html>