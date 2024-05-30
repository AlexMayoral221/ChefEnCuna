<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

if(isset($_POST['instructor_id']) && isset($_POST['nueva_contraseña'])){
    $instructor_id = $_POST['instructor_id'];
    $nueva_contraseña = $_POST['nueva_contraseña'];

    // Aquí deberías realizar la conexión a tu base de datos
    $host = "localhost";
    $user = "root";
    $password = "";
    $db = "chefencuna";
    $conn = new mysqli($host, $user, $password, $db);

    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    // Encriptar la nueva contraseña
    $hashed_password = password_hash($nueva_contraseña, PASSWORD_DEFAULT);

    // Consulta SQL para actualizar la contraseña del usuario
    $sql_actualizar_contraseña = "UPDATE instructores SET contraseña = ? WHERE id = ?";
    $stmt_actualizar_contraseña = $conn->prepare($sql_actualizar_contraseña);
    $stmt_actualizar_contraseña->bind_param("si", $hashed_password, $instructor_id);

    // Ejecutar la consulta
    if ($stmt_actualizar_contraseña->execute()) {
        // Contraseña actualizada correctamente
        $_SESSION['success_message'] = "Contraseña actualizada correctamente. La nueva contraseña es: " . $nueva_contraseña;
        header('Location: adminhome.php'); // Redirigir a la página de administrador
        exit();
    } else {
        // Error al actualizar la contraseña
        $_SESSION['error_message'] = "Error al actualizar la contraseña: " . $stmt_actualizar_contraseña->error;
        header('Location: adminhome.php'); // Redirigir a la página de administrador
        exit();
    }

    // Cerrar la conexión y la declaración
    $stmt_actualizar_contraseña->close();
    $conn->close();
} else {
    // Si no se enviaron los datos necesarios, redirigir a la página de administrador
    header('Location: adminhome.php');
    exit();
}
?>