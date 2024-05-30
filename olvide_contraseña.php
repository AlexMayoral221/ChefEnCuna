<?php
session_start();

include 'templates/_header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header("Location: confirmacion_envio_correo.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Olvidé mi contraseña</title>
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
    h2 {
      font-family: 'Georgia', serif;
      color: #007bff;
    }
    .form-container {
        max-width: 500px;
        margin: 100px auto;
        padding: 30px;
        background-color: rgba(255, 255, 255, 0.9);
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
    }
    .form-container h2 {
        margin-bottom: 20px;
    }
    .btn-primary {
        width: 100%;
        padding: 10px;
    }
    </style>
</head>
<body>
<div class="container">
    <div class="form-container">
        <h2>Olvidé mi contraseña</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="correo">Correo electrónico:</label>
                <input type="email" class="form-control" id="correo" name="correo" required>
            </div>
            <button type="submit" class="btn btn-primary">Enviar correo de restablecimiento</button>
            <div class="text-center mt-3">
                <a href="login.php">Iniciar sesión</a> | <a href="register.php">Registrarse</a>
            </div>
        </form>
    </div>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</body>
</html>