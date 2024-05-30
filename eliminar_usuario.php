<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Verificar si se ha enviado el formulario para eliminar un usuario
if(isset($_POST['usuario_id'])){
    $usuario_id = $_POST['usuario_id'];

    // Aquí deberías realizar la conexión a tu base de datos
    $host = "localhost";
    $user = "root";
    $password = "";
    $db = "chefencuna";
    $conn = new mysqli($host, $user, $password, $db);

    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    // Consulta SQL para eliminar el usuario
    $sql_eliminar_usuario = "DELETE FROM usuarios WHERE id = ?";
    $stmt_eliminar_usuario = $conn->prepare($sql_eliminar_usuario);
    $stmt_eliminar_usuario->bind_param("i", $usuario_id);

    // Ejecutar la consulta
    if ($stmt_eliminar_usuario->execute()) {
        // Usuario eliminado correctamente
        $_SESSION['success_message'] = "Usuario eliminado correctamente.";
        header('Location: adminhome.php'); // Redirigir a la página de administrador
        exit();
    } else {
        // Error al eliminar el usuario
        $_SESSION['error_message'] = "Error al eliminar el usuario: " . $stmt_eliminar_usuario->error;
        header('Location: adminhome.php'); // Redirigir a la página de administrador
        exit();
    }

    // Cerrar la conexión y la declaración
    $stmt_eliminar_usuario->close();
    $conn->close();
} else {
    // Si no se envió el ID del usuario, redirigir a la página de administrador
    header('Location: adminhome.php');
    exit();
}
?>