<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'instructor') {
        header('Location: login.php');
        exit();
    }

    $titulo = $_POST["titulo"];
    $descripcion = $_POST["descripcion"];
    $ingredientes = $_POST["ingredientes"];
    $instrucciones = $_POST["instrucciones"];

    // Configuración de conexión a la base de datos
    $host = "localhost";
    $user = "root";
    $password = "";
    $db = "chefencuna";

    $conn = new mysqli($host, $user, $password, $db);
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    $sql = "INSERT INTO recetas (titulo, descripcion, ingredientes, procedimiento) VALUES ('$titulo', '$descripcion', '$ingredientes', '$instrucciones')";
    if ($conn->query($sql) === TRUE) {
        // Redirigir de vuelta a la página principal
        header('Location: instructorhome.php');
        exit();
    } else {
        echo "Error al guardar la receta: " . $conn->error;
    }
} else {
    header('Location: instructorhome.php');
    exit();
}
?>
