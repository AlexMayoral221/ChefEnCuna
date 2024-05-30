<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    header('Location: login.php');
    exit();
}

include 'templates/_header.php';

// Verifica si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recibe los datos del formulario
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo = $_POST['correo'];
    $genero = $_POST['genero'];

    // Conexión a la base de datos
    $host = "localhost";
    $user = "root";
    $password = "";
    $db = "chefencuna";

    $conn = new mysqli($host, $user, $password, $db);
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    // Actualiza los datos del usuario en la base de datos
    $usuario_id = $_SESSION['usuario_id'];
    $sql_actualizar = "UPDATE usuarios SET nombre = '$nombre', apellido = '$apellido', correo = '$correo', genero = '$genero' WHERE id = $usuario_id";

    if ($conn->query($sql_actualizar) === TRUE) {
        // Éxito al actualizar los datos
        $success = "¡Perfil actualizado con éxito!";
    } else {
        // Error al actualizar los datos
        $error = "Error al actualizar el perfil: " . $conn->error;
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - ChefEnCuna</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
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
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 0px 10px 0px rgba(0,0,0,0.2);
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center">Editar Perfil</h1>
    <!-- Mensajes de error y éxito -->
    <?php if (!empty($error)) : ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if (!empty($success)) : ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <!-- Formulario de edición de perfil -->
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="form-group">
            <label for="nombre">Nombre:</label>
            <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo $nombre; ?>" required>
        </div>
        <div class="form-group">
            <label for="apellido">Apellido:</label>
            <input type="text" class="form-control" id="apellido" name="apellido" value="<?php echo $apellido; ?>" required>
        </div>
        <div class="form-group">
            <label for="correo">Correo electrónico:</label>
            <input type="email" class="form-control" id="correo" name="correo" value="<?php echo $correo; ?>" required>
        </div>
        <div class="form-group">
            <label for="genero">Género:</label>
            <select class="form-control" id="genero" name="genero" required>
                <option value="masculino" <?php if ($genero == 'masculino') echo 'selected'; ?>>Masculino</option>
                <option value="femenino" <?php if ($genero == 'femenino') echo 'selected'; ?>>Femenino</option>
                <option value="otro" <?php if ($genero == 'otro') echo 'selected'; ?>>Otro</option>
                <option value="prefiero_no_decir" <?php if ($genero == 'prefiero_no_decir') echo 'selected'; ?>>Prefiero no decir</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
    </form>
</div>
</body>
</html> 