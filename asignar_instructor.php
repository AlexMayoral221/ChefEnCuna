<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "chefencuna";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $curso_id = $_POST["curso_id"];
    $instructor_id = $_POST["instructor_id"];

    if (!empty($instructor_id) && !empty($curso_id)) {
        $sql = "UPDATE cursos SET instructor_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $instructor_id, $curso_id);

        if ($stmt->execute()) {
            echo "Instructor asignado correctamente.";
        } else {
            echo "Error al asignar el instructor: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
header("Location: adminhome.php");
?>