<?php
// Conexión a la base de datos
$host = "localhost";
$user = "root";
$password = "";
$db = "chefencuna";
$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Verificar si se ha enviado el formulario de agregar curso
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = $_POST["titulo"];
    $nivel = $_POST["nivel"];
    $duracion = $_POST["duracion"];
    $descripcion = $_POST["descripcion"];
    $fecha_creacion = $_POST["fecha_creacion"];

    // Consulta SQL para insertar el nuevo curso
    $sql = "INSERT INTO cursos (titulo, nivel, duracion, descripcion, fecha_creacion) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $titulo, $nivel, $duracion, $descripcion, $fecha_creacion);

    if ($stmt->execute()) {
        $_SESSION["success_message"] = "Curso agregado correctamente.";
    } else {
        $_SESSION["error_message"] = "Error al agregar el curso: " . $stmt->error;
    }

    $stmt->close();
    header("Location: adminhome.php");
    exit();
}

$conn->close();
?>