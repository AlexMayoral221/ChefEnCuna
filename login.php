<?php
session_start();

include 'templates/_header.php';

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "chefencuna";

$conn = new mysqli($servername, $username, $password, $dbname);

if(isset($_SESSION['usuario_id'])) {
    if($_SESSION['rol'] == "admin") {
        header("Location: adminhome.php");
    } elseif($_SESSION['rol'] == "instructor") {
        header("Location: instructorhome.php");
    } elseif($_SESSION['rol'] == "usuario") {
        header("Location: userhome.php");
    }
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['correo'];
    $contraseña = $_POST['contraseña'];

    // Consultar tabla de administradores
    $sql = "SELECT id, nombre FROM administradores WHERE correo = '$correo' AND contraseña = '$contraseña'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['usuario_id'] = $row['id'];
        $_SESSION['rol'] = "admin";
        header("Location: adminhome.php");
        exit;
    }

    // Consultar tabla de instructores
    $sql = "SELECT id, nombre FROM instructores WHERE correo = '$correo' AND contraseña = '$contraseña'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['usuario_id'] = $row['id'];
        $_SESSION['rol'] = "instructor";

        // Agregar el código para almacenar el nombre del instructor en la sesión
        $_SESSION['nombre_instructor'] = $row['nombre'];

        header("Location: instructorhome.php");
        exit;
    }

    // Consultar tabla de usuarios
    $sql = "SELECT id, nombre FROM usuarios WHERE correo = '$correo' AND contraseña = '$contraseña'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['usuario_id'] = $row['id'];
        $_SESSION['rol'] = "usuario";
        header("Location: userhome.php");
        exit;
    }

    echo "Correo electrónico o contraseña incorrectos.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión</title>
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
        <h2>Iniciar sesión</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="correo">Correo electrónico:</label>
                <input type="email" class="form-control" id="correo" name="correo" required>
            </div>
            <div class="form-group">
                <label for="contraseña">Contraseña:</label>
                <input type="password" class="form-control" id="contraseña" name="contraseña" required>
            </div>
            <button type="submit" class="btn btn-primary">Iniciar sesión</button>
            <div class="text-center mt-3">
                <a href="register.php">Registrarse</a> | <a href="olvide_contraseña.php">Olvidé mi contraseña</a>
            </div>
        </form>
    </div>
</div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</body>
</html>