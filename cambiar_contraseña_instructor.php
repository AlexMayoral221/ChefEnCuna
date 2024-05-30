<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["instructor_id"]) && isset($_POST["nueva_contraseña"])) {
    $instructorId = $_POST["instructor_id"];
    $nuevaContraseña = $_POST["nueva_contraseña"];
    
    // Configuración de la conexión a la base de datos
    $host = "localhost";
    $user = "root";
    $password = "";
    $db = "chefencuna";

    $conn = new mysqli($host, $user, $password, $db);
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    // Hash de la nueva contraseña antes de almacenarla
    $hashed_password = password_hash($nuevaContraseña, PASSWORD_DEFAULT);

    // Actualizar la contraseña del instructor en la base de datos
    $sql = "UPDATE instructores SET contraseña = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $hashed_password, $instructorId); 
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['success_message'] = "La contraseña del instructor ha sido cambiada correctamente.";
    } else {
        $_SESSION['error_message'] = "Error al intentar cambiar la contraseña del instructor.";
    }

    $stmt->close();
    $conn->close();
}

// Redirigir de vuelta a la página de administrador
header("Location: adminhome.php");
exit;
?>