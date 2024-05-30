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

    if (!empty($curso_id)) {
        $sql = "UPDATE cursos SET instructor_id = NULL WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $curso_id);

        if ($stmt->execute()) {
            echo "Instructor eliminado correctamente.";
        } else {
            echo "Error al eliminar el instructor: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
header("Location: adminhome.php");
?>