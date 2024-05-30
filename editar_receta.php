<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$host = "localhost";
$user = "root";
$password = "";
$db = "chefencuna";
$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $receta_id = $_POST['receta_id'];
    $titulo = $_POST['titulo'];
    $descripcion = $_POST['descripcion'];
    $ingredientes = $_POST['ingredientes'];
    $procedimiento = $_POST['procedimiento'];

    $sql_actualizar_receta = "UPDATE recetas SET titulo = ?, descripcion = ?, ingredientes = ?, procedimiento = ? WHERE id = ?";
    $stmt_actualizar_receta = $conn->prepare($sql_actualizar_receta);
    $stmt_actualizar_receta->bind_param("ssssi", $titulo, $descripcion, $ingredientes, $procedimiento, $receta_id);

    if ($stmt_actualizar_receta->execute()) {
        $_SESSION['success_message'] = "Receta actualizada correctamente.";
    } else {
        $_SESSION['error_message'] = "Error al actualizar la receta: " . $stmt_actualizar_receta->error;
    }

    $stmt_actualizar_receta->close();
    header('Location: adminhome.php');
    exit();
}
?>