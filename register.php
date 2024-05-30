<?php
require 'bd.php';
include 'templates/_header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo = $_POST['correo'];
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
    $genero = $_POST['genero'];

    try {
        // Preparar la consulta SQL para insertar un nuevo usuario
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, apellido, correo, contraseña, genero) VALUES (?, ?, ?, ?, ?)");
        
        // Ejecutar la consulta con los parámetros
        $stmt->execute([$nombre, $apellido, $correo, $contrasena, $genero]);

        // Redirigir al usuario después de un registro exitoso
        header("Location: login.php");
        exit();
    } catch (PDOException $e) {
        // Manejar errores de la base de datos
        echo "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Registro</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
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
        .registration-form {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-6 col-md-offset-3 registration-form">
                <h2 class="text-center">Registro</h2>
                <form method="post" action="register.php">
                    <div class="form-group">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="apellido">Apellido:</label>
                        <input type="text" id="apellido" name="apellido" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="correo">Correo:</label>
                        <input type="email" id="correo" name="correo" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="contrasena">Contraseña:</label>
                        <input type="password" id="contrasena" name="contrasena" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="genero">Género:</label>
                        <select id="genero" name="genero" class="form-control" required>
                            <option value="masculino">Masculino</option>
                            <option value="femenino">Femenino</option>
                            <option value="prefiero_no_decir">Prefiero no decir</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Registrar</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>