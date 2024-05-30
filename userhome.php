<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    header('Location: login.php');
    exit();
}

include 'templates/_header.php';

if (isset($_POST['cerrar_sesion'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

// Conexión a la base de datos
$host = "localhost";
$user = "root";
$password = "";
$db = "chefencuna";

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Consulta para obtener el nombre del usuario
$usuario_id = $_SESSION['usuario_id'];
$sql_nombre_usuario = "SELECT CONCAT(nombre, ' ', apellido) AS nombre_completo FROM usuarios WHERE id = $usuario_id";
$result_nombre_usuario = $conn->query($sql_nombre_usuario);
$row_nombre_usuario = $result_nombre_usuario->fetch_assoc();
$nombre_completo = $row_nombre_usuario['nombre_completo'];

// Consulta para obtener los datos del usuario
$usuario_id = $_SESSION['usuario_id'];
$sql_datos_usuario = "SELECT nombre, apellido, correo, genero FROM usuarios WHERE id = $usuario_id";
$result_datos_usuario = $conn->query($sql_datos_usuario);
if ($result_datos_usuario->num_rows > 0) {
    $row_datos_usuario = $result_datos_usuario->fetch_assoc();
    $nombre = $row_datos_usuario['nombre'];
    $apellido = $row_datos_usuario['apellido'];
    $correo = $row_datos_usuario['correo'];
    $genero = $row_datos_usuario['genero'];
} else {
    $nombre = "Nombre no encontrado";
    $apellido = "Apellido no encontrado";
    $correo = "Correo no encontrado";
    $genero = "Género no encontrado";
}

$imagen_perfil = "img/usuarios/" . $nombre_completo . ".jpg";

// Verificar si el archivo de imagen existe
if (!file_exists($imagen_perfil)) {
    // Si la imagen no existe, utilizar una imagen por defecto
    $imagen_perfil = "img/usuarios/default.jpg";
}

$conn->close();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Home - ChefEnCuna</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
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
<div class="container">
    <h1 class="text-center">¡Bienvenido, <?php echo $nombre_completo; ?>!</h1>
    <div class="text-center mt-4">
        <form method="post" action="">
            <button type="submit" name="cerrar_sesion" class="btn btn-danger mt-3">Cerrar Sesión</button>
        </form>
    </div><br>

    <div class="text-center mt-4">
        <!-- Botón para ver los datos -->
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#perfilModal">Ver Datos</button>
        
        <!-- Botón para editar el perfil -->
        <a href="editar_perfil.php" class="btn btn-success ml-3">Editar Perfil</a>
    </div>
</div>


<!-- Modal para mostrar los datos del perfil -->
<div class="modal fade" id="perfilModal" tabindex="-1" role="dialog" aria-labelledby="perfilModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="perfilModalLabel">Datos del Perfil</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <img src="<?php echo $imagen_perfil; ?>" alt="Foto de Perfil" class="img-thumbnail" style="max-width: 200px;">
                    </div>
                    <div class="col-md-8">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td><strong>Nombre:</strong></td>
                                    <td><?php echo $nombre; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Apellido:</strong></td>
                                    <td><?php echo $apellido; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Correo:</strong></td>
                                    <td><?php echo $correo; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Género:</strong></td>
                                    <td><?php echo $genero; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

</div>
</body>
</html>
