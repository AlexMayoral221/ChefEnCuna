<?php
session_start();

// Verificar si se recibió una solicitud POST y si se recibió el ID del instructor a eliminar
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["instructor_id"])) {
    $instructorId = $_POST["instructor_id"];
    
    // Configuración de la conexión a la base de datos
    $host = "localhost";
    $user = "root";
    $password = "";
    $db = "chefencuna";

    $conn = new mysqli($host, $user, $password, $db);
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    // Eliminar el instructor de la base de datos
    $sql = "DELETE FROM instructores WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $instructorId); 
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['success_message'] = "El instructor ha sido eliminado correctamente.";
    } else {
        $_SESSION['error_message'] = "Error al intentar eliminar al instructor.";
    }

    $stmt->close();
    $conn->close();
}

// Redirigir de vuelta a la página de administrador
header("Location: adminhome.php");
exit;
?>
