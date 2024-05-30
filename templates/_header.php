<?php
session_start();

// Verificar si hay una sesión iniciada
if(isset($_SESSION['usuario']) || isset($_SESSION['instructor']) || isset($_SESSION['admin'])) {
    $mostrarBotonInicioSesion = false;
} else {
    $mostrarBotonInicioSesion = true;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>ChefEnCuna</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <style>
        .navbar {
            background-color: #FDFD96; 
        }
        .modal-header, h4, .close {
            background-color: #FDFD96;
            color: #333; 
            text-align: center;
            font-size: 30px;
        }
        .modal-body {
            background-color: #FFFDE7; 
            color: #666; 
        }
        .modal-footer {
            background-color: #E1F5FE; 
        }
        .btn-success {
            background-color: #81D4FA; 
            border-color: #81D4FA;
        }
        .btn-success:hover {
            background-color: #29B6F6; 
            border-color: #29B6F6;
        }
        .btn-danger {
            background-color: #FFCCBC; 
            border-color: #FFCCBC;
        }
        .btn-danger:hover {
            background-color: #FFAB91; 
            border-color: #FFAB91;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-custom navbar-expand-lg">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>                        
            </button>
            <a class="navbar-brand" href="index.php">ChefEnCuna</a>
        </div>
        <div class="collapse navbar-collapse" id="myNavbar">
            <ul class="nav navbar-nav">
                <li><a href="Cursos.php">Cursos</a></li>
                <li><a href="Nosotros.php">Sobre Nosotros</a></li>
                <li><a href="#">Comunidad</a></li>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li>
                <form class="navbar-form navbar-left" role="search" action="Buscador.php" method="get">
                    <div class="form-group">
                        <input type="text" class="form-control" placeholder="Buscar en ChefEnCuna" name="query">
                    </div>
                    <button type="submit" class="btn btn-default">Buscar</button>
                </form>
                </li>
                <?php if($mostrarBotonInicioSesion): ?>
                <li class="dropdown">
                    <a href="login.php">
                        <img src="img/login.png" alt="Iniciar sesión" style="width: 25px; height: 25px;">
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
</body>
</html>