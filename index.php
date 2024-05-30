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
  </style>
</head>
<body>
<div class="container text-center">
    <h2>Recetas Destacadas</h2>
    <div class="row">
        <?php
        $query = "SELECT * FROM recetas ORDER BY id ASC LIMIT 3";

        if (isset($pdo) && $pdo instanceof PDO) {
            $result = $pdo->query($query);
            if ($result && $result->rowCount() > 0) {
                foreach ($result as $row) {
                    echo '<div class="col-md-4">';
                    echo '<img src="img/recetas/' . htmlspecialchars($row['id']) . '.jpg" alt="Imagen de ' . htmlspecialchars($row['titulo']) . '" style="width:100%;max-height:200px;">';
                    echo '<h3>' . htmlspecialchars($row['titulo']) . '</h3>';
                    echo '<a href="detalle_receta.php?id=' . htmlspecialchars($row['id']) . '" class="btn btn-primary">Leer más</a>';
                    echo '</div>';
                }
            } else {
                echo "<p>No se encontraron recetas.</p>";
            }
        } else {
            echo "<p>La conexión a la base de datos no está configurada correctamente.</p>";
        }
        ?>
        
    </div>
    <div class="col-12 text-center espacio-superior">
        <a href="Recetas.php" class="btn-ver-mas">Ver más recetas</a>
    </div>
</div>
  
<div class="container text-center">    
  <h3>Descubre el mundo de la cocina con ChefEnCuna</h3><br>
  <div class="row">
    <div class="col-sm-4">
      <img src="img/Cocinero1.jpg" class="img-responsive img-resize" alt="Explora nuestros cursos">
      <p>Explora nuestros cursos</p>
    </div>
    <div class="col-sm-4"> 
      <img src="img/Cocinero2.jpg" class="img-responsive img-resize" alt="Aprende nuevas recetas">
      <p>Aprende nuevas recetas</p>    
    </div>
    <div class="col-sm-4">
      <div class="well">
        <p>Únete a nuestra comunidad de entusiastas de la cocina.</p>
      </div>
      <div class="well">
        <p>Amplía tus habilidades culinarias a tu propio ritmo.</p>
      </div>
    </div>
  </div>
</div><br>

<?php include 'templates/_footer.php'; ?>
</body>
</html>